<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\FinalExamAttempt;
use App\Models\Certificate;
use App\Jobs\GenerateCertificateJob;
use Illuminate\Support\Facades\Log;

class ProgressService
{
    /**
     * Weight configuration for progress calculation.
     * 
     * NOTE: The following weights are based on partial client specification:
     * - Quizzes: 3% of total course grade
     * - Video completion: 7% of total course grade
     * - Final exam: 20% of total course grade
     * 
     * TOTAL DEFINED: 30%
     * REMAINING UNDEFINED: 70%
     * 
     * OPEN QUESTION: The client has not confirmed how the remaining 70% should be allocated.
     * Current implementation only uses the confirmed 30%. The progress_percentage field
     * will reflect only these confirmed components until the client provides the full breakdown.
     * 
     * TODO: Update these weights once client confirms the complete weighting model.
     */
    private const QUIZ_WEIGHT = 3.0; // 3% of total
    private const VIDEO_WEIGHT = 7.0; // 7% of total
    private const FINAL_EXAM_WEIGHT = 20.0; // 20% of total
    
    // Total defined weight (30%) - remaining 70% is unconfirmed
    private const TOTAL_DEFINED_WEIGHT = 30.0;

    /**
     * Recalculate progress percentage for an enrollment.
     * This should be called whenever:
     * - A video is marked as completed
     * - A quiz is submitted/graded
     * - A final exam is submitted/graded
     */
    public function recalculateProgress(Enrollment $enrollment): void
    {
        $course = $enrollment->course;
        $studentId = $enrollment->student_id;
        
        $quizScore = $this->calculateQuizScore($studentId, $course->id);
        $videoScore = $this->calculateVideoScore($studentId, $course->id);
        $finalExamScore = $this->calculateFinalExamScore($studentId, $course->id);
        
        // Calculate weighted progress based on confirmed weights only
        // Note: This represents only 30% of the total possible grade
        $progressPercentage = $quizScore + $videoScore + $finalExamScore;
        
        // Cap at 100% even though we only track 30% worth of activities
        $progressPercentage = min($progressPercentage, 100.0);
        
        $enrollment->update([
            'progress_percentage' => round($progressPercentage, 2),
        ]);
        
        Log::info("Progress recalculated for enrollment #{$enrollment->id}", [
            'student_id' => $studentId,
            'course_id' => $course->id,
            'quiz_score' => $quizScore,
            'video_score' => $videoScore,
            'final_exam_score' => $finalExamScore,
            'total_progress' => $progressPercentage,
            'note' => 'Only confirmed 30% weighting applied (quizzes 3%, videos 7%, final exam 20%). Remaining 70% undefined by client.',
        ]);
        
        // Check if course is complete and trigger certificate generation
        $this->checkCompletionAndTriggerCertificate($enrollment);
    }

    /**
     * Calculate quiz component score.
     * Quizzes contribute 3% of total course grade.
     */
    private function calculateQuizScore(int $studentId, int $courseId): float
    {
        $course = \App\Models\Course::with('chapters.quizzes.questions')->findOrFail($courseId);
        
        $totalQuizPoints = 0;
        $earnedQuizPoints = 0;
        
        foreach ($course->chapters as $chapter) {
            foreach ($chapter->quizzes as $quiz) {
                foreach ($quiz->questions as $question) {
                    $totalQuizPoints += $question->points;
                    
                    // Find the student's attempt for this quiz
                    $attempt = QuizAttempt::where('student_id', $studentId)
                        ->where('quiz_id', $quiz->id)
                        ->where('status', '!=', 'pending')
                        ->latest('graded_at')
                        ->first();
                    
                    if ($attempt) {
                        // For auto-graded MCQ or already graded written questions
                        $earnedQuizPoints += $attempt->score;
                    }
                }
            }
        }
        
        if ($totalQuizPoints === 0) {
            return 0.0;
        }
        
        // Scale to quiz weight (3%)
        $quizPercentage = ($earnedQuizPoints / $totalQuizPoints) * 100;
        return ($quizPercentage / 100) * self::QUIZ_WEIGHT;
    }

