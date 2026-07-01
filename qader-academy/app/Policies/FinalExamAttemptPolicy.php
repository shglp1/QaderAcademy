<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FinalExamAttempt;

class FinalExamAttemptPolicy
{
    /**
     * Determine if the user can view the final exam attempt.
     */
    public function view(User $user, FinalExamAttempt $finalExamAttempt): bool
    {
        // Student can view their own attempts
        if ($user->id === $finalExamAttempt->student_id) {
            return true;
        }

        // Trainer can view attempts for their courses
        $finalExam = $finalExamAttempt->finalExam;
        $course = $finalExam->course;

        return $user->id === $course->trainer_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can grade the final exam attempt.
     * Only the trainer of the course or admins can grade.
     */
    public function grade(User $user, FinalExamAttempt $finalExamAttempt): bool
    {
        $finalExam = $finalExamAttempt->finalExam;
        $course = $finalExam->course;

        return $user->id === $course->trainer_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can create final exam attempts.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['student']);
    }
}
