<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateReady extends Notification implements ShouldQueue
{
    use Queueable;

    protected $certificate;

    /**
     * Create a new notification instance.
     */
    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
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
        $course = $this->certificate->course;
        
        return (new MailMessage)
            ->subject(__('messages.certificate_ready_subject'))
            ->greeting(__('messages.congratulations') . ', ' . $notifiable->name . '!')
            ->line(__('messages.certificate_ready_line1', [
                'course' => $course->title_en,
            ]))
            ->line(__('messages.certificate_ready_line2'))
            ->action(__('messages.download_certificate'), url('/student/certificates/' . $this->certificate->id))
            ->line(__('messages.certificate_verification_info', [
                'code' => $this->certificate->verification_code,
            ]))
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
            'type' => 'certificate_ready',
            'certificate_id' => $this->certificate->id,
            'course_id' => $this->certificate->course_id,
            'course_title' => $this->certificate->course->title_en,
            'verification_code' => $this->certificate->verification_code,
            'message' => __('messages.certificate_ready_notification', [
                'course' => $this->certificate->course->title_en,
            ]),
        ];
    }
}
