@extends('layouts.app', ['title' => 'Absensi Check-in'])

@section('content')
    <style>
        .attendance-shell {
            display: grid;
            gap: 14px;
        }

        .attendance-tabs {
            display: inline-flex;
            gap: 8px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 999px;
            padding: 6px;
        }

        .attendance-tab {
            text-decoration: none;
            border-radius: 999px;
            padding: 8px 14px;
            color: #9a3412;
            font-weight: 700;
            border: 1px solid transparent;
            transition: all .2s ease;
        }

        .attendance-tab:hover {
            background: #fff;
            border-color: #fdba74;
        }

        .attendance-tab.active {
            background: #ea580c;
            color: #fff;
            border-color: #ea580c;
        }

        .attendance-card {
            border: 1px solid #fed7aa;
            border-radius: 14px;
            background: linear-gradient(180deg, #fff 0%, #fff7ed 100%);
            padding: 16px;
        }

        .location-info {
            margin: 0 0 12px;
            color: #334155;
            font-weight: 600;
        }

        .map-wrap {
            display: none;
            margin-bottom: 14px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #fdba74;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .map-frame {
            display: block;
            width: 100%;
            height: 280px;
            border: 0;
        }

        .primary-btn {
            border: 0;
            background: #ea580c;
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
            padding: 11px 16px;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(234, 88, 12, 0.28);
        }

        .primary-btn:hover {
            background: #c2410c;
        }
    </style>

    @if (session('success'))
        <div class="card alert mb-16">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="card alert alert-error mb-16">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mt-10">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="attendance-shell">
        <div class="card mb-16">
            <div class="attendance-tabs" role="tablist" aria-label="Attendance tabs">
                <a href="{{ route('absensi.checkin.page') }}" class="attendance-tab active" aria-current="page">Check-in</a>
                <a href="{{ route('absensi.checkout.page') }}" class="attendance-tab">Check-out</a>
            </div>
        </div>

        <div class="card mb-16 attendance-card">
            <h3 class="mt-0">Check-in</h3>
            @if ($todayAttendance && $todayAttendance->check_in_at && $todayAttendance->checkin_validation_status !== 'rejected')
                <div class="alert mb-10">Check-in hari ini sudah berhasil.</div>
                <p><strong>Lokasi:</strong> {{ $todayAttendance->check_in_location_label ?: '-' }}</p>
                <p><strong>Alamat:</strong> {{ $todayAttendance->check_in_location_address ?: '-' }}</p>
                <p><strong>IP:</strong> {{ $todayAttendance->check_in_ip ?: '-' }}</p>
            @else
                @if ($todayAttendance && $todayAttendance->checkin_validation_status === 'rejected')
                    <div class="card alert alert-error mb-16">
                        <strong>Check-in sebelumnya ditolak:</strong>
                        <p class="mt-5">{{ $todayAttendance->pembimbing_note ?: 'Tidak ada alasan spesifik.' }}</p>
                        <p class="mt-5 text-sm">Silakan lakukan check-in ulang.</p>
                    </div>
                @endif

                <form action="{{ route('absensi.checkin') }}" method="POST">
                    @csrf
                    <input type="hidden" id="checkin-latitude" name="latitude" required>
                    <input type="hidden" id="checkin-longitude" name="longitude" required>
                    <input type="hidden" name="request_token" value="{{ $checkinToken }}">

                    <p id="checkin-location-info" class="location-info">Mengambil lokasi otomatis...</p>
                    <div id="checkin-map-wrapper" class="map-wrap">
                        <iframe
                            id="checkin-map"
                            class="map-frame"
                            title="Peta Lokasi Check-in"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                    <button class="primary-btn" type="submit">Check in</button>
                </form>
            @endif
        </div>
    </div>

    <script>
        function setCheckinLocation(latitude, longitude) {
            const latEl = document.getElementById('checkin-latitude');
            const lonEl = document.getElementById('checkin-longitude');
            if (!latEl || !lonEl) return;

            const infoEl = document.getElementById('checkin-location-info');
            latEl.value = latitude.toFixed(7);
            lonEl.value = longitude.toFixed(7);
            const mapsUrl = `https://www.google.com/maps?q=${latEl.value},${lonEl.value}`;
            infoEl.innerHTML = `Lokasi terdeteksi. <a href="${mapsUrl}" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a>`;
            infoEl.style.color = '#166534';

            const mapEl = document.getElementById('checkin-map');
            const mapWrapper = document.getElementById('checkin-map-wrapper');
            if (mapEl && mapWrapper) {
                mapEl.src = `https://maps.google.com/maps?q=${latEl.value},${lonEl.value}&z=17&output=embed`;
                mapWrapper.style.display = 'block';
            }
        }

        function setCheckinLocationError(message) {
            const infoEl = document.getElementById('checkin-location-info');
            if (!infoEl) return;
            infoEl.textContent = message;
            infoEl.style.color = '#b91c1c';
        }

        function applyCheckinLocation() {
            if (!navigator.geolocation) {
                setCheckinLocationError('Browser tidak mendukung geolocation.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (position) => setCheckinLocation(position.coords.latitude, position.coords.longitude),
                () => setCheckinLocationError('Lokasi tidak diizinkan. Izinkan lokasi untuk check-in.'),
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        applyCheckinLocation();
    </script>
@endsection
