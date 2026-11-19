@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
    <div class="breadcrumb"><b>RINGKASAN PENJUALAN</b></div>
    <div class="page-heading">
        <div>
            <h1>Dashboard Utama</h1>
            <p>Selamat datang kembali, {{ auth()->user()->nama ?? 'Admin' }}. Pantau performa penjualan restoran selama tujuh hari terakhir.</p>
        </div>
        <div class="heading-actions"><span class="date-chip">◷ &nbsp;{{ now()->translatedFormat('l, d F Y') }}</span><a
                class="button button-dark" href="{{ route('admin.menu.index') }}">＋ Kelola Menu</a></div>
    </div>
    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-top"><span>Pesanan hari ini</span><span class="stat-icon violet">▤</span></div>
            <strong>{{ $ringkasan['pesanan_hari_ini'] }}</strong><small>Transaksi tercatat hari ini</small>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span>Penjualan hari ini</span><span class="stat-icon green">↗</span></div><strong>Rp
                {{ number_format($ringkasan['penjualan_hari_ini'], 0, ',', '.') }}</strong><small>Total pendapatan
                berjalan</small>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span>Pesanan aktif</span><span class="stat-icon amber">◷</span></div>
            <strong>{{ $ringkasan['pesanan_aktif'] }}</strong><small>Perlu diproses oleh tim</small>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span>Menu tidak tersedia</span><span class="stat-icon red">!</span></div>
            <strong>{{ $ringkasan['menu_tidak_tersedia'] }}</strong><small><a href="{{ route('admin.menu.index') }}">Periksa
                    ketersediaan →</a></small>
        </article>
    </section>
    <section class="panel weekly-sales-panel">
        <div class="panel-heading weekly-sales-heading">
            <div><span class="sales-eyebrow">TREN PENJUALAN</span><h2>Penjualan 7 hari terakhir</h2><p>{{ now()->subDays(6)->translatedFormat('d M') }} – {{ now()->translatedFormat('d M Y') }}</p></div>
            <div class="weekly-sales-total"><small>Total minggu ini</small><b>Rp {{ number_format($totalPenjualanMinggu, 0, ',', '.') }}</b></div>
        </div>
        <div class="weekly-chart" role="img" aria-label="Grafik penjualan tujuh hari terakhir">
            <div class="chart-scale" aria-hidden="true"><span>Rp {{ number_format($grafikPenjualan->max('total'), 0, ',', '.') }}</span><span>Rp {{ number_format($grafikPenjualan->max('total') * .75, 0, ',', '.') }}</span><span>Rp {{ number_format($grafikPenjualan->max('total') * .5, 0, ',', '.') }}</span><span>Rp {{ number_format($grafikPenjualan->max('total') * .25, 0, ',', '.') }}</span><span>Rp 0</span></div>
            <div class="chart-plot">
                <div class="chart-grid-lines" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
                @foreach($grafikPenjualan as $hari)
                    <div class="chart-day" title="{{ $hari['tanggal_label'] }}: Rp {{ number_format($hari['total'], 0, ',', '.') }}">
                        <div class="chart-bar-track"><div class="chart-bar {{ $hari['tanggal'] === today()->toDateString() ? 'is-today' : '' }}" style="height: {{ $hari['persentase'] }}%"></div></div>
                        <b class="chart-day-value">{{ $hari['total'] > 0 ? 'Rp ' . $hari['nilai_label'] : '—' }}</b><span>{{ $hari['label'] }}</span><small>{{ $hari['tanggal_label'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
        <p class="chart-caption"><i></i> Nilai penjualan per hari, tidak termasuk pesanan batal.</p>
    </section>
    <section class="summary-grid">
        <article class="summary-card dark-card">
            <div class="summary-label">KATALOG MENU</div>
            <div class="composition"><strong>{{ $ringkasan['total_menu'] }}</strong><span>menu terdaftar</span></div>
            <small>{{ $ringkasan['menu_tidak_tersedia'] }} menu sedang tidak tersedia</small>
        </article>
        <article class="summary-card">
            <div class="summary-label">KELOLA MENU</div><strong class="summary-value"><a
                    href="{{ route('admin.menu.index') }}">Buka katalog ?</a></strong><small>Atur kategori, varian, harga,
                dan ketersediaan.</small>
        </article>
        <article class="summary-card">
            <div class="summary-label">LAPORAN PENJUALAN</div><strong class="summary-value"><a
                    href="{{ route('kasir.laporan') }}">Lihat laporan ?</a></strong><small>Rekap dari data transaksi
                pesanan.</small>
        </article>
</section>@endsection
