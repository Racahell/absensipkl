<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyPendingEmail extends Notification
{
    public function __construct(
        private readonly User $user,
        private readonly string $pendingEmail
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.pending',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->pendingEmail),
                'email' => $this->pendingEmail,
            ]
        );

        return (new MailMessage)
            ->subject('Verifikasi Perubahan Email')
            ->greeting('Halo '.$this->user->name.',')
            ->line('Anda meminta perubahan email akun.')
            ->line('Klik tombol di bawah untuk mengaktifkan email baru Anda.')
            ->action('Verifikasi Email Baru', $verificationUrl)
            ->line('Jika Anda tidak meminta perubahan ini, abaikan email ini.');
    }
}

