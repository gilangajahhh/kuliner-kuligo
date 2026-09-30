<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Pesanan;

class DashboardController extends Controller
{
    public function index()
    {
        $ringkasan = [
            'pesanan_hari_ini' => Pesanan::whereDate('waktu_pesan', today())->count(),
            'penjualan_hari_ini' => Pesanan::whereDate('waktu_pesan', today())
                ->where('status_pesanan', '!=', 'batal')
                ->sum('total_harga'),
            'pesanan_aktif' => Pesanan::whereIn('status_pesanan', ['baru', 'diproses', 'siap_diantar'])->count(),
            'total_menu' => Menu::count(),
            'menu_tidak_tersedia' => Menu::where('status_tersedia', false)->count(),
        ];

        $pesananTerbaru = Pesanan::with('meja')
            ->latest('waktu_pesan')
            ->limit(10)
            ->get();

        $view = request()->routeIs('admin.pesanan') ? 'admin.pesanan.index' : 'admin.dashboard.index';

        return view($view, compact('ringkasan', 'pesananTerbaru'));
    }
}
