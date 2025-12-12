<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Category;
use App\Models\BannerEtalase;
use App\Models\Store; // <-- PENTING: Sudah ada
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
// use App\Models\User; // Tidak perlu jika relasi di 'Store' sudah benar
// use Illuminate\Support\Facades\Auth; // TIDAK DIPAKAI DI SINI, INI PANEL ADMIN

class MarketplaceController extends Controller
{
    /**
     * Menampilkan halaman daftar produk (Manajemen Produk).
     */
    public function index(Request $request)
    {
        $query = Marketplace::query()->with('category');

        // Filter Pencarian
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter Kategori
        if ($request->filled('category_filter') && $request->category_filter != 'all') {
            $query->where('category_id', $request->category_filter);
        }

        $products = $query->latest()->paginate(10)->withQueryString();
        
        // Ambil kategori untuk dropdown filter
        $categories = Category::all();
        $banner_estalase = BannerEtalase::latest()->get();
        
        // Ambil data toko untuk dropdown SELECT (saat create/edit)
        $stores = Store::all(); 
        
        return view('admin.marketplace.index', compact('products', 'categories', 'banner_estalase', 'stores'));
    }

// GANTI method create() yang saya berikan sebelumnya dengan ini:
    public function create()
    {
        // Karena kita pakai Modal di Index, jika user akses /create, lempar balik ke index
        return redirect()->route('admin.marketplace.index');
        
        // Catatan: Jika route resource Anda bernama 'stores', ganti jadi:
        // return redirect()->route('admin.stores.index');
    }

    /**
     * Menyimpan produk baru ke database.
     */
    public function store(Request $request)
    {
        // Validasi diperluas untuk mencocokkan database
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id', // Admin memilih toko
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'weight' => 'required|integer|min:1', 
            'length' => 'nullable|integer|min:1', 
            'width' => 'nullable|integer|min:1', 
            'height' => 'nullable|integer|min:1', 
            'jenis_barang' => 'required|integer', 
            'sku' => 'nullable|string|max:255|unique:marketplaces,sku',
            'tags' => 'nullable|string',
            'status' => 'required|in:active,inactive,pending',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        
        // Membuat slug unik
        $data['slug'] = $this->createUniqueSlug($data['name']);
        
        $data['is_flash_sale'] = $request->has('is_flash_sale');
        
        // ==========================================================
        // PERBAIKAN UTAMA (MENGISI SEMUA KOLOM NULL)
        // ==========================================================
        
        // 1. Ambil nama Kategori
        $category = Category::find($data['category_id']);
        if ($category) {
            $data['category'] = $category->name; // Menyimpan nama kategori
        }

        // 2. Ambil data Toko dan Penjual (Seller) berdasarkan 'store_id' dari form
        $store = Store::with('user')->find($data['store_id']);
        
        if (!$store) {
            // Seharusnya tidak terjadi karena sudah divalidasi, tapi untuk keamanan
            return response()->json(['errors' => ['store_id' => 'Toko yang dipilih tidak ditemukan.']], 422);
        }
        
        $seller = $store->user; // Asumsi ada relasi 'user' di model Store
        
        if (!$seller) {
            // Ini PENTING, sama seperti masalah Anda sebelumnya
            return response()->json(['errors' => ['store_id' => 'Toko ini tidak memiliki Penjual (User) yang valid.']], 422);
        }

        // 3. Isi semua kolom 'contekan' (denormalized data)
        $data['store_name']  = $store->name;
        $data['seller_name'] = $seller->nama_lengkap; // Sesuaikan nama kolom jika beda
        $data['seller_city'] = $store->regency;      // Sesuaikan nama kolom jika beda
        $data['seller_logo'] = $store->logo;         // Sesuaikan nama kolom jika beda
        $data['seller_wa']   = $seller->no_wa;        // Sesuaikan nama kolom jika beda
        
        // ==========================================================
        // AKHIR PERBAIKAN UTAMA
        // ==========================================================

        if ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('products', 'public');
            $data['image_url'] = $path;
        }
        
