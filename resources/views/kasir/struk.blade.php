@php
    $metodePembayaran = match ($pesanan->pembayaran?->metode_pembayaran) {
        'qris' => 'QRIS',
        'e_wallet' => 'E-Wallet',
        'tunai' => 'Tunai',
        'kartu_debit' => 'Kartu debit (riwayat)',
        'kartu_kredit' => 'Kartu kredit',
        default => 'Belum dipilih',
    };
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $pesanan->no_pesanan }} · Kuligo</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:20px;background:#eef1f6;color:#202a39;font:13px/1.45 Arial,sans-serif}.receipt{width:min(100%,380px);margin:0 auto;padding:24px;background:#fff;border:1px solid #e1e6ed;border-radius:10px}.receipt-head{text-align:center;padding-bottom:16px;border-bottom:1px dashed #aab2bf}.brand{font-size:23px;font-weight:800;letter-spacing:-.8px}.brand small{display:block;margin-top:1px;color:#7d8797;font-size:9px;font-weight:600;letter-spacing:2px}.receipt-head h1{margin:18px 0 0;font-size:14px;letter-spacing:.6px}.receipt-meta{display:grid;gap:5px;padding:13px 0;border-bottom:1px dashed #aab2bf}.receipt-meta div,.receipt-total div,.receipt-payment div{display:flex;justify-content:space-between;gap:12px}.receipt-meta span,.receipt-payment span{color:#6e7888}.items{width:100%;margin:12px 0;border-collapse:collapse}.items th{padding:6px 0;border-bottom:1px solid #dfe4eb;text-align:left;font-size:10px;color:#6e7888}.items th:last-child,.items td:last-child{text-align:right}.items td{padding:8px 0;border-bottom:1px dashed #e3e7ed;vertical-align:top}.item-name{font-weight:700}.item-sub{display:block;margin-top:2px;color:#737e8f;font-size:10px}.receipt-total{display:grid;gap:6px;padding:10px 0 14px;border-bottom:1px dashed #aab2bf}.receipt-total .grand{padding-top:8px;font-size:16px;font-weight:800}.receipt-payment{display:grid;gap:5px;padding:13px 0}.receipt-foot{text-align:center;padding-top:12px;border-top:1px dashed #aab2bf;color:#667184;font-size:11px}.receipt-foot strong{display:block;margin-bottom:4px;color:#202a39}.no-print{display:flex;justify-content:center;gap:8px;margin:0 auto 14px}.no-print a,.no-print button{padding:9px 13px;border:0;border-radius:6px;background:#172033;color:#fff;text-decoration:none;font:600 12px Arial,sans-serif;cursor:pointer}.no-print a{background:#fff;color:#273143;border:1px solid #dce2eb}
        @media print{@page{size:80mm auto;margin:4mm}body{padding:0;background:#fff}.receipt{width:72mm;margin:0;padding:0;border:0;border-radius:0}.no-print{display:none}}
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">Cetak struk</button>
        <a href="{{ route('kasir.pesanan.show', $pesanan) }}">Kembali ke pesanan</a>
    </div>
    <main class="receipt">
        <header class="receipt-head">
            <div class="brand">kuligo<small>RESTO POS</small></div>
            <h1>STRUK PESANAN</h1>
        </header>
        <section class="receipt-meta">
            <div><span>No. pesanan</span><b>{{ $pesanan->no_pesanan }}</b></div>
            <div><span>Tanggal</span><b>{{ $pesanan->waktu_pesan?->format('d/m/Y H:i') }}</b></div>
            <div><span>Meja</span><b>{{ $pesanan->meja->nomor_meja }}</b></div>
            <div><span>Pelanggan</span><b>{{ $pesanan->nama_pelanggan ?: 'Pelanggan' }}</b></div>
        </section>
        <table class="items">
            <thead><tr><th>Pesanan</th><th>Jumlah</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($pesanan->detail as $item)
                    <tr>
                        <td><span class="item-name">{{ $item->menu->nama_menu }}</span>@if($item->varian)<small class="item-sub">{{ $item->varian->nama_varian }}</small>@endif</td>
                        <td>{{ $item->jumlah }}×</td>
                        <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <section class="receipt-total">
            <div><span>Jumlah item</span><b>{{ $pesanan->detail->sum('jumlah') }}</b></div>
            <div class="grand"><span>Total</span><b>Rp {{ number_format($pesanan->total_harga, 0, ',', '.') }}</b></div>
        </section>
        <section class="receipt-payment">
            <div><span>Metode</span><b>{{ $metodePembayaran }}</b></div>
            <div><span>Pembayaran</span><b>{{ ucfirst($pesanan->pembayaran->status_pembayaran ?? 'pending') }}</b></div>
            @if($pesanan->pembayaran?->waktu_pembayaran)
                <div><span>Waktu bayar</span><b>{{ $pesanan->pembayaran->waktu_pembayaran->format('d/m/Y H:i') }}</b></div>
            @endif
            @if($pesanan->pembayaran?->metode_pembayaran === 'tunai' && $pesanan->pembayaran->uang_diterima !== null)
                <div><span>Uang diterima</span><b>Rp {{ number_format($pesanan->pembayaran->uang_diterima, 0, ',', '.') }}</b></div>
                <div><span>Kembalian</span><b>Rp {{ number_format($pesanan->pembayaran->kembalian, 0, ',', '.') }}</b></div>
            @endif
            <div><span>Kasir</span><b>{{ $pesanan->user->nama ?? auth()->user()->nama }}</b></div>
        </section>
        <footer class="receipt-foot"><strong>Terima kasih sudah berkunjung!</strong>Semoga harimu menyenangkan.</footer>
    </main>
    @if(request()->boolean('cetak'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
