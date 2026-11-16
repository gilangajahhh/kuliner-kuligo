@extends('layouts.cashier')
@section('title', 'Transaksi')
@section('content')
    <div class="breadcrumb">KULIGO POS / <b>TRANSAKSI</b></div>
    <div class="page-heading cashier-heading">
        <div>
            <span class="cashier-kicker">CATATAN PEMBAYARAN</span>
            <h1>Riwayat pembayaran</h1>
            <p>Cari transaksi dan periksa pembayaran atau kembalian.</p>
        </div>
        <a href="{{ route('kasir.pesanan') }}" class="button button-light">Lihat pesanan</a>
    </div>

    <section class="panel recent-panel cashier-transaction-panel">
        <div class="panel-heading">
            <div>
                <h2>Riwayat transaksi</h2>
                <p>{{ number_format($ringkasanTransaksi->jumlah, 0, ',', '.') }} transaksi · Total Rp {{ number_format($ringkasanTransaksi->total, 0, ',', '.') }}</p>
            </div>
        </div>
        <form method="GET" action="{{ route('kasir.transaksi') }}" class="cashier-transaction-filters">
            <label>Metode pembayaran
                <select name="metode">
                    <option value="semua" @selected($metode === 'semua')>Semua</option>
                    <option value="tunai" @selected($metode === 'tunai')>Tunai</option>
                    <option value="qris" @selected($metode === 'qris')>QRIS</option>
                    <option value="bank_transfer" @selected($metode === 'bank_transfer')>Transfer Bank</option>
                </select>
            </label>
            <label>Status pesanan
                <select name="status_pesanan">
                    <option value="semua" @selected($statusPesanan === 'semua')>Semua status</option>
                    <option value="baru" @selected($statusPesanan === 'baru')>Baru</option>
                    <option value="diproses" @selected($statusPesanan === 'diproses')>Diproses</option>
                    <option value="siap_diantar" @selected($statusPesanan === 'siap_diantar')>Siap diantar</option>
                    <option value="selesai" @selected($statusPesanan === 'selesai')>Selesai</option>
                    <option value="batal" @selected($statusPesanan === 'batal')>Batal</option>
                </select>
            </label>
            <button class="button button-light" type="submit">Terapkan</button>
            @if($metode !== 'semua' || $statusPesanan !== 'semua')
                <a class="button button-light" href="{{ route('kasir.transaksi') }}">Reset filter</a>
            @endif
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No. transaksi</th>
                        <th>Waktu</th>
                        <th>Meja / pelanggan</th>
                        <th>Pembayaran</th>
                        <th>Status pesanan</th>
                        <th>Total</th>
                        <th>Uang diterima</th>
                        <th>Kembalian</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksi as $order)
                        <tr>
                            <td><b>#{{ $order->no_pesanan }}</b></td>
                            <td>{{ $order->waktu_pesan?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>Meja {{ $order->meja->nomor_meja ?? '—' }}<br><small>{{ $order->nama_pelanggan ?: 'Pelanggan' }}</small></td>
                            <td><b>{{ strtoupper(str_replace('_', ' ', $order->pembayaran?->metode_pembayaran ?? '—')) }}</b><br><span class="queue-status {{ $order->pembayaran?->status_pembayaran === 'berhasil' ? 'done' : '' }}">{{ ucfirst($order->pembayaran?->status_pembayaran ?? 'pending') }}</span></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $order->status_pesanan)) }}</td>
                            <td>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                            <td>{{ $order->pembayaran?->uang_diterima !== null ? 'Rp ' . number_format($order->pembayaran->uang_diterima, 0, ',', '.') : '—' }}</td>
                            <td>{{ $order->pembayaran?->kembalian !== null ? 'Rp ' . number_format($order->pembayaran->kembalian, 0, ',', '.') : '—' }}</td>
                            <td><a class="button button-light" href="{{ route('kasir.pesanan.struk', $order) }}" target="_blank" rel="noopener">Lihat struk</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="align-center">Belum ada transaksi untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transaksi->hasPages())
            <div class="cashier-transaction-pagination">{{ $transaksi->withQueryString()->links() }}</div>
        @endif
    </section>
@endsection
