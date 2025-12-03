<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DigiflazzService;
use App\Models\Setting;
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use App\Models\Banner; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PublicPpobController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    /**
     * Halaman Utama Daftar Harga / Kategori (Frontend)
     * URL: /etalase/ppob/digital/{slug}
     */
    public function index($slug = 'pulsa') 
    {
        // 1. Mapping Kategori Database
        $categoriesMap = [
            'pulsa'          => ['Pulsa'],
            'data'           => ['Data'],
            'pln-token'      => ['PLN'],
            'pln-pascabayar' => ['PLN Pascabayar', 'Tagihan PLN'], 
            'pdam'           => ['PDAM'],
            'e-money'        => ['E-Money'],
            'voucher-game'   => ['Games'],
            'streaming'      => ['TV', 'Streaming'],
            'bpjs'           => ['BPJS'],
        ];

        // Fallback jika slug tidak dikenali
        if (!array_key_exists($slug, $categoriesMap)) {
            // Cek apakah slug mengandung kata kunci pascabayar
            if (str_contains($slug, 'pasca') || str_contains($slug, 'bill')) {
                // Biarkan lanjut, jangan abort 404 dulu
            } else {
                $slug = 'pulsa';
            }
        }

        $dbCategories = $categoriesMap[$slug] ?? [];

        // 2. Setup Variable $pageInfo Default
        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt',
            'is_postpaid' => false // Default false (Prabayar)
        ];

        // 3. LOGIC SAKTI: Deteksi Pascabayar Otomatis
        // Jika slug adalah salah satu dari ini, PAKSA mode Postpaid (Tombol Cek Muncul)
        $postpaidSlugs = ['pln-pascabayar', 'pdam', 'bpjs', 'gas', 'pbb', 'internet-pasca'];
        
        if (in_array($slug, $postpaidSlugs)) {
            $pageInfo['is_postpaid'] = true;
        }

        // Custom UI per Kategori
        switch ($slug) {
            case 'pln-token':
                $pageInfo['title']       = 'Token Listrik PLN';
                $pageInfo['input_label'] = 'Nomor Meter / ID Pelanggan';
                $pageInfo['input_place'] = 'Contoh: 141234567890';
                $pageInfo['icon']        = 'fa-bolt';
                break;

            case 'pln-pascabayar': 
                $pageInfo['title']       = 'Tagihan Listrik Pasca';
                $pageInfo['input_label'] = 'ID Pelanggan';
                $pageInfo['input_place'] = 'Contoh: 53xxxxxxxxx';
                $pageInfo['icon']        = 'fa-file-invoice-dollar';
                $pageInfo['is_postpaid'] = true; // Konfirmasi ulang
                break;

            case 'pdam':
                $pageInfo['title']       = 'Tagihan Air PDAM';
                $pageInfo['input_label'] = 'ID Pelanggan / No Sambungan';
                $pageInfo['icon']        = 'fa-faucet';
                $pageInfo['is_postpaid'] = true;
                break;
            
            case 'bpjs':
                $pageInfo['title']       = 'Bayar BPJS Kesehatan';
                $pageInfo['input_label'] = 'Nomor VA Keluarga';
                $pageInfo['icon']        = 'fa-heartbeat';
                $pageInfo['is_postpaid'] = true;
                break;

            case 'e-money':
                $pageInfo['title']       = 'Top Up E-Wallet';
                $pageInfo['icon']        = 'fa-wallet';
                break;

            case 'voucher-game':
                $pageInfo['title']       = 'Voucher Game';
                $pageInfo['input_label'] = 'ID Pemain (User ID)';
                $pageInfo['input_place'] = 'Masukkan ID Game';
                $pageInfo['icon']        = 'fa-gamepad';
                break;
                
            case 'streaming':
                $pageInfo['title']       = 'Voucher TV & Streaming';
                $pageInfo['icon']        = 'fa-tv';
                break;
        }

        // 4. Ambil Produk (Hanya jika ada kategori database)
        $products = [];
        if (!empty($dbCategories)) {
            $products = PpobProduct::whereIn('category', $dbCategories)
                ->where('buyer_product_status', true)
                ->where('seller_product_status', true)
                ->orderBy('sell_price', 'asc')
                ->get();
        }

        // 5. Data Pendukung View Public
        $brands = collect($products)->pluck('brand')->unique()->values();
        
        // Menu Kategori (Hardcoded agar urutan rapi & icon sesuai blade)
        $categories = [
            (object)['slug' => 'pulsa', 'name' => 'Pulsa'],
            (object)['slug' => 'data', 'name' => 'Data'],
            (object)['slug' => 'pln-token', 'name' => 'Token PLN'],
            (object)['slug' => 'pln-pascabayar', 'name' => 'PLN Pasca'],
            (object)['slug' => 'pdam', 'name' => 'PDAM'],
            (object)['slug' => 'e-money', 'name' => 'E-Wallet'],
            (object)['slug' => 'voucher-game', 'name' => 'Games'],
            (object)['slug' => 'streaming', 'name' => 'Streaming'],
        ];

        // Ambil Banners (Try Catch biar ga error kalau tabel kosong)
        try {
            $banners = Banner::where('status', 'active')->get();
        } catch (\Exception $e) {
            $banners = [];
        }

        // Ambil Settings Banner Kecil
        $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key');

        // 6. Return ke View PUBLIC (JANGAN KE ADMIN)
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
     * AJAX: Cek Tagihan Pascabayar (PLN/PDAM/BPJS)
     */
    public function checkBill(Request $request)
    {
        // Validasi input
        $request->validate([
            'customer_no' => 'required',
            'sku' => 'required'
        ]);

        $refId = 'INQ-' . time() . rand(100,999);
        
        // Panggil Service Digiflazz
        // Pastikan parameter SKU yang dikirim dari JS sesuai (pln, pdam, bpjs)
        $response = $this->digiflazz->inquiryPasca($request->sku, $request->customer_no, $refId);

        // Parsing Response
        if (isset($response['data'])) {
            $data = $response['data'];
            
            // Cek status sukses (Bisa 'Sukses', 'Pending', atau RC '00')
            if ($data['rc'] === '00' || $data['status'] === 'Sukses' || $data['status'] === 'Pending') {
                return response()->json([
                    'status'        => 'success',
                    'customer_name' => $data['customer_name'],
                    'customer_no'   => $data['customer_no'],
                    'amount'        => $data['selling_price'], // Harga Jual ke User
                    'desc'          => $data['desc'] ?? [], 
                    'ref_id'        => $refId 
                ]);
            } else {
                return response()->json([
                    'status'  => 'error', 
                    'message' => $data['message'] ?? 'Tagihan tidak ditemukan atau sudah terbayar.'
                ]);
            }
        }
        
        return response()->json([
            'status'  => 'error', 
            'message' => 'Gagal terhubung ke server provider. Coba lagi nanti.'
        ]);
    }

    /**
     * AJAX: Cek Nama PLN Prabayar (Token)
     */
    public function checkPlnPrabayar(Request $request)
    {
        $request->validate(['customer_no' => 'required']);
        
        $response = $this->digiflazz->inquiryPln($request->customer_no);

        if (isset($response['data']) && ($response['data']['rc'] === '00' || $response['data']['status'] === 'Sukses')) {
            return response()->json([
                'status'        => 'success',
                'name'          => $response['data']['name'],
                'segment_power' => $response['data']['segment_power'] ?? '-'
            ]);
        }
        
        return response()->json([
            'status'  => 'error', 
            'message' => 'Nomor meter tidak ditemukan atau salah.'
        ]);
    }

    /**
     * Proses Transaksi (Checkout)
     */
    public function store(Request $request)
    {
        // Cek Login
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk melanjutkan pembayaran.');
        }

        $request->validate([
            'buyer_sku_code' => 'required|exists:ppob_products,buyer_sku_code',
            'customer_no'    => 'required|numeric',
        ]);

        $user = Auth::user();
        $sku  = $request->buyer_sku_code;
        $noHp = $request->customer_no;

        // Ambil Data Produk
        $product = PpobProduct::where('buyer_sku_code', $sku)->first();

        // Cek Saldo User
        if ($user->saldo < $product->sell_price) {
            return redirect()->back()->with('error', 'Saldo tidak mencukupi. Silakan Top Up Saldo Anda.');
        }

        $refId = 'TRX-' . time() . rand(100, 999);

        DB::beginTransaction();
        try {
            // Potong Saldo
            $user->decrement('saldo', $product->sell_price);

            // Simpan Transaksi (Pending)
            $trx = PpobTransaction::create([
                'user_id'        => $user->id, // Sesuaikan primary key (id / id_pengguna)
                'order_id'       => $refId,
                'buyer_sku_code' => $sku,
                'customer_no'    => $noHp,
                'price'          => $product->price,
                'selling_price'  => $product->sell_price,
                'profit'         => $product->sell_price - $product->price,
                'status'         => 'Pending',
                'message'        => 'Sedang diproses...',
            ]);

            // Request ke Digiflazz
            $maxPrice = (int) $product->sell_price; 
            $response = $this->digiflazz->transaction($sku, $noHp, $refId, $maxPrice);

            if (isset($response['data'])) {
                $data = $response['data'];
                
                if ($data['status'] == 'Gagal') {
                    // Refund
                    $user->increment('saldo', $product->sell_price);
                    $trx->update([
                        'status'  => 'Gagal', 
                        'message' => $data['message'], 
                        'sn'      => $data['sn'] ?? null
                    ]);
                    DB::commit();
                    return redirect()->back()->with('error', 'Transaksi Gagal: ' . $data['message']);
                } else {
                    // Sukses/Pending
                    $trx->update([
                        'status'  => $data['status'], 
                        'message' => $data['message'], 
                        'sn'      => $data['sn'] ?? null
                    ]);
                    DB::commit();
                    return redirect()->back()->with('success', 'Transaksi Berhasil Diproses! Status: ' . $data['status']);
                }
            } else {
                // Timeout/Error Koneksi -> Refund
                $user->increment('saldo', $product->sell_price);
                $trx->update(['status' => 'Gagal', 'message' => 'Koneksi ke provider gagal']);
                DB::commit();
                return redirect()->back()->with('error', 'Gagal terhubung ke provider. Saldo telah dikembalikan.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Transaksi Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }
}