<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use App\Models\User;
use Exception;

class AgentTransactionController extends Controller
{
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
        // (Kita pakai sell_price sebagai acuan sorting server-side)
        ->orderBy('ppob_products.sell_price', 'asc')
        
        // 4. PENTING: Gunakan get() bukan paginate()
        // Agar JavaScript di frontend bisa memfilter SEMUA data (Indosat, Telkomsel, dll)
        // tanpa terpotong pagination.
        ->get();

    return view('customer.agent_transaction.create', compact('products'));
}
    /**
     * Proses Transaksi Offline (Potong Saldo Agen)
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
        // MULAI PERBAIKAN DD PAYLOAD (HARDCODED CREDENTIALS)
        // ============================================================
        
        // Kredensial Langsung (Tanpa ENV)
        $username = 'mihetiDVGdeW'; 
        $apiKey   = 'dev-d54808c0-87ed-11f0-bdb6-8d5622821215'; 
        $testingMode = true; // Set false jika sudah production

        $orderId  = 'TRX-OFFLINE-' . time() . rand(100, 999);
        
        // Generate Signature Asli: md5(username + key + ref_id)
        $validSign = md5($username . $apiKey . $orderId);

        // Payload Bersih (Siap Copy ke Postman)
        $cleanPayload = [
            'username'       => $username,
            'buyer_sku_code' => $request->sku,
            'customer_no'    => $request->customer_no,
            'ref_id'         => $orderId,
            'sign'           => $validSign,
            'testing'        => $testingMode,
        ];

        // Tampilkan JSON Rapi
        dd($cleanPayload); 

        // ============================================================
        // KODE KE BAWAH TIDAK AKAN DIEKSEKUSI SELAMA ADA DD DI ATAS
        // ============================================================

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
            $trx->message        = 'Transaksi Outlet Offline';
            $trx->desc           = json_encode(['type' => 'offline_sale']);
            $trx->save();

            // Panggil Service Digiflazz (Jika dd sudah dihapus)
            // Note: Pastikan Service Digiflazz Anda juga sudah tidak pakai ENV
            // $service = new DigiflazzService();
            // $resp = $service->transaction($request->sku, $request->customer_no, $orderId);

            DB::commit();

            return redirect()->route('agent.transaction.create')->with('success', 'Transaksi Berhasil Disimpan');

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}