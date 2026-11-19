<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meja extends Model
{
    protected $table = 'meja';
    protected $primaryKey = 'id_meja';
    protected $fillable = ['nomor_meja', 'kode_qr', 'status_meja', 'tipe_meja', 'kapasitas'];
    public function pesanan() { return $this->hasMany(Pesanan::class, 'id_meja', 'id_meja'); }
    public function reservasi() { return $this->hasMany(Reservasi::class, 'id_meja', 'id_meja'); }
}
