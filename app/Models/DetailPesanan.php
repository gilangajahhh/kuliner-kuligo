<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPesanan extends Model
{
    protected $table = 'detail_pesanan';
    protected $primaryKey = 'id_detail';
    protected $fillable = ['id_pesanan', 'id_menu', 'id_varian', 'jumlah', 'catatan', 'harga_satuan', 'subtotal'];
    public function pesanan() { return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id_pesanan'); }
    public function menu() { return $this->belongsTo(Menu::class, 'id_menu', 'id_menu'); }
    public function varian() { return $this->belongsTo(VarianMenu::class, 'id_varian', 'id_varian'); }
}
