<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';
    protected $fillable = ['id_pesanan', 'metode_pembayaran', 'jumlah_bayar', 'status_pembayaran', 'kode_transaksi_gateway', 'waktu_pembayaran'];
    protected $casts = ['waktu_pembayaran' => 'datetime'];
    public function pesanan() { return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id_pesanan'); }
}
