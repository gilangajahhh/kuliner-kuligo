<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservasi extends Model
{
    protected $table = 'reservasi';
    protected $primaryKey = 'id_reservasi';
    protected $fillable = ['id_meja', 'kode_booking', 'nama_pemesan', 'whatsapp', 'keperluan', 'tanggal_reservasi', 'waktu_reservasi', 'jumlah_orang', 'status'];
    protected $casts = ['tanggal_reservasi' => 'date'];

    public function meja() { return $this->belongsTo(Meja::class, 'id_meja', 'id_meja'); }
}
