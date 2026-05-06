<?php

use App\Models\ChatbotMessage;
use App\Models\EmailReminderLog;
use App\Models\StudentGuidanceNote;
use App\Models\User;
use App\Notifications\StudentGuidanceReminderNotification;
use App\Support\MonthlyBackupService;
use App\Support\SettingStore;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('chatbot:prune-history', function () {
    $deleted = ChatbotMessage::query()
        ->where('created_at', '<', now()->subDays(30))
        ->delete();

    $this->info('Pruned '.$deleted.' chatbot messages.');
})->purpose('Prune chatbot message history older than 30 days');

Schedule::command('chatbot:prune-history')->dailyAt('01:00');

Artisan::command('backup:monthly-auto', function (MonthlyBackupService $service) {
    $result = $service->run();
    $this->info('Monthly backup created: '.$result['name']);
})->purpose('Create automatic monthly backup');

Artisan::command('reminder:guidance-email {type=first}', function (string $type) {
    $type = in_array($type, ['first', 'followup'], true) ? $type : 'first';
    $today = now(config('app.timezone', 'Asia/Jakarta'))->toDateString();
    $deadline = SettingStore::get('guidance_reminder_deadline', '23:59');
    $students = User::query()
        ->where('role', 'siswa')
        ->whereNotNull('email')
        ->where('email', '!=', '')
        ->whereNotNull('email_verified_at')
        ->get(['id', 'name', 'email']);

    $sent = 0;
    foreach ($students as $student) {
        $hasNote = StudentGuidanceNote::query()
            ->where('student_user_id', (int) $student->id)
            ->whereDate('guidance_date', $today)
            ->whereNotNull('student_submitted_at')
            ->exists();
        if ($hasNote) {
            continue;
        }

        $alreadySent = EmailReminderLog::query()
            ->where('user_id', (int) $student->id)
            ->whereDate('created_at', $today)
            ->where('reminder_type', $type)
            ->exists();
        if ($alreadySent) {
            continue;
        }

        try {
            Notification::route('mail', (string) $student->email)
                ->notify(new StudentGuidanceReminderNotification(
                    (string) $student->name,
                    now()->format('d M Y'),
                    $deadline,
                    route('guidance.student.index')
                ));

            EmailReminderLog::query()->create([
                'user_id' => (int) $student->id,
                'email' => (string) $student->email,
                'reminder_type' => $type,
                'status' => 'sent',
                'message' => 'Reminder sent',
                'sent_at' => now(),
            ]);
            $sent++;
        } catch (\Throwable $e) {
            EmailReminderLog::query()->create([
                'user_id' => (int) $student->id,
                'email' => (string) $student->email,
                'reminder_type' => $type,
                'status' => 'failed',
                'message' => mb_substr($e->getMessage(), 0, 500),
                'sent_at' => now(),
            ]);
        }
    }

    $this->info("Guidance reminder {$type} finished. Sent: {$sent}");
})->purpose('Send guidance reminder email to students');

if ((string) SettingStore::get('monthly_backup_auto_enabled', '1') === '1') {
    Schedule::command('backup:monthly-auto')->monthlyOn(1, '00:30');
}
if ((string) SettingStore::get('guidance_reminder_enabled', '1') === '1') {
    Schedule::command('reminder:guidance-email first')
        ->fridays()
        ->at((string) SettingStore::get('guidance_reminder_time_first', '09:00'));
    Schedule::command('reminder:guidance-email followup')
        ->fridays()
        ->at((string) SettingStore::get('guidance_reminder_time_followup', '14:00'));
}
