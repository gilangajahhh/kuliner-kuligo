@extends('layouts.admin')
@section('title', 'Kelola Menu')
@section('content')
    <div class="breadcrumb">OTO.KASIR / <b>KATALOG PRODUK</b></div>
    <div class="page-heading">
        <div>
            <h1>Kelola Menu</h1>
            <p>Manajemen item hidangan, varian, ketersediaan, dan harga sesuai katalog POS.</p>
        </div>
        <div class="heading-actions"><span class="count-pill">{{ $menu->total() }} Menu</span><a class="button button-light"
                href="{{ route('admin.kategori.index') }}">Kelola Kategori</a><a class="button button-dark"
                href="{{ route('admin.menu.create') }}">＋ Tambah Menu</a></div>
    </div>
    <section class="panel table-panel">
        <div class="table-toolbar">
            <div class="filter-tabs"><button class="filter-tab chosen" type="button">Semua
                    ({{ $menu->total() }})</button><button class="filter-tab" type="button">Makanan</button><button
                    class="filter-tab" type="button">Minuman</button></div><label class="search-box">⌕ <input
                    id="menu-search" type="search" placeholder="Cari nama menu..."></label>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th class="align-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menu as $item)
                        <tr>
                            <td class="muted">{{ $menu->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="menu-name"><span
                                        class="food-thumb">{{ str_contains(strtolower($item->kategori->nama_kategori ?? ''), 'minum') ? '☕' : '🍽' }}</span><span><b>{{ $item->nama_menu }}</b><small>{{ $item->deskripsi ?: 'Menu pilihan Kuligo Resto' }}</small></span>
                                </div>
                            </td>
                            <td><span class="category-tag">{{ $item->kategori->nama_kategori ?? 'Lainnya' }}</span></td>
                            <td class="price">Rp {{ number_format($item->harga_dasar, 0, ',', '.') }}</td>
                            <td><span
                                    class="status {{ $item->status_tersedia ? 'status-ok' : 'status-off' }}"><i></i>{{ $item->status_tersedia ? 'Tersedia' : 'Habis' }}</span>
                            </td>
                            <td class="align-right"><a class="button button-light"
                                    href="{{ route('admin.menu.edit', $item) }}">✎ Ubah</a></td>
                    </tr>@empty<tr>
                        <td colspan="6">
                            <div class="empty-state"><span>♨</span><b>Belum ada menu</b><small>Tambahkan menu pertama untuk
                                    mulai mengelola katalog.</small><a class="button button-dark"
                                    href="{{ route('admin.menu.create') }}">＋ Tambah Menu</a></div>
                        </td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="table-bottom"><span>▣ Total {{ $menu->total() }} Menu Terdaftar di Sistem POS</span><span>Menampilkan
                {{ $menu->firstItem() ?? 0 }}–{{ $menu->lastItem() ?? 0 }} data</span></div>
        <div class="pagination">{{ $menu->links() }}</div>
    </section>
    <section class="summary-grid">
        <article class="summary-card dark-card">
            <div class="summary-label">KOMPOSISI MENU <span>◉</span></div>
            <div class="composition"><strong>{{ $menu->total() }}</strong><span>item katalog</span></div>
            <div class="meter"><i style="width:72%"></i></div><small>Kelola menu dan pantau stok harian.</small>
        </article>
        <article class="summary-card">
            <div class="summary-label">RATA-RATA HARGA ITEM <span>▣</span></div><strong
                class="summary-value">{{ $menu->count() ? 'Rp ' . number_format($menu->avg('harga_dasar'), 0, ',', '.') : 'Rp 0' }}</strong><small>Harga
                menu aktif dalam katalog POS</small>
        </article>
        <article class="summary-card alert-card">
            <div class="summary-label">PENGINGAT KETERSEDIAAN <span>♧</span></div><strong
                class="summary-value">{{ $menu->where('status_tersedia', false)->count() }} Menu
                Habis</strong><small>Perbarui ketersediaan stok menu Anda.</small>
        </article>
    </section>
    <script>document.addEventListener("DOMContentLoaded", () => { const search = document.querySelector("#menu-search"), rows = [...document.querySelectorAll(".table-panel tbody tr")].filter(r => r.querySelector(".category-tag")), tabs = [...document.querySelectorAll(".filter-tab")]; let category = "all"; function filter() { const q = (search?.value || "").trim().toLocaleLowerCase("id"); rows.forEach(row => { const text = row.textContent.toLocaleLowerCase("id"), cat = row.querySelector(".category-tag")?.textContent.toLocaleLowerCase("id") || ""; row.hidden = !text.includes(q) || (category !== "all" && !cat.includes(category)) }) } search?.addEventListener("input", filter); tabs.forEach((tab, i) => tab.addEventListener("click", () => { category = i === 1 ? "makanan" : i === 2 ? "minuman" : "all"; tabs.forEach(t => t.classList.toggle("chosen", t === tab)); filter() })) });</script>
@endsection