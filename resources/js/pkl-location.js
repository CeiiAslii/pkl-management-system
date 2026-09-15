const locationPanel = document.getElementById('pklLocation');

if (locationPanel) {
    const button = document.getElementById('capturePklLocation');
    const inputs = ['pkl_latitude', 'pkl_longitude', 'pkl_location_accuracy'].map((id) => document.getElementById(id));
    const preview = document.getElementById('pklLocationPreview');
    const status = document.getElementById('pklLocationStatus');
    const error = document.getElementById('pklLocationError');
    const map = document.getElementById('pklLocationMap');
    const saved = [locationPanel.dataset.savedLatitude, locationPanel.dataset.savedLongitude, locationPanel.dataset.savedAccuracy];
    const valid = (values) => values.every(Number.isFinite)
        && Math.abs(values[0]) <= 90 && Math.abs(values[1]) <= 180
        && values[2] >= 0 && values[2] <= 10000;

    const showError = (message) => {
        error.textContent = message;
        error.hidden = false;
    };
    const render = (newCapture = false) => {
        const values = inputs.map((input) => input.value === '' ? NaN : Number(input.value));
        const hasLocation = valid(values);
        preview.hidden = !hasLocation;
        map.hidden = !hasLocation;
        button.textContent = hasLocation ? 'Perbarui Lokasi' : 'Ambil lokasi saya';
        if (!hasLocation) {
            map.removeAttribute('href');
            return;
        }
        const isSaved = !newCapture && saved.every((value, index) => value !== '' && Number(value) === values[index]);
        status.textContent = isSaved ? 'Lokasi tersimpan' : 'Lokasi belum disimpan. Tekan Simpan perubahan.';
        document.getElementById('pklLatitudePreview').textContent = values[0].toFixed(7);
        document.getElementById('pklLongitudePreview').textContent = values[1].toFixed(7);
        document.getElementById('pklAccuracyPreview').textContent = values[2].toFixed(2);
        map.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${values[0]},${values[1]}`)}`;
    };
    render();

    button.addEventListener('click', () => {
        error.hidden = true;
        if (!window.isSecureContext) {
            showError('Pengambilan lokasi memerlukan HTTPS.');
            return;
        }
        if (!navigator.geolocation) {
            showError('Browser Anda belum mendukung pengambilan lokasi GPS.');
            return;
        }
        button.disabled = true;
        button.textContent = 'Mengambil lokasi...';
        locationPanel.setAttribute('aria-busy', 'true');
        const finish = () => {
            button.disabled = false;
            locationPanel.removeAttribute('aria-busy');
        };
        navigator.geolocation.getCurrentPosition((position) => {
            const values = [position.coords.latitude, position.coords.longitude, position.coords.accuracy];
            finish();
            if (!valid(values)) {
                render();
                showError('Lokasi GPS belum cukup akurat. Coba lagi di area terbuka.');
                return;
            }
            inputs.forEach((input, index) => { input.value = values[index].toFixed(index === 2 ? 2 : 7); });
            render(true);
        }, (failure) => {
            finish();
            render();
            showError({
                1: 'Izin lokasi diperlukan untuk menyimpan lokasi PKL.',
                2: 'Lokasi GPS tidak tersedia. Aktifkan GPS lalu coba lagi di area terbuka.',
                3: 'Pengambilan lokasi terlalu lama. Silakan coba lagi.',
            }[failure.code] ?? 'Lokasi belum dapat diambil. Silakan coba lagi.');
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
}
