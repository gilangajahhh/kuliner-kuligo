<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\LogStatusPesanan;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $pesanan->load('meja', 'detail.menu', 'detail.varian', 'pembayaran', 'logStatus.user', 'user');
        return view('kasir.pesanan-detail', compact('pesanan'));
    }

    public function struk(Pesanan $pesanan)
    {
        $pesanan->load('meja', 'detail.menu', 'detail.varian', 'pembayaran', 'user');

        return view('kasir.struk', compact('pesanan'));
    }

    public function transaksi()
    {
        $transaksi = Pesanan::with('meja', 'pembayaran')
            ->whereHas('pembayaran')
            ->latest('waktu_pesan')
            ->paginate(15);

        return view('kasir.transaksi', compact('transaksi'));
    }

    public function verifikasi(Request $request, Pesanan $pesanan)
    {
        $data = $request->validate([
            'valid' => 'required|boolean',
            'alasan_batal' => 'nullable|string|max:255',
            'uang_diterima' => [
                Rule::requiredIf(fn () => $request->boolean('valid') && $pesanan->pembayaran?->metode_pembayaran === 'tunai'),
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        if (! $data['valid']) {
            $pesanan->update(['status_pesanan' => 'batal']);
            $this->catatLog($pesanan, 'batal');
            return back()->with('success', 'Pesanan dibatalkan: ' . ($data['alasan_batal'] ?? '-'));
        }

        $uangDiterima = null;
        $kembalian = null;

        if ($pesanan->pembayaran?->metode_pembayaran === 'tunai') {
            $uangDiterima = round((float) $data['uang_diterima'], 2);
            $totalPesanan = round((float) $pesanan->total_harga, 2);

            if ($uangDiterima < $totalPesanan) {
                return back()->withErrors([
                    'uang_diterima' => 'Uang yang diterima kurang Rp ' . number_format($totalPesanan - $uangDiterima, 0, ',', '.') . '.',
                ])->withInput();
            }

            $kembalian = round($uangDiterima - $totalPesanan, 2);
        }

        DB::transaction(function () use ($pesanan, $uangDiterima, $kembalian) {
            $pesanan->pembayaran?->update([
                'status_pembayaran' => 'berhasil',
                'waktu_pembayaran' => now(),
                'uang_diterima' => $uangDiterima,
                'kembalian' => $kembalian,
            ]);
            $pesanan->update(['status_pesanan' => 'diproses', 'id_user' => auth()->id()]);
            $this->catatLog($pesanan, 'diproses');
        });

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
