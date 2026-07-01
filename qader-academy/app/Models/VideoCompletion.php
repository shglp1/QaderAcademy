<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCompletion extends Model
{
    protected $fillable = [
        'student_id',
        'video_id',
        'is_completed',
        'watched_seconds',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'watched_seconds' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
