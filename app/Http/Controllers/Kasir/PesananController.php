<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\LogStatusPesanan;
use App\Models\Pesanan;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    public function index()
    {
        $pesananBaru = Pesanan::with('meja', 'pembayaran', 'detail.menu')
            ->where('status_pesanan', 'baru')
            ->latest('waktu_pesan')
            ->get();

        $pesananDiproses = Pesanan::with('meja', 'detail.menu')
            ->where('status_pesanan', 'diproses')
            ->orderBy('waktu_pesan')
            ->get();

        $pesananSiap = Pesanan::with('meja', 'detail.menu')
            ->where('status_pesanan', 'siap_diantar')
            ->orderBy('waktu_pesan')
            ->get();

        if (request()->routeIs('kasir.pesanan')) {
            return view('kasir.pesanan', compact('pesananBaru', 'pesananDiproses', 'pesananSiap'));
        }

        $ringkasan = [
            'pesanan_aktif' => Pesanan::whereIn('status_pesanan', ['baru', 'diproses', 'siap_diantar'])->count(),
            'pesanan_masuk' => $pesananBaru->count(),
            'pesanan_selesai' => Pesanan::whereDate('waktu_pesan', today())->where('status_pesanan', 'selesai')->count(),
            'pendapatan_hari_ini' => Pesanan::whereDate('waktu_pesan', today())->where('status_pesanan', '!=', 'batal')->sum('total_harga'),
        ];
        $pesananTerbaru = Pesanan::with('meja')->latest('waktu_pesan')->limit(7)->get();
        $penjualanMingguan = Pesanan::whereDate('waktu_pesan', '>=', today()->subDays(6))
            ->whereDate('waktu_pesan', '<=', today())
            ->where('status_pesanan', '!=', 'batal')
            ->get(['waktu_pesan', 'total_harga'])
            ->groupBy(fn ($pesanan) => $pesanan->waktu_pesan->toDateString());
        $grafikMingguan = collect(range(6, 0))->map(function ($hari) use ($penjualanMingguan) {
            $tanggal = today()->subDays($hari);
            $nilai = $penjualanMingguan->get($tanggal->toDateString(), collect())->sum('total_harga');

            return ['label' => $tanggal->translatedFormat('D'), 'nilai' => $nilai];
        });

        return view('kasir.dashboard', compact('ringkasan', 'pesananTerbaru', 'grafikMingguan'));
    }

    public function show(Pesanan $pesanan)
    {
        $pesanan->load('meja', 'detail.menu', 'detail.varian', 'pembayaran', 'logStatus.user');
        return view('kasir.pesanan-detail', compact('pesanan'));
    }

    public function verifikasi(Request $request, Pesanan $pesanan)
    {
        $data = $request->validate([
            'valid' => 'required|boolean',
            'alasan_batal' => 'nullable|string|max:255',
        ]);

        if (! $data['valid']) {
            $pesanan->update(['status_pesanan' => 'batal']);
            $this->catatLog($pesanan, 'batal');
            return back()->with('success', 'Pesanan dibatalkan: ' . ($data['alasan_batal'] ?? '-'));
        }

        $pesanan->pembayaran?->update(['status_pembayaran' => 'berhasil']);
        $pesanan->update(['status_pesanan' => 'diproses', 'id_user' => auth()->id()]);
        $this->catatLog($pesanan, 'diproses');

        return back()->with('success', 'Pembayaran diverifikasi, pesanan diteruskan ke dapur.');
    }

    public function updateStatus(Request $request, Pesanan $pesanan)
    {
        $data = $request->validate([
            'status' => 'required|in:diproses,siap_diantar,selesai',
        ]);

        $pesanan->update(['status_pesanan' => $data['status']]);
        $this->catatLog($pesanan, $data['status']);

        return back()->with('success', 'Status pesanan diperbarui menjadi ' . $data['status']);
    }

    private function catatLog(Pesanan $pesanan, string $status): void
    {
        LogStatusPesanan::create([
            'id_pesanan' => $pesanan->id_pesanan,
            'id_user' => auth()->id(),
            'status_baru' => $status,
        ]);
    }
}
