<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoursePendingReview extends Notification implements ShouldQueue
{
    use Queueable;

    protected $course;

    /**
     * Create a new notification instance.
     */
    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Get the notification's delivery channels.
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
            ->subject(__('notifications.course_pending_subject'))
            ->line(__('notifications.course_pending_message', [
                'course' => $this->course->title,
                'trainer' => $this->course->trainer->name ?? '',
            ]))
            ->action(__('notifications.review_course'), url('/admin/courses/pending'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_pending_review',
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'trainer_id' => $this->course->trainer_id,
            'trainer_name' => $this->course->trainer->name ?? '',
            'message' => __('notifications.course_pending_message', [
                'course' => $this->course->title,
                'trainer' => $this->course->trainer->name ?? '',
            ]),
        ];
    }
}