    /**
     * Calculate video completion score.
     * Videos contribute 7% of total course grade.
     */
    private function calculateVideoScore(int $studentId, int $courseId): float
    {
        $course = \App\Models\Course::with('chapters.videos')->findOrFail($courseId);
        
        $totalVideos = $course->chapters->sum(fn($chapter) => $chapter->videos->count());
        
        if ($totalVideos === 0) {
            return 0.0;
        }
        
        // Track completed videos via a simple mechanism
        // In a real implementation, you'd have a video_completions table
        // For now, we'll assume video completion is tracked elsewhere
        // This is a placeholder that should be connected to actual video completion tracking
        
        // TODO: Implement video completion tracking table and update this logic
        $completedVideos = 0; // Placeholder - should query video_completions table
        
        $videoPercentage = ($completedVideos / $totalVideos) * 100;
        return ($videoPercentage / 100) * self::VIDEO_WEIGHT;
    }

    /**
     * Calculate final exam score.
     * Final exam contributes 20% of total course grade.
     */
    private function calculateFinalExamScore(int $studentId, int $courseId): float
    {
        $finalExam = \App\Models\FinalExam::where('course_id', $courseId)->first();
        
        if (!$finalExam) {
            return 0.0;
        }
        
        $attempt = FinalExamAttempt::where('student_id', $studentId)
            ->where('final_exam_id', $finalExam->id)
            ->where('status', '!=', 'pending')
            ->latest('graded_at')
            ->first();
        
        if (!$attempt || $attempt->max_score === 0) {
            return 0.0;
        }
        
        $examPercentage = ($attempt->score / $attempt->max_score) * 100;
        return ($examPercentage / 100) * self::FINAL_EXAM_WEIGHT;
    }

    /**
     * Check if enrollment meets completion criteria and trigger certificate generation.
     * 
     * Completion criteria (configurable per course):
     * - All chapters completed (videos watched)
     * - All quizzes submitted
     * - Final exam submitted and graded
     * - Overall grade meets minimum threshold (if configured)
     */
    private function checkCompletionAndTriggerCertificate(Enrollment $enrollment): void
    {
        $course = $enrollment->course;
        
        // For now, we consider a course complete when:
        // 1. Progress reaches 100% (based on tracked components)
        // 2. Final exam is graded
        // 
        // Note: Since we only track 30% of the weighting, this is a simplified check
        // until the client confirms the full weighting model.
        
        $finalExam = $course->finalExam()->first();
        $finalExamComplete = false;
        
        if ($finalExam) {
            $finalExamAttempt = FinalExamAttempt::where('student_id', $enrollment->student_id)
                ->where('final_exam_id', $finalExam->id)
                ->where('status', '!=', 'pending')
                ->exists();
            
            $finalExamComplete = $finalExamAttempt;
        }
        
        // Simplified completion check - adjust once full weighting is confirmed
        if ($enrollment->progress_percentage >= 30.0 && $finalExamComplete) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            
            // Generate certificate if not already issued
            $existingCertificate = Certificate::where('enrollment_id', $enrollment->id)->exists();
            
            if (!$existingCertificate) {
                dispatch(new GenerateCertificateJob($enrollment));
                
                Log::info("Certificate generation triggered for enrollment #{$enrollment->id}", [
                    'student_id' => $enrollment->student_id,
                    'course_id' => $course->id,
                ]);
            }
        }
    }

    /**
     * Get the current progress breakdown for an enrollment.
     * Useful for displaying detailed progress to students.
     */
    public function getProgressBreakdown(Enrollment $enrollment): array
    {
        $studentId = $enrollment->student_id;
        $courseId = $enrollment->course_id;
        
        return [
            'quiz_score' => $this->calculateQuizScore($studentId, $courseId),
            'quiz_weight' => self::QUIZ_WEIGHT,
            'video_score' => $this->calculateVideoScore($studentId, $courseId),
            'video_weight' => self::VIDEO_WEIGHT,
            'final_exam_score' => $this->calculateFinalExamScore($studentId, $courseId),
            'final_exam_weight' => self::FINAL_EXAM_WEIGHT,
            'total_progress' => $enrollment->progress_percentage,
            'total_defined_weight' => self::TOTAL_DEFINED_WEIGHT,
            'remaining_undefined_weight' => 70.0,
            'warning' => 'Progress calculation uses only confirmed 30% weighting. Remaining 70% undefined by client.',
        ];
    }
}
