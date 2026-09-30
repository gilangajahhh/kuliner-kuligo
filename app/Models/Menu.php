<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';
    protected $primaryKey = 'id_menu';
    protected $fillable = ['id_kategori', 'nama_menu', 'deskripsi', 'harga_dasar', 'url_gambar', 'status_tersedia'];
    protected $casts = ['status_tersedia' => 'boolean', 'harga_dasar' => 'decimal:2'];

    public function kategori() { return $this->belongsTo(KategoriMenu::class, 'id_kategori', 'id_kategori'); }
    public function varian() { return $this->hasMany(VarianMenu::class, 'id_menu', 'id_menu'); }
}
