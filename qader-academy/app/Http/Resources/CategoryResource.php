<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'type' => $this->type,
            'year' => $this->year,
            'semester' => $this->semester,
            'is_active' => $this->is_active,
            'courses_count' => $this->whenCounted('courses'),
        ];
    }
}
