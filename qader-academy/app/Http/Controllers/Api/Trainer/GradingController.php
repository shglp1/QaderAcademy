<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\FinalExamAttempt;
use App\Http\Requests\Trainer\GradeAttemptRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GradingController extends Controller
{
    /**
     * Display grading queue (pending written attempts for trainer's courses).
     */
    public function index()
    {
        $trainerId = Auth::id();
        
        // Get all course IDs for this trainer
        $courseIds = \App\Models\Course::where('trainer_id', $trainerId)->pluck('id');
        
        // Get pending quiz attempts (written questions)
        $pendingQuizAttempts = QuizAttempt::whereHas('quiz.chapter.course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->where('status', 'pending_review')
            ->with(['student', 'quiz.chapter.course'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get pending final exam attempts
        $pendingFinalExamAttempts = FinalExamAttempt::whereHas('finalExam.course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->where('status', 'pending_review')
            ->with(['student', 'finalExam.course'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'quiz_attempts' => $pendingQuizAttempts,
            'final_exam_attempts' => $pendingFinalExamAttempts,
            'total_pending' => $pendingQuizAttempts->count() + $pendingFinalExamAttempts->count(),
        ]);
    }

    public function queue()
    {
        return $this->index();
    }

    /**
     * Grade a quiz attempt.
     */
    public function grade(GradeAttemptRequest $request, $attemptId)
    {
        $validated = $request->validated();
        $type = $request->input('type', 'quiz');
        
        DB::beginTransaction();
        try {
            if ($type === 'quiz') {
                $attempt = QuizAttempt::findOrFail($attemptId);
                
                // Verify ownership via policy
                $this->authorize('grade', $attempt);
                
                $attempt->update([
                    'score' => $validated['score'],
                    'grader_feedback' => $validated['feedback'] ?? null,
                    'graded_by' => Auth::id(),
                    'graded_at' => now(),
                    'status' => 'graded',
                ]);
                
                // Recalculate progress
                $enrollment = $attempt->enrollment ?? \App\Models\Enrollment::where('student_id', $attempt->student_id)
                    ->where('course_id', $attempt->quiz->chapter->course_id)
                    ->first();

                if ($enrollment) {
                    $progressService = new \App\Services\ProgressService();
                    $progressService->recalculateProgress($enrollment);
                }
                
                // Notify student
                $attempt->student->notify(new \App\Notifications\GradePosted($attempt));
                
                DB::commit();
                
                return response()->json([
                    'message' => __('messages.attempt_graded'),
                    'attempt' => $attempt->fresh(['student', 'grader', 'answerItems.question']),
                ]);
                
            } elseif ($type === 'final_exam') {
                $attempt = FinalExamAttempt::findOrFail($attemptId);
                
                // Verify ownership via policy
                $this->authorize('grade', $attempt);
                
                $attempt->update([
                    'score' => $validated['score'],
                    'grader_feedback' => $validated['feedback'] ?? null,
                    'graded_by' => Auth::id(),
                    'graded_at' => now(),
                    'status' => 'graded',
                ]);
                
                // Recalculate progress
                if ($attempt->enrollment) {
                    $progressService = new \App\Services\ProgressService();
                    $progressService->recalculateProgress($attempt->enrollment);
                }
                
                // Notify student
                $attempt->student->notify(new \App\Notifications\GradePosted($attempt));
                
                DB::commit();
                
                return response()->json([
                    'message' => __('messages.attempt_graded'),
                    'attempt' => $attempt->fresh(['student']),
                ]);
            }
            
            return response()->json([
                'message' => __('messages.invalid_attempt_type'),
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => __('messages.error_grading_attempt'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get Q&A threads for trainer's courses.
     */
    public function qaThreads()
    {
        $trainerId = Auth::id();
        
        $threads = \App\Models\QAThread::whereHas('chapter.course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->with(['student', 'chapter.course', 'replies'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'threads' => $threads,
        ]);
    }
    
    /**
     * Reply to a Q&A thread.
     */
    public function answerQA(\App\Models\QAThread $thread, \App\Http\Requests\Trainer\AnswerQARequest $request)
    {
        // Verify the thread belongs to trainer's course
        $this->authorize('reply', $thread);
        
        $validated = $request->validated();
        
        $reply = \App\Models\QAReply::create([
            'qa_thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'answer' => $validated['answer'],
            'answer_ar' => $validated['answer_ar'] ?? null,
        ]);
        
        // Notify student
        $thread->student->notify(new \App\Notifications\QuestionAnswered($thread));
        
        return response()->json([
            'message' => __('messages.reply_posted'),
            'reply' => $reply->fresh(['user']),
        ], 201);
    }
}
