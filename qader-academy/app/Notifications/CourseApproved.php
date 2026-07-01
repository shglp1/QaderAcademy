<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Course $course)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Course Approved')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your course \"{$this->course->title_en}\" has been approved and is now published!")
            ->action('View Course', url("/courses/{$this->course->id}"))
            ->line('Thank you for contributing to QaderAcademy.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_approved',
            'course_id' => $this->course->id,
            'course_title' => $this->course->title_en,
            'message' => "Your course '{$this->course->title_en}' has been approved and published.",
        ];
    }
}
