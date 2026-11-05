<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\KategoriMenu;
use App\Models\Meja;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PelangganController extends Controller
{
    public function index(string $kode_qr)
    {
        $meja = Meja::where('kode_qr', $kode_qr)->firstOrFail();
        $kategori = KategoriMenu::with(['menu' => function ($q) {
            $q->where('status_tersedia', true)->where('stok', '>', 0)->with('varian');
        }])->orderBy('urutan_tampil')->get();

        return view('pelanggan.menu', compact('meja', 'kategori'));
    }

    public function checkout(Request $request, string $kode_qr)
    {
        $meja = Meja::where('kode_qr', $kode_qr)->firstOrFail();

        $data = $request->validate([
            'idempotency_key' => 'required|uuid',
            'nama_pelanggan' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.id_menu' => 'required|exists:menu,id_menu',
            'items.*.id_varian' => 'nullable|exists:varian_menu,id_varian',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string|max:255',
        ]);

        $fingerprint = hash('sha256', json_encode([
            'meja' => $meja->kode_qr,
            'nama_pelanggan' => $data['nama_pelanggan'] ?? null,
            'items' => $data['items'],
        ], JSON_THROW_ON_ERROR));

        try {
            $pesanan = DB::transaction(function () use ($data, $meja, $fingerprint) {
            $existing = Pesanan::where('idempotency_key', $data['idempotency_key'])
                ->where('id_meja', $meja->id_meja)
                ->first();

            if ($existing) {
                if (! hash_equals((string) $existing->idempotency_fingerprint, $fingerprint)) {
                    abort(409, 'Permintaan checkout ini sudah digunakan untuk isi keranjang yang berbeda.');
                }

                return $existing;
            }

            $meja = Meja::whereKey($meja->getKey())->lockForUpdate()->firstOrFail();
            $pesanan = Pesanan::create([
                'id_meja' => $meja->id_meja,
                'no_pesanan' => 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'nama_pelanggan' => $data['nama_pelanggan'] ?? null,
                'status_pesanan' => 'baru',
                'total_harga' => 0,
                'idempotency_key' => $data['idempotency_key'],
                'idempotency_fingerprint' => $fingerprint,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $menu = \App\Models\Menu::where('status_tersedia', true)->whereKey($item['id_menu'])->lockForUpdate()->firstOrFail();
                if ($item['jumlah'] > $menu->stok) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Stok {$menu->nama_menu} tersisa {$menu->stok}, sedangkan jumlah pesanan {$item['jumlah']}.",
                    ]);
                }
                $hargaTambahan = 0;
                if (! empty($item['id_varian'])) {
                    $varian = \App\Models\VarianMenu::where('id_menu', $menu->id_menu)->findOrFail($item['id_varian']);
                    $hargaTambahan = $varian->harga_tambahan;
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
                $menu->decrement('stok', $item['jumlah']);
            }

            $pesanan->update(['total_harga' => $total]);
            $meja->update(['status_meja' => 'terisi']);

            return $pesanan;
            });
        } catch (QueryException $exception) {
            if (! in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true)) {
                throw $exception;
            }

            $pesanan = Pesanan::where('idempotency_key', $data['idempotency_key'])->first();
            if (! $pesanan || (int) $pesanan->id_meja !== (int) $meja->id_meja) {
                throw $exception;
            }
            if (! hash_equals((string) $pesanan->idempotency_fingerprint, $fingerprint)) {
                abort(409, 'Permintaan checkout ini sudah digunakan untuk isi keranjang yang berbeda.');
            }
        }

        return response()->json([
            'no_pesanan' => $pesanan->no_pesanan,
            'total_harga' => $pesanan->total_harga,
            'redirect' => route('menu.bayar', ['kode_qr' => $meja->kode_qr]) . '?no_pesanan=' . $pesanan->no_pesanan,
        ]);
    }

    public function bayar(Request $request, string $kode_qr)
    {
        $kanal = match ($request->input('metode_pembayaran')) {
            'bank_transfer' => 'BCA,BNI,BRI,Mandiri',
            'e_wallet' => 'GoPay,OVO,DANA,ShopeePay',
            default => 'BCA,BNI,BRI,Mandiri,GoPay,OVO,DANA,ShopeePay',
        };
        $data = $request->validate([
            'no_pesanan' => 'required|exists:pesanan,no_pesanan',
            'metode_pembayaran' => 'required|in:qris,bank_transfer,e_wallet,tunai',
            'kanal_pembayaran' => ['required_if:metode_pembayaran,bank_transfer,e_wallet', 'nullable', 'in:' . $kanal],
        ]);

        $pesanan = Pesanan::where('no_pesanan', $data['no_pesanan'])
            ->whereHas('meja', fn ($query) => $query->where('kode_qr', $kode_qr))
            ->firstOrFail();

        $pembayaran = DB::transaction(function () use ($pesanan, $data) {
            $pesanan = Pesanan::whereKey($pesanan->getKey())->lockForUpdate()->firstOrFail();
            $existing = Pembayaran::where('id_pesanan', $pesanan->id_pesanan)->first();

            if ($existing) {
                $sameMethod = $existing->metode_pembayaran === $data['metode_pembayaran'];
                $sameChannel = $existing->kanal_pembayaran === ($data['kanal_pembayaran'] ?? null);
                if (! $sameMethod || ! $sameChannel) {
                    abort(409, 'Pesanan ini sudah memiliki pembayaran dengan metode lain.');
                }

                return $existing;
            }

            return Pembayaran::create([
                'id_pesanan' => $pesanan->id_pesanan,
                'metode_pembayaran' => $data['metode_pembayaran'],
                'kanal_pembayaran' => $data['kanal_pembayaran'] ?? null,
                'jumlah_bayar' => $pesanan->total_harga,
                'status_pembayaran' => 'pending',
                'kode_transaksi_gateway' => 'TRX-' . Str::upper(Str::random(10)),
            ]);
        });

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
            ->whereHas('meja', fn ($query) => $query->where('kode_qr', $kode_qr))
            ->firstOrFail();

        return view('pelanggan.status', compact('pesanan'));
    }
}
