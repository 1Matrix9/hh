<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ApiResponses;
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        $courses = Course::orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->ok('Courses retrieved successfully', [
            'courses' => $courses
        ]);
    }

    public function show($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return $this->error('Course not found', 404);
        }

        return $this->ok('Course retrieved successfully', [
            'course' => $course
        ]);
    }

    public function purchase(Request $request, $id)
    {
        $user = $request->user();

        // Check if user already purchased the course
        if ($user->courses()->where('course_id', $id)->exists()) {
            return $this->error('Course already purchased', 400);
        }

        if($user->wallet_balance < Course::find($id)->price) {
            return $this->error('Insufficient wallet balance', 400);
        }   

        $user->wallet_balance -= Course::find($id)->price;
        $user->save();

        $course = Course::find($id);

        if (!$course) {
            return $this->error('Course not found', 404);
        }

        // Attach course to user with purchase date
        $user->courses()->attach($id, ['purchase_date' => now(), 'progress_percentage' => 0]);

        return $this->ok('Course purchased successfully');
    }

    public function updateProgress(Request $request, $id)
    {
        $user = $request->user();
        $course = Course::find($id);

        if (!$course) {
            return $this->error('Course not found', 404);
        }

        // Check if user has purchased the course
        if (!$user->courses()->where('course_id', $id)->exists()) {
            return $this->error('Course not purchased', 400);
        }

        $validated = $request->validate([
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ]);

        // Update progress percentage in pivot table
        $user->courses()->updateExistingPivot($id, [
            'progress_percentage' => $validated['progress_percentage']
        ]);

        return $this->ok('Course progress updated successfully');
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:255',
            'description' => 'required|string',
            'total_duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $course = Course::create($validated);

        return $this->ok('Course created successfully', [
            'course' => $course
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $course = Course::find($id);

        if (!$course) {
            return $this->error('Course not found', 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'subtitle' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'total_duration' => 'sometimes|required|integer|min:1',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        $course->update($validated);

        return $this->ok('Course updated successfully', [
            'course' => $course
        ]);
    }

    public function destroy($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return $this->error('Course not found', 404);
        }

        $course->delete();

        return $this->ok('Course deleted successfully');
    }
}
