@extends('layouts.cashier')
@section('title', 'Manajemen Meja')
@section('content')
<div class="breadcrumb">KULIGO POS / <b>MANAJEMEN MEJA</b></div>
<div class="page-heading cashier-heading"><div><span class="cashier-kicker">AREA PELANGGAN</span><h1>Manajemen Meja</h1><p>Pantau ketersediaan meja dan pesanan aktif.</p></div><button class="button button-dark" type="button" onclick="document.getElementById('add-table-dialog').showModal()">＋ Tambah Meja Baru</button></div>
@php
    $aktif = $meja->filter(fn($m) => $m->pesanan->isNotEmpty());
    $terisi = $aktif->count();
    $kosong = $meja->count() - $terisi;
    $reservasi = 0; // Skema saat ini belum memiliki status reservasi.
@endphp
<section class="table-summary"><article><small>Total Meja</small><b>{{ $meja->count() }}</b><span>semua area</span></article><article class="occupied"><small>Meja Terisi</small><b>{{ $terisi }}</b><span>pesanan aktif</span></article><article class="available"><small>Meja Kosong</small><b>{{ $kosong }}</b><span>siap digunakan</span></article><article class="reserved"><small>Meja Reservasi</small><b>{{ $reservasi }}</b><span>fitur reservasi belum aktif</span></article></section>
<section class="panel table-management-panel"><div class="table-zone-tabs" id="table-zone-tabs"><button class="active" data-zone="semua">Semua <b>{{ $meja->count() }}</b></button><button data-zone="lantai-1">Lantai 1</button><button data-zone="lantai-2">Lantai 2</button><button data-zone="outdoor">Outdoor</button><span class="zone-note">Area mengikuti nama meja (contoh: “Outdoor 01”)</span></div>
<div class="table-card-grid" id="table-card-grid">
@forelse($meja as $item)
    @php
        $order = $item->pesanan->first();
        $lastOrder = $pesananTerakhir->get($item->id_meja);
        $hasCompletedOrder = ! $order && $lastOrder?->status_pesanan === 'selesai';
        $zone = preg_match('/outdoor|teras|luar/i', $item->nomor_meja) ? 'outdoor' : (preg_match('/lantai\s*2|\bl2\b/i', $item->nomor_meja) ? 'lantai-2' : 'lantai-1');
        $availableTables = $meja->where('status_meja', 'kosong')->where('id_meja', '!=', $item->id_meja)->map(fn($table) => ['id' => $table->id_meja, 'name' => $table->nomor_meja])->values();
    @endphp
    <article class="managed-table-card {{ $order ? 'is-occupied' : 'is-available' }}" data-zone="{{ $zone }}" data-table="{{ $item->id_meja }}">
        <div class="managed-card-head"><div><small>MEJA</small><h2>{{ $item->nomor_meja }}</h2></div><span class="table-state {{ $order ? 'state-occupied' : 'state-available' }}"><i></i>{{ $order ? 'Terisi' : 'Kosong' }}</span></div>
        @if($order)
            <div class="managed-order-info"><div><small>Pesanan aktif</small><b>#{{ $order->no_pesanan }}</b></div><div><small>Mulai</small><b>{{ $order->waktu_pesan?->format('H:i') }} · {{ $order->waktu_pesan?->diffForHumans(null, true) }}</b></div><div><small>Pelanggan</small><b>{{ $order->nama_pelanggan ?: 'Pelanggan' }}</b></div><div><small>Jumlah tamu</small><b>Belum dicatat</b></div><div><small>Item</small><b>{{ $order->detail_count }} item</b></div><div class="managed-bill"><small>Total berjalan</small><b>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</b></div></div>
            <div class="managed-card-actions"><a class="button button-light" href="{{ route('kasir.pesanan.show', $order) }}">Lihat Pesanan</a><button class="button button-light move-table" type="button" data-url="{{ route('kasir.meja.pindah', $item) }}" data-targets='@json($availableTables)'>Pindah Meja</button></div>
        @else
            <div class="managed-empty"><span>✓</span><b>Meja siap digunakan</b><small>Kode QR: {{ $item->kode_qr }}</small></div>
            <div class="managed-card-actions">
                @if($hasCompletedOrder)
                    <a class="button button-dark" href="{{ route('kasir.pesanan.struk', $lastOrder) }}?cetak=1" target="_blank" rel="noopener">Cetak Struk Kasir</a>
                    <a class="button button-light" href="{{ route('kasir.pesanan.struk', $lastOrder) }}?jenis=dapur" target="_blank" rel="noopener">Cetak Struk Dapur</a>
                @endif
            </div>
        @endif
    </article>
