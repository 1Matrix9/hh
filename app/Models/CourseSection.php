<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'order_index',
    ];
    public function course() {
        return $this->belongsTo(Course::class);
    }

    public function videos() {
        return $this->hasMany(Video::class, 'section_id')->orderBy('order_index');
    }
    
}
