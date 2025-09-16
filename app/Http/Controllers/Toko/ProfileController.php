<?php

// ✅ DIPERBAIKI: Menggunakan nama namespace dan class yang benar
namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Menampilkan form untuk mengedit profil toko.
     */
    public function edit()
    {
        $store = Auth::user()->store;

        if (!$store) {
            // Jika karena suatu alasan data toko tidak ada, redirect
            return redirect()->route('seller.dashboard')->with('error', 'Data toko tidak ditemukan.');
        }

        return view('seller.profile.edit', compact('store'));
    }

    /**
     * Mengupdate data profil toko di database.
     */
    public function update(Request $request)
    {
        $store = Auth::user()->store;

        $request->validate([
            'name' => 'required|string|max:255|unique:stores,name,' . $store->id,
            'description' => 'required|string|min:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024', // Logo maks 1MB
        ]);

        $data = $request->only('name', 'description');

        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            if ($store->seller_logo) {
                Storage::delete(str_replace('/storage', 'public', $store->seller_logo));
            }
            // Simpan logo baru
            $path = $request->file('logo')->store('public/seller_logos');
            // Sesuaikan nama kolom dengan database Anda ('seller_logo' atau 'logo_url')
            $data['seller_logo'] = Storage::url($path);
        }

        $store->update($data);

        return redirect()->route('seller.profile.edit')->with('success', 'Profil toko berhasil diperbarui.');
    }
}