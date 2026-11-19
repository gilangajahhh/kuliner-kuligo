<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Reservasi;
use Illuminate\Http\Request;

class ReservasiController extends Controller
{
    public function index()
    {
        $reservasi = Reservasi::with('meja')
            ->orderByRaw("CASE status WHEN 'menunggu' THEN 0 WHEN 'dikonfirmasi' THEN 1 ELSE 2 END")
            ->orderBy('tanggal_reservasi')
            ->orderBy('waktu_reservasi')
            ->paginate(20);

        return view('kasir.reservasi.index', compact('reservasi'));
    }

    public function status(Request $request, Reservasi $reservasi)
    {
        $data = $request->validate(['status' => 'required|in:dikonfirmasi,ditolak,selesai']);
        $reservasi->update($data);

        return back()->with('success', 'Status reservasi ' . $reservasi->kode_booking . ' diperbarui.');
    }
}
