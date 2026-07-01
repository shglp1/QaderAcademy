<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'title_ar' => $this->title_ar,
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'file_url' => $this->getFileUrl(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'course' => $this->whenLoaded('course', function () {
                return new CourseResource($this->course);
            }),
        ];
    }
    
    protected function getFileUrl()
    {
        // Generate signed URL for S3 file
        return \Storage::disk('s3')->temporaryUrl(
            $this->file_path,
            now()->addMinutes(5)
        );
    }
}
