@extends('layouts.admin')
@section('title', 'Kelola Meja & QR')
@section('content')
    <div class="breadcrumb">OTO.KASIR / <b>MEJA &amp; QR</b></div>
    <div class="page-heading">
        <div>
            <h1>Kelola Meja &amp; QR</h1>
            <p>Tambah, ubah, dan hapus meja serta tautan menu pelanggan.</p>
        </div>
        <span class="count-pill">▦ {{ $meja->count() }} Meja</span>
    </div>

    <section class="panel create-staff">
        <div class="panel-heading">
            <div>
                <h2>Tambah meja</h2>
                <p>Kode QR dibuat otomatis dari nama meja.</p>
            </div>
        </div>
        <form class="staff-form table-create-form" method="POST" action="{{ route('admin.meja.store') }}">
            @csrf
            <label class="field">Nama / nomor meja<input name="nomor_meja" required maxlength="50" placeholder="Contoh: Meja 01" value="{{ old('nomor_meja') }}"></label>
            <button class="button button-dark" type="submit">＋ Tambah meja</button>
        </form>
    </section>

    <section class="panel recent-panel">
        <div class="panel-heading">
            <div>
                <h2>Daftar meja</h2>
                <p>QR yang sudah dicetak tetap aktif saat nama meja diubah.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Meja</th><th>Kode QR</th><th>Riwayat pesanan</th><th>Tautan menu</th><th>QR</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($meja as $item)
                        <tr>
                            <td><b>{{ $item->nomor_meja }}</b></td>
                            <td><span class="category-tag">{{ $item->kode_qr }}</span></td>
                            <td>{{ $item->pesanan_count }} pesanan</td>
                            <td><a href="{{ route('menu.index', $item->kode_qr) }}" target="_blank" rel="noopener">Buka menu</a></td>
                            <td><a class="button button-light" href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&amp;data={{ urlencode(route('menu.index', $item->kode_qr)) }}" target="_blank" rel="noopener">Buka / cetak</a></td>
                            <td>
                                <div class="table-actions">
                                    <details class="crud-details">
                                        <summary class="button button-light">✎ Ubah</summary>
                                        <form class="crud-edit-form" method="POST" action="{{ route('admin.meja.update', $item) }}">
                                            @csrf @method('PUT')
                                            <label class="field">Nama / nomor meja<input class="crud-row-input" name="nomor_meja" value="{{ $item->nomor_meja }}" required maxlength="50"></label>
                                            <small class="crud-note">Kode QR tidak berubah agar QR cetak tetap bisa dipakai.</small>
                                            <button class="button button-dark" type="submit">Simpan perubahan</button>
                                        </form>
                                    </details>
                                    @if($item->pesanan_count === 0)
                                        <form method="POST" action="{{ route('admin.meja.destroy', $item) }}" onsubmit="return confirm('Hapus meja ini?');">
                                            @csrf @method('DELETE')
                                            <button class="button button-light" type="submit">Hapus</button>
                                        </form>
                                    @else
                                        <span class="crud-note">Tersimpan karena ada riwayat transaksi</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><span>▦</span><b>Belum ada meja</b><small>Tambahkan meja di atas untuk membuat tautan dan QR pelanggan.</small></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <p class="crud-note">Pastikan alamat aplikasi pada tautan menu dapat diakses dari ponsel pelanggan di jaringan yang digunakan.</p>
@endsection
