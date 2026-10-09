<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') · Kuligo Resto POS</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="admin-body">
<aside class="sidebar">
    <a class="brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark"><img src="{{ asset('images/kuligo-food-mark.png') }}" alt=""></span><span><b>kuligo</b><small>RESTO POS</small></span></a>
    <div class="side-caption">MENU UTAMA</div>
    <nav class="side-nav">
        <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span>▦</span>Dashboard</a>
        <a class="{{ request()->routeIs('admin.pesanan') ? 'active' : '' }}" href="{{ route('admin.pesanan') }}"><span>▤</span>Pesanan</a>
        <a class="{{ request()->routeIs('admin.menu.*') ? 'active' : '' }}" href="{{ route('admin.menu.index') }}"><span>⚒</span>Kelola Menu</a>
        <a class="{{ request()->routeIs('admin.meja.*') ? 'active' : '' }}" href="{{ route('admin.meja.index') }}"><span>▦</span>Meja &amp; QR</a>
        <a class="{{ request()->routeIs('admin.laporan') ? 'active' : '' }}" href="{{ route('admin.laporan') }}"><span>▥</span>Laporan Penjualan</a>
        <a class="{{ request()->routeIs('admin.staff.*') ? 'active' : '' }}" href="{{ route('admin.staff.index') }}"><span>♙</span>Kelola Akun</a>
    </nav>
    <div class="side-bottom"><div class="staff-chip"><span class="avatar">{{ strtoupper(substr(auth()->user()->nama ?? 'A', 0, 1)) }}</span><span><b>{{ auth()->user()->nama ?? 'Admin' }}</b><small>{{ ucfirst(auth()->user()->role ?? 'admin') }}</small></span></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">↪ &nbsp;Keluar / Logout</button></form>
    </div>
</aside>
<main class="workspace">
    <header class="topbar"><div class="mobile-brand">kuligo <span>RESTO POS</span></div><div class="system-status">Panel {{ ucfirst(auth()->user()->role ?? 'admin') }}</div><div class="top-actions"><button class="profile-icon" type="button">{{ strtoupper(substr(auth()->user()->nama ?? 'A', 0, 1)) }}</button></div></header>
    <div class="content">
        @if(session('success'))<div class="notice-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="notice-error">{{ $errors->first() }}</div>@endif
        @yield('content')
        <footer class="page-footer">▣ &nbsp;Kuligo Resto POS <span>Data katalog dan transaksi tersimpan pada sistem</span></footer>
    </div>
</main>
</body></html>

