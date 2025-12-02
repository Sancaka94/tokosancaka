<?php

namespace App\Http\Controllers;

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
        $this->digiflazz = $digiflazz;
    }

    private function getWebLogo()
    {
        $setting = Setting::where('key', 'logo')->first();
        return $setting ? $setting->value : null;
    }

    /**
     * 1. Halaman Utama PPOB
     */
    public function index()
    {
        $weblogo = $this->getWebLogo();
        return view('ppob.index', compact('weblogo'));
    }

    /**
     * 2. Sinkronisasi Data (Update Harga)
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
     * 3. Cek Saldo Admin (AJAX)
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

    public function category($slug)
    {
        $weblogo = $this->getWebLogo();

        // 1. Mapping Slug URL ke Kategori Database
        $categoriesMap = [
            'pulsa'       => ['Pulsa'],
            'data'        => ['Data'],
            'pln-token'   => ['PLN'],
            'pln-bill'    => ['PLN Pascabayar', 'Tagihan PLN'],      
            'e-money'     => ['E-Money'],
            'voucher-game'=> ['Games'],
            'streaming'   => ['TV', 'Streaming'],
        ];

        // Validasi Slug
        if (!array_key_exists($slug, $categoriesMap)) {
            abort(404);
        }

        $dbCategories = $categoriesMap[$slug];

        // 2. Konfigurasi Tampilan Halaman
        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt',
            'is_postpaid' => false
        ];

        // Logika Icon & Input Label
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

        // 3. Ambil Produk
        $products = PpobProduct::whereIn('category', $dbCategories)
            ->where('buyer_product_status', true)
            ->where('seller_product_status', true)
            ->orderBy('sell_price', 'asc')
            ->get();

        $brands = $products->pluck('brand')->unique()->values();
        $data   = compact('products', 'brands', 'weblogo', 'pageInfo');

        // ============================================================
        // 🧠 SMART VIEW DETECTION (DETEKSI ROLE & URL)
        // ============================================================
        
        // Ambil segmen pertama dari URL (contoh: tokosancaka.com/admin/...)
        $prefix = request()->segment(1); 

        // A. JIKA DIAKSES DARI ETALASE (PUBLIC MARKETPLACE)
        if ($prefix == 'etalase') {
            return view('etalase.ppob.category', $data);
        }

        // B. JIKA DIAKSES OLEH ADMIN (URL 'admin' atau Role Admin)
        if ($prefix == 'admin' || (auth()->check() && auth()->user()->hasRole('admin'))) {
            // Pastikan Anda punya view khusus admin, misal: resources/views/admin/ppob/category.blade.php
            // Jika belum ada, bisa pakai view default dulu
            return view('admin.ppob.category', $data); 
        }

        // C. JIKA DIAKSES OLEH SELLER (URL 'seller' atau Role Seller)
        if ($prefix == 'seller' || (auth()->check() && auth()->user()->hasRole('seller'))) {
            return view('seller.ppob.category', $data);
        }

        // D. JIKA DIAKSES OLEH CUSTOMER (MEMBER DASHBOARD)
        // Biasanya URL 'member', 'dashboard', atau user biasa yang login
        if ($prefix == 'member' || $prefix == 'customer' || (auth()->check() && auth()->user()->hasRole('customer'))) {
            return view('customer.ppob.category', $data);
        }

        // E. FALLBACK (DEFAULT)
        // Jika tidak terdeteksi (misal akses langsung /digital/pulsa tanpa prefix),
        // Kita bisa arahkan ke Etalase atau Admin tergantung kebijakan Anda.
        // Di sini saya arahkan ke Etalase (Public) agar aman.
        return view('etalase.ppob.category', $data);
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

            // Hit API Digiflazz (dengan Max Price Protection)
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
     * AJAX: Cek ID Pelanggan PLN Prabayar
     * URL: /digital/ajax/check-pln-prabayar
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
                    'segment_power' => $data['segment_power'], // R1 / 1300VA
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