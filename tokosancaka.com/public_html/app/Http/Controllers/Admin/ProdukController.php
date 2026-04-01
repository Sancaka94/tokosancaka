<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product; // Sesuaikan dengan nama Model Anda

class ProdukController extends Controller
{
    /**
     * Menampilkan daftar produk (READ)
     */
    public function index(Request $request)
    {
        $currentTab = $request->input('tab', 'pulsa-prabayar');
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Product::where('kategori', $currentTab);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('kode_sku', 'like', '%' . $search . '%')
                  ->orWhere('nama_produk', 'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $products = $query->latest()->paginate(15);

        return view('admin.produk.index', compact('products', 'currentTab'));
    }

    /**
     * Menampilkan form tambah produk (CREATE)
     */
    public function create(Request $request)
    {
        // Menangkap tab saat ini agar form tahu mau menambahkan ke kategori mana
        $currentTab = $request->input('tab', 'pulsa-prabayar');

        return view('admin.produk.create', compact('currentTab'));
    }

    /**
     * Menyimpan produk baru ke database (STORE)
     */
    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $request->validate([
            'kode_sku'    => 'required|unique:products,kode_sku', // Pastikan nama tabel sesuai, misal: 'products'
            'nama_produk' => 'required|string|max:255',
            'kategori'    => 'required|string',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual'  => 'required|numeric|min:0',
            'status'      => 'required|in:aktif,gangguan,nonaktif',
        ], [
            // Kustomisasi pesan error (opsional)
            'kode_sku.required' => 'Kode SKU wajib diisi.',
            'kode_sku.unique'   => 'Kode SKU sudah terdaftar, gunakan kode lain.',
            'nama_produk.required' => 'Nama produk wajib diisi.',
        ]);

        // 2. Simpan ke database
        Product::create([
            'kode_sku'    => $request->kode_sku,
            'nama_produk' => $request->nama_produk,
            'kategori'    => $request->kategori,
            'harga_modal' => $request->harga_modal,
            'harga_jual'  => $request->harga_jual,
            'status'      => $request->status,
        ]);

        // 3. Redirect kembali ke index dengan tab yang sesuai
        return redirect()->route('admin.produk.index', ['tab' => $request->kategori])
                         ->with('success', 'Produk berhasil ditambahkan!');
    }

    /**
     * Menampilkan form edit produk (EDIT)
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);

        return view('admin.produk.edit', compact('product'));
    }

    /**
     * Memperbarui data produk di database (UPDATE)
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // 1. Validasi input
        $request->validate([
            // Abaikan aturan unique untuk ID produk yang sedang diedit
            'kode_sku'    => 'required|unique:products,kode_sku,' . $product->id,
            'nama_produk' => 'required|string|max:255',
            'kategori'    => 'required|string',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual'  => 'required|numeric|min:0',
            'status'      => 'required|in:aktif,gangguan,nonaktif',
        ]);

        // 2. Update data
        $product->update([
            'kode_sku'    => $request->kode_sku,
            'nama_produk' => $request->nama_produk,
            'kategori'    => $request->kategori,
            'harga_modal' => $request->harga_modal,
            'harga_jual'  => $request->harga_jual,
            'status'      => $request->status,
        ]);

        // 3. Redirect kembali ke index
        return redirect()->route('admin.produk.index', ['tab' => $request->kategori])
                         ->with('success', 'Produk berhasil diperbarui!');
    }

    /**
     * Menghapus produk (DELETE)
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Simpan kategori sebelum dihapus agar redirect kembalinya pas di tab tersebut
        $kategori = $product->kategori;

        $product->delete();

        return redirect()->route('admin.produk.index', ['tab' => $kategori])
                         ->with('success', 'Produk berhasil dihapus!');
    }
}
