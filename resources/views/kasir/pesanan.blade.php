@extends('layouts.cashier')
@section('title', 'Pesanan Kasir')
@section('content')
    <div class="breadcrumb">KULIGO POS / <b>DAFTAR PESANAN</b></div>
    <div class="page-heading cashier-heading">
        <div><span class="cashier-kicker">ANTREAN LANGSUNG</span>
            <h1>Tampilan Pesanan</h1>
            <p>Kelola pesanan masuk, proses di dapur, dan antar ke meja pelanggan.</p>
        </div><span class="cashier-date"><i class="queue-live-dot"></i> {{ now()->translatedFormat('l, d M Y') }}</span>
    </div>
    <div class="board-toolbar">
        <div class="board-filters"><a class="board-filter active" href="#masuk">Semua
                <b>{{ $pesananBaru->count() + $pesananDiproses->count() + $pesananSiap->count() }}</b></a><a
                class="board-filter" href="#masuk">Masuk <b>{{ $pesananBaru->count() }}</b></a><a class="board-filter"
                href="#diproses">Diproses <b>{{ $pesananDiproses->count() }}</b></a><a class="board-filter"
                href="#siap-diantar">Siap diantar <b>{{ $pesananSiap->count() }}</b></a></div><a
            href="{{ route('kasir.dashboard') }}" class="button button-light">Ringkasan dashboard</a>
    </div>

    <section class="panel recent-panel" style="margin-bottom:24px">
        <div class="panel-heading">
            <div><h2>Status meja</h2><p>Status diperbarui otomatis setiap 5 detik.</p></div>
            <span class="count-pill"><i class="queue-live-dot"></i> Langsung</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Meja</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody id="cashier-table-status">
                    @forelse($meja as $table)
                        <tr data-table-id="{{ $table->id_meja }}">
                            <td><b>{{ $table->nomor_meja }}</b></td>
                            <td><span class="queue-status {{ $table->status_meja === 'kosong' ? 'done' : '' }}">{{ ucfirst($table->status_meja) }}</span></td>
                            <td>@if($table->status_meja === 'terisi')<button class="button button-light clear-table" type="button" data-url="{{ route('kasir.meja.kosongkan', $table) }}">Kosongkan meja</button>@else<span>Siap digunakan</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Belum ada meja.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <small id="table-action-message" role="status" aria-live="polite"></small>
    </section>

    <div class="order-board">
        <section class="board-column" id="masuk">
            <header class="board-column-head"><span class="board-dot incoming"></span>
                <div>
                    <h2>Pesanan Masuk</h2><small>Perlu verifikasi kasir</small>
                </div><b class="board-total">{{ $pesananBaru->count() }}</b>
            </header>
            <div class="board-cards">
                @forelse($pesananBaru as $order)
                    <article class="board-card">
                        <div class="board-card-top">
                            <b>#{{ $order->no_pesanan }}</b><time>{{ $order->waktu_pesan?->format('H:i') }}</time></div>
                        <div class="board-card-meta"><span>MEJA
                                {{ $order->meja->nomor_meja }}</span><span>{{ $order->nama_pelanggan ?: 'Pelanggan' }}</span>
                        </div>
                        <div class="board-card-items">@foreach($order->detail->take(3) as $detail)
                            <div><span>{{ $detail->jumlah }}× {{ $detail->menu->nama_menu }}</span><b>Rp
                        {{ number_format($detail->subtotal, 0, ',', '.') }}</b></div>@endforeach
                            @if($order->detail->count() > 3)<small>+ {{ $order->detail->count() - 3 }} item
                            lainnya</small>@endif
                        </div>
                        <div class="board-card-total"><span>Total</span><b>Rp
                                {{ number_format($order->total_harga, 0, ',', '.') }}</b></div>
                        <div class="board-card-payment">
                            <span>{{ $order->pembayaran?->metode_pembayaran ? strtoupper(str_replace('_', ' ', $order->pembayaran->metode_pembayaran)) : 'BELUM DIBAYAR' }}</span><b>{{ ucfirst($order->pembayaran?->status_pembayaran ?? 'pending') }}</b>
                        </div>
                        <div class="board-card-actions"><a
                                href="{{ route('kasir.pesanan.show', $order) }}">{{ $order->pembayaran?->metode_pembayaran === 'tunai' ? 'Input kembalian' : 'Detail' }}</a>@if($order->pembayaran?->metode_pembayaran !== 'tunai')
                                    <form method="POST" action="{{ route('kasir.pesanan.verifikasi', $order) }}">@csrf<input
                                            type="hidden" name="valid" value="1"><button type="submit">Verifikasi &amp; proses</button>
                                </form>@endif
                        </div>
                </article>@empty<div class="board-empty">Belum ada pesanan masuk.</div>@endforelse
            </div>
        </section>

        <section class="board-column" id="diproses">
            <header class="board-column-head"><span class="board-dot cooking"></span>
                <div>
                    <h2>Diproses</h2><small>Sedang disiapkan dapur</small>
                </div><b class="board-total">{{ $pesananDiproses->count() }}</b>
            </header>
            <div class="board-cards">
                @forelse($pesananDiproses as $order)
                    <article class="board-card">
                        <div class="board-card-top">
                            <b>#{{ $order->no_pesanan }}</b><time>{{ $order->waktu_pesan?->format('H:i') }}</time></div>
                        <div class="board-card-meta"><span>MEJA
                                {{ $order->meja->nomor_meja }}</span><span>{{ $order->nama_pelanggan ?: 'Pelanggan' }}</span>
                        </div>
                        <div class="board-card-items">@foreach($order->detail->take(3) as $detail)
                            <div><span>{{ $detail->jumlah }}× {{ $detail->menu->nama_menu }}</span><b>Rp
                        {{ number_format($detail->subtotal, 0, ',', '.') }}</b></div>@endforeach
                            @if($order->detail->count() > 3)<small>+ {{ $order->detail->count() - 3 }} item
                            lainnya</small>@endif
                        </div>
                        <div class="board-card-total"><span>Total pesanan</span><b>Rp
                                {{ number_format($order->total_harga, 0, ',', '.') }}</b></div>
                        <div class="board-card-actions"><a href="{{ route('kasir.pesanan.show', $order) }}">Detail</a>
                            <form method="POST" action="{{ route('kasir.pesanan.status', $order) }}">@csrf<input type="hidden"
                                    name="status" value="siap_diantar"><button type="submit">Tandai siap diantar ✓</button>
                            </form>
                        </div>
                </article>@empty<div class="board-empty">Belum ada pesanan diproses.</div>@endforelse
            </div>
        </section>

        <section class="board-column" id="siap-diantar">
            <header class="board-column-head"><span class="board-dot ready"></span>
                <div>
                    <h2>Siap Diantar</h2><small>Antarkan ke meja pelanggan</small>
                </div><b class="board-total">{{ $pesananSiap->count() }}</b>
            </header>
            <div class="board-cards">
                @forelse($pesananSiap as $order)
                    <article class="board-card">
                        <div class="board-card-top">
                            <b>#{{ $order->no_pesanan }}</b><time>{{ $order->waktu_pesan?->format('H:i') }}</time></div>
                        <div class="board-card-meta"><span>MEJA
                                {{ $order->meja->nomor_meja }}</span><span>{{ $order->nama_pelanggan ?: 'Pelanggan' }}</span>
                        </div>
                        <div class="board-card-items">@foreach($order->detail->take(3) as $detail)
                            <div><span>{{ $detail->jumlah }}× {{ $detail->menu->nama_menu }}</span><b>Rp
                        {{ number_format($detail->subtotal, 0, ',', '.') }}</b></div>@endforeach
                            @if($order->detail->count() > 3)<small>+ {{ $order->detail->count() - 3 }} item
                            lainnya</small>@endif
                        </div>
                        <div class="board-card-total"><span>Total pesanan</span><b>Rp
                                {{ number_format($order->total_harga, 0, ',', '.') }}</b></div>
                        <div class="board-card-actions"><a href="{{ route('kasir.pesanan.show', $order) }}">Detail</a>
                            <form method="POST" action="{{ route('kasir.pesanan.status', $order) }}">@csrf<input type="hidden"
                                    name="status" value="selesai"><button class="finish-button" type="submit">Selesaikan
                                    ✓</button></form>
                        </div>
                </article>@empty<div class="board-empty">Belum ada pesanan siap diantar.</div>@endforelse
            </div>
        </section>
    </div>
    <div class="board-note"><span class="online-dot"></span> Status pesanan menggunakan data terbaru dari transaksi
        pelanggan <a href="{{ route('kasir.dashboard') }}">Kembali ke dashboard →</a></div>
    <script>
        (() => {
            const body = document.querySelector('#cashier-table-status');
            const message = document.querySelector('#table-action-message');
            const statusUrl = @json(route('kasir.meja.status'));
            const clearUrl = @json(url('/kasir/meja'));
            const csrf = @json(csrf_token());
            const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));
            async function refreshTables() {
                try {
                    const response = await fetch(statusUrl, {headers: {'Accept': 'application/json'}});
                    if (!response.ok) return;
                    const tables = await response.json();
                    body.innerHTML = tables.length ? tables.map(table => `<tr data-table-id="${table.id_meja}"><td><b>${escapeHtml(table.nomor_meja)}</b></td><td><span class="queue-status ${table.status_meja === 'kosong' ? 'done' : ''}">${escapeHtml(table.status_meja.charAt(0).toUpperCase() + table.status_meja.slice(1))}</span></td><td>${table.status_meja === 'terisi' ? `<button class="button button-light clear-table" type="button" data-url="${clearUrl}/${table.id_meja}/kosongkan">Kosongkan meja</button>` : 'Siap digunakan'}</td></tr>`).join('') : '<tr><td colspan="3">Belum ada meja.</td></tr>';
                } catch (_) {}
            }
            body.addEventListener('click', async event => {
                const button = event.target.closest('.clear-table');
                if (!button) return;
                button.disabled = true;
                try {
                    const response = await fetch(button.dataset.url, {method: 'PATCH', headers: {'Accept':'application/json', 'X-CSRF-TOKEN':csrf}});
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Status meja gagal diperbarui.');
                    message.textContent = result.message;
                    await refreshTables();
                } catch (error) {
                    message.textContent = error.message;
                    button.disabled = false;
                }
            });
            window.setInterval(refreshTables, 5000);
        })();
    </script>
@endsection
