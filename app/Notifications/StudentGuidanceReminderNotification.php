<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentGuidanceReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $studentName,
        private readonly string $dateLabel,
        private readonly string $deadlineLabel,
        private readonly string $url
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Reminder Catatan Bimbingan')
            ->greeting('Halo '.$this->studentName.',')
            ->line('Mohon isi Catatan Bimbingan Anda.')
            ->line('Tanggal pengisian: '.$this->dateLabel)
            ->line('Batas waktu: '.$this->deadlineLabel)
            ->action('Buka Catatan Bimbingan', $this->url);
    }
}

