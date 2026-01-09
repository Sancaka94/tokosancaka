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

class AgentRegistrationController extends Controller
{
    /**
     * Halaman Utama / Riwayat Transaksi Agen
     */
    public function index()
    {
        // 1. Ambil user yang login
        $user = Auth::user();

        // 2. (Opsional) Ambil riwayat transaksi jika ingin ditampilkan di halaman registrasi
        // Jika tidak dipakai di view, bagian ini bisa dihapus/diabaikan
        $transactions = PpobTransaction::where('user_id', $user->id_pengguna)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('customer.agent_registration.index', compact('user'));
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
        
        // Ambil Data Produk
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
            // Potong Saldo Dulu (Prabayar)
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
            $resp = $digiflazz->transaction($request->sku, $request->customer_no, $orderId);
            $d = $resp['data'] ?? [];

            // Update Info Instan (RC, SN, Desc)
            if(isset($d['rc'])) $trx->rc = $d['rc'];
            if(isset($d['sn'])) $trx->sn = $d['sn'];
            if(isset($d['message'])) $trx->message = $d['message'];
            // Simpan deskripsi lengkap (untuk struk)
            if(isset($d['desc'])) $trx->desc = json_encode($d['desc']); 

            // Handle Error Instan
            if (isset($d['status']) && in_array($d['status'], ['Gagal', 'Failed'])) {
                 $trx->status = 'Failed';
                 $trx->save();
                 
                 $user->increment('saldo', $modalAgen); // Refund Otomatis
                 DB::commit();
                 return back()->with('error', 'Transaksi Gagal: ' . $trx->message . ' (RC: ' . ($trx->rc ?? '-') . ')');
            }

            // Jika Sukses/Pending, simpan update
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
            'ref_id' => 'required', // Ref ID Inquiry
            'selling_price' => 'required|numeric' 
        ]);

        $user = Auth::user();
        
        // Cek Saldo Awal (Estimasi dari selling_price yang dikirim frontend)
        if ($user->saldo < $request->selling_price) {
            return back()->with('error', 'Saldo tidak mencukupi.');
        }

        DB::beginTransaction();
        try {
            // 1. Buat Transaksi Processing Dulu (Supaya tercatat)
            $trx = new PpobTransaction();
            $trx->user_id        = $user->id_pengguna;
            $trx->order_id       = $request->ref_id; // Pakai ID Inquiry agar sesuai flow Digiflazz
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
                $trx->rc = $d['rc'] ?? null; // Simpan RC (00 = Sukses)
                
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
}