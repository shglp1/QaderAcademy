<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentActivated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $enrollment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Enrollment $enrollment)
    {
        $this->enrollment = $enrollment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.enrollment_activated_subject'))
            ->greeting(__('messages.dear') . ' ' . $notifiable->name)
            ->line(__('messages.enrollment_activated_line1', [
                'course' => $this->enrollment->course->title_en,
            ]))
            ->line(__('messages.enrollment_activated_line2'))
            ->action(__('messages.start_learning'), url('/student/courses/' . $this->enrollment->course_id))
            ->line(__('messages.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'enrollment_activated',
            'enrollment_id' => $this->enrollment->id,
            'course_id' => $this->enrollment->course_id,
            'course_title' => $this->enrollment->course->title_en,
            'message' => __('messages.enrollment_activated_notification', [
                'course' => $this->enrollment->course->title_en,
            ]),
        ];
    }
}
