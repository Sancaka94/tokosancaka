<?php

namespace App\Http\Controllers\admin; // Atau App\Http\Controllers\Admin jika dipindah

use Illuminate\Http\Request;
use App\Services\DigiflazzService;
use App\Models\Setting;
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
        // Middleware Auth & Role Admin sudah dihandle di routes/web.php
        // Tapi kita bisa tambahkan double protection di sini jika mau
        // $this->middleware(['auth', 'role:admin']);
        
        $this->digiflazz = $digiflazz;
    }

    private function getWebLogo()
    {
        $setting = Setting::where('key', 'logo')->first();
        return $setting ? $setting->value : null;
    }

      /**
     * Halaman Daftar Harga / Kategori
     * URL: /daftar-harga/{slug?}
     */
    public function index($slug = 'pulsa') // Default ke pulsa jika slug kosong
    {
        // 1. Mapping Kategori (Sama seperti di Admin)
        $categoriesMap = [
            'pulsa'         => ['Pulsa'],
            'data'          => ['Data'],
            'pln-token'     => ['PLN'],
            'pln-pascabayar'=> ['PLN Pascabayar', 'Tagihan PLN'], // Sesuaikan dengan slug di blade
            'pdam'          => ['PDAM'],
            'e-money'       => ['E-Money'],
            'voucher-game'  => ['Games'],
            'streaming'     => ['TV', 'Streaming'],
        ];

        // Jika slug tidak valid, default ke pulsa atau 404
        if (!array_key_exists($slug, $categoriesMap)) {
            abort(404);
        }

        $dbCategories = $categoriesMap[$slug];

        // 2. Setup Variable $pageInfo (INI YANG MENYEBABKAN ERROR SEBELUMNYA)
        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt',
            'is_postpaid' => false
        ];

        // Custom Logic per Kategori untuk UI
        switch ($slug) {
            case 'pln-token':
                $pageInfo['title'] = 'Token Listrik PLN';
                $pageInfo['input_label'] = 'Nomor Meter / ID Pelanggan';
                $pageInfo['input_place'] = 'Contoh: 141234567890';
                $pageInfo['icon'] = 'fa-bolt';
                break;
            case 'pln-pascabayar':
                $pageInfo['title'] = 'Tagihan Listrik Pasca';
                $pageInfo['input_label'] = 'ID Pelanggan';
                $pageInfo['input_place'] = 'Contoh: 53xxxxxxxxx';
                $pageInfo['icon'] = 'fa-file-invoice-dollar';
                $pageInfo['is_postpaid'] = true;
                break;
            case 'pdam':
                $pageInfo['title'] = 'Tagihan Air PDAM';
                $pageInfo['input_label'] = 'ID Pelanggan';
                $pageInfo['icon'] = 'fa-faucet';
                $pageInfo['is_postpaid'] = true;
                break;
            case 'e-money':
                $pageInfo['title'] = 'Top Up E-Wallet';
                $pageInfo['icon'] = 'fa-wallet';
                break;
            case 'voucher-game':
                $pageInfo['title'] = 'Voucher Game';
                $pageInfo['input_label'] = 'ID Pemain (User ID)';
                $pageInfo['input_place'] = 'Masukkan ID Game';
                $pageInfo['icon'] = 'fa-gamepad';
                break;
            case 'streaming':
                $pageInfo['title'] = 'Voucher TV & Streaming';
                $pageInfo['icon'] = 'fa-tv';
                break;
        }

        // 3. Ambil Data Produk
        // Query produk aktif untuk customer
        $products = PpobProduct::whereIn('category', $dbCategories)
            ->where('buyer_product_status', true) // Hanya yang aktif untuk pembeli
            ->where('seller_product_status', true) // Hanya yang aktif dari provider
            ->orderBy('sell_price', 'asc')
            ->get();

        // 4. Data Pendukung Lainnya untuk View
        // List Kategori untuk Menu Icon (sesuai blade line 68)
        $categories = [
            (object)['slug' => 'pulsa', 'name' => 'Pulsa'],
            (object)['slug' => 'data', 'name' => 'Paket Data'],
            (object)['slug' => 'pln-token', 'name' => 'Token PLN'],
            (object)['slug' => 'pln-pascabayar', 'name' => 'PLN Pasca'],
            (object)['slug' => 'pdam', 'name' => 'PDAM'],
            (object)['slug' => 'e-money', 'name' => 'E-Money'],
            (object)['slug' => 'voucher-game', 'name' => 'Games'],
            (object)['slug' => 'streaming', 'name' => 'Streaming'],
        ];
        
        // Ambil Brands untuk Filter
        $brands = $products->pluck('brand')->unique()->values();

        // Ambil Banners (Jika tabel banners ada, jika tidak kosongkan array)
        // $banners = Banner::where('status', 'active')->get(); 
        $banners = []; // Default kosong agar tidak error jika tabel belum ada
        
        // Ambil Settings untuk Banner Promo Kecil
        $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key');

        // 5. Return View Public
        return view('public.pricelist', compact(
            'products', 
            'pageInfo', 
            'categories', 
            'brands', 
            'banners', 
            'settings'
        ));
    }

    /**
     * 2. Halaman Kategori PPOB (ADMIN)
     * URL: /admin/digital/{slug}
     */
    public function category($slug)
    {
        // Pastikan User adalah Admin
        if (!auth()->check() || !auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized access.');
        }

        $weblogo = $this->getWebLogo();

        // Mapping Slug URL
        $categoriesMap = [
            'pulsa'       => ['Pulsa'],
            'data'        => ['Data'],
            'pln-token'   => ['PLN'],
            'pln-bill'    => ['PLN Pascabayar', 'Tagihan PLN'],      
            'e-money'     => ['E-Money'],
            'voucher-game'=> ['Games'],
            'streaming'   => ['TV', 'Streaming'],
        ];

        if (!array_key_exists($slug, $categoriesMap)) {
            abort(404);
        }

        $dbCategories = $categoriesMap[$slug];

        // Konfigurasi Halaman
        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt',
            'is_postpaid' => false
        ];

        // Custom Logic per Kategori
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
        } elseif ($slug == 'pln-bill') {
            $pageInfo['input_label'] = 'ID Pelanggan / Nomor Meter';
            $pageInfo['input_place'] = 'Contoh: 53xxxxxxxxx';
            $pageInfo['icon']        = 'fa-file-invoice-dollar';
            $pageInfo['is_postpaid'] = true; 
        }

        // Ambil Produk
        $products = PpobProduct::whereIn('category', $dbCategories)
            ->where('buyer_product_status', true)
            ->where('seller_product_status', true)
            ->orderBy('sell_price', 'asc')
            ->get();

        $brands = $products->pluck('brand')->unique()->values();
        $data   = compact('products', 'brands', 'weblogo', 'pageInfo');

        // Return View Khusus Admin
        // Pastikan file: resources/views/admin/ppob/category.blade.php ADA
        return view('admin.ppob.category', $data); 
    }

    /**
     * 3. Sync Produk (ADMIN ONLY)
     */
    public function sync()
    {
        // Proteksi Tambahan
        if (!auth()->user()->hasRole('Admin')) abort(403);

        $success = $this->digiflazz->syncProducts();

        if ($success) {
            return redirect()->back()->with('success', 'Sync Produk Berhasil!');
        }
        return redirect()->back()->with('error', 'Gagal Sync Produk.');
    }

    /**
     * 4. Cek Saldo Modal (ADMIN ONLY)
     */
    public function cekSaldo()
    {
        // Proteksi Tambahan
        if (!auth()->user()->hasRole('Admin')) abort(403);

        $result = $this->digiflazz->checkDeposit();

        if (request()->ajax()) {
            $result['formatted'] = 'Rp ' . number_format($result['deposit'], 0, ',', '.');
            return response()->json($result);
        }
        return redirect()->back();
    }

    // ... Method store, checkBill, checkPlnPrabayar bisa tetap sama ...
    // Karena method tersebut logic transaksinya universal.
    
    // ... (Sertakan method store, checkBill, checkPlnPrabayar dari kode sebelumnya di sini) ...
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

    /**
     * 6. AJAX: Cek Tagihan Pascabayar (PLN/PDAM)
     */
    public function checkBill(Request $request)
    {
        $request->validate(['customer_no' => 'required', 'sku' => 'required']);

        $refId = 'INQ-' . time() . rand(100,999);
        $response = $this->digiflazz->inquiryPasca($request->sku, $request->customer_no, $refId);

        if (isset($response['data'])) {
            $data = $response['data'];
            if ($data['rc'] === '00' || $data['status'] === 'Sukses') {
                return response()->json([
                    'status' => 'success',
                    'customer_name' => $data['customer_name'],
                    'customer_no' => $data['customer_no'],
                    'amount' => $data['selling_price'],
                    'desc' => $data['desc'] ?? [], 
                    'ref_id' => $refId 
                ]);
            } else {
                return response()->json(['status' => 'error', 'message' => $data['message'] ?? 'Tagihan tidak ditemukan.']);
            }
        }
        return response()->json(['status' => 'error', 'message' => 'Gagal terhubung ke server.']);
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
}