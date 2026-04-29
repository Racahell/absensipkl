<?php

use App\Models\ChatbotMessage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
