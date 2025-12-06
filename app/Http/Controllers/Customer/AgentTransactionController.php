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
use App\Models\User;
use App\Services\DigiflazzService;
use Exception;

class AgentTransactionController extends Controller
{
    // --- KREDENSIAL DIGIFLAZZ HARDCODED ---
    private $digiflazzUsername = 'mihetiDVGdeW'; 
    private $apiKeyDev  = 'dev-d54808c0-87ed-11f0-bdb6-8d5622821215'; 
    private $apiKeyProd = '1f48c69f-8676-5d56-a868-10a46a69f9b7'; // KUNCI PRODUCTION BARU
    private $testingMode = false; // UBAH KE FALSE JIKA SUDAH SIAP PRODUCTION

    private function getCurrentApiKey()
    {
        return $this->testingMode ? $this->apiKeyDev : $this->apiKeyProd;
    }

    /**
     * Halaman Kasir / Transaksi Offline
     */
    public function create(Request $request)
    {
        $userId = Auth::id();

        // 1. Mulai Query dari PpobProduct yang aktif
        $products = PpobProduct::where('seller_product_status', 1)
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
                'ppob_products.sell_price as modal_agen', 
                'agent_product_prices.selling_price as harga_jual_agen' 
            )
            ->orderBy('ppob_products.sell_price', 'asc')
            ->get();

