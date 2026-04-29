<?php

namespace App\Http\Controllers;

use App\Support\SettingStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'title' => 'Setting Website',
            'settings' => SettingStore::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
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
            'app_logo' => ['nullable', 'image', 'max:2048'],
            'app_favicon' => ['nullable', 'image', 'max:1024'],
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
        ] as $key) {
            if (array_key_exists($key, $data)) {
                SettingStore::set($key, $data[$key]);
            }
        }

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

        return back()->with('success', 'Setting website berhasil diperbarui.');
    }
}
