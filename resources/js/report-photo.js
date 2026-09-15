const photoPanel = document.querySelector('[data-report-photo]');
if (photoPanel) {
    const input = photoPanel.querySelector('input[type=file]');
    const remove = photoPanel.querySelector('input[name=remove_photo]');
    const preview = photoPanel.querySelector('[data-photo-preview]');
    const status = photoPanel.querySelector('[data-photo-status]');
    const cancel = photoPanel.querySelector('[data-photo-cancel]');
    let objectUrl;
    const clear = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        input.value = '';
        preview.removeAttribute('src');
        preview.hidden = true;
        cancel.hidden = true;
        status.textContent = '';
    };
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            clear();
            status.textContent = 'Pilih gambar JPG, PNG, atau WebP maksimal 5 MB.';
            return;
        }
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.hidden = false;
        cancel.hidden = false;
        if (remove) remove.checked = false;
        status.textContent = 'Foto dipilih. Simpan laporan untuk menerapkan perubahan.';
    });
    cancel.addEventListener('click', clear);
    remove?.addEventListener('change', () => { if (remove.checked) clear(); });
}
