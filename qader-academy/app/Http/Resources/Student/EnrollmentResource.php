<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'progress_percentage' => $this->progress_percentage,
            'enrolled_at' => $this->created_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'course' => [
                'id' => $this->course->id,
                'title' => $this->course->title,
                'thumbnail' => $this->course->thumbnail,
                'trainer_name' => $this->course->trainer->name,
            ],
        ];
    }
}
