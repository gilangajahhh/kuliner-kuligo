@extends('layouts.admin')
@section('title', isset($menu) ? 'Ubah Menu' : 'Tambah Menu')
@section('content')
    <div class="breadcrumb"><a href="{{ route('admin.menu.index') }}">KATALOG PRODUK</a> /
        {{ isset($menu) ? 'UBAH MENU' : 'TAMBAH MENU' }}</div>
    <div class="page-heading">
        <div>
            <h1>{{ isset($menu) ? 'Ubah Menu' : 'Tambah Menu' }}</h1>
            <p>Lengkapi informasi hidangan untuk katalog Kuligo Resto POS.</p>
        </div>
    </div>
    <form class="form-card" method="POST" enctype="multipart/form-data"
        action="{{ isset($menu) ? route('admin.menu.update', $menu) : route('admin.menu.store') }}">@csrf @foreach(old('varian_hapus', []) as $hapusVarian)<input type="hidden" name="varian_hapus[]" value="{{ $hapusVarian }}">@endforeach @if(isset($menu))
        @method('PUT') @endif
        <div class="form-grid"><label class="field">Nama menu<input name="nama_menu" required maxlength="150"
                    value="{{ old('nama_menu', $menu->nama_menu ?? '') }}"
                    placeholder="Contoh: Nasi goreng spesial"></label><label class="field">Kategori<select
                    name="id_kategori" required>
                    <option value="">Pilih kategori</option>@foreach($kategori as $item)
                    <option value="{{ $item->id_kategori }}" @selected(old('id_kategori', $menu->id_kategori ?? '') == $item->id_kategori)>{{ $item->nama_kategori }}</option>@endforeach
                </select></label><label class="field">Harga (Rp)<input type="number" min="0" name="harga_dasar" required
                    value="{{ old('harga_dasar', $menu->harga_dasar ?? '') }}" placeholder="30000"></label><label
                class="field">Pilih foto menu <span class="optional">Opsional · maksimal 2 MB</span><input type="file"
                    name="gambar" accept="image/*">@error('gambar')<span
                    class="error-text">{{ $message }}</span>@enderror</label><label
                class="field field-wide">Deskripsi<textarea name="deskripsi" rows="4"
                    placeholder="Ceritakan menu Anda">{{ old('deskripsi', $menu->deskripsi ?? '') }}</textarea></label>
            <div class="field field-wide"><span>Varian menu <span class="optional">Opsional · tersimpan di tabel
                        varian_menu</span></span>
                <div class="variant-list" id="variant-list">
                    @forelse(old('varian', isset($menu) ? $menu->varian : []) as $i => $varian)
                        <div class="variant-row">@if(data_get($varian, 'id_varian'))<input type="hidden"
                        name="varian[{{ $i }}][id_varian]" value="{{ data_get($varian, 'id_varian') }}">@endif<input
                                name="varian[{{ $i }}][nama_varian]"
                                value="{{ old("varian.$i.nama_varian", data_get($varian, 'nama_varian')) }}"
                                placeholder="Nama varian, contoh: Large"><input type="number" min="0"
                                name="varian[{{ $i }}][harga_tambahan]"
                                value="{{ old("varian.$i.harga_tambahan", data_get($varian, 'harga_tambahan', 0)) }}"
                                placeholder="Tambahan harga"><button class="remove-variant" type="button" aria-label="Hapus varian">×</button></div>@empty
                                <div class="variant-row"><input name="varian[0][nama_varian]"
                                        placeholder="Nama varian, contoh: Large"><input type="number" min="0"
                                        name="varian[0][harga_tambahan]" value="0" placeholder="Tambahan harga"><button
                            class="remove-variant" type="button" aria-label="Hapus varian">×</button></div>@endforelse
                </div><button class="button button-light add-variant" type="button" id="add-variant">＋ Tambah
                    varian</button>
            </div><label class="check-field"><input type="hidden" name="status_tersedia" value="0"><input type="checkbox"
                    name="status_tersedia" value="1" @checked(old('status_tersedia', $menu->status_tersedia ?? true))> Menu
                tersedia untuk dipesan</label>
        </div>
        <div class="form-actions"><a class="button button-light" href="{{ route('admin.menu.index') }}">Batal</a><button
                class="button button-dark" type="submit">{{ isset($menu) ? 'Simpan Perubahan' : 'Simpan Menu' }}</button>
        </div>
    </form>
    <script>document.addEventListener('DOMContentLoaded', () => { const list = document.querySelector('#variant-list'); const form = list?.closest('form'); document.querySelector('#add-variant')?.addEventListener('click', () => { const i = list.querySelectorAll('.variant-row').length; const row = document.createElement('div'); row.className = 'variant-row'; row.innerHTML = `<input name="varian[${i}][nama_varian]" placeholder="Nama varian"><input type="number" min="0" name="varian[${i}][harga_tambahan]" value="0" placeholder="Tambahan harga"><button class="remove-variant" type="button" aria-label="Hapus varian">×</button>`; list.append(row) }); list?.addEventListener('click', e => { const button = e.target.closest('.remove-variant'); if (!button) return; const row = button.closest('.variant-row'); const id = row.querySelector('input[name$="[id_varian]"]')?.value; if (id) { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'varian_hapus[]'; input.value = id; form.append(input) } row.remove() }) });</script>
@endsection
