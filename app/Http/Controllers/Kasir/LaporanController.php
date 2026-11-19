<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $dari = $request->get('dari', today()->startOfMonth()->toDateString());
        $sampai = $request->get('sampai', today()->toDateString());
        $metode = in_array($request->query('metode'), ['semua', 'tunai', 'qris', 'bank_transfer'], true)
            ? $request->query('metode')
            : 'semua';
        $statusPesanan = in_array($request->query('status_pesanan'), ['semua', 'baru', 'diproses', 'siap_diantar', 'selesai', 'batal'], true)
            ? $request->query('status_pesanan')
            : 'semua';

        $pesanan = Pesanan::whereBetween('waktu_pesan', [$dari, $sampai . ' 23:59:59'])
            ->whereHas('pembayaran')
            ->when(in_array($metode, ['tunai', 'qris', 'bank_transfer'], true), function ($query) use ($metode) {
                $query->whereHas('pembayaran', fn ($pembayaran) => $pembayaran->where('metode_pembayaran', $metode));
            })
            ->when(in_array($statusPesanan, ['baru', 'diproses', 'siap_diantar', 'selesai', 'batal'], true), fn ($query) => $query->where('status_pesanan', $statusPesanan))
            ->orderBy('waktu_pesan')
            ->get();

        $totalPenjualan = $pesanan->sum('total_harga');
        $totalTransaksi = $pesanan->count();

        return view('kasir.laporan.index', compact('pesanan', 'totalPenjualan', 'totalTransaksi', 'dari', 'sampai', 'metode', 'statusPesanan'));
    }
}
