<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'library_id' => 'string',
        'api_key' => 'string',
        'thumbnail' => 'string',
        'total_duration' => 'float',
    ];
    public function sections() {
        return $this->hasMany(CourseSection::class)->orderBy('order_index');
    }

    /**
     * All videos for this course (through sections).
     */
    public function videos()
    {
        return $this->hasManyThrough(Video::class, CourseSection::class, 'course_id', 'section_id', 'id', 'id');
    }

    /**
     * Total duration in seconds for all videos in the course.
     */
    public function totalDuration(): int
    {
        // Sum durations, treating null as 0
        return (int) $this->videos()->whereNotNull('duration')->sum('duration');
    }

    public function users() {
        return $this->belongsToMany(User::class, 'user_courses')
                    ->withPivot('purchase_date', 'progress_percentage');
    }
}
