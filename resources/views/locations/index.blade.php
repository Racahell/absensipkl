@extends('layouts.app', ['title' => $title])

@section('content')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(17, 24, 39, 0.55);
            align-items: center;
            justify-content: center;
            padding: 14px;
        }
        .modal-box {
            width: min(1100px, 100%);
            max-height: 92vh;
            overflow: auto;
            background: #fff;
            border: 1px solid #fdba74;
            border-radius: 14px;
            padding: 14px;
        }
        .locations-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 12px;
            align-items: end;
            margin-bottom: 10px;
        }
        .locations-toolbar.deleted-mode {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
        }
        .tab-link {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            color: var(--accent-text);
            background: var(--accent-soft);
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            display: inline-flex;
            align-items: center;
        }
        .tab-link.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
        .locations-filter-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: end;
        }
        .locations-bulk-wrap {
            display: grid;
            gap: 8px;
            min-width: 320px;
        }
        .locations-bulk-wrap.deleted-mode {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: nowrap;
        }
        .locations-bulk-wrap.deleted-mode .bulk-meta-row {
            justify-content: flex-start;
        }
        .locations-bulk-wrap.deleted-mode .bulk-action-row {
            margin-left: auto;
            justify-content: flex-end;
        }
        .bulk-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }
        .bulk-meta-left {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .selected-count-chip {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #9a3412;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
        }
        .bulk-action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .bulk-action-row.right {
            justify-content: flex-end;
        }
        .bulk-meta-row .bulk-location-btn {
            margin-left: auto;
        }
        .bulk-select-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            color: #7c2d12;
            margin: 0;
            line-height: 1;
        }
        .bulk-select-label input[type="checkbox"] {
            margin: 0;
        }
        @media (max-width: 900px) {
            .locations-toolbar {
                grid-template-columns: 1fr;
            }
            .locations-toolbar.deleted-mode {
                grid-template-columns: 1fr;
            }
            .locations-bulk-wrap {
                min-width: 0;
            }
            .locations-bulk-wrap.deleted-mode {
                flex-wrap: wrap;
            }
        }
    </style>

    <div class="card mb-14">
        <div class="flex items-center justify-between wrap gap-10">
            <h3 class="mt-0 text-primary" style="margin-bottom:0;">Lokasi PKL</h3>
            <button type="button" id="open-add-location-modal" class="logout-btn">Tambah Lokasi PKL</button>
        </div>
        @if(session('success'))
            <div class="alert alert-success mb-10">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error mb-10">{{ $errors->first() }}</div>
        @endif
    </div>

    <div class="card">
        <div class="flex items-center justify-between wrap gap-10 mb-10">
            <h3 class="mt-0 text-primary">Daftar Lokasi PKL</h3>
            <div class="flex gap-8">
                <a href="{{ route('fitur.lokasi-pkl', ['tab' => 'active', 'per_page' => $perPage]) }}" class="tab-link {{ ($tab ?? 'active') === 'active' ? 'active' : '' }}">
                    Active ({{ $activeCount ?? 0 }})
                </a>
                @if ($hasDeletedTabAccess ?? false)
                    <a href="{{ route('fitur.lokasi-pkl', ['tab' => 'deleted', 'per_page' => $perPage]) }}" class="tab-link {{ ($tab ?? 'active') === 'deleted' ? 'active' : '' }}">
                        Deleted ({{ $deletedCount ?? 0 }})
                    </a>
                @endif
            </div>
        </div>
        <div class="locations-toolbar {{ ($tab ?? 'active') === 'deleted' ? 'deleted-mode' : '' }}">
            <div class="locations-filter-wrap">
                <div style="min-width:240px; flex:1 1 240px;">
                    <label for="location-search">Search Location</label>
                    <input id="location-search" type="text" placeholder="Type company, address, coordinate, IP...">
                </div>
                <div style="min-width:130px; max-width:170px; flex:0 1 140px;">
                    <label for="per-page-select">Show</label>
                    <select id="per-page-select">
                        @foreach (($perPageOptions ?? [10, 20, 50, 100]) as $opt)
                            <option value="{{ $opt }}" {{ (int) ($perPage ?? 20) === (int) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <form id="bulk-location-form" method="POST" action="{{ route('locations.bulk-action') }}" class="locations-bulk-wrap {{ ($tab ?? 'active') === 'deleted' ? 'deleted-mode' : '' }}">
                @csrf
                <input type="hidden" name="action" id="bulk-location-action">
                <div id="bulk-location-selected-ids"></div>
                <div class="bulk-meta-row">
                    <div class="bulk-meta-left">
                        <label class="bulk-select-label">
                            <input type="checkbox" id="select-all-locations">
                            Select All
                        </label>
                        <span id="selected-location-count" class="selected-count-chip">0 selected</span>
                    </div>
                    @if (($tab ?? 'active') === 'active')
                        <button type="button" class="btn-danger bulk-location-btn" data-action="delete">Delete Selected</button>
                    @endif
                </div>
                <div class="bulk-action-row right">
                    @if (($tab ?? 'active') !== 'active' && ($hasDeletedTabAccess ?? false))
                        <button type="button" class="btn-success bulk-location-btn" data-action="restore">Restore Selected</button>
                        <button type="button" class="btn-danger bulk-location-btn" data-action="force_delete">Delete Permanent Selected</button>
                    @endif
                </div>
            </form>
        </div>
        <div class="table-wrap">
            <table class="w-full">
                <thead>
                    <tr>
                        <th style="width:42px;">Select</th>
                        <th>Nama PT</th>
                        <th>Alamat</th>
                        <th>Titik</th>
                        <th>Radius</th>
                        <th>IP Referensi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $item)
                        <tr class="location-row"
                            data-name="{{ strtolower($item->name ?? '') }}"
                            data-address="{{ strtolower($item->address ?? '') }}"
                            data-coordinate="{{ strtolower((string) $item->latitude . ',' . (string) $item->longitude) }}"
                            data-ip="{{ strtolower($item->ip_reference ?? '') }}">
                            <td style="text-align:center;">
                                <input type="checkbox" class="location-select" value="{{ $item->id }}">
                            </td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->address ?: '-' }}</td>
                            <td>
                                {{ $item->latitude }}, {{ $item->longitude }}
                                <br>
                                <a href="https://www.google.com/maps?q={{ $item->latitude }},{{ $item->longitude }}" target="_blank" rel="noopener noreferrer">Lihat Lokasi</a>
                            </td>
                            <td>{{ $item->radius_meters }}m</td>
                            <td>{{ $item->ip_reference ?: '-' }}</td>
                            <td>
                                <a href="{{ route('locations.show', $item) }}" class="btn btn-ghost" style="display:inline-block; text-decoration:none;">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;">Belum ada data lokasi PKL.</td>
                        </tr>
                    @endforelse
                    <tr id="empty-location-search-row" style="display:none;">
                        <td colspan="7" style="text-align:center;">Tidak ada data yang cocok dengan pencarian.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-10">{{ $locations->links() }}</div>
    </div>

    <div id="add-location-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="flex items-center justify-between wrap gap-10 mb-10">
                <h3 class="mt-0 text-primary" style="margin-bottom:0;">Tambah Lokasi PKL</h3>
                <button type="button" class="btn btn-ghost close-location-modal">Tutup</button>
            </div>

            <form id="location-form" method="POST" action="{{ route('locations.store') }}" class="grid gap-8" style="width:100%;">
                @csrf
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
                        <input id="name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="full">
                        <label for="address">Alamat</label>
                        <textarea id="address" name="address" rows="3">{{ old('address') }}</textarea>
                    </div>
                    <div>
                        <label for="location_latitude">Latitude</label>
                        <input id="location_latitude" name="location_latitude" value="{{ old('location_latitude') }}" required>
                    </div>
                    <div>
                        <label for="location_longitude">Longitude</label>
                        <input id="location_longitude" name="location_longitude" value="{{ old('location_longitude') }}" required>
                    </div>
                    <div>
                        <label for="radius_meters">Radius Absensi (meter)</label>
                        <input id="radius_meters" name="radius_meters" type="number" min="10" max="10000" value="{{ old('radius_meters', 100) }}" required>
                    </div>
                    <div>
                        <label for="ip_reference">IP Referensi (opsional)</label>
                        <input id="ip_reference" name="ip_reference" value="{{ old('ip_reference') }}">
                    </div>
                </div>

                <div>
                    <label>Peta Lokasi (klik peta untuk pilih titik)</label>
                    <div id="location-map" style="height:300px; border:1px solid #fdba74; border-radius:10px;"></div>
                </div>

                <div class="flex gap-8 wrap">
                    <button type="submit" class="logout-btn" id="submit-btn">Simpan Lokasi</button>
                    <button type="button" class="btn btn-ghost close-location-modal">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        (function () {
            const lang = window.localStorage.getItem('ui_lang') || 'id';
            const uiText = lang === 'en'
                ? {
                    selectedSuffix: 'selected',
                    pickOne: 'Select at least one location first.',
                    confirmDelete: 'Delete selected locations?',
                    confirmRestore: 'Restore selected locations?',
                    confirmForceDelete: 'Permanently delete selected locations?',
                }
                : {
                    selectedSuffix: 'dipilih',
                    pickOne: 'Pilih minimal satu lokasi terlebih dahulu.',
                    confirmDelete: 'Hapus lokasi yang dipilih?',
                    confirmRestore: 'Restore lokasi yang dipilih?',
                    confirmForceDelete: 'Hapus permanen lokasi yang dipilih?',
                };
            const addModal = document.getElementById('add-location-modal');
            const openAddModalBtn = document.getElementById('open-add-location-modal');
            const closeAddModalBtns = document.querySelectorAll('.close-location-modal');
            const locationRows = Array.from(document.querySelectorAll('.location-row'));
            const locationSearchInput = document.getElementById('location-search');
            const selectAllLocations = document.getElementById('select-all-locations');
            const selectedLocationCount = document.getElementById('selected-location-count');
            const locationSelectBoxes = Array.from(document.querySelectorAll('.location-select'));
            const emptyLocationSearchRow = document.getElementById('empty-location-search-row');
            const bulkLocationForm = document.getElementById('bulk-location-form');
            const bulkLocationAction = document.getElementById('bulk-location-action');
            const bulkLocationSelectedIds = document.getElementById('bulk-location-selected-ids');
            const bulkLocationButtons = Array.from(document.querySelectorAll('.bulk-location-btn'));
            const perPageSelect = document.getElementById('per-page-select');
            const nameInput = document.getElementById('name');
            const addressInput = document.getElementById('address');
            const latitudeInput = document.getElementById('location_latitude');
            const longitudeInput = document.getElementById('location_longitude');
            const searchInput = document.getElementById('place-search');
            const searchHelp = document.getElementById('place-search-help');
            const fallbackResults = document.getElementById('place-fallback-results');

            let marker = null;
            let fallbackDebounce = null;

            function openAddModal() {
                if (!addModal) return;
                addModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    map.invalidateSize();
                }, 50);
            }

            function closeAddModal() {
                if (!addModal) return;
                addModal.style.display = 'none';
                document.body.style.overflow = '';
            }

            const map = L.map('location-map').setView([-6.2000000, 106.8166660], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            function setHelpText(text) {
                searchHelp.textContent = text || '';
            }

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

            if (openAddModalBtn) {
                openAddModalBtn.addEventListener('click', openAddModal);
            }
            closeAddModalBtns.forEach((btn) => {
                btn.addEventListener('click', closeAddModal);
            });
            if (addModal) {
                addModal.addEventListener('click', function (event) {
                    if (event.target === addModal) {
                        closeAddModal();
                    }
                });
            }
            window.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeAddModal();
                }
            });

            @if($errors->any())
                openAddModal();
            @endif

            function getVisibleLocationRows() {
                return locationRows.filter((row) => row.style.display !== 'none');
            }

            function syncLocationSelectionUI() {
                const visibleRows = getVisibleLocationRows();
                const visibleCheckboxes = visibleRows
                    .map((row) => row.querySelector('.location-select'))
                    .filter(Boolean);
                const checkedVisible = visibleCheckboxes.filter((cb) => cb.checked).length;

                if (selectAllLocations) {
                    selectAllLocations.checked = visibleCheckboxes.length > 0 && checkedVisible === visibleCheckboxes.length;
                    selectAllLocations.indeterminate = checkedVisible > 0 && checkedVisible < visibleCheckboxes.length;
                }

                const selectedTotal = locationSelectBoxes.filter((cb) => cb.checked).length;
                if (selectedLocationCount) {
                    selectedLocationCount.textContent = selectedTotal + ' ' + uiText.selectedSuffix;
                }
            }

            function applyLocationFilter() {
                const keyword = (locationSearchInput?.value || '').toLowerCase().trim();
                let visibleCount = 0;

                locationRows.forEach((row) => {
                    const haystack = [
                        row.dataset.name || '',
                        row.dataset.address || '',
                        row.dataset.coordinate || '',
                        row.dataset.ip || '',
                    ].join(' ');
                    const visible = keyword === '' || haystack.includes(keyword);
                    row.style.display = visible ? '' : 'none';
                    const checkbox = row.querySelector('.location-select');
                    if (!visible && checkbox) {
                        checkbox.checked = false;
                    }
                    if (visible) {
                        visibleCount++;
                    }
                });

                if (emptyLocationSearchRow) {
                    emptyLocationSearchRow.style.display = locationRows.length > 0 && visibleCount === 0 ? '' : 'none';
                }
                syncLocationSelectionUI();
            }

            if (locationSearchInput) {
                locationSearchInput.addEventListener('input', applyLocationFilter);
            }
            if (selectAllLocations) {
                selectAllLocations.addEventListener('change', function () {
                    getVisibleLocationRows().forEach((row) => {
                        const checkbox = row.querySelector('.location-select');
                        if (checkbox) {
                            checkbox.checked = selectAllLocations.checked;
                        }
                    });
                    syncLocationSelectionUI();
                });
            }
            locationSelectBoxes.forEach((checkbox) => {
                checkbox.addEventListener('change', syncLocationSelectionUI);
            });
            bulkLocationButtons.forEach((button) => {
                button.addEventListener('click', async function () {
                    const action = this.dataset.action;
                    const selected = locationSelectBoxes.filter((cb) => cb.checked).map((cb) => cb.value);

                    if (!action || selected.length === 0 || !bulkLocationForm || !bulkLocationAction || !bulkLocationSelectedIds) {
                        window.AppDialog.alert(uiText.pickOne);
                        return;
                    }

                    const confirmText = action === 'delete'
                        ? uiText.confirmDelete
                        : (action === 'restore'
                            ? uiText.confirmRestore
                            : uiText.confirmForceDelete);
                    const confirmed = await window.AppDialog.confirm(confirmText);
                    if (!confirmed) {
                        return;
                    }

                    bulkLocationAction.value = action;
                    bulkLocationSelectedIds.innerHTML = selected
                        .map((id) => '<input type="hidden" name="selected_ids[]" value="' + id + '">')
                        .join('');
                    bulkLocationForm.submit();
                });
            });
            if (perPageSelect) {
                perPageSelect.addEventListener('change', function () {
                    const url = new URL(window.location.href);
                    url.searchParams.set('per_page', this.value);
                    url.searchParams.set('page', '1');
                    window.location.assign(url.toString());
                });
            }
            applyLocationFilter();
            syncLocationSelectionUI();

            setHelpText('Cari lokasi otomatis memakai OpenStreetMap.');
        })();
    </script>
@endsection
