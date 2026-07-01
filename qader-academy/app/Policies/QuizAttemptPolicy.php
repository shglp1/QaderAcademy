<?php

namespace App\Policies;

use App\Models\User;
use App\Models\QuizAttempt;

class QuizAttemptPolicy
{
    /**
     * Determine if the user can view the quiz attempt.
     */
    public function view(User $user, QuizAttempt $quizAttempt): bool
    {
        // Student can view their own attempts
        if ($user->id === $quizAttempt->student_id) {
            return true;
        }

        // Trainer can view attempts for quizzes in their courses
        $quiz = $quizAttempt->quiz;
        $chapter = $quiz->chapter;
        $course = $chapter->course;

        return $user->id === $course->trainer_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can grade the quiz attempt.
     * Only the trainer of the course or admins can grade.
     */
    public function grade(User $user, QuizAttempt $quizAttempt): bool
    {
        $quiz = $quizAttempt->quiz;
        $chapter = $quiz->chapter;
        $course = $chapter->course;

        return $user->id === $course->trainer_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can create quiz attempts.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['student']);
    }
}
