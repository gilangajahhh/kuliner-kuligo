@extends('layouts.admin')
@section('title', 'Meja & QR')
@section('content')
    <div class="breadcrumb">OTO.KASIR / <b>MEJA &amp; QR</b></div>
    <div class="page-heading">
        <div>
            <h1>Meja &amp; QR pelanggan</h1>
            <p>Daftarkan meja dan cetak QR yang membuka menu pelanggan khusus meja tersebut.</p>
        </div><span class="count-pill">{{ $meja->count() }} Meja</span>
    </div>
    <section class="panel" style="padding:24px;margin-bottom:24px">
        <h2 style="margin:0 0 6px">Tambah meja</h2>
        <p style="margin:0 0 18px;color:#777">Kode QR dibuat otomatis dari nama meja.</p>
        <form method="POST" action="{{ route('admin.meja.store') }}"
            style="display:flex;gap:12px;max-width:600px;flex-wrap:wrap">@csrf<label class="field"
                style="flex:1;min-width:220px">Nama / nomor meja<input name="nomor_meja" required maxlength="50"
                    placeholder="Contoh: Meja 01" value="{{ old('nomor_meja') }}"></label><button class="button button-dark"
                style="align-self:end" type="submit">＋ Tambah meja</button></form>
    </section>
    <section class="panel" style="padding:24px">
        <div class="panel-heading">
            <div>
                <h2>Daftar QR meja</h2>
                <p>Scan untuk membuka halaman menu pelanggan</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Meja</th>
                        <th>Kode QR</th>
                        <th>Tautan menu</th>
                        <th>QR untuk dicetak</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($meja as $item)
                        <tr>
                            <td><b>{{ $item->nomor_meja }}</b></td>
                            <td><span class="category-tag">{{ $item->kode_qr }}</span></td>
                            <td><a href="{{ route('menu.index', $item->kode_qr) }}" target="_blank"
                                    rel="noopener">{{ route('menu.index', $item->kode_qr) }}</a></td>
                            <td><a class="button button-light"
                                    href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&amp;data={{ urlencode(route('menu.index', $item->kode_qr)) }}"
                                    target="_blank" rel="noopener">Buka / cetak QR</a></td>
                    </tr>@empty<tr>
                        <td colspan="4">
                            <div class="empty-state"><span>▦</span><b>Belum ada meja</b><small>Tambahkan meja di atas untuk
                                    membuat tautan dan QR pelanggan.</small></div>
                        </td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
    </section>
    <p style="font-size:12px;color:#777;margin-top:14px">Pastikan alamat aplikasi yang tampil di tautan menu dapat diakses
        dari ponsel pelanggan pada jaringan yang digunakan.</p>
@endsection