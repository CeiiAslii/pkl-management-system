<x-student-layout title="Profil">
    <section class="mx-auto max-w-4xl">
        <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" class="grid gap-4">
            @csrf
            @method('PATCH')

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="relative shrink-0 self-start">
                        <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-sky-100 text-2xl font-bold text-sky-700 ring-4 ring-sky-50">
                            <img id="profilePhotoPreview" src="{{ $studentProfile?->profile_photo_path ? route('student.profile.photo') : '' }}" alt="Foto profil {{ auth()->user()->name }}" @class(['h-full w-full object-cover', 'hidden' => ! $studentProfile?->profile_photo_path])>
                            <span id="profilePhotoInitial" @class(['hidden' => $studentProfile?->profile_photo_path])>{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        </div>
                        <button type="button" id="chooseProfilePhoto" class="absolute -bottom-1 -right-1 grid h-10 w-10 place-items-center rounded-full border-2 border-white bg-sky-700 text-lg font-bold text-white shadow-md" aria-label="Ubah foto profil">+</button>
                        <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-sky-700">Profil saya</p>
                        <h1 class="mt-1 text-xl font-bold text-slate-900 sm:text-2xl">{{ auth()->user()->name }}</h1>
                        <button type="button" id="chooseProfilePhotoText" class="mt-1 min-h-11 text-sm font-semibold text-sky-700">Ubah foto</button>
                        <p id="profilePhotoStatus" class="text-xs text-slate-500">JPG, PNG, atau WEBP. Foto akan dipotong persegi sebelum disimpan.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="font-bold text-slate-900">DATA SISWA</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-form.input label="Nama lengkap" name="name" :value="auth()->user()->name" required maxlength="255" autocomplete="name" />
                    <x-form.input label="Email" name="email" type="email" :value="auth()->user()->email" required maxlength="255" autocomplete="email" />
                    <x-form.input label="Nomor HP" name="phone" type="tel" :value="$studentProfile?->phone" maxlength="30" autocomplete="tel" placeholder="Contoh: 080000000000" />
                    <div><p class="text-sm text-slate-500">Jurusan</p><p class="mt-2 font-semibold text-slate-900">{{ auth()->user()->major?->name ?? 'Belum ditentukan' }}</p></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div>
                    <h2 class="font-bold text-slate-900">DATA PKL</h2>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><x-form.input label="Nama tempat PKL" name="pkl_place_name" :value="$studentProfile?->pkl_place_name" maxlength="150" /></div>
                    <div id="pklLocation" class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2" data-saved-latitude="{{ $studentProfile?->pkl_latitude }}" data-saved-longitude="{{ $studentProfile?->pkl_longitude }}" data-saved-accuracy="{{ $studentProfile?->pkl_location_accuracy }}">
                        <h3 class="text-sm font-semibold text-slate-900">Lokasi tempat PKL</h3>
                        <p class="mt-1 text-sm leading-5 text-slate-500">Datang ke lokasi PKL lalu gunakan GPS perangkat untuk menyimpan titik lokasi.</p>
                        <input type="hidden" id="pkl_latitude" name="pkl_latitude" value="{{ old('pkl_latitude', $studentProfile?->pkl_latitude) }}">
                        <input type="hidden" id="pkl_longitude" name="pkl_longitude" value="{{ old('pkl_longitude', $studentProfile?->pkl_longitude) }}">
                        <input type="hidden" id="pkl_location_accuracy" name="pkl_location_accuracy" value="{{ old('pkl_location_accuracy', $studentProfile?->pkl_location_accuracy) }}">
                        <div id="pklLocationPreview" class="mt-3 text-sm" @if (! $studentProfile?->pklMapUrl()) hidden @endif>
                            <p id="pklLocationStatus" class="font-semibold text-sky-800" role="status" aria-live="polite">Lokasi tersimpan</p>
                            <dl class="mt-2 grid gap-1 text-slate-600 sm:grid-cols-3">
                                <div>Latitude: <span id="pklLatitudePreview">{{ $studentProfile?->pkl_latitude }}</span></div>
                                <div>Longitude: <span id="pklLongitudePreview">{{ $studentProfile?->pkl_longitude }}</span></div>
                                <div>Akurasi: ±<span id="pklAccuracyPreview">{{ $studentProfile?->pkl_location_accuracy }}</span> m</div>
                            </dl>
                        </div>
                        <p id="pklLocationError" class="mt-3 text-sm font-medium text-rose-700" role="alert" hidden></p>
                        @foreach (['pkl_latitude', 'pkl_longitude', 'pkl_location_accuracy'] as $field)
                            @error($field)<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        @endforeach
                        <div class="mt-3 flex flex-wrap gap-3">
                            <button type="button" id="capturePklLocation" class="min-h-11 rounded-xl border border-sky-200 bg-white px-4 py-2 text-sm font-bold text-sky-700 disabled:cursor-wait disabled:opacity-60">{{ $studentProfile?->pklMapUrl() ? 'Perbarui Lokasi' : 'Ambil lokasi saya' }}</button>
                            <a id="pklLocationMap" @if ($studentProfile?->pklMapUrl()) href="{{ $studentProfile->pklMapUrl() }}" @else hidden @endif target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center text-sm font-semibold text-sky-700 hover:underline">Buka di Maps</a>
                        </div>
                        <noscript><p class="mt-2 text-sm text-slate-600">Aktifkan JavaScript untuk mengambil lokasi GPS.</p></noscript>
                    </div>
                    <x-form.input label="PIC / pembimbing lapangan (opsional)" name="pkl_contact_name" :value="$studentProfile?->pkl_contact_name" maxlength="120" />
                    <x-form.input label="Nomor kontak PIC (opsional)" name="pkl_phone" type="tel" :value="$studentProfile?->pkl_contact_phone" maxlength="30" placeholder="Contoh: 080000000000" />
                </div>
            </section>

            <button type="submit" class="min-h-12 w-full rounded-xl bg-sky-700 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-300 sm:w-auto sm:justify-self-start">Simpan perubahan</button>
        </form>
    </section>

    <dialog id="profileCropDialog" class="m-auto w-[calc(100%-1.5rem)] max-w-md rounded-3xl border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-950/60">
        <div class="p-4 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-sm font-semibold text-sky-700">Foto profil</p><h2 class="mt-1 text-xl font-bold text-slate-900">Atur posisi foto</h2></div>
                <button type="button" id="closeProfileCrop" class="min-h-11 rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-700">Batal</button>
            </div>
            <p class="mt-2 text-sm text-slate-500">Geser foto untuk mengatur posisi, lalu gunakan penggeser untuk memperbesar.</p>
            <div class="mx-auto mt-4 aspect-square w-full max-w-80 overflow-hidden rounded-2xl bg-slate-100">
                <canvas id="profileCropCanvas" width="720" height="720" class="h-full w-full touch-none cursor-move"></canvas>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <span class="text-sm font-semibold text-slate-600">Zoom</span>
                <input id="profileCropZoom" type="range" min="1" max="3" value="1" step="0.01" class="h-11 min-w-0 flex-1 accent-sky-700">
                <img id="profileCropPreview" alt="Pratinjau foto profil berbentuk lingkaran" class="h-14 w-14 shrink-0 rounded-full border-2 border-white bg-slate-100 object-cover shadow">
            </div>
            <p id="profileCropError" class="mt-3 hidden rounded-xl bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700" role="alert"></p>
            <button type="button" id="useProfilePhoto" class="mt-4 min-h-12 w-full rounded-xl bg-sky-700 px-4 py-3 text-sm font-bold text-white">Gunakan foto</button>
        </div>
    </dialog>

    <script>
        (() => {
            const input = document.getElementById('profile_photo');
            const dialog = document.getElementById('profileCropDialog');
            const canvas = document.getElementById('profileCropCanvas');
            const context = canvas.getContext('2d');
            const zoomInput = document.getElementById('profileCropZoom');
            const cropPreview = document.getElementById('profileCropPreview');
            const profilePreview = document.getElementById('profilePhotoPreview');
            const profileInitial = document.getElementById('profilePhotoInitial');
            const status = document.getElementById('profilePhotoStatus');
            const cropError = document.getElementById('profileCropError');
            const image = new Image();
            let baseScale = 1;
            let zoom = 1;
            let offsetX = 0;
            let offsetY = 0;
            let dragging = false;
            let pointerX = 0;
            let pointerY = 0;

            const clampOffsets = () => {
                const width = image.naturalWidth * baseScale * zoom;
                const height = image.naturalHeight * baseScale * zoom;
                offsetX = Math.min(0, Math.max(canvas.width - width, offsetX));
                offsetY = Math.min(0, Math.max(canvas.height - height, offsetY));
            };
            const draw = (updatePreview = false) => {
                clampOffsets();
                context.fillStyle = '#f1f5f9';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, offsetX, offsetY, image.naturalWidth * baseScale * zoom, image.naturalHeight * baseScale * zoom);
                if (updatePreview) {
                    cropPreview.src = canvas.toDataURL('image/jpeg', 0.88);
                }
            };
            const showCropError = (message) => {
                cropError.textContent = message;
                cropError.classList.remove('hidden');
            };

            document.querySelectorAll('#chooseProfilePhoto, #chooseProfilePhotoText').forEach((button) => {
                button.addEventListener('click', () => input.click());
            });
            input.addEventListener('change', () => {
                const file = input.files?.[0];
                cropError.classList.add('hidden');
                if (!file) {
                    return;
                }
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                    input.value = '';
                    status.textContent = 'Pilih foto berformat JPG, PNG, atau WEBP.';
                    status.classList.add('text-rose-700');
                    return;
                }
                image.onload = () => {
                    URL.revokeObjectURL(image.src);
                    zoom = 1;
                    zoomInput.value = '1';
                    baseScale = Math.max(canvas.width / image.naturalWidth, canvas.height / image.naturalHeight);
                    offsetX = (canvas.width - image.naturalWidth * baseScale) / 2;
                    offsetY = (canvas.height - image.naturalHeight * baseScale) / 2;
                    draw(true);
                    dialog.showModal();
                };
                image.onerror = () => {
                    input.value = '';
                    status.textContent = 'Foto tidak dapat dibaca. Pilih foto lain.';
                    status.classList.add('text-rose-700');
                };
                image.src = URL.createObjectURL(file);
            });
            zoomInput.addEventListener('input', () => {
                const previousZoom = zoom;
                zoom = Number(zoomInput.value);
                offsetX = canvas.width / 2 - ((canvas.width / 2 - offsetX) / previousZoom) * zoom;
                offsetY = canvas.height / 2 - ((canvas.height / 2 - offsetY) / previousZoom) * zoom;
                draw(true);
            });
            canvas.addEventListener('pointerdown', (event) => {
                dragging = true;
                pointerX = event.clientX;
                pointerY = event.clientY;
                canvas.setPointerCapture(event.pointerId);
            });
            canvas.addEventListener('pointermove', (event) => {
                if (!dragging) {
                    return;
                }
                const ratio = canvas.width / canvas.getBoundingClientRect().width;
                offsetX += (event.clientX - pointerX) * ratio;
                offsetY += (event.clientY - pointerY) * ratio;
                pointerX = event.clientX;
                pointerY = event.clientY;
                draw();
            });
            const stopDragging = () => {
                if (dragging) {
                    dragging = false;
                    draw(true);
                }
            };
            canvas.addEventListener('pointerup', stopDragging);
            canvas.addEventListener('pointercancel', stopDragging);
            document.getElementById('closeProfileCrop').addEventListener('click', () => {
                input.value = '';
                dialog.close();
            });
            document.getElementById('useProfilePhoto').addEventListener('click', () => {
                canvas.toBlob((blob) => {
                    if (!blob) {
                        showCropError('Foto gagal diproses. Silakan pilih ulang foto.');
                        return;
                    }
                    try {
                        const transfer = new DataTransfer();
                        transfer.items.add(new File([blob], 'foto-profil.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
                        input.files = transfer.files;
                    } catch (error) {
                        showCropError('Browser tidak dapat menyiapkan foto. Gunakan Safari versi terbaru.');
                        return;
                    }
                    const previewUrl = URL.createObjectURL(blob);
                    profilePreview.src = previewUrl;
                    profilePreview.classList.remove('hidden');
                    profileInitial.classList.add('hidden');
                    status.textContent = 'Foto siap disimpan bersama perubahan profil.';
                    status.classList.remove('text-rose-700');
                    status.classList.add('text-emerald-700');
                    dialog.close();
                }, 'image/jpeg', 0.88);
            });
        })();
    </script>
</x-student-layout>
