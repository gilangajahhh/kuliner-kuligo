<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status Pesanan · Kuligo Resto</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/css/customer.css?v=status-payment-panel-20261117">
    <link rel="stylesheet" href="{{ asset('css/app-notifications.css') }}?v=1">
    <link rel="stylesheet" href="/css/payment-options.css?v=status-payment-panel-20261117">
    @if($pesanan->pembayaran?->kanal_pembayaran === 'midtrans' && $pesanan->pembayaran?->status_pembayaran === 'pending' && config('services.midtrans.client_key'))
        <script type="text/javascript" src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif
</head>

<body class="customer-body">
    <header class="customer-header"><a class="customer-brand"
            href="{{ route('menu.index', $pesanan->meja->kode_qr) }}"><span class="brand-mark"><img
                    src="/images/kuligo-food-mark.png" alt=""></span><span>kuligo<small>RESTO</small></span></a>
        <div class="table-indicator"><span class="live-dot"></span> Meja {{ $pesanan->meja->nomor_meja }}</div>
    </header>
    <main class="status-page"><a class="back-link" href="{{ route('menu.index', $pesanan->meja->kode_qr) }}">← Kembali
            ke menu</a>
        <section class="status-card"><span class="success-mark">✓</span><span class="eyebrow">PESANAN
                {{ strtoupper($pesanan->status_pesanan === 'batal' ? 'DIBATALKAN' : 'DITERIMA') }}</span>
            <h1>{{ $pesanan->status_pesanan === 'batal' ? 'Pesanan dibatalkan' : 'Terima kasih! Pesananmu kami terima.' }}
            </h1>
            <p>Meja {{ $pesanan->meja->nomor_meja }} · {{ $pesanan->nama_pelanggan ?: 'Pelanggan Kuligo' }} ·
                {{ $pesanan->waktu_pesan->format('d M Y, H:i') }}</p>
            <div class="order-number"><span>Nomor pesanan</span><b>{{ $pesanan->no_pesanan }}</b></div>
            <div class="status-timeline">
                <div class="timeline-step done"><i>✓</i><span><b>Pesanan diterima</b><small>Pesananmu sudah masuk ke
                            sistem kami</small></span></div>
                <div
                    class="timeline-step {{ in_array($pesanan->status_pesanan, ['diproses', 'siap_diantar', 'selesai']) ? 'done' : '' }}">
                    <i>{{ in_array($pesanan->status_pesanan, ['diproses', 'siap_diantar', 'selesai']) ? '✓' : '2' }}</i><span><b>{{ in_array($pesanan->status_pesanan, ['diproses', 'siap_diantar', 'selesai']) ? 'Sedang disiapkan' : (in_array($pesanan->pembayaran?->kanal_pembayaran, ['xendit', 'midtrans']) ? 'Menunggu pembayaran terverifikasi otomatis' : 'Menunggu konfirmasi pembayaran oleh kasir') }}</b><small>{{ $pesanan->pembayaran ? strtoupper(str_replace('_', ' ', $pesanan->pembayaran->metode_pembayaran)) . ' · ' . ucfirst($pesanan->pembayaran->status_pembayaran) : 'Pembayaran akan diperiksa oleh kasir' }}</small></span>
                </div>
                <div
                    class="timeline-step {{ in_array($pesanan->status_pesanan, ['siap_diantar', 'selesai']) ? 'done' : '' }}">
                    <i>{{ in_array($pesanan->status_pesanan, ['siap_diantar', 'selesai']) ? '✓' : '3' }}</i><span><b>Siap dinikmati</b><small>Kami antar langsung ke meja kamu</small></span></div>
            </div>
            @if($pesanan->status_pesanan !== 'batal' && $pesanan->pembayaran?->status_pembayaran === 'pending' && in_array($pesanan->pembayaran?->metode_pembayaran, ['qris', 'bank_transfer']))
                <div class="manual-payment-instructions status-payment-instructions">
                    @if($pesanan->pembayaran->metode_pembayaran === 'bank_transfer' && $pesanan->pembayaran->kanal_pembayaran !== 'midtrans')
                        @php $transferLabels = ['dana' => 'DANA', 'ovo' => 'OVO', 'gopay' => 'GoPay', 'bca' => 'BCA', 'bri' => 'BRI / BRImo', 'mandiri' => 'Mandiri', 'bjb' => 'BJB', 'seabank' => 'SeaBank']; @endphp
                        <b>Transfer melalui {{ $transferLabels[$pesanan->pembayaran->kanal_pembayaran] ?? $pesanan->pembayaran->kanal_pembayaran }}</b>
                        <p>Total transfer: Rp {{ number_format($pesanan->pembayaran->jumlah_bayar, 0, ',', '.') }}. Minta tujuan transfer kepada kasir.</p>
                    @elseif($pesanan->pembayaran->kanal_pembayaran === 'xendit' && $pesanan->pembayaran->qris_string && (! $pesanan->pembayaran->qris_expires_at || $pesanan->pembayaran->qris_expires_at->isFuture()))
                        <b>Scan QRIS untuk membayar</b>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=360x360&amp;format=png&amp;data={{ urlencode($pesanan->pembayaran->qris_string) }}" alt="QRIS untuk pesanan {{ $pesanan->no_pesanan }}">
                        <a href="https://api.qrserver.com/v1/create-qr-code/?size=360x360&amp;format=png&amp;data={{ urlencode($pesanan->pembayaran->qris_string) }}" target="_blank" rel="noopener">Buka atau unduh QR</a>
                        @if(str_starts_with((string) config('services.xendit.secret_key'), 'xnd_development_'))
                            <a class="qris-action qris-action-secondary" href="{{ route('qris.simulator') }}" target="_blank" rel="noopener">Buka simulator scan QRIS</a>
                            <small>Mode sandbox: buka simulator di perangkat lain, lalu scan QR pesanan ini.</small>
                        @endif
                        @if($pesanan->pembayaran->qris_expires_at)
                            <small>Berlaku sampai {{ $pesanan->pembayaran->qris_expires_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}.</small>
                        @endif
                    @elseif($pesanan->pembayaran->kanal_pembayaran === 'xendit' && $pesanan->pembayaran->qris_string)
                        <b>QRIS sudah kedaluwarsa</b>
                        <p>Minta kasir membuat QRIS baru sebelum melanjutkan pembayaran.</p>
                    @elseif($pesanan->pembayaran->kanal_pembayaran === 'midtrans' && $pesanan->pembayaran->snap_token)
                        <div class="midtrans-payment-panel">
                            <span class="midtrans-payment-badge"><i></i> Menunggu pembayaran</span>
                            <div class="midtrans-payment-copy">
                                <b>Lanjutkan pembayaran online</b>
                                <p>Pilih QRIS atau transfer bank di halaman aman Midtrans.</p>
                            </div>
                            <div class="midtrans-payment-amount">
                                <span>Total pembayaran</span>
                                <strong>Rp {{ number_format($pesanan->pembayaran->jumlah_bayar, 0, ',', '.') }}</strong>
                            </div>
                            <button class="qris-action qris-action-primary midtrans-payment-button" type="button" id="resume-midtrans-payment" data-token="{{ $pesanan->pembayaran->snap_token }}">
                                <span id="resume-midtrans-label" aria-live="polite">Bayar via Midtrans</span><span aria-hidden="true">→</span>
                            </button>
                            <small class="midtrans-payment-note">Status pembayaran diperbarui otomatis setelah Midtrans mengirim notifikasi.</small>
                        </div>
                    @else
                        <b>QRIS belum tersedia</b>
                        <p>Silakan hubungi kasir untuk bantuan.</p>
                    @endif
                    @if($pesanan->pembayaran->kanal_pembayaran !== 'midtrans')
                        <strong>Rp {{ number_format($pesanan->pembayaran->jumlah_bayar, 0, ',', '.') }}</strong>
                        <small>{{ $pesanan->pembayaran->kanal_pembayaran === 'xendit' ? 'Status pembayaran diperbarui otomatis setelah penyedia pembayaran mengirim notifikasi.' : 'Beri tahu kasir setelah transfer. Kasir dapat langsung mengonfirmasi pembayaran.' }}</small>
                    @endif
                </div>
            @endif
            <div class="order-lines">
                <h2>Ringkasan pesanan</h2>@foreach($pesanan->detail as $item)
                    <div><span>{{ $item->jumlah }}× {{ $item->menu->nama_menu }}@if($item->varian) <small>·
                    {{ $item->varian->nama_varian }}</small>@endif</span><b>Rp
                {{ number_format($item->subtotal, 0, ',', '.') }}</b></div>@endforeach<div class="order-total">
                    <span>Total</span><b>Rp {{ number_format($pesanan->total_harga, 0, ',', '.') }}</b></div>
            </div><a class="checkout-button" href="{{ route('menu.index', $pesanan->meja->kode_qr) }}">Pesan menu
                lainnya <span>→</span></a>
            @if($pesanan->pembayaran?->status_pembayaran === 'berhasil')
                <a class="back-link" href="{{ route('menu.struk', [$pesanan->meja->kode_qr, $pesanan->no_pesanan]) }}" target="_blank" rel="noopener">Lihat / cetak bukti pembayaran</a>
            @endif
        </section>
    </main>
    <footer class="customer-footer"><span>kuligo <b>RESTO</b></span><span>Terima kasih telah memilih kami</span>
    </footer>
    @if(in_array($pesanan->pembayaran?->metode_pembayaran, ['qris', 'bank_transfer']) && $pesanan->pembayaran?->status_pembayaran === 'pending')
        <script>
            const resumePaymentButton = document.querySelector('#resume-midtrans-payment');
            let snapCheckoutOpen = false;
            resumePaymentButton?.addEventListener('click', () => {
                const label = document.querySelector('#resume-midtrans-label');
                if (!window.snap) {
                    if (label) label.textContent = 'Pembayaran belum siap';
                    return;
                }

                resumePaymentButton.disabled = true;
                snapCheckoutOpen = true;
                if (label) label.textContent = 'Membuka pembayaran…';
                window.snap.pay(resumePaymentButton.dataset.token, {
                    onSuccess: () => { snapCheckoutOpen = false; window.location.reload(); },
                    onPending: () => { snapCheckoutOpen = false; window.location.reload(); },
                    onError: () => {
                        snapCheckoutOpen = false;
                        resumePaymentButton.disabled = false;
                        if (label) label.textContent = 'Coba bayar lagi';
                    },
                    onClose: () => {
                        snapCheckoutOpen = false;
                        resumePaymentButton.disabled = false;
                        if (label) label.textContent = 'Bayar via Midtrans';
                    },
                });
            });
            const refreshTimer = window.setInterval(() => {
                if (!snapCheckoutOpen) window.location.reload();
            }, 5000);
            window.setTimeout(() => window.clearInterval(refreshTimer), 60000);
        </script>
    @endif
<script src="{{ asset('js/app-notifications.js') }}" defer></script>
</body>

</html>
