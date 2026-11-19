<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class MejaController extends Controller
{
    public function index()
    {
        $meja = Meja::withCount(['pesanan', 'reservasi'])->orderBy('nomor_meja')->get();

        return view('admin.meja.index', compact('meja'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nomor_meja' => 'required|string|max:50|unique:meja,nomor_meja',
            'tipe_meja' => 'required|in:indoor,outdoor',
            'kapasitas' => 'required|integer|min:1|max:50',
        ]);

        $kode = Str::slug($data['nomor_meja']) ?: 'meja';
        $kodeQr = $kode;
        while (Meja::where('kode_qr', $kodeQr)->exists()) {
            $kodeQr = $kode . '-' . Str::lower(Str::random(4));
        }

        Meja::create([
            'nomor_meja' => $data['nomor_meja'],
            'kode_qr' => $kodeQr,
            'status_meja' => 'kosong',
            'tipe_meja' => $data['tipe_meja'],
            'kapasitas' => $data['kapasitas'],
        ]);

        return redirect()->route('admin.meja.index')->with('success', 'Meja berhasil ditambahkan. QR siap dicetak.');
    }

    public function update(Request $request, Meja $meja)
    {
        $data = $request->validate([
            'nomor_meja' => ['required', 'string', 'max:50', Rule::unique('meja', 'nomor_meja')->ignore($meja->getKey(), 'id_meja')],
            'tipe_meja' => 'required|in:indoor,outdoor',
            'kapasitas' => 'required|integer|min:1|max:50',
        ]);

        $meja->update($data);

        return redirect()->route('admin.meja.index')->with('success', 'Nama meja berhasil diperbarui. Kode QR tetap sama.');
    }

    public function kosongkan(Meja $meja)
    {
        $meja->update(['status_meja' => 'kosong']);

        return redirect()->route('admin.meja.index')->with('success', 'Meja ' . $meja->nomor_meja . ' berhasil dikosongkan.');
    }

    public function destroy(Meja $meja)
    {
        if ($meja->pesanan()->exists() || $meja->reservasi()->exists()) {
            return back()->withErrors(['meja' => 'Meja sudah memiliki riwayat pesanan atau reservasi, jadi tidak dapat dihapus agar data tetap aman.']);
        }

        $meja->delete();

        return redirect()->route('admin.meja.index')->with('success', 'Meja berhasil dihapus.');
    }
}
