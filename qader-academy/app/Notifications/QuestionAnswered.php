<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestionAnswered extends Notification implements ShouldQueue
{
    use Queueable;

    protected $thread;

    /**
     * Create a new notification instance.
     */
    public function __construct($thread)
    {
        $this->thread = $thread;
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
            ->subject(__('notifications.question_answered_subject'))
            ->line(__('notifications.question_answered_message', [
                'course' => $this->getCourseTitle(),
                'chapter' => $this->thread->chapter->title ?? '',
            ]))
            ->action(__('notifications.view_details'), url('/student/qa-threads/' . $this->thread->id));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'question_answered',
            'thread_id' => $this->thread->id,
            'course_id' => $this->thread->chapter->course_id,
            'chapter_id' => $this->thread->chapter_id,
            'message' => __('notifications.question_answered_message', [
                'course' => $this->getCourseTitle(),
                'chapter' => $this->thread->chapter->title ?? '',
            ]),
        ];
    }
    
    protected function getCourseTitle()
    {
        $course = \App\Models\Course::find($this->thread->chapter->course_id);
        return $course ? $course->title : '';
    }
}
