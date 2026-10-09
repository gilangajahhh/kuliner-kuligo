<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogStatusPesanan;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $user = User::orderBy('nama')->get();
        return view('admin.kasir.index', compact('user'));
    }

    public function store(Request $request)
    {
        $request->merge(['username' => mb_strtolower(trim((string) $request->input('username')))]);

        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'username' => ['required', 'string', 'max:50', Rule::unique('user', 'username')],
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,kasir',
        ]);

        if (User::whereRaw('LOWER(username) = ?', [$data['username']])->exists()) {
            return back()->withErrors(['username' => 'Username sudah digunakan.'])->withInput();
        }

        User::create([
            'nama' => $data['nama'],
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'],
            'status_aktif' => true,
        ]);

        return back()->with('success', 'Akun berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $request->merge(['username' => mb_strtolower(trim((string) $request->input('username')))]);

        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'username' => ['required', 'string', 'max:50', Rule::unique('user', 'username')->ignore($user->getKey(), 'id_user')],
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,kasir',
            'status_aktif' => 'required|boolean',
        ]);

        if (User::whereRaw('LOWER(username) = ?', [$data['username']])
            ->where('id_user', '<>', $user->getKey())
            ->exists()) {
            return back()->withErrors(['username' => 'Username sudah digunakan.'])->withInput();
        }

        $isActive = (bool) $data['status_aktif'];
        $isCurrentAccount = (int) auth()->id() === (int) $user->getKey();

        if ($isCurrentAccount && ($data['role'] !== 'admin' || ! $isActive)) {
            return back()->withErrors(['akun' => 'Akun admin yang sedang digunakan tidak dapat dinonaktifkan atau diubah menjadi kasir.']);
        }

        $removingAdminAccess = $user->role === 'admin'
            && $user->status_aktif
            && ($data['role'] !== 'admin' || ! $isActive);

        if ($removingAdminAccess && ! User::where('role', 'admin')
            ->where('status_aktif', true)
            ->where('id_user', '<>', $user->getKey())
            ->exists()) {
            return back()->withErrors(['akun' => 'Admin aktif terakhir tidak dapat dinonaktifkan atau diubah perannya.']);
        }

        $updates = [
            'nama' => $data['nama'],
            'username' => $data['username'],
            'role' => $data['role'],
            'status_aktif' => $isActive,
        ];

        if (filled($data['password'] ?? null)) {
            $updates['password_hash'] = Hash::make($data['password']);
        }

        $user->update($updates);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ((int) auth()->id() === (int) $user->getKey()) {
            return back()->withErrors(['akun' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan.']);
        }

        if ($user->role === 'admin' && $user->status_aktif
            && ! User::where('role', 'admin')->where('status_aktif', true)
                ->where('id_user', '<>', $user->getKey())->exists()) {
            return back()->withErrors(['akun' => 'Admin aktif terakhir tidak dapat dinonaktifkan.']);
        }

        $hasHistory = Pesanan::where('id_user', $user->getKey())->exists()
            || LogStatusPesanan::where('id_user', $user->getKey())->exists();

        if ($hasHistory) {
            $user->update(['status_aktif' => false]);
            return back()->with('success', 'Akun dinonaktifkan karena memiliki riwayat transaksi. Riwayat tetap tersimpan.');
        }

        $user->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
