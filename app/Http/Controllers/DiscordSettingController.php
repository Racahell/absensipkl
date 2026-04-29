<?php

namespace App\Http\Controllers;

use App\Support\DiscordNotifier;
use App\Support\SettingStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DiscordSettingController extends Controller
{
    public function index(): View
    {
        return view('settings.discord', [
            'title' => 'Notifikasi Discord',
            'webhook' => SettingStore::get('discord_webhook_url', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'discord_webhook_url' => ['nullable', 'url', 'max:500'],
        ]);

        SettingStore::set('discord_webhook_url', $data['discord_webhook_url'] ?? null);

        return back()->with('success', 'Webhook Discord berhasil disimpan.');
    }

    public function test(): RedirectResponse
    {
        DiscordNotifier::notifyEditDelete('Tes Notifikasi Discord', [
            'Sumber' => 'Menu Notifikasi Discord',
            'Waktu' => now()->toDateTimeString(),
        ]);

        return back()->with('success', 'Tes notifikasi Discord dikirim (jika webhook valid).');
    }
}
