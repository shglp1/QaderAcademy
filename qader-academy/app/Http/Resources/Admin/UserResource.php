<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status, // active, suspended
            'created_at' => $this->created_at->toIso8601String(),
            'profile' => $this->when($this->studentProfile || $this->trainerProfile, function () {
                return $this->studentProfile ?? $this->trainerProfile;
            }),
        ];
    }
}
