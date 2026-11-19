<!doctype html>
<html lang="id"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservasi Meja · Kuligo Resto</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/customer.css') }}?v=reservasi-20261119">
    <link rel="stylesheet" href="{{ asset('css/reservasi.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/reservation-areas.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/app-notifications.css') }}?v=1">
</head><body class="customer-body reservation-body">
<header class="customer-header"><a class="customer-brand" href="{{ route('reservasi.index') }}"><span class="brand-mark"><img src="{{ asset('images/kuligo-food-mark.png') }}" alt=""></span><span>kuligo<small>RESTO</small></span></a><button class="reservation-back" style="border:0;background:transparent;color:#536176;font:700 13px 'DM Sans',sans-serif;cursor:pointer" type="button" onclick="keluarDariWebsite()">Keluar dari website ↗</button></header>
<main class="reservation-main">
    <section class="reservation-hero"><div><span class="eyebrow">RESTO &amp; CAFE · KULIGO</span><h1>Rencanakan momen<br><em>istimewamu.</em></h1><p>Pilih meja dan waktu yang pas. Kami akan menyiapkannya untuk kedatanganmu.</p></div><div class="reservation-art" aria-hidden="true">🍽️<span>✦</span></div></section>
    <div class="reservation-layout">
        <section class="reservation-tables"><div class="reservation-section-heading"><div><span class="eyebrow">PILIH TEMPAT</span><h2>Meja tersedia</h2></div><span class="table-count">{{ $meja->count() }} meja</span></div>
            <p class="reservation-hint">Pilih area Indoor atau Outdoor, lalu sesuaikan jumlah tamu dengan kapasitas meja.</p>
            <nav class="reservation-area-tabs" aria-label="Filter area meja"><button type="button" class="active" data-area="semua">Semua area <b>{{ $meja->count() }}</b></button><button type="button" data-area="indoor">Indoor <b>{{ $meja->where('tipe_meja', 'indoor')->count() }}</b></button><button type="button" data-area="outdoor">Outdoor <b>{{ $meja->where('tipe_meja', 'outdoor')->count() }}</b></button></nav>
            <p id="area-empty" class="empty-reservation" hidden>Belum ada meja pada area ini.</p>
            <div class="reservation-table-grid">
                @forelse($meja as $item)
                    <label class="reservation-table-card" data-area="{{ $item->tipe_meja }}" data-capacity="{{ $item->kapasitas }}" for="meja-{{ $item->id_meja }}"><input type="radio" id="meja-{{ $item->id_meja }}" name="id_meja" form="reservation-form" value="{{ $item->id_meja }}" @checked(old('id_meja', $loop->first ? $item->id_meja : null) == $item->id_meja) required>
                        <span class="table-card-top"><span class="table-icon">⌂</span><span class="table-open"><i></i>Tersedia</span></span>
                        <b>{{ $item->nomor_meja }}</b><span class="table-area-badge {{ $item->tipe_meja }}">{{ ucfirst($item->tipe_meja) }}</span><small class="table-capacity">♙ Kapasitas {{ $item->kapasitas }} orang</small><span class="table-select">Pilih meja <b>→</b></span>
                    </label>
                @empty
                    <div class="empty-reservation"><b>Meja belum tersedia</b><span>Silakan hubungi restoran untuk informasi lebih lanjut.</span></div>
                @endforelse
            </div>
            <div class="reservation-legend"><span><i></i>Meja siap dipesan</span><span><i></i>Booking akan dikonfirmasi admin</span></div>
        </section>
        <section class="reservation-form-card"><div class="form-card-heading"><span class="form-icon">✳</span><div><h2>Detail reservasi</h2><p>Isi data kedatanganmu</p></div></div>
            @if($errors->any())<div class="reservation-error">{{ $errors->first() }}</div>@endif
            <form id="reservation-form" method="POST" action="{{ route('reservasi.store') }}">@csrf
                <label class="reservation-field">Nama lengkap <i>*</i><input name="nama_pemesan" value="{{ old('nama_pemesan') }}" required maxlength="100" placeholder="Nama pemesan"></label>
                <label class="reservation-field">Nomor WhatsApp <i>*</i><input name="whatsapp" value="{{ old('whatsapp') }}" required maxlength="30" inputmode="tel" placeholder="Contoh: 081234567890"></label>
                <label class="reservation-field">Acara / keperluan <small>Opsional</small><input name="keperluan" value="{{ old('keperluan') }}" maxlength="160" placeholder="Makan malam, ulang tahun, meeting"></label>
                <div class="reservation-field-row">
                    <label class="reservation-field">Tanggal <i>*</i><span class="custom-date-picker" data-min-date="{{ now()->toDateString() }}"><input type="hidden" id="tanggal-reservasi" name="tanggal_reservasi" value="{{ old('tanggal_reservasi', now()->toDateString()) }}"><button class="date-picker-trigger" id="date-picker-trigger" type="button" aria-expanded="false" aria-controls="date-picker-panel"><span aria-hidden="true">▦</span><b id="date-picker-label">Pilih tanggal</b><span aria-hidden="true">⌄</span></button><div class="date-picker-panel" id="date-picker-panel" hidden><div class="calendar-heading"><button type="button" id="calendar-prev" aria-label="Bulan sebelumnya">‹</button><b id="calendar-month"></b><button type="button" id="calendar-next" aria-label="Bulan berikutnya">›</button></div><div class="calendar-weekdays" aria-hidden="true"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div><div class="calendar-days" id="calendar-days" role="group" aria-label="Pilih tanggal"></div></div></span></label>
                    <label class="reservation-field">Waktu <i>*</i><input type="hidden" id="waktu-reservasi" name="waktu_reservasi" value="{{ old('waktu_reservasi', '19:00') }}"><div class="reservation-time-options" id="reservation-time-options" role="group" aria-label="Pilih waktu reservasi">@foreach(['11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00'] as $jam)<button type="button" data-time="{{ $jam }}" aria-pressed="{{ old('waktu_reservasi', '19:00') === $jam ? 'true' : 'false' }}" class="{{ old('waktu_reservasi', '19:00') === $jam ? 'selected' : '' }}">{{ $jam }}</button>@endforeach</div></label>
                </div>
                <label class="reservation-field">Jumlah orang <i>*</i><div class="guest-stepper" role="group" aria-label="Pilih jumlah orang"><button id="guest-minus" type="button" aria-label="Kurangi jumlah orang">−</button><output id="guest-count" aria-live="polite">{{ old('jumlah_orang', 2) }} orang</output><button id="guest-plus" type="button" aria-label="Tambah jumlah orang">+</button></div><input type="hidden" name="jumlah_orang" id="jumlah-orang" value="{{ old('jumlah_orang', 2) }}"><small id="capacity-hint" class="capacity-hint"></small></label>
                <div class="reservation-note"><span>◷</span><p>Reservasi ditahan selama <b>15 menit</b> dari waktu kedatangan. Admin akan menghubungimu untuk konfirmasi.</p></div>
                <button class="reservation-submit" type="submit" {{ $meja->isEmpty() ? 'disabled' : '' }}>Konfirmasi reservasi <span>→</span></button>
                <p class="form-terms">Dengan melanjutkan, kamu setuju untuk dihubungi terkait reservasi ini.</p>
            </form>
        </section>
    </div>
    <footer class="reservation-footer">KULIGO RESTO <span>·</span> Dibuat untuk momen yang berkesan</footer>
