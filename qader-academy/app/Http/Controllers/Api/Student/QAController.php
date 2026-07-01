<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\QAThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QAController extends Controller
{
    /**
     * Display list of Q&A threads for the authenticated student.
     * GET /api/student/qa-threads
     */
    public function index()
    {
        $threads = QAThread::with(['student', 'chapter.course', 'trainerResponse'])
            ->where('student_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'threads' => $threads,
        ]);
    }

    /**
     * Store a new Q&A thread.
     * POST /api/student/qa-threads
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'chapter_id' => 'required|exists:chapters,id',
            'question' => 'required|string|max:2000',
        ]);

        $chapter = \App\Models\Chapter::findOrFail($validated['chapter_id']);
        
        // Verify student is enrolled in the course
        $enrollment = \App\Models\Enrollment::where('student_id', Auth::id())
            ->where('course_id', $chapter->course_id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => __('messages.enrollment_required'),
            ], 403);
        }

        $thread = QAThread::create([
            'student_id' => Auth::id(),
            'chapter_id' => $validated['chapter_id'],
            'question' => $validated['question'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => __('messages.question_submitted'),
            'thread' => $thread->fresh(['student', 'chapter.course']),
        ], 201);
    }

    /**
     * Display a specific Q&A thread.
     * GET /api/student/qa-threads/{thread}
     */
    public function show(QAThread $thread)
    {
        // Ensure student can only view their own threads
        if ($thread->student_id !== Auth::id()) {
            return response()->json([
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $thread->load(['student', 'chapter.course', 'trainerResponse']);

        return response()->json([
            'thread' => $thread,
        ]);
    }
}
