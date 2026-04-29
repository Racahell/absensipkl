<?php

namespace App\Http\Controllers;

use App\Models\PklLocation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PklLocationController extends Controller
{
    public function index(Request $request): View
    {
        $googleMapsEnabled = filter_var(env('GOOGLE_MAPS_ENABLED', false), FILTER_VALIDATE_BOOL);
        $isSuperadmin = (string) ($request->user()?->role ?? '') === 'superadmin';
        $tab = $request->string('tab', 'active')->toString();
        if (! in_array($tab, ['active', 'deleted'], true)) {
            $tab = 'active';
        }
        if ($tab === 'deleted' && ! $isSuperadmin) {
            $tab = 'active';
        }

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->integer('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $locationsQuery = $tab === 'deleted'
            ? PklLocation::onlyTrashed()
            : PklLocation::query()->withoutTrashed();

        return view('locations.index', [
            'title' => 'Lokasi PKL',
            'isSuperadmin' => $isSuperadmin,
            'tab' => $tab,
            'activeCount' => PklLocation::query()->withoutTrashed()->count(),
            'deletedCount' => PklLocation::onlyTrashed()->count(),
            'perPage' => $perPage,
            'perPageOptions' => $allowedPerPage,
            'locations' => $locationsQuery->latest()->paginate($perPage)->withQueryString(),
            'googleMapsApiKey' => (string) env('GOOGLE_MAPS_API_KEY', ''),
            'googleMapsEnabled' => $googleMapsEnabled,
        ]);
    }

    public function show(PklLocation $location): View
    {
        $googleMapsEnabled = filter_var(env('GOOGLE_MAPS_ENABLED', false), FILTER_VALIDATE_BOOL);

        return view('locations.show', [
            'title' => 'Detail Lokasi PKL',
            'location' => $location,
            'googleMapsApiKey' => (string) env('GOOGLE_MAPS_API_KEY', ''),
            'googleMapsEnabled' => $googleMapsEnabled,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'location_latitude' => ['required', 'numeric', 'between:-90,90'],
            'location_longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
            'ip_reference' => ['nullable', 'string', 'max:100'],
        ]);

        PklLocation::create([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'latitude' => $data['location_latitude'],
            'longitude' => $data['location_longitude'],
            'radius_meters' => $data['radius_meters'],
            'ip_reference' => $data['ip_reference'] ?? null,
            'is_deleted' => false,
        ]);

        return back()->with('success', 'Lokasi PKL berhasil ditambahkan.');
    }

    public function update(Request $request, PklLocation $location): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'location_latitude' => ['required', 'numeric', 'between:-90,90'],
            'location_longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
            'ip_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $location->update([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'latitude' => $data['location_latitude'],
            'longitude' => $data['location_longitude'],
            'radius_meters' => $data['radius_meters'],
            'ip_reference' => $data['ip_reference'] ?? null,
        ]);

        return redirect()->route('locations.show', $location)->with('success', 'Lokasi PKL berhasil diperbarui.');
    }

    public function destroy(PklLocation $location): RedirectResponse
    {
        $location->update(['is_deleted' => true]);
        $location->delete();

        return redirect()->route('fitur.lokasi-pkl')->with('success', 'Lokasi PKL berhasil dihapus.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['delete', 'restore', 'force_delete'])],
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer'],
        ]);

        $action = (string) $data['action'];
        $selectedIds = collect($data['selected_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return back()->with('error', 'Tidak ada lokasi yang dipilih.');
        }

        if (in_array($action, ['restore', 'force_delete'], true) && (string) ($request->user()?->role ?? '') !== 'superadmin') {
            return back()->with('error', 'Hanya superadmin yang boleh restore/hapus permanen lokasi.');
        }

        $locations = PklLocation::withTrashed()->whereIn('id', $selectedIds)->get();
        $processed = 0;
        $skipped = 0;

        foreach ($locations as $location) {
            try {
                if ($action === 'delete') {
                    if ($location->trashed()) {
                        $skipped++;
                        continue;
                    }
                    $location->update(['is_deleted' => true]);
                    $location->delete();
                    $processed++;
                    continue;
                }

                if ($action === 'restore') {
                    if (! $location->trashed()) {
                        $skipped++;
                        continue;
                    }
                    $location->restore();
                    $location->update(['is_deleted' => false]);
                    $processed++;
                    continue;
                }

                if (! $location->trashed()) {
                    $skipped++;
                    continue;
                }

                $location->forceDelete();
                $processed++;
            } catch (\Throwable) {
                $skipped++;
            }
        }

        $label = match ($action) {
            'delete' => 'Delete',
            'restore' => 'Restore',
            'force_delete' => 'Delete Permanent',
            default => 'Aksi',
        };

        return back()->with('success', "{$label} massal lokasi selesai: {$processed} berhasil, {$skipped} dilewati.");
    }
}
