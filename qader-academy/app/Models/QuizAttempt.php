<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $fillable = [
        'student_id',
        'quiz_id',
        'enrollment_id',
        'answers',
        'score',
        'max_score',
        'status',
        'graded_by',
        'grader_feedback',
        'graded_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'decimal:2',
        'max_score' => 'integer',
        'graded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (QuizAttempt $attempt) {
            $attempt->answers ??= [];
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function answerItems(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function getFeedbackAttribute(): ?string
    {
        return $this->grader_feedback;
    }
}
