<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function sections() {
        return $this->hasMany(CourseSection::class)->orderBy('order_index');
    }

    public function users() {
        return $this->belongsToMany(User::class, 'user_courses')
                    ->withPivot('purchase_date', 'progress_percentage');
    }
}
