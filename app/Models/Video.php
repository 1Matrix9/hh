<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;
    protected $fillable = [
        'section_id',
        'title',
        'duration',
        'video_url',
        'order_index',
    ];
    public function section() {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }
    
}
