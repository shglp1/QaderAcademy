<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoCompletion;
use App\Services\ProgressService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class VideoProgressController extends Controller
{
    protected ProgressService $progressService;

    public function __construct(ProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Mark a video as completed or update watch progress.
     */
    public function markComplete(Request $request, Video $video)
    {
        $studentId = Auth::id();
        
        // Verify student is enrolled in the course
        $chapter = $video->chapter;
        $course = $chapter->course;
        
        $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'message' => 'You are not enrolled in this course',
            ], 403);
        }

        $validated = $request->validate([
            'watched_seconds' => 'nullable|integer|min:0',
            'is_completed' => 'nullable|boolean',
        ]);

        $watchedSeconds = $validated['watched_seconds'] ?? $video->duration_seconds ?? 0;
        $isCompleted = $validated['is_completed'] ?? ($watchedSeconds >= ($video->duration_seconds ?? 0) * 0.9);

        // Create or update video completion record
        $completion = VideoCompletion::updateOrCreate(
            ['student_id' => $studentId, 'video_id' => $video->id],
            [
                'watched_seconds' => $watchedSeconds,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
            ]
        );

        // Recalculate enrollment progress
        $this->progressService->recalculateProgress($enrollment);

        return response()->json([
            'message' => $isCompleted ? 'Video marked as completed' : 'Progress updated',
            'completion' => [
                'video_id' => $video->id,
                'watched_seconds' => $completion->watched_seconds,
                'is_completed' => $completion->is_completed,
                'completed_at' => $completion->completed_at,
            ],
        ]);
    }

    /**
     * Get video completion status for the current student.
     */
    public function showProgress(Video $video)
    {
        $studentId = Auth::id();
        
        $completion = VideoCompletion::where('student_id', $studentId)
            ->where('video_id', $video->id)
            ->first();

        return response()->json([
            'video_id' => $video->id,
            'watched_seconds' => $completion?->watched_seconds ?? 0,
            'is_completed' => $completion?->is_completed ?? false,
            'completed_at' => $completion?->completed_at,
        ]);
    }
}
