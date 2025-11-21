<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProdukController extends Controller
{
    /**
     * Menampilkan daftar produk HANYA untuk toko yang sedang login.
     */
    public function index()
    {
        $store = Auth::user()->store;

        if (!$store) {
            // Jika user belum punya toko, arahkan ke halaman dashboard dengan info
            return redirect()->route('seller.dashboard')->with('info', 'Anda perlu membuat toko terlebih dahulu untuk mengelola produk.');
        }

        $products = Product::where('store_id', $store->id)->latest()->paginate(10);

        return view('seller.produk.index', compact('products'));
    }

    /**
     * Menampilkan form untuk membuat produk baru.
     */
    public function create()
    {
        return view('seller.produk.create');
    }

    /**
     * Menyimpan produk baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $store = Auth::user()->store;

        if (!$store) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', 'Gagal membuat produk: Data toko Anda tidak ditemukan.');
        }

        $data = $request->except('_token');
        $data['store_id'] = $store->id;
        $data['slug'] = Str::slug($request->name) . '-' . uniqid();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/products');
            $data['image_url'] = Storage::url($path);
        }

        Product::create($data);

        return redirect()->route('seller.produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit produk.
     * @param string $slug Slug produk dari URL.
     */
    public function edit($slug)
    {
        $userStore = Auth::user()->store;
        if (!$userStore) {
            abort(403, 'Anda harus memiliki toko untuk mengedit produk.');
        }

        $product = Product::where('slug', $slug)
                          ->where('store_id', $userStore->id)
                          ->firstOrFail();

        return view('seller.produk.edit', ['produk' => $product]);
    }

    /**
     * Mengupdate produk di database.
     * @param string $slug Slug produk dari URL.
     */
    public function update(Request $request, $slug)
    {
        $userStore = Auth::user()->store;
        if (!$userStore) {
            abort(403, 'Anda tidak memiliki akses untuk memperbarui produk ini.');
        }

        $product = Product::where('slug', $slug)
                          ->where('store_id', $userStore->id)
                          ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('_token', '_method');
        
        if ($request->name !== $product->name) {
            $data['slug'] = Str::slug($request->name) . '-' . uniqid();
        }

        if ($request->hasFile('image')) {
            if ($product->image_url) {
                Storage::delete(str_replace('/storage', 'public', $product->image_url));
            }
            $path = $request->file('image')->store('public/products');
            $data['image_url'] = Storage::url($path);
        }

        $product->update($data);

        return redirect()->route('seller.produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Menghapus produk dari database.
     * @param string $slug Slug produk dari URL.
     */
    public function destroy($slug)
    {
        $userStore = Auth::user()->store;
        if (!$userStore) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus produk ini.');
        }

        $product = Product::where('slug', $slug)
                          ->where('store_id', $userStore->id)
                          ->firstOrFail();

        if ($product->image_url) {
            Storage::delete(str_replace('/storage', 'public', $product->image_url));
        }

        if ($product->delete()) {
            return redirect()->route('seller.produk.index')->with('success', 'Produk berhasil dihapus.');
        } else {
            return redirect()->route('seller.produk.index')->with('error', 'Gagal menghapus produk. Silakan coba lagi.');
        }
    }
}
