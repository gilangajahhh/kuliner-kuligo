<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogStatusPesanan extends Model
{
    protected $table = 'log_status_pesanan';
    protected $primaryKey = 'id_log';
    public $timestamps = false;
    protected $fillable = ['id_pesanan', 'id_user', 'status_baru', 'waktu_update'];
    protected $casts = ['waktu_update' => 'datetime'];
    public function pesanan() { return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id_pesanan'); }
    public function user() { return $this->belongsTo(User::class, 'id_user', 'id_user'); }
}
