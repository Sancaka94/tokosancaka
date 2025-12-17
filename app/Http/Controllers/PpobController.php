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
use App\Services\FonnteService; // Pastikan Service ini ada
use App\Models\User; 
use Illuminate\Support\Str;

class PpobController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    // =================================================================
    // HELPER FUNCTIONS (WA, Logo, Sanitizer)
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

    /**
     * Mengirim notifikasi WA berisi SN transaksi PPOB.
     * Menggunakan FonnteService.
     */
    public function _sendWhatsappNotificationSN(PpobTransaction $trx, string $sn)
    {
        try {
            // 1. Ambil Data Agent (Penjual)
            $user = User::find($trx->user_id); 
            
            // 2. Tentukan Nomor WA (Agent & Customer)
            $agentWa = $this->_sanitizePhoneNumber($user->no_wa ?? $user->no_hp ?? null);
            $customerWa = $this->_sanitizePhoneNumber($trx->customer_wa ?? null); 
            
            $fmt = function($val) { return number_format($val, 0, ',', '.'); };
            
            if (!$user) {
                 Log::error("WA Notification SN PPOB: Data Agent tidak ditemukan untuk user_id: " . $trx->user_id);
                 return false;
            }

            // --- DATA TOKO AGENT ---
            $storeName = $user->store_name ?? 'Sancaka Express';
            $storeAddress = $user->address_detail ?? 'Kantor Pusat Sancaka Express';
            $storePhone = $this->_sanitizePhoneNumber($user->no_wa ?? null) ?? '628819435180'; 

            // Data Transaksi
            $produk = $trx->buyer_sku_code;
            $tujuan = $trx->customer_no;
            $hargaJual = $fmt($trx->selling_price);

            // 1. PESAN UNTUK AGENT (FORMAT SINGKAT)
            $messageAgent = "[NOTIF AGENT - SN] Transaksi {$trx->order_id} Sukses.\n\n";
            $messageAgent .= "*✅ Transaksi PPOB Sukses!*\n";
            $messageAgent .= "------------------------------------\n";
            $messageAgent .= "Produk: {$produk}\n";
            $messageAgent .= "Tujuan: {$tujuan}\n";
            $messageAgent .= "Harga Jual: Rp {$hargaJual}\n";
            $messageAgent .= "*Serial Number (SN):*\n*{$sn}*\n";
            $messageAgent .= "------------------------------------\n";
            $messageAgent .= "Saldo Baru: Rp " . $fmt($user->saldo ?? 0); 

            // 2. PESAN UNTUK CUSTOMER (DENGAN BRANDING TOKO)
            $messageCustomer = "*Halo Pelanggan {$storeName} 👋*\n\n";
            $messageCustomer .= "Transaksi PPOB Anda telah Berhasil diproses!\n\n";
            $messageCustomer .= "*✅ DETAIL TRANSAKSI*\n";
            $messageCustomer .= "------------------------------------\n";
            $messageCustomer .= "Produk: {$produk}\n";
            $messageCustomer .= "Nomor Tujuan: {$tujuan}\n";
            $messageCustomer .= "Harga Jual: Rp {$hargaJual}\n";
            $messageCustomer .= "*Serial Number (SN):*\n*{$sn}*\n";
            $messageCustomer .= "------------------------------------\n\n";
            $messageCustomer .= "Terima kasih telah bertransaksi.\n";
            $messageCustomer .= "Jika ada kendala, hubungi:\n\n";
            $messageCustomer .= "*Toko: {$storeName}*\n";
            $messageCustomer .= "*WA/Telp: {$storePhone}*\n";
            $messageCustomer .= "*Alamat: {$storeAddress}*\n\n";
            $messageCustomer .= "Manajemen {$storeName}. 🙏";

            // --- KIRIM KE AGENT ---
            if ($user && $agentWa) {
                FonnteService::sendMessage($agentWa, $messageAgent);
                Log::info('PPOB SN sent via WA to Agent.', ['ref_id' => $trx->order_id, 'agent_wa' => $agentWa]);
            }

            // --- KIRIM KE PEMBELI ---
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

        // 2. MAPPING JUDUL & KATEGORI DATABASE LENGKAP
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

        // 4. QUERY PRODUK (DIPERBAIKI)
$products = PpobProduct::where(function($query) use ($dbCategoryKeyword) {
        $query->where('category', 'LIKE', "%{$dbCategoryKeyword}%")
              ->orWhere('brand', 'LIKE', "%{$dbCategoryKeyword}%"); // Tambahkan cari by Brand juga
    })
    ->where('seller_product_status', 1) // Pastikan status seller aktif
    ->where('buyer_product_status', 1)  // Pastikan status buyer aktif
    ->orderBy('price', 'asc') // Urutkan dari termurah
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
        // 1. VALIDASI INPUT
        $request->validate([
            'customer_no' => 'required', 
            'sku' => 'required' 
        ]);

        $customerNo = $request->input('customer_no');
        $inputSku   = $request->input('sku'); 
        $finalSku   = $inputSku; 
        $refId      = 'INQ-' . time() . rand(100,999);

        // =======================================================================
        // 2. UNIVERSAL SEARCH (LOGIKA GABUNGAN 3 KOLOM)
        // =======================================================================
        
        // Cek jika ini bukan SKU kode unik (bukan diawali 'post', 'pln', dsb jika itu kode asli)
        // Kita asumsikan inputSku adalah slug seperti: 'pdam-surabaya', 'bpjs-kesehatan', 'samsat-jatim'
        if (!Str::startsWith($inputSku, 'post') && !preg_match('/^[A-Z0-9]+$/', $inputSku)) {

            // A. Bersihkan Input (Ubah 'pdam-surabaya' jadi 'pdam surabaya')
            $keyword = str_replace('-', ' ', $inputSku);
            
            // B. TEKNIK CONCAT: Gabungkan Brand + Nama + Kategori jadi satu kalimat panjang
            // Lalu cari kata kuncinya di kalimat panjang tersebut.
            // Contoh DB: Brand="PDAM", Name="Kota Surabaya". Digabung jadi "PDAM Kota Surabaya".
            // Input "pdam surabaya" -> KETEMU!
            
            $product = PpobProduct::whereRaw(
                "CONCAT(IFNULL(brand,''), ' ', IFNULL(product_name,''), ' ', IFNULL(category,'')) LIKE ?", 
                ["%{$keyword}%"]
            )
            ->where('seller_product_status', true)
            ->orderBy('id', 'desc') // Ambil yang paling baru
            ->first();

            // C. Fallback: Jika pencarian spesifik gagal, cari kata depannya saja
            // Misal: 'pdam-kota-malang' gagal, coba cari apapun yang ada kata 'pdam'
            if (!$product) {
                $firstWord = explode(' ', $keyword)[0]; // Ambil 'pdam', 'samsat', atau 'bpjs'
                
                $product = PpobProduct::where(function($q) use ($firstWord) {
                        $q->where('brand', 'LIKE', "%{$firstWord}%")
                          ->orWhere('category', 'LIKE', "%{$firstWord}%");
                    })
                    ->where('seller_product_status', true)
                    ->first();
            }

            // Jika ketemu produknya, ambil SKU aslinya
            if ($product) {
                $finalSku = $product->buyer_sku_code;
            }
        }

        // =======================================================================
        // 3. VALIDASI AKHIR (Memastikan SKU Valid)
        // =======================================================================
        
        // Cari ulang berdasarkan SKU final untuk memastikan data konsisten
        $activeProduct = PpobProduct::where('buyer_sku_code', $finalSku)->first();

        if (!$activeProduct || $activeProduct->seller_product_status != true) {
            return response()->json([
                'status' => 'error', 
                'message' => "Produk layanan '$inputSku' (atau sejenisnya) sedang tidak aktif atau tidak ditemukan."
            ]);
        }

        // =======================================================================
        // 4. PROSES KE API DIGIFLAZZ (Tetap Sama)
        // =======================================================================
        
        $username = 'mihetiDVGdeW'; 
        $apiKeyProd = '1f48c69f-8676-5d56-a868-10a46a69f9b7';
        $this->digiflazz->setCredentials($username, $apiKeyProd, false); 
        
        try {
            $response = $this->digiflazz->inquiryPasca($finalSku, $customerNo, $refId);
            
            if (isset($response['data']) && ($response['data']['rc'] === '00' || $response['data']['status'] === 'Sukses' || $response['data']['status'] === 'Pending')) {
                
                $data = $response['data'];
                $tagihanPenyedia = (float) ($data['price'] ?? $data['selling_price'] ?? $data['amount'] ?? 0);
                $adminFeePenyedia = (float) ($data['admin'] ?? 0);
                
                // Hitung Profit
                $marginAgen = ($activeProduct->sell_price > $activeProduct->price) 
                              ? ($activeProduct->sell_price - $activeProduct->price) 
                              : 2500;

                $totalTagihanUser = $tagihanPenyedia + $adminFeePenyedia + $marginAgen;

                return response()->json([
                    'status' => 'success',
                    'customer_name' => $data['customer_name'] ?? $data['name'],
                    'customer_no'   => $data['customer_no'],
                    'product_name'  => $activeProduct->product_name, // Gunakan nama dari DB kita agar rapi
                    'amount_pokok'  => $tagihanPenyedia,
                    'total_tagihan' => $totalTagihanUser,
                    'buyer_sku_code' => $finalSku, 
                    'ref_id'         => $data['ref_id'] ?? $refId,
                    'desc'           => $data['desc'] ?? []
                ]);
            }
            
            $msg = $response['data']['message'] ?? 'Tagihan tidak ditemukan atau Nomor Salah.';
            return response()->json(['status' => 'error', 'message' => $msg]);
            
        } catch (\Exception $e) {
            Log::error("Inquiry Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal koneksi ke server provider.'], 500);
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
     * FUNGSI STORE (TRANSAKSI AKHIR) - DIPERBAIKI DENGAN IDEMPOTENCY & WA
     */
    public function store(Request $request)
    {
        $request->validate([
            'buyer_sku_code' => 'required', 
            'customer_no' => 'required',
            'customer_wa' => 'required|string|min:8|max:20',
            'idempotency_key' => 'required|string|max:36',
            'selling_price' => 'nullable|numeric', // Untuk pascabayar (harga dinamis)
            'ref_id' => 'nullable|string', // Untuk pascabayar (dari inquiry)
        ]);
        
        $user = Auth::user(); 
        if (!$user) return redirect()->route('login');

        // 1. DETEKSI JENIS TRANSAKSI (PRABAYAR / PASCABAYAR)
        // Jika ada ref_id dari input, asumsikan ini Pascabayar
        $isPasca = $request->has('ref_id') && !empty($request->input('ref_id'));
        $sku = $request->buyer_sku_code;

        // 2. CARI PRODUK DI DATABASE
        $product = PpobProduct::where('buyer_sku_code', $sku)->first();

        // [FIX PASCABAYAR] Jika SKU API (misal post641598) tidak ada di DB, cari map generic
        if (!$product && $isPasca) {
            // Mapping fallback ke produk generic Pascabayar agar dapat settingan profit
            $product = PpobProduct::where('category', 'LIKE', '%Postpaid%')
                        ->orWhere('brand', 'PLN PASCABAYAR')
                        ->first();
        }

        if (!$product) return back()->with('error', 'Produk tidak ditemukan di sistem.');

        // 3. TENTUKAN HARGA JUAL & MODAL
        if ($isPasca) {
            // Pascabayar: Harga jual dari input (Total Tagihan Inquiry)
            $sellingPrice = $request->input('selling_price');
            if(!$sellingPrice) return back()->with('error', 'Harga Tagihan tidak valid.');
            
            // Estimasi profit (misal 2500), nanti diupdate real-nya setelah respon sukses
            $estimasiProfit = 2500; 
            $priceToDeduct = $sellingPrice; // Saldo user dipotong sebesar Total Tagihan
            $modalPrice = $sellingPrice - $estimasiProfit; 
        } else {
            // Prabayar: Harga dari Database
            $sellingPrice = $product->sell_price;
            $priceToDeduct = $product->sell_price;
            $modalPrice = $product->price;
        }
        
        // 4. CEK SALDO
        if ($user->saldo < $priceToDeduct) {
            return back()->with('error', 'Saldo tidak cukup. Silakan Top Up.');
        }

        // 5. IDEMPOTENCY CHECK
        $idempotencyKey = 'ppob_lock:' . $request->customer_no . ':' . $sku . ':' . $request->idempotency_key;
        if (Cache::has($idempotencyKey)) {
            Log::warning('PPOB Idempotency Check: Duplicate request blocked.', [
                'key' => $idempotencyKey, 
                'user_id' => $user->id
            ]);
            return back()->with('error', 'Transaksi sedang diproses. Mohon tunggu 5 menit sebelum mencoba lagi.');
        }

        Cache::put($idempotencyKey, true, 300); // Lock 5 menit
        
        // 6. SET REF ID
        // Jika Pasca, WAJIB pakai ref_id inquiry. Jika Pra, buat baru.
        $trxRefId = $isPasca ? $request->input('ref_id') : 'TRX-' . time() . rand(100,999);

        DB::beginTransaction();
        try {
            // A. Potong Saldo
            $user->decrement('saldo', $priceToDeduct);
            
            // B. Simpan Transaksi Lokal
            $trx = PpobTransaction::create([
                'user_id' => $user->id,
                'order_id' => $trxRefId, 
                'buyer_sku_code' => $sku, // Simpan SKU asli (post...)
                'customer_no' => $request->customer_no,
                'customer_wa' => $this->_sanitizePhoneNumber($request->customer_wa),
                'price' => $modalPrice,
                'selling_price' => $sellingPrice,
                'profit' => $sellingPrice - $modalPrice,
                'status' => 'Pending',
                'message' => 'Sedang diproses...',
                'desc' => json_encode(['type' => $isPasca ? 'postpaid' : 'prepaid'])
            ]);

            // C. HIT API DIGIFLAZZ
            // Perlu Parameter 'pay-pasca' jika Pascabayar
            $command = $isPasca ? 'pay-pasca' : null; 
            
            // Panggil Service
            $response = $this->digiflazz->transaction($sku, $request->customer_no, $trxRefId, 0, $command);
            
            $status = $response['data']['status'] ?? 'Gagal';
            $sn = $response['data']['sn'] ?? '';
            $msg = $response['data']['message'] ?? '-';

            // D. HANDLE RESPONSE
            if ($status !== 'Gagal') {
                // Sukses / Pending
                $updateData = ['status' => $status, 'sn' => $sn, 'message' => $msg];
                
                // Update Modal Asli (Khusus Pasca, Digiflazz kasih harga real saat sukses)
                if (isset($response['data']['price']) && $response['data']['price'] > 0) {
                    $realModal = $response['data']['price'];
                    $updateData['price'] = $realModal;
                    $updateData['profit'] = $trx->selling_price - $realModal;
                }

                $trx->update($updateData);
                
                // [FIX WA] Jika Langsung Sukses -> Kirim WA Fonnte
                if ($status === 'Sukses' || $status === 'Success') {
                    $this->_sendWhatsappNotificationSN($trx, $sn);
                }

                DB::commit();
                
                // Redireksi Sukses
                return redirect('customer/ppob/history')->with('success', 'Transaksi Berhasil Diproses!'); 
            } else {
                // Gagal -> Refund
                $user->increment('saldo', $priceToDeduct);
                $trx->update(['status' => 'Gagal', 'message' => $msg]);
                DB::commit();
                
                return back()->with('error', 'Transaksi Gagal: ' . $msg);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PPOB Store Exception: ' . $e->getMessage());
            return back()->with('error', 'Error Sistem: ' . $e->getMessage());
        }
    }

    /**
     * SYNC: Update Database Produk Lokal dari Digiflazz
     */
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
                    if (!is_array($product)) continue; 

                    $localProduct = PpobProduct::firstOrNew(['buyer_sku_code' => $product['buyer_sku_code']]);

                    $localProduct->fill([
                        'product_name' => $product['product_name'],
                        'category' => $product['category'],
                        'brand' => $product['brand'],
                        'type' => $product['type'] ?? null,
                        'price' => $product['price'] ?? 0,
                        // Jika produk baru, set sell_price = modal + 1000. Jika update, jangan ubah sell_price
                        'sell_price' => $localProduct->sell_price ?? ($product['price'] ?? 0) + 1000, 
                        'admin' => $product['admin'] ?? 0, 
                        'status' => $product['status'] ?? null,
                        'buyer_sku_code' => $product['buyer_sku_code'],
                        'desc' => $product['desc'] ?? null,
                        'buyer_product_status' => $product['buyer_product_status'],
                        // Jangan ubah status seller otomatis jika produk sudah ada
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
}