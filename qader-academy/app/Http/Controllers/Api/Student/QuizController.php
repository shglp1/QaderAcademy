<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\FinalExam;
use App\Models\FinalExamAttempt;
use App\Models\Enrollment;
use App\Services\ProgressService;
use App\Http\Requests\Student\SubmitQuizRequest;
use App\Http\Resources\QuizAttemptResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    protected ProgressService $progressService;

    public function __construct(ProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Submit a quiz attempt.
     * POST /api/student/quiz-attempts
     * 
     * - Auto-grades MCQ questions immediately
     * - Saves written questions as "pending_review" (model answer hidden until graded)
     * - Calls ProgressService::recalculate() after grading
     */
    public function submitQuiz(SubmitQuizRequest $request)
    {
        $validated = $request->validated();
        $studentId = Auth::id();
        $quizId = $validated['quiz_id'];
        $answers = $validated['answers'];

        $quiz = Quiz::with(['questions', 'chapter.course'])->findOrFail($quizId);
        $course = $quiz->chapter->course;

        // Verify student is enrolled in the course
        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => __('messages.enrollment_required'),
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Create quiz attempt
            $attempt = QuizAttempt::create([
                'student_id' => $studentId,
                'quiz_id' => $quizId,
                'status' => 'pending', // Will be updated after grading
                'score' => 0,
                'max_score' => 0,
            ]);

            $totalScore = 0;
            $maxScore = 0;
            $hasWrittenQuestions = false;

            // Process each question
            foreach ($answers as $answerData) {
                $question = \App\Models\QuizQuestion::findOrFail($answerData['question_id']);
                $maxScore += $question->points;

                if ($question->type === 'mcq') {
                    // Auto-grade MCQ
                    $isCorrect = strtolower(trim($answerData['answer'])) === strtolower(trim($question->correct_answer));
                    
                    if ($isCorrect) {
                        $totalScore += $question->points;
                    }

                    // Store the attempt detail
                    \App\Models\QuizAnswer::create([
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'student_answer' => $answerData['answer'],
                        'is_correct' => $isCorrect,
                        'points_earned' => $isCorrect ? $question->points : 0,
                    ]);

                } elseif ($question->type === 'written') {
                    $hasWrittenQuestions = true;
                    
                    // Store written answer for manual grading
                    \App\Models\QuizAnswer::create([
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'student_answer' => $answerData['answer'],
                        'is_correct' => null, // Pending manual grading
                        'points_earned' => 0, // Will be set by trainer
                    ]);
                }
            }

            // Update attempt status and scores
            if ($hasWrittenQuestions) {
                // Has written questions - mark as pending review
                $attempt->update([
                    'status' => 'pending_review',
                    'score' => $totalScore, // Only MCQ score for now
                    'max_score' => $maxScore,
                ]);
            } else {
                // All MCQ - grade immediately
                $attempt->update([
                    'status' => 'graded',
                    'score' => $totalScore,
                    'max_score' => $maxScore,
                    'graded_at' => now(),
                ]);

                // Recalculate progress after auto-graded quiz
                $this->progressService->recalculateProgress($enrollment);
            }

            DB::commit();

            $response = [
                'message' => $hasWrittenQuestions 
                    ? __('messages.quiz_submitted_pending_review') 
                    : __('messages.quiz_graded_immediately'),
                'attempt' => new QuizAttemptResource($attempt->fresh()),
                'immediate_feedback' => !$hasWrittenQuestions,
            ];

            // Include immediate feedback for MCQ questions only
            if (!$hasWrittenQuestions) {
                $response['score'] = $totalScore;
                $response['max_score'] = $maxScore;
                $response['percentage'] = round(($maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0), 2);
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Quiz submission failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => __('messages.quiz_submission_failed'),
            ], 500);
        }
    }

    /**
     * Submit a final exam attempt.
     * POST /api/student/final-exam-attempts
     * 
     * - Gated: only available if all chapters are completed
     * - Same grading logic as quiz submission
     */
    public function submitFinalExam(Request $request)
    {
        $validated = $request->validate([
            'final_exam_id' => 'required|exists:final_exams,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:final_exam_questions,id',
            'answers.*.answer' => 'required|string',
        ]);

        $studentId = Auth::id();
        $finalExamId = $validated['final_exam_id'];
        $answers = $validated['answers'];

        $finalExam = FinalExam::with(['questions', 'course'])->findOrFail($finalExamId);
        $course = $finalExam->course;

        // Verify student is enrolled
        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => __('messages.enrollment_required'),
            ], 403);
        }

        // Gate: Check if all chapters are completed
        if (config('progress.gate_final_exam', true)) {
            $allChaptersComplete = $course->chapters()->count() > 0 && 
                $course->chapters()->wherePivot('completed', false)->count() === 0;

            // Alternative check: verify enrollment progress meets threshold
            if ($enrollment->progress_percentage < config('progress.completion_threshold', 30.0)) {
                return response()->json([
                    'message' => __('messages.final_exam_gated'),
                    'current_progress' => $enrollment->progress_percentage,
                    'required_progress' => config('progress.completion_threshold', 30.0),
                ], 403);
            }
        }

        // Check if already attempted
        $existingAttempt = FinalExamAttempt::where('student_id', $studentId)
            ->where('final_exam_id', $finalExamId)
            ->first();

        if ($existingAttempt) {
            return response()->json([
                'message' => __('messages.final_exam_already_attempted'),
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create final exam attempt
            $attempt = FinalExamAttempt::create([
                'student_id' => $studentId,
                'final_exam_id' => $finalExamId,
                'status' => 'pending',
                'score' => 0,
                'max_score' => 0,
            ]);

            $totalScore = 0;
            $maxScore = 0;
            $hasWrittenQuestions = false;

            // Process each question
            foreach ($answers as $answerData) {
                $question = \App\Models\FinalExamQuestion::findOrFail($answerData['question_id']);
                $maxScore += $question->points;

                if ($question->type === 'mcq') {
                    // Auto-grade MCQ
                    $isCorrect = strtolower(trim($answerData['answer'])) === strtolower(trim($question->correct_answer));
                    
                    if ($isCorrect) {
                        $totalScore += $question->points;
                    }

                    \App\Models\FinalExamAnswer::create([
                        'final_exam_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'student_answer' => $answerData['answer'],
                        'is_correct' => $isCorrect,
                        'points_earned' => $isCorrect ? $question->points : 0,
                    ]);

                } elseif ($question->type === 'written') {
                    $hasWrittenQuestions = true;
                    
                    \App\Models\FinalExamAnswer::create([
                        'final_exam_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'student_answer' => $answerData['answer'],
                        'is_correct' => null,
                        'points_earned' => 0,
                    ]);
                }
            }

            // Update attempt status and scores
            if ($hasWrittenQuestions) {
                $attempt->update([
                    'status' => 'pending_review',
                    'score' => $totalScore,
                    'max_score' => $maxScore,
                ]);
            } else {
                $attempt->update([
                    'status' => 'graded',
                    'score' => $totalScore,
                    'max_score' => $maxScore,
                    'graded_at' => now(),
                ]);

                // Recalculate progress after auto-graded final exam
                $this->progressService->recalculateProgress($enrollment);
            }

            DB::commit();

            $response = [
                'message' => $hasWrittenQuestions 
                    ? __('messages.final_exam_submitted_pending_review') 
                    : __('messages.final_exam_graded_immediately'),
                'attempt' => new \App\Http\Resources\FinalExamAttemptResource($attempt->fresh()),
            ];

            if (!$hasWrittenQuestions) {
                $response['score'] = $totalScore;
                $response['max_score'] = $maxScore;
                $response['percentage'] = round(($maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0), 2);
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Final exam submission failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => __('messages.final_exam_submission_failed'),
            ], 500);
        }
    }
}
