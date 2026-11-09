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
                            <form method="POST" action="{{ route('kasir.pesanan.status', $order) }}" class="finish-order-form" data-order="{{ $order->no_pesanan }}" data-customer="{{ $order->nama_pelanggan ?: 'Meja ' . $order->meja->nomor_meja }}" data-total="Rp {{ number_format($order->total_harga, 0, ',', '.') }}" data-method="{{ strtoupper(str_replace('_', ' ', $order->pembayaran?->metode_pembayaran ?? 'Belum dibayar')) }}" data-receipt="{{ route('kasir.pesanan.struk', $order) }}">@csrf<input type="hidden"
                                    name="status" value="selesai"><button class="finish-button" type="submit">Selesaikan
                                    ✓</button></form>
                        </div>
                </article>@empty<div class="board-empty">Belum ada pesanan siap diantar.</div>@endforelse
            </div>
        </section>
    </div>
    <section class="panel recent-panel completed-orders-panel">
        <div class="panel-heading"><div><h2>Pesanan Selesai</h2><p>Cetak struk langsung tanpa membuka detail pesanan.</p></div><span class="count-pill">{{ $pesananSelesai->count() }} terbaru</span></div>
        <div class="table-wrap"><table><thead><tr><th>No. Pesanan</th><th>Meja / Pelanggan</th><th>Selesai</th><th>Total</th><th>Pembayaran</th><th>Aksi</th></tr></thead><tbody>
            @forelse($pesananSelesai as $order)
                <tr><td><b>#{{ $order->no_pesanan }}</b></td><td>{{ $order->meja->nomor_meja }} · {{ $order->nama_pelanggan ?: 'Pelanggan' }}</td><td>{{ $order->updated_at?->format('d/m/Y H:i') }}</td><td><b>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</b></td><td>{{ strtoupper(str_replace('_', ' ', $order->pembayaran?->metode_pembayaran ?? '—')) }}</td><td><a class="button button-dark" href="{{ route('kasir.pesanan.struk', $order) }}?cetak=1" target="_blank" rel="noopener">Cetak Struk</a></td></tr>
            @empty <tr><td colspan="6">Belum ada pesanan selesai.</td></tr> @endforelse
        </tbody></table></div>
    </section>
    <div class="board-note"><span class="online-dot"></span> Status pesanan menggunakan data terbaru dari transaksi
        pelanggan <a href="{{ route('kasir.dashboard') }}">Kembali ke dashboard →</a></div>
    <dialog class="table-edit-dialog receipt-dialog" id="finish-receipt-dialog"><div class="table-edit-heading"><div><span class="cashier-kicker">PEMBAYARAN SELESAI</span><h2>Pesanan Selesai</h2><p>Pesanan sudah ditandai selesai. Pilih cara memberikan struk.</p></div><button class="dialog-close" type="button" id="close-receipt-dialog">&times;</button></div><div class="receipt-dialog-body"><div class="receipt-summary"><div><small>No. pesanan</small><b id="receipt-order"></b></div><div><small>Pelanggan / meja</small><b id="receipt-customer"></b></div><div><small>Metode pembayaran</small><b id="receipt-method"></b></div><div class="receipt-grand"><small>Total pembayaran</small><b id="receipt-total"></b></div></div><div class="printer-status"><span><i></i> Kasir: Siap untuk cetak browser</span><span><i></i> Dapur: tiket dapur manual</span></div><div class="receipt-actions"><button class="button button-dark" id="print-cashier">Cetak Struk Kasir</button><button class="button button-light" id="print-kitchen">Cetak Struk Dapur</button></div><div class="digital-receipt"><label class="field">Tautan e-receipt<input id="receipt-link" readonly></label><img id="receipt-qr" alt="QR e-receipt"><div class="digital-share"><input id="receipt-contact" placeholder="Nomor WhatsApp atau email"><button class="button button-light" id="share-receipt" type="button">Bagikan</button></div></div><div id="receipt-error" role="alert"></div><div class="table-edit-actions receipt-close-actions"><button class="button button-light" id="finish-without-print" type="button">Selesai (Tanpa Struk)</button><a class="button button-dark" href="{{ route('kasir.pesanan') }}">Tutup &amp; Pesanan Baru</a></div></div></dialog>
    <script>
        (() => {
            const dialog=document.querySelector('#finish-receipt-dialog');let receiptUrl='';
            document.querySelectorAll('.finish-order-form').forEach(form=>form.addEventListener('submit',async event=>{event.preventDefault();const submit=form.querySelector('button');submit.disabled=true;const data=new FormData(form);data.set('status','selesai');try{const response=await fetch(form.action,{method:'POST',headers:{'X-CSRF-TOKEN':form.querySelector('[name=_token]').value,'Accept':'application/json'},body:data});const result=await response.json();if(!response.ok)throw new Error(result.message||'Pesanan gagal diselesaikan.');receiptUrl=result.receipt_url||form.dataset.receipt;document.querySelector('#receipt-order').textContent='#'+form.dataset.order;document.querySelector('#receipt-customer').textContent=form.dataset.customer;document.querySelector('#receipt-total').textContent=form.dataset.total;document.querySelector('#receipt-method').textContent=form.dataset.method;document.querySelector('#receipt-link').value=receiptUrl;document.querySelector('#receipt-qr').src='https://api.qrserver.com/v1/create-qr-code/?size=120x120&data='+encodeURIComponent(receiptUrl);dialog.showModal()}catch(error){alert(error.message);submit.disabled=false}}));
            const print=(type)=>{if(!receiptUrl)return;const url=new URL(receiptUrl,location.href);if(type==='cashier')url.searchParams.set('cetak','1');else url.searchParams.set('jenis','dapur');const popup=window.open(url,'_blank','noopener');if(!popup)document.querySelector('#receipt-error').innerHTML='Jendela cetak terblokir. <a target="_blank" rel="noopener" href="'+url.toString()+'">Buka struk / Simpan PDF</a>.'};
            document.querySelector('#print-cashier').onclick=()=>print('cashier');document.querySelector('#print-kitchen').onclick=()=>print('kitchen');
            document.querySelector('#share-receipt').onclick=()=>{const contact=document.querySelector('#receipt-contact').value.trim();const text=`E-receipt pesanan: ${receiptUrl}`;if(contact.includes('@')){location.href=`mailto:${encodeURIComponent(contact)}?subject=${encodeURIComponent('E-receipt Kuligo')}&body=${encodeURIComponent(text)}`}else if(contact){const phone=contact.replace(/\D/g,'');window.open(`https://wa.me/${phone}?text=${encodeURIComponent(text)}`,'_blank','noopener')}else{navigator.clipboard?.writeText(receiptUrl);document.querySelector('#receipt-error').textContent='Tautan struk disalin.'}};
            dialog.addEventListener('close',()=>location.reload());
            document.querySelector('#close-receipt-dialog').onclick=()=>dialog.close();document.querySelector('#finish-without-print').onclick=()=>dialog.close();
        })();
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
