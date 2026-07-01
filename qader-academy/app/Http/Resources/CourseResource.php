<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'description' => app()->getLocale() === 'ar' ? $this->description_ar : $this->description_en,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'goal' => app()->getLocale() === 'ar' ? $this->goal_ar : $this->goal_en,
            'goal_ar' => $this->goal_ar,
            'goal_en' => $this->goal_en,
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'year' => $this->year,
            'semester' => $this->semester,
            'intro_video_url' => $this->intro_video_url,
            'rating_average' => $this->rating_average ?? 0,
            'rating_count' => $this->rating_count ?? 0,
            'trainer' => new TrainerResource($this->whenLoaded('trainer')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'ratings_count' => $this->whenLoaded('ratings', fn() => $this->ratings->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