        return view('customer.agent_transaction.create', compact('products'));
    }

    /**
     * AJAX: Cek Tagihan Pascabayar (PLN, BPJS, PDAM, dll)
     */
    public function checkBill(Request $request)
    {
        $request->validate([
            'customer_no' => 'required',
            'sku' => 'required', 
            'ref_id' => 'required'
        ]);

        $digiflazz = new DigiflazzService();
        
        // Panggil inquiryPasca dengan kunci yang sesuai
        $apiKey = $this->getCurrentApiKey();
        $digiflazz->setCredentials($this->digiflazzUsername, $apiKey, $this->testingMode);
        
        $response = $digiflazz->inquiryPasca($request->sku, $request->customer_no, $request->ref_id);
        
        return response()->json($response);
    }

    /**
     * PROSES TRANSAKSI (PRABAYAR & PASCABAYAR)
     */
    public function store(Request $request)
    {
        if ($request->payment_type === 'pasca') {
            return $this->storePascabayar($request);
        } else {
            return $this->storePrabayar($request);
        }
    }

    /**
     * LOGIKA 1: Transaksi Prabayar (Pulsa, Data, Token)
     */
    private function storePrabayar(Request $request)
    {
        $request->validate([
            'sku' => 'required|exists:ppob_products,buyer_sku_code',
            'customer_no' => 'required|numeric|digits_between:9,20',
        ]);

        $user = Auth::user();
        
        $productData = PpobProduct::leftJoin('agent_product_prices', function($join) use ($user) {
            $join->on('ppob_products.id', '=', 'agent_product_prices.product_id')
                 ->where('agent_product_prices.user_id', '=', $user->id);
        })
        ->where('buyer_sku_code', $request->sku)
        ->select('ppob_products.*', 'agent_product_prices.selling_price as custom_price')
        ->first();

        if (!$productData) return back()->with('error', 'Produk tidak ditemukan.');

        $modalAgen = $productData->sell_price;
        $hargaJual = $productData->custom_price ?? ($modalAgen + 2000);
        $profit    = $hargaJual - $modalAgen;

        if ($user->saldo < $modalAgen) {
            return back()->with('error', 'Saldo tidak mencukupi.');
        }

        $orderId = 'TRX-' . time() . rand(100, 999);

        DB::beginTransaction();
        try {
            $user->decrement('saldo', $modalAgen);

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
            $trx->message        = 'Sedang diproses...';
            $trx->desc           = json_encode(['type' => 'offline_sale_prepaid']);
            $trx->save();

            // KIRIM KE DIGIFLAZZ
            $digiflazz = new DigiflazzService();
            // PENTING: Mengatur kredensial sebelum memanggil method service
            $digiflazz->setCredentials($this->digiflazzUsername, $this->getCurrentApiKey(), $this->testingMode);
            
            $resp = $digiflazz->transaction($request->sku, $request->customer_no, $orderId);
            $d = $resp['data'] ?? [];

            // Update Info Instan (RC, SN, Desc)
            if(isset($d['rc'])) $trx->rc = $d['rc'];
            if(isset($d['sn'])) $trx->sn = $d['sn'];
            if(isset($d['message'])) $trx->message = $d['message'];
            if(isset($d['desc'])) $trx->desc = json_encode($d['desc']); 

            // Handle Error Instan
            if (isset($d['status']) && in_array($d['status'], ['Gagal', 'Failed'])) {
                 $trx->status = 'Failed';
                 $trx->save();
                 $user->increment('saldo', $modalAgen); // Refund Otomatis
                 DB::commit();
                 return back()->with('error', 'Transaksi Gagal: ' . $trx->message . ' (RC: ' . ($trx->rc ?? '-') . ')');
            }

            $trx->save();

            DB::commit();
            return redirect()->route('agent.transaction.create')->with('success', 'Transaksi Prabayar Diproses!');

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * LOGIKA 2: Transaksi Pascabayar (Bayar Tagihan)
     */
    private function storePascabayar(Request $request)
    {
        $request->validate([
            'sku' => 'required',
            'customer_no' => 'required',
            'ref_id' => 'required', 
            'selling_price' => 'required|numeric' 
        ]);

        $user = Auth::user();
        
        if ($user->saldo < $request->selling_price) {
            return back()->with('error', 'Saldo tidak mencukupi.');
        }

        DB::beginTransaction();
        try {
            // 1. Buat Transaksi Processing Dulu (Supaya tercatat)
            $trx = new PpobTransaction();
            $trx->user_id        = $user->id_pengguna;
            $trx->order_id       = $request->ref_id; 
            $trx->buyer_sku_code = $request->sku;
            $trx->customer_no    = $request->customer_no;
            $trx->price          = 0; // Modal belum fix
            $trx->selling_price  = $request->selling_price;
            $trx->profit         = 0;
            $trx->payment_method = 'SALDO_AGEN';
            $trx->status         = 'Processing';
            $trx->message        = 'Melakukan Pembayaran...';
            $trx->desc           = json_encode(['type' => 'offline_sale_postpaid']);
            $trx->save();

            // 2. Eksekusi Pembayaran ke Digiflazz
            $digiflazz = new DigiflazzService();
            // PENTING: Mengatur kredensial sebelum memanggil method service
            $digiflazz->setCredentials($this->digiflazzUsername, $this->getCurrentApiKey(), $this->testingMode);
            
            $apiResponse = $digiflazz->payPasca($request->sku, $request->customer_no, $request->ref_id);
            $d = $apiResponse['data'] ?? [];

            // 3. Cek Status Pembayaran
            if (isset($d['status']) && ($d['status'] == 'Sukses' || $d['status'] == 'Pending' || $d['rc'] == '00')) {
                
                // Update Data Real dari API
                $modalReal = $d['price'] ?? 0;
                $trx->price = $modalReal;
                $trx->profit = $trx->selling_price - $modalReal;
                
                // Status & Detail
                $trx->status = ($d['status'] == 'Sukses' || $d['rc'] == '00') ? 'Success' : 'Pending';
                $trx->message = $d['message'] ?? 'Pembayaran Berhasil';
                $trx->sn = $d['sn'] ?? '';
                $trx->rc = $d['rc'] ?? null; 
                
                // PENTING: Simpan Deskripsi Lengkap (Nama, Lembar, Detail Item)
                if(isset($d['desc'])) {
                    $trx->desc = json_encode($d['desc']);
                }
                
                $trx->save();

                // Potong Saldo sesuai Modal Real
                if ($modalReal > 0) {
                    $user->decrement('saldo', $modalReal);
                }

                DB::commit();
                return redirect()->route('agent.transaction.create')->with('success', 'Pembayaran Tagihan Berhasil!');
            
            } else {
                // Gagal Bayar
                $trx->status = 'Failed';
                $trx->message = $d['message'] ?? 'Pembayaran Gagal';
                $trx->rc = $d['rc'] ?? null; // Simpan RC Error
                $trx->save();
                
                DB::commit(); // Commit status Failed, Saldo tidak terpotong
                return back()->with('error', 'Pembayaran Gagal: ' . $trx->message);
            }

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error System: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Mengambil Daftar Kota PBB dari Database Lokal
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
            
            $finalCities = $cities->map(function ($city) {
                return [
                    'sku' => $city->sku,
                    'name' => $city->name
                ];
            })->toArray();
            
            return response()->json(['success' => true, 'cities' => $finalCities]);

        } catch (\Exception $e) {
            Log::error('Get PBB Cities DB Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data kota dari database: ' . $e->getMessage()], 500);
        }
    }
}