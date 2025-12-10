<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use App\Services\DigiflazzService; // PENTING: Service sudah di-import

use App\Models\User;
use Exception;

class AgentTransactionController extends Controller
{
    // ⭐ PROPERTI KREDENSIAL YANG DIGUNAKAN DI CONTROLLER INI
    protected $digiflazzUsername = 'mihetiDVGdeW';
    protected $apiKeyProd = '1f48c69f-8676-5d56-a868-10a46a69f9b7'; 
    protected $testingMode = false; // Set false untuk Production (Live API)

    protected $digiflazz;
    
    // Dependency Injection Service
    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }


    /**
     * Halaman Kasir / Transaksi Offline
     */
    public function create(Request $request)
    {
        $userId = Auth::id();

        // 1. Mulai Query dari PpobProduct yang aktif
        $products = PpobProduct::where('seller_product_status', 1)
            // 2. Join ke tabel harga agen untuk dapat harga khusus (jika ada)
            ->leftJoin('agent_product_prices', function($join) use ($userId) {
                $join->on('ppob_products.id', '=', 'agent_product_prices.product_id')
                     ->where('agent_product_prices.user_id', '=', $userId);
            })
            ->select(
                'ppob_products.id',
                'ppob_products.product_name',
                'ppob_products.buyer_sku_code',
                'ppob_products.brand',
                'ppob_products.category',
                'ppob_products.sell_price as modal_agen', // Harga dasar
                'agent_product_prices.selling_price as harga_jual_agen' // Harga settingan agen (bisa null)
            )
            // 3. Sorting: Urutkan dari Harga Modal Termurah ke Termahal
            ->orderBy('ppob_products.sell_price', 'asc')
            ->get();

        return view('customer.agent_transaction.create', compact('products'));
    }

    /**
     * Proses Transaksi Offline & Kirim ke Digiflazz
     */
    /**
     * Proses Transaksi Offline & Kirim ke Digiflazz
     */
    public function store(Request $request)
    {
        // A. Tentukan Jenis Validasi
        $rules = [
            'customer_no' => 'required|numeric',
            'payment_type' => 'required|in:pra,pasca', // Tambahkan validasi tipe
        ];

        // Jika Prabayar, SKU wajib ada di DB. Jika Pasca, SKU bebas (diterima dari API).
        if ($request->payment_type == 'pra') {
            $rules['sku'] = 'required|exists:ppob_products,buyer_sku_code';
        } else {
            $rules['sku'] = 'required|string'; // Pasca tidak wajib exists di DB
            $rules['selling_price'] = 'required|numeric|min:1'; // Wajib kirim harga jual hasil inquiry
        }

        $request->validate($rules);

        $user = Auth::user();
        
        // ============================================================
        // 1. PERSIAPAN DATA (HARGA & SKU)
        // ============================================================
        
        $modalAgen = 0;
        $hargaJual = 0;
        $profit    = 0;
        $productName = '';

        if ($request->payment_type == 'pra') {
            // --- LOGIKA PRABAYAR (Ambil dari Database) ---
            $productData = PpobProduct::leftJoin('agent_product_prices', function($join) use ($user) {
                $join->on('ppob_products.id', '=', 'agent_product_prices.product_id')
                     ->where('agent_product_prices.user_id', '=', $user->id);
            })
            ->where('buyer_sku_code', $request->sku)
            ->select('ppob_products.*', 'agent_product_prices.selling_price as custom_price')
            ->first();

            if (!$productData) return back()->with('error', 'Produk Prabayar tidak ditemukan.');

            $modalAgen = $productData->sell_price; // Harga Modal dari DB
            $hargaJual = $productData->custom_price ?? ($modalAgen + 2000);
            $productName = $productData->product_name;

        } else {
            // --- LOGIKA PASCABAYAR (Ambil dari Input Inquiry) ---
            // Harga jual dikirim dari frontend (hasil inquiry + margin)
            $hargaJual = $request->selling_price; 
            
            // Estimasi modal (Harga Jual - Margin Admin). 
            // Margin admin biasanya statis misal 2500 atau 3000, sesuaikan dengan frontend.
            // Di sini kita asumsikan profit 2500 agar tercatat di laporan.
            $estimasiProfit = 2500; 
            $modalAgen = $hargaJual - $estimasiProfit; 
            
            $productName = 'Tagihan Pascabayar (' . strtoupper($request->sku) . ')';
        }

        $profit = $hargaJual - $modalAgen;

        // 2. Cek Saldo
        if ($user->saldo < $modalAgen) {
            return back()->with('error', 'Saldo Agen tidak mencukupi. Dibutuhkan: Rp ' . number_format($modalAgen));
        }

        // ============================================================
        // 3. PROSES TRANSAKSI
        // ============================================================
        
        $username = $this->digiflazzUsername; 
        $apiKey = $this->apiKeyProd;
        $testingMode = $this->testingMode;
        
        // Generate Order ID Unik
        $orderId = 'TRX-' . strtoupper($request->payment_type) . '-' . time() . rand(100, 999);
        
        $this->digiflazz->setCredentials($username, $apiKey, $testingMode); 

        DB::beginTransaction();
        try {
            // A. Potong Saldo Agen (Sesuai Modal)
            $user->decrement('saldo', $modalAgen);

            // B. Simpan Transaksi Lokal
            $trx = new PpobTransaction();
            $trx->user_id        = $user->id_pengguna; // Pastikan kolom di DB user_id atau id_pengguna
            $trx->order_id       = $orderId;
            $trx->buyer_sku_code = $request->sku;
            $trx->customer_no    = $request->customer_no;
            $trx->price          = $modalAgen;
            $trx->selling_price  = $hargaJual;
            $trx->profit         = $profit;
            $trx->payment_method = 'SALDO_AGEN';
            $trx->status         = 'Processing';
            $trx->message        = 'Sedang diproses ke Provider...';
            // Simpan info tambahan seperti WA jika ada
            $trx->desc           = json_encode([
                'type' => $request->payment_type == 'pra' ? 'prepaid' : 'postpaid',
                'wa'   => $request->customer_wa ?? '-'
            ]);
            $trx->save();

            // C. KIRIM KE DIGIFLAZZ
            // Jika Pasca: Kirim commands 'pay-pasca', Jika Pra: default (topup)
            $commands = $request->payment_type == 'pasca' ? 'pay-pasca' : null;

            // Pastikan ref_id inquiry dikirim untuk Pascabayar (PENTING UNTUK BAYAR TAGIHAN)
            // Di frontend, pastikan input hidden name="ref_id" terisi saat inquiry sukses
            $refId = $request->ref_id ?? $orderId; 

            $respData = $this->digiflazz->transaction(
                $request->sku, 
                $request->customer_no, 
                $refId, // Gunakan Ref ID Inquiry untuk Pasca
                0, // Harga max (biasanya 0 utk prod)
                $commands 
            );
            
            // D. Handling Respon
            if (isset($respData['data']['status']) && in_array($respData['data']['status'], ['Gagal', 'Failed'])) {
                 $trx->status = 'Failed';
                 $trx->message = $respData['data']['message'] ?? 'Transaksi Gagal dari Pusat';
                 $trx->sn = $respData['data']['sn'] ?? '';
                 $trx->save();
                 
                 // Refund saldo agen
                 $user->increment('saldo', $modalAgen);
                 
                 DB::commit();
                 return back()->with('error', 'Transaksi Gagal: ' . $trx->message);
            }
            
            // Update Sukses / Pending
            if (isset($respData['data']['message'])) {
                $trx->message = $respData['data']['message'];
                if (isset($respData['data']['sn'])) {
                    $trx->sn = $respData['data']['sn'];
                }
                // Jika status langsung sukses dari API
                if(isset($respData['data']['status']) && $respData['data']['status'] == 'Sukses') {
                    $trx->status = 'Success';
                }
                $trx->save();
            }

            DB::commit();

            return redirect()->route('agent.transaction.create')->with('success', 'Transaksi Berhasil Diproses! Silakan cek riwayat.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Transaction Error: " . $e->getMessage());
            return back()->with('error', 'Error System: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Mengambil Daftar Kota PBB dari Database Lokal (Dilengkapi Pencarian)
     * Endpoint: /agent/ppob/cities
     */
    public function getPbbCities(Request $request)
    {
        try {
            $query = DB::table('ppob_pbb_products')
                ->select('sku_code as sku', 'city_name as name')
                ->where('is_active', 1);

            // LOGIKA PENCARIAN
            if ($request->has('q') && !empty($request->q)) {
                $searchTerm = '%' . strtolower($request->q) . '%';
                $query->where(DB::raw('LOWER(city_name)'), 'like', $searchTerm);
            }

            $cities = $query->orderBy('city_name', 'asc')
                ->get();
            
            // Konversi ke array sederhana untuk JSON response
            $finalCities = $cities->map(function ($city) {
                return [
                    'sku' => $city->sku,
                    'name' => $city->name
                ];
            })->toArray();

            // PENTING: Untuk memastikan CIMAHI test case tersedia jika Anda menggunakannya
            $cimahiExists = false;
            foreach ($finalCities as $city) {
                if ($city['sku'] === 'cimahi') {
                    $cimahiExists = true;
                    break;
                }
            }
            if (!$cimahiExists) {
                 // Tambahkan CIMAHI (TEST CASE) di awal
                 array_unshift($finalCities, ['sku' => 'cimahi', 'name' => 'CIMAHI (TEST CASE)']);
            }
            
            return response()->json(['success' => true, 'cities' => $finalCities]);

        } catch (\Exception $e) {
            Log::error('Get PBB Cities DB Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data kota dari database: ' . $e->getMessage()], 500);
        }
    }
}