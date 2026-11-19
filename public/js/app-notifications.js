(() => {
    let toast, timer;
    function notify(message, title = 'Periksa kembali') {
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'app-toast';
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.innerHTML = '<span class="app-toast-mark" aria-hidden="true">!</span><span><b class="app-toast-title"></b><span class="app-toast-message"></span></span><button type="button" aria-label="Tutup notifikasi">×</button>';
            toast.querySelector('button').addEventListener('click', () => { toast.hidden = true; });
        }
        const activeDialog = document.querySelector('dialog:modal');
        const host = activeDialog || document.body;
        if (toast.parentElement !== host) host.append(toast);
        toast.querySelector('.app-toast-title').textContent = title;
        toast.querySelector('.app-toast-message').textContent = String(message || 'Terjadi kendala. Silakan coba lagi.');
        toast.hidden = false;
        clearTimeout(timer);
        timer = setTimeout(() => { if (toast) toast.hidden = true; }, 5200);
    }

    window.appNotify = notify;
    window.alert = message => notify(message, 'Informasi');

    let invalidNoticeOpen = false;
    document.addEventListener('invalid', event => {
        const field = event.target;
        if (!(field instanceof HTMLElement) || !('validity' in field)) return;
        event.preventDefault();
        if (invalidNoticeOpen) return;
        invalidNoticeOpen = true;
        let message = 'Periksa kembali data yang dimasukkan.';
        if (field.name === 'uang_diterima' && field.validity.rangeUnderflow) {
            const kurang = Number(field.dataset.total || 0) - Number(field.value || 0);
            message = `Uang tunai kurang ${new Intl.NumberFormat('id-ID', {style:'currency',currency:'IDR',maximumFractionDigits:0}).format(kurang)} dari total tagihan.`;
        } else if (field.validity.valueMissing) {
            const known = {nama_pelanggan:'Nama pemesan wajib diisi.', nama_pemesan:'Nama lengkap wajib diisi.', whatsapp:'Nomor WhatsApp wajib diisi.', username:'Username wajib diisi.', password:'Password wajib diisi.', uang_diterima:'Masukkan jumlah uang yang diterima.'};
            const label = field.labels?.[0]?.innerText?.replace(/\*/g, '').trim().replace(/\s+/g, ' ');
            message = known[field.name] || (label ? `${label} wajib diisi.` : 'Lengkapi kolom yang wajib diisi.');
        } else if (field.validity.typeMismatch || field.validity.patternMismatch) {
            message = 'Format data belum benar. Periksa kembali isianmu.';
        } else if (field.validity.rangeUnderflow || field.validity.rangeOverflow || field.validity.stepMismatch) {
            message = 'Nilai yang dimasukkan di luar batas yang diperbolehkan.';
        }
        notify(message);
        window.setTimeout(() => { invalidNoticeOpen = false; }, 600);
    }, true);
})();
