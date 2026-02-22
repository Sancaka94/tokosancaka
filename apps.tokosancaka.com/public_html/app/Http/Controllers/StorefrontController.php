<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;

class StorefrontController extends Controller
{
    /**
     * Helper: Validasi Subdomain & Ambil Data Toko (Tenant)
     */
    private function getActiveTenant($subdomain)
    {
        // Cari toko yang aktif berdasarkan subdomain. Jika tidak ada/nonaktif, otomatis muncul halaman 404.
        return Tenant::where('subdomain', $subdomain)->where('status', 'active')->firstOrFail();
    }

    /**
     * 1. HALAMAN UTAMA (Katalog Produk & Pencarian)
     */
    public function index(Request $request, $subdomain)
    {
        $tenant = $this->getActiveTenant($subdomain);

        // Ambil Kategori Aktif
        $categories = Category::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        // Ambil Produk Aktif & Berstok
        $query = Product::where('tenant_id', $tenant->id)
                        ->where('stock_status', 'available')
                        ->where('stock', '>', 0);

        // Jika ada pencarian dari Header
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $products = $query->latest()->paginate(12);

        return view('storefront.index', compact('tenant', 'subdomain', 'categories', 'products'));
    }

    /**
     * 2. FILTER BERDASARKAN KATEGORI
     */
    public function category($subdomain, $slug)
    {
        $tenant = $this->getActiveTenant($subdomain);
        $categories = Category::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        // Cari kategori berdasarkan slug
        $selectedCategory = Category::where('tenant_id', $tenant->id)->where('slug', $slug)->firstOrFail();

        // Ambil Produk berdasarkan ID Kategori
        $products = Product::where('tenant_id', $tenant->id)
                        ->where('category_id', $selectedCategory->id)
                        ->where('stock_status', 'available')
                        ->where('stock', '>', 0)
                        ->latest()
                        ->paginate(12);

        return view('storefront.index', compact('tenant', 'subdomain', 'categories', 'products', 'selectedCategory'));
    }

    /**
     * 3. HALAMAN KERANJANG (Cart)
     */
    public function cart($subdomain)
    {
        $tenant = $this->getActiveTenant($subdomain);

        return view('storefront.cart', compact('tenant', 'subdomain'));
    }

    /**
     * 4. HALAMAN CHECKOUT (Isi Alamat & Ekspedisi)
     */
    public function checkout($subdomain)
    {
        $tenant = $this->getActiveTenant($subdomain);

        return view('storefront.checkout', compact('tenant', 'subdomain'));
    }

    /**
     * 5. HALAMAN SUKSES (Setelah Order Diproses)
     */
    public function success($subdomain, $orderNumber)
    {
        $tenant = $this->getActiveTenant($subdomain);

        // Ambil data order untuk ditampilkan
        $order = Order::where('tenant_id', $tenant->id)
                      ->where('order_number', $orderNumber)
                      ->firstOrFail();

        return view('storefront.sukses', compact('tenant', 'subdomain', 'order'));
    }

        public function productDetail($subdomain, $slug)
    {
        $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->firstOrFail();
        $categories = \App\Models\Category::where('tenant_id', $tenant->id)->get(); // <--- WAJIB ADA JIKA DIPAKAI DI LAYOUT

        $product = \App\Models\Product::with('variants')
            ->where('tenant_id', $tenant->id)
            ->where(function($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        return view('storefront.product-detail', compact('product', 'subdomain', 'tenant', 'categories')); // <--- PASTIKAN MASUK SINI
    }
}
