@extends('layouts.app', ['title' => $title])

@section('content')
    <style>
        .audit-filter {
            display: grid;
            grid-template-columns: 160px 1fr 120px 120px;
            gap: 8px;
            align-items: end;
        }
        .audit-filter label {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--accent-text);
        }
        .audit-filter input,
        .audit-filter select {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 9px;
            background: var(--surface);
            color: var(--text);
        }
        .audit-auth-pill {
            display: inline-block;
            border: 1px solid var(--line);
            padding: 2px 8px;
            border-radius: 999px;
            color: var(--accent-text);
            background: var(--accent-soft);
        }
        @media (max-width: 900px) {
            .audit-filter {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div class="card">
        <h3 class="mt-0 text-primary">Log Activity User</h3>
        <form method="GET" class="mb-10 audit-filter">
            <div>
                <label>Scope</label>
                <select name="scope">
                    <option value="all" {{ ($filters['scope'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="auth" {{ ($filters['scope'] ?? 'all') === 'auth' ? 'selected' : '' }}>Auth Only</option>
                </select>
            </div>
            <div>
                <label>Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari user, action, path, ip...">
            </div>
            <div>
                <label>Per Page</label>
                <select name="per_page">
                    @foreach([20,30,50,100] as $size)
                        <option value="{{ $size }}" {{ (int)($filters['per_page'] ?? 30) === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit">Filter</button>
        </form>

        <div class="table-wrap">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>Detail</th>
                        <th>Path/URL</th>
                        <th>IP</th>
                        <th>Koordinat</th>
                        <th>Maps</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        @php
                            $hasValidCoordinate = is_numeric($log->latitude) && is_numeric($log->longitude);
                            $mapsUrl = $hasValidCoordinate
                                ? 'https://www.google.com/maps?q=' . $log->latitude . ',' . $log->longitude
                                : null;
                            $rawAction = strtolower((string) ($log->action ?? ''));
                            $method = strtoupper((string) ($log->method ?? ''));
                            $pathOrUrl = strtolower((string) ($log->path ?? $log->url ?? ''));
                            $isLogin = str_contains($rawAction, 'login') || str_contains($pathOrUrl, '/login') || $pathOrUrl === 'login';
                            $isLogout = str_contains($rawAction, 'logout') || str_contains($pathOrUrl, '/logout') || $pathOrUrl === 'logout';
                            $isAuthEvent = str_starts_with($rawAction, 'auth.') || strtoupper((string) ($log->method ?? '')) === 'AUTH';
                            $displayAction = match (true) {
                                $isLogin => 'Login',
                                $isLogout => 'Logout',
                                str_contains($rawAction, 'delete'), $method === 'DELETE' => 'Delete',
                                str_contains($rawAction, 'update'), str_contains($rawAction, 'edit'), in_array($method, ['PUT', 'PATCH'], true) => 'Update',
                                str_contains($rawAction, 'create'), $method === 'POST' => 'Create',
                                default => 'View',
                            };
                            $payload = is_array($log->payload)
                                ? $log->payload
                                : (json_decode((string) ($log->payload ?? ''), true) ?: []);
                            $payloadText = '';
                            if (is_array($payload) && $payload !== []) {
                                $pairs = [];
                                foreach ($payload as $key => $value) {
                                    if (is_scalar($value) || $value === null) {
                                        $pairs[] = $key.': '.(string) ($value ?? '-');
                                    }
                                }
                                $payloadText = implode(', ', array_slice($pairs, 0, 3));
                            }
                        @endphp
                        <tr>
                            <td>{{ $log->created_at }}</td>
                            <td>
                                <div>{{ $log->user?->name ?? '-' }}</div>
                                <small class="text-muted">{{ $log->user?->username ?? '-' }}</small>
                            </td>
                            <td>
                                {{ $displayAction }}
                                @if($isAuthEvent)
                                    <div><small class="audit-auth-pill">AUTH</small></div>
                                @endif
                            </td>
                            <td style="max-width:220px;">
                                <div><small>{{ $log->action ?? '-' }}</small></div>
                                @if($payloadText !== '')
                                    <small class="text-muted">{{ $payloadText }}</small>
                                @endif
                            </td>
                            <td>{{ $log->path ?? $log->url ?? '-' }}</td>
                            <td>{{ $log->ip_address ?? $log->ip }}</td>
                            <td>{{ $log->latitude ?? '-' }}, {{ $log->longitude ?? '-' }}</td>
                            <td>
                                @if ($hasValidCoordinate)
                                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">Lihat Lokasi</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-10">{{ $logs->links() }}</div>
    </div>
@endsection
