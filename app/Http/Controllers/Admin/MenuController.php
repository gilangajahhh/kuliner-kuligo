<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FotoMenuRequest;
use App\Models\KategoriMenu;
use App\Models\DetailPesanan;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function index()
    {
        $menu = Menu::with('kategori', 'varian')->latest()->paginate(15);
        return view('admin.menu.index', compact('menu'));
    }

    public function create()
    {
        $kategori = KategoriMenu::orderBy('urutan_tampil')->get();
        return view('admin.menu.form', compact('kategori'));
    }

    public function store(FotoMenuRequest $request)
    {
        $data = $request->validate([
            'id_kategori' => 'required|exists:kategori_menu,id_kategori',
            'nama_menu' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
            'harga_dasar' => 'required|numeric|min:0',
            'url_gambar' => 'nullable|string|max:255',
            'status_tersedia' => 'boolean',
            'varian' => 'nullable|array',
            'varian.*.id_varian' => 'nullable|integer',
            'varian.*.nama_varian' => 'nullable|string|max:100',
            'varian.*.harga_tambahan' => 'nullable|numeric|min:0',
        ]);

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('menu', 'public');
            $data['url_gambar'] = Storage::disk('public')->url($path);
        }

        DB::transaction(function () use ($data) {
            $menu = Menu::create(collect($data)->except(['varian', 'varian_hapus'])->all());
            foreach ($data['varian'] ?? [] as $varian) {
                if (filled($varian['nama_varian'] ?? null)) {
                    $menu->varian()->create([
                        'nama_varian' => $varian['nama_varian'],
                        'harga_tambahan' => $varian['harga_tambahan'] ?? 0,
                    ]);
                }
            }
        });

        return redirect()->route('admin.menu.index')->with('success', 'Menu berhasil ditambahkan.');
    }

    public function edit(Menu $menu)
    {
        $kategori = KategoriMenu::orderBy('urutan_tampil')->get();
        return view('admin.menu.form', compact('menu', 'kategori'));
    }

    public function update(FotoMenuRequest $request, Menu $menu)
    {
        $data = $request->validate([
            'id_kategori' => 'required|exists:kategori_menu,id_kategori',
            'nama_menu' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
            'harga_dasar' => 'required|numeric|min:0',
            'url_gambar' => 'nullable|string|max:255',
            'status_tersedia' => 'boolean',
            'varian' => 'nullable|array',
            'varian.*.id_varian' => 'nullable|integer',
            'varian.*.nama_varian' => 'nullable|string|max:100',
            'varian.*.harga_tambahan' => 'nullable|numeric|min:0',
            'varian_hapus' => 'nullable|array',
            'varian_hapus.*' => 'integer',
        ]);

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('menu', 'public');
            $data['url_gambar'] = Storage::disk('public')->url($path);
        }

        DB::transaction(function () use ($data, $menu) {
            $menu->update(collect($data)->except(['varian', 'varian_hapus'])->all());

            if (! empty($data['varian_hapus'])) {
                $menu->varian()->whereIn('id_varian', $data['varian_hapus'])->delete();
            }

            foreach ($data['varian'] ?? [] as $varian) {
                if (! filled($varian['nama_varian'] ?? null)) {
                    continue;
                }

                $attributes = [
                    'nama_varian' => $varian['nama_varian'],
                    'harga_tambahan' => $varian['harga_tambahan'] ?? 0,
                ];

                if (! empty($varian['id_varian'])) {
                    $menu->varian()->whereKey($varian['id_varian'])->update($attributes);
                } else {
                    $menu->varian()->create($attributes);
                }
            }
        });

        return redirect()->route('admin.menu.index')->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(Menu $menu)
    {
        if (DetailPesanan::where('id_menu', $menu->getKey())->exists()) {
            return back()->withErrors(['menu' => 'Menu memiliki riwayat transaksi dan tidak dapat dihapus. Ubah statusnya menjadi habis jika tidak dijual lagi.']);
        }

        $menu->delete();
        return redirect()->route('admin.menu.index')->with('success', 'Menu berhasil dihapus.');
    }
}
