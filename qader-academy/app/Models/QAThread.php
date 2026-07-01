<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QAThread extends Model
{
    protected $fillable = [
        'student_id',
        'chapter_id',
        'question_en',
        'question_ar',
        'answer_en',
        'answer_ar',
        'answered_by',
        'is_resolved',
        'answered_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function answerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, through: 'chapter');
    }
}
