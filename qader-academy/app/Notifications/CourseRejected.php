<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Course $course, public string $reason)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Course Rejected')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your course \"{$this->course->title_en}\" was not approved.")
            ->line("Reason: {$this->reason}")
            ->line('Please review the feedback and resubmit your course.')
            ->action('Edit Course', url("/trainer/courses/{$this->course->id}/edit"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_rejected',
            'course_id' => $this->course->id,
            'course_title' => $this->course->title_en,
            'reason' => $this->reason,
            'message' => "Your course '{$this->course->title_en}' was rejected: {$this->reason}",
        ];
    }
}
