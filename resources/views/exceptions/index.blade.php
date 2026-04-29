@extends('layouts.app', ['title' => $title])

@section('content')
    @php
        $normalizeKey = static function (?string $value): string {
            if (! $value) {
                return '';
            }

            $normalized = strtolower(trim($value));
            $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';

            return trim($normalized, '_');
        };

        $typeLabels = [
            'checkin_outside_cutoff' => 'Check in di luar jam PKL',
            'checkout_outside_cutoff' => 'Check out di luar jam PKL',
            'checkout_without_checkin' => 'Check out tanpa check in',
            'outside_radius' => 'Di luar radius PKL',
            'location_missing' => 'Lokasi PKL belum ditentukan',
            'ip_mismatch' => 'IP tidak sesuai referensi',
            'daily_report_empty' => 'Laporan harian kosong',
            'internship_location_is_not_set' => 'Lokasi PKL belum ditentukan',
            'outside_internship_radius' => 'Di luar radius PKL',
            'no_checkout' => 'Belum check out',
            'check_out_outside_internship_hours' => 'Check out di luar jam PKL',
            'checkout_outside_internship_hours' => 'Check out di luar jam PKL',
            'check_in_outside_internship_hours' => 'Check in di luar jam PKL',
        ];

        $severityLabels = [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
        ];

        $metaLabels = [
            'time' => 'Jam',
            'date' => 'Tanggal',
            'attendance_date' => 'Tanggal Absensi',
            'distance_m' => 'Jarak (m)',
            'radius_m' => 'Radius (m)',
            'expected_ip' => 'IP Referensi',
            'actual_ip' => 'IP Aktual',
        ];

        $formatType = static function (?string $type) use ($typeLabels, $normalizeKey): string {
            if (! $type) {
                return '-';
            }

            $normalizedType = $normalizeKey($type);

            if (isset($typeLabels[$type])) {
                return $typeLabels[$type];
            }

            if (isset($typeLabels[$normalizedType])) {
                return $typeLabels[$normalizedType];
            }

            return ucwords(str_replace('_', ' ', $normalizedType ?: $type));
        };

        $formatSeverity = static function (?string $severity) use ($severityLabels, $normalizeKey): string {
            if (! $severity) {
                return '-';
            }

            $normalizedSeverity = $normalizeKey($severity);

            return $severityLabels[$normalizedSeverity] ?? ucfirst($normalizedSeverity ?: $severity);
        };

        $formatDetail = static function ($meta) use ($metaLabels, $normalizeKey): string {
            if (! is_array($meta) || $meta === []) {
                return '-';
            }

            $lines = [];
            foreach ($meta as $key => $value) {
                $normalizedKey = $normalizeKey((string) $key);
                $label = $metaLabels[$normalizedKey] ?? ucwords(str_replace('_', ' ', $normalizedKey ?: (string) $key));
                $displayValue = is_scalar($value) || $value === null
                    ? (string) ($value ?? '-')
                    : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $lines[] = $label.': '.$displayValue;
            }

            return implode("\n", $lines);
        };
    @endphp

    @if (session('success'))
        <div class="card alert alert-success mb-16">
            {{ session('success') }}
        </div>
    @endif

    <div class="card mb-14">
        <h3 class="mt-0 text-primary">Filter Pengecualian</h3>
        <form id="exception-filter-form" method="GET" action="{{ route('fitur.exception-monitoring') }}" class="grid gap-8" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
            <div>
                <label for="filter-type">Jenis Pengecualian</label>
                <select id="filter-type" name="type">
                    <option value="">Semua Jenis</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $formatType($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-severity">Tingkat Keparahan</label>
                <select id="filter-severity" name="severity">
                    <option value="">Semua Tingkat</option>
                    <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Rendah</option>
                    <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Sedang</option>
                    <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>Tinggi</option>
                </select>
            </div>
            <div>
                <label for="filter-date-from">Tanggal Mulai</label>
                <input id="filter-date-from" type="date" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div>
                <label for="filter-date-to">Tanggal Akhir</label>
                <input id="filter-date-to" type="date" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div>
                <label for="filter-per-page">Jumlah Data</label>
                <select id="filter-per-page" name="per_page">
                    @foreach ([10, 20, 30, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) ($perPage ?? 30) === $size ? 'selected' : '' }}>
                            Tampilkan {{ $size }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 class="mt-0 text-primary">Daftar Pengecualian</h3>
        <div class="table-wrap">
            <table class="w-full">
                <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Pengguna</th>
                    <th>Jenis</th>
                    <th>Tingkat Keparahan</th>
                    <th>Detail</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ ($items->firstItem() ?? 1) + $loop->index }}</td>
                        <td>{{ $item->event_date }}</td>
                        <td>{{ $item->user?->name ?? '-' }}</td>
                        <td>{{ $formatType($item->exception_type) }}</td>
                        <td>{{ $formatSeverity($item->severity) }}</td>
                        <td><pre style="margin:0; white-space:pre-wrap;">{{ $formatDetail($item->meta) }}</pre></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;">Tidak ada data pengecualian.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-10">{{ $items->links() }}</div>
    </div>

    <script>
        (function () {
            const filterForm = document.getElementById('exception-filter-form');
            if (!filterForm) return;

            const controls = Array.from(filterForm.querySelectorAll('select, input[type="date"]'));
            let timer = null;
            const submitAuto = (delay = 0) => {
                if (timer) clearTimeout(timer);
                timer = window.setTimeout(() => filterForm.submit(), delay);
            };

            controls.forEach((el) => {
                const tag = (el.tagName || '').toLowerCase();
                if (tag === 'select') {
                    el.addEventListener('change', () => submitAuto());
                } else {
                    el.addEventListener('change', () => submitAuto(120));
                }
            });
        })();

    </script>
@endsection
