<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalExamAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'final_exam_attempt_id',
        'question_id',
        'student_answer',
        'is_correct',
        'points_earned',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(FinalExamAttempt::class, 'final_exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(FinalExamQuestion::class, 'question_id');
    }

    public function setAttemptIdAttribute($value): void
    {
        $this->attributes['final_exam_attempt_id'] = $value;
    }
}
