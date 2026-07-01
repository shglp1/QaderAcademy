<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinalExam extends Model
{
    protected $fillable = [
        'course_id',
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'passing_score',
        'is_published',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FinalExamQuestion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(FinalExamAttempt::class);
    }
}
