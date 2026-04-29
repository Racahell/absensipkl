<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteSettingController extends Controller
{
    public function edit(): View
    {
        $setting = WebsiteSetting::firstOrCreate(['id' => 1], [
            'site_name' => 'Absensi PKL',
            'site_title' => 'Absensi & Monitoring PKL',
        ]);

        return view('settings.web', ['setting' => $setting]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_title' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:100'],
            'contact' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'favicon' => ['nullable', 'image', 'max:2048'],
        ]);

        $setting = WebsiteSetting::firstOrCreate(['id' => 1]);
        $logoPath = $request->file('logo')?->store('settings', 'public');
        $faviconPath = $request->file('favicon')?->store('settings', 'public');

        $setting->update([
            'site_name' => $validated['site_name'],
            'site_title' => $validated['site_title'],
            'address' => $validated['address'] ?? null,
            'manager_name' => $validated['manager_name'] ?? null,
            'contact' => $validated['contact'] ?? null,
            'logo_path' => $logoPath ?? $setting->logo_path,
            'favicon_path' => $faviconPath ?? $setting->favicon_path,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Setting web berhasil disimpan.');
    }
}
