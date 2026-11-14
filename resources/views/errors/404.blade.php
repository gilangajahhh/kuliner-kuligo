<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Halaman tidak ditemukan · Kuligo</title><link rel="stylesheet" href="{{ asset('css/error.css') }}"></head>
@php
    $areaOperasional = request()->is('admin/*') || request()->is('kasir/*') || request()->is('login');
    $menuPelangganUrl = request()->is('menu/*') && request()->segment(2)
        ? route('menu.index', ['kode_qr' => request()->segment(2)])
        : route('menu.home');
    $urlKembali = $areaOperasional ? url('/') : $menuPelangganUrl;
    $labelKembali = $areaOperasional ? 'Kembali ke halaman utama' : 'Kembali ke menu pelanggan';
@endphp
<body><main class="error-card"><a class="error-brand" href="{{ $urlKembali }}">kuligo <small>RESTO POS</small></a><span class="error-code">404</span><h1>Halaman tidak ditemukan</h1><p>Alamat yang Anda buka tidak tersedia atau sudah dipindahkan.</p><a class="error-action" href="{{ $urlKembali }}">{{ $labelKembali }}</a></main></body>
</html>
