<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Pesanan;
use Carbon\CarbonPeriod;

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
            'menu_tidak_tersedia' => Menu::whereRaw('status_tersedia IS FALSE')->count(),
        ];

        $view = request()->routeIs('admin.pesanan') ? 'admin.pesanan.index' : 'admin.dashboard.index';

        if (request()->routeIs('admin.pesanan')) {
            $pesananTerbaru = Pesanan::with('meja')->latest('waktu_pesan')->limit(10)->get();

            return view($view, compact('ringkasan', 'pesananTerbaru'));
        }

        $mulai = today()->subDays(6)->startOfDay();
        $akhir = today()->endOfDay();
        $penjualanHarian = Pesanan::query()
            ->whereBetween('waktu_pesan', [$mulai, $akhir])
            ->where('status_pesanan', '!=', 'batal')
            ->selectRaw('DATE(waktu_pesan) as tanggal, SUM(total_harga) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $grafikPenjualan = collect(CarbonPeriod::create($mulai->toDateString(), $akhir->toDateString()))
            ->map(function ($tanggal) use ($penjualanHarian) {
                $key = $tanggal->toDateString();

                return [
                    'tanggal' => $key,
                    'label' => $tanggal->translatedFormat('D'),
                    'tanggal_label' => $tanggal->format('d M'),
                    'total' => (float) ($penjualanHarian[$key] ?? 0),
                ];
            });
        $maksPenjualan = max(1, (float) $grafikPenjualan->max('total'));
        $grafikPenjualan = $grafikPenjualan->map(function ($hari) use ($maksPenjualan) {
            $hari['persentase'] = $hari['total'] > 0 ? max(8, $hari['total'] / $maksPenjualan * 100) : 0;
            $hari['nilai_label'] = $hari['total'] >= 1000000
                ? number_format($hari['total'] / 1000000, 1, ',', '.') . ' jt'
                : ($hari['total'] >= 1000 ? number_format($hari['total'] / 1000, 0, ',', '.') . ' rb' : number_format($hari['total'], 0, ',', '.'));

            return $hari;
        });
        $totalPenjualanMinggu = $grafikPenjualan->sum('total');

        return view($view, compact('ringkasan', 'grafikPenjualan', 'totalPenjualanMinggu'));
    }
}
