<?php

namespace App\Http\Controllers;

use App\Models\EmailReminderLog;
use App\Support\MenuAccess;
use App\Support\SettingStore;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    private function ensureSettingAccess(Request $request): void
    {
        $role = (string) ($request->user()?->role ?? '');
        abort_unless(MenuAccess::canAccess($role, 'fitur/setting-web'), 403, 'Akses ditolak.');
    }

    public function index(): View
    {
        $this->ensureSettingAccess(request());
        return view('settings.index', [
            'title' => 'Setting Website',
            'settings' => SettingStore::all(),
            'emailReminderLogs' => EmailReminderLog::query()->latest()->limit(100)->get(['email', 'reminder_type', 'status', 'sent_at', 'message']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureSettingAccess($request);
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'app_tagline' => ['nullable', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_manager' => ['nullable', 'string', 'max:255'],
            'school_contact' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'footer_link_1_label' => ['nullable', 'string', 'max:100'],
            'footer_link_1_url' => ['nullable', 'string', 'max:255'],
            'footer_link_2_label' => ['nullable', 'string', 'max:100'],
            'footer_link_2_url' => ['nullable', 'string', 'max:255'],
            'footer_link_3_label' => ['nullable', 'string', 'max:100'],
            'footer_link_3_url' => ['nullable', 'string', 'max:255'],
            'attendance_timezone' => ['nullable', 'string', 'max:100'],
            'attendance_checkin_start' => ['nullable', 'date_format:H:i'],
            'attendance_checkin_end' => ['nullable', 'date_format:H:i'],
            'attendance_checkout_start' => ['nullable', 'date_format:H:i'],
            'attendance_checkout_end' => ['nullable', 'date_format:H:i'],
            'holiday_dates' => ['nullable', 'string', 'max:20000'],
            'app_logo' => ['nullable', 'image', 'max:2048'],
            'app_favicon' => ['nullable', 'image', 'max:1024'],
            'theme_primary' => ['nullable', 'string', 'max:20'],
            'theme_sidebar' => ['nullable', 'string', 'max:20'],
            'theme_button' => ['nullable', 'string', 'max:20'],
            'theme_background' => ['nullable', 'string', 'max:20'],
            'theme_card' => ['nullable', 'string', 'max:20'],
            'guidance_reminder_enabled' => ['nullable', 'boolean'],
            'guidance_reminder_time_first' => ['nullable', 'date_format:H:i'],
            'guidance_reminder_time_followup' => ['nullable', 'date_format:H:i'],
            'guidance_reminder_deadline' => ['nullable', 'date_format:H:i'],
            'monthly_backup_auto_enabled' => ['nullable', 'boolean'],
        ]);

        foreach ([
            'app_name',
            'app_tagline',
            'school_address',
            'school_manager',
            'school_contact',
            'footer_text',
            'footer_link_1_label',
            'footer_link_1_url',
            'footer_link_2_label',
            'footer_link_2_url',
            'footer_link_3_label',
            'footer_link_3_url',
            'attendance_timezone',
            'attendance_checkin_start',
            'attendance_checkin_end',
            'attendance_checkout_start',
            'attendance_checkout_end',
            'holiday_dates',
            'guidance_reminder_time_first',
            'guidance_reminder_time_followup',
            'guidance_reminder_deadline',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                SettingStore::set($key, $data[$key]);
            }
        }

        foreach (['theme_primary', 'theme_sidebar', 'theme_button', 'theme_background', 'theme_card'] as $themeKey) {
            $themeValue = $this->normalizeHexColor((string) $request->input($themeKey, ''));
            if ($themeValue !== null) {
                SettingStore::set($themeKey, $themeValue);
            }
        }

        SettingStore::set('guidance_reminder_enabled', (string) ((bool) ($request->boolean('guidance_reminder_enabled')) ? '1' : '0'));
        SettingStore::set('monthly_backup_auto_enabled', (string) ((bool) ($request->boolean('monthly_backup_auto_enabled')) ? '1' : '0'));

        $logoUpdated = false;
        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('logos', 'public');
            SettingStore::set('app_logo', 'storage/'.$path);
            $logoUpdated = true;
        }

        if ($request->hasFile('app_favicon')) {
            $path = $request->file('app_favicon')->store('favicons', 'public');
            SettingStore::set('app_favicon', 'storage/'.$path);
        } elseif ($logoUpdated) {
            // Jika favicon tidak diupload, ikuti logo terbaru.
            SettingStore::set('app_favicon', SettingStore::get('app_logo', 'image/download.png'));
        }

        return redirect()
            ->route('fitur.setting-web', ['saved' => 1])
            ->with('success', 'Setting website berhasil diperbarui.');
    }

    private function normalizeHexColor(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1) {
            return strtolower($value);
        }

        return null;
    }

    public function sendGuidanceReminderNow(Request $request): RedirectResponse
    {
        $this->ensureSettingAccess($request);
        $data = $request->validate([
            'reminder_type' => ['required', 'in:first,followup'],
        ]);

        Artisan::call('reminder:guidance-email', ['type' => (string) $data['reminder_type']]);
        return back()->with('success', 'Reminder manual berhasil dijalankan.');
    }
}
