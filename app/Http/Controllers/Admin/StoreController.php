<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StoreController extends Controller
{
     /**
     * FITUR 2: Menampilkan daftar semua toko yang terdaftar.
     */
    public function index()
    {
        $adminUser = Auth::user();

        // ✅ DIPERBAIKI: Mengambil toko milik admin secara terpisah
        $adminStore = Store::where('user_id', $adminUser->id_pengguna)->first();

        // ✅ DIPERBAIKI: Mengambil semua toko milik customer (bukan admin)
        $customerStores = Store::where('user_id', '!=', $adminUser->id_pengguna)
                                ->with('user')
                                ->latest()
                                ->paginate(15);

        return view('admin.store.index', compact('adminStore', 'customerStores'));
    }
    /**
     * FITUR 1: Menampilkan form untuk admin membuat toko baru.
     */
    public function create()
    {
        $adminUser = Auth::user();
        if ($adminUser->store) {
            return redirect()->route('admin.stores.index')
                ->with('info', 'Anda sudah memiliki toko. Gunakan form di bawah untuk mengelola toko customer.');
        }
        return view('admin.store.create');
    }

    /**
     * FITUR 1: Menyimpan toko baru yang dibuat oleh admin.
     */
    public function store(Request $request)
    {
        $adminUser = Auth::user();
        $request->validate(['name' => 'required|string|max:255|unique:stores', 'description' => 'required|string|min:20']);

        Store::create([
            'user_id' => $adminUser->id_pengguna,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        // Opsional: Update role admin jika perlu
        // $adminUser->role = 'Seller';
        // $adminUser->save();

        // ✅ DIPERBAIKI: Redirect kembali ke panel admin, bukan dashboard seller
        return redirect()->route('admin.stores.index')->with('success', 'Toko untuk akun admin berhasil dibuat!');
    
    }

    /**
     * FITUR 3: Menampilkan form untuk mengedit toko milik customer.
     */
    public function edit(Store $store)
    {
        // $store akan otomatis ditemukan oleh Laravel berdasarkan ID dari URL
        return view('admin.store.edit', compact('store'));
    }

    /**
     * FITUR 3: Mengupdate data toko milik customer.
     */
    public function update(Request $request, Store $store)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:stores,name,' . $store->id,
            'description' => 'required|string|min:20',
        ]);

        $store->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return redirect()->route('admin.stores.index')->with('success', 'Data toko berhasil diperbarui.');
    }
}