@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    @php
        $initial = strtoupper(substr(trim((string) $user->name), 0, 1) ?: 'U');
        $defaultAvatarSvg = "<svg xmlns='http://www.w3.org/2000/svg' width='512' height='512' viewBox='0 0 512 512'><defs><linearGradient id='g' x1='0' x2='1' y1='0' y2='1'><stop stop-color='#fb923c'/><stop offset='1' stop-color='#ea580c'/></linearGradient></defs><rect width='512' height='512' fill='url(#g)'/><circle cx='256' cy='200' r='96' fill='#ffedd5'/><path d='M96 448c28-88 96-136 160-136s132 48 160 136' fill='#ffedd5'/><text x='256' y='470' text-anchor='middle' font-size='64' fill='#7c2d12' font-family='Segoe UI,Tahoma,sans-serif' font-weight='700'>{$initial}</text></svg>";
        $defaultAvatar = 'data:image/svg+xml;utf8,'.rawurlencode($defaultAvatarSvg);
        $profilePhotoSrc = $user->profile_photo_path ? asset($user->profile_photo_path) : $defaultAvatar;
    @endphp

    <div class="card">
        <h3 class="mt-0 text-primary">Profil Saya</h3>
        <p class="text-muted" style="margin-top:-4px; margin-bottom:14px;">
            Foto profil bisa diambil dari kamera langsung atau upload file, lalu crop sesuai kebutuhan.
        </p>

        @if(session('success'))
            <div class="alert alert-success mb-14">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error mb-14">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form id="profile-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="cropped_photo" id="cropped_photo">

            <div class="grid grid-2 mb-14" style="align-items:stretch;">
                <div class="panel" style="display:flex; flex-direction:column; height:100%;">
                    <label>Preview Foto Profil</label>
                    <div style="margin-top:8px; display:flex; justify-content:center; align-items:center; flex:1;">
                        <img
                            id="avatar-preview"
                            src="{{ $profilePhotoSrc }}"
                            alt="Foto Profil"
                            style="width:220px; height:220px; object-fit:cover; border-radius:50%; border:2px solid var(--line); background:var(--surface);"
                        >
                    </div>
                    <div class="flex gap-8 wrap mt-10">
                        <button id="camera-open" type="button" class="btn-ghost">Ambil Dari Kamera</button>
                    </div>
                </div>

                <div class="panel" style="height:100%;">
                    <label for="photo-file">Pilih File Foto</label>
                    <input id="photo-file" type="file" name="photo" accept="image/*" class="mb-10">

                    <div class="grid grid-2 mb-10">
                        <div>
                            <label>Nama</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div>
                            <label>No WA</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="62812xxxx">
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div>
                            <label>NIS</label>
                            <input type="text" value="{{ $user->nis }}" readonly>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->pending_email ?: $user->email) }}" required>
                            <small class="text-muted" style="display:block; margin-top:6px;">
                                Jika email diganti, verifikasi email akan diminta ulang.
                            </small>
                            @if ($user->pending_email)
                                <small class="text-danger" style="display:block; margin-top:6px;">
                                    Email aktif saat ini: {{ $user->email }}. Email baru menunggu verifikasi: {{ $user->pending_email }}.
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel mb-14">
                <strong style="color:var(--accent-text);">Reset Password</strong>
                <p class="text-muted" style="margin:6px 0 10px;">Reset password dilakukan melalui email akun Anda.</p>
                <button type="submit" class="btn-ghost" form="reset-password-form">Kirim Link Reset Password Email</button>
            </div>

            <button type="submit">Simpan Profil</button>
        </form>

        <form id="reset-password-form" method="POST" action="{{ route('profile.password.email') }}" style="display:none;">
            @csrf
        </form>
    </div>

    <div
        id="camera-modal"
        style="display:none; position:fixed; inset:0; z-index:9998; background:rgba(17,24,39,.65); align-items:center; justify-content:center; padding:14px;"
    >
        <div style="width:min(860px, 100%); background:var(--surface); border:1px solid var(--line); border-radius:14px; padding:14px;">
            <div class="flex justify-between items-center mb-10">
                <strong style="color:var(--accent-text);">Ambil Foto Dari Kamera</strong>
                <button id="camera-close" type="button" class="btn-ghost">Tutup</button>
            </div>
            <div style="border:1px solid var(--line); border-radius:12px; background:var(--accent-soft); padding:8px;">
                <video id="camera-video" autoplay playsinline style="width:100%; border-radius:10px; background:#111827; transform:scaleX(-1);"></video>
            </div>
            <div class="flex gap-8 wrap mt-10" style="justify-content:flex-end;">
                <button id="camera-capture" type="button" class="btn">Ambil Foto</button>
            </div>
        </div>
    </div>

    <div
        id="crop-modal"
        style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(17,24,39,.65); align-items:center; justify-content:center; padding:14px;"
    >
        <div style="width:min(860px, 100%); max-height:92vh; overflow:auto; background:var(--surface); border:1px solid var(--line); border-radius:14px; padding:14px;">
            <div class="flex justify-between items-center mb-10">
                <strong style="color:var(--accent-text);">Crop Foto Profil</strong>
                <button id="crop-modal-close" type="button" class="btn-ghost">Tutup</button>
            </div>
            <div style="min-height:260px; border:1px solid var(--line); border-radius:12px; background:var(--accent-soft); padding:8px;">
                <img id="crop-modal-image" alt="Crop Foto Profil" style="max-width:100%; display:block;">
            </div>
            <div class="flex gap-8 wrap mt-10" style="justify-content:flex-end;">
                <button id="crop-modal-retake" type="button" class="btn-ghost" style="display:none;">Foto Ulang</button>
                <button id="crop-modal-cancel" type="button" class="btn-ghost">Batal</button>
                <button id="crop-modal-apply" type="button">Gunakan Foto</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script>
        (function () {
            const form = document.getElementById('profile-form');
            const fileInput = document.getElementById('photo-file');
            const hiddenCropped = document.getElementById('cropped_photo');
            const avatarPreview = document.getElementById('avatar-preview');
            const cropModal = document.getElementById('crop-modal');
            const cropModalImage = document.getElementById('crop-modal-image');
            const cropModalApply = document.getElementById('crop-modal-apply');
            const cropModalRetake = document.getElementById('crop-modal-retake');
            const cropModalCancel = document.getElementById('crop-modal-cancel');
            const cropModalClose = document.getElementById('crop-modal-close');

            const openCameraBtn = document.getElementById('camera-open');
            const captureBtn = document.getElementById('camera-capture');
            const closeCameraBtn = document.getElementById('camera-close');
            const cameraModal = document.getElementById('camera-modal');
            const video = document.getElementById('camera-video');

            let cropper = null;
            let activeStream = null;
            let photoChanged = false;
            let cropSourceType = 'file';

            function destroyCropper() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }

            function openCropModal(src, sourceType = 'file') {
                destroyCropper();
                cropSourceType = sourceType;
                cropModalRetake.style.display = sourceType === 'camera' ? 'inline-block' : 'none';
                cropModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                cropModalImage.src = src;
                cropModalImage.onload = function () {
                    cropper = new Cropper(cropModalImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        movable: true,
                        zoomable: true,
                        scalable: true,
                        responsive: true,
                        background: false,
                    });
                };
            }

            function closeCropModal() {
                destroyCropper();
                cropModal.style.display = 'none';
                document.body.style.overflow = '';
                cropModalImage.src = '';
            }

            function stopCamera() {
                if (activeStream) {
                    activeStream.getTracks().forEach(track => track.stop());
                    activeStream = null;
                }
                cameraModal.style.display = 'none';
                document.body.style.overflow = '';
            }

            async function startCamera() {
                hiddenCropped.value = '';
                try {
                    activeStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                    video.srcObject = activeStream;
                    cameraModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                } catch (err) {
                    const lang = window.localStorage.getItem('ui_lang') || 'id';
                    window.AppDialog.alert(
                        lang === 'en'
                            ? 'Camera cannot be accessed. Please ensure camera permission is enabled.'
                            : 'Kamera tidak dapat diakses. Pastikan izin kamera aktif.'
                    );
                }
            }

            fileInput.addEventListener('change', function (event) {
                const file = event.target.files && event.target.files[0];
                if (!file) {
                    return;
                }
                hiddenCropped.value = '';
                const url = URL.createObjectURL(file);
                openCropModal(url, 'file');
            });

            openCameraBtn.addEventListener('click', startCamera);

            closeCameraBtn.addEventListener('click', stopCamera);

            captureBtn.addEventListener('click', function () {
                if (!video.videoWidth || !video.videoHeight) {
                    return;
                }
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const dataUrl = canvas.toDataURL('image/png');
                stopCamera();
                openCropModal(dataUrl, 'camera');
            });

            cropModalRetake.addEventListener('click', function () {
                if (cropSourceType !== 'camera') {
                    return;
                }
                closeCropModal();
                startCamera();
            });

            cropModalApply.addEventListener('click', function () {
                if (!cropper) {
                    return;
                }
                const canvas = cropper.getCroppedCanvas({
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });
                const dataUrl = canvas.toDataURL('image/png');
                hiddenCropped.value = dataUrl;
                avatarPreview.src = dataUrl;
                photoChanged = true;
                closeCropModal();
            });

            cropModalCancel.addEventListener('click', closeCropModal);
            cropModalClose.addEventListener('click', closeCropModal);
            cameraModal.addEventListener('click', function (event) {
                if (event.target === cameraModal) {
                    stopCamera();
                }
            });
            cropModal.addEventListener('click', function (event) {
                if (event.target === cropModal) {
                    closeCropModal();
                }
            });

            form.addEventListener('submit', function () {
                if (!photoChanged) {
                    hiddenCropped.value = '';
                }
            });

            window.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && cropModal.style.display === 'flex') {
                    closeCropModal();
                    return;
                }
                if (event.key === 'Escape' && cameraModal.style.display === 'flex') {
                    stopCamera();
                }
            });

            window.addEventListener('beforeunload', stopCamera);
        })();
    </script>
@endsection
