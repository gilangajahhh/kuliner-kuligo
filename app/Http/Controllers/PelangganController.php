<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\KategoriMenu;
use App\Models\Meja;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PelangganController extends Controller
{
    public function index(string $kode_qr)
    {
        $meja = Meja::where('kode_qr', $kode_qr)->firstOrFail();
        $kategori = KategoriMenu::with(['menu' => function ($q) {
            $q->where('status_tersedia', true)->with('varian');
        }])->orderBy('urutan_tampil')->get();

        return view('pelanggan.menu', compact('meja', 'kategori'));
    }

    public function checkout(Request $request, string $kode_qr)
    {
        $meja = Meja::where('kode_qr', $kode_qr)->firstOrFail();

        $data = $request->validate([
            'nama_pelanggan' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.id_menu' => 'required|exists:menu,id_menu',
            'items.*.id_varian' => 'nullable|exists:varian_menu,id_varian',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string|max:255',
        ]);

        $pesanan = DB::transaction(function () use ($data, $meja) {
            $pesanan = Pesanan::create([
                'id_meja' => $meja->id_meja,
                'no_pesanan' => 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'nama_pelanggan' => $data['nama_pelanggan'] ?? null,
                'status_pesanan' => 'baru',
                'total_harga' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $menu = \App\Models\Menu::findOrFail($item['id_menu']);
                $hargaTambahan = 0;
                if (! empty($item['id_varian'])) {
                    $hargaTambahan = \App\Models\VarianMenu::findOrFail($item['id_varian'])->harga_tambahan;
                }
                $hargaSatuan = $menu->harga_dasar + $hargaTambahan;
                $subtotal = $hargaSatuan * $item['jumlah'];
                $total += $subtotal;

                DetailPesanan::create([
                    'id_pesanan' => $pesanan->id_pesanan,
                    'id_menu' => $item['id_menu'],
                    'id_varian' => $item['id_varian'] ?? null,
                    'jumlah' => $item['jumlah'],
                    'catatan' => $item['catatan'] ?? null,
                    'harga_satuan' => $hargaSatuan,
                    'subtotal' => $subtotal,
                ]);
            }

            $pesanan->update(['total_harga' => $total]);

            return $pesanan;
        });

        return response()->json([
            'no_pesanan' => $pesanan->no_pesanan,
            'total_harga' => $pesanan->total_harga,
            'redirect' => route('menu.bayar', ['kode_qr' => $meja->kode_qr]) . '?no_pesanan=' . $pesanan->no_pesanan,
        ]);
    }

    public function bayar(Request $request, string $kode_qr)
    {
        $data = $request->validate([
            'no_pesanan' => 'required|exists:pesanan,no_pesanan',
            'metode_pembayaran' => 'required|in:qris,e_wallet,kartu_debit,kartu_kredit',
        ]);

        $pesanan = Pesanan::where('no_pesanan', $data['no_pesanan'])->firstOrFail();

        $pembayaran = Pembayaran::create([
            'id_pesanan' => $pesanan->id_pesanan,
            'metode_pembayaran' => $data['metode_pembayaran'],
            'jumlah_bayar' => $pesanan->total_harga,
            'status_pembayaran' => 'pending',
            'kode_transaksi_gateway' => 'TRX-' . Str::upper(Str::random(10)),
        ]);

        // TODO: integrasi payment gateway asli (Midtrans/Xendit) di sini.

        return response()->json([
            'status' => $pembayaran->status_pembayaran,
            'kode_transaksi' => $pembayaran->kode_transaksi_gateway,
        ]);
    }

    public function status(string $kode_qr, string $no_pesanan)
    {
        $pesanan = Pesanan::with('detail.menu', 'detail.varian', 'pembayaran')
            ->where('no_pesanan', $no_pesanan)
            ->firstOrFail();

        return view('pelanggan.status', compact('pesanan'));
    }
}