</main><script>
(() => {
    const cards = [...document.querySelectorAll('.reservation-table-card')];
    const tabs = [...document.querySelectorAll('.reservation-area-tabs button')];
    const guests = document.querySelector('#jumlah-orang');
    const guestCount = document.querySelector('#guest-count');
    const guestMinus = document.querySelector('#guest-minus');
    const guestPlus = document.querySelector('#guest-plus');
    const hint = document.querySelector('#capacity-hint');
    const empty = document.querySelector('#area-empty');
    const dateInput = document.querySelector('#tanggal-reservasi');
    const dateTrigger = document.querySelector('#date-picker-trigger');
    const dateLabel = document.querySelector('#date-picker-label');
    const datePanel = document.querySelector('#date-picker-panel');
    const calendarMonth = document.querySelector('#calendar-month');
    const calendarDays = document.querySelector('#calendar-days');
    const minDate = new Date(`${document.querySelector('.custom-date-picker').dataset.minDate}T00:00:00`);
    let calendarDate = new Date(`${dateInput.value}T00:00:00`);
    if (Number.isNaN(calendarDate.getTime()) || calendarDate < minDate) {
        calendarDate = new Date(minDate);
        dateInput.value = localIso(minDate);
    }
    calendarDate.setDate(1);
    function localIso(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }
    function updateDateLabel() {
        const selectedDate = new Date(`${dateInput.value}T00:00:00`);
        dateLabel.textContent = new Intl.DateTimeFormat('id-ID', {day:'2-digit', month:'short', year:'numeric'}).format(selectedDate);
    }
    function renderCalendar() {
        calendarMonth.textContent = new Intl.DateTimeFormat('id-ID', {month:'long', year:'numeric'}).format(calendarDate);
        calendarDays.replaceChildren();
        const firstWeekday = (new Date(calendarDate.getFullYear(), calendarDate.getMonth(), 1).getDay() + 6) % 7;
        const daysInMonth = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + 1, 0).getDate();
        for (let blank = 0; blank < firstWeekday; blank++) {
            const spacer = document.createElement('span');
            spacer.className = 'calendar-spacer';
            calendarDays.append(spacer);
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(calendarDate.getFullYear(), calendarDate.getMonth(), day);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'calendar-day';
            button.textContent = String(day);
            button.disabled = date < minDate;
            if (localIso(date) === dateInput.value) button.classList.add('selected');
            if (localIso(date) === localIso(minDate)) button.classList.add('is-today');
            button.addEventListener('click', () => {
                dateInput.value = localIso(date);
                updateDateLabel();
                datePanel.hidden = true;
                dateTrigger.setAttribute('aria-expanded', 'false');
            });
            calendarDays.append(button);
        }
        document.querySelector('#calendar-prev').disabled = new Date(calendarDate.getFullYear(), calendarDate.getMonth(), 0) < minDate;
    }
    updateDateLabel();
    renderCalendar();
    dateTrigger.addEventListener('click', () => {
        datePanel.hidden = !datePanel.hidden;
        dateTrigger.setAttribute('aria-expanded', String(!datePanel.hidden));
    });
    document.querySelector('#calendar-prev').addEventListener('click', () => { calendarDate.setMonth(calendarDate.getMonth() - 1); renderCalendar(); });
    document.querySelector('#calendar-next').addEventListener('click', () => { calendarDate.setMonth(calendarDate.getMonth() + 1); renderCalendar(); });
    const timeInput = document.querySelector('#waktu-reservasi');
    const timeButtons = [...document.querySelectorAll('#reservation-time-options button')];
    if (!timeButtons.some(button => button.dataset.time === timeInput.value)) timeInput.value = '19:00';
    timeButtons.forEach(button => {
        const selected = button.dataset.time === timeInput.value;
        button.classList.toggle('selected', selected);
        button.setAttribute('aria-pressed', String(selected));
        button.addEventListener('click', () => {
        timeInput.value = button.dataset.time;
        timeButtons.forEach(item => {
            const selected = item === button;
            item.classList.toggle('selected', selected);
            item.setAttribute('aria-pressed', String(selected));
        });
    });
    });
    let maxGuests = 30;
    function renderGuestCount() {
        const count = Math.max(1, Math.min(maxGuests, Number(guests.value || 1)));
        guests.value = String(count);
        guestCount.textContent = `${count} orang`;
        guestMinus.disabled = count <= 1;
        guestPlus.disabled = count >= maxGuests;
    }
    function syncCapacity() {
        const selected = document.querySelector('.reservation-table-card input:checked');
        const capacity = Number(selected?.closest('.reservation-table-card')?.dataset.capacity || 0);
        maxGuests = capacity ? Math.min(capacity, 30) : 30;
        renderGuestCount();
        hint.textContent = capacity ? `Maksimal ${capacity} orang untuk meja ini.` : 'Pilih meja untuk melihat kapasitas.';
    }
    guestMinus.addEventListener('click', () => { guests.value = String(Number(guests.value) - 1); renderGuestCount(); });
    guestPlus.addEventListener('click', () => { guests.value = String(Number(guests.value) + 1); renderGuestCount(); });
    tabs.forEach(tab => tab.addEventListener('click', () => {
        tabs.forEach(item => item.classList.toggle('active', item === tab));
        cards.forEach(card => { card.hidden = tab.dataset.area !== 'semua' && card.dataset.area !== tab.dataset.area; });
        empty.hidden = cards.some(card => !card.hidden);
        const selectedCard = document.querySelector('.reservation-table-card input:checked')?.closest('.reservation-table-card');
        if (selectedCard?.hidden) {
            const firstVisible = cards.find(card => !card.hidden);
            if (firstVisible) firstVisible.querySelector('input').checked = true;
            else selectedCard.querySelector('input').checked = false;
        }
        syncCapacity();
    }));
    document.querySelectorAll('.reservation-table-card input').forEach(input => input.addEventListener('change', syncCapacity));
    syncCapacity();
})();
</script><script src="{{ asset('js/exit-reservation.js') }}" defer></script><script src="{{ asset('js/app-notifications.js') }}" defer></script></body></html>
