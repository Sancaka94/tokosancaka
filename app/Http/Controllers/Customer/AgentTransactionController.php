<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; // Tambahkan Import Ini
use Illuminate\Support\Facades\Log;  // Tambahkan Import Ini
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use App\Services\DigiflazzService; // <<< PASTIKAN INI ADA

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
        // PERSIAPAN PAYLOAD (HARDCODED CREDENTIALS)
        // ============================================================
        
        // Kredensial Langsung
        $username = 'mihetiDVGdeW'; 
        $apiKey   = 'dev-d54808c0-87ed-11f0-bdb6-8d5622821215'; 
        $testingMode = true; // Set false jika sudah production

        $orderId  = 'TRX-OFFLINE-' . time() . rand(100, 999);
        
        // Generate Signature: md5(username + key + ref_id)
        $validSign = md5($username . $apiKey . $orderId);

        // Payload Bersih
        $cleanPayload = [
            'username'       => $username,
            'buyer_sku_code' => $request->sku,
            'customer_no'    => $request->customer_no,
            'ref_id'         => $orderId,
            'sign'           => $validSign,
            'testing'        => $testingMode,
        ];

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
            $trx->status         = 'Processing'; // Status awal processing
            $trx->message        = 'Sedang diproses ke Provider...';
            $trx->desc           = json_encode(['type' => 'offline_sale']);
            $trx->save();

            // 3. KIRIM KE DIGIFLAZZ (Langsung dari Controller)
            // Timeout 30 detik untuk menghindari loading lama
            $response = Http::timeout(30)->post('https://api.digiflazz.com/v1/transaction', $cleanPayload);
            
            $respData = $response->json();
            
            // Log respon untuk debugging (cek di storage/logs/laravel.log)
            Log::info('Digiflazz Response:', $respData ?? []);

            // Cek apakah provider langsung merespon Gagal (misal: nomor salah/produk gangguan)
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
            
            // Update pesan jika ada info dari provider (misal: "Transaksi Pending")
            if (isset($respData['data']['message'])) {
                $trx->message = $respData['data']['message'];
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
     * AJAX: Mengambil Daftar Kota PBB dari API Digiflazz
     * Endpoint: /agent/ppob/cities
     */
    public function getPbbCities(DigiflazzService $digiflazz)
    {
        try {
            // Memanggil fungsi di DigiflazzService (asumsi fungsi ini mengambil semua produk PBB)
            $products = $digiflazz->getPbbProducts(); 

            $uniqueCities = [];
            
            // Filter hanya produk PBB dan hapus duplikasi
            foreach ($products as $product) {
                $sku = strtolower($product['sku'] ?? '');
                $name = $product['name'] ?? $product['brand'] ?? 'PBB';

                // Kita mencari produk yang nama atau SKU nya mengandung PBB/Pajak (misal: cimahi, pdl)
                // Filter hanya produk PBB yang memiliki SKU spesifik kota/daerah
                if (str_contains(strtolower($name), 'pbb') && !isset($uniqueCities[$sku])) {
                    $uniqueCities[$sku] = [
                        'sku' => $sku,
                        // Hanya ambil nama produk/kota/daerah PBB
                        'name' => str_replace(['PBB Kabupaten ', 'PBB Kota ', 'PBB '], '', $name)
                    ];
                }
            }

            // Urutkan berdasarkan nama kota
            $finalCities = array_values($uniqueCities);
            usort($finalCities, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            // Tambahkan test case CIMAHI jika belum ada (hanya untuk testing)
            // Ini akan memastikan list tidak kosong saat testing di sandbox
            if (!isset($uniqueCities['cimahi'])) {
                 array_unshift($finalCities, ['sku' => 'cimahi', 'name' => 'CIMAHI (TEST CASE)']);
            }

            return response()->json(['success' => true, 'cities' => $finalCities]);

        } catch (\Exception $e) {
            Log::error('Get PBB Cities Error: ' . $e->getMessage());
            // PENTING: Return JSON 500 agar frontend tahu terjadi error PHP
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data kota: ' . $e->getMessage()], 500);
        }
    }
}