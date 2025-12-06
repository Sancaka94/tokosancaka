<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DigiflazzService;
use App\Models\Setting;
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use App\Models\BannerEtalase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache; // <-- PASTIKAN INI ADA

class PpobController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }


    private function getWebLogo()
    {
        try {
            $setting = Setting::where('key', 'logo')->first();
            return $setting ? $setting->value : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * FUNGSI UTAMA: MENANGANI SEMUA HALAMAN KATEGORI
     */
    public function index($slug = 'pulsa')
    {
        // ============================================================
        // 1. AMBIL DATA PENDUKUNG (LOGO, BANNER, SETTING)
        // ============================================================
        
        // Ambil Logo (SOLUSI ERROR UNDEFINED VARIABLE $weblogo)
        $weblogo = $this->getWebLogo();

        // Ambil Banner Slider
        $banners = BannerEtalase::latest()->get(); 
        
        // Ambil Setting Banner Promo
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');

        // ============================================================
        // 2. MAPPING JUDUL & KATEGORI DATABASE
        // ============================================================
        $categoryMap = [
            // [Slug URL]       => ['Judul Halaman', 'Kata Kunci di Database']
            'pulsa'             => ['title' => 'Pulsa Reguler',      'db_cat' => 'Pulsa'],
            'data'              => ['title' => 'Paket Data',         'db_cat' => 'Data'],
            'pln-token'         => ['title' => 'Token PLN',          'db_cat' => 'PLN'], // Biasanya 'PLN' atau 'Token'
            'games'             => ['title' => 'Voucher Games',      'db_cat' => 'Games'],
            'voucher'           => ['title' => 'Voucher Digital',    'db_cat' => 'Voucher'],
            'e-money'           => ['title' => 'E-Money',            'db_cat' => 'E-Money'],
            'paket-sms-telpon'  => ['title' => 'Paket SMS & Telpon', 'db_cat' => 'SMS'],
            'masa-aktif'        => ['title' => 'Masa Aktif',         'db_cat' => 'Masa Aktif'],
            'streaming'         => ['title' => 'Streaming Premium',  'db_cat' => 'Streaming'],
            'tv'                => ['title' => 'TV Prabayar',        'db_cat' => 'TV'],
            'gas'               => ['title' => 'Token Gas',          'db_cat' => 'Gas'],
            'esim'              => ['title' => 'eSIM',               'db_cat' => 'eSIM'],
            
            // INTERNATIONAL
            'china-topup'       => ['title' => 'China TOPUP',        'db_cat' => 'China'],
            'malaysia-topup'    => ['title' => 'Malaysia TOPUP',     'db_cat' => 'Malaysia'],
            'philippines-topup' => ['title' => 'Philippines TOPUP',  'db_cat' => 'Philippines'],
            'singapore-topup'   => ['title' => 'Singapore TOPUP',    'db_cat' => 'Singapore'],
            'thailand-topup'    => ['title' => 'Thailand TOPUP',     'db_cat' => 'Thailand'],
            'vietnam-topup'     => ['title' => 'Vietnam TOPUP',      'db_cat' => 'Vietnam'],

            // PASCABAYAR
            'pln-pascabayar'       => ['title' => 'PLN Pascabayar',       'db_cat' => 'PLN Postpaid'],
            'pdam'                 => ['title' => 'PDAM',                 'db_cat' => 'PDAM'],
            'hp-pascabayar'        => ['title' => 'HP Pascabayar',        'db_cat' => 'HP Postpaid'],
            'internet-pascabayar'  => ['title' => 'Internet Pascabayar',  'db_cat' => 'Internet'],
            'bpjs-kesehatan'       => ['title' => 'BPJS Kesehatan',       'db_cat' => 'BPJS'],
            'multifinance'         => ['title' => 'Multifinance',         'db_cat' => 'Multifinance'],
            'pbb'                  => ['title' => 'Pajak PBB',            'db_cat' => 'PBB'],
            'gas-negara'           => ['title' => 'Gas Negara (PGN)',     'db_cat' => 'Gas Postpaid'],
            'tv-pascabayar'        => ['title' => 'TV Pascabayar',        'db_cat' => 'TV Postpaid'],
            'samsat'               => ['title' => 'E-Samsat',             'db_cat' => 'Samsat'],
            'pln-nontaglis'        => ['title' => 'PLN Non-Taglis',       'db_cat' => 'Non Taglis'],
        ];

        // Fallback jika slug tidak dikenal
        $mapData = $categoryMap[$slug] ?? ['title' => ucwords(str_replace('-', ' ', $slug)), 'db_cat' => $slug];
        
        $pageTitle = $mapData['title'];
        $dbCategoryKeyword = $mapData['db_cat'];

        // ============================================================
        // 3. KONFIGURASI TAMPILAN (INPUT LABEL & PASCABAYAR)
        // ============================================================
        $postpaidSlugs = [
            'pln-pascabayar', 'pdam', 'hp-pascabayar', 'internet-pascabayar', 
            'bpjs-kesehatan', 'multifinance', 'pbb', 'gas-negara', 
            'tv-pascabayar', 'samsat', 'bpjs-ketenagakerjaan', 'pln-nontaglis'
        ];
        
        $isPostpaid = in_array($slug, $postpaidSlugs);

        // Default Config
        $inputLabel       = 'Nomor Telepon / Tujuan';
        $inputPlaceholder = 'Contoh: 0812xxxx';
        $icon             = 'fa-mobile-alt';

        // Custom Config per Kategori
        if (str_contains($slug, 'pln')) {
            $inputLabel       = 'ID Pelanggan / No. Meter';
            $inputPlaceholder = 'Contoh: 5300xxxx';
            $icon             = 'fa-bolt';
        } elseif (str_contains($slug, 'bpjs')) {
            $inputLabel       = 'Nomor VA BPJS';
            $inputPlaceholder = 'Contoh: 88888xxxx';
            $icon             = 'fa-heartbeat';
        } elseif (str_contains($slug, 'pdam')) {
            $inputLabel       = 'ID Pelanggan PDAM';
            $inputPlaceholder = 'Nomor Pelanggan...';
            $icon             = 'fa-faucet';
        } elseif (str_contains($slug, 'game')) {
            $inputLabel       = 'User ID Game';
            $inputPlaceholder = 'Masukkan ID Game...';
            $icon             = 'fa-gamepad';
        } elseif ($slug == 'e-money') {
            $icon             = 'fa-wallet';
        }

        $pageInfo = [
            'slug'        => $slug,
            'title'       => $pageTitle,
            'is_postpaid' => $isPostpaid,
            'input_label' => $inputLabel,
            'input_place' => $inputPlaceholder,
            'icon'        => $icon,
        ];

        // ============================================================
        // 4. QUERY PRODUK (AGAR TIDAK KOSONG)
        // ============================================================
        // Menggunakan LIKE agar lebih fleksibel. 
        // Contoh: Keyword 'Pulsa' akan cocok dengan kategori 'Pulsa Reguler', 'Pulsa Transfer'
        $products = PpobProduct::where('category', 'LIKE', "%{$dbCategoryKeyword}%")
            ->where('buyer_product_status', true)
            ->where('seller_product_status', true)
            ->orderBy('sell_price', 'asc')
            ->get();

        // Ambil list Brand untuk filter operator
        $brands = $products->pluck('brand')->unique()->values();

        // ============================================================
        // 5. RETURN VIEW
        // ============================================================
        
        // Cek apakah Admin/Seller/Member
        $prefix = request()->segment(1);
        if ($prefix == 'admin' || (auth()->check() && auth()->user()->hasRole('Admin'))) {
             // Pastikan view admin menerima variabel yang sama
             return view('admin.ppob.index', compact('pageInfo', 'products', 'banners', 'settings', 'weblogo', 'brands')); 
        }

        // Return ke Layout Marketplace (Public)
        return view('etalase.ppob.category', compact(
            'pageInfo', 
            'products', 
            'banners', 
            'settings', 
            'weblogo',   // <--- INI PENTING
            'brands'     // <--- INI PENTING
        ));
    }

    // =================================================================
    // FUNGSI API PENDUKUNG (CHECK BILL & TRANSAKSI)
    // =================================================================

    // =================================================================
// FUNGSI API PENDUKUNG (CHECK BILL & TRANSAKSI)
// =================================================================

public function checkBill(Request $request)
{
    // Ambil data dari request (asumsi ini dari form data atau URL-encoded)
    $customerNo = $request->input('customer_no');
    $sku = $request->input('sku');
    
    $request->validate(['customer_no' => 'required', 'sku' => 'required']);
    $refId = 'INQ-' . time() . rand(100,999);

    // =========================================================
    // ATUR KREDENSIAL DIGIFLAZZ (Production)
    // =========================================================
    $username = 'mihetiDVGdeW'; 
    $apiKeyProd = '1f48c69f-8676-5d56-a868-10a46a69f9b7'; // Kunci Production
    $testingMode = false; // Mode Production

    // Set kredensial di service sebelum memanggil inquiryPasca
    $this->digiflazz->setCredentials($username, $apiKeyProd, $testingMode);
    // =========================================================
    
    try {
        // Panggil Service. Service akan menggunakan API Key yang baru di-set
        $response = $this->digiflazz->inquiryPasca($sku, $customerNo, $refId);
        
        if (isset($response['data']) && ($response['data']['rc'] === '00' || $response['data']['status'] === 'Sukses' || $response['data']['status'] === 'Pending')) {
            $data = $response['data'];
            return response()->json([
                'status' => 'success',
                'customer_name' => $data['customer_name'],
                'customer_no' => $data['customer_no'],
                'admin_fee'  => $data['admin'],
                'amount' => $data['selling_price'], 
                'ref_id' => $refId,
                'desc' => $data['desc'] ?? []
            ]);
        }
        // Tambahkan pengecekan jika $response['data'] tidak ada sama sekali
        $message = $response['data']['message'] ?? ($response['message'] ?? 'Tagihan tidak ditemukan atau Signature salah.'); 
        return response()->json(['status' => 'error', 'message' => $message]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
    }
}

    public function checkPlnPrabayar(Request $request)
    {
        $request->validate(['customer_no' => 'required']);
        try {
            $response = $this->digiflazz->inquiryPln($request->customer_no);
            if (isset($response['data']) && ($response['data']['rc'] === '00' || $response['data']['status'] === 'Sukses')) {
                return response()->json([
                    'status' => 'success', 
                    'name' => $response['data']['name'], 
                    'segment_power' => $response['data']['segment_power']
                ]);
            }
            return response()->json(['status' => 'error', 'message' => 'ID Pelanggan tidak ditemukan.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate(['buyer_sku_code' => 'required', 'customer_no' => 'required']);
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        $product = PpobProduct::where('buyer_sku_code', $request->buyer_sku_code)->first();
        if (!$product) return back()->with('error', 'Produk tidak ditemukan.');
        
        // Cek Saldo
        if ($user->saldo < $product->sell_price) {
            return back()->with('error', 'Saldo tidak cukup. Silakan Top Up.');
        }

        $refId = 'TRX-' . time() . rand(100,999);
        
        DB::beginTransaction();
        try {
            // Potong Saldo
            $user->decrement('saldo', $product->sell_price);
            
            // Simpan Transaksi
            $trx = PpobTransaction::create([
                'user_id' => $user->id_pengguna ?? $user->id,
                'order_id' => $refId,
                'buyer_sku_code' => $product->buyer_sku_code,
                'customer_no' => $request->customer_no,
                'price' => $product->price,
                'selling_price' => $product->sell_price,
                'profit' => $product->sell_price - $product->price,
                'status' => 'Pending',
                'message' => 'Sedang diproses...'
            ]);

            // Hit API
            $response = $this->digiflazz->transaction($product->buyer_sku_code, $request->customer_no, $refId, $product->sell_price);
            
            if (isset($response['data']) && $response['data']['status'] !== 'Gagal') {
                $trx->update(['status' => $response['data']['status'], 'sn' => $response['data']['sn'] ?? '']);
                DB::commit();
                return back()->with('success', 'Transaksi Berhasil Diproses!');
            } else {
                // Gagal -> Refund
                $user->increment('saldo', $product->sell_price);
                $trx->update(['status' => 'Gagal', 'message' => $response['data']['message'] ?? 'Gagal dari provider']);
                DB::commit();
                return back()->with('error', 'Transaksi Gagal: ' . ($response['data']['message'] ?? 'Unknown Error'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error Sistem: ' . $e->getMessage());
        }
    }

    // FILE: App\Http\Controllers\PpobController.php

public function sync()
{
    // ⭐ BARU: Cek apakah data Pricelist sudah disinkronkan dalam 6 jam terakhir
    $cacheKey = 'digiflazz_pricelist_synced';
    $ttlInMinutes = 60 * 6; // 6 jam

    if (Cache::has($cacheKey)) {
        // Jika masih dalam periode 6 jam, hentikan proses sync dan beri notifikasi
        return redirect()->route('admin.ppob.index')->with(
             'error', 
             'Sinkronisasi harga sudah dilakukan baru-baru ini. Coba lagi setelah 6 jam.'
         );
    }
    
    // Asumsi: Route di web.php sudah memastikan user adalah Admin dan sudah login.
    if (!Auth::check()) {
         return redirect()->route('login'); 
    }

    $productsPrepaid = [];
    $productsPostpaid = [];

    try {
        // 1. Ambil Produk Prabayar
        $productsPrepaid = $this->digiflazz->getPriceList('prepaid'); 
        
        // 2. Ambil Produk Pascabayar
        $productsPostpaid = $this->digiflazz->getPriceList('postpaid');

        // 3. Gabungkan hasilnya
        $productsPrepaid = is_array($productsPrepaid) ? $productsPrepaid : [];
        $productsPostpaid = is_array($productsPostpaid) ? $productsPostpaid : [];
        
        $productArray = array_merge($productsPrepaid, $productsPostpaid);

        // 4. Lanjutkan proses jika array hasil gabungan valid dan tidak kosong
        if (!empty($productArray)) {
            
            DB::beginTransaction();

            $insertedCount = 0;
            $updatedCount = 0;

            foreach ($productArray as $product) {
                
                // FIX TypeError: Pastikan $product adalah array
                if (!is_array($product)) {
                    Log::warning('Skipping invalid product data during sync: ' . (string)$product);
                    continue; 
                }

                $localProduct = PpobProduct::firstOrNew(['buyer_sku_code' => $product['buyer_sku_code']]);

                // Update atau isi data produk
                $localProduct->fill([
                    'product_name'          => $product['product_name'],
                    'category'              => $product['category'],
                    'brand'                 => $product['brand'],
                    'type'                  => $product['type'] ?? null,
                    'price'                 => $product['price'] ?? 0,
                    'sell_price'            => $localProduct->sell_price ?? ($product['price'] ?? 0) + 1000, 
                    'admin'                 => $product['admin'] ?? 0, 
                    'status'                => $product['status'] ?? null,
                    'buyer_sku_code'        => $product['buyer_sku_code'],
                    'desc'                  => $product['desc'] ?? null,
                    'buyer_product_status'  => $product['buyer_product_status'],
                    'seller_product_status' => $localProduct->exists ? $localProduct->seller_product_status : true, 
                ]);
                
                if ($localProduct->exists) {
                    $localProduct->save();
                    $updatedCount++;
                } else {
                    $localProduct->save();
                    $insertedCount++;
                }
            }
            
            DB::commit(); 
            
            // ⭐ BARU: Set Cache jika sinkronisasi berhasil
            Cache::put($cacheKey, now(), $ttlInMinutes); 
            
            // FIX Redirect Sukses
            return redirect()->route('admin.ppob.index')->with(
                'success', 
                "Sinkronisasi Berhasil. Ditambahkan: $insertedCount, Diperbarui: $updatedCount."
            );

        } else {
            // Jika Sinkronisasi Gagal (Array Kosong):
            Log::error('Digiflazz Sync Failed: Response Empty or Invalid');
            
            // FIX Redirect Gagal (Respons Kosong)
            return redirect()->route('admin.ppob.index')->with(
                'error', 
                'Gagal mengambil data dari Digiflazz. Respons kosong.'
            );
        }

    } catch (\Exception $e) {
        DB::rollBack(); 
        Log::error('PPOB Sync Exception: ' . $e->getMessage());
        
        // FIX Redirect Fatal Error
        return redirect()->route('admin.ppob.index')->with(
             'error', 
             'Error Sistem saat sinkronisasi: ' . $e->getMessage()
         );
    }
}

}