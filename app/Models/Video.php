<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;
    protected $fillable = [
        'section_id', 'title', 'bunny_guid', 'status',
        'duration', 'video_url', 'order_index', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function section() {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }
    
}
