<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class DiscordNotifier
{
    public static function notifyEditDelete(string $title, array $fields = []): void
    {
        $webhook = SettingStore::get('discord_webhook_url') ?: config('services.discord.webhook_url');

        if (! $webhook) {
            return;
        }

        $embedFields = [];
        foreach ($fields as $name => $value) {
            $embedFields[] = [
                'name' => (string) $name,
                'value' => (string) ($value === null ? '-' : $value),
                'inline' => false,
            ];
        }

        try {
            Http::post($webhook, [
                'username' => 'PKL Monitor Bot',
                'embeds' => [[
                    'title' => $title,
                    'color' => 15158332,
                    'fields' => $embedFields,
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);
        } catch (\Throwable) {
            // ignore webhook failures so main flow is not blocked
        }
    }
}
