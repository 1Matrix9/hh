<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    use HasFactory;
    /**
     * Leaderboards table only contains an `updated_at` column (no created_at),
     * so disable Eloquent's automatic timestamps to avoid inserting created_at.
     */
    public $timestamps = false;

    protected $guarded = [];
    
    protected $fillable = [
        'user_id',
        'points',
        'rank',
    ]; 
}
