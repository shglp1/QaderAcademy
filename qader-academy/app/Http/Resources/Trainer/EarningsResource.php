<?php

namespace App\Http\Resources\Trainer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trainer_id' => $this->trainer_id,
            'course_id' => $this->course_id,
            'enrollment_id' => $this->enrollment_id,
            'amount' => $this->amount,
            'commission_percentage' => $this->commission_percentage,
            'net_amount' => $this->net_amount,
            'status' => $this->status, // pending, paid, withheld
            'created_at' => $this->created_at->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'course' => $this->whenLoaded('course', fn() => [
                'id' => $this->course->id,
                'title' => $this->course->title,
            ]),
        ];
    }
}
