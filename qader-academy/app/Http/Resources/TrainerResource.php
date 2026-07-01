<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'bio' => $this->trainerProfile?->bio,
            'specialization' => $this->trainerProfile?->specialization,
            'approval_status' => $this->trainerProfile?->approval_status,
            'total_courses' => $this->courses()->count(),
            'average_rating' => round($this->courses()->avg('rating_average'), 2) ?? 0,
        ];
    }
}