        Marketplace::create($data); // Data sudah lengkap
        
        return response()->json(['message' => 'Produk berhasil ditambahkan.'], 201);
    }

    /**
     * Mengambil data satu produk (JSON) untuk modal "Edit".
     */
    public function show(Marketplace $product)
    {
        // $product sekarang ditemukan via slug berkat Model
        return response()->json(['product' => $product]);
    }

    /**
     * Memperbarui produk yang ada di database.
     */
    public function update(Request $request, Marketplace $product)
    {
        // $product sudah ditemukan otomatis via slug
        
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id', 
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'weight' => 'required|integer|min:1', 
            'length' => 'nullable|integer|min:1', 
            'width' => 'nullable|integer|min:1', 
            'height' => 'nullable|integer|min:1', 
            'jenis_barang' => 'required|integer', 
            'sku' => 'nullable|string|max:255|unique:marketplaces,sku,' . $product->id,
            'tags' => 'nullable|string',
            'status' => 'required|in:active,inactive,pending',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $data = $validator->validated();
        $data['is_flash_sale'] = $request->has('is_flash_sale');
        
        // ==========================================================
        // PERBAIKAN UTAMA (MENGISI SEMUA KOLOM NULL)
        // ==========================================================
        
        // 1. Ambil nama Kategori
        $category = Category::find($data['category_id']);
        if ($category) {
            $data['category'] = $category->name; // Menyimpan nama kategori
        }

        // 2. Ambil data Toko dan Penjual (Seller) berdasarkan 'store_id' dari form
        $store = Store::with('user')->find($data['store_id']);
        
        if (!$store) {
            return response()->json(['errors' => ['store_id' => 'Toko yang dipilih tidak ditemukan.']], 422);
        }
        
        $seller = $store->user; // Asumsi ada relasi 'user' di model Store
        
        if (!$seller) {
            return response()->json(['errors' => ['store_id' => 'Toko ini tidak memiliki Penjual (User) yang valid.']], 422);
        }

        // 3. Isi semua kolom 'contekan' (denormalized data)
        $data['store_name']  = $store->name;
        $data['seller_name'] = $seller->nama_lengkap; // Sesuaikan nama kolom jika beda
        $data['seller_city'] = $store->regency;      // Sesuaikan nama kolom jika beda
        $data['seller_logo'] = $store->logo;         // Sesuaikan nama kolom jika beda
        $data['seller_wa']   = $seller->no_wa;        // Sesuaikan nama kolom jika beda
        
        // ==========================================================
        // AKHIR PERBAIKAN UTAMA
        // ==========================================================

        // Perbarui slug jika nama berubah
        if ($product->name !== $data['name']) {
            $data['slug'] = $this->createUniqueSlug($data['name']);
        }

        if ($request->hasFile('image_url')) {
            // Hapus gambar lama jika ada
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            // Simpan gambar baru
            $path = $request->file('image_url')->store('products', 'public');
            $data['image_url'] = $path;
        }

        $product->update($data); // Data sudah lengkap
        
        return response()->json(['message' => 'Produk berhasil diperbarui.']);
    }

    /**
     * Menghapus produk dari database.
     */
    public function destroy(Marketplace $product)
    {
        // $product sudah ditemukan otomatis via slug
        
        // Hapus gambar terkait
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }
        
        $product->delete();
        
        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }

    /**
     * Helper function to create a unique slug.
     */
    private function createUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $count = Marketplace::where('slug', 'LIKE', "{$slug}%")->count();
        if ($count > 0) {
            // Cek apakah slug asli sudah ada
            $originalExists = Marketplace::where('slug', $slug)->exists();
            if ($originalExists || $count > 1) {
                 return "{$slug}-" . ($count + 1); // atau Str::random(5)
            }
        }
        return $slug;
    }
}