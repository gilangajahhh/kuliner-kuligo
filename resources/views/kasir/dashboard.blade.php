@extends('layouts.cashier')
@section('title', 'Dashboard Kasir')
@section('content')
<div class="breadcrumb">KULIGO POS / <b>DASHBOARD</b></div>
<div class="page-heading cashier-heading">
    <div><span class="cashier-kicker">RINGKASAN OPERASIONAL</span>
        <h1>Ringkasan Kasir</h1>
        <p>Aktivitas restoran hari ini, {{ auth()->user()->nama }}.</p>
    </div><span class="cashier-date">{{ now()->translatedFormat('l, d M Y') }}</span>
</div>

<section class="cashier-stats">
    <article class="cashier-stat cashier-stat-dark"><span class="stat-icon">▤</span><small>PESANAN
            AKTIF</small><strong>{{ str_pad($ringkasan['pesanan_aktif'], 2, '0', STR_PAD_LEFT) }}</strong><span
            class="stat-foot">Antrean yang sedang berjalan</span></article>
    <article class="cashier-stat"><span class="stat-icon">◷</span><small>PESANAN
            MASUK</small><strong>{{ str_pad($ringkasan['pesanan_masuk'], 2, '0', STR_PAD_LEFT) }}</strong><span
            class="stat-foot">Menunggu pemeriksaan kasir</span></article>
    <article class="cashier-stat"><span class="stat-icon">✓</span><small>PESANAN SELESAI HARI
            INI</small><strong>{{ str_pad($ringkasan['pesanan_selesai'], 2, '0', STR_PAD_LEFT) }}</strong><span
            class="stat-foot">Sudah selesai disajikan</span></article>
    <article class="cashier-stat"><span class="stat-icon">Rp</span><small>PENDAPATAN HARI INI</small><strong
            class="stat-money">{{ number_format($ringkasan['pendapatan_hari_ini'], 0, ',', '.') }}</strong><span
            class="stat-foot">Dari pesanan yang tercatat</span></article>
</section>

<div class="cashier-overview-grid">
    <section class="panel cashier-chart-panel">
        <div class="cashier-panel-title">
            <div>
                <h2>Arus penjualan 7 hari</h2>
                <p>Ringkasan nilai pesanan harian</p>
            </div><span class="chart-period">7 HARI</span>
        </div>
        <div class="sales-chart">@php($maxSales = max(1, $grafikMingguan->max('nilai')))
            @foreach($grafikMingguan as $hari)
                <div class="sales-day"><span
                        class="sales-value">{{ $hari['nilai'] > 0 ? number_format($hari['nilai'] / 1000, 0) . 'k' : '—' }}</span>
                    <div class="sales-track"><i style="height:{{ max(4, round($hari['nilai'] / $maxSales * 100)) }}%"></i>
                    </div><small>{{ $hari['label'] }}</small>
            </div>@endforeach
        </div>
        <div class="chart-foot"><i></i> Penjualan tercatat dari seluruh pesanan selain yang dibatalkan</div>
    </section>
    <section class="panel cashier-quick-panel cashier-quick-simple">
        <div class="cashier-panel-title">
            <div>
                <h2>Perlu diproses</h2>
                <p>{{ $ringkasan['pesanan_masuk'] }} pesanan menunggu kasir</p>
            </div>
        </div>
        <a class="button button-dark quick-board" href="{{ route('kasir.pesanan') }}">Buka daftar pesanan <span>→</span></a>
        <a class="quick-action" href="{{ route('kasir.meja.index') }}"><span class="quick-icon">▦</span><span><b>Kelola meja</b><small>Lihat meja kosong dan terisi</small></span><strong>→</strong></a>
    </section>
</div>

<section class="panel cashier-recent-panel">
    <div class="panel-heading">
        <div>
            <h2>Pesanan terbaru</h2>
            <p>Pesanan pelanggan yang baru masuk ke sistem</p>
        </div><a class="text-link" href="{{ route('kasir.pesanan') }}">Lihat semua →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>No. Meja</th>
                    <th>Pelanggan</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>@forelse($pesananTerbaru as $order)
                <tr>
                    <td><b>#{{ $order->no_pesanan }}</b></td>
                    <td>{{ $order->meja->nomor_meja }}</td>
                    <td>{{ $order->nama_pelanggan ?: 'Pelanggan' }}</td>
                    <td class="price">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                    <td><span
                            class="queue-status {{ $order->status_pesanan === 'selesai' ? 'done' : ($order->status_pesanan === 'batal' ? 'cancelled' : '') }}">{{ ucfirst(str_replace('_', ' ', $order->status_pesanan)) }}</span>
                    </td>
                    <td><a class="button button-light" href="{{ route('kasir.pesanan.struk', $order) }}?cetak=1" target="_blank" rel="noopener">Cetak struk</a></td>
            </tr>@empty<tr>
                    <td colspan="6">
                        <div class="empty-state"><b>Belum ada pesanan</b><small>Pesanan pelanggan akan muncul di
                                sini.</small></div>
                    </td>
                </tr>@endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
