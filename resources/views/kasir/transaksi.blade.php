@extends('layouts.cashier')
@section('title', 'Transaksi')
@section('content')
    <div class="breadcrumb">KULIGO POS / <b>TRANSAKSI</b></div>
    <div class="page-heading cashier-heading">
        <div>
            <span class="cashier-kicker">RIWAYAT PEMBAYARAN</span>
            <h1>Transaksi</h1>
            <p>Lihat pembayaran, uang yang diterima, dan kembalian dari halaman Kasir.</p>
        </div>
        <a href="{{ route('kasir.pesanan') }}" class="button button-light">Lihat pesanan</a>
    </div>

    <section class="panel recent-panel cashier-transaction-panel">
        <div class="panel-heading">
            <div>
                <h2>Riwayat transaksi</h2>
                <p>{{ $transaksi->total() }} transaksi tercatat</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No. transaksi</th>
                        <th>Waktu</th>
                        <th>Meja / pelanggan</th>
                        <th>Metode</th>
                        <th>Status</th>
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
                            <td>{{ strtoupper(str_replace('_', ' ', $order->pembayaran?->metode_pembayaran ?? '—')) }}</td>
                            <td><span class="queue-status {{ $order->pembayaran?->status_pembayaran === 'berhasil' ? 'done' : '' }}">{{ ucfirst($order->pembayaran?->status_pembayaran ?? 'pending') }}</span></td>
                            <td>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                            <td>{{ $order->pembayaran?->uang_diterima !== null ? 'Rp ' . number_format($order->pembayaran->uang_diterima, 0, ',', '.') : '—' }}</td>
                            <td>{{ $order->pembayaran?->kembalian !== null ? 'Rp ' . number_format($order->pembayaran->kembalian, 0, ',', '.') : '—' }}</td>
                            <td><a class="button button-light" href="{{ route('kasir.pesanan.show', $order) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="align-center">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transaksi->hasPages())
            <div class="cashier-transaction-pagination">{{ $transaksi->links() }}</div>
        @endif
    </section>
@endsection
