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
        $pesananBaru = Pesanan::with('meja', 'pembayaran')
            ->where('status_pesanan', 'baru')
            ->latest('waktu_pesan')
            ->get();

        $pesananDiproses = Pesanan::with('meja')
            ->whereIn('status_pesanan', ['diproses', 'siap_diantar'])
            ->orderBy('waktu_pesan')
            ->get();

        return view('kasir.dashboard', compact('pesananBaru', 'pesananDiproses'));
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
