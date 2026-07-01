<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'certificate_number' => $this->certificate_number,
            'verification_code' => $this->verification_code,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'issued_date' => $this->issued_date?->toDateString(),
            'completed_at' => $this->enrollment?->completed_at?->toIso8601String(),
            'download_url' => $this->file_path
                ? route('student.certificates.download', $this->id)
                : null,
            'course' => $this->whenLoaded('enrollment', function () {
                return [
                    'id' => $this->enrollment?->course?->id,
                    'title' => $this->enrollment?->course?->title,
                    'title_en' => $this->enrollment?->course?->title_en,
                    'title_ar' => $this->enrollment?->course?->title_ar,
                ];
            }),
        ];
    }
}
