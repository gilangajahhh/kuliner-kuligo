<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard Kasir') · Kuligo Resto POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/app-notifications.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/cashier.css') }}?v=simple-cashier-pages-20261116">
    <link rel="stylesheet" href="{{ asset('css/cashier-report.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/admin-meja.css') }}?v=1">
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js']) @endif
</head>

<body class="admin-body @yield('body-class')">
    <aside class="sidebar"><a class="brand" href="{{ route('kasir.dashboard') }}"><span class="brand-mark"><img
                    src="{{ asset('images/kuligo-food-mark.png') }}" alt=""></span><span><b>kuligo</b><small>RESTO
                    POS</small></span></a>
        <div class="side-caption">KASIR</div>
        <nav class="side-nav"><a class="{{ request()->routeIs('kasir.dashboard') ? 'active' : '' }}"
                href="{{ route('kasir.dashboard') }}"><span>▦</span>Ringkasan</a><a
                class="{{ request()->routeIs('kasir.pesanan') || request()->routeIs('kasir.pesanan.*') ? 'active' : '' }}"
                href="{{ route('kasir.pesanan') }}"><span>▤</span>Pesanan</a><a
                class="{{ request()->routeIs('kasir.reservasi.*') ? 'active' : '' }}" href="{{ route('kasir.reservasi.index') }}"><span>◷</span>Reservasi</a><a
                class="{{ request()->routeIs('kasir.laporan') ? 'active' : '' }}" href="{{ route('kasir.laporan') }}"><span>▥</span>Laporan Penjualan</a><a
                class="{{ request()->routeIs('kasir.meja.*') ? 'active' : '' }}" href="{{ route('kasir.meja.index') }}"><span>▦</span>Meja</a><a
                class="{{ request()->routeIs('kasir.transaksi') ? 'active' : '' }}"
                href="{{ route('kasir.transaksi') }}"><span>▧</span>Riwayat pembayaran</a></nav>
        <div class="side-bottom">
            <div class="staff-chip"><span
                    class="avatar">{{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}</span><span><b>{{ auth()->user()->nama }}</b><small>Kasir</small></span>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">Keluar / Logout</button>
            </form>
        </div>
    </aside>
    <main class="workspace">
        <header class="topbar">
            <div class="system-status">Panel Kasir</div>
            <div class="top-actions"><span>{{ auth()->user()->nama }}</span></div>
        </header>
        <div class="content cashier-content">@if(session('success'))
        <div class="notice-success">{{ session('success') }}</div>@endif @if($errors->any())
            <div class="notice-error">{{ $errors->first() }}</div>@endif @yield('content')
        </div>
    </main>
    <div class="global-loading" id="global-loading" role="status" aria-live="polite" hidden><span class="loading-spinner" aria-hidden="true"></span><span>Memproses permintaan…</span></div>
    <script src="{{ asset('js/admin.js') }}" defer></script>
    <script src="{{ asset('js/app-notifications.js') }}" defer></script>
</body>

</html>
