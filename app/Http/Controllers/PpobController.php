<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DigiflazzService;
use App\Models\Setting;
use App\Models\Product;
use App\Models\BannerEtalase;
use App\Models\Category;
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PpobController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    private function getWebLogo()
    {
        $setting = Setting::where('key', 'logo')->first();
        return $setting ? $setting->value : null;
    }

    /**
     * 1. Halaman Utama PPOB (Dashboard)
     * URL: /admin/digital  atau  /etalase/ppob/digital
     */
    public function index()
    {
        $weblogo = $this->getWebLogo();
        
        // --- 1. LOGIKA CERDAS DETEKSI PENGUNJUNG ---
        $prefix = request()->segment(1);

        // Jika URL diawali 'admin' ATAU user yang login adalah Admin
        // Maka arahkan ke Dashboard Admin
        if ($prefix == 'admin' || (auth()->check() && auth()->user()->hasRole('Admin'))) {
            // Pastikan view ini ada: resources/views/admin/ppob/index.blade.php
            return view('admin.ppob.index'); 
        }

        // Jika Pengunjung Biasa (Public / Customer)
        // Pastikan view ini ada: resources/views/ppob/index.blade.php
        return view('ppob.index');
    }

    /**
     * 2. Halaman Kategori (Pulsa, Data, Token, dll)
     * URL: /admin/digital/{slug}  atau  /etalase/ppob/digital/{slug}
     */
   public function category($slug)
    {
        $weblogo = $this->getWebLogo();

        // -----------------------------------------------------------
        // 1. TAMBAHAN PENTING: DEFINISI BANNERS & SETTINGS
        // (Agar error Undefined variable hilang)
        // -----------------------------------------------------------
        
        // OPSI A: Jika Anda punya Model Banner, aktifkan baris ini:
        //$banners = \App\Models\Banner::where('status', 'active')->get();
        //$settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        $banners = BannerEtalase::latest()->get(); 
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');

        // -----------------------------------------------------------

        // 1. Mapping Slug URL ke Kategori Database
        // Pastikan nama kategori di array value SAMA dengan di database 'ppob_products' -> column 'category'
        $categoriesMap = [
            'pulsa'          => ['Pulsa'],
            'data'           => ['Data'],
            'pln-token'      => ['PLN', 'Token PLN'],
            // Sesuaikan slug dengan link di view sebelumnya
            'pln-pascabayar' => ['PLN Pascabayar', 'PLN Postpaid', 'Tagihan PLN'],       
            'pdam'           => ['PDAM', 'Air PDAM'], // [BARU] Tambahan PDAM
            'e-money'        => ['E-Money', 'E-Wallet'],
            'voucher-game'   => ['Games', 'Voucher Game'],
            'streaming'      => ['TV', 'Streaming'],
        ];

        // Validasi Slug
        if (!array_key_exists($slug, $categoriesMap)) {
            abort(404);
        }

        $dbCategories = $categoriesMap[$slug];

        // 2. Konfigurasi Tampilan Halaman (Default)
        $pageInfo = [
            'title'             => ucfirst(str_replace('-', ' ', $slug)),
            'slug'              => $slug,
            'input_label'       => 'Nomor Handphone',
            'input_place'       => 'Contoh: 0812xxxx',
            'icon'              => 'fa-mobile-alt',
            'is_postpaid'       => false, // Default Prabayar
            'has_region_select' => false  // Default tidak butuh pilih wilayah
        ];

        // Logika Icon, Input Label & Tipe Transaksi
        if ($slug == 'pln-token') {
            $pageInfo['input_label'] = 'Nomor Meter / ID Pelanggan';
            $pageInfo['input_place'] = 'Contoh: 141234567890';
            $pageInfo['icon']        = 'fa-bolt';

        } elseif ($slug == 'e-money') {
            $pageInfo['icon']        = 'fa-wallet';

        } elseif ($slug == 'voucher-game') {
            $pageInfo['input_label'] = 'ID Pemain (User ID)';
            $pageInfo['input_place'] = 'Masukkan ID Game';
            $pageInfo['icon']        = 'fa-gamepad';

        // [BARU] Konfigurasi PLN PASCABAYAR
        } elseif ($slug == 'pln-pascabayar') {
            $pageInfo['title']       = 'PLN Pascabayar';
            $pageInfo['input_label'] = 'ID Pelanggan / Nomor Meter';
            $pageInfo['input_place'] = 'Contoh: 53xxxxxxxxx';
            $pageInfo['icon']        = 'fa-file-invoice-dollar';
            $pageInfo['is_postpaid'] = true; // Mode Cek Tagihan

        // [BARU] Konfigurasi PDAM
        } elseif ($slug == 'pdam') {
            $pageInfo['title']       = 'Tagihan Air PDAM';
            $pageInfo['input_label'] = 'Nomor Pelanggan'; // PDAM butuh ID Pelanggan
            $pageInfo['input_place'] = 'Masukan Nomor Pelanggan';
            $pageInfo['icon']        = 'fa-faucet';
            $pageInfo['is_postpaid'] = true; // Mode Cek Tagihan
            $pageInfo['has_region_select'] = true; // Perlu dropdown wilayah
        }


        // 3. Ambil Produk dari DB
        $query = PpobProduct::whereIn('category', $dbCategories)
            ->where('buyer_product_status', true)
            ->where('seller_product_status', true);

        // GANTI MENJADI (Mengurutkan sesuai Brand/Nama agar konsisten dengan Admin)
        if ($slug == 'pdam') {
            $query->orderBy('brand', 'asc');
        } else {
            // Ubah ini agar tidak loncat-loncat harganya
            $query->orderBy('sell_price', 'asc'); 
            // ATAU jika ingin urut abjad:
            // $query->orderBy('product_name', 'asc');
        }

        $products = $query->get();

        // Ambil list Brand (Provider/Wilayah) untuk filter/dropdown
        $brands = $products->pluck('brand')->unique()->values();
        
        $data = compact('products', 'brands', 'weblogo', 'pageInfo', 'banners', 'settings');

        // --- 4. LOGIKA CERDAS DETEKSI VIEW ---
        $prefix = request()->segment(1); 

        // A. JIKA ADMIN
        if ($prefix == 'admin' || (auth()->check() && auth()->user()->hasRole('Admin'))) {
            return view('admin.ppob.category', $data); 
        }

        // B. JIKA SELLER
        if ($prefix == 'seller' || (auth()->check() && auth()->user()->hasRole('Seller'))) {
            return view('seller.ppob.category', $data);
        }

        // C. JIKA MEMBER / CUSTOMER
        if ($prefix == 'member' || $prefix == 'customer' || (auth()->check() && auth()->user()->hasRole('Customer'))) {
            return view('customer.ppob.category', $data);
        }

        // D. FALLBACK (ETALASE PUBLIC)
        // View utama: resources/views/etalase/ppob/category.blade.php
        return view('etalase.ppob.category', $data);
    }

    /**
     * 3. Sinkronisasi Data (Update Harga)
     */
    public function sync()
    {
        $success = $this->digiflazz->syncProducts();

        if ($success) {
            return redirect()->back()->with('success', 'Daftar harga berhasil diperbarui dari pusat!');
        } else {
            return redirect()->back()->with('error', 'Gagal mengambil data dari Digiflazz.');
        }
    }

    /**
     * 4. Cek Saldo Admin (AJAX)
     */
    public function cekSaldo()
    {
        $result = $this->digiflazz->checkDeposit();

        if (request()->ajax()) {
            $result['formatted'] = 'Rp ' . number_format($result['deposit'], 0, ',', '.');
            return response()->json($result);
        }
        return redirect()->back();
    }

    /**
     * 5. Proses Transaksi Prabayar (Checkout)
     */
    public function store(Request $request)
    {
        $request->validate([
            'buyer_sku_code' => 'required|exists:ppob_products,buyer_sku_code',
            'customer_no'    => 'required|numeric|digits_between:9,20',
        ]);

        $user = Auth::user();
        $sku = $request->buyer_sku_code;
        $noHp = $request->customer_no;

        $product = PpobProduct::where('buyer_sku_code', $sku)->first();

        if ($user->saldo < $product->sell_price) {
            return redirect()->back()->with('error', 'Saldo tidak cukup. Silakan Top Up.');
        }

        $refId = 'TRX-' . time() . rand(100, 999);

        DB::beginTransaction();
        try {
            // Potong Saldo
            $user->decrement('saldo', $product->sell_price);

            // Catat Transaksi
            $trx = PpobTransaction::create([
                'user_id' => $user->id_pengguna, // Sesuaikan dengan PK tabel user Anda
                'order_id' => $refId,
                'buyer_sku_code' => $sku,
                'customer_no' => $noHp,
                'price' => $product->price,
                'selling_price' => $product->sell_price,
                'profit' => $product->sell_price - $product->price,
                'status' => 'Pending',
                'message' => 'Sedang diproses...',
            ]);

            // Hit API Digiflazz
            $maxPrice = (int) $product->sell_price; 
            $response = $this->digiflazz->transaction($sku, $noHp, $refId, $maxPrice);

            if (isset($response['data'])) {
                $data = $response['data'];
                
                if ($data['status'] == 'Gagal') {
                    // Refund jika gagal langsung
                    $user->increment('saldo', $product->sell_price);
                    $trx->update(['status' => 'Gagal', 'message' => $data['message'], 'sn' => $data['sn'] ?? null]);
                    DB::commit();
                    return redirect()->back()->with('error', 'Transaksi Gagal: ' . $data['message']);
                } else {
                    // Sukses / Pending
                    $trx->update(['status' => $data['status'], 'message' => $data['message'], 'sn' => $data['sn'] ?? null]);
                    DB::commit();
                    return redirect()->back()->with('success', 'Transaksi Diproses! SN: ' . ($data['sn'] ?? 'Menunggu...'));
                }
            } else {
                // Error Koneksi -> Refund
                $user->increment('saldo', $product->sell_price);
                $trx->update(['status' => 'Gagal', 'message' => 'Koneksi ke server gagal']);
                DB::commit();
                return redirect()->back()->with('error', 'Gagal terhubung ke provider.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Transaksi Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }

   public function checkBill(Request $request)
    {
        $request->validate([
            'customer_no' => 'required', 
            'sku' => 'required' 
        ]);

        $refId = 'INQ-' . time() . rand(100,999);

        try {
            if (!$this->digiflazz) {
                throw new \Exception('Service Digiflazz belum di-inject.');
            }

            // Panggil Service
            $response = $this->digiflazz->inquiryPasca($request->sku, $request->customer_no, $refId);

            // Cek Response
            if (isset($response['data'])) {
                $data = $response['data'];
                
                if ($data['rc'] === '00' || $data['status'] === 'Sukses' || $data['status'] === 'Pending') {
                    return response()->json([
                        'status' => 'success',
                        'customer_name' => $data['customer_name'],
                        'customer_no' => $data['customer_no'],
                        'admin_fee' => $data['admin'],
                        'amount' => $data['selling_price'],
                        'ref_id' => $refId,
                        'desc' => $data['desc'] ?? []
                    ]);
                } else {
                    // Log alasan gagal dari API (Misal: Saldo kurang, ID salah)
                    Log::warning("⚠️ [CheckBill Gagal] RefID: $refId. Pesan: " . ($data['message'] ?? 'Unknown'));
                    
                    return response()->json([
                        'status' => 'error', 
                        'message' => $data['message'] ?? 'Tagihan tidak ditemukan.'
                    ]);
                }
            }

            // Jika sampai sini, berarti response API aneh (tidak ada key 'data')
            Log::error("❌ [CheckBill Invalid Response] RefID: $refId. Raw: " . json_encode($response));
            return response()->json(['status' => 'error', 'message' => 'Respon server vendor tidak valid.']);

        } catch (\Exception $e) {
            // Log Error Sistem (Syntax error, codingan salah, dll)
            Log::error("🔥 [CheckBill Exception] " . $e->getMessage() . " on Line " . $e->getLine());
            
            return response()->json([
                'status' => 'error', 
                'message' => 'System Error (Cek Log): ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * 7. AJAX: Cek ID Pelanggan PLN Prabayar
     */
    public function checkPlnPrabayar(Request $request)
    {
        $request->validate(['customer_no' => 'required']);

        $response = $this->digiflazz->inquiryPln($request->customer_no);

        if (isset($response['data'])) {
            $data = $response['data'];
            
            // Cek status RC 00 (Sukses)
            if ($data['rc'] === '00' || $data['status'] === 'Sukses') {
                return response()->json([
                    'status' => 'success',
                    'name' => $data['name'],
                    'meter_no' => $data['meter_no'] ?? $data['customer_no'],
                    'segment_power' => $data['segment_power'], 
                    'subscriber_id' => $data['subscriber_id'] ?? '-'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $data['message'] ?? 'Nomor meter tidak ditemukan.'
                ]);
            }
        }

        return response()->json(['status' => 'error', 'message' => 'Gagal terhubung ke server PLN.']);
    }

    /**
     * Menampilkan Halaman Kategori Spesifik
     * URL: /layanan/pln-pascabayar, /layanan/pulsa, dll
     */
    public function showCategory($slug)
    {
        // 1. Ambil Data Umum (Banner & Setting)
        try {
            $banners = BannerEtalase::latest()->get();
            $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key')->toArray();
        } catch (\Exception $e) {
            $banners = collect([]);
            $settings = [];
        }

        // 2. Konfigurasi Halaman & Mode
        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt',
            'is_postpaid' => false, // Default Prabayar
        ];

        // 3. Deteksi Mode Pascabayar (Cek Tagihan)
        if ($slug == 'pln-pascabayar') {
            $pageInfo['title']       = 'Cek Tagihan PLN';
            $pageInfo['input_label'] = 'ID Pelanggan PLN';
            $pageInfo['input_place'] = 'Contoh: 53xxxx';
            $pageInfo['icon']        = 'fa-file-invoice-dollar';
            $pageInfo['is_postpaid'] = true; 

        } elseif ($slug == 'pdam') {
            $pageInfo['title']       = 'Cek Tagihan PDAM';
            $pageInfo['input_label'] = 'ID Pelanggan PDAM';
            $pageInfo['input_place'] = 'Nomor Pelanggan';
            $pageInfo['icon']        = 'fa-faucet';
            $pageInfo['is_postpaid'] = true; 
        
        } elseif ($slug == 'bpjs') {
            $pageInfo['title']       = 'Cek Tagihan BPJS';
            $pageInfo['input_label'] = 'Nomor VA Keluarga';
            $pageInfo['input_place'] = '88888xxxx';
            $pageInfo['icon']        = 'fa-heart-pulse';
            $pageInfo['is_postpaid'] = true; 
        }

        // 4. Ambil Produk (Jika Mode Prabayar)
        $products = collect([]);
        $categories = collect([]);
        
        if (!$pageInfo['is_postpaid']) {
            $products = PpobProduct::where('seller_product_status', 1)
                ->orderBy('category', 'asc')
                ->orderBy('brand', 'asc')
                ->orderBy('sell_price', 'asc')
                ->get();
            $categories = $products->pluck('category')->unique()->values();
        }

        // 5. Return View yang sama dengan Pricelist
        return view('public.pricelist', compact('products', 'categories', 'banners', 'settings', 'pageInfo'));
    }
}