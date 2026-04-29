@extends('layouts.app', ['title' => $title])

@section('content')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">

    <div class="card">
        <h3 class="mt-0 text-primary">Detail Lokasi PKL</h3>
        @if(session('success'))
            <div class="alert alert-success mb-10">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error mb-10">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('locations.update', $location) }}" class="grid gap-8" style="width:100%;">
            @csrf
            @method('PUT')

            <div class="grid-2">
                <div>
                    <label for="place-search">Cari Lokasi PT</label>
                    <div style="position:relative;">
                        <input id="place-search" type="text" placeholder="Contoh: PT Telkom Indonesia Jakarta" autocomplete="off">
                        <div id="place-fallback-results" class="panel" style="display:none; position:absolute; z-index:1200; left:0; right:0; top:calc(100% + 6px); max-height:240px; overflow:auto; padding:8px; background:#fff;"></div>
                    </div>
                    <small id="place-search-help" class="text-muted" style="display:block; margin-top:6px;">Ketik minimal 3 huruf.</small>
                </div>
                <div>
                    <label for="name">Nama PT / Tempat PKL</label>
                    <input id="name" name="name" value="{{ old('name', $location->name) }}" required>
                </div>
                <div class="full">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address" rows="3">{{ old('address', $location->address) }}</textarea>
                </div>
                <div>
                    <label for="location_latitude">Latitude</label>
                    <input id="location_latitude" name="location_latitude" value="{{ old('location_latitude', $location->latitude) }}" required>
                </div>
                <div>
                    <label for="location_longitude">Longitude</label>
                    <input id="location_longitude" name="location_longitude" value="{{ old('location_longitude', $location->longitude) }}" required>
                </div>
                <div>
                    <label for="radius_meters">Radius Absensi (meter)</label>
                    <input id="radius_meters" name="radius_meters" type="number" min="10" max="10000" value="{{ old('radius_meters', $location->radius_meters) }}" required>
                </div>
                <div>
                    <label for="ip_reference">IP Referensi (opsional)</label>
                    <input id="ip_reference" name="ip_reference" value="{{ old('ip_reference', $location->ip_reference) }}">
                </div>
            </div>

            <div>
                <label>Peta Lokasi (klik peta untuk pilih titik)</label>
                <div id="location-map" style="height:300px; border:1px solid #fdba74; border-radius:10px;"></div>
            </div>

            <div class="flex gap-8 wrap">
                <button type="submit" class="logout-btn">Simpan Perubahan</button>
                <a href="{{ route('fitur.lokasi-pkl') }}" class="btn btn-ghost" style="text-decoration:none; display:inline-block;">Batal</a>
                <button type="button" id="delete-location-btn" class="btn-danger">Hapus</button>
            </div>
        </form>

        <form id="delete-location-form" method="POST" action="{{ route('locations.destroy', $location) }}" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>

    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        (function () {
            const nameInput = document.getElementById('name');
            const addressInput = document.getElementById('address');
            const latitudeInput = document.getElementById('location_latitude');
            const longitudeInput = document.getElementById('location_longitude');
            const searchInput = document.getElementById('place-search');
            const searchHelp = document.getElementById('place-search-help');
            const fallbackResults = document.getElementById('place-fallback-results');
            const deleteLocationBtn = document.getElementById('delete-location-btn');
            const deleteLocationForm = document.getElementById('delete-location-form');

            let marker = null;
            let fallbackDebounce = null;

            function setHelpText(text) {
                searchHelp.textContent = text || '';
            }

            const map = L.map('location-map').setView([-6.2000000, 106.8166660], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            function setPoint(lat, lng, moveMap) {
                const nLat = Number(lat);
                const nLng = Number(lng);
                if (!Number.isFinite(nLat) || !Number.isFinite(nLng)) {
                    return;
                }

                latitudeInput.value = nLat.toFixed(7);
                longitudeInput.value = nLng.toFixed(7);

                if (!marker) {
                    marker = L.marker([nLat, nLng], { draggable: true }).addTo(map);
                    marker.on('dragend', function (event) {
                        const pos = event.target.getLatLng();
                        setPoint(pos.lat, pos.lng, false);
                    });
                } else {
                    marker.setLatLng([nLat, nLng]);
                }

                if (moveMap) {
                    map.setView([nLat, nLng], 17);
                }
            }

            function applyLocationResult(label, address, lat, lng) {
                if (label) {
                    nameInput.value = label;
                }
                if (address) {
                    addressInput.value = address;
                }
                setPoint(lat, lng, true);
            }

            function showFallbackResults(items) {
                fallbackResults.innerHTML = '';
                if (!items.length) {
                    fallbackResults.style.display = 'none';
                    return;
                }

                items.forEach((item) => {
                    const optionBtn = document.createElement('button');
                    optionBtn.type = 'button';
                    optionBtn.style.display = 'block';
                    optionBtn.style.width = '100%';
                    optionBtn.style.textAlign = 'left';
                    optionBtn.style.marginBottom = '6px';
                    optionBtn.style.background = '#fff';
                    optionBtn.style.color = '#9a3412';
                    optionBtn.style.border = '1px solid #fdba74';
                    optionBtn.style.borderRadius = '8px';
                    optionBtn.style.padding = '8px';
                    optionBtn.textContent = item.display_name;
                    optionBtn.addEventListener('click', () => {
                        applyLocationResult(item.name || item.display_name, item.display_name, item.lat, item.lon);
                        fallbackResults.style.display = 'none';
                    });
                    fallbackResults.appendChild(optionBtn);
                });

                fallbackResults.style.display = 'block';
            }

            async function searchLocation(query) {
                if (query.length < 3) {
                    showFallbackResults([]);
                    return;
                }

                try {
                    const endpoint = 'https://nominatim.openstreetmap.org/search?format=json&limit=6&q=' + encodeURIComponent(query);
                    const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
                    if (!response.ok) {
                        showFallbackResults([]);
                        return;
                    }
                    const rows = await response.json();
                    showFallbackResults(Array.isArray(rows) ? rows : []);
                } catch (error) {
                    showFallbackResults([]);
                }
            }

            searchInput.addEventListener('input', () => {
                if (fallbackDebounce) {
                    clearTimeout(fallbackDebounce);
                }
                fallbackDebounce = setTimeout(() => {
                    searchLocation(searchInput.value.trim());
                }, 350);
            });

            document.addEventListener('click', function (event) {
                if (event.target === searchInput || fallbackResults.contains(event.target)) {
                    return;
                }
                fallbackResults.style.display = 'none';
            });

            map.on('click', function (event) {
                setPoint(event.latlng.lat, event.latlng.lng, false);
            });

            latitudeInput.addEventListener('input', () => {
                setPoint(latitudeInput.value, longitudeInput.value, false);
            });
            longitudeInput.addEventListener('input', () => {
                setPoint(latitudeInput.value, longitudeInput.value, false);
            });

            const initialLat = latitudeInput.value.trim();
            const initialLng = longitudeInput.value.trim();
            if (initialLat !== '' && initialLng !== '') {
                setPoint(initialLat, initialLng, true);
            }

            setHelpText('Cari lokasi otomatis memakai OpenStreetMap.');

            if (deleteLocationBtn && deleteLocationForm) {
                deleteLocationBtn.addEventListener('click', async function () {
                    const isEn = window.localStorage.getItem('ui_lang') === 'en';
                    const ok = await window.AppDialog.confirm(isEn ? 'Delete this location?' : 'Hapus lokasi ini?');
                    if (ok) {
                        deleteLocationForm.submit();
                    }
                });
            }
        })();
    </script>
@endsection
