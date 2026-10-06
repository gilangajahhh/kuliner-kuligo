@extends('layouts.cashier')
@section('title', 'Detail Pesanan')
@section('content')
    <div class="breadcrumb"><a href="{{ route('kasir.dashboard') }}">PESANAN</a> / <b>#{{ $pesanan->no_pesanan }}</b></div>
    <div class="page-heading">
        <div>
            <h1>Detail Pesanan</h1>
            <p>Meja {{ $pesanan->meja->nomor_meja }} · {{ $pesanan->waktu_pesan?->format('d M Y, H:i') }}</p>
        </div><a class="button button-light" href="{{ route('kasir.dashboard') }}">Kembali</a>
    </div>
    <section class="panel recent-panel">
        <div class="panel-heading">
            <div>
                <h2>Item pesanan</h2>
                <p>{{ $pesanan->nama_pelanggan ?: 'Pelanggan' }} · Status
                    {{ ucfirst(str_replace('_', ' ', $pesanan->status_pesanan)) }}</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th>Varian</th>
                        <th>Jumlah</th>
                        <th>Harga satuan</th>
                        <th>Subtotal</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>@foreach($pesanan->detail as $item)
                    <tr>
                        <td>{{ $item->menu->nama_menu }}</td>
                        <td>{{ $item->varian->nama_varian ?? '—' }}</td>
                        <td>{{ $item->jumlah }}</td>
                        <td>Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="price">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        <td>{{ $item->catatan ?: '—' }}</td>
                </tr>@endforeach<tr>
                        <td colspan="4" class="align-right"><b>Total pesanan</b></td>
                        <td class="price">Rp {{ number_format($pesanan->total_harga, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
    <section class="summary-grid cashier-actions">
        <article class="summary-card">
            <div class="summary-label">PEMBAYARAN</div><strong
                class="summary-value">{{ $pesanan->pembayaran ? strtoupper(str_replace('_', ' ', $pesanan->pembayaran->metode_pembayaran)) : 'Belum ada' }}</strong><small>{{ ucfirst($pesanan->pembayaran->status_pembayaran ?? 'pending') }}
                · Rp
                {{ number_format($pesanan->pembayaran->jumlah_bayar ?? 0, 0, ',', '.') }}</small>@if($pesanan->status_pesanan === 'baru')
                    <form method="POST" action="{{ route('kasir.pesanan.verifikasi', $pesanan) }}" class="action-form">@csrf<input
                            type="hidden" name="valid" value="1"><button class="button button-dark">Verifikasi &amp; proses</button>
                </form>@endif
        </article>
        <article class="summary-card">
            <div class="summary-label">PERBARUI STATUS</div><strong
                class="summary-value">{{ ucfirst(str_replace('_', ' ', $pesanan->status_pesanan)) }}</strong>
            <form method="POST" action="{{ route('kasir.pesanan.status', $pesanan) }}" class="action-form">@csrf<select
                    name="status" required>
                    <option value="">Pilih status berikutnya</option>
                    <option value="siap_diantar">Siap diantar</option>
                    <option value="selesai">Selesai</option>
                </select><button class="button button-light">Simpan status</button></form>
        </article>
    </section>
    <section class="panel history-panel">
        <div class="panel-heading">
            <div>
                <h2>Riwayat status</h2>
                <p>Catatan perubahan status pesanan</p>
            </div>
        </div>
        <div class="history-list">@forelse($pesanan->logStatus as $log)
            <div><span
                    class="online-dot"></span><b>{{ ucfirst(str_replace('_', ' ', $log->status_baru)) }}</b><small>{{ $log->waktu_update?->format('d M Y, H:i') }}
        · {{ $log->user->nama ?? 'Sistem' }}</small></div>@empty<p class="history-empty">Belum ada riwayat
                    status.</p>@endforelse
        </div>
</section>@endsection