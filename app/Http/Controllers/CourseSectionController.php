<?php

namespace App\Http\Controllers;

use App\Models\CourseSection;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CourseSectionController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     */
    public function index($course_id)
    {
        $courseSections = CourseSection::where('course_id', $course_id)->orderBy('order_index')->get();
        if($courseSections->isEmpty()) {
            return $this->error('No course sections found for this course', 404);
        }
        return $this->ok('Course sections retrieved successfully', [
            'course_sections' => $courseSections
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $course_id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order_index' => 'required|integer',
        ]);
        $validated['course_id'] = $course_id;
        $courseSection = CourseSection::create($validated);
        return $this->ok('Course section created successfully', [
            'course_section' => $courseSection
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show($course_id, $id)
    {
        $courseSection = CourseSection::where('course_id', $course_id)->find($id);
        if (!$courseSection) {
            return $this->error('Course section not found', 404);
        }
        return $this->ok('Course section retrieved successfully', [
            'course_section' => $courseSection
        ]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CourseSection $courseSection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $course_id, $id)
    {
        $courseSection = CourseSection::where('course_id', $course_id)->find($id);
        if (!$courseSection) {
            return $this->error('Course section not found', 404);
        }
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'order_index' => 'sometimes|required|integer',
        ]);
        $courseSection->update($validated);
        return $this->ok('Course section updated successfully', [
            'course_section' => $courseSection
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($course_id, $id)
    {
        $courseSection = CourseSection::where('course_id', $course_id)->find($id);
        if (!$courseSection) {
            return $this->error('Course section not found', 404);
        }
        $courseSection->delete();
        return $this->ok('Course section deleted successfully');
    }
}
