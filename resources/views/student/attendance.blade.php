<x-student-layout title="Absensi">
    @php
        $hasCheckedIn = $attendance !== null;
        $hasCheckedOut = $attendance?->check_out_at !== null;
    @endphp
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div><p class="text-sm font-semibold text-sky-700">Absensi PKL hari ini</p><h2 class="mt-1 text-2xl font-bold text-slate-900">{{ ! $hasCheckedIn ? 'Belum absen' : ($hasCheckedOut ? 'Sudah pulang' : 'Sudah masuk') }}</h2><p class="mt-2 text-sm text-slate-500">{{ today()->translatedFormat('l, d F Y') }}</p></div>
            <span @class(['self-start rounded-full px-3 py-1.5 text-xs font-bold', 'bg-slate-100 text-slate-600' => ! $hasCheckedIn, 'bg-amber-50 text-amber-700' => $hasCheckedIn && ! $hasCheckedOut, 'bg-emerald-50 text-emerald-700' => $hasCheckedOut])>{{ ! $hasCheckedIn ? 'Menunggu absen masuk' : ($hasCheckedOut ? 'Absensi selesai' : 'Menunggu absen pulang') }}</span>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jam masuk</p><p class="mt-1 text-xl font-bold text-slate-900">{{ $attendance?->check_in_at?->format('H:i') ?? '—' }}</p></div>
            <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jam pulang</p><p class="mt-1 text-xl font-bold text-slate-900">{{ $attendance?->check_out_at?->format('H:i') ?? '—' }}</p></div>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2">
        <button type="button" data-attendance-action="{{ route('student.attendance.check-in') }}" data-attendance-label="Absen Masuk" @disabled($hasCheckedIn) class="rounded-2xl bg-sky-700 p-5 text-left text-white shadow-sm transition enabled:hover:bg-sky-800 disabled:cursor-not-allowed disabled:bg-slate-300"><span class="block text-sm font-semibold opacity-80">Mulai kegiatan PKL</span><span class="mt-2 block text-xl font-bold">{{ $hasCheckedIn ? 'Absen Masuk Selesai' : 'Absen Masuk' }}</span></button>
        <button type="button" data-attendance-action="{{ route('student.attendance.check-out') }}" data-attendance-label="Absen Pulang" @disabled(! $hasCheckedIn || $hasCheckedOut) class="rounded-2xl bg-blue-600 p-5 text-left text-white shadow-sm transition enabled:hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"><span class="block text-sm font-semibold opacity-80">Akhiri kegiatan PKL</span><span class="mt-2 block text-xl font-bold">{{ $hasCheckedOut ? 'Absen Pulang Selesai' : 'Absen Pulang' }}</span></button>
    </section>

    <section class="mt-5 rounded-2xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-900">
        <p class="font-semibold">Sebelum melakukan absensi</p>
        <p class="mt-1 leading-6">Aktifkan GPS dan izinkan kamera. Lokasi dan selfie wajib diambil saat proses absensi. Waktu absensi dicatat langsung oleh server.</p>
    </section>

    <p id="attendanceError" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert"></p>
    <p id="attendanceHttpsWarning" class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium leading-6 text-amber-900" role="alert">Absensi GPS dan kamera memerlukan HTTPS. Gunakan alamat HTTPS atau localhost untuk melakukan absensi.</p>

    <dialog id="attendanceDialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-3xl border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-950/60">
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-semibold text-sky-700">Konfirmasi absensi</p><h2 id="dialogTitle" class="mt-1 text-xl font-bold text-slate-900"></h2></div><button type="button" id="closeAttendanceDialog" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Tutup</button></div>
            <div class="mt-5 overflow-hidden rounded-2xl bg-slate-950">
                <video id="cameraPreview" autoplay playsinline muted class="aspect-[3/4] w-full object-cover"></video>
                <img id="selfiePreview" alt="Pratinjau selfie absensi" class="hidden aspect-[3/4] w-full object-cover">
                <canvas id="selfieCanvas" class="hidden"></canvas>
            </div>
            <p id="captureStatus" class="mt-3 text-center text-sm text-slate-600">Posisikan wajah dengan jelas di dalam kamera.</p>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <button type="button" id="captureSelfie" class="rounded-xl bg-sky-700 px-4 py-3 text-sm font-bold text-white">Ambil Foto</button>
                <button type="button" id="retakeSelfie" disabled class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 disabled:opacity-40">Ambil Ulang</button>
            </div>
            <form id="attendanceForm" method="POST" class="mt-3">
                @csrf
                <input type="hidden" name="latitude" id="attendanceLatitude">
                <input type="hidden" name="longitude" id="attendanceLongitude">
                <input type="hidden" name="accuracy" id="attendanceAccuracy">
                <input type="hidden" name="selfie" id="attendanceSelfie">
                <button type="submit" id="confirmAttendance" disabled class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-300">Konfirmasi dan Kirim</button>
            </form>
        </div>
    </dialog>

    <script>
        (() => {
            if (!window.isSecureContext) {
                document.getElementById('attendanceHttpsWarning').classList.remove('hidden');
            }

            const dialog = document.getElementById('attendanceDialog');
            const error = document.getElementById('attendanceError');
            const video = document.getElementById('cameraPreview');
            const preview = document.getElementById('selfiePreview');
            const canvas = document.getElementById('selfieCanvas');
            const form = document.getElementById('attendanceForm');
            const captureButton = document.getElementById('captureSelfie');
            const retakeButton = document.getElementById('retakeSelfie');
            const confirmButton = document.getElementById('confirmAttendance');
            const captureStatus = document.getElementById('captureStatus');
            let stream = null;

            const showError = (message) => {
                error.textContent = message;
                error.classList.remove('hidden');
            };
            const stopCamera = () => {
                stream?.getTracks().forEach((track) => track.stop());
                stream = null;
                video.srcObject = null;
            };
            const startCamera = async () => {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                    video.srcObject = stream;
                    video.classList.remove('hidden');
                    preview.classList.add('hidden');
                    captureButton.disabled = false;
                    retakeButton.disabled = true;
                    confirmButton.disabled = true;
                    document.getElementById('attendanceSelfie').value = '';
                    captureStatus.textContent = 'Posisikan wajah dengan jelas di dalam kamera.';
                } catch (cameraError) {
                    stopCamera();
                    dialog.close();
                    showError('Izin kamera diperlukan untuk melakukan absensi.');
                }
            };
            const getLocation = () => new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
            });

            document.querySelectorAll('[data-attendance-action]').forEach((button) => {
                button.addEventListener('click', async () => {
                    error.classList.add('hidden');
                    if (!window.isSecureContext) {
                        showError('Absensi GPS dan kamera memerlukan koneksi HTTPS atau localhost.');
                        return;
                    }
                    if (!navigator.geolocation) {
                        showError('Geolokasi tidak didukung oleh browser ini.');
                        return;
                    }
                    if (!navigator.mediaDevices?.getUserMedia) {
                        showError('Kamera tidak didukung oleh browser ini.');
                        return;
                    }

                    button.disabled = true;
                    try {
                        const position = await getLocation();
                        document.getElementById('attendanceLatitude').value = position.coords.latitude;
                        document.getElementById('attendanceLongitude').value = position.coords.longitude;
                        document.getElementById('attendanceAccuracy').value = position.coords.accuracy;
                        form.action = button.dataset.attendanceAction;
                        document.getElementById('dialogTitle').textContent = button.dataset.attendanceLabel;
                        dialog.showModal();
                        await startCamera();
                    } catch (locationError) {
                        showError('Izin lokasi diperlukan untuk melakukan absensi.');
                    } finally {
                        button.disabled = false;
                    }
                });
            });

            captureButton.addEventListener('click', () => {
                if (!video.videoWidth || !video.videoHeight) {
                    showError('Kamera belum siap. Silakan coba lagi.');
                    return;
                }
                const targetWidth = Math.min(720, video.videoWidth);
                canvas.width = targetWidth;
                canvas.height = Math.round(video.videoHeight * (targetWidth / video.videoWidth));
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                const selfie = canvas.toDataURL('image/jpeg', 0.85);
                document.getElementById('attendanceSelfie').value = selfie;
                preview.src = selfie;
                preview.classList.remove('hidden');
                video.classList.add('hidden');
                stopCamera();
                captureButton.disabled = true;
                retakeButton.disabled = false;
                confirmButton.disabled = false;
                captureStatus.textContent = 'Periksa selfie sebelum mengirim absensi.';
            });

            retakeButton.addEventListener('click', startCamera);
            document.getElementById('closeAttendanceDialog').addEventListener('click', () => {
                stopCamera();
                dialog.close();
            });
            dialog.addEventListener('close', stopCamera);
            form.addEventListener('submit', () => {
                confirmButton.disabled = true;
                confirmButton.textContent = 'Mengirim...';
            });
        })();
    </script>
</x-student-layout>
