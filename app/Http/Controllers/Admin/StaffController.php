<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index()
    {
        $user = User::orderBy('nama')->get();
        return view('admin.kasir.index', compact('user'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'username' => 'required|string|max:50|unique:user,username',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,kasir',
        ]);

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
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'role' => 'required|in:admin,kasir',
            'status_aktif' => 'boolean',
        ]);

        $user->update($data);
        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->update(['status_aktif' => false]);
        return back()->with('success', 'Akun dinonaktifkan.');
    }
}
