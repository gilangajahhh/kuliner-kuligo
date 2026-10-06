@extends('layouts.admin')
@section('title', 'Kelola Akun Kasir')
@section('content')
    <div class="breadcrumb">OTO.KASIR / <b>AKUN PENGGUNA</b></div>
    <div class="page-heading">
        <div>
            <h1>Kelola Akun Kasir</h1>
            <p>Atur akses administrator dan kasir untuk sistem Kuligo Resto POS.</p>
        </div><span class="count-pill">♙ {{ $user->count() }} Akun</span>
    </div>
    <section class="panel create-staff">
        <div class="panel-heading">
            <div>
                <h2>Tambah akun baru</h2>
                <p>Buat akses baru untuk anggota tim restoran.</p>
            </div>
        </div>
        <form class="staff-form" method="POST" action="{{ route('admin.staff.store') }}">@csrf<label
                class="field">Nama<input name="nama" required placeholder="Nama lengkap"></label><label
                class="field">Username<input name="username" required placeholder="username"></label><label
                class="field">Password<input name="password" type="password" minlength="8" required
                    placeholder="Minimal 8 karakter"></label><label class="field">Peran<select name="role">
                    <option value="kasir">Kasir</option>
                    <option value="admin">Admin</option>
                </select></label><button class="button button-dark">＋ Buat akun</button></form>
    </section>
    <section class="panel recent-panel">
        <div class="panel-heading">
            <div>
                <h2>Daftar akun</h2>
                <p>{{ $user->count() }} akun terdaftar di sistem</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>@forelse($user as $akun)
                    <tr>
                        <td>
                            <div class="menu-name"><span
                                    class="avatar avatar-soft">{{ strtoupper(substr($akun->nama, 0, 1)) }}</span><b>{{ $akun->nama }}</b>
                            </div>
                        </td>
                        <td>{{ $akun->username }}</td>
                        <td><span class="category-tag">{{ ucfirst($akun->role) }}</span></td>
                        <td><span
                                class="status {{ $akun->status_aktif ? 'status-ok' : 'status-off' }}"><i></i>{{ $akun->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.staff.update', $akun) }}" class="staff-inline">@csrf
                                @method('PATCH')<input type="hidden" name="nama" value="{{ $akun->nama }}"><select
                                    name="role" aria-label="Peran akun">
                                    <option value="kasir" @selected($akun->role === 'kasir')>Kasir</option>
                                    <option value="admin" @selected($akun->role === 'admin')>Admin</option>
                                </select><input type="hidden" name="status_aktif" value="0"><label
                                    class="staff-active"><input type="checkbox" name="status_aktif" value="1"
                                        @checked($akun->status_aktif)> Aktif</label><button class="button button-light"
                                    type="submit">Simpan</button></form>
                        </td>
                </tr>@empty<tr>
                        <td colspan="5">
                            <div class="empty-state"><b>Belum ada akun</b></div>
                        </td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
</section>@endsection