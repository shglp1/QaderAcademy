<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'order' => $this->order,
            'videos' => VideoResource::collection($this->whenLoaded('videos')),
            'quizzes' => QuizResource::collection($this->whenLoaded('quizzes')),
        ];
    }
}
