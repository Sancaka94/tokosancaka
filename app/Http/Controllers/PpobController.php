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
   public function index($slug = 'pulsa')
    {
        // ============================================================
        // 1. AMBIL DATA PENDUKUNG (Logo, Banner, Setting)
        // ============================================================
        
        // Ambil Logo (PENTING: Agar tidak error undefined variable)
        $logoData = \App\Models\Setting::where('key', 'web_logo')->first();
        $weblogo  = $logoData ? $logoData->value : 'logo.png'; 

        // Ambil Banner Slider
        $banners = \App\Models\BannerEtalase::latest()->get(); 
        
        // Ambil Setting Banner Promo (Banner kecil di samping slider)
        $settings = \App\Models\Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');

        // ============================================================
        // 2. LOGIKA ADMIN / REDIRECT
        // ============================================================
        $prefix = request()->segment(1);

        // Jika URL diawali 'admin' ATAU user yang login adalah Admin
        if ($prefix == 'admin' || (auth()->check() && auth()->user()->hasRole('Admin'))) {
            // Pastikan Anda mengirim data yang sama ke admin view juga
            return view('admin.ppob.index', compact('weblogo', 'banners')); 
        }

        // ============================================================
        // 3. LOGIKA JUDUL & SLUG (MAPPING)
        // ============================================================
        $titles = [
            // PRABAYAR
            'pulsa'             => 'Pulsa Reguler',
            'data'              => 'Paket Data',
            'pln-token'         => 'Token PLN',
            'games'             => 'Voucher Games',
            'voucher'           => 'Voucher Digital',
            'e-money'           => 'E-Money',
            'paket-sms-telpon'  => 'Paket SMS & Telpon',
            'masa-aktif'        => 'Masa Aktif',
            'streaming'         => 'Streaming Premium',
            'tv'                => 'TV Prabayar',
            'aktivasi-voucher'  => 'Aktivasi Voucher',
            'aktivasi-perdana'  => 'Aktivasi Perdana',
            'gas'               => 'Token Gas',
            'esim'              => 'eSIM',
            'media-sosial'      => 'Media Sosial',
            
            // INTERNATIONAL TOPUP
            'china-topup'       => 'China TOPUP',
            'malaysia-topup'    => 'Malaysia TOPUP',
            'philippines-topup' => 'Philippines TOPUP',
            'singapore-topup'   => 'Singapore TOPUP',
            'thailand-topup'    => 'Thailand TOPUP',
            'vietnam-topup'     => 'Vietnam TOPUP',

            // SPECIAL OFFERS
            'telkomsel-omni'    => 'Telkomsel Omni',
            'indosat-only4u'    => 'Indosat Only4u',
            'tri-cuanmax'       => 'Tri CuanMax',
            'xl-axis-cuanku'    => 'XL Axis Cuanku',
            'by-u'              => 'by.U',

            // PASCABAYAR
            'pln-pascabayar'       => 'PLN Pascabayar',
            'pdam'                 => 'PDAM',
            'hp-pascabayar'        => 'HP Pascabayar',
            'internet-pascabayar'  => 'Internet Pascabayar',
            'bpjs-kesehatan'       => 'BPJS Kesehatan',
            'multifinance'         => 'Multifinance / Cicilan',
            'pbb'                  => 'Pajak PBB',
            'gas-negara'           => 'Gas Negara (PGN)',
            'tv-pascabayar'        => 'TV Pascabayar',
            'samsat'               => 'E-Samsat',
            'bpjs-ketenagakerjaan' => 'BPJS Ketenagakerjaan',
            'pln-nontaglis'        => 'PLN Non-Taglis',
        ];

        $pageTitle = $titles[$slug] ?? ucwords(str_replace('-', ' ', $slug));

        // ============================================================
        // 4. LOGIKA PASCABAYAR & INPUT LABEL
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
        // 5. QUERY PRODUK (Agar Produk Muncul, Tidak Kosong)
        // ============================================================
        
        // Mapping slug URL ke nama kategori di Database Anda
        $dbCategoryMap = [
            'pulsa' => 'Pulsa', 'data' => 'Data', 'pln-token' => 'PLN', 
            'games' => 'Games', 'e-money' => 'E-Money'
            // Tambahkan mapping lain jika perlu, atau gunakan logic LIKE
        ];

        $dbCategory = $dbCategoryMap[$slug] ?? ucwords(str_replace('-', ' ', $slug));

        // Ambil produk dari DB (Sesuaikan model Anda: PpobProduct atau Product)
        // Saya pakai query LIKE agar lebih fleksibel menangkap variasi nama kategori
        $products = \App\Models\PpobProduct::where('category', 'LIKE', "%{$dbCategory}%")
            ->where('buyer_product_status', true) // Hanya yang aktif
            ->where('seller_product_status', true)
            ->orderBy('sell_price', 'asc') // Urutkan termurah
            ->get();

        // Ambil list Brand untuk filter
        $brands = $products->pluck('brand')->unique()->values();

        // ============================================================
        // 6. RETURN VIEW (FIXED)
        // ============================================================
        // Kuncinya ada di sini: Menambahkan 'weblogo' dan 'brands' ke compact
        
        return view('layouts.marketplace', compact(
            'pageInfo', 
            'products', 
            'banners', 
            'settings', 
            'weblogo',   // <--- INI PERBAIKANNYA (Agar tidak error undefined variable)
            'brands'     // <--- INI JUGA PERLU (Untuk filter operator)
        ));
    }

   /**
     * 2. Halaman Kategori (Pulsa, Data, Token, dll)
     * URL: /admin/digital/{slug}  atau  /etalase/ppob/digital/{slug}
     */
    public function category($slug)
    {
        // ===============================================================
        // 1. PERBAIKAN: AMBIL WEBLOGO & SETTING (Agar tidak Error Undefined)
        // ===============================================================
        
        // Ambil data logo dari tabel settings (sesuaikan key 'web_logo' jika beda)
        $logoData = \App\Models\Setting::where('key', 'web_logo')->first();
        $weblogo  = $logoData ? $logoData->value : 'logo.png'; // Fallback jika kosong

        // Ambil Banner untuk slider
        $banners  = \App\Models\BannerEtalase::latest()->get(); 
        
        // Ambil Setting Banner Promo
        $settings = \App\Models\Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');

        // ===============================================================
        // 2. MAPPING KATEGORI (Sesuai Database)
        // ===============================================================
        $categoriesMap = [
            // PRABAYAR
            'pulsa'             => ['Pulsa', 'Pulsa Reguler'],
            'data'              => ['Data', 'Paket Data'],
            'pln-token'         => ['PLN', 'Token PLN'],
            'games'             => ['Games', 'Voucher Game'],
            'voucher'           => ['Voucher', 'Voucher Digital'],
            'e-money'           => ['E-Money', 'E-Wallet'],
            'paket-sms-telpon'  => ['Paket SMS & Telpon', 'SMS & Telpon'],
            'masa-aktif'        => ['Masa Aktif'],
            'streaming'         => ['Streaming', 'TV Prabayar'],
            'aktivasi-voucher'  => ['Aktivasi Voucher'],
            'aktivasi-perdana'  => ['Aktivasi Perdana'],
            'gas'               => ['Gas Token', 'Pertagas'],
            'esim'              => ['eSIM'],
            // TOPUP LUAR NEGERI
            'china-topup'       => ['China Topup'],
            'malaysia-topup'    => ['Malaysia Topup'],
            'philippines-topup' => ['Philippines Topup'],
            'singapore-topup'   => ['Singapore Topup'],
            'thailand-topup'    => ['Thailand Topup'],
            'vietnam-topup'     => ['Vietnam Topup'],
            // PASCABAYAR
            'pln-pascabayar'       => ['PLN Pascabayar', 'Tagihan PLN'],
            'pdam'                 => ['PDAM', 'Air PDAM'],
            'bpjs-kesehatan'       => ['BPJS', 'BPJS Kesehatan'],
            'bpjs-ketenagakerjaan' => ['BPJS Ketenagakerjaan', 'BPJS TK'],
            'hp-pascabayar'        => ['HP Pascabayar', 'Pascabayar'],
            'internet-pascabayar'  => ['Internet', 'Internet Pascabayar', 'Wifi'],
            'tv-pascabayar'        => ['TV Kabel', 'TV Pascabayar'],
            'multifinance'         => ['Multifinance', 'Cicilan', 'Angsuran Kredit'],
            'pbb'                  => ['PBB', 'Pajak PBB'],
            'gas-negara'           => ['Gas Negara', 'PGN'],
            'samsat'               => ['Samsat', 'E-Samsat'],
            'pln-nontaglis'        => ['PLN Non Taglis', 'Non Taglis'],
        ];

        // Validasi Slug
        if (!array_key_exists($slug, $categoriesMap)) {
            abort(404);
        }

        $dbCategories = $categoriesMap[$slug];

        // ===============================================================
        // 3. KONFIGURASI TAMPILAN (JUDUL, ICON, INPUT)
        // ===============================================================
        $pageInfo = [
            'title'             => ucwords(str_replace('-', ' ', $slug)),
            'slug'              => $slug,
            'input_label'       => 'Nomor Handphone',
            'input_place'       => 'Contoh: 0812xxxx',
            'icon'              => 'fa-mobile-alt',
            'is_postpaid'       => false,
            'has_region_select' => false
        ];

        // LOGIKA KHUSUS PER KATEGORI
        if ($slug == 'pln-token') {
            $pageInfo['input_label'] = 'ID Pelanggan / No. Meter';
            $pageInfo['input_place'] = 'Contoh: 14xxxxxxxx';
            $pageInfo['icon']        = 'fa-bolt';
        
        } elseif ($slug == 'e-money') {
            $pageInfo['icon']        = 'fa-wallet';
            $pageInfo['input_place'] = 'Nomor HP / Nomor Kartu';

        } elseif ($slug == 'voucher-game' || $slug == 'games') {
            $pageInfo['input_label'] = 'ID Pemain (User ID)';
            $pageInfo['input_place'] = 'Masukkan ID Game / Server';
            $pageInfo['icon']        = 'fa-gamepad';

        } elseif (str_contains($slug, 'topup')) {
            $pageInfo['icon']        = 'fa-globe-asia';

        // LOGIKA PASCABAYAR
        } elseif ($slug == 'pln-pascabayar') {
            $pageInfo['title']       = 'Tagihan Listrik PLN';
            $pageInfo['input_label'] = 'ID Pelanggan / No. Meter';
            $pageInfo['input_place'] = 'Contoh: 53xxxxxxxx';
            $pageInfo['icon']        = 'fa-file-invoice-dollar';
            $pageInfo['is_postpaid'] = true;

        } elseif ($slug == 'pdam') {
            $pageInfo['title']       = 'Tagihan Air PDAM';
            $pageInfo['input_label'] = 'Nomor Pelanggan';
            $pageInfo['input_place'] = 'Masukan ID Pelanggan';
            $pageInfo['icon']        = 'fa-faucet';
            $pageInfo['is_postpaid'] = true;
            $pageInfo['has_region_select'] = true;

        } elseif ($slug == 'bpjs-kesehatan') {
            $pageInfo['input_label'] = 'Nomor VA Keluarga';
            $pageInfo['input_place'] = 'Contoh: 88888xxxx';
            $pageInfo['icon']        = 'fa-heartbeat';
            $pageInfo['is_postpaid'] = true;

        } elseif ($slug == 'multifinance') {
            $pageInfo['title']       = 'Bayar Cicilan / Kredit';
            $pageInfo['input_label'] = 'Nomor Kontrak';
            $pageInfo['input_place'] = 'Masukan Nomor Kontrak';
            $pageInfo['icon']        = 'fa-money-bill-wave';
            $pageInfo['is_postpaid'] = true;
            $pageInfo['has_region_select'] = true; 
        }

        // ===============================================================
        // 4. QUERY PRODUK DARI DATABASE
        // ===============================================================
        $query = \App\Models\PpobProduct::whereIn('category', $dbCategories)
            ->where('buyer_product_status', true)
            ->where('seller_product_status', true);

        // Sorting: Pascabayar by Brand, Prabayar by Harga
        if ($pageInfo['is_postpaid'] || $pageInfo['has_region_select']) {
            $query->orderBy('brand', 'asc');
        } else {
            $query->orderBy('sell_price', 'asc');
        }

        $products = $query->get();
        $brands   = $products->pluck('brand')->unique()->values();
        
        // PASTI ADA WEBLOGO DISINI
        $data = compact('products', 'brands', 'weblogo', 'pageInfo', 'banners', 'settings');

        // ===============================================================
        // 5. RETURN VIEW SESUAI ROLE
        // ===============================================================
        $prefix = request()->segment(1); 

        if ($prefix == 'admin' || (auth()->check() && auth()->user()->hasRole('Admin'))) {
            return view('admin.ppob.category', $data); 
        }

        if ($prefix == 'seller' || (auth()->check() && auth()->user()->hasRole('Seller'))) {
            return view('seller.ppob.category', $data);
        }

        if ($prefix == 'member' || $prefix == 'customer' || (auth()->check() && auth()->user()->hasRole('Customer'))) {
            return view('customer.ppob.category', $data);
        }

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
        // 1. Validasi Input
        $request->validate([
            'customer_no' => 'required', 
            'sku' => 'required' 
        ]);

        // 2. Generate Ref ID
        $refId = 'INQ-' . time() . rand(100,999);

        try {
            // 3. Tembak API Digiflazz
            // (Service Anda sudah terbukti benar di langkah debug sebelumnya)
            $response = $this->digiflazz->inquiryPasca($request->sku, $request->customer_no, $refId);

            // 4. Cek Response dari API
            if (isset($response['data'])) {
                $data = $response['data'];
                
                // Status Sukses (RC 00, Sukses, atau Pending)
                if ($data['rc'] === '00' || $data['status'] === 'Sukses' || $data['status'] === 'Pending') {
                    
                    return response()->json([
                        'status' => 'success',
                        'customer_name' => $data['customer_name'],
                        'customer_no'   => $data['customer_no'],
                        'admin_fee'     => $data['admin'],
                        // selling_price adalah harga yg harus dibayar user (sudah termasuk admin)
                        'amount'        => $data['selling_price'], 
                        'ref_id'        => $refId,
                        'desc'          => $data['desc'] ?? []
                    ]);

                } else {
                    // Jika Gagal (Misal: Tagihan sudah dibayar)
                    return response()->json([
                        'status' => 'error', 
                        'message' => $data['message'] ?? 'Tagihan tidak ditemukan atau Gagal.'
                    ]);
                }
            }
            
            // Jika response ada message tapi tidak ada data (Error dari Digiflazz langsung)
            if (isset($response['message'])) {
                 return response()->json(['status' => 'error', 'message' => $response['message']]);
            }

            return response()->json(['status' => 'error', 'message' => 'Gagal terhubung ke server provider.']);

        } catch (\Exception $e) {
            // Tangkap error sistem agar tidak muncul layar putih
            return response()->json([
                'status' => 'error', 
                'message' => 'Kesalahan Sistem: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * 7. AJAX: Cek ID Pelanggan PLN Prabayar
     * Route: /ppob/check-pln-prabayar
     */
    public function checkPlnPrabayar(Request $request)
    {
        $request->validate(['customer_no' => 'required']);

        try {
            // Panggil Service yang baru diupdate
            $response = $this->digiflazz->inquiryPln($request->customer_no);

            if (isset($response['data'])) {
                $data = $response['data'];
                
                // Cek status RC 00 (Sukses)
                if ($data['rc'] === '00' || $data['status'] === 'Sukses') {
                    return response()->json([
                        'status' => 'success',
                        'name' => $data['name'],                 // Nama: DAVID
                        'meter_no' => $data['meter_no'],         // No Meter
                        'subscriber_id' => $data['subscriber_id'], // ID Pelanggan
                        'segment_power' => $data['segment_power']  // Daya: R1 /000001300
                    ]);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => $data['message'] ?? 'Nomor ID/Meter tidak ditemukan.'
                    ]);
                }
            }

            return response()->json(['status' => 'error', 'message' => 'Gagal terhubung ke server PLN.']);

        } catch (\Exception $e) {
            Log::error("CheckPlnPrabayar Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan sistem.']);
        }
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

    public function debugDirect()
    {
        echo "<h1>MODE DEBUGGING SAMA SEKALI TANPA JS</h1>";
        echo "<hr>";

        try {
            // 1. Cek apakah Class Service Ada
            if (!class_exists(\App\Services\DigiflazzService::class)) {
                die("FATAL ERROR: File App\Services\DigiflazzService tidak ditemukan!");
            }

            // 2. Coba instansiasi manual (Bypass Dependency Injection Laravel)
            echo "1. Mencoba load Service Digiflazz... <br>";
            $service = new \App\Services\DigiflazzService();
            echo "✅ Service Berhasil Diload.<br><br>";

            // 3. Siapkan Data Dummy
            $sku = 'pln'; // SKU Pascabayar
            $customerNo = '530000000001'; // Nomor ID Pelanggan Anda dari screenshot
            $refId = 'DEBUG-' . time();

            echo "2. Mengirim Request ke Digiflazz...<br>";
            echo "SKU: $sku <br>";
            echo "No: $customerNo <br>";
            echo "Ref: $refId <br><br>";

            // 4. Tembak
            $response = $service->inquiryPasca($sku, $customerNo, $refId);

            echo "3. HASIL BALASAN (RAW):<br>";
            echo "<pre style='background: #eee; padding: 10px; border: 1px solid #333;'>";
            print_r($response);
            echo "</pre>";

        } catch (\Exception $e) {
            echo "<h2 style='color:red'>TERJADI ERROR SISTEM!</h2>";
            echo "<b>Pesan:</b> " . $e->getMessage() . "<br>";
            echo "<b>File:</b> " . $e->getFile() . "<br>";
            echo "<b>Baris:</b> " . $e->getLine() . "<br>";
        }
    }
}