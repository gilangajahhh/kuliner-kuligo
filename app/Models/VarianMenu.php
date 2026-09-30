<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VarianMenu extends Model
{
    protected $table = 'varian_menu';
    protected $primaryKey = 'id_varian';
    protected $fillable = ['id_menu', 'nama_varian', 'harga_tambahan'];
    public function menu() { return $this->belongsTo(Menu::class, 'id_menu', 'id_menu'); }
}

