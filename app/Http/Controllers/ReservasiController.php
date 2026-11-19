<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use App\Models\Reservasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservasiController extends Controller
{
    public function index()
    {
        $meja = Meja::orderBy('nomor_meja')->get();
        return view('reservasi.index', compact('meja'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_meja' => 'required|exists:meja,id_meja',
            'nama_pemesan' => 'required|string|max:100',
            'whatsapp' => 'required|string|max:30',
            'keperluan' => 'nullable|string|max:160',
            'tanggal_reservasi' => 'required|date|after_or_equal:today',
            'waktu_reservasi' => 'required|date_format:H:i',
            'jumlah_orang' => 'required|integer|min:1|max:30',
        ]);

        $reservasi = DB::transaction(function () use ($data) {
            $meja = Meja::whereKey($data['id_meja'])->lockForUpdate()->firstOrFail();
            if ((int) $data['jumlah_orang'] > (int) $meja->kapasitas) {
                throw ValidationException::withMessages([
                    'jumlah_orang' => 'Jumlah tamu melebihi kapasitas ' . $meja->nomor_meja . ' (' . $meja->kapasitas . ' orang). Silakan pilih meja lain atau kurangi jumlah tamu.',
                ]);
            }
            $bentrok = Reservasi::where('id_meja', $data['id_meja'])
                ->where('tanggal_reservasi', $data['tanggal_reservasi'])
                ->where('waktu_reservasi', $data['waktu_reservasi'])
                ->whereIn('status', ['menunggu', 'dikonfirmasi'])->exists();
            if ($bentrok) {
                return null;
            }

            do { $kode = 'DN-' . Str::upper(Str::random(6)); } while (Reservasi::where('kode_booking', $kode)->exists());
            return Reservasi::create([...$data, 'kode_booking' => $kode]);
        });
        if (! $reservasi) {
            return back()->withInput()->withErrors(['id_meja' => 'Meja sudah dipesan pada waktu tersebut. Silakan pilih waktu atau meja lain.']);
        }
        return redirect()->route('reservasi.success', $reservasi->kode_booking);
    }

    public function success(string $kode)
    {
        $reservasi = Reservasi::with('meja')->where('kode_booking', $kode)->firstOrFail();
        return view('reservasi.success', compact('reservasi'));
    }
}
