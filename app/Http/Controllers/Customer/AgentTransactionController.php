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
    public function store(Request $request)
    {
        $request->validate([
            'sku' => 'required|exists:ppob_products,buyer_sku_code',
            'customer_no' => 'required|numeric|digits_between:9,15',
        ]);

        $user = Auth::user();
        
        // 1. Ambil Data Produk
        $productData = PpobProduct::leftJoin('agent_product_prices', function($join) use ($user) {
            $join->on('ppob_products.id', '=', 'agent_product_prices.product_id')
                 ->where('agent_product_prices.user_id', '=', $user->id);
        })
        ->where('buyer_sku_code', $request->sku)
        ->select('ppob_products.*', 'agent_product_prices.selling_price as custom_price')
        ->first();

        if (!$productData) return back()->with('error', 'Produk tidak ditemukan.');

        // 2. Hitung Harga & Cek Saldo
        $modalAgen = $productData->sell_price;
        $hargaJual = $productData->custom_price ?? ($modalAgen + 2000);
        $profit    = $hargaJual - $modalAgen;

        if ($user->saldo < $modalAgen) {
            return back()->with('error', 'Saldo tidak mencukupi.');
        }

        // ============================================================
        // PENGATURAN KREDENSIAL DAN ID
        // ============================================================
        
        $username = $this->digiflazzUsername; 
        $apiKey = $this->apiKeyProd;
        $testingMode = $this->testingMode;
        $orderId = 'TRX-OFFLINE-' . time() . rand(100, 999);
        
        // ⭐ PENTING: Set kredensial di Service sebelum transaksi
        $this->digiflazz->setCredentials($username, $apiKey, $testingMode); 

        // ============================================================
        // EKSEKUSI TRANSAKSI
        // ============================================================

        DB::beginTransaction();
        try {
            // 1. Potong Saldo Agen
            $user->decrement('saldo', $modalAgen);

            // 2. Simpan Transaksi Lokal
            $trx = new PpobTransaction();
            $trx->user_id        = $user->id_pengguna;
            $trx->order_id       = $orderId;
            $trx->buyer_sku_code = $request->sku;
            $trx->customer_no    = $request->customer_no;
            $trx->price          = $modalAgen;
            $trx->selling_price  = $hargaJual;
            $trx->profit         = $profit;
            $trx->payment_method = 'SALDO_AGEN';
            $trx->status         = 'Processing';
            $trx->message        = 'Sedang diproses ke Provider...';
            $trx->desc           = json_encode(['type' => 'offline_sale']);
            $trx->save();

            // 3. KIRIM KE DIGIFLAZZ (via Service)
            $respData = $this->digiflazz->transaction(
                $request->sku, 
                $request->customer_no, 
                $orderId, 
                0
            );
            
            // Cek apakah provider langsung merespon Gagal
            if (isset($respData['data']['status']) && in_array($respData['data']['status'], ['Gagal', 'Failed'])) {
                 $trx->status = 'Failed';
                 $trx->message = $respData['data']['message'] ?? 'Transaksi Gagal dari Pusat';
                 $trx->sn = $respData['data']['sn'] ?? '';
                 $trx->save();
                 
                 // Refund saldo agen karena gagal
                 $user->increment('saldo', $modalAgen);
                 
                 DB::commit();
                 return back()->with('error', 'Transaksi Gagal: ' . $trx->message);
            }
            
            // Update pesan jika ada info dari provider
            if (isset($respData['data']['message'])) {
                $trx->message = $respData['data']['message'];
                // Update SN jika sudah sukses atau pending
                if (isset($respData['data']['sn'])) {
                    $trx->sn = $respData['data']['sn'];
                }
                $trx->save();
            }

            DB::commit();

            return redirect()->route('agent.transaction.create')->with('success', 'Transaksi Berhasil Diproses! Silakan tunggu status berubah.');

        } catch (Exception $e) {
            DB::rollBack();
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