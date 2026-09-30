<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriMenu extends Model
{
    protected $table = 'kategori_menu';
    protected $primaryKey = 'id_kategori';
    protected $fillable = ['nama_kategori', 'urutan_tampil'];
    public function menu() { return $this->hasMany(Menu::class, 'id_kategori', 'id_kategori'); }
}
