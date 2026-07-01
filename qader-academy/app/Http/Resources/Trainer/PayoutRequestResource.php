<?php

namespace App\Http\Resources\Trainer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trainer_id' => $this->trainer_id,
            'trainer_name' => $this->trainer->name,
            'amount' => $this->amount,
            'status' => $this->status, // pending, approved, rejected
            'payment_method' => $this->payment_method,
            'payment_details' => $this->payment_details,
            'notes' => $this->notes,
            'admin_response' => $this->admin_response,
            'requested_at' => $this->created_at->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'processed_by' => $this->processor?->name,
        ];
    }
}
