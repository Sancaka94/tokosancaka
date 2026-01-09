<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use App\Services\DigiflazzService;
use App\Services\FonnteService; // [ADD] Gunakan Service Fonnte agar rapi
use Exception;

class AgentTransactionController extends Controller
{
    protected $digiflazz;

    // Dependency Injection Service
    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
        
        // Setup Credentials diambil dari .env (LEBIH AMAN)
        // Pastikan di .env ada: DIGIFLAZZ_USERNAME, DIGIFLAZZ_KEY_PROD, dll
        $username = env('DIGIFLAZZ_USERNAME', 'mihetiDVGdeW');
        $apiKey   = env('DIGIFLAZZ_KEY_PROD', '1f48c69f-8676-5d56-a868-10a46a69f9b7');
        $isDev = false; // Atau buat env khusus DIGIFLAZZ_MODE_DEV
        
        $this->digiflazz->setCredentials($username, $apiKey, $isDev);
    }

    /**
     * Halaman Kasir / Transaksi Offline
     */
    public function create(Request $request)
    {
        $userId = Auth::id();

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
     * Proses Transaksi Offline & Kirim ke Digiflazz
     */
    public function store(Request $request)
    {
        // 1. Validasi
        $rules = [
            'customer_no' => 'required|numeric',
            'payment_type' => 'required|in:pra,pasca',
            // Pastikan ada input untuk no hp pembeli di form view Anda (name="customer_wa")
            'customer_wa'  => 'nullable|numeric|digits_between:10,15', 
        ];

        if ($request->payment_type == 'pra') {
            $rules['sku'] = 'required|exists:ppob_products,buyer_sku_code';
        } else {
            $rules['sku'] = 'required|string'; 
            $rules['selling_price'] = 'required|numeric|min:1';
            $rules['ref_id'] = 'required|string'; 
        }

        $request->validate($rules);
        $user = Auth::user();
        
        // 2. Persiapan Data
        $modalAgen = 0;
        $hargaJual = 0;
        $profit    = 0;
        $apiRefId  = ''; 

        if ($request->payment_type == 'pra') {
            // --- PRABAYAR ---
            // Gunakan Logic yang sama dengan Create untuk konsistensi harga
            $productData = PpobProduct::leftJoin('agent_product_prices', function($join) use ($user) {
                // Pastikan menggunakan kolom ID yang benar (id atau id_pengguna)
                $userIdCol = $user->getKeyName(); // Mendeteksi otomatis primary key user
                $join->on('ppob_products.id', '=', 'agent_product_prices.product_id')
                     ->where('agent_product_prices.user_id', '=', $user->$userIdCol);
            })
            ->where('buyer_sku_code', $request->sku)
            ->select('ppob_products.*', 'agent_product_prices.selling_price as custom_price')
            ->first();

            if (!$productData) return back()->with('error', 'Produk tidak ditemukan.');

            $modalAgen = $productData->sell_price;
            $hargaJual = $productData->custom_price ?? ($modalAgen + 2000); 
            
            $apiRefId = 'TRX-PRA-' . time() . rand(100, 999);

        } else {
            // --- PASCABAYAR ---
            $hargaJual = $request->selling_price; 
            $marginEstimasi = 2500; 
            $modalAgen = $hargaJual - $marginEstimasi;
            $apiRefId = $request->ref_id; 
        }

        $profit = $hargaJual - $modalAgen;

        // 3. Cek Saldo
        if ($user->saldo < $modalAgen) {
            return back()->with('error', 'Saldo tidak mencukupi.');
        }

        DB::beginTransaction();
        try {
            // Potong Saldo
            $user->decrement('saldo', $modalAgen);

            // Simpan Transaksi Lokal
            $trx = new PpobTransaction();
            // Perbaikan: Gunakan getKey() agar dinamis (baik 'id' maupun 'id_pengguna')
            $trx->user_id      = $user->getKey(); 
            $trx->order_id     = $apiRefId;
            $trx->buyer_sku_code = $request->sku;
            $trx->customer_no  = $request->customer_no;
            
            // [FIX UTAMA] Simpan Customer WA ke kolomnya langsung!
            // Agar webhook tidak error/null lagi saat ambil data
            $trx->customer_wa  = $request->customer_wa; 
            
            $trx->price        = $modalAgen;
            $trx->selling_price = $hargaJual;
            $trx->profit       = $profit;
            $trx->payment_method = 'SALDO_AGEN';
            $trx->status       = 'Processing';
            $trx->message      = 'Sedang diproses...';
            
            // Desc tetap disimpan sebagai cadangan/detail tambahan
            $trx->desc         = json_encode([
                'type' => $request->payment_type,
                'wa'   => $request->customer_wa ?? '-'
            ]);
            $trx->save();

            // 5. EKSEKUSI API DIGIFLAZZ
            $command = ($request->payment_type == 'pasca') ? 'pay-pasca' : null;

            $respData = $this->digiflazz->transaction(
                $request->sku, 
                $request->customer_no, 
                $apiRefId,
                0,
                $command 
            );
            
            // 6. Handle Response
            $statusApi = $respData['data']['status'] ?? 'Pending';
            $messageApi = $respData['data']['message'] ?? '-';
            
            // Jika Gagal
            if (in_array($statusApi, ['Gagal', 'Failed'])) {
                 $trx->status = 'Failed';
                 $trx->message = $messageApi;
                 $trx->sn = $respData['data']['sn'] ?? '';
                 $trx->save();
                 
                 // Refund Saldo Full
                 $user->increment('saldo', $modalAgen);
                 
                 DB::commit();
                 return back()->with('error', 'Transaksi Gagal: ' . $messageApi);
            }
            
            // Jika Sukses / Pending
            $trx->message = $messageApi;
            $trx->sn = $respData['data']['sn'] ?? '';
            
            // Update Data Real dari API (Price Adjustment)
            if (isset($respData['data']['price']) && $respData['data']['price'] > 0) {
                $realModal = $respData['data']['price'];
                $trx->price = $realModal;
                $trx->profit = $trx->selling_price - $realModal; // Recalculate Profit
                
                // Koreksi Saldo User (Refund kelebihan / Potong kekurangan)
                if ($realModal != $modalAgen) {
                    $selisih = $modalAgen - $realModal; 
                    if ($selisih > 0) {
                        $user->increment('saldo', $selisih); // Modal asli lebih murah -> Refund selisih
                    } elseif ($selisih < 0) {
                        $user->decrement('saldo', abs($selisih)); // Modal asli lebih mahal -> Potong lagi
                    }
                }
            }

            // Update Status Transaksi
            if ($statusApi == 'Sukses') {
                $trx->status = 'Success';
            } elseif ($statusApi == 'Pending') {
                $trx->status = 'Pending';
            }
            
            $trx->save();
            DB::commit(); // Commit dulu sebelum kirim WA (supaya data aman)

            // 7. NOTIFIKASI WA (JIKA SUKSES LANGSUNG)
            // Hanya kirim jika status SUKSES. Jika PENDING, biarkan Webhook yang mengirim nanti.
            if ($trx->status == 'Success') {
                
                $fmt = function($val) { return number_format($val, 0, ',', '.'); };

                // A. Pesan Pembeli
                if ($request->customer_wa) {
                    $msgPembeli = "TERIMA KASIH!\n\n";
                    $msgPembeli .= "Transaksi PPOB Berhasil.\n";
                    $msgPembeli .= "Produk: " . ($request->payment_type == 'pasca' ? 'Tagihan Pasca' : $trx->buyer_sku_code) . "\n";
                    $msgPembeli .= "No. Pel: " . $request->customer_no . "\n";
                    $msgPembeli .= "SN/Ref: " . ($trx->sn ?? '-') . "\n";
                    $msgPembeli .= "Total: Rp " . $fmt($trx->selling_price) . "\n\n";
                    $msgPembeli .= "Simpan bukti ini sebagai referensi.";
                    
                    // Gunakan Service Fonnte
                    FonnteService::sendMessage($request->customer_wa, $msgPembeli);
                }

                // B. Pesan Seller (Owner)
                $nomorSeller = $user->no_hp ?? $user->no_wa; 
                if ($nomorSeller) {
                    $msgSeller = "[INFO TRANSAKSI]\n";
                    $msgSeller .= "Status: SUKSES\n";
                    $msgSeller .= "Produk: " . ($request->payment_type == 'pasca' ? 'Tagihan Pasca' : $trx->buyer_sku_code) . "\n";
                    $msgSeller .= "Tujuan: " . $request->customer_no . "\n";
                    $msgSeller .= "SN: " . ($trx->sn ?? '-') . "\n";
                    $msgSeller .= "Profit: Rp " . $fmt($trx->profit) . "\n";
                    $msgSeller .= "Sisa Saldo: Rp " . $fmt($user->saldo);

                    FonnteService::sendMessage($nomorSeller, $msgSeller);
                }
            }

            $pesanBalikan = ($trx->status == 'Pending') 
                ? 'Transaksi Sedang Diproses. Mohon Tunggu.' 
                : 'Transaksi Berhasil! SN: ' . $trx->sn;

        return redirect('customer/ppob/history')->with('success', $pesanBalikan);
        
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Agent Transaction Error: ' . $e->getMessage());
            return back()->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Mengambil Daftar Kota PBB
     */
    public function getPbbCities(Request $request)
    {
        try {
            $query = DB::table('ppob_pbb_products')
                ->select('sku_code as sku', 'city_name as name')
                ->where('is_active', 1);

            if ($request->has('q') && !empty($request->q)) {
                $searchTerm = '%' . strtolower($request->q) . '%';
                $query->where(DB::raw('LOWER(city_name)'), 'like', $searchTerm);
            }

            $cities = $query->orderBy('city_name', 'asc')->get();
            
            $finalCities = $cities->map(function ($city) {
                return ['sku' => $city->sku, 'name' => $city->name];
            })->toArray();

            // Tambah test case manual jika belum ada
            $cimahiExists = collect($finalCities)->contains('sku', 'cimahi');
            if (!$cimahiExists) {
                 array_unshift($finalCities, ['sku' => 'cimahi', 'name' => 'CIMAHI (TEST CASE)']);
            }
            
            return response()->json(['success' => true, 'cities' => $finalCities]);

        } catch (\Exception $e) {
            Log::error('Get PBB Cities DB Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal data kota: ' . $e->getMessage()], 500);
        }
    }
}