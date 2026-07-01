<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;

class CoursePolicy
{
    /**
     * Determine if the user can view any courses.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Public browsing
    }

    /**
     * Determine if the user can view the course.
     */
    public function view(?User $user, Course $course): bool
    {
        // Published courses are visible to everyone
        if ($course->status === 'published') {
            return true;
        }

        // Draft/pending courses only visible to owner and admins
        if (!$user) {
            return false;
        }

        return $user->id === $course->trainer_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can create courses.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['trainer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can update the course.
     * Only the trainer who owns the course or admins can update it.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->id === $course->trainer_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can delete the course.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->id === $course->trainer_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can submit the course for approval.
     */
    public function submitForApproval(User $user, Course $course): bool
    {
        return $user->id === $course->trainer_id && $course->status === 'draft';
    }

    /**
     * Determine if the user can approve/reject the course (admin only).
     */
    public function moderate(User $user, Course $course): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }
}
