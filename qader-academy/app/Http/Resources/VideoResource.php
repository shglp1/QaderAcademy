<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chapter_id' => $this->chapter_id,
            'title' => app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'url' => $this->video_url,
            'video_url' => $this->video_url,
            'duration_seconds' => $this->duration_seconds,
            'order' => $this->order,
            'is_intro' => $this->is_intro_video,
            'is_intro_video' => $this->is_intro_video,
        ];
    }
}
