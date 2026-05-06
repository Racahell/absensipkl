@extends('layouts.app', ['title' => $title])

@section('content')
    <style>
        .card a:hover {
            color: var(--accent);
        }

        .card button:hover {
            filter: brightness(0.95);
        }

        .card input:hover,
        .card select:hover,
        .card textarea:hover {
            border-color: var(--accent);
        }

        .import-progress-container {
            position: relative;
            width: 100%;
            height: 36px;
            background: linear-gradient(180deg, #ffedd5 0%, #fed7aa 100%);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: inset 0 0 0 1px var(--line), 0 6px 12px color-mix(in srgb, var(--accent-text) 18%, transparent);
            box-sizing: border-box;
        }

        .import-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #fb923c, #f97316, #ea580c);
            border-radius: 10px;
            box-shadow: 0 0 16px rgba(234, 88, 12, 0.45);
            transition: width 0.2s ease;
        }

        .import-progress-bar::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 220%;
            height: 220%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.26), transparent);
            opacity: 0.55;
            animation: import-progress-ripple 2.2s infinite;
        }

        .import-progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            color: #fff;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.45);
            z-index: 2;
            white-space: nowrap;
        }

        .import-progress-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            inset: 0;
            pointer-events: none;
        }

        .import-progress-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            opacity: 0.7;
            animation: import-progress-float 4.8s infinite ease-in-out;
        }

        @keyframes import-progress-ripple {
            0% {
                transform: translate(-50%, -50%) scale(0.45);
                opacity: 0.65;
            }
            100% {
                transform: translate(-50%, -50%) scale(1.45);
                opacity: 0;
            }
        }

        @keyframes import-progress-float {
            0% {
                transform: translateY(0) translateX(0);
            }
            50% {
                transform: translateY(-12px) translateX(8px);
            }
            100% {
                transform: translateY(0) translateX(0);
            }
        }

        .import-progress-particle:nth-child(1) { top: 12%; left: 18%; animation-delay: 0s; }
        .import-progress-particle:nth-child(2) { top: 34%; left: 74%; animation-delay: .8s; }
        .import-progress-particle:nth-child(3) { top: 62%; left: 56%; animation-delay: 1.4s; }
        .import-progress-particle:nth-child(4) { top: 78%; left: 38%; animation-delay: 1s; }
        .import-progress-particle:nth-child(5) { top: 88%; left: 68%; animation-delay: 1.9s; }
    </style>

    <div class="card" style="margin-bottom:14px;">
        <h3 style="margin-top:0; color:var(--accent-text);">Import User</h3>
        <p style="color:var(--accent-text); margin:0 0 6px;">Total Siswa: <strong>{{ $usersCount['siswa'] ?? 0 }}</strong></p>
        <p style="color:var(--accent-text); margin:0 0 6px;">Total Guru: <strong>{{ $usersCount['instruktur'] ?? 0 }}</strong></p>
        <p style="color:var(--accent-text); margin:0;">Total Kepsek: <strong>{{ $usersCount['kepsek'] ?? 0 }}</strong></p>
        @if(session('success'))<div style="padding:10px; border:1px solid #86efac; background:#f0fdf4; color:#166534; border-radius:8px; margin-bottom:10px;">{{ session('success') }}</div>@endif
        @if(session('error'))<div style="padding:10px; border:1px solid #fca5a5; background:#fef2f2; color:#991b1b; border-radius:8px; margin-bottom:10px;">{{ session('error') }}</div>@endif
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:14px;">
        <div class="card">
            <h4 style="margin-top:0; color:var(--accent-text);">Data User</h4>
            <a href="{{ route('import-export.users.export') }}">Download Template CSV User</a>
            <form id="import-users-form" method="POST" action="{{ route('import-export.users.import') }}" enctype="multipart/form-data" style="margin-top:10px; display:grid; gap:8px;">
                @csrf
                <input type="file" name="users_file" accept=".csv,text/csv" required>
                <div id="import-action-wrap">
                    <button id="import-users-submit" type="submit" style="width:100%;">Import CSV User</button>
                </div>
                <div id="import-action-progress" class="import-progress-container" style="display:none;">
                    <div id="import-progress-bar" class="import-progress-bar"></div>
                    <div class="import-progress-particles">
                        <span class="import-progress-particle"></span>
                        <span class="import-progress-particle"></span>
                        <span class="import-progress-particle"></span>
                        <span class="import-progress-particle"></span>
                        <span class="import-progress-particle"></span>
                    </div>
                    <div id="import-progress-text" class="import-progress-text">0%</div>
                </div>
                <small id="import-progress-detail" class="text-muted" style="display:none;">Processing row 0/0</small>
            </form>
            <small>
                Gunakan file template dari tombol di atas.
                Header wajib dan urutan kolom harus sama:
                `nis_nuptk;nama;email;password;role;jurusan;kelas;tempat_pkl`.
            </small>
        </div>
        <div class="card">
            <h4 style="margin-top:0; color:var(--accent-text);">Panduan Import User</h4>
            <div class="text-muted" style="font-size:13px; line-height:1.5;">
                <p style="margin:0 0 8px;">Import user untuk menambah akun massal via Excel/CSV.</p>
                <p style="margin:0 0 6px;"><strong>Role didukung:</strong> siswa, admin_sekolah, pembimbing_pkl, instruktur, kajur, wali_kelas, kesiswaan, kepsek, wakil_kepsek.</p>
                <p style="margin:0 0 6px;"><strong>Format delimiter:</strong> delimiter harus konsisten <code>;</code> (titik koma), bukan campur <code>,</code> (koma).</p>
                <p style="margin:0 0 6px;"><strong>Catatan:</strong> wajib membuat data master jurusan (department) dan kelas terlebih dahulu sebelum import user.</p>
                <p style="margin:0 0 6px;"><strong>Aturan identitas:</strong> email valid/asli → non-siswa NUPTK opsional. Email tidak valid/asli → NIS/NUPTK wajib.</p>
                <p style="margin:0 0 4px;"><strong>Wajib per role:</strong></p>
                <ul style="margin:0; padding-left:18px;">
                    <li><code>siswa</code>: semua kolom wajib diisi terkhusus untuk role siswa.</li>
                    <li><code>admin_sekolah</code>: email + password.</li>
                    <li><code>pembimbing_pkl</code>: email + password + jurusan (NUPTK opsional jika email valid).<br>NUPTK wajib diisi jika email tidak asli.</li>
                    <li><code>instruktur</code>: email asli + password + tempat PKL.</li>
                    <li><code>kajur</code>: email + password + jurusan.</li>
                    <li><code>wali_kelas</code>: email + password + jurusan + kelas.</li>
                    <li><code>kesiswaan</code>, <code>kepsek</code>, <code>wakil_kepsek</code>: email + password.</li>
                </ul>
            </div>
        </div>

        <div class="card">
            <h4 style="margin-top:0; color:var(--accent-text);">Export User</h4>
            <form method="GET" action="{{ route('import-export.users.export-data') }}" class="grid gap-8">
                <div>
                    <label>Role</label>
                    <select name="role">
                        <option value="">Semua Role</option>
                        @foreach (($exportRoles ?? []) as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Jurusan</label>
                    <select name="jurusan">
                        <option value="">Semua Jurusan</option>
                        @foreach (($exportDepartments ?? []) as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Kelas</label>
                    <select name="kelas">
                        <option value="">Semua Kelas</option>
                        @foreach (($exportClasses ?? []) as $cls)
                            <option value="{{ $cls }}">{{ $cls }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Tempat PKL</label>
                    <select name="tempat_pkl">
                        <option value="">Semua Tempat PKL</option>
                        @foreach (($exportLocations ?? []) as $loc)
                            <option value="{{ $loc }}">{{ $loc }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit">Export User CSV</button>
                <small class="text-muted">Password tidak ikut diexport.</small>
            </form>
        </div>

    </div>

    <script>
        (function () {
            const form = document.getElementById('import-users-form');
            if (!form || !window.fetch) return;

            const submitBtn = document.getElementById('import-users-submit');
            const actionWrap = document.getElementById('import-action-wrap');
            const actionProgress = document.getElementById('import-action-progress');
            const progressBar = document.getElementById('import-progress-bar');
            const progressText = document.getElementById('import-progress-text');
            const progressDetail = document.getElementById('import-progress-detail');
            const fileInput = form.querySelector('input[name="users_file"]');

            const initUrl = @json(route('import-export.users.import.init'));
            const processUrl = @json(route('import-export.users.import.process'));

            function setLoading(isLoading) {
                submitBtn.disabled = isLoading;
                fileInput.disabled = isLoading;
                submitBtn.textContent = isLoading ? 'Importing...' : 'Import CSV User';
                actionWrap.style.display = isLoading ? 'none' : 'block';
                actionProgress.style.display = isLoading ? 'block' : 'none';
                progressDetail.style.display = isLoading ? 'block' : 'none';
            }

            function updateProgress(progress) {
                const percent = Number(progress?.percent || 0);
                const doneRows = Number(progress?.done_rows || 0);
                const totalRows = Number(progress?.total_rows || 0);
                progressBar.style.width = percent + '%';
                progressText.textContent = percent + '% (' + doneRows + '/' + totalRows + ' baris)';
                progressDetail.textContent = 'Processing row ' + doneRows + '/' + totalRows;
            }

            async function processLoop(token) {
                while (true) {
                    const response = await fetch(processUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: (() => {
                            const fd = new FormData();
                            fd.append('_token', form.querySelector('input[name="_token"]').value);
                            fd.append('token', token);
                            return fd;
                        })(),
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload.ok) {
                        throw new Error(payload.message || 'Import process gagal.');
                    }

                    updateProgress(payload.progress);

                    if (payload.completed) {
                        if (payload.message) {
                            await window.AppDialog.alert(payload.message);
                        }
                        window.location.reload();
                        return;
                    }
                }
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                const file = fileInput.files && fileInput.files[0];
                if (!file) return;

                try {
                    const initBody = new FormData(form);
                    setLoading(true);
                    updateProgress({ percent: 0, done_rows: 0, total_rows: 0 });

                    const initResponse = await fetch(initUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: initBody,
                    });

                    const initPayload = await initResponse.json();
                    if (!initResponse.ok || !initPayload.ok || !initPayload.token) {
                        throw new Error(initPayload.message || 'Import init gagal.');
                    }

                    updateProgress(initPayload.progress);
                    await processLoop(initPayload.token);
                } catch (error) {
                    window.AppDialog.alert(error?.message || 'Terjadi kesalahan saat import.');
                    setLoading(false);
                }
            });
        })();
    </script>
@endsection
