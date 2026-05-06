@extends('layouts.app', ['title' => 'Absensi Harian'])

@section('content')
    <style>
        .attendance-action-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .attendance-action-row .submit-btn {
            margin-left: auto;
        }

        @media (max-width: 640px) {
            .attendance-action-row .submit-btn {
                margin-left: 0;
                width: 100%;
            }
        }

        .attendance-location-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--accent-soft);
            padding: 10px 12px;
            margin-bottom: 12px;
        }

        .attendance-location-text {
            color: var(--accent-text);
            font-weight: 600;
        }

        .attendance-location-link {
            display: none;
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 6px 10px;
            background: var(--surface);
            color: var(--accent-text);
            font-size: 13px;
            font-weight: 600;
        }

        .attendance-media-frame {
            border: 1px solid var(--line);
            border-radius: 10px;
        }

        .attendance-camera-card {
            width: min(460px, 100%);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
        }

        .attendance-camera-title {
            margin-top: 0;
            margin-bottom: 10px;
            color: var(--accent-text);
        }
    </style>

    @if (session('success'))
        <div class="card alert mb-16">
            {{ session('success') }}
        </div>
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

    <div class="card mb-16">
        <h3 class="mt-0">Status Hari Ini</h3>
        @if ($todayAttendance)
            @php
                $formatStatus = static function (?string $status): string {
                    $raw = strtolower(trim((string) $status));
                    return match (true) {
                        $raw === '', $raw === '-' => '-',
                        $raw === 'pending_pembimbing' => 'pending pembimbing sekolah',
                        $raw === 'pending_instruktur' => 'approved pembimbing sekolah',
                        $raw === 'pending_kajur' => 'approved instruktur',
                        $raw === 'hadir',
                        $raw === 'approved_final',
                        str_starts_with($raw, 'approved'),
                        str_starts_with($raw, 'reviewed_') => 'approved',
                        default => str_replace('_', ' ', $raw),
                    };
                };
                $statusLabel = $formatStatus($todayAttendance->status ?? '-');
                $reportStatusLabel = $formatStatus($todayAttendance->report?->review_status ?? '-');
            @endphp
            <p>Status: <strong>{{ $statusLabel }}</strong></p>
            <p>Status Laporan: <strong>{{ $reportStatusLabel }}</strong></p>
        @else
            <p>Belum ada absensi untuk hari ini.</p>
        @endif
    </div>

    <div class="card mb-16" id="checkin-card">
        <h3 class="mt-0">Check-in</h3>
        <form action="{{ route('absensi.checkin') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="checkin-latitude" name="latitude" required>
            <input type="hidden" id="checkin-longitude" name="longitude" required>
            <input type="hidden" name="request_token" value="{{ $checkinToken }}">
            <div class="attendance-location-box">
                <span id="checkin-location-info" class="attendance-location-text">Mengambil lokasi otomatis...</span>
                <a id="checkin-location-link" class="attendance-location-link" href="#" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a>
            </div>
            <div id="checkin-map-wrapper" style="display:none; margin-bottom:12px;">
                <iframe
                    id="checkin-map"
                    title="Peta Lokasi Check-in"
                    width="100%"
                    height="240"
                    class="attendance-media-frame"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <label>Foto Selfie</label>
            <p class="text-muted">Foto wajib diambil langsung dari kamera perangkat.</p>
            <div id="checkin-photo-preview-wrapper" style="display:none; margin-bottom:10px;">
                <img
                    id="checkin-photo-preview"
                    alt="Preview Selfie Check-in"
                    style="width:100%; max-width:360px; display:block;"
                    class="attendance-media-frame">
            </div>
            <p id="checkin-camera-info" class="text-primary" style="margin-top:8px;">Belum ada foto selfie.</p>
            <div class="attendance-action-row">
                <button id="open-checkin-camera-modal-btn" type="button">Ambil Foto (Kamera)</button>
                <button id="retake-checkin-btn" type="button" style="display:none;">Ambil Ulang</button>
                <button class="submit-btn" type="submit">
                    Check in
                </button>
            </div>
            <input type="file" id="checkin-selfie-file" name="selfie" accept="image/*" required style="display:none;">
        </form>

        <div
            id="checkin-camera-modal"
            style="display:none; position:fixed; inset:0; background:rgba(17, 24, 39, 0.65); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div class="attendance-camera-card">
                <h4 class="attendance-camera-title">Ambil Foto Check-in</h4>
                <video
                    id="checkin-camera"
                    autoplay
                    playsinline
                    muted
                    style="width:100%; background:#111;"
                    class="attendance-media-frame">
                </video>
                <canvas
                    id="checkin-canvas"
                    style="display:none; width:100%; margin-top:10px;"
                    class="attendance-media-frame">
                </canvas>
                <p id="checkin-modal-info" class="text-primary" style="margin-top:8px;">Mengaktifkan kamera...</p>
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                    <button id="capture-checkin-btn" type="button">Jepret</button>
                    <button id="use-checkin-photo-btn" type="button" style="display:none;">Gunakan Foto Ini</button>
                    <button id="close-checkin-camera-modal-btn" type="button">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card" id="checkout-card">
        <h3 class="mt-0">Check-out + Daily Report</h3>
        <form action="{{ route('absensi.checkout') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="checkout-latitude" name="latitude" required>
            <input type="hidden" id="checkout-longitude" name="longitude" required>
            <input type="hidden" name="request_token" value="{{ $checkoutToken }}">
            <div class="attendance-location-box">
                <span id="checkout-location-info" class="attendance-location-text">Mengambil lokasi otomatis...</span>
                <a id="checkout-location-link" class="attendance-location-link" href="#" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a>
            </div>
            <div id="checkout-map-wrapper" style="display:none; margin-bottom:12px;">
                <iframe
                    id="checkout-map"
                    title="Peta Lokasi Check-out"
                    width="100%"
                    height="240"
                    class="attendance-media-frame"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <label>Rencana Pekerjaan</label>
            <textarea name="plan_work" rows="3" class="mb-10" required></textarea>
            <label>Realisasi Pekerjaan</label>
            <textarea name="actual_work" rows="3" class="mb-10" required></textarea>
            <label>Penugasan Khusus dari Atasan</label>
            <textarea name="assigned_task" rows="3" class="mb-10"></textarea>
            <label>Penemuan Masalah di Lapangan</label>
            <textarea name="field_issue" rows="3" class="mb-10"></textarea>
            <label>Catatan untuk Diingat</label>
            <textarea name="remember_note" rows="3" class="mb-10"></textarea>
            <label>Bukti Foto (wajib)</label>
            <p class="text-muted">Bukti foto wajib diambil langsung dari kamera perangkat.</p>
            <div id="checkout-photo-preview-wrapper" style="display:none; margin-bottom:10px;">
                <img
                    id="checkout-photo-preview"
                    alt="Preview Bukti Foto Check-out"
                    style="width:100%; max-width:360px; display:block;"
                    class="attendance-media-frame">
            </div>
            <p id="checkout-camera-info" class="text-primary" style="margin-top:8px;">Belum ada bukti foto.</p>
            <div class="attendance-action-row">
                <button id="open-checkout-camera-modal-btn" type="button">Ambil Bukti Foto (Kamera)</button>
                <button id="retake-checkout-btn" type="button" style="display:none;">Ambil Ulang</button>
                <button class="submit-btn" type="submit">
                    Kirim Check-out + Report
                </button>
            </div>
            <input type="file" id="checkout-evidence-file" name="evidence" accept="image/*" required style="display:none;">
        </form>

        <div
            id="checkout-camera-modal"
            style="display:none; position:fixed; inset:0; background:rgba(17, 24, 39, 0.65); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div class="attendance-camera-card">
                <h4 class="attendance-camera-title">Ambil Bukti Foto Check-out</h4>
                <video
                    id="checkout-camera"
                    autoplay
                    playsinline
                    muted
                    style="width:100%; background:#111;"
                    class="attendance-media-frame">
                </video>
                <canvas
                    id="checkout-canvas"
                    style="display:none; width:100%; margin-top:10px;"
                    class="attendance-media-frame">
                </canvas>
                <p id="checkout-modal-info" class="text-primary" style="margin-top:8px;">Mengaktifkan kamera...</p>
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                    <button id="capture-checkout-btn" type="button">Jepret</button>
                    <button id="use-checkout-photo-btn" type="button" style="display:none;">Gunakan Foto Ini</button>
                    <button id="close-checkout-camera-modal-btn" type="button">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setLocation(targetPrefix, latitude, longitude) {
            const latEl = document.getElementById(targetPrefix + '-latitude');
            const lonEl = document.getElementById(targetPrefix + '-longitude');
            const infoEl = document.getElementById(targetPrefix + '-location-info');
            const linkEl = document.getElementById(targetPrefix + '-location-link');

            latEl.value = latitude.toFixed(7);
            lonEl.value = longitude.toFixed(7);
            const mapsUrl = `https://www.google.com/maps?q=${latEl.value},${lonEl.value}`;
            infoEl.textContent = 'Lokasi terdeteksi.';
            infoEl.style.color = '#166534';
            if (linkEl) {
                linkEl.href = mapsUrl;
                linkEl.style.display = 'inline-flex';
            }

            const mapEl = document.getElementById(targetPrefix + '-map');
            const mapWrapper = document.getElementById(targetPrefix + '-map-wrapper');
            if (mapEl && mapWrapper) {
                mapEl.src = `https://maps.google.com/maps?q=${latEl.value},${lonEl.value}&z=17&output=embed`;
                mapWrapper.style.display = 'block';
            }
        }

        function setLocationError(targetPrefix, message) {
            const infoEl = document.getElementById(targetPrefix + '-location-info');
            const linkEl = document.getElementById(targetPrefix + '-location-link');
            infoEl.textContent = message;
            infoEl.style.color = '#b91c1c';
            if (linkEl) {
                linkEl.style.display = 'none';
                linkEl.removeAttribute('href');
            }

            const mapWrapper = document.getElementById(targetPrefix + '-map-wrapper');
            if (mapWrapper) {
                mapWrapper.style.display = 'none';
            }
        }

        function applyCurrentLocation() {
            if (!navigator.geolocation) {
                setLocationError('checkin', 'Browser tidak mendukung geolocation.');
                setLocationError('checkout', 'Browser tidak mendukung geolocation.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setLocation('checkin', position.coords.latitude, position.coords.longitude);
                    setLocation('checkout', position.coords.latitude, position.coords.longitude);
                },
                () => {
                    setLocationError('checkin', 'Lokasi tidak diizinkan. Izinkan lokasi untuk check-in.');
                    setLocationError('checkout', 'Lokasi tidak diizinkan. Izinkan lokasi untuk check-out.');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        let checkinCameraStream = null;
        let checkinPreviewUrl = null;
        let checkoutCameraStream = null;
        let checkoutPreviewUrl = null;

        function stopCheckinCamera() {
            if (!checkinCameraStream) return;
            checkinCameraStream.getTracks().forEach((track) => track.stop());
            checkinCameraStream = null;
        }

        function stopCheckoutCamera() {
            if (!checkoutCameraStream) return;
            checkoutCameraStream.getTracks().forEach((track) => track.stop());
            checkoutCameraStream = null;
        }

        async function initCheckinCamera() {
            const videoEl = document.getElementById('checkin-camera');
            const infoEl = document.getElementById('checkin-modal-info');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                infoEl.textContent = 'Browser tidak mendukung akses kamera.';
                infoEl.style.color = '#b91c1c';
                return;
            }

            try {
                checkinCameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false,
                });
                videoEl.srcObject = checkinCameraStream;
                infoEl.textContent = 'Kamera aktif. Tekan "Ambil Foto".';
                infoEl.style.color = '#166534';
            } catch (error) {
                infoEl.textContent = 'Akses kamera ditolak. Izinkan kamera untuk check-in.';
                infoEl.style.color = '#b91c1c';
            }
        }

        async function initCheckoutCamera() {
            const videoEl = document.getElementById('checkout-camera');
            const infoEl = document.getElementById('checkout-modal-info');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                infoEl.textContent = 'Browser tidak mendukung akses kamera.';
                infoEl.style.color = '#b91c1c';
                return;
            }

            try {
                checkoutCameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' },
                    audio: false,
                });
                videoEl.srcObject = checkoutCameraStream;
                infoEl.textContent = 'Kamera aktif. Tekan "Jepret".';
                infoEl.style.color = '#166534';
            } catch (error) {
                infoEl.textContent = 'Akses kamera ditolak. Izinkan kamera untuk bukti foto.';
                infoEl.style.color = '#b91c1c';
            }
        }

        function bindCheckinCapture() {
            const modalEl = document.getElementById('checkin-camera-modal');
            const videoEl = document.getElementById('checkin-camera');
            const canvasEl = document.getElementById('checkin-canvas');
            const openModalBtn = document.getElementById('open-checkin-camera-modal-btn');
            const closeModalBtn = document.getElementById('close-checkin-camera-modal-btn');
            const captureBtn = document.getElementById('capture-checkin-btn');
            const usePhotoBtn = document.getElementById('use-checkin-photo-btn');
            const retakeBtn = document.getElementById('retake-checkin-btn');
            const fileInput = document.getElementById('checkin-selfie-file');
            const modalInfoEl = document.getElementById('checkin-modal-info');
            const infoEl = document.getElementById('checkin-camera-info');
            const previewWrapperEl = document.getElementById('checkin-photo-preview-wrapper');
            const previewEl = document.getElementById('checkin-photo-preview');
            const checkinForm = document.querySelector('form[action="{{ route('absensi.checkin') }}"]');
            let capturedBlob = null;

            const openModal = async () => {
                modalEl.style.display = 'flex';
                videoEl.style.display = 'block';
                canvasEl.style.display = 'none';
                usePhotoBtn.style.display = 'none';
                capturedBlob = null;
                await initCheckinCamera();
            };

            const closeModal = () => {
                stopCheckinCamera();
                modalEl.style.display = 'none';
            };

            openModalBtn.addEventListener('click', openModal);
            closeModalBtn.addEventListener('click', closeModal);
            modalEl.addEventListener('click', (event) => {
                if (event.target === modalEl) {
                    closeModal();
                }
            });

            captureBtn.addEventListener('click', () => {
                if (!checkinCameraStream) {
                    modalInfoEl.textContent = 'Kamera belum aktif.';
                    modalInfoEl.style.color = '#b91c1c';
                    return;
                }

                const width = videoEl.videoWidth || 720;
                const height = videoEl.videoHeight || 1280;
                canvasEl.width = width;
                canvasEl.height = height;
                const ctx = canvasEl.getContext('2d');
                ctx.drawImage(videoEl, 0, 0, width, height);

                canvasEl.toBlob((blob) => {
                    if (!blob) {
                        modalInfoEl.textContent = 'Gagal mengambil foto. Coba lagi.';
                        modalInfoEl.style.color = '#b91c1c';
                        return;
                    }

                    capturedBlob = blob;
                    videoEl.style.display = 'none';
                    canvasEl.style.display = 'block';
                    usePhotoBtn.style.display = 'inline-block';
                    modalInfoEl.textContent = 'Foto berhasil dijepret. Gunakan foto ini atau jepret ulang.';
                    modalInfoEl.style.color = '#166534';
                }, 'image/jpeg', 0.9);
            });

            usePhotoBtn.addEventListener('click', () => {
                if (!capturedBlob) {
                    modalInfoEl.textContent = 'Belum ada foto yang diambil.';
                    modalInfoEl.style.color = '#b91c1c';
                    return;
                }

                const file = new File([capturedBlob], `selfie-${Date.now()}.jpg`, { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;

                if (checkinPreviewUrl) {
                    URL.revokeObjectURL(checkinPreviewUrl);
                }
                checkinPreviewUrl = URL.createObjectURL(file);
                previewEl.src = checkinPreviewUrl;
                previewWrapperEl.style.display = 'block';

                retakeBtn.style.display = 'inline-block';
                infoEl.textContent = 'Foto selfie siap dikirim.';
                infoEl.style.color = '#166534';
                closeModal();
            });

            retakeBtn.addEventListener('click', () => {
                fileInput.value = '';
                retakeBtn.style.display = 'none';
                previewWrapperEl.style.display = 'none';
                if (checkinPreviewUrl) {
                    URL.revokeObjectURL(checkinPreviewUrl);
                    checkinPreviewUrl = null;
                }
                infoEl.textContent = 'Silakan ambil foto selfie.';
                infoEl.style.color = '#9a3412';
                openModal();
            });

            if (checkinForm) {
                checkinForm.addEventListener('submit', (event) => {
                    if (!fileInput.files || fileInput.files.length === 0) {
                        event.preventDefault();
                        infoEl.textContent = 'Ambil foto selfie terlebih dahulu.';
                        infoEl.style.color = '#b91c1c';
                    }
                });
            }
        }

        function bindCheckoutCapture() {
            const modalEl = document.getElementById('checkout-camera-modal');
            const videoEl = document.getElementById('checkout-camera');
            const canvasEl = document.getElementById('checkout-canvas');
            const openModalBtn = document.getElementById('open-checkout-camera-modal-btn');
            const closeModalBtn = document.getElementById('close-checkout-camera-modal-btn');
            const captureBtn = document.getElementById('capture-checkout-btn');
            const usePhotoBtn = document.getElementById('use-checkout-photo-btn');
            const retakeBtn = document.getElementById('retake-checkout-btn');
            const fileInput = document.getElementById('checkout-evidence-file');
            const modalInfoEl = document.getElementById('checkout-modal-info');
            const infoEl = document.getElementById('checkout-camera-info');
            const previewWrapperEl = document.getElementById('checkout-photo-preview-wrapper');
            const previewEl = document.getElementById('checkout-photo-preview');
            const checkoutForm = document.querySelector('form[action="{{ route('absensi.checkout') }}"]');
            let capturedBlob = null;

            const openModal = async () => {
                modalEl.style.display = 'flex';
                videoEl.style.display = 'block';
                canvasEl.style.display = 'none';
                usePhotoBtn.style.display = 'none';
                capturedBlob = null;
                await initCheckoutCamera();
            };

            const closeModal = () => {
                stopCheckoutCamera();
                modalEl.style.display = 'none';
            };

            openModalBtn.addEventListener('click', openModal);
            closeModalBtn.addEventListener('click', closeModal);
            modalEl.addEventListener('click', (event) => {
                if (event.target === modalEl) {
                    closeModal();
                }
            });

            captureBtn.addEventListener('click', () => {
                if (!checkoutCameraStream) {
                    modalInfoEl.textContent = 'Kamera belum aktif.';
                    modalInfoEl.style.color = '#b91c1c';
                    return;
                }

                const width = videoEl.videoWidth || 1280;
                const height = videoEl.videoHeight || 720;
                canvasEl.width = width;
                canvasEl.height = height;
                const ctx = canvasEl.getContext('2d');
                ctx.drawImage(videoEl, 0, 0, width, height);

                canvasEl.toBlob((blob) => {
                    if (!blob) {
                        modalInfoEl.textContent = 'Gagal mengambil foto. Coba lagi.';
                        modalInfoEl.style.color = '#b91c1c';
                        return;
                    }

                    capturedBlob = blob;
                    videoEl.style.display = 'none';
                    canvasEl.style.display = 'block';
                    usePhotoBtn.style.display = 'inline-block';
                    modalInfoEl.textContent = 'Foto berhasil dijepret. Gunakan foto ini atau jepret ulang.';
                    modalInfoEl.style.color = '#166534';
                }, 'image/jpeg', 0.9);
            });

            usePhotoBtn.addEventListener('click', () => {
                if (!capturedBlob) {
                    modalInfoEl.textContent = 'Belum ada foto yang diambil.';
                    modalInfoEl.style.color = '#b91c1c';
                    return;
                }

                const file = new File([capturedBlob], `evidence-${Date.now()}.jpg`, { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;

                if (checkoutPreviewUrl) {
                    URL.revokeObjectURL(checkoutPreviewUrl);
                }
                checkoutPreviewUrl = URL.createObjectURL(file);
                previewEl.src = checkoutPreviewUrl;
                previewWrapperEl.style.display = 'block';

                retakeBtn.style.display = 'inline-block';
                infoEl.textContent = 'Bukti foto siap dikirim.';
                infoEl.style.color = '#166534';
                closeModal();
            });

            retakeBtn.addEventListener('click', () => {
                fileInput.value = '';
                retakeBtn.style.display = 'none';
                previewWrapperEl.style.display = 'none';
                if (checkoutPreviewUrl) {
                    URL.revokeObjectURL(checkoutPreviewUrl);
                    checkoutPreviewUrl = null;
                }
                infoEl.textContent = 'Silakan ambil bukti foto.';
                infoEl.style.color = '#9a3412';
                openModal();
            });

            if (checkoutForm) {
                checkoutForm.addEventListener('submit', (event) => {
                    if (!fileInput.files || fileInput.files.length === 0) {
                        event.preventDefault();
                        infoEl.textContent = 'Ambil bukti foto terlebih dahulu.';
                        infoEl.style.color = '#b91c1c';
                    }
                });
            }
        }

        applyCurrentLocation();
        bindCheckinCapture();
        bindCheckoutCapture();

        window.addEventListener('beforeunload', () => {
            stopCheckinCamera();
            stopCheckoutCamera();
            if (checkinPreviewUrl) {
                URL.revokeObjectURL(checkinPreviewUrl);
            }
            if (checkoutPreviewUrl) {
                URL.revokeObjectURL(checkoutPreviewUrl);
            }
        });
    </script>
@endsection

