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
        
        // Ambil Data Produk & Harga Custom Agen
        $productData = PpobProduct::leftJoin('agent_product_prices', function($join) use ($user) {
            $join->on('ppob_products.id', '=', 'agent_product_prices.product_id')
                 ->where('agent_product_prices.user_id', '=', $user->id);
        })
        ->where('buyer_sku_code', $request->sku)
        ->select(
            'ppob_products.*',
            'agent_product_prices.selling_price as custom_price'
        )
        ->first();

        if (!$productData) {
            return back()->with('error', 'Produk tidak ditemukan.');
        }

        // Tentukan Harga
        $modalAgen = $productData->sell_price; // Saldo yang akan dipotong
        $hargaJualKeUser = $productData->custom_price ?? ($modalAgen + 2000); // Harga di Struk/History
        $profitTunai = $hargaJualKeUser - $modalAgen; // Keuntungan cash agen

        // Cek Saldo
        if ($user->saldo < $modalAgen) {
            return back()->with('error', 'Saldo Anda tidak mencukupi. Modal yang dibutuhkan: Rp ' . number_format($modalAgen));
        }

        DB::beginTransaction();
        try {
            // 1. Potong Saldo Agen (Hanya Sebesar Modal)
            $user->decrement('saldo', $modalAgen);

            // 2. Buat ID Transaksi
            $orderId = 'TRX-OFFLINE-' . time() . rand(100, 999);

            // 3. Simpan Riwayat Transaksi
            $trx = new PpobTransaction();
            $trx->user_id = $user->id_pengguna;
            $trx->order_id = $orderId;
            $trx->buyer_sku_code = $request->sku;
            $trx->customer_no = $request->customer_no;
            
            // PENTING: Struktur Data untuk Laporan
            // price = Modal Agen (Uang keluar dari sistem)
            // selling_price = Harga Jual Agen (Untuk data struk)
            // profit = Profit Tunai (Hanya catatan, karena uangnya dipegang cash oleh agen)
            $trx->price = $modalAgen; 
            $trx->selling_price = $hargaJualKeUser; 
            $trx->profit = $profitTunai;
            
            $trx->payment_method = 'SALDO_AGEN';
            $trx->status = 'Processing'; // Nanti diproses cronjob/job queue ke Digiflazz
            $trx->message = 'Transaksi Outlet Offline';
            $trx->desc = json_encode([
                'type' => 'offline_sale',
                'customer_pay_cash' => true
            ]);
            $trx->save();

            // TODO: Panggil API Digiflazz di sini atau biarkan Worker yang memprosesnya
            // ... (Kode integrasi Digiflazz sama seperti sebelumnya) ...

            DB::commit();

            return redirect()->route('agent.transaction.create')
                ->with('success', "Transaksi Sukses! Saldo terpotong Rp " . number_format($modalAgen) . ". Silakan minta uang tunai Rp " . number_format($hargaJualKeUser) . " ke pembeli.");

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }
}