@empty <div class="board-empty">Belum ada meja. Tambahkan meja baru untuk mulai.</div> @endforelse
</div></section>
<dialog class="table-edit-dialog" id="add-table-dialog"><div class="table-edit-heading"><div><h2>Tambah meja baru</h2><p>Meja baru langsung mendapat kode QR menu.</p></div><button class="dialog-close" type="button" onclick="this.closest('dialog').close()">&times;</button></div><form class="table-edit-form" method="POST" action="{{ route('kasir.meja.store') }}">@csrf<label class="field">Nama / nomor meja<input class="crud-row-input" name="nomor_meja" placeholder="Contoh: Meja 01 atau Outdoor 01" required maxlength="50"></label><div class="table-edit-actions"><button class="button button-light" type="button" onclick="this.closest('dialog').close()">Batal</button><button class="button button-dark">Tambah meja</button></div></form></dialog>
<dialog class="table-edit-dialog" id="move-table-dialog"><div class="table-edit-heading"><div><h2>Pindah pesanan</h2><p>Pilih meja kosong sebagai tujuan.</p></div><button class="dialog-close" type="button" onclick="this.closest('dialog').close()">&times;</button></div><form class="table-edit-form" id="move-table-form"><label class="field">Meja tujuan<select class="crud-row-input" name="meja_tujuan" required></select></label><div id="move-table-error" role="alert"></div><div class="table-edit-actions"><button class="button button-light" type="button" onclick="this.closest('dialog').close()">Batal</button><button class="button button-dark">Pindahkan pesanan</button></div></form></dialog>
<script>
(() => {
 const tabs=document.querySelector('#table-zone-tabs'), cards=[...document.querySelectorAll('.managed-table-card')], moveDialog=document.querySelector('#move-table-dialog'), form=document.querySelector('#move-table-form'); let moveUrl='';
 tabs.addEventListener('click',e=>{const b=e.target.closest('[data-zone]');if(!b)return;tabs.querySelectorAll('button').forEach(x=>x.classList.toggle('active',x===b));cards.forEach(c=>c.hidden=b.dataset.zone!=='semua'&&c.dataset.zone!==b.dataset.zone)});
 document.querySelectorAll('.move-table').forEach(button=>button.addEventListener('click',()=>{moveUrl=button.dataset.url;const targets=JSON.parse(button.dataset.targets);form.elements.meja_tujuan.innerHTML=targets.map(t=>`<option value="${t.id}">${t.name}</option>`).join('');document.querySelector('#move-table-error').textContent='';moveDialog.showModal()}));
 form.addEventListener('submit',async e=>{e.preventDefault();const button=form.querySelector('button[type="submit"],button:not([type])');button.disabled=true;try{const data=new FormData(form);const response=await fetch(moveUrl,{method:'POST',headers:{'X-CSRF-TOKEN':@json(csrf_token()),'Accept':'application/json'},body:data});const result=await response.json();if(!response.ok)throw new Error(result.message||'Gagal memindahkan meja.');location.reload()}catch(err){document.querySelector('#move-table-error').textContent=err.message}finally{button.disabled=false}});
})();
</script>
@endsection
