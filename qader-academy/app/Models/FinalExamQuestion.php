<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalExamQuestion extends Model
{
    protected $fillable = [
        'final_exam_id',
        'question_en',
        'question_ar',
        'type',
        'options',
        'correct_answer_en',
        'correct_answer_ar',
        'points',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
        'order' => 'integer',
    ];

    public function getCorrectAnswerAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? ($this->correct_answer_ar ?: $this->correct_answer_en)
            : ($this->correct_answer_en ?: $this->correct_answer_ar);
    }

    public function finalExam(): BelongsTo
    {
        return $this->belongsTo(FinalExam::class);
    }
}
