<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Course;
use App\Http\Requests\Trainer\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /**
     * Display attachments for the trainer's courses.
     */
    public function index()
    {
        $trainerId = Auth::id();
        
        $attachments = Attachment::whereHas('course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->with(['course'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'attachments' => AttachmentResource::collection($attachments),
        ]);
    }

    /**
     * Store a newly created attachment.
     */
    public function store(StoreAttachmentRequest $request)
    {
        $validated = $request->validated();
        
        // Verify course ownership
        $course = Course::where('trainer_id', Auth::id())
            ->findOrFail($validated['course_id']);
        
        // Handle file upload
        $file = $request->file('file');
        $path = $file->store('attachments', 's3'); // or 'public' disk
        
        $attachment = Attachment::create([
            'course_id' => $course->id,
            'title' => $validated['title'],
            'title_ar' => $validated['title_ar'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);
        
        return response()->json([
            'message' => __('messages.attachment_uploaded'),
            'attachment' => new AttachmentResource($attachment->fresh(['course'])),
        ], 201);
    }

    /**
     * Display the specified attachment.
     */
    public function show(Attachment $attachment)
    {
        $this->authorize('view', $attachment);
        
        return response()->json([
            'attachment' => new AttachmentResource($attachment->load(['course'])),
        ]);
    }

    /**
     * Update the specified attachment.
     */
    public function update(StoreAttachmentRequest $request, Attachment $attachment)
    {
        $this->authorize('update', $attachment);
        
        $validated = $request->validated();
        
        $attachment->update([
            'title' => $validated['title'] ?? $attachment->title,
            'title_ar' => $validated['title_ar'] ?? $attachment->title_ar,
        ]);
        
        // Handle optional file replacement
        if ($request->hasFile('file')) {
            // Delete old file
            Storage::disk('s3')->delete($attachment->file_path);
            
            $file = $request->file('file');
            $path = $file->store('attachments', 's3');
            
            $attachment->update([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }
        
        return response()->json([
            'message' => __('messages.attachment_updated'),
            'attachment' => new AttachmentResource($attachment->fresh(['course'])),
        ]);
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(Attachment $attachment)
    {
        $this->authorize('delete', $attachment);
        
        // Delete file from storage
        Storage::disk('s3')->delete($attachment->file_path);
        
        $attachment->delete();
        
        return response()->json([
            'message' => __('messages.attachment_deleted'),
        ]);
    }
}
