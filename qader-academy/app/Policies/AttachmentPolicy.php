<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attachment;

class AttachmentPolicy
{
    /**
     * Determine if the user can view the attachment.
     */
    public function view(?User $user, Attachment $attachment): bool
    {
        // If no user, check if attachment is public (unlikely for course materials)
        if (!$user) {
            return false;
        }

        // Admins can view all attachments
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Trainer can view attachments from their own courses
        $course = $attachment->course;
        if ($user->id === $course->trainer_id) {
            return true;
        }

        // Students can only view attachments if they're enrolled in the course
        if ($user->hasRole('student')) {
            $isEnrolled = \App\Models\Enrollment::where('student_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->exists();

            return $isEnrolled;
        }

        return false;
    }

    /**
     * Determine if the user can create attachments.
     * Only trainers (for their own courses) and admins can create.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['trainer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can update the attachment.
     */
    public function update(User $user, Attachment $attachment): bool
    {
        $course = $attachment->course;
        return $user->id === $course->trainer_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can delete the attachment.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        $course = $attachment->course;
        return $user->id === $course->trainer_id || $user->hasRole(['admin', 'super_admin']);
    }
}
