<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\CertificateResource;
use App\Http\Resources\CourseResource;
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
            'course' => new CourseResource($this->whenLoaded('course')),
            'payment' => $this->whenLoaded('payment', function () {
                return $this->payment ? [
                    'id' => $this->payment->id,
                    'status' => $this->payment->status,
                    'checkout_url' => $this->payment->gateway_url,
                    'gateway_reference' => $this->payment->gateway_reference,
                ] : null;
            }),
            'certificate' => $this->whenLoaded('certificate', fn() => $this->certificate ? new CertificateResource($this->certificate->loadMissing('enrollment.course')) : null),
        ];
    }
}
