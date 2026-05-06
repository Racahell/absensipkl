@extends('layouts.app', ['title' => $title])

@section('content')
    <style>
        .settings-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .settings-tab-btn {
            border: 1px solid var(--line);
            background: var(--accent-soft);
            color: var(--accent-text);
            padding: 8px 12px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
        }
        .settings-tab-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        .settings-pane {
            display: none;
        }
        .settings-pane.active {
            display: block;
        }
        .settings-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .settings-grid .full {
            grid-column: 1 / -1;
        }
        @media (max-width: 900px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
        .dropzone {
            border: 2px dashed #fdba74;
            border-radius: 12px;
            background: #fff7ed;
            padding: 16px;
            cursor: pointer;
            transition: all .2s ease;
            min-height: 92px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .dropzone:hover {
            border-color: #ea580c;
            background: #ffedd5;
        }
        .dropzone.is-dragover {
            border-color: #ea580c;
            background: #ffedd5;
            box-shadow: 0 0 0 3px #fed7aa;
        }
        .dropzone-title {
            font-weight: 700;
            color: #9a3412;
            margin-bottom: 6px;
        }
        .dropzone-meta {
            font-size: 13px;
            color: #7c2d12;
        }
        .dropzone input[type="file"] {
            display: none;
        }
        .upload-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .upload-item label.upload-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #9a3412;
        }
        @media (max-width: 900px) {
            .upload-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="card">
        <h3 class="mt-0 text-primary">Setting Website</h3>
        @if(session('success'))
            <div class="alert alert-success mb-10">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error mb-10">{{ $errors->first() }}</div>
        @endif

        <div class="settings-tabs">
            <button type="button" class="settings-tab-btn active" data-tab="identitas">Identitas</button>
            <button type="button" class="settings-tab-btn" data-tab="logo">Logo & Favicon</button>
            <button type="button" class="settings-tab-btn" data-tab="tema">Tema Warna</button>
            <button type="button" class="settings-tab-btn" data-tab="otomasi">Otomasi</button>
        </div>

        <form id="website-settings-form" method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="grid gap-8" style="max-width:980px;">
            @csrf
            @method('PUT')

            <section id="pane-identitas" class="settings-pane active panel">
                <h4 class="mt-0 text-primary">Identitas Website</h4>
                <div class="settings-grid">
                    <div>
                        <label>Nama Website</label>
                        <input name="app_name" value="{{ old('app_name', $settings['app_name'] ?? 'Absensi PKL') }}" required>
                    </div>
                    <div>
                        <label>Tagline</label>
                        <input name="app_tagline" value="{{ old('app_tagline', $settings['app_tagline'] ?? 'Absensi & Monitoring PKL') }}">
                    </div>
                    <div class="full">
                        <label>Alamat</label>
                        <input name="school_address" value="{{ old('school_address', $settings['school_address'] ?? '') }}">
                    </div>
                    <div>
                        <label>Manager/Penanggung Jawab</label>
                        <input name="school_manager" value="{{ old('school_manager', $settings['school_manager'] ?? '') }}">
                    </div>
                    <div class="full">
                        <label>Tanggal Merah / Libur</label>
                        <textarea name="holiday_dates" rows="6" placeholder="2026-01-01|Tahun Baru&#10;2026-03-29|Nyepi&#10;2026-04-18|Wafat Isa Almasih">{{ old('holiday_dates', $settings['holiday_dates'] ?? '') }}</textarea>
                        <small class="text-muted" style="display:block; margin-top:6px;">
                            Satu baris satu tanggal. Format: <code>YYYY-MM-DD|Keterangan</code>. Pemisah juga bisa koma atau titik koma.
                        </small>
                    </div>
                </div>
            </section>

            <section id="pane-logo" class="settings-pane panel">
                <h4 class="mt-0 text-primary">Branding</h4>
                <div class="upload-grid">
                    <div class="upload-item">
                        <label class="upload-label">Logo Baru (opsional)</label>
                        <label class="dropzone" data-dropzone for="app_logo_input">
                            <input id="app_logo_input" type="file" name="app_logo" accept="image/*">
                            <div class="dropzone-title">Drag & drop logo di sini</div>
                            <div class="dropzone-meta" data-file-label>atau klik untuk pilih file</div>
                        </label>
                    </div>
                    <div class="upload-item">
                        <label class="upload-label">Favicon Baru (opsional)</label>
                        <label class="dropzone" data-dropzone for="app_favicon_input">
                            <input id="app_favicon_input" type="file" name="app_favicon" accept="image/*">
                            <div class="dropzone-title">Drag & drop favicon di sini</div>
                            <div class="dropzone-meta" data-file-label>atau klik untuk pilih file</div>
                        </label>
                        <small class="text-muted" style="display:block; margin-top:8px;">
                            Jika kosong, favicon otomatis mengikuti logo terbaru.
                        </small>
                    </div>
                </div>
            </section>

            <section id="pane-tema" class="settings-pane panel">
                <h4 class="mt-0 text-primary">Tema Warna Website</h4>
                <div class="settings-grid">
                    <div>
                        <label>Warna Utama</label>
                        <input type="color" name="theme_primary" value="{{ old('theme_primary', $settings['theme_primary'] ?? '#f97316') }}">
                    </div>
                    <div>
                        <label>Warna Sidebar</label>
                        <input type="color" name="theme_sidebar" value="{{ old('theme_sidebar', $settings['theme_sidebar'] ?? '#ffffff') }}">
                    </div>
                    <div>
                        <label>Warna Tombol</label>
                        <input type="color" name="theme_button" value="{{ old('theme_button', $settings['theme_button'] ?? '#f97316') }}">
                    </div>
                    <div>
                        <label>Warna Background</label>
                        <input type="color" name="theme_background" value="{{ old('theme_background', $settings['theme_background'] ?? '#ffffff') }}">
                    </div>
                    <div>
                        <label>Warna Card</label>
                        <input type="color" name="theme_card" value="{{ old('theme_card', $settings['theme_card'] ?? '#ffffff') }}">
                    </div>
                </div>
                <div class="mt-10">
                    <label>Palette Bawaan</label>
                    <div class="flex gap-8 wrap">
                        <button type="button" class="btn btn-ghost theme-palette" data-theme='{"primary":"#f97316","sidebar":"#ffffff","button":"#f97316","background":"#ffffff","card":"#ffffff"}'>Default</button>
                        <button type="button" class="btn btn-ghost theme-palette" data-theme='{"primary":"#ea580c","sidebar":"#ffffff","button":"#ea580c","background":"#fffaf5","card":"#ffffff"}'>Sunset</button>
                        <button type="button" class="btn btn-ghost theme-palette" data-theme='{"primary":"#0f766e","sidebar":"#f0fdfa","button":"#0d9488","background":"#f8fafc","card":"#ffffff"}'>Teal</button>
                        <button type="button" class="btn btn-ghost theme-palette" data-theme='{"primary":"#1d4ed8","sidebar":"#eff6ff","button":"#2563eb","background":"#f8fafc","card":"#ffffff"}'>Ocean</button>
                    </div>
                </div>
            </section>

            <section id="pane-otomasi" class="settings-pane panel">
                <h4 class="mt-0 text-primary">Otomasi Reminder & Backup</h4>
                <div class="settings-grid">
                    <div>
                        <label style="display:flex; gap:8px; align-items:center;">
                            <input type="checkbox" name="guidance_reminder_enabled" value="1" {{ (string) old('guidance_reminder_enabled', $settings['guidance_reminder_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                            Aktifkan Reminder Catatan Bimbingan (Jumat)
                        </label>
                    </div>
                    <div>
                        <label style="display:flex; gap:8px; align-items:center;">
                            <input type="checkbox" name="monthly_backup_auto_enabled" value="1" {{ (string) old('monthly_backup_auto_enabled', $settings['monthly_backup_auto_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                            Aktifkan Backup Otomatis Bulanan
                        </label>
                    </div>
                    <div>
                        <label>Jam Reminder Pertama</label>
                        <input type="time" name="guidance_reminder_time_first" value="{{ old('guidance_reminder_time_first', $settings['guidance_reminder_time_first'] ?? '09:00') }}">
                    </div>
                    <div>
                        <label>Jam Reminder Tambahan</label>
                        <input type="time" name="guidance_reminder_time_followup" value="{{ old('guidance_reminder_time_followup', $settings['guidance_reminder_time_followup'] ?? '14:00') }}">
                    </div>
                    <div>
                        <label>Batas Waktu Pengisian</label>
                        <input type="time" name="guidance_reminder_deadline" value="{{ old('guidance_reminder_deadline', $settings['guidance_reminder_deadline'] ?? '23:59') }}">
                    </div>
                </div>
                <div class="mt-10">
                    <h5 style="margin:0 0 8px; color:#9a3412;">Kirim Reminder Sekarang</h5>
                    <div class="flex gap-8 wrap items-center mb-10">
                        <select form="reminder-now-form" name="reminder_type" style="max-width:220px;">
                            <option value="first">Reminder Pertama</option>
                            <option value="followup">Reminder Tambahan</option>
                        </select>
                        <button type="submit" form="reminder-now-form">Kirim Reminder</button>
                    </div>

                    <h5 style="margin:0 0 8px; color:#9a3412;">Log Reminder Email</h5>
                    <div class="table-wrap">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th>Email Tujuan</th>
                                    <th>Jenis Reminder</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                    <th>Pesan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($emailReminderLogs ?? []) as $log)
                                    <tr>
                                        <td>{{ $log->email }}</td>
                                        <td>{{ $log->reminder_type }}</td>
                                        <td>{{ $log->status }}</td>
                                        <td>{{ optional($log->sent_at)->format('d M Y H:i') }}</td>
                                        <td>{{ $log->message ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">Belum ada log reminder email.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <button class="logout-btn w-fit" type="submit">Simpan Setting</button>
        </form>
        <form id="reminder-now-form" method="POST" action="{{ route('settings.reminder-now') }}" style="display:none;">
            @csrf
        </form>
    </div>

    <script>
        (function () {
            const buttons = Array.from(document.querySelectorAll('.settings-tab-btn'));
            const panes = Array.from(document.querySelectorAll('.settings-pane'));

            function activate(tabId) {
                buttons.forEach((btn) => {
                    btn.classList.toggle('active', btn.dataset.tab === tabId);
                });
                panes.forEach((pane) => {
                    pane.classList.toggle('active', pane.id === 'pane-' + tabId);
                });
            }

            buttons.forEach((btn) => {
                btn.addEventListener('click', function () {
                    activate(btn.dataset.tab);
                });
            });

            const zones = Array.from(document.querySelectorAll('[data-dropzone]'));
            zones.forEach((zone) => {
                const input = zone.querySelector('input[type="file"]');
                const label = zone.querySelector('[data-file-label]');
                if (!input || !label) return;

                const setLabel = (file) => {
                    label.textContent = file ? ('File: ' + file.name) : 'atau klik untuk pilih file';
                };

                input.addEventListener('change', () => {
                    setLabel(input.files && input.files[0] ? input.files[0] : null);
                });

                ['dragenter', 'dragover'].forEach((eventName) => {
                    zone.addEventListener(eventName, (event) => {
                        event.preventDefault();
                        zone.classList.add('is-dragover');
                    });
                });

                ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
                    zone.addEventListener(eventName, (event) => {
                        event.preventDefault();
                        zone.classList.remove('is-dragover');
                    });
                });

                zone.addEventListener('drop', (event) => {
                    const files = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files : null;
                    if (!files || files.length === 0) return;
                    input.files = files;
                    setLabel(files[0]);
                });
            });

            const palettes = Array.from(document.querySelectorAll('.theme-palette'));
            const primary = document.querySelector('input[name="theme_primary"]');
            const sidebar = document.querySelector('input[name="theme_sidebar"]');
            const button = document.querySelector('input[name="theme_button"]');
            const background = document.querySelector('input[name="theme_background"]');
            const card = document.querySelector('input[name="theme_card"]');
            palettes.forEach((btn) => {
                btn.addEventListener('click', function () {
                    const raw = btn.getAttribute('data-theme');
                    if (!raw) return;
                    let data = null;
                    try { data = JSON.parse(raw); } catch (e) { data = null; }
                    if (!data) return;
                    if (primary) primary.value = data.primary || primary.value;
                    if (sidebar) sidebar.value = data.sidebar || sidebar.value;
                    if (button) button.value = data.button || button.value;
                    if (background) background.value = data.background || background.value;
                    if (card) card.value = data.card || card.value;
                });
            });
        })();
    </script>
@endsection
