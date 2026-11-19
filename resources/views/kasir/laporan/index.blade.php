@extends('layouts.cashier')
@section('title', 'Laporan Penjualan')
@section('body-class', 'sales-report-page')
@section('content')
    @php
        $labelMetode = ['semua' => 'Semua', 'tunai' => 'Tunai', 'qris' => 'QRIS', 'bank_transfer' => 'Transfer Bank'][$metode];
        $labelStatus = ['semua' => 'Semua status', 'baru' => 'Baru', 'diproses' => 'Diproses', 'siap_diantar' => 'Siap diantar', 'selesai' => 'Selesai', 'batal' => 'Batal'][$statusPesanan];
    @endphp
    <div class="print-report-heading"><b>KULIGO RESTO</b><strong>LAPORAN PENJUALAN</strong><small>Periode {{ \Carbon\Carbon::parse($dari)->format('d M Y') }} – {{ \Carbon\Carbon::parse($sampai)->format('d M Y') }} · Metode {{ $labelMetode }} · Status {{ $labelStatus }} · Dicetak {{ now()->format('d M Y, H:i') }} WIB</small></div>
    <div class="breadcrumb"><b>LAPORAN PENJUALAN</b></div>
    <div class="page-heading">
        <div>
            <h1>Laporan Penjualan</h1>
            <p>Rekap transaksi dan pendapatan restoran berdasarkan rentang tanggal.</p>
        </div>
        <div class="heading-actions print-hide"><button class="button button-dark" type="button" onclick="window.print()">▣ &nbsp;Cetak laporan</button></div>
    </div>
    <form class="cashier-transaction-filters print-hide" method="GET" action="{{ route('kasir.laporan') }}">
        <label>Dari tanggal<input type="date" name="dari" value="{{ $dari }}"></label>
        <label>Sampai tanggal<input type="date" name="sampai" value="{{ $sampai }}"></label>
        <label>Metode pembayaran<select name="metode"><option value="semua" @selected($metode === 'semua')>Semua</option><option value="tunai" @selected($metode === 'tunai')>Tunai</option><option value="qris" @selected($metode === 'qris')>QRIS</option><option value="bank_transfer" @selected($metode === 'bank_transfer')>Transfer Bank</option></select></label>
        <label>Status pesanan<select name="status_pesanan"><option value="semua" @selected($statusPesanan === 'semua')>Semua status</option><option value="baru" @selected($statusPesanan === 'baru')>Baru</option><option value="diproses" @selected($statusPesanan === 'diproses')>Diproses</option><option value="siap_diantar" @selected($statusPesanan === 'siap_diantar')>Siap diantar</option><option value="selesai" @selected($statusPesanan === 'selesai')>Selesai</option><option value="batal" @selected($statusPesanan === 'batal')>Batal</option></select></label>
        <button class="button button-dark" type="submit">Terapkan</button>
        @if($metode !== 'semua' || $statusPesanan !== 'semua' || request()->hasAny(['dari', 'sampai']))<a class="button button-light" href="{{ route('kasir.laporan') }}">Reset filter</a>@endif
    </form>
    <section class="stats-grid report-stats">
        <article class="stat-card">
            <div class="stat-top"><span>Total penjualan</span><span class="stat-icon green">↗</span></div><strong>Rp
                {{ number_format($totalPenjualan, 0, ',', '.') }}</strong><small>Transaksi sesuai filter pada periode ini</small>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span>Total transaksi</span><span class="stat-icon violet">▤</span></div>
            <strong>{{ $totalTransaksi }}</strong><small>{{ \Carbon\Carbon::parse($dari)->format('d M Y') }} –
                {{ \Carbon\Carbon::parse($sampai)->format('d M Y') }}</small>
        </article>
    </section>
    <section class="panel recent-panel">
        <div class="panel-heading">
            <div>
                <h2>Rincian transaksi</h2>
                <p>Daftar pesanan pada periode yang dipilih</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th class="align-right">Total</th>
                    </tr>
                </thead>
                <tbody>@forelse($pesanan as $item)
                    <tr>
                        <td><b>#{{ $item->no_pesanan }}</b></td>
                        <td>{{ $item->waktu_pesan?->format('d M Y, H:i') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $item->status_pesanan)) }}</td>
                        <td class="align-right price">Rp {{ number_format($item->total_harga, 0, ',', '.') }}</td>
                </tr>@empty<tr>
                        <td colspan="4">
                            <div class="empty-state"><span>▥</span><b>Belum ada transaksi</b><small>Coba pilih rentang
                                    tanggal yang berbeda.</small></div>
                        </td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
</section>@endsection
