<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Video;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     */
    public function index($course_id, $section_id)
    {
        // check if course is enrolled by the user
        $course = Course::where('id', $course_id)->first();
        $user = request()->user();
        if (!$user->courses()->where('course_id', $course_id)->exists()) {
            return $this->error('Course not purchased', 400);
        }
        // check if section belongs to the course
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }
        $videos = Video::where('section_id', $section_id)->orderBy('order_index')->get();
        if($videos->isEmpty()) {
            return $this->error('No videos found for this course section', 404);
        }
        return $this->ok('Videos retrieved successfully', [
            'videos' => $videos
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($course_id, $section_id, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'duration' => 'required|integer',
            'video_url' => 'required|url',
            'order_index' => 'required|integer',
        ]);
        $validated['section_id'] = $section_id;
        // check if section belongs to the course
        $course = Course::where('id', $course_id)->first();
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }
        $video = Video::create($validated);
        return $this->ok('Video created successfully', [
            'video' => $video
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($course_id, $section_id, $id)
    {
        // check if course is enrolled by the user
        $course = Course::where('id', $course_id)->first();
        $user = request()->user();
        if (!$user->courses()->where('course_id', $course_id)->exists()) {
            return $this->error('Course not purchased', 400);
        }
        // check if section belongs to the course
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }
        // check if video belongs to the section
        $video = Video::where('section_id', $section_id)->where('id', $id)->first();
        if (!$video) {
            return $this->error('Video not found in this course section', 404);
        }
        return $this->ok('Video retrieved successfully', [
            'video' => $video
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Video $video)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($course_id, $section_id,$id,Request $request)
    {
        $video = Video::where('section_id', $section_id)->where('id', $id)->first();
        if (!$video) {
            return $this->error('Video not found', 404);
        }
        // check if section belongs to the course
        $course = Course::where('id', $course_id)->first();
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'duration' => 'sometimes|required|integer',
            'video_url' => 'sometimes|required|url',
            'order_index' => 'sometimes|required|integer',
        ]);
        $video->update($validated);
        return $this->ok('Video updated successfully', [
            'video' => $video
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($course_id, $section_id, $id)
    {
        $video = Video::where('section_id', $section_id)->where('id', $id)->first();
        if (!$video) {
            return $this->error('Video not found', 404);
        }
        // check if section belongs to the course
        $course = Course::where('id', $course_id)->first();
        $section = $course->sections()->where('id', $section_id)->first();
        if (!$section) {
            return $this->error('Course section not found in this course', 404);
        }
        $video->delete();
        return $this->ok('Video deleted successfully');
    }
}
