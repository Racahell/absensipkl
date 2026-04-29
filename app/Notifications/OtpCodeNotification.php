<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly string $purposeLabel,
        private readonly int $minutes = 5,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Kode OTP Absensi PKL')
            ->line('Kode OTP untuk '.$this->purposeLabel.' adalah: '.$this->code)
            ->line('Kode berlaku '.$this->minutes.' menit.')
            ->line('Jangan bagikan kode ini ke siapa pun.');
    }
}

