<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    protected $fillable = [
        'chapter_id',
        'title_en',
        'title_ar',
        'video_url',
        'duration_seconds',
        'order',
        'is_intro_video',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'order' => 'integer',
        'is_intro_video' => 'boolean',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, through: 'chapter');
    }
}
