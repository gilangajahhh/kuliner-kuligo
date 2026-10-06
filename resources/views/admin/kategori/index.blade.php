@extends('layouts.admin')
@section('title', 'Kategori Menu')
@section('content')
    <div class="breadcrumb"><a href="{{ route('admin.menu.index') }}">KATALOG PRODUK</a> / <b>KATEGORI MENU</b></div>
    <div class="page-heading">
        <div>
            <h1>Kategori Menu</h1>
            <p>Kategori produk digunakan pada katalog dan data menu.</p>
        </div><a class="button button-light" href="{{ route('admin.menu.index') }}">← Kembali ke Menu</a>
    </div>
    <section class="panel create-staff">
        <div class="panel-heading">
            <div>
                <h2>Tambah kategori</h2>
                <p>Nama kategori disimpan pada tabel kategori_menu.</p>
            </div>
        </div>
        <form class="staff-form category-form" method="POST" action="{{ route('admin.kategori.store') }}">@csrf<label
                class="field">Nama kategori<input name="nama_kategori" maxlength="100" required
                    placeholder="Contoh: Makanan"></label><label class="field">Urutan tampil<input type="number"
                    name="urutan_tampil" value="0"></label><button class="button button-dark" type="submit">＋ Tambah
                kategori</button></form>
    </section>
    <section class="panel recent-panel">
        <div class="panel-heading">
            <div>
                <h2>Daftar kategori</h2>
                <p>{{ $kategori->count() }} kategori terdaftar</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Urutan</th>
                        <th>Nama kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>@forelse($kategori as $item)
                    <tr>
                        <td>{{ $item->urutan_tampil }}</td>
                        <td>
                            <form class="category-inline" method="POST"
                                action="{{ route('admin.kategori.update', $item) }}">@csrf @method('PUT')<input
                                    name="nama_kategori" required maxlength="100" value="{{ $item->nama_kategori }}"><input
                                    type="number" name="urutan_tampil" value="{{ $item->urutan_tampil }}"><button
                                    class="button button-light">Simpan</button></form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.kategori.destroy', $item) }}"
                                onsubmit="return confirm('Hapus kategori ini? Kategori yang masih digunakan menu tidak dapat dihapus.');">
                                @csrf @method('DELETE')<button class="button button-light" type="submit">Hapus</button>
                            </form>
                        </td>
                </tr>@empty<tr>
                        <td colspan="3">
                            <div class="empty-state"><b>Belum ada kategori</b><small>Tambahkan kategori agar menu dapat
                                    dimasukkan ke katalog.</small></div>
                        </td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
</section>@endsection