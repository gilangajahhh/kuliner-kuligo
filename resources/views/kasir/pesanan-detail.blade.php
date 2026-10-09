@extends('layouts.cashier')
@section('title', 'Detail Transaksi')
@section('content')
    <div class="breadcrumb"><a href="{{ route('kasir.transaksi') }}">TRANSAKSI</a> / <b>#{{ $pesanan->no_pesanan }}</b></div>
    <div class="page-heading">
        <div>
            <h1>Detail Transaksi</h1>
            <p>Meja {{ $pesanan->meja->nomor_meja }} · {{ $pesanan->waktu_pesan?->format('d M Y, H:i') }}</p>
        </div><div class="heading-actions"><a class="button button-light" href="{{ route('kasir.pesanan.struk', $pesanan) }}?cetak=1" target="_blank" rel="noopener">▤ Cetak Struk</a><a class="button button-light" href="{{ route('kasir.transaksi') }}">Kembali ke transaksi</a></div>
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
            <div class="summary-label">PEMBAYARAN</div>
            <strong class="summary-value">{{ $pesanan->pembayaran ? strtoupper(str_replace('_', ' ', $pesanan->pembayaran->metode_pembayaran)) : 'Belum ada' }}</strong>
            <small>{{ ucfirst($pesanan->pembayaran->status_pembayaran ?? 'pending') }} · Rp {{ number_format($pesanan->pembayaran->jumlah_bayar ?? 0, 0, ',', '.') }}</small>
            @if($pesanan->pembayaran?->metode_pembayaran === 'tunai' && $pesanan->pembayaran->uang_diterima !== null)
                <small>Uang diterima Rp {{ number_format($pesanan->pembayaran->uang_diterima, 0, ',', '.') }} · Kembalian Rp {{ number_format($pesanan->pembayaran->kembalian, 0, ',', '.') }}</small>
            @endif
            @if($pesanan->status_pesanan === 'baru')
                <form method="POST" action="{{ route('kasir.pesanan.verifikasi', $pesanan) }}" class="action-form cash-verification-form" id="cash-verification-form">
                    @csrf
                    <input type="hidden" name="valid" value="1">
                    @if($pesanan->pembayaran?->metode_pembayaran === 'tunai')
                        <label class="field cash-received-field">Uang diterima (Rp)
                            <input id="cash-received" type="number" name="uang_diterima" min="{{ $pesanan->total_harga }}" step="1" required value="{{ old('uang_diterima') }}" data-total="{{ $pesanan->total_harga }}" placeholder="Masukkan uang dari pelanggan">
                        </label>
                        <div class="cash-change-preview"><span id="cash-change-label">Kembalian</span><strong id="cash-change-value">Masukkan nominal uang</strong></div>
                    @endif
                    <button class="button button-dark" type="submit">{{ $pesanan->pembayaran?->metode_pembayaran === 'tunai' ? 'Verifikasi & proses' : 'Verifikasi & proses' }}</button>
                </form>
            @endif
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
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('#cash-received');
    if (!input) return;
    const total = Number(input.dataset.total || 0);
    const label = document.querySelector('#cash-change-label');
    const value = document.querySelector('#cash-change-value');
    const button = document.querySelector('#cash-verification-form button[type="submit"]');
    const formatRupiah = amount => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount);
    const updateChange = () => {
        const received = Number(input.value || 0);
        const difference = received - total;
        if (input.value === '') {
            label.textContent = 'Kembalian';
            value.textContent = 'Masukkan nominal uang';
        } else if (difference < 0) {
            label.textContent = 'Uang kurang';
            value.textContent = `Rp ${formatRupiah(Math.abs(difference))}`;
        } else {
            label.textContent = 'Kembalian';
            value.textContent = `Rp ${formatRupiah(difference)}`;
        }
        button.disabled = received < total;
    };
    input.addEventListener('input', updateChange);
    updateChange();
});
</script>
@endsection
