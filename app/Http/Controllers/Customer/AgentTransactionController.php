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
        // 1. Validasi
        $rules = [
            'customer_no' => 'required|numeric',
            'payment_type' => 'required|in:pra,pasca',
        ];

        if ($request->payment_type == 'pra') {
            $rules['sku'] = 'required|exists:ppob_products,buyer_sku_code';
        } else {
            // PENTING: Pascabayar wajib punya ref_id dari inquiry sebelumnya
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
        
        // Tentukan Order ID / Ref ID yang akan dikirim ke API
        $apiRefId = ''; 

        if ($request->payment_type == 'pra') {
            // --- PRABAYAR ---
            $productData = PpobProduct::leftJoin('agent_product_prices', function($join) use ($user) {
                $join->on('ppob_products.id', '=', 'agent_product_prices.product_id')
                     ->where('agent_product_prices.user_id', '=', $user->id_pengguna);
            })->where('buyer_sku_code', $request->sku)->select('ppob_products.*', 'agent_product_prices.selling_price as custom_price')->first();

            if (!$productData) return back()->with('error', 'Produk tidak ditemukan.');

            $modalAgen = $productData->sell_price;
            $hargaJual = $productData->custom_price ?? ($modalAgen + 2000); // Margin default
            
            // Prabayar: Generate ID Baru
            $apiRefId = 'TRX-PRA-' . time() . rand(100, 999);

        } else {
            // --- PASCABAYAR ---
            // Harga Jual dari Input (Hasil Inquiry + Margin di Frontend)
            $hargaJual = $request->selling_price; 
            
            // Estimasi Modal = Harga Jual - Margin (Misal 2500)
            // Di real case, nanti modal akan diupdate sesuai respon sukses API ('price')
            $marginEstimasi = 2500; 
            $modalAgen = $hargaJual - $marginEstimasi;

            // PENTING: Pascabayar HARUS pakai Ref ID Inquiry
            $apiRefId = $request->ref_id; 
        }

        $profit = $hargaJual - $modalAgen;

        // 3. Cek Saldo
        if ($user->saldo < $modalAgen) {
            return back()->with('error', 'Saldo tidak mencukupi.');
        }

        // 4. Set Service
        $this->digiflazz->setCredentials($this->digiflazzUsername, $this->apiKeyProd, $this->testingMode); 

        DB::beginTransaction();
        try {
            // Potong Saldo
            $user->decrement('saldo', $modalAgen);

            // Simpan Transaksi Lokal
            $trx = new PpobTransaction();
            $trx->user_id        = $user->id_pengguna; // FIX: Sesuai kolom DB Anda
            $trx->order_id       = $apiRefId;          // Simpan Ref ID yang sama
            $trx->buyer_sku_code = $request->sku;
            $trx->customer_no    = $request->customer_no;
            $trx->price          = $modalAgen;
            $trx->selling_price  = $hargaJual;
            $trx->profit         = $profit;
            $trx->payment_method = 'SALDO_AGEN';
            $trx->status         = 'Processing';
            $trx->message        = 'Sedang diproses...';
            $trx->desc           = json_encode([
                'type' => $request->payment_type,
                'wa'   => $request->customer_wa ?? '-'
            ]);
            $trx->save();

            // 5. EKSEKUSI API DIGIFLAZZ
            // Parameter Pasca: command='pay-pasca'
            $command = ($request->payment_type == 'pasca') ? 'pay-pasca' : null;

            $respData = $this->digiflazz->transaction(
                $request->sku, 
                $request->customer_no, 
                $apiRefId, // Menggunakan Ref ID yang benar
                0,
                $command 
            );
            
            // 6. Handle Response
            $status = $respData['data']['status'] ?? 'Pending';
            $message = $respData['data']['message'] ?? '-';
            
            // Jika Gagal
            if (in_array($status, ['Gagal', 'Failed'])) {
                 $trx->status = 'Failed';
                 $trx->message = $message;
                 $trx->sn = $respData['data']['sn'] ?? '';
                 $trx->save();
                 
                 // Refund Saldo
                 $user->increment('saldo', $modalAgen);
                 DB::commit();
                 return back()->with('error', 'Transaksi Gagal: ' . $message);
            }
            
            // Jika Sukses / Pending
            $trx->message = $message;
            $trx->sn = $respData['data']['sn'] ?? '';
            
            // Update Data Real dari API (Price & Selling Price)
            if (isset($respData['data']['price'])) {
                // Update Modal Asli dari API
                $realModal = $respData['data']['price'];
                $trx->price = $realModal;
                
                // Hitung ulang profit (Selling price tetap sesuai input user, modal berubah)
                $trx->profit = $trx->selling_price - $realModal;
                
                // Koreksi pemotongan saldo jika ada selisih estimasi vs real
                if ($realModal != $modalAgen) {
                    $selisih = $modalAgen - $realModal; 
                    // Jika modal asli lebih murah, kembalikan selisih ke user
                    // Jika modal asli lebih mahal, potong lagi (biasanya jarang terjadi di pasca krn admin fee fix)
                    if ($selisih > 0) {
                        $user->increment('saldo', $selisih);
                    } elseif ($selisih < 0) {
                        $user->decrement('saldo', abs($selisih));
                    }
                }
            }

          // Jika Sukses (Langsung dari API)
            if ($status == 'Sukses') {
                $trx->status = 'Success';
                
                // 1. Pesan untuk PEMBELI
                if ($request->customer_wa) {
                    $msgPembeli = "TERIMA KASIH!\n\n";
                    $msgPembeli .= "Transaksi PPOB Berhasil.\n";
                    $msgPembeli .= "Produk: " . ($request->payment_type == 'pasca' ? 'Tagihan Pascabayar' : $trx->buyer_sku_code) . "\n";
                    $msgPembeli .= "No. Pel: " . $request->customer_no . "\n";
                    $msgPembeli .= "SN/Ref: " . ($trx->sn ?? '-') . "\n";
                    $msgPembeli .= "Total: Rp " . number_format($trx->selling_price, 0, ',', '.') . "\n\n";
                    $msgPembeli .= "Simpan bukti ini sebagai referensi.";
                    
                    $this->sendWhatsApp($request->customer_wa, $msgPembeli);
                }

                $nomorSeller = $user->no_hp; // <--- GANTI 'no_hp' SESUAI KOLOM DI DATABASE ANDA
                
                if ($nomorSeller) {
                    $msgSeller = "[INFO TRANSAKSI]\n";
                    $msgSeller .= "Status: SUKSES\n";
                    $msgSeller .= "Produk: " . ($request->payment_type == 'pasca' ? 'Tagihan Pasca' : $trx->buyer_sku_code) . "\n";
                    $msgSeller .= "Tujuan: " . $request->customer_no . "\n";
                    $msgSeller .= "SN: " . ($trx->sn ?? '-') . "\n";
                    $msgSeller .= "Profit: Rp " . number_format($trx->profit, 0, ',', '.') . "\n";
                    $msgSeller .= "Sisa Saldo: Rp " . number_format($user->saldo, 0, ',', '.');

                    $this->sendWhatsApp($nomorSeller, $msgSeller);
                }
                // ---------------------------
            }
            
            $trx->save();
            DB::commit();

            return redirect()->route('agent.transaction.create')->with('success', 'Transaksi Berhasil! SN: ' . $trx->sn);

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'System Error: ' . $e->getMessage());
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

    /**
     * Helper: Kirim Notifikasi WA via Fonnte
     */
    private function sendWhatsApp($target, $message)
    {
        try {
            // Pastikan nomor diawali 62 (Fonnte biasanya butuh 62 atau 08, tapi 62 lebih aman)
            if (substr($target, 0, 1) == '0') {
                $target = '62' . substr($target, 1);
            }

            $token = 'tw2v5GDT1u4pZ4iBHUbD'; // <--- GANTI DENGAN TOKEN FONNTE ASLI ANDA

            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', // optional
            ]);

            Log::info("WA Sent to $target: " . $response->body());
        } catch (\Exception $e) {
            Log::error("WA Gagal: " . $e->getMessage());
        }
    }
}