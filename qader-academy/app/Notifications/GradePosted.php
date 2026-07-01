<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GradePosted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $attempt;

    /**
     * Create a new notification instance.
     */
    public function __construct($attempt)
    {
        $this->attempt = $attempt;
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
        $type = $this->attempt instanceof \App\Models\QuizAttempt ? 'quiz' : 'final exam';
        
        return (new MailMessage)
            ->subject(__('notifications.grade_posted_subject'))
            ->line(__('notifications.grade_posted_message', [
                'type' => $type,
                'score' => $this->attempt->score,
                'course' => $this->getCourseTitle(),
            ]))
            ->action(__('notifications.view_details'), url('/student/enrollments'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $type = $this->attempt instanceof \App\Models\QuizAttempt ? 'quiz' : 'final_exam';
        
        return [
            'type' => 'grade_posted',
            'attempt_type' => $type,
            'attempt_id' => $this->attempt->id,
            'score' => $this->attempt->score,
            'feedback' => $this->attempt->feedback,
            'course_id' => $this->getCourseId(),
            'course_title' => $this->getCourseTitle(),
            'message' => __('notifications.grade_posted_message', [
                'type' => $type,
                'score' => $this->attempt->score,
                'course' => $this->getCourseTitle(),
            ]),
        ];
    }
    
    protected function getCourseId()
    {
        if ($this->attempt instanceof \App\Models\QuizAttempt) {
            return $this->attempt->quiz->chapter->course_id;
        }
        return $this->attempt->finalExam->course_id;
    }
    
    protected function getCourseTitle()
    {
        $course = \App\Models\Course::find($this->getCourseId());
        return $course ? $course->title_en : '';
    }
}
