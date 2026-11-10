<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\LogStatusPesanan;
use App\Models\Meja;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PesananController extends Controller
{
    public function index()
    {
        $meja = Meja::orderBy('nomor_meja')->get(['id_meja', 'nomor_meja', 'status_meja']);
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
            $pesananSelesai = Pesanan::with('meja', 'pembayaran')
                ->where('status_pesanan', 'selesai')
                ->latest('updated_at')
                ->limit(10)
                ->get();
            return view('kasir.pesanan', compact('meja', 'pesananBaru', 'pesananDiproses', 'pesananSiap', 'pesananSelesai'));
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

    public function statusMeja()
    {
        return response()->json(Meja::with(['pesanan' => fn ($query) => $query->whereIn('status_pesanan', ['baru', 'diproses', 'siap_diantar'])->latest('waktu_pesan')->with('pembayaran')])->orderBy('nomor_meja')->get(['id_meja', 'nomor_meja', 'status_meja']));
    }

    public function meja()
    {
        $meja = Meja::withCount('pesanan')->with(['pesanan' => fn ($query) => $query->whereIn('status_pesanan', ['baru', 'diproses', 'siap_diantar'])->latest('waktu_pesan')->with('pembayaran')->withCount('detail')])->orderBy('nomor_meja')->get();
        $pesananTerakhir = Pesanan::with('pembayaran')
            ->whereIn('id_pesanan', Pesanan::query()->selectRaw('MAX(id_pesanan)')->groupBy('id_meja'))
            ->get(['id_pesanan', 'id_meja', 'no_pesanan', 'status_pesanan'])
            ->keyBy('id_meja');
        return view('kasir.meja', compact('meja', 'pesananTerakhir'));
    }

    public function tambahMeja(Request $request)
    {
        $data = $request->validate(['nomor_meja' => 'required|string|max:50|unique:meja,nomor_meja']);
        $kode = Str::slug($data['nomor_meja']) ?: 'meja';
        $kodeQr = $kode;
        while (Meja::where('kode_qr', $kodeQr)->exists()) $kodeQr = $kode . '-' . Str::lower(Str::random(4));
        Meja::create(['nomor_meja' => $data['nomor_meja'], 'kode_qr' => $kodeQr, 'status_meja' => 'kosong']);
        return redirect()->route('kasir.meja.index')->with('success', 'Meja berhasil ditambahkan.');
    }

    public function ubahMeja(Request $request, Meja $meja)
    {
        $data = $request->validate([
            'nomor_meja' => ['required', 'string', 'max:50', Rule::unique('meja', 'nomor_meja')->ignore($meja->getKey(), 'id_meja')],
        ]);
        $meja->update(['nomor_meja' => $data['nomor_meja']]);

        return redirect()->route('kasir.meja.index')->with('success', 'Nama meja berhasil diperbarui.');
    }

    public function hapusMeja(Meja $meja)
    {
        if ($meja->pesanan()->exists()) {
            return back()->withErrors(['meja' => 'Meja memiliki riwayat pesanan dan tidak dapat dihapus.']);
        }
        $meja->delete();

        return redirect()->route('kasir.meja.index')->with('success', 'Meja berhasil dihapus.');
    }

    public function pindahMeja(Request $request, Meja $meja)
    {
        $data = $request->validate(['meja_tujuan' => 'required|integer|exists:meja,id_meja']);
        $tujuan = Meja::findOrFail($data['meja_tujuan']);
        if ($tujuan->id_meja === $meja->id_meja || $tujuan->status_meja !== 'kosong') return response()->json(['message' => 'Pilih meja tujuan lain yang kosong.'], 422);
        $pesanan = Pesanan::where('id_meja', $meja->id_meja)->whereIn('status_pesanan', ['baru', 'diproses', 'siap_diantar'])->latest('waktu_pesan')->first();
        if (! $pesanan) return response()->json(['message' => 'Tidak ada pesanan aktif di meja ini.'], 422);
        $moved = DB::transaction(function () use ($pesanan, $meja, $tujuan) {
            $tujuan->newQuery()->whereKey($tujuan->id_meja)->lockForUpdate()->firstOrFail();
            if ($tujuan->fresh()->status_meja !== 'kosong' || Pesanan::where('id_meja', $tujuan->id_meja)->whereIn('status_pesanan', ['baru', 'diproses', 'siap_diantar'])->exists()) return false;
            $pesanan->update(['id_meja' => $tujuan->id_meja]);
            $meja->update(['status_meja' => 'kosong']);
            $tujuan->update(['status_meja' => 'terisi']);
            return true;
        });
        if (! $moved) return response()->json(['message' => 'Meja tujuan baru saja digunakan. Pilih meja kosong lainnya.'], 422);
        return response()->json(['message' => 'Pesanan dipindahkan ke ' . $tujuan->nomor_meja . '.']);
    }

    public function kosongkanMeja(Meja $meja)
    {
        $meja->update(['status_meja' => 'kosong']);

        return response()->json(['message' => 'Meja ' . $meja->nomor_meja . ' berhasil dikosongkan.']);
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
            DB::transaction(function () use ($pesanan) {
                $pesanan->newQuery()->whereKey($pesanan->getKey())->lockForUpdate()->firstOrFail();
                $pesanan->refresh();

                if ($pesanan->status_pesanan !== 'batal') {
                    foreach ($pesanan->detail as $detail) {
                        $menu = \App\Models\Menu::whereKey($detail->id_menu)->lockForUpdate()->first();
                        $menu?->increment('stok', $detail->jumlah);
                    }

                    $pesanan->update(['status_pesanan' => 'batal']);
                    $this->catatLog($pesanan, 'batal');
                }
            });
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
        } elseif ($pesanan->pembayaran?->metode_pembayaran === 'e_wallet') {
            // E-wallet payments are paid at the exact order total, so record zero change.
            $kembalian = 0;
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

        DB::transaction(function () use ($pesanan, $data) {
            $pesanan->update(['status_pesanan' => $data['status']]);
            $this->catatLog($pesanan, $data['status']);
            if ($data['status'] === 'selesai') {
                $stillActive = Pesanan::where('id_meja', $pesanan->id_meja)->where('id_pesanan', '!=', $pesanan->id_pesanan)->whereIn('status_pesanan', ['baru', 'diproses', 'siap_diantar'])->exists();
                if (! $stillActive) $pesanan->meja?->update(['status_meja' => 'kosong']);
            }
        });

        if ($request->expectsJson()) return response()->json(['message' => 'Pesanan selesai.', 'receipt_url' => route('kasir.pesanan.struk', $pesanan)]);

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
