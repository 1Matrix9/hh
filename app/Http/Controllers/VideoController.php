<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Video;
use App\Services\BunnyStreamService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PharIo\Manifest\Library;

class VideoController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the videos in a course section.
     */
    public function index($course_id, $section_id)
    {
        // Ensure user is enrolled
        $user = request()->user();
        if (!$user || !$user->courses()->where('course_id', $course_id)->exists()) {
            return $this->error('Course not purchased', 400);
        }

        // Validate course and section relation
        $course = Course::find($course_id);
        if (!$course) {
            return $this->error('Course not found', 404);
        }

        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }

        $videos = Video::where('section_id', $section_id)
            ->orderBy('order_index')
            ->get();

        if ($videos->isEmpty()) {
            return $this->error('No videos found for this course section', 404);
            // If you prefer 200 with empty array, replace with:
            // return $this->ok('No videos found for this course section', ['videos' => []]);
        }

        return $this->ok('Videos retrieved successfully', [
            'videos' => $videos
        ]);
    }

    /**
     * Store a newly created video and create a Bunny Stream item.
     */
    public function store($course_id, $section_id, Request $request, BunnyStreamService $bunny)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order_index' => 'required|integer',
            
            'duration' => 'sometimes|integer',
            'video_url' => 'sometimes|nullable|url',
        ]);

        // Validate course and section relation
        $course = Course::find($course_id);
        if (!$course) {
            return $this->error('Course not found', 404);
        }
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }

        $validated['section_id'] = (int) $section_id;

        // Create local record first
        $video = Video::create(array_merge($validated, [
            'status' => 'pending',
        ]));

        // Create Bunny video and store GUID/meta
        try {
            // Use course's library_id and api_key if available, otherwise use defaults
            if ($course->library_id && $course->api_key) {
                $bunnyService = new BunnyStreamService($course->library_id, $course->api_key);
            } elseif ($course->library_id) {
                $bunnyService = new BunnyStreamService($course->library_id, config('services.bunny.stream_api_key'));
            } else {
                $bunnyService = $bunny;
            }
            $created = $bunnyService->createVideo($validated['title']); // ['guid','uploadUrl',...]
            $video->update([
                'bunny_guid' => $created['guid'] ?? null,
                'status'     => 'uploading',
                'meta'       => $created,
            ]);
        } catch (\Throwable $e) {
            $video->update(['status' => 'failed']);
            return $this->error('Failed to create Bunny video: ' . $e->getMessage(), 502);
        }

        // Note: Some libraries do not return uploadUrl; upload via PUT to /library/{LIB}/videos/{GUID}
        // Recalculate course total duration and persist
        try {
            $course->total_duration = $course->totalDuration();
            $course->save();
        } catch (\Throwable $e) {
            \Log::warning('Failed to update course total_duration', ['course_id' => $course->id, 'error' => $e->getMessage()]);
        }

        return $this->ok('Video created. Upload to the provided URL using PUT.', [
            'video' => $video,
            'upload_url' => $created['uploadUrl'] ?? null,
            'course_total_duration' => $course->total_duration,
        ]);
    }

    public function upload($course_id, $section_id, $id, Request $request, BunnyStreamService $bunny)
    {
        // Disable execution time limit for large video uploads
        set_time_limit(0);
 
        $data = $request->validate([
            'file' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-matroska,video/webm,video/avi|max:3072000000', // ~3GB
        ]);

        $course = Course::find($course_id);
        if (!$course) return $this->error('Course not found', 404);

        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) return $this->error('Course section not found in this course', 404);

        $video = Video::where('section_id', $section_id)->where('id', $id)->first();
        if (!$video) return $this->error('Video not found', 404);
        if (!$video->bunny_guid) return $this->error('Video not initialized on Bunny', 409);
        
        // 3) Stream to Bunny
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['file'];

        try {
            // Prefer course-specific Bunny credentials when available (same logic as store)
            if ($course->library_id && $course->api_key) {
                $bunnyService = new BunnyStreamService($course->library_id, $course->api_key);
            } elseif ($course->library_id) {
                $bunnyService = new BunnyStreamService($course->library_id, config('services.bunny.stream_api_key'));
            } else {
                $bunnyService = $bunny;
            }

            $bunnyService->uploadBinary($video->bunny_guid, $file->getRealPath());

            $video->status = 'processing';
            $video->save();
        } catch (\Throwable $e) {
            \Log::error('Bunny upload failed', ['video_id' => $video->id, 'guid' => $video->bunny_guid, 'error' => $e->getMessage()]);
            return $this->error('Upload to Bunny failed: ' . $e->getMessage(), 502);
        }

        return $this->ok('File uploaded to Bunny. Processing started.', ['video' => $video]);
    }

    /**
     * Display the specified video with an iframe URL.
     */
    public function show($course_id, $section_id, $id)
    {
        // Ensure user is enrolled
        $user = request()->user();
        if (!$user || !$user->courses()->where('course_id', $course_id)->exists()) {
            return $this->error('Course not purchased', 400);
        }

        // Validate course and section relation
        $course = Course::find($course_id);
        if (!$course) {
            return $this->error('Course not found', 404);
        }
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }

        // Find video in section
        $video = Video::where('section_id', $section_id)
            ->where('id', $id)
            ->first();

        if (!$video) {
            return $this->error('Video not found in this course section', 404);
        }

        // Token (works even if Bunny Player Token Auth is disabled; then it’s just ignored)
        $token = null;
        $libraryId = $course->library_id ?? (string) config('services.bunny.stream_library_id');
        $signingKey = $course->api_key ?? (string) config('services.bunny.signing_key');
        if ($signingKey && $video->bunny_guid) {
            $token = $this->generateBunnyToken($signingKey, $video->bunny_guid, 3600);
        }

        return $this->ok('Video retrieved successfully', [
            'video' => $video,
            'playback' => [
                'library_id' => (string) $libraryId,
                'video_id'   => $video->bunny_guid,
                'token'      => $token,
                'iframe_url' => $this->iframeUrl($video->bunny_guid, $token, $libraryId),
            ],
        ]);
    }

    /**
     * Update the specified video details or refresh status from Bunny.
     */
    public function update($course_id, $section_id, $id, Request $request, BunnyStreamService $bunny)
    {
        // 1) Find the video within the section
        $video = Video::where('section_id', $section_id)->where('id', $id)->first();
        if (!$video) {
            return $this->error('Video not found', 404);
        }

        // 2) Validate course and section relation
        $course = Course::find($course_id);
        if (!$course) {
            return $this->error('Course not found', 404);
        }
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }

        // 3) Validate payload. We support:
        // - title/order_index (local)
        // - refresh_status (pull from Bunny)
        // - remote_bunny_update: { title?: string, is_public?: bool }
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'order_index' => 'sometimes|required|integer|min:0',

            'refresh_status' => 'sometimes|boolean',

            // Optional: update Bunny video itself
            'remote_bunny_update' => 'sometimes|array',
            'remote_bunny_update.title' => 'sometimes|string|max:255',
            'remote_bunny_update.is_public' => 'sometimes|boolean',

            // Optional legacy fields you may still accept
            'duration' => 'sometimes|integer|min:0|nullable',
            'video_url' => 'sometimes|nullable|url',
        ]);

        // 4) Local metadata updates
        if (array_key_exists('title', $data)) {
            $video->title = $data['title'];
        }
        if (array_key_exists('order_index', $data)) {
            $video->order_index = $data['order_index'];
        }
        if (array_key_exists('duration', $data)) {
            $video->duration = $data['duration'];
        }
        if (array_key_exists('video_url', $data)) {
            $video->video_url = $data['video_url'];
        }

        // 5) Optional: update Bunny video metadata (rename, public)
        // Requires bunny_guid to exist
        if (!empty($data['remote_bunny_update']) && $video->bunny_guid) {
            try {
                $payload = [];
                if (array_key_exists('title', $data['remote_bunny_update'])) {
                    $payload['title'] = $data['remote_bunny_update']['title'];
                }
                if (array_key_exists('is_public', $data['remote_bunny_update'])) {
                    $payload['isPublic'] = (bool) $data['remote_bunny_update']['is_public'];
                }

                if (!empty($payload)) {
                    // PATCH the Bunny video
                    $updatedRemote = $bunny->updateVideo($video->bunny_guid, $payload);
                    // Optionally sync title locally if changed
                    if (isset($payload['title'])) {
                        $video->title = $payload['title'];
                    }
                    // Merge meta with the latest from Bunny (if response returned JSON)
                    if (is_array($updatedRemote) && !empty($updatedRemote)) {
                        $video->meta = $updatedRemote;
                    }
                }
            } catch (\Throwable $e) {
                return $this->error('Failed to update Bunny video: ' . $e->getMessage(), 502);
            }
        }

        // 6) Refresh status from Bunny if requested
        if (($data['refresh_status'] ?? false) && $video->bunny_guid) {
            try {
                $details = $bunny->getVideo($video->bunny_guid);
                // Bunny: status 4 = ready
                $video->status = ((int) ($details['status'] ?? 0) === 4) ? 'ready' : 'processing';
                $video->duration = $details['length'] ?? $video->duration;
                $video->meta = $details;
            } catch (\Throwable $e) {
                return $this->error('Failed to refresh Bunny status: ' . $e->getMessage(), 502);
            }
        }

        $video->save();

        return $this->ok('Video updated successfully', [
            'video' => $video
        ]);
    }

    /**
     * Remove the specified video from storage and Bunny (best effort).
     */
    public function destroy($course_id, $section_id, $id, BunnyStreamService $bunny)
    {
        $video = Video::where('section_id', $section_id)->where('id', $id)->first();
        if (!$video) {
            return $this->error('Video not found', 404);
        }

        // Validate course and section relation
        $course = Course::find($course_id);
        if (!$course) {
            return $this->error('Course not found', 404);
        }
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }

       
        if ($video->bunny_guid) {
            try {
                // Prefer course-specific Bunny credentials when available
                if ($course->library_id && $course->api_key) {
                    $bunnyService = new BunnyStreamService($course->library_id, $course->api_key);
                } elseif ($course->library_id) {
                    $bunnyService = new BunnyStreamService($course->library_id, config('services.bunny.stream_api_key'));
                } else {
                    $bunnyService = $bunny;
                }

                $bunnyService->deleteVideo($video->bunny_guid);
            } catch (\Throwable $e) {
                \Log::warning('Bunny delete failed', [
                    'guid' => $video->bunny_guid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $video->delete();

        return $this->ok('Video deleted successfully');
    }

    /**
     * Reorder videos within a section.
     * Body: [{ "id": 10, "order_index": 1 }, { "id": 11, "order_index": 2 }]
     */
    public function reorder($course_id, $section_id, Request $request)
    {
        $items = $request->validate([
            '*.id' => 'required|integer|exists:videos,id',
            '*.order_index' => 'required|integer|min:0',
        ]);
        
       
        $course = Course::find($course_id);
        if (!$course) {
            return $this->error('Course not found', 404);
        }
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }

        DB::transaction(function () use ($items, $section_id) {
            foreach ($items as $it) {
                Video::where('section_id', $section_id)
                    ->where('id', $it['id'])
                    ->update(['order_index' => $it['order_index']]);
            }
        });

        return $this->ok('Order updated successfully');
    }

    /**
     * Create a Bunny Player Token Authentication token.
     * Format: HMAC_SHA256(secret + "/{videoId}" + expiry, secret) + expiry
     */
    protected function generateBunnyToken(string $secret, string $videoId, int $ttlSeconds = 3600): string
    {
        $expires = time() + $ttlSeconds; // epoch seconds
        $path = "/{$videoId}";
        $hash = hash_hmac('sha256', $secret . $path . $expires, $secret);

        $this->lastTokenExpiry = $expires;
        $this->lastTokenHash = $hash;

        return $hash . $expires; 
    }

    /**
     * Build the Bunny iframe URL and append token if provided.
     */
    protected function iframeUrl(?string $videoId, ?string $token, ?string $libraryId = null): ?string
    {
        if (!$videoId) return null;
        $lib = $libraryId ?? (string) config('services.bunny.stream_library_id');
        $url = "https://iframe.mediadelivery.net/embed/{$lib}/{$videoId}";
        if ($token) {
            $url .= "?token={$token}";
        }
        return $url;
    }
}
