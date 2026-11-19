@extends('layouts.cashier')
@section('title', 'Pesanan Kasir')
@section('content')
    <div class="breadcrumb">KULIGO POS / <b>DAFTAR PESANAN</b></div>
    <div class="page-heading cashier-heading">
        <div><span class="cashier-kicker">ANTREAN LANGSUNG</span>
            <h1>Proses Pesanan</h1>
            <p>Lihat pesanan yang perlu dibayar, disiapkan, dan diantar.</p>
        </div><span class="cashier-date"><i class="queue-live-dot"></i> {{ now()->translatedFormat('l, d M Y') }}</span>
    </div>
    <div class="board-toolbar">
        <div class="board-filters"><a class="board-filter active" href="#masuk">Semua
                <b>{{ $pesananBaru->count() + $pesananDiproses->count() + $pesananSiap->count() }}</b></a><a
                class="board-filter" href="#masuk">Masuk <b>{{ $pesananBaru->count() }}</b></a><a class="board-filter"
                href="#diproses">Diproses <b>{{ $pesananDiproses->count() }}</b></a><a class="board-filter"
                href="#siap-diantar">Siap diantar <b>{{ $pesananSiap->count() }}</b></a></div><a
            href="{{ route('kasir.dashboard') }}" class="button button-light">Ringkasan</a>
    </div>

    <section class="cashier-steps" aria-label="Langkah memproses pesanan">
        <article><span>1</span><div><b>Pesanan masuk</b><small>Kasir cek pesanan dan pembayaran.</small></div></article>
        <article><span>2</span><div><b>Diproses</b><small>Sampaikan pesanan ke dapur.</small></div></article>
        <article><span>3</span><div><b>Siap diantar</b><small>Antar ke meja, lalu tandai selesai.</small></div></article>
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
                        <div class="board-card-items">@foreach($order->detail as $detail)
                            <div><span>{{ $detail->jumlah }}× {{ $detail->menu->nama_menu }}</span><b>Rp
                        {{ number_format($detail->subtotal, 0, ',', '.') }}</b></div>@endforeach
                        </div>
                        <div class="board-card-total"><span>Total</span><b>Rp
                                {{ number_format($order->total_harga, 0, ',', '.') }}</b></div>
                        <div class="board-card-payment">
                            <span>{{ $order->pembayaran?->metode_pembayaran ? strtoupper(str_replace('_', ' ', $order->pembayaran->metode_pembayaran)) : 'BELUM DIBAYAR' }}</span><b>{{ ucfirst($order->pembayaran?->status_pembayaran ?? 'pending') }}</b>
                        </div>
                        <div class="board-card-actions">
                            @if($order->pembayaran?->metode_pembayaran === 'tunai')
                                <button type="button" class="open-cash-modal" data-modal="cash-modal-{{ $order->id_pesanan }}">Periksa &amp; input uang</button>
                            @else
                                <form method="POST" action="{{ route('kasir.pesanan.verifikasi', $order) }}">@csrf<input
                                            type="hidden" name="valid" value="1"><button type="submit">Pembayaran diterima · Proses</button>
                                    </form>
                            @endif
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
                        <div class="board-card-items">@foreach($order->detail as $detail)
                            <div><span>{{ $detail->jumlah }}× {{ $detail->menu->nama_menu }}</span><b>Rp
                        {{ number_format($detail->subtotal, 0, ',', '.') }}</b></div>@endforeach
                        </div>
                        <div class="board-card-total"><span>Total pesanan</span><b>Rp
                                {{ number_format($order->total_harga, 0, ',', '.') }}</b></div>
                        <div class="board-card-actions">
                            <form method="POST" action="{{ route('kasir.pesanan.status', $order) }}">@csrf<input type="hidden"
                                    name="status" value="siap_diantar"><button type="submit">Pesanan siap diantar ✓</button>
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
                        <div class="board-card-items">@foreach($order->detail as $detail)
                            <div><span>{{ $detail->jumlah }}× {{ $detail->menu->nama_menu }}</span><b>Rp
                        {{ number_format($detail->subtotal, 0, ',', '.') }}</b></div>@endforeach
                        </div>
                        <div class="board-card-total"><span>Total pesanan</span><b>Rp
                                {{ number_format($order->total_harga, 0, ',', '.') }}</b></div>
                        <div class="board-card-actions">
                            <form method="POST" action="{{ route('kasir.pesanan.status', $order) }}" class="finish-order-form" data-order="{{ $order->no_pesanan }}" data-customer="{{ $order->nama_pelanggan ?: 'Meja ' . $order->meja->nomor_meja }}" data-total="Rp {{ number_format($order->total_harga, 0, ',', '.') }}" data-method="{{ strtoupper(str_replace('_', ' ', $order->pembayaran?->metode_pembayaran ?? 'Belum dibayar')) }}" data-receipt="{{ route('kasir.pesanan.struk', $order) }}">@csrf<input type="hidden"
                                    name="status" value="selesai"><button class="finish-button" type="submit">Sudah diantar · Selesaikan
                                    ✓</button></form>
                        </div>
                </article>@empty<div class="board-empty">Belum ada pesanan siap diantar.</div>@endforelse
            </div>
        </section>
    </div>
    @foreach($pesananBaru as $order)
        @if($order->pembayaran?->metode_pembayaran === 'tunai')
            <dialog class="cash-process-dialog" id="cash-modal-{{ $order->id_pesanan }}" aria-labelledby="cash-title-{{ $order->id_pesanan }}">
                <form method="POST" action="{{ route('kasir.pesanan.verifikasi', $order) }}" class="cash-process-form">
                    @csrf
                    <input type="hidden" name="valid" value="1">
                    <header class="cash-process-heading">
                        <div><span class="cashier-kicker">PESANAN TUNAI</span><h2 id="cash-title-{{ $order->id_pesanan }}">Periksa &amp; input uang</h2><p>#{{ $order->no_pesanan }} · Meja {{ $order->meja->nomor_meja }}</p></div>
                        <button class="cash-modal-close" type="button" aria-label="Tutup">&times;</button>
                    </header>
                    <div class="cash-process-items">
                        @foreach($order->detail as $detail)
                            <div><span>{{ $detail->jumlah }} × {{ $detail->menu->nama_menu }}</span><b>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</b></div>
                        @endforeach
                    </div>
                    <div class="cash-process-total"><span>Total tagihan</span><b>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</b></div>
                    <label class="cash-modal-field">Uang diterima dari pelanggan
                        <span><b>Rp</b><input type="number" name="uang_diterima" min="{{ $order->total_harga }}" step="1" required data-total="{{ $order->total_harga }}" placeholder="Contoh: 50000"></span><small class="cash-modal-error" aria-live="polite"></small>
                    </label>
                    <div class="cash-modal-change"><span>Kembalian</span><b data-change>Masukkan uang diterima</b></div>
                    <div class="cash-modal-actions"><button class="button button-light cash-modal-cancel" type="button">Batal</button><button class="button button-dark" type="submit">Uang sesuai · Proses pesanan</button></div>
                </form>
            </dialog>
        @endif
    @endforeach
    <details class="cashier-secondary">
      <summary><span>Status meja</span><small>{{ $meja->count() }} meja · lihat atau kosongkan meja</small></summary>
      <section class="panel recent-panel" style="margin:12px 0 0">
        <div class="panel-heading">
            <div><h2>Status meja</h2><p>Daftar ini diperbarui otomatis setiap 5 detik.</p></div>
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
    </details>
    <details class="cashier-secondary completed-orders-panel">
      <summary><span>Pesanan selesai</span><small>{{ $pesananSelesai->count() }} pesanan terbaru · struk opsional</small></summary>
      <section class="panel recent-panel" style="margin:12px 0 0">
        <div class="panel-heading"><div><h2>Pesanan selesai</h2><p>Struk bisa dicetak jika diperlukan.</p></div><span class="count-pill">{{ $pesananSelesai->count() }} terbaru</span></div>
        <div class="table-wrap"><table><thead><tr><th>No. Pesanan</th><th>Meja / Pelanggan</th><th>Selesai</th><th>Total</th><th>Pembayaran</th><th>Aksi</th></tr></thead><tbody>
            @forelse($pesananSelesai as $order)
                <tr><td><b>#{{ $order->no_pesanan }}</b></td><td>{{ $order->meja->nomor_meja }} · {{ $order->nama_pelanggan ?: 'Pelanggan' }}</td><td>{{ $order->updated_at?->format('d/m/Y H:i') }}</td><td><b>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</b></td><td>{{ strtoupper(str_replace('_', ' ', $order->pembayaran?->metode_pembayaran ?? '—')) }}</td><td><a class="button button-dark" href="{{ route('kasir.pesanan.struk', $order) }}?cetak=1" target="_blank" rel="noopener">Cetak Struk</a></td></tr>
            @empty <tr><td colspan="6">Belum ada pesanan selesai.</td></tr> @endforelse
        </tbody></table></div>
      </section>
    </details>
    <div class="board-note"><span class="online-dot"></span> Status pesanan menggunakan data terbaru dari transaksi
        pelanggan <a href="{{ route('kasir.dashboard') }}">Kembali ke dashboard →</a></div>
    <dialog class="table-edit-dialog receipt-dialog" id="finish-receipt-dialog">
        <div class="table-edit-heading"><div><span class="cashier-kicker">PESANAN SELESAI</span><h2>Pesanan selesai</h2><p>Struk hanya perlu dibuat jika pelanggan memintanya.</p></div><button class="dialog-close" type="button" id="close-receipt-dialog">&times;</button></div>
        <div class="receipt-dialog-body">
            <div class="receipt-summary"><div><small>No. pesanan</small><b id="receipt-order"></b></div><div><small>Pelanggan / meja</small><b id="receipt-customer"></b></div><div><small>Metode pembayaran</small><b id="receipt-method"></b></div><div class="receipt-grand"><small>Total pembayaran</small><b id="receipt-total"></b></div></div>
            <details class="receipt-extra"><summary>Cetak struk (opsional)</summary><div class="receipt-actions"><button class="button button-dark" id="print-cashier">Struk kasir</button><button class="button button-light" id="print-kitchen">Tiket dapur</button></div></details>
            <details class="receipt-extra"><summary>Kirim tautan struk (opsional)</summary><div class="digital-receipt"><label class="field">Tautan e-receipt<input id="receipt-link" readonly></label><img id="receipt-qr" alt="QR e-receipt"><div class="digital-share"><input id="receipt-contact" placeholder="Nomor WhatsApp atau email"><button class="button button-light" id="share-receipt" type="button">Bagikan</button></div></div></details>
            <div id="receipt-error" role="alert"></div><div class="table-edit-actions receipt-close-actions"><button class="button button-dark" id="finish-without-print" type="button">Selesai</button><a class="button button-light" href="{{ route('kasir.pesanan') }}">Pesanan baru</a></div>
        </div>
    </dialog>
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
        (() => {
            document.querySelectorAll('.open-cash-modal').forEach(button => {
                const dialog = document.getElementById(button.dataset.modal);
                if (!dialog) return;
                button.addEventListener('click', () => dialog.showModal());
                dialog.querySelectorAll('.cash-modal-close, .cash-modal-cancel').forEach(close => close.addEventListener('click', () => dialog.close()));
                dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
                const input = dialog.querySelector('input[name="uang_diterima"]');
                const change = dialog.querySelector('[data-change]');
                const format = amount => 'Rp ' + new Intl.NumberFormat('id-ID', {maximumFractionDigits: 0}).format(amount);
                input.addEventListener('input', () => {
                    const value = Number(input.value || 0) - Number(input.dataset.total || 0);
                    change.textContent = input.value ? (value >= 0 ? format(value) : `Kurang ${format(Math.abs(value))}`) : 'Masukkan uang diterima';
                    const warning = dialog.querySelector('.cash-modal-error');
                    warning.textContent = input.value && value < 0 ? `Uang tunai kurang ${format(Math.abs(value))} dari total tagihan.` : '';
                });
            });
        })();
    </script>
@endsection
