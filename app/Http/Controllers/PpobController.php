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
use Illuminate\Support\Facades\Cache;
use App\Services\FonnteService;
use App\Models\User; // Alias Model User ke Users (tabel Pengguna)
use Illuminate\Support\Str;

class PpobController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    // =================================================================
    // HELPER FUNCTIONS (WA, Logo)
    // =================================================================

    /**
     * Helper untuk membersihkan dan memformat nomor HP menjadi 62xxxx.
     */
    private function _sanitizePhoneNumber(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return null;

        if (Str::startsWith($phone, '08')) {
            return '62' . substr($phone, 1);
        }
        if (Str::startsWith($phone, '62')) {
            return $phone;
        }
        if (strlen($phone) > 8 && !Str::startsWith($phone, '0')) {
            return '62' . $phone;
        }
        return $phone;
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
    
    // =================================================================
    // FUNGSI UTAMA & API (index, checkBill, checkPlnPrabayar)
    // =================================================================

    /**
     * FUNGSI UTAMA: MENANGANI SEMUA HALAMAN KATEGORI
     */
    public function index($slug = 'pulsa')
    {
        // 1. AMBIL DATA PENDUKUNG (LOGO, BANNER, SETTING)
        $weblogo = $this->getWebLogo();
        $banners = BannerEtalase::latest()->get(); 
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');

        // 2. MAPPING JUDUL & KATEGORI DATABASE
        $categoryMap = [
            'pulsa' => ['title' => 'Pulsa Reguler', 'db_cat' => 'Pulsa'],
            'data' => ['title' => 'Paket Data', 'db_cat' => 'Data'],
            'pln-token' => ['title' => 'Token PLN', 'db_cat' => 'PLN'],
            'games' => ['title' => 'Voucher Games', 'db_cat' => 'Games'],
            'voucher' => ['title' => 'Voucher Digital', 'db_cat' => 'Voucher'],
            'e-money' => ['title' => 'E-Money', 'db_cat' => 'E-Money'],
            'paket-sms-telpon' => ['title' => 'Paket SMS & Telpon', 'db_cat' => 'SMS'],
            'masa-aktif' => ['title' => 'Masa Aktif', 'db_cat' => 'Masa Aktif'],
            'streaming' => ['title' => 'Streaming Premium', 'db_cat' => 'Streaming'],
            'tv' => ['title' => 'TV Prabayar', 'db_cat' => 'TV'],
            'gas' => ['title' => 'Token Gas', 'db_cat' => 'Gas'],
            'esim' => ['title' => 'eSIM', 'db_cat' => 'eSIM'],
            'china-topup' => ['title' => 'China TOPUP', 'db_cat' => 'China'],
            'malaysia-topup' => ['title' => 'Malaysia TOPUP', 'db_cat' => 'Malaysia'],
            'philippines-topup' => ['title' => 'Philippines TOPUP', 'db_cat' => 'Philippines'],
            'singapore-topup' => ['title' => 'Singapore TOPUP', 'db_cat' => 'Singapore'],
            'thailand-topup' => ['title' => 'Thailand TOPUP', 'db_cat' => 'Thailand'],
            'vietnam-topup' => ['title' => 'Vietnam TOPUP', 'db_cat' => 'Vietnam'],
            'pln-pascabayar' => ['title' => 'PLN Pascabayar', 'db_cat' => 'PLN Postpaid'],
            'pdam' => ['title' => 'PDAM', 'db_cat' => 'PDAM'],
            'hp-pascabayar' => ['title' => 'HP Pascabayar', 'db_cat' => 'HP Postpaid'],
            'internet-pascabayar' => ['title' => 'Internet Pascabayar', 'db_cat' => 'Internet'],
            'bpjs-kesehatan' => ['title' => 'BPJS Kesehatan', 'db_cat' => 'BPJS'],
            'multifinance' => ['title' => 'Multifinance', 'db_cat' => 'Multifinance'],
            'pbb' => ['title' => 'Pajak PBB', 'db_cat' => 'PBB'],
            'gas-negara' => ['title' => 'Gas Negara (PGN)', 'db_cat' => 'Gas Postpaid'],
            'tv-pascabayar' => ['title' => 'TV Pascabayar', 'db_cat' => 'TV Postpaid'],
            'samsat' => ['title' => 'E-Samsat', 'db_cat' => 'Samsat'],
            'pln-nontaglis' => ['title' => 'PLN Non-Taglis', 'db_cat' => 'Non Taglis'],
        ];

        $mapData = $categoryMap[$slug] ?? ['title' => ucwords(str_replace('-', ' ', $slug)), 'db_cat' => $slug];
        
        $pageTitle = $mapData['title'];
        $dbCategoryKeyword = $mapData['db_cat'];

        // 3. KONFIGURASI TAMPILAN
        $postpaidSlugs = [
            'pln-pascabayar', 'pdam', 'hp-pascabayar', 'internet-pascabayar', 
            'bpjs-kesehatan', 'multifinance', 'pbb', 'gas-negara', 
            'tv-pascabayar', 'samsat', 'bpjs-ketenagakerjaan', 'pln-nontaglis'
        ];
        
        $isPostpaid = in_array($slug, $postpaidSlugs);
        $inputLabel = 'Nomor Telepon / Tujuan';
        $inputPlaceholder = 'Contoh: 0812xxxx';
        $icon = 'fa-mobile-alt';

        if (str_contains($slug, 'pln')) {
            $inputLabel = 'ID Pelanggan / No. Meter';
            $inputPlaceholder = 'Contoh: 5300xxxx';
            $icon = 'fa-bolt';
        } elseif (str_contains($slug, 'bpjs')) {
            $inputLabel = 'Nomor VA BPJS';
            $inputPlaceholder = 'Contoh: 88888xxxx';
            $icon = 'fa-heartbeat';
        } elseif (str_contains($slug, 'pdam')) {
            $inputLabel = 'ID Pelanggan PDAM';
            $inputPlaceholder = 'Nomor Pelanggan...';
            $icon = 'fa-faucet';
        } elseif (str_contains($slug, 'game')) {
            $inputLabel = 'User ID Game';
            $inputPlaceholder = 'Masukkan ID Game...';
            $icon = 'fa-gamepad';
        } elseif ($slug == 'e-money') {
            $icon = 'fa-wallet';
        }

        $pageInfo = [
            'slug' => $slug,
            'title' => $pageTitle,
            'is_postpaid' => $isPostpaid,
            'input_label' => $inputLabel,
            'input_place' => $inputPlaceholder,
            'icon' => $icon,
        ];

        // 4. QUERY PRODUK
        $products = PpobProduct::where('category', 'LIKE', "%{$dbCategoryKeyword}%")
            ->where('buyer_product_status', true)
            ->where('seller_product_status', true)
            ->orderBy('sell_price', 'asc')
            ->get();

        $brands = $products->pluck('brand')->unique()->values();

        $defaultInquirySku = null;

        if ($isPostpaid && $slug === 'pln-pascabayar') {
            $defaultInquirySku = $products
                ->where('brand', 'PLN PASCABAYAR')
                ->where('seller_product_status', true)
                ->pluck('buyer_sku_code')
                ->first();
        }

        // 5. RETURN VIEW
        $prefix = request()->segment(1);
        if ($prefix == 'admin' || (auth()->check() && auth()->user()->hasRole('Admin'))) {
            return view('admin.ppob.index', compact('pageInfo', 'products', 'banners', 'settings', 'weblogo', 'brands')); 
        }

        return view('etalase.ppob.category', compact(
            'pageInfo', 
            'products', 
            'banners', 
            'settings', 
            'weblogo', 
            'brands' 
        ));
    }

    public function checkBill(Request $request)
    {
        $request->validate([
            'customer_no' => 'required', 
            'sku' => 'required' 
        ]);

        $customerNo = $request->input('customer_no');
        $sku = $request->input('sku');
        $refId = 'INQ-' . time() . rand(100,999);
        
        $product = PpobProduct::where('buyer_sku_code', $sku)->first();

        if (!$product || $product->seller_product_status != true) {
            $alternativeSku = PpobProduct::where('brand', 'PLN PASCABAYAR')
                ->where('seller_product_status', true)
                ->inRandomOrder()
                ->first();

            if ($alternativeSku) {
                $product = $alternativeSku;
                $sku = $alternativeSku->buyer_sku_code;
                Log::warning("SKU $sku tidak aktif. Menggunakan alternatif: $sku.");
            } else {
                Log::warning("Inquiry Failed: SKU $sku tidak aktif, dan tidak ada alternatif.");
                return response()->json([
                    'status' => 'error', 
                    'message' => 'SKU produk Pascabayar tidak ditemukan atau sedang non-aktif.'
                ]);
            }
        }

        $username = 'mihetiDVGdeW'; 
        $apiKeyProd = '1f48c69f-8676-5d56-a868-10a46a69f9b7';
        $testingMode = false; 
        $this->digiflazz->setCredentials($username, $apiKeyProd, $testingMode);
        
        try {
            $response = $this->digiflazz->inquiryPasca($sku, $customerNo, $refId);
            
            if (isset($response['data']) && ($response['data']['rc'] === '00' || $response['data']['status'] === 'Sukses' || $response['data']['status'] === 'Pending')) {
                $data = $response['data'];
                
                $tagihanPokokAPI = $data['price'] ?? $data['selling_price'] ?? $data['amount'] ?? 0;
                $adminFeeModal = $data['admin'] ?? 0;

                $markupKita = $product->sell_price - ($product->price ?? 0); 
                
                $tagihanPokokAPI = (float)$tagihanPokokAPI;
                $adminFeeModal = (float)$adminFeeModal;
                
                $totalTagihanAkhir = $tagihanPokokAPI + $adminFeeModal + $markupKita;

                return response()->json([
                    'status' => 'success',
                    'customer_name' => $data['customer_name'] ?? $data['name'] ?? '',
                    'customer_no' => $data['customer_no'] ?? '',
                    'product_name' => $product->product_name,
                    'period' => $data['period'] ?? null,
                    'amount_pokok' => (float)$tagihanPokokAPI,
                    'admin_fee_modal' => (float)$adminFeeModal,
                    'markup' => $markupKita,
                    'total_tagihan' => $totalTagihanAkhir,
                    'ref_id' => $refId,
                    'desc' => $data['desc'] ?? []
                ]);
            }
            
            $message = $response['data']['message'] ?? ($response['message'] ?? 'Tagihan tidak ditemukan atau Signature salah.'); 
            Log::error("Inquiry Pasca API Error: $message", ['response' => $response, 'sku' => $sku, 'customer_no' => $customerNo]);
            
            return response()->json(['status' => 'error', 'message' => $message]);
            
        } catch (\Exception $e) {
            Log::error("PPOB Inquiry Exception: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error: Gagal koneksi ke provider. ' . $e->getMessage()], 500);
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

    /**
     * FUNGSI STORE (TRANSAKSI AKHIR) - DIPERBAIKI DENGAN IDEMPOTENCY
     */
    public function store(Request $request)
    {
        $request->validate([
            'buyer_sku_code' => 'required', 
            'customer_no' => 'required',
            'customer_wa' => 'required|string|min:8|max:20',
            'idempotency_key' => 'required|string|max:36',
        ]);
        
        $user = Auth::user(); 
        if (!$user) return redirect()->route('login');

        $product = PpobProduct::where('buyer_sku_code', $request->buyer_sku_code)->first();
        if (!$product) return back()->with('error', 'Produk tidak ditemukan.');
        
        // Cek Saldo
        if ($user->saldo < $product->sell_price) {
            return back()->with('error', 'Saldo tidak cukup. Silakan Top Up.');
        }

        // 1. BUAT REF ID UNIK & IDEMPOTENCY LOCK
        $refId = 'TRX-' . time() . rand(100,999);
        $idempotencyKey = 'ppob_lock:' . $request->customer_no . ':' . $product->buyer_sku_code;
        $lockDuration = 300; 

        if (Cache::has($idempotencyKey)) {
            Log::warning('PPOB Idempotency Check: Duplicate request blocked.', [
                'key' => $idempotencyKey, 
                'user_id' => $user->id_pengguna ?? $user->id
            ]);
            return back()->with('error', 'Transaksi sedang diproses. Mohon tunggu 5 menit sebelum mencoba lagi (Cek riwayat transaksi).');
        }

        Cache::put($idempotencyKey, $refId, $lockDuration);
        
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
                'customer_wa' => $this->_sanitizePhoneNumber($request->customer_wa),
                'price' => $product->price,
                'selling_price' => $product->sell_price,
                'profit' => $product->sell_price - $product->price,
                'status' => 'Pending',
                'message' => 'Sedang diproses...',
            ]);

            // Hit API
            $response = $this->digiflazz->transaction($product->buyer_sku_code, $request->customer_no, $refId, $product->sell_price);
            
            if (isset($response['data']) && $response['data']['status'] !== 'Gagal') {
                $trx->update(['status' => $response['data']['status'], 'sn' => $response['data']['sn'] ?? '']);
                DB::commit();
                
                // Redireksi Sukses
                return redirect('customer/ppob/history')->with('success', 'Transaksi Berhasil Diproses!'); 
            } else {
                // Gagal -> Refund
                $user->increment('saldo', $product->sell_price);
                $trx->update(['status' => 'Gagal', 'message' => $response['data']['message'] ?? 'Gagal dari provider']);
                DB::commit();
                
                // GAGAL: Lock tetap aktif sampai TTL berakhir.
                return back()->with('error', 'Transaksi Gagal: ' . ($response['data']['message'] ?? 'Unknown Error'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            // EXCEPTION: Lock tetap aktif sampai TTL berakhir.
            Log::error('PPOB Store Exception (Double Order Protection Active): ' . $e->getMessage());
            return back()->with('error', 'Error Sistem: ' . $e->getMessage());
        }
    }

    public function sync()
    {
        $cacheKey = 'digiflazz_pricelist_synced';
        $ttlInMinutes = 10;

        if (Cache::has($cacheKey)) {
            return redirect()->route('admin.ppob.index')->with(
                'error', 
                'Sinkronisasi harga sudah dilakukan baru-baru ini. Silakan coba lagi setelah 10 menit.'
            );
        }
        
        if (!Auth::check()) {
            return redirect()->route('login'); 
        }

        $productsPrepaid = [];
        $productsPostpaid = [];

        try {
            $productsPrepaid = $this->digiflazz->getPriceList('prepaid'); 
            $productsPostpaid = $this->digiflazz->getPriceList('postpaid');

            $productsPrepaid = is_array($productsPrepaid) ? $productsPrepaid : [];
            $productsPostpaid = is_array($productsPostpaid) ? $productsPostpaid : [];
            
            $productArray = array_merge($productsPrepaid, $productsPostpaid);

            if (!empty($productArray)) {
                DB::beginTransaction();

                $insertedCount = 0;
                $updatedCount = 0;

                foreach ($productArray as $product) {
                    if (!is_array($product)) {
                        Log::warning('Skipping invalid product data during sync: ' . (string)$product);
                        continue; 
                    }

                    $localProduct = PpobProduct::firstOrNew(['buyer_sku_code' => $product['buyer_sku_code']]);

                    $localProduct->fill([
                        'product_name' => $product['product_name'],
                        'category' => $product['category'],
                        'brand' => $product['brand'],
                        'type' => $product['type'] ?? null,
                        'price' => $product['price'] ?? 0,
                        'sell_price' => $localProduct->sell_price ?? ($product['price'] ?? 0) + 1000, 
                        'admin' => $product['admin'] ?? 0, 
                        'status' => $product['status'] ?? null,
                        'buyer_sku_code' => $product['buyer_sku_code'],
                        'desc' => $product['desc'] ?? null,
                        'buyer_product_status' => $product['buyer_product_status'],
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
                
                Cache::put($cacheKey, now(), $ttlInMinutes); 
                
                return redirect()->route('admin.ppob.index')->with(
                    'success', 
                    "Sinkronisasi Berhasil. Ditambahkan: $insertedCount, Diperbarui: $updatedCount."
                );

            } else {
                Log::error('Digiflazz Sync Failed: Response Empty or Invalid');
                
                return redirect()->route('admin.ppob.index')->with(
                    'error', 
                    'Gagal mengambil data dari Digiflazz. Respons kosong.'
                );
            }

        } catch (\Exception $e) {
            DB::rollBack(); 
            Log::error('PPOB Sync Exception: ' . $e->getMessage());
            
            return redirect()->route('admin.ppob.index')->with(
                'error', 
                'Error Sistem saat sinkronisasi: ' . $e->getMessage()
            );
        }
    }

    /**
     * Mengirim notifikasi WA berisi SN transaksi PPOB.
     * Dipanggil dari Webhook Controller setelah status SUKSES dan SN diterima.
     */
    public function _sendWhatsappNotificationSN(PpobTransaction $trx, string $sn)
    {
        try {
            // 1. Ambil Data Agent (Penjual) - Menggunakan Model Users
            $user = Users::find($trx->user_id); 
            
            // 2. Tentukan Nomor WA (Agent & Customer)
            $agentWa = $this->_sanitizePhoneNumber($user->no_wa ?? null);
            $customerWa = $this->_sanitizePhoneNumber($trx->customer_wa ?? null); 
            
            $fmt = function($val) { return number_format($val, 0, ',', '.'); };
            
            if (!$user) {
                 Log::error("WA Notification SN PPOB: Data Agent tidak ditemukan untuk user_id: " . $trx->user_id);
            }

            // --- DATA TOKO AGENT DARI DATABASE PENGGUNA (USERS) ---
            $storeName = $user->store_name ?? 'Sancaka Express';
            $storeAddress = $user->address_detail ?? 'Kantor Pusat Sancaka Express';
            $storePhone = $this->_sanitizePhoneNumber($user->no_wa ?? null) ?? '628819435180'; 

            // ... (Data Transaksi Utama) ...
            $produk = $trx->buyer_sku_code;
            $tujuan = $trx->customer_no;
            $hargaJual = $fmt($trx->selling_price);

            // ===============================================
            // 1. SUSUN PESAN UNTUK AGENT (FORMAT SINGKAT)
            // ===============================================
            $messageAgent = "[NOTIF AGENT - SN] Transaksi {$trx->order_id} Sukses.
        
*✅ Transaksi PPOB Sukses!*
------------------------------------
Produk: {$produk}
Tujuan: {$tujuan}
Harga Jual: Rp {$hargaJual}
*Serial Number (SN):*\n*{$sn}*\n
------------------------------------
Saldo Baru: Rp " . $fmt($user->saldo ?? 0); 

            // ===============================================
            // 2. SUSUN PESAN UNTUK CUSTOMER (DENGAN BRANDING TOKO)
            // ===============================================
            $messageCustomer = "*Halo Pelanggan {$storeName} 👋*
        
Transaksi PPOB Anda telah Berhasil diproses!
        
*✅ DETAIL TRANSAKSI*
------------------------------------
Produk: {$produk}
Nomor Tujuan: {$tujuan}
Harga Jual: Rp {$hargaJual}
*Serial Number (SN):*\n*{$sn}*\n
------------------------------------
        
Terima kasih telah bertransaksi.
Jika ada kendala, hubungi:
        
*Toko: {$storeName}*
*WA/Telp: {$storePhone}*
*Alamat: {$storeAddress}*
        
Manajemen {$storeName}. 🙏";


            // --- 3. KIRIM KE AGENT ---
            if ($user && $agentWa) {
                FonnteService::sendMessage($agentWa, $messageAgent);
                Log::info('PPOB SN sent via WA to Agent.', ['ref_id' => $trx->order_id, 'agent_wa' => $agentWa]);
            }

            // --- 4. KIRIM KE PEMBELI (Hanya jika WA Pembeli tersedia) ---
            if ($customerWa) {
                FonnteService::sendMessage($customerWa, $messageCustomer);
                Log::info('PPOB SN sent via WA to Customer.', ['ref_id' => $trx->order_id, 'customer_wa' => $customerWa]);
            } else {
                Log::warning("Notifikasi Pembeli GAGAL dikirim: customer_wa tidak tersedia di database.");
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('WA Notification SN PPOB Error: ' . $e->getMessage(), ['trx_id' => $trx->id]);
            return false;
        }
    }
}