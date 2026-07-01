<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canSeeAnswers = $user && $user->hasRole(['trainer', 'admin', 'super_admin']);

        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'type' => $this->type,
            'question_text' => app()->getLocale() === 'ar' ? $this->question_ar : $this->question_en,
            'question_en' => $this->question_en,
            'question_ar' => $this->question_ar,
            'options' => collect($this->options ?? [])->values()->map(function ($option, $index) use ($canSeeAnswers) {
                return [
                    'id' => $index,
                    'text' => app()->getLocale() === 'ar'
                        ? ($option['text_ar'] ?? $option['text_en'] ?? '')
                        : ($option['text_en'] ?? $option['text_ar'] ?? ''),
                    'text_en' => $option['text_en'] ?? null,
                    'text_ar' => $option['text_ar'] ?? null,
                    'is_correct' => $canSeeAnswers ? (bool) ($option['is_correct'] ?? false) : null,
                ];
            }),
            'correct_answer' => $canSeeAnswers ? $this->correct_answer : null,
            'points' => $this->points,
        ];
    }
}
