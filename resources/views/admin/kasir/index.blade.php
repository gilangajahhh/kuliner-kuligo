@extends('layouts.admin')
@section('title', 'Kelola Akun')
@section('content')
    <div class="breadcrumb">OTO.KASIR / <b>AKUN PENGGUNA</b></div>
    <div class="page-heading">
        <div>
            <h1>Kelola Akun</h1>
            <p>Atur data, peran, sandi, dan akses administrator serta kasir.</p>
        </div>
        <span class="count-pill">♙ {{ $user->count() }} Akun</span>
    </div>

    <section class="panel create-staff">
        <div class="panel-heading">
            <div>
                <h2>Tambah akun baru</h2>
                <p>Buat akses baru untuk anggota tim restoran.</p>
            </div>
        </div>
        <form class="staff-form" method="POST" action="{{ route('admin.staff.store') }}">
            @csrf
            <label class="field">Nama<input name="nama" required maxlength="150" placeholder="Nama lengkap" value="{{ old('nama') }}"></label>
            <label class="field">Username<input name="username" required maxlength="50" placeholder="username" value="{{ old('username') }}"></label>
            <label class="field">Password<input name="password" type="password" minlength="8" required autocomplete="new-password" placeholder="Minimal 8 karakter"></label>
            <label class="field">Peran<select name="role"><option value="kasir" @selected(old('role', 'kasir') === 'kasir')>Kasir</option><option value="admin" @selected(old('role') === 'admin')>Admin</option></select></label>
            <button class="button button-dark" type="submit">＋ Buat akun</button>
        </form>
    </section>

    <section class="panel recent-panel">
        <div class="panel-heading">
            <div>
                <h2>Daftar akun</h2>
                <p>{{ $user->count() }} akun terdaftar · sandi tidak ditampilkan demi keamanan</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Nama</th><th>Username</th><th>Peran</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($user as $akun)
                        <tr>
                            <td><div class="menu-name"><span class="avatar avatar-soft">{{ strtoupper(substr($akun->nama, 0, 1)) }}</span><b>{{ $akun->nama }}</b></div></td>
                            <td>{{ $akun->username }}</td>
                            <td><span class="category-tag">{{ ucfirst($akun->role) }}</span></td>
                            <td><span class="status {{ $akun->status_aktif ? 'status-ok' : 'status-off' }}"><i></i>{{ $akun->status_aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <div class="crud-actions">
                                    <details class="crud-details">
                                        <summary class="button button-light">✎ Ubah</summary>
                                        <form class="crud-edit-form" method="POST" action="{{ route('admin.staff.update', $akun) }}">
                                            @csrf @method('PATCH')
                                            <label class="field">Nama<input name="nama" value="{{ $akun->nama }}" required maxlength="150"></label>
                                            <label class="field">Username<input name="username" value="{{ $akun->username }}" required maxlength="50"></label>
                                            <label class="field">Password baru <span class="optional">Kosongkan jika tidak diganti</span><input name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter"></label>
                                            <label class="field">Peran<select name="role"><option value="kasir" @selected($akun->role === 'kasir')>Kasir</option><option value="admin" @selected($akun->role === 'admin')>Admin</option></select></label>
                                            <input type="hidden" name="status_aktif" value="0">
                                            <label class="staff-active"><input type="checkbox" name="status_aktif" value="1" @checked($akun->status_aktif)> Akun aktif</label>
                                            <button class="button button-dark" type="submit">Simpan perubahan</button>
                                        </form>
                                    </details>
                                    @if((int) auth()->id() !== (int) $akun->getKey())
                                        <form method="POST" action="{{ route('admin.staff.destroy', $akun) }}" onsubmit="return confirm('Hapus akun ini? Jika memiliki riwayat transaksi, aksesnya akan dinonaktifkan agar riwayat tetap aman.');">
                                            @csrf @method('DELETE')
                                            <button class="button button-light" type="submit">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><b>Belum ada akun</b></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
