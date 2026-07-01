<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Course extends Model
{
    use Searchable;

    protected $fillable = [
        'trainer_id',
        'category_id',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'goal_ar',
        'goal_en',
        'price',
        'duration_minutes',
        'status', // draft, pending, published, rejected
        'rejection_reason',
        'year',
        'semester', // first, second
        'intro_video_url',
        'rating_average',
        'rating_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
    ];

    /**
     * Get the indexable data array for the model.
     * Bilingual search across title and description.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
        ];
    }

    public function getTitleAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? ($this->title_ar ?: $this->title_en)
            : ($this->title_en ?: $this->title_ar);
    }

    public function getDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? ($this->description_ar ?: $this->description_en)
            : ($this->description_en ?: $this->description_ar);
    }

    public function getGoalAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? ($this->goal_ar ?: $this->goal_en)
            : ($this->goal_en ?: $this->goal_ar);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function finalExam(): HasMany
    {
        return $this->hasMany(FinalExam::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function qaThreads(): HasMany
    {
        return $this->hasMany(QAThread::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot('progress_percentage', 'completed_at')
            ->withTimestamps();
    }
}
