@php
    $kanal = ['dana' => 'DANA', 'ovo' => 'OVO', 'gopay' => 'GoPay', 'bca' => 'BCA', 'bri' => 'BRI / BRImo', 'mandiri' => 'Mandiri', 'bjb' => 'BJB', 'seabank' => 'SeaBank'];
    $metode = $pesanan->pembayaran?->metode_pembayaran === 'bank_transfer'
        ? 'Transfer · ' . ($kanal[$pesanan->pembayaran->kanal_pembayaran] ?? $pesanan->pembayaran->kanal_pembayaran)
        : strtoupper(str_replace('_', ' ', $pesanan->pembayaran?->metode_pembayaran ?? ''));
    $waktuPesanWib = $pesanan->waktu_pesan?->copy()->timezone('Asia/Jakarta');
    $waktuBayarWib = $pesanan->pembayaran?->waktu_pembayaran?->copy()->timezone('Asia/Jakarta');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bukti Pembayaran {{ $pesanan->no_pesanan }} · Kuligo</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:20px;background:#eef1f6;color:#202a39;font:13px/1.45 Arial,sans-serif}.receipt{width:min(100%,380px);margin:0 auto;padding:24px;background:#fff;border:1px solid #e1e6ed;border-radius:10px}.no-print{display:flex;justify-content:center;gap:8px;margin:0 auto 14px}.no-print button,.no-print a{padding:9px 13px;border:0;border-radius:6px;background:#172033;color:#fff;text-decoration:none;font:600 12px Arial,sans-serif;cursor:pointer}.no-print a{background:#fff;color:#273143;border:1px solid #dce2eb}.head{text-align:center;padding-bottom:15px;border-bottom:1px dashed #aab2bf}.head strong{font-size:23px}.head h1{margin:14px 0 0;font-size:14px}.meta,.payment{display:grid;gap:7px;padding:13px 0;border-bottom:1px dashed #aab2bf}.meta div,.payment div,.total{display:flex;justify-content:space-between;gap:12px}.meta span,.payment span,.total span{color:#6e7888}.items{width:100%;margin:12px 0;border-collapse:collapse}.items th,.items td{padding:7px 0;border-bottom:1px dashed #e3e7ed;text-align:left}.items th:last-child,.items td:last-child{text-align:right}.total{padding:12px 0;font-size:16px;font-weight:800}.paid{margin-top:12px;padding:10px;border-radius:7px;background:#eaf8f2;color:#168665;text-align:center;font-weight:700}.foot{padding-top:13px;border-top:1px dashed #aab2bf;text-align:center;color:#667184;font-size:11px}
        @media print{@page{size:80mm auto;margin:4mm}body{padding:0;background:#fff}.receipt{width:72mm;margin:0;padding:0;border:0;border-radius:0}.no-print{display:none}}
    </style>
</head>
<body>
    <div class="no-print"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button><a href="{{ route('menu.status', [$pesanan->meja->kode_qr, $pesanan->no_pesanan]) }}">Kembali</a></div>
    <main class="receipt">
        <header class="head"><strong>kuligo</strong><h1>BUKTI PEMBAYARAN</h1></header>
        <section class="meta"><div><span>No. pesanan</span><b>{{ $pesanan->no_pesanan }}</b></div><div><span>Tanggal pemesanan</span><b>{{ $waktuPesanWib?->format('d/m/Y H:i') }} WIB</b></div><div><span>Meja</span><b>{{ $pesanan->meja->nomor_meja }}</b></div><div><span>Pelanggan</span><b>{{ $pesanan->nama_pelanggan ?: 'Pelanggan' }}</b></div></section>
        <table class="items"><thead><tr><th>Pesanan</th><th>Qty</th><th>Total</th></tr></thead><tbody>@foreach($pesanan->detail as $item)<tr><td>{{ $item->menu->nama_menu }}@if($item->varian) · {{ $item->varian->nama_varian }}@endif</td><td>{{ $item->jumlah }}×</td><td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td></tr>@endforeach</tbody></table>
        <div class="total"><span>Total dibayar</span><b>Rp {{ number_format($pesanan->pembayaran->jumlah_bayar, 0, ',', '.') }}</b></div>
        <section class="payment"><div><span>Metode</span><b>{{ $metode }}</b></div><div><span>Status</span><b>Terverifikasi</b></div><div><span>Waktu verifikasi</span><b>{{ $waktuBayarWib?->format('d/m/Y H:i') ?? '—' }} WIB</b></div></section>
        <div class="paid">Pembayaran berhasil diterima</div>
        <footer class="foot">Terima kasih sudah berkunjung ke Kuligo.</footer>
    </main>
</body>
</html>
