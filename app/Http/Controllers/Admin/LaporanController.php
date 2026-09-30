<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $dari = $request->get('dari', today()->startOfMonth()->toDateString());
        $sampai = $request->get('sampai', today()->toDateString());

        $pesanan = Pesanan::whereBetween('waktu_pesan', [$dari, $sampai . ' 23:59:59'])
            ->where('status_pesanan', '!=', 'batal')
            ->orderBy('waktu_pesan')
            ->get();

        $totalPenjualan = $pesanan->sum('total_harga');
        $totalTransaksi = $pesanan->count();

        return view('admin.laporan.index', compact('pesanan', 'totalPenjualan', 'totalTransaksi', 'dari', 'sampai'));
    }
}
