<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected $table = 'pesanan';
    protected $primaryKey = 'id_pesanan';
    protected $fillable = ['no_pesanan', 'id_meja', 'id_user', 'nama_pelanggan', 'waktu_pesan', 'status_pesanan', 'total_harga'];
    protected $casts = ['waktu_pesan' => 'datetime'];
    public function meja() { return $this->belongsTo(Meja::class, 'id_meja', 'id_meja'); }
    public function detail() { return $this->hasMany(DetailPesanan::class, 'id_pesanan', 'id_pesanan'); }
    public function pembayaran() { return $this->hasOne(Pembayaran::class, 'id_pesanan', 'id_pesanan'); }
    public function logStatus() { return $this->hasMany(LogStatusPesanan::class, 'id_pesanan', 'id_pesanan')->orderBy('waktu_update'); }
}
