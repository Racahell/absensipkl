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
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #9a3412;
            padding: 8px 12px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
        }
        .settings-tab-btn.active {
            background: #ea580c;
            border-color: #ea580c;
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

        <div class="settings-tabs">
            <button type="button" class="settings-tab-btn active" data-tab="identitas">Identitas</button>
            <button type="button" class="settings-tab-btn" data-tab="kontak-footer">Kontak & Footer</button>
            <button type="button" class="settings-tab-btn" data-tab="logo">Logo & Favicon</button>
        </div>

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="grid gap-8" style="max-width:980px;">
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
                    <div>
                        <label>Kontak</label>
                        <input name="school_contact" value="{{ old('school_contact', $settings['school_contact'] ?? '') }}">
                    </div>
                </div>
            </section>

            <section id="pane-kontak-footer" class="settings-pane panel">
                <h4 class="mt-0 text-primary">Footer</h4>
                <div class="settings-grid">
                    <div class="full">
                        <label>Teks Footer</label>
                        <input name="footer_text" value="{{ old('footer_text', $settings['footer_text'] ?? 'Absensi PKL') }}">
                    </div>
                    <div>
                        <label>Footer Link 1 (Nama)</label>
                        <input name="footer_link_1_label" value="{{ old('footer_link_1_label', $settings['footer_link_1_label'] ?? 'Privacy') }}">
                    </div>
                    <div>
                        <label>Footer Link 1 (URL)</label>
                        <input name="footer_link_1_url" value="{{ old('footer_link_1_url', $settings['footer_link_1_url'] ?? '#') }}">
                    </div>
                    <div>
                        <label>Footer Link 2 (Nama)</label>
                        <input name="footer_link_2_label" value="{{ old('footer_link_2_label', $settings['footer_link_2_label'] ?? 'Terms') }}">
                    </div>
                    <div>
                        <label>Footer Link 2 (URL)</label>
                        <input name="footer_link_2_url" value="{{ old('footer_link_2_url', $settings['footer_link_2_url'] ?? '#') }}">
                    </div>
                    <div>
                        <label>Footer Link 3 (Nama)</label>
                        <input name="footer_link_3_label" value="{{ old('footer_link_3_label', $settings['footer_link_3_label'] ?? 'Support') }}">
                    </div>
                    <div>
                        <label>Footer Link 3 (URL)</label>
                        <input name="footer_link_3_url" value="{{ old('footer_link_3_url', $settings['footer_link_3_url'] ?? '#') }}">
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

            <button class="logout-btn w-fit" type="submit">Simpan Setting</button>
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
        })();
    </script>
@endsection
