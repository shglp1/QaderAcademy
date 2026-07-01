<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'payment_id',
        'progress_percentage',
        'overall_grade',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'overall_grade' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function finalExamAttempts(): HasMany
    {
        return $this->hasMany(FinalExamAttempt::class);
    }
}
