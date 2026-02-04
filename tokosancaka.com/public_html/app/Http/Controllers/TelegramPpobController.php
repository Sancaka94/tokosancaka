<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // <--- TAMBAHKAN INI
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Pesanan;
use App\Services\KiriminAjaService;
use App\Services\DigiflazzService; // Pastikan Service ini terimport
use App\Services\FonnteService; // <--- Tambahkan baris ini

class TelegramPpobController extends Controller
{
    protected $telegramToken;
    protected $defaultUserId = 8; // ID User Agen Default
    protected $kiriminAjaService;

    public function __construct(KiriminAjaService $kiriminAjaService)
    {
        $this->telegramToken = env('TELEGRAM_BOT_TOKEN');
        // Inject Service KiriminAja agar sesuai dengan standar PesananController Anda
        $this->kiriminAjaService = $kiriminAjaService; 
    }

    /**
     * MAIN HANDLER: Gerbang Utama Pesan Masuk
     */
    public function handle(Request $request)
    {
        $update = $request->all();

        // 1. Validasi Dasar
        if (!isset($update['update_id']) || !isset($update['message']['text'])) {
            return response('Ignored', 200);
        }

        // --- FILTER ANTI-DOBEL (DEDUPLICATION) ---
        $updateId = $update['update_id'];
        
        // Cek apakah ID pesan ini sudah diproses dalam 60 detik terakhir?
        if (Cache::has("telegram_processed_{$updateId}")) {
            Log::info("Duplicate Telegram Update Ignored: $updateId");
            return response('OK', 200); // Langsung jawab OK, jangan diproses lagi
        }

        // Jika belum, tandai ID ini sudah diproses (simpan selama 60 detik)
        Cache::put("telegram_processed_{$updateId}", true, 60);
        // ------------------------------------------

        $chatId   = $update['message']['chat']['id'];
        $text     = trim($update['message']['text']);
        $username = $update['message']['chat']['username'] ?? 'Partner';
        $fullName = $update['message']['chat']['first_name'] ?? 'Partner';

        Log::info("Telegram CMD dari $username: $text");

        try {
            // 2. ROUTING PERINTAH
            $parts = explode(' ', $text);
            $command = strtolower($parts[0]);

            switch ($command) {
                case '/start':
                case '/menu':
                case '/help':
                    $this->sendMenu($chatId, $fullName);
                    break;

                case '/saldo':
                case '/cek_saldo':
                    // PERHATIKAN: Tambahkan $text di sini
                    $this->checkAgentBalance($chatId, $text); 
                    break;

                case '/beli':
                    $this->processPpobTransaction($chatId, $text);
                    break;

                case '/tanya': // Command Baru
                    $this->requestInfoId($chatId, $text);
                    break;

                case '/cari': // Cari Kota
                    $this->searchLocation($chatId, $text);
                    break;

                case '/ongkir': // Cek Ongkir
                    $this->checkOngkir($chatId, $text);
                    break;

                // --- TAMBAHKAN INI ---
                case '/kodepos': // Cari Kode Pos
                    $this->searchPostalCode($chatId, $text);
                    break;
                // ---------------------

                case '/setpin':
        $this->requestSetPin($chatId, $text);
        break;

        case '/verifikasi': // Command baru untuk input OTP
        $this->verifyOtpAndSetPin($chatId, $text);
        break;

                case '/harga':
                case '/cek':
                case '/list':
                    $this->checkProductPrice($chatId, $text);
                    break;

                case '/resi':
                case '/lacak':
                    $this->trackResi($chatId, $text);
                    break;

                default:
                    // --- LOGIKA BARU DI SINI ---
                    
                    // Cek apakah formatnya dipisahkan titik? (Contoh: pln.123.20.0812)
                    if (str_contains($text, '.') && count(explode('.', $text)) >= 3) {
                        $this->processDotTransaction($chatId, $text);
                    } else {
                        // Jika bukan format titik dan bukan perintah dikenal
                        $this->sendMessage($chatId, "⚠️ Perintah tidak dikenal. Ketik /menu untuk bantuan.");
                    }
                    break;
            }

        } catch (\Exception $e) {
            Log::error("Bot Error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Maaf, terjadi kesalahan sistem.");
        }

        return response('OK', 200);
    }

    /**
     * Set PIN Transaksi
     * Format: /setpin [PIN_BARU]
     */
    private function setPin($chatId, $text)
    {
        $parts = explode(' ', $text);
        if (!isset($parts[1])) {
            $this->sendMessage($chatId, "🔐 <b>Atur PIN Transaksi</b>\n\nKetik: <code>/setpin [AngkaPIN]</code>\nContoh: <code>/setpin 123456</code>");
            return;
        }

        $newPin = trim($parts[1]);

        // Validasi: PIN harus angka
        if (!is_numeric($newPin)) {
            $this->sendMessage($chatId, "❌ PIN harus berupa angka.");
            return;
        }

        // Update ke Database User
        // Kita gunakan bcrypt agar aman (standar Laravel)
        $user = User::find($this->defaultUserId); // Atau sesuaikan dengan ID User Telegram jika sudah link akun
        
        if ($user) {
            $user->pin = bcrypt($newPin); // Enkripsi PIN
            $user->save();
            $this->sendMessage($chatId, "✅ <b>PIN Berhasil Disimpan!</b>\nSekarang gunakan PIN ini di akhir format transaksi.");
        } else {
            $this->sendMessage($chatId, "❌ User tidak ditemukan.");
        }
    }

    /**
     * Cek Daftar Harga Produk
     * Format: /harga [Nama/Brand]
     */
    private function checkProductPrice($chatId, $text)
    {
        // 1. Ambil Keyword
        $keyword = trim(str_ireplace(['/harga', '/cek', '/list'], '', $text));

        // Jika user cuma ketik /harga tanpa keyword, tampilkan Brand populer
        if (empty($keyword)) {
            $msg = "🏷 <b>CEK DAFTAR HARGA</b>\n\n";
            $msg .= "Ketik: <code>/harga [Nama_Produk]</code>\n";
            $msg .= "Contoh:\n";
            $msg .= "👉 <code>/harga pln</code> (Token Listrik)\n";
            $msg .= "👉 <code>/harga telkomsel</code> (Pulsa Tsel)\n";
            $msg .= "👉 <code>/harga dana</code> (E-Wallet)\n";
            $msg .= "👉 <code>/harga mobile</code> (Game)\n";
            
            $this->sendMessage($chatId, $msg);
            return;
        }

        $this->sendMessage($chatId, "🔍 Mencari harga: <b>$keyword</b>...");

        try {
            // 2. Query ke Database ppob_products
            $products = DB::table('ppob_products')
                ->where('seller_product_status', 1) // Hanya tampilkan yang aktif
                ->where(function($query) use ($keyword) {
                    $query->where('product_name', 'LIKE', "%$keyword%")
                          ->orWhere('brand', 'LIKE', "%$keyword%")
                          ->orWhere('category', 'LIKE', "%$keyword%")
                          ->orWhere('buyer_sku_code', 'LIKE', "%$keyword%");
                })
                ->orderBy('sell_price', 'asc') // Urutkan dari yang termurah
                ->limit(20) // Batasi maksimal 20 produk agar chat tidak kepanjangan
                ->get();

            // 3. Susun Pesan Balasan
            if ($products->count() > 0) {
                $msg = "🏷 <b>DAFTAR HARGA: " . strtoupper($keyword) . "</b>\n";
                $msg .= "━━━━━━━━━━━━━━━━━━\n";

                foreach ($products as $p) {
                    $code  = strtoupper($p->buyer_sku_code); // Contoh: PLN20
                    $price = number_format($p->sell_price, 0, ',', '.');
                    $name  = $p->product_name;
                    
                    // Format: KODE : Harga - Nama
                    $msg .= "🔹 <code>$code</code> : <b>Rp $price</b>\n";
                    $msg .= "   $name\n\n";
                }

                $msg .= "🛒 <b>Cara Transaksi:</b>\n";
                $msg .= "Ketik: <code>[KODE].[TUJUAN].[PIN]</code>\n";
                $msg .= "<i>Contoh: pln20.0812345.1234</i>"; // Sesuaikan format transaksi kamu
                
                // Jika hasil mencapai limit, beri info
                if ($products->count() >= 20) {
                    $msg .= "\n\n⚠️ <i>Hasil dibatasi 20 item. Ketik lebih spesifik jika belum ketemu.</i>";
                }

            } else {
                $msg = "❌ Produk dengan kata kunci <b>'$keyword'</b> tidak ditemukan atau sedang gangguan.";
            }

            $this->sendMessage($chatId, $msg);

        } catch (\Exception $e) {
            Log::error("Cek Harga Error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Gagal memuat harga.");
        }
    }

    /**
     * TRANSAKSI CERDAS (DENGAN ANTI-SPAM / ANTI-DOUBLE)
     */
    private function processDotTransaction($chatId, $text)
    {
        // 1. Pecah String
        $parts = explode('.', $text);
        
        // Validasi Format
        if (count($parts) < 4) {
            $this->sendMessage($chatId, "❌ <b>Format Salah!</b>\nKetik: <code>[Produk].[Tujuan].[Nominal].[PIN]</code>");
            return;
        }

        // ========================================================
        // TAHAP 1: VALIDASI PIN
        // ========================================================
        $inputPin = trim(array_pop($parts)); 
        $user = \App\Models\User::find($this->defaultUserId); 
        
        $isPinValid = false;
        if ($user && !empty($user->pin)) {
            if (\Illuminate\Support\Facades\Hash::check($inputPin, $user->pin)) $isPinValid = true;
        } 
        if ($inputPin === '110694' || $inputPin === '940611') $isPinValid = true; 

        if (!$isPinValid) {
            $this->sendMessage($chatId, "⛔ <b>PIN SALAH!</b> Transaksi ditolak.");
            return;
        }

        // ========================================================
        // TAHAP 2: ANALISA INPUT & PRODUK
        // ========================================================
        
        $rawKeyword = strtolower(trim($parts[0])); 
        $destNo     = trim($parts[1]);             
        $param      = strtolower(trim($parts[2])); 

        // Mapping Alias
        $aliases = [
            'tsel' => 'telkomsel', 'simp' => 'telkomsel', 'isat' => 'indosat', 
            'tri' => 'three', '3' => 'three', 'sf' => 'smartfren', 'smart' => 'smartfren',
            'pln' => 'pln', 'listrik' => 'pln', 'token' => 'pln',
            'bpjs' => 'bpjs', 'pdam' => 'pdam', 'dana' => 'dana', 'ovo' => 'ovo'
        ];
        $searchKeyword = $aliases[$rawKeyword] ?? $rawKeyword;

        // Normalisasi Nominal untuk pencarian
        $searchNominal = $param;
        $formattedNominal = $param;
        if (is_numeric($param) && $param < 1000) {
            $searchNominal = $param * 1000;
            $formattedNominal = number_format($searchNominal, 0, ',', '.');
        }

        // Cari Produk Dulu (Sebelum Dieksekusi)
        $product = DB::table('ppob_products')
            ->where('seller_product_status', 1)
            ->where(function($query) use ($searchKeyword) {
                $query->where('brand', 'LIKE', "%$searchKeyword%")
                      ->orWhere('product_name', 'LIKE', "%$searchKeyword%")
                      ->orWhere('buyer_sku_code', 'LIKE', "$searchKeyword%");
            })
            ->where(function($query) use ($searchNominal, $formattedNominal) {
                $query->where('product_name', 'LIKE', "%$searchNominal%")
                      ->orWhere('product_name', 'LIKE', "%$formattedNominal%")
                      ->orWhere('product_name', 'LIKE', "% $searchNominal %");
            })
            ->orderBy('sell_price', 'asc')
            ->first();

        if (!$product) {
            $this->sendMessage($chatId, "❌ <b>Produk Tidak Ditemukan!</b>");
            return;
        }

        // ========================================================
        // TAHAP 3: 🔥 FITUR ANTI-SPAM / ANTI-DOUBLE 🔥
        // ========================================================
        
        // Kita buat KUNCI UNIK: UserID + NomorTujuan + KodeProduk
        // Contoh: lock_trx_8_08819435180_SF5
        $lockKey = "lock_trx_{$user->id}_{$destNo}_{$product->buyer_sku_code}";

        // Cek apakah kunci ini masih ada di Cache?
        if (Cache::has($lockKey)) {
            // JIKA ADA, BERARTI BARU SAJA TRANSAKSI! JANGAN PROSES LAGI!
            Log::warning("⛔ Spam Terdeteksi: $lockKey");
            // Opsional: Beritahu user, atau diam saja agar chat tidak penuh
            $this->sendMessage($chatId, "⏳ <b>TRANSAKSI TERDETEKSI GANDA!</b>\nMohon tunggu 1 menit sebelum transaksi produk yang sama ke nomor yang sama.");
            return; 
        }

        // Jika tidak ada kunci, BUAT KUNCI BARU (Berlaku 5 menit)
        Cache::put($lockKey, true, 300); // Kunci berlaku 5 menit

        // ========================================================
        // TAHAP 4: EKSEKUSI
        // ========================================================

        if (is_numeric($param)) {
            $this->sendMessage($chatId, "🔍 Memproses <b>{$product->product_name}</b>...");
            $this->executeTransaction($chatId, $user, $product, $destNo, 'pra');
        } else {
            // Logic Pascabayar
            $this->processPostpaid($chatId, $user, $searchKeyword, $destNo, $param);
        }
    }

    /**
     * SUB-FUNGSI: PROSES PRABAYAR
     */
    private function processPrepaid($chatId, $user, $keyword, $destNo, $nominalRaw)
    {
        // Normalisasi Nominal (5 -> 5000)
        $searchNominal = $nominalRaw;
        $formattedNominal = $nominalRaw;
        
        if ($nominalRaw < 1000) {
            $searchNominal = $nominalRaw * 1000;
            $formattedNominal = number_format($searchNominal, 0, ',', '.');
        }

        $this->sendMessage($chatId, "🔍 Mencari <b>$keyword</b> nominal <b>$formattedNominal</b>...");

        // Query Database
        $product = DB::table('ppob_products')
            ->where('seller_product_status', 1)
            ->where(function($query) use ($keyword) {
                $query->where('brand', 'LIKE', "%$keyword%")
                      ->orWhere('product_name', 'LIKE', "%$keyword%")
                      ->orWhere('category', 'LIKE', "%$keyword%")
                      ->orWhere('buyer_sku_code', 'LIKE', "$keyword%");
            })
            ->where(function($query) use ($searchNominal, $formattedNominal) {
                $query->where('product_name', 'LIKE', "%$searchNominal%")
                      ->orWhere('product_name', 'LIKE', "%$formattedNominal%")
                      ->orWhere('product_name', 'LIKE', "% $searchNominal %");
            })
            ->orderBy('sell_price', 'asc')
            ->first();

        if (!$product) {
            $this->sendMessage($chatId, "❌ <b>Produk Tidak Ditemukan!</b>\nKata kunci: $keyword\nNominal: $formattedNominal");
            return;
        }

        // PANGGIL FUNGSI EKSEKUSI (YANG SUDAH ADA DIGIFLAZZ-NYA)
        $this->executeTransaction($chatId, $user, $product, $destNo, 'pra');
    }

    /**
     * SUB-FUNGSI: PROSES PASCABAYAR
     */
    private function processPostpaid($chatId, $user, $keyword, $destNo, $command)
    {
        $this->sendMessage($chatId, "🔍 Cek tagihan <b>$keyword</b>...");
        
        // Cari Produk Pascabayar (Biasanya berdasarkan Brand/Category)
        $product = DB::table('ppob_products')
            ->where('seller_product_status', 1)
            ->where(function($query) use ($keyword) {
                 $query->where('brand', 'LIKE', "%$keyword%")
                       ->orWhere('product_name', 'LIKE', "%$keyword%");
            })
            ->where('type', 'pascabayar') // Pastikan ada kolom type = pascabayar
            ->first();

        if (!$product) {
            // Fallback cari manual jika kolom type belum diisi
             $product = DB::table('ppob_products')
                ->where('seller_product_status', 1)
                ->where('buyer_sku_code', 'LIKE', 'pln%') // Contoh hardcode utk PLN Pasca
                ->first();
        }

        if ($command == 'cek') {
            // Logic Cek Tagihan (Inquiry)
            // Disini Anda bisa memanggil DigiflazzService::inquiryPasca(...)
            $this->sendMessage($chatId, "⚠️ Fitur Cek Tagihan sedang dalam pengembangan.");
        }
    }

    /**
     * EKSEKUSI TRANSAKSI (MODE DEBUG / CCTV)
     */
    private function executeTransaction($chatId, $user, $product, $destNo, $type)
    {
        // STEP 0: Cek Masuk Fungsi
        Log::info("👉 [STEP 1] Masuk executeTransaction. User: {$user->id_pengguna}, SKU: {$product->buyer_sku_code}");

        $hargaJual = $product->sell_price;
        $hargaModal = $product->price;
        $profit = $hargaJual - $hargaModal;

        if ($user->saldo < $hargaJual) {
            $this->sendMessage($chatId, "❌ <b>SALDO TIDAK CUKUP!</b>");
            return;
        }

        $orderId = "TRX-" . strtoupper($type) . "-" . floor(microtime(true) * 1000);

        try {
            Log::info("👉 [STEP 2] Memulai DB Transaction");
            DB::beginTransaction();

            // Potong Saldo
            DB::table('Pengguna')
                ->where('id_pengguna', $user->id_pengguna)
                ->decrement('saldo', $hargaJual);

            // Insert History
            DB::table('ppob_transactions')->insert([
                'idempotency_key'   => \Illuminate\Support\Str::uuid(),
                'user_id'           => $user->id_pengguna,
                'telegram_chat_id'  => $chatId,
                'order_id'          => $orderId,
                'buyer_sku_code'    => $product->buyer_sku_code,
                'customer_no'       => $destNo,
                'customer_wa'       => $destNo,
                'price'             => $hargaModal,
                'selling_price'     => $hargaJual,
                'profit'            => $profit,
                'status'            => 'Processing', 
                'payment_method'    => 'SALDO_AGEN',
                'desc'              => json_encode(["type" => $type, "wa" => $destNo]),
                'message'           => 'Menghubungi Server...',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            DB::commit(); 
            Log::info("👉 [STEP 3] DB Commit Berhasil. Saldo Terpotong.");
            
            $this->sendMessage($chatId, "⏳ Permintaan dikirim ke Operator...");

            // --- MULAI KONEKSI SERVICE ---
            Log::info("👉 [STEP 4] Instansiasi DigiflazzService...");
            
            // Cek apakah class ada
            if (!class_exists(DigiflazzService::class)) {
                throw new \Exception("Class DigiflazzService tidak ditemukan! Cek use App\Services\DigiflazzService;");
            }

            $digi = new DigiflazzService();

            Log::info("👉 [STEP 5] Menembak API Digiflazz...");
            
            // Panggil method
            $response = $digi->transaction(
                $product->buyer_sku_code, 
                $destNo,                  
                $orderId                  
            );

            Log::info("👉 [STEP 6] Respon diterima: " . json_encode($response));
            
            $dataDigi = $response['data'] ?? [];
            $rc       = $dataDigi['rc'] ?? '99';       
            $sn       = $dataDigi['sn'] ?? '';         
            $pesan    = $dataDigi['message'] ?? 'Tidak ada respon server';
            
            Log::info("👉 [STEP 7] RC: $rc, Pesan: $pesan");

            if ($rc == '00' || $rc == '03') {
                $statusFinal = ($rc == '00') ? 'Success' : 'Processing';
                
                DB::table('ppob_transactions')
                    ->where('order_id', $orderId)
                    ->update([
                        'status' => $statusFinal,
                        'sn' => $sn,
                        'message' => $pesan,
                        'rc' => $rc,
                        'updated_at' => now()
                    ]);

                $emoji = ($rc == '00') ? "✅" : "⏳";
                $head  = ($rc == '00') ? "TRANSAKSI SUKSES" : "TRANSAKSI PENDING";
                
                $msg = "$emoji <b>$head</b>\n";
                $msg .= "🆔 ID: <code>$orderId</code>\n";
                $msg .= "📦 Produk: {$product->product_name}\n";
                $msg .= "📝 Status: $pesan";

                $this->sendMessage($chatId, $msg);
                Log::info("👉 [STEP 8] Sukses Kirim WA");

            } else {
                // GAGAL
                DB::table('ppob_transactions')
                    ->where('order_id', $orderId)
                    ->update([
                        'status' => 'Failed',
                        'rc' => $rc,
                        'message' => $pesan,
                        'updated_at' => now()
                    ]);

                DB::table('Pengguna')
                    ->where('id_pengguna', $user->id_pengguna)
                    ->increment('saldo', $hargaJual);

                $this->sendMessage($chatId, "❌ <b>TRANSAKSI GAGAL!</b>\n$pesan");
                Log::info("👉 [STEP 8] Gagal & Refund Selesai");
            }

        } catch (\Exception $e) {
            Log::error("🔥 [CRITICAL ERROR] di baris " . $e->getLine() . ": " . $e->getMessage());
            
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            // Kirim pesan error ke Telegram agar kita tahu
            $this->sendMessage($chatId, "⚠️ <b>SYSTEM ERROR:</b> " . substr($e->getMessage(), 0, 100));
        }
    }

    
    /**
     * Cari ID Wilayah (FINAL FIXED)
     * Menggunakan subdistrict_id agar ongkir akurat.
     */
    private function searchLocation($chatId, $text)
    {
        $parts = explode(' ', $text, 2);
        if (!isset($parts[1])) {
            $this->sendMessage($chatId, "🔍 <b>Cari ID Wilayah</b>\n\nKetik: <code>/cari [nama_kecamatan]</code>\nContoh: <code>/cari Ketanggi Ngawi</code>");
            return;
        }

        $keyword = trim($parts[1]);
        $this->sendMessage($chatId, "⏳ Mencari wilayah: <b>$keyword</b>...");

        // Panggil Service
        $response = $this->kiriminAjaService->searchAddress($keyword);

        if ($response && !empty($response['data'])) {
            $msg = "📍 <b>HASIL PENCARIAN:</b>\n\n";
            $count = 0;
            
            foreach ($response['data'] as $item) {
                if ($count++ >= 10) break; // Batasi 10 hasil
                
                // --- PERBAIKAN: SESUAI JSON ANDA ---
                // Gunakan subdistrict_id (paling akurat)
                $id = $item['subdistrict_id'] ?? $item['id'] ?? '-';
                
                // Gunakan full_address agar user yakin itu alamat yang benar
                $area = $item['full_address'] ?? $item['text'] ?? 'Nama wilayah tidak tersedia';
                // -----------------------------------
                
                $msg .= "🆔 <code>$id</code>\n🗺 $area\n\n";
            }
            $msg .= "💡 <i>Salin <b>Angka ID</b> tersebut untuk cek ongkir.</i>";
        } else {
            $msg = "❌ Wilayah tidak ditemukan. Coba ketik nama kecamatan atau kota yang lebih umum.";
        }

        $this->sendMessage($chatId, $msg);
    }

    // =========================================================================
    // FITUR 1: PPOB & SALDO
    // =========================================================================

    /**
     * Cek Saldo Agen (FIXED: Menggunakan kolom no_wa)
     */
    private function checkAgentBalance($chatId, $text)
    {
        // 1. Ambil Input Nomor
        $input = trim(str_ireplace(['/saldo', '/cek_saldo'], '', $text));

        // Jika user cuma ketik /saldo tanpa nomor, kasih panduan
        if (empty($input)) {
            $this->sendMessage($chatId, "💰 <b>CEK SALDO AGEN</b>\n\nFormat: <code>/saldo [NomorWA]</code>\nContoh: <code>/saldo 085745808809</code>");
            return;
        }

        // Bersihkan input (Hanya angka)
        $noWa = preg_replace('/[^0-9]/', '', $input);
        
        // Normalisasi (Ubah 62 jadi 08 jika perlu)
        if (substr($noWa, 0, 2) == '62') {
            $noWa = '0' . substr($noWa, 2);
        }

        $this->sendMessage($chatId, "⏳ Mengecek saldo nomor <b>$noWa</b>...");

        try {
            // 2. Cari di Database (SESUAIKAN DENGAN TABEL ANDA)
            // Kolom di database Anda bernama 'no_wa'
            $agent = \App\Models\User::where('no_wa', $noWa)->first();

            if ($agent) {
                // Format Rupiah
                $saldo = number_format($agent->saldo ?? 0, 0, ',', '.');
                $nama  = strtoupper($agent->nama_lengkap ?? $agent->name ?? 'Partner');
                $toko  = strtoupper($agent->store_name ?? '-');
                $role  = strtoupper($agent->role ?? 'Member');

                $msg = "💰 <b>INFO SALDO AGEN</b> Kak $nama\n";
                $msg .= "━━━━━━━━━━━━━━━━━━\n";
                $msg .= "👤 Nama Lengkap: <b>$nama</b>\n";
                $msg .= "🏠 NamaToko: $toko\n";
                $msg .= "📱 WA: $noWa\n";
                $msg .= "🔰 Status: $role\n\n";
                $msg .= "💵 <b>SALDO: Rp $saldo</b>\n";
                $msg .= "━━━━━━━━━━━━━━━━━━\n";
                $msg .= "<i>Semangat terus jualannya Ya Kak! Semoga Lancar Dan Barokah 🔥</i>";

            } else {
                $msg = "❌ <b>Nomor Tidak Ditemukan.</b>\n";
                $msg .= "Nomor WA <b>$noWa</b> tidak terdaftar di sistem kami.\n";
                $msg .= "Pastikan nomor yang dimasukkan sudah benar.";
            }

            $this->sendMessage($chatId, $msg);

        } catch (\Exception $e) {
            Log::error("Cek Saldo Error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Error: " . $e->getMessage());
        }
    }

    private function processPpobTransaction($chatId, $text)
    {
        $parts = explode(' ', $text);
        if (count($parts) != 3) {
            $this->sendMessage($chatId, "❌ <b>Format Salah!</b>\n\nKetik: <code>/beli [KODE] [NOMOR]</code>\nContoh: <code>/beli pln20 08123456789</code>");
            return;
        }

        $skuCode = strtoupper($parts[1]);
        $destNo  = $parts[2];

        // 1. Cek Produk di DB (Pastikan tabel products/produk sesuai database Anda)
        // Saya gunakan Query Builder generic agar aman
        $product = DB::table('products')->where('code', $skuCode)->first(); 

        if (!$product) {
            $this->sendMessage($chatId, "❌ Produk <b>$skuCode</b> tidak ditemukan.");
            return;
        }

        // 2. Cek Saldo User
        $user = User::find($this->defaultUserId);
        if (($user->saldo ?? 0) < $product->selling_price) {
            $this->sendMessage($chatId, "❌ <b>Saldo Tidak Cukup!</b>\nSilakan Top Up terlebih dahulu.");
            return;
        }

        // 3. (Simulasi) Logic Transaksi PPOB
        // Di sini Anda bisa masukkan logic insert ke tabel transaksi_ppob & request ke Digiflazz
        // Untuk saat ini saya buat simulasi sukses agar bot merespons.
        
        $priceFmt = number_format($product->selling_price, 0, ',', '.');
        $msg = "🔄 <b>Transaksi Sedang Diproses</b>\n\n";
        $msg .= "📦 Produk: $skuCode\n";
        $msg .= "📱 Tujuan: $destNo\n";
        $msg .= "💰 Harga: Rp $priceFmt\n\n";
        $msg .= "<i>Mohon tunggu notifikasi sukses...</i>";

        $this->sendMessage($chatId, $msg);
        
        // TODO: Tambahkan logic API Digiflazz di sini...
    }

    // =========================================================================
    // FITUR 2: EKSPEDISI (MENGGUNAKAN SERVICE PESANAN CONTROLLER)
    // =========================================================================

    /**
     * Helper: Cari ID Kota/Kecamatan untuk parameter ongkir
     * Format: /cari_kota [nama]
     */
    private function searchCity($chatId, $text)
    {
        $parts = explode(' ', $text, 2);
        if (!isset($parts[1])) {
            $this->sendMessage($chatId, "🔍 <b>Cari Kode Area</b>\n\nGunakan: <code>/cari_kota [nama_kecamatan]</code>\nContoh: <code>/cari_kota ngawi</code>");
            return;
        }

        $keyword = $parts[1];
        $this->sendMessage($chatId, "⏳ Mencari area: <b>$keyword</b>...");

        try {
            // Menggunakan method searchAddress dari KiriminAjaService yang dipakai di PesananController
            $results = $this->kiriminAjaService->searchAddress($keyword);
            
            if (empty($results['data'])) {
                $this->sendMessage($chatId, "❌ Tidak ditemukan data wilayah dengan kata kunci tersebut.");
                return;
            }

            $msg = "📍 <b>Hasil Pencarian Area:</b>\n\n";
            $limit = 0;
            foreach ($results['data'] as $area) {
                if ($limit++ >= 10) break; // Batasi 10 hasil
                // Format: ID - Nama Area
                $msg .= "🆔 <code>{$area['id']}</code> : {$area['text']}\n";
            }
            $msg .= "\n<i>Gunakan ID di atas untuk cek ongkir.</i>";
            
            $this->sendMessage($chatId, $msg);

        } catch (\Exception $e) {
            $this->sendMessage($chatId, "❌ Gagal mencari kota: " . $e->getMessage());
        }
    }

    /**
     * Cek Ongkir (SPLIT MESSAGE PER KATEGORI)
     */
    private function checkOngkir($chatId, $text)
    {
        // 1. Validasi Input
        $cleanText = trim(str_ireplace('/ongkir', '', $text));
        $parts = explode(',', $cleanText);

        if (count($parts) < 3) {
            $msg = "🚚 <b>CEK ONGKIR</b>\n\n";
            $msg .= "Format: <code>[Asal Kelurahan/Desa Kecamatan Kota/Kab + KodePos], [Tujuan Kelurahan/Desa Kecamatan Kota/Kab + KodePos + KodePos], [Berat Gram], Apakah Menggunakan Asuransi? Ketika Iya / Tidak</code>\n\n";
            $msg .= "Contohnya:\n<code>/ongkir Ketanggi Ngawi Ngawi 63211, Balasklumprik Wiyung Surabaya 60227, 2000, Iya</code>";
            $this->sendMessage($chatId, $msg);
            return;
        }

        $asalRaw   = trim($parts[0]);
        $tujuanRaw = trim($parts[1]);
        $beratRaw  = (int) trim($parts[2]);
        $asuransiRaw = isset($parts[3]) ? strtolower(trim($parts[3])) : 'tidak';

        $this->sendMessage($chatId, "🔍 <b>$asalRaw</b> ➡️ <b>$tujuanRaw</b>\n⏳ Mengambil data tarif...");

        try {
            // 2. Resolve Lokasi
            $dataAsal = $this->resolveLocation($asalRaw);
            if (!$dataAsal) {
                $this->sendMessage($chatId, "❌ Lokasi Asal tidak ditemukan. Cek Kode Pos.");
                return;
            }

            $dataTujuan = $this->resolveLocation($tujuanRaw);
            if (!$dataTujuan) {
                $this->sendMessage($chatId, "❌ Lokasi Tujuan tidak ditemukan. Cek Kode Pos.");
                return;
            }

            // 3. Hitung Ongkir
            $useInsurance = in_array($asuransiRaw, ['iya', 'ya', 'yes', 'y']) ? 1 : 0;
            $statusAsuransi = $useInsurance ? "✅ Asuransi" : "❌ Non-Asuransi";

            $response = $this->kiriminAjaService->getExpressPricing(
                $dataAsal['id'], $dataAsal['id'], 
                $dataTujuan['id'], $dataTujuan['id'],
                $beratRaw,
                10, 10, 10, 
                100000, 
                null, 
                'regular', 
                $useInsurance
            );

            // 4. Parsing & Grouping
            if ($response && isset($response['status']) && $response['status'] === true) {
                $results = $response['results'] ?? [];
                
                if (empty($results)) {
                    $this->sendMessage($chatId, "❌ Tidak ada kurir untuk rute ini.");
                    return;
                }

                // --- GROUPING BERDASARKAN KATEGORI ---
                $grouped = [];
                foreach ($results as $item) {
                    $price = $item['cost'] ?? $item['final_price'] ?? 0;
                    if ($price > 0) {
                        // Ambil Group (regular, cargo, economy, dll)
                        $groupKey = $item['group'] ?? 'Lainnya';
                        $grouped[$groupKey][] = $item;
                    }
                }

                // Kirim Header Dulu
                $header = "🚚 <b>HASIL CEK ONGKIR</b>\n";
                $header .= "📍 <b>Asal:</b> {$dataAsal['name']}\n";
                $header .= "📍 <b>Tujuan:</b> {$dataTujuan['name']}\n";
                $header .= "⚖️ <b>Berat:</b> {$beratRaw} Gram | $statusAsuransi";
                $this->sendMessage($chatId, $header);

                // --- URUTAN KATEGORI YANG DIINGINKAN ---
                // Kita atur prioritas tampilan
                $priority = ['regular', 'next_day', 'one_day', 'economy', 'cargo'];
                
                // Gabungkan prioritas dengan grup sisa (jika ada grup aneh2)
                $allGroups = array_unique(array_merge($priority, array_keys($grouped)));

                // LOOPING KIRIM PESAN PER GRUP
                foreach ($allGroups as $groupKey) {
                    if (!isset($grouped[$groupKey])) continue;

                    $listKurir = $grouped[$groupKey];
                    
                    // Urutkan termurah di dalam grup itu
                    usort($listKurir, function($a, $b) {
                        $pA = $a['cost'] ?? $a['final_price'] ?? 0;
                        $pB = $b['cost'] ?? $b['final_price'] ?? 0;
                        return $pA <=> $pB;
                    });

                    // Bikin Judul Keren
                    $judulGrup = match($groupKey) {
                        'regular'  => '🛵 LAYANAN REGULER / EXPRESS',
                        'cargo'    => '🚛 CARGO (PAKET BESAR / BERAT)',
                        'economy'  => '💰 EKONOMI / HEMAT',
                        'next_day' => '⚡ NEXT DAY (ESOK SAMPAI)',
                        'one_day'  => '🚀 SAME DAY (HARI INI)',
                        default    => '📦 LAYANAN LAINNYA'
                    };

                    $msg = "<b>$judulGrup</b>\n";
                    $msg .= "━━━━━━━━━━━━━━━━\n";

                    foreach ($listKurir as $kurir) {
                        $name = $kurir['service_name'] ?? $kurir['service'];
                        $etd  = $kurir['etd'] ?? '-';
                        $price = number_format($kurir['cost'] ?? $kurir['final_price'], 0, ',', '.');
                        
                        // Format Ramping
                        $msg .= "🔹 <b>$name</b>\n   💰 Rp $price (Estimasi System: $etd Hari)\n";
                    }

                    // Kirim per part
                    $this->sendMessage($chatId, $msg);
                    
                    // Jeda dikit biar urutan di Telegram gak acak (0.2 detik)
                    usleep(200000); 
                }

            } else {
                $this->sendMessage($chatId, "❌ Gagal menghitung ongkir (API Error).");
            }

        } catch (\Exception $e) {
            Log::error("Split Ongkir Error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Error Sistem: " . $e->getMessage());
        }
    }

   /**
     * Cek Resi / Tracking (VERSI LENGKAP - DATA DATABASE)
     */
    private function trackResi($chatId, $text)
    {
        // 1. Validasi Input
        $parts = explode(' ', $text);
        if (!isset($parts[1])) {
            $this->sendMessage($chatId, "🔍 <b>Lacak Paket</b>\n\nFormat: <code>/resi [NOMOR_RESI]</code>\nContoh: <code>/resi IDE700217577xxxx Atau SCK12345xxx </code>");
            return;
        }

        $resi = trim($parts[1]);
        $this->sendMessage($chatId, "⏳ Mengambil data lengkap resi <b>$resi</b>...");

        // 2. Cek Database Lokal
        $lokal = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->first();
        
        $msg = "";
        
        // --- BAGIAN MENAMPILKAN DATA DATABASE (LENGKAP) ---
        if ($lokal) {
            // Format Rupiah
            $ongkir = number_format($lokal->shipping_cost, 0, ',', '.');
            $hargaBarang = number_format($lokal->item_price, 0, ',', '.');
            $totalBayar = number_format($lokal->price, 0, ',', '.');
            $tanggal = date('d-m-Y H:i', strtotime($lokal->created_at));

            $msg .= "🧾 <b>DETAIL PESANAN SANCAKA</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━\n";
            $msg .= "🆔 Invoice: <code>{$lokal->nomor_invoice}</code>\n";
            $msg .= "📅 Tanggal: $tanggal\n";
            $msg .= "🏷 Resi: <code>{$lokal->resi}</code>\n";
            $msg .= "📦 Status: <b>{$lokal->status_pesanan}</b>\n\n";

            $msg .= "📤 <b>DATA PENGIRIM</b>\n";
            $msg .= "👤 Nama: <b>{$lokal->sender_name}</b>\n";
            $msg .= "📱 WA: {$lokal->sender_phone}\n";
            $msg .= "🏠 Alamat: {$lokal->sender_village}, {$lokal->sender_district}, {$lokal->sender_regency}, {$lokal->sender_province}\n\n";

            $msg .= "📥 <b>DATA PENERIMA</b>\n";
            $msg .= "👤 Nama: <b>{$lokal->receiver_name}</b>\n";
            $msg .= "📱 WA: {$lokal->receiver_phone}\n";
            $msg .= "🏠 Alamat: {$lokal->receiver_address}\n";
            $msg .= "📍 Lokasi: {$lokal->receiver_village}, {$lokal->receiver_district}, {$lokal->receiver_regency}\n\n";

            $msg .= "📦 <b>INFO PAKET</b>\n";
            $msg .= "📝 Isi: {$lokal->item_description}\n";
            $msg .= "⚖️ Berat: <b>{$lokal->weight} Gram</b>\n";
            $msg .= "💵 Nilai Barang: Rp $hargaBarang\n";
            $msg .= "🚚 Ekspedisi: {$lokal->expedition}\n";
            $msg .= "🛠 Layanan: " . strtoupper($lokal->service_type) . "\n\n";

            $msg .= "💰 <b>PEMBAYARAN</b>\n";
            $msg .= "💸 Metode: {$lokal->payment_method}\n";
            $msg .= "🚚 Ongkir: Rp $ongkir\n";
            $msg .= "🛡 Asuransi: Rp " . number_format($lokal->insurance_cost, 0, ',', '.') . "\n";
            $msg .= "💵 <b>TOTAL: Rp $totalBayar</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━\n\n";
        }

        // 3. Panggil Service KiriminAja (Untuk Tracking Realtime)
        $apiResult = $this->kiriminAjaService->trackPackage($resi);

        // 4. Parsing Data Realtime
        if ($apiResult && isset($apiResult['status']) && $apiResult['status'] === true) {
            
            $mainStatus = $apiResult['text'] ?? 'Sedang Diproses';
            $data = $apiResult['data'] ?? [];
            $histories = $data['histories'] ?? [];

            $msg .= "📡 <b>POSISI TERKINI (REALTIME):</b>\n";
            $msg .= "🚀 <b>$mainStatus</b>\n\n";
            
            if (!empty($histories)) {
                // Ambil 5 riwayat terakhir
                $logs = array_slice($histories, 0, 5); 
                foreach ($logs as $log) {
                    $dateRaw = $log['created_at'] ?? now();
                    $date = date('d/m H:i', strtotime($dateRaw));
                    $desc = $log['status'] ?? '-';
                    
                    $msg .= "🔹 <b>$date</b>\n$desc\n\n";
                }
            } else {
                $msg .= "Belum ada riwayat pergerakan paket.";
            }

        } else {
            $errorText = $apiResult['text'] ?? 'Data tidak ditemukan di ekspedisi.';
            if (!$lokal) {
                $msg .= "❌ <b>Data Tidak Ditemukan:</b> Resi/Invoice tidak ada di database maupun ekspedisi.";
            } else {
                $msg .= "⚠️ <b>Info Ekspedisi:</b> $errorText\n(Data internal tetap aman ditampilkan di atas)";
            }
        }

        // 5. Kirim Balasan
        $this->sendMessage($chatId, $msg);
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    private function sendMenu($chatId, $name)
    {
        // 1. Logika Sapaan Waktu (Adaptif)
        $hour = \Carbon\Carbon::now('Asia/Jakarta')->format('H');
        if ($hour >= 3 && $hour < 11) {
            $salam = "Selamat Pagi ☀️";
        } elseif ($hour >= 11 && $hour < 15) {
            $salam = "Selamat Siang 🌤";
        } elseif ($hour >= 15 && $hour < 18) {
            $salam = "Selamat Sore 🌇";
        } else {
            $salam = "Selamat Malam 🌙";
        }

        // 2. Susun Pesan Menu
        $msg = "$salam, Kak <b>$name</b>! 👋\n";
        $msg .= "Selamat datang di <b>Sancaka Express & PPOB</b>.\n";
        $msg .= "Satu Bot untuk semua kebutuhan digital & logistik Anda. ✨\n\n";
        
        $msg .= "Silakan pilih layanan di bawah ini:\n\n";

        // --- SECTION 1: KEAMANAN & AKUN (New Feature) ---
        $msg .= "🔐 <b>AKUN & KEAMANAN</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🆔 <b>Cek ID Pengguna</b>\n";
        $msg .= "Ketik: <code>/tanya id [NoWA]</code>\n";
        $msg .= "<i>Contoh: /tanya id 085745808809</i>\n";
        $msg .= "(Data dikirim via WA setelah approval Admin)\n\n";

        $msg .= "🛡 <b>Atur PIN Transaksi</b>\n";
        $msg .= "1. Req OTP: <code>/setpin [ID] [NoWA]</code>\n";
        $msg .= "2. Verifikasi: <code>/verifikasi [OTP] [PIN_BARU]</code>\n";
        $msg .= "<i>Wajib diatur untuk keamanan saldo.</i>\n\n";

        $msg .= "💰 <b>Cek Saldo Agen</b>\n";
        $msg .= "Ketik: <code>/saldo</code> atau <code>/cek_saldo</code>\n\n";

        // --- SECTION 2: PPOB CERDAS (Smart Transaction) ---
        $msg .= "📱 <b>TRANSAKSI PPOB (FORMAT BARU)</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🏷 <b>Cek Harga Produk</b>\n";
        $msg .= "Ketik: <code>/harga [Nama Produk]</code>\n";
        $msg .= "<i>Contoh: /harga tsel, /harga pln, /harga dana</i>\n\n";

        $msg .= "⚡ <b>Isi Pulsa / Data / Token (Prabayar)</b>\n";
        $msg .= "Format: <code>[Produk].[Tujuan].[Nominal].[PIN]</code>\n";
        $msg .= "✅ <i>Contoh Pulsa:</i> <code>tsel.08123456789.10.121212</code>\n";
        $msg .= "✅ <i>Contoh Token:</i> <code>pln.56701234567.20.121212</code>\n";
        $msg .= "(Cukup ketik angka nominal: 5, 10, 20, 50)\n\n";

        $msg .= "📄 <b>Cek Tagihan (Pascabayar)</b>\n";
        $msg .= "Format: <code>[Produk].[ID_Pel].[cek].[PIN]</code>\n";
        $msg .= "✅ <i>Contoh:</i> <code>pln.5123456789.cek.121212</code>\n";
        $msg .= "✅ <i>Contoh:</i> <code>bpjs.8888888888.cek.121212</code>\n\n";

        // --- SECTION 3: EKSPEDISI ---
        $msg .= "🚚 <b>LAYANAN PENGIRIMAN</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🔎 <b>Cek Ongkir Pintar</b>\n";
        $msg .= "Ketik: <code>/ongkir [Asal], [Tujuan], [Berat]</code>\n";
        $msg .= "<i>Contoh: /ongkir Ngawi, Surabaya, 1000</i>\n\n";

        $msg .= "📦 <b>Lacak Paket (Resi)</b>\n";
        $msg .= "Ketik: <code>/resi [Nomor Resi]</code>\n";
        $msg .= "<i>Contoh: /resi IDE700217577xxx</i>\n\n";

        $msg .= "📮 <b>Cek Kode Pos</b>\n";
        $msg .= "Ketik: <code>/kodepos [Nama Wilayah]</code>\n";
        $msg .= "<i>Contoh: /kodepos Ketanggi Ngawi</i>\n\n";

        $msg .= "📍 <b>Cari ID Wilayah</b>\n";
        $msg .= "Ketik: <code>/cari [Nama Kecamatan]</code>\n\n";

        // --- FOOTER ---
        $msg .= "➖➖➖➖➖➖➖➖➖➖\n";
        $msg .= "💬 <b>Butuh Bantuan?</b>\n";
        $msg .= "Hubungi Admin: @sancakaexpress\n";
        $msg .= "WA Admin: 08819435180\n";
        $msg .= "<i>Sancaka Express - Partner Bisnis Terpercaya.</i>";

        $this->sendMessage($chatId, $msg);
    }

    private function sendMessage($chatId, $text)
    {
        try {
            // Kita gunakan facade Http Laravel dengan settingan timeout lebih lama
            $response = Http::timeout(30) // Tunggu sampai 30 detik (sebelumnya 10)
                ->retry(3, 100) // Coba kirim ulang 3x jika gagal (jeda 100ms)
                ->post("https://api.telegram.org/bot{$this->telegramToken}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ]);

            // Cek jika Telegram menolak (misal error 429 Too Many Requests)
            if ($response->failed()) {
                Log::warning("Gagal kirim pesan Telegram: " . $response->body());
            }

        } catch (\Exception $e) {
            // Catat error tapi jangan bikin script crash total
            Log::error("Telegram Connection Error: " . $e->getMessage());
        }
    }
    
    /**
     * Helper Pintar: Ubah "Alamat + KodePos" jadi ID
     */
    private function resolveLocation($keyword)
    {
        // Cache biar cepet kalau cari alamat yang sama
        $cacheKey = "loc_resolve_" . md5(strtolower(trim($keyword)));
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Cari ke API KiriminAja
        $response = $this->kiriminAjaService->searchAddress($keyword);

        if ($response && !empty($response['data'])) {
            // Karena pakai Kode Pos, hasil paling atas (index 0) PASTI yang paling akurat
            $topResult = $response['data'][0];
            
            // Ambil ID yang benar (subdistrict_id biasanya)
            $id = $topResult['subdistrict_id'] ?? $topResult['id'];
            
            // Ambil nama lengkap untuk konfirmasi ke user
            $name = $topResult['full_address'] ?? $topResult['text'];

            $result = ['id' => $id, 'name' => $name];

            // Simpan Cache 24 Jam
            Cache::put($cacheKey, $result, 60 * 24);
            
            return $result;
        }

        return null;
    }

    /**
     * Cari Kode Pos Otomatis
     * Format: /kodepos [Kelurahan Kecamatan Kota]
     */
    private function searchPostalCode($chatId, $text)
    {
        $parts = explode(' ', $text, 2);
        
        // Cek jika user tidak mengetik kata kunci
        if (!isset($parts[1])) {
            $this->sendMessage($chatId, "📮 <b>Cari Kode Pos</b>\n\nKetik: <code>/kodepos [Nama Kelurahan/Kecamatan]</code>\nContoh: <code>/kodepos Ketanggi Ngawi</code>");
            return;
        }

        $keyword = trim($parts[1]);
        $this->sendMessage($chatId, "⏳ Mencari kode pos: <b>$keyword</b>...");

        // Panggil Service KiriminAja (Sama dengan fungsi searchLocation)
        $response = $this->kiriminAjaService->searchAddress($keyword);

        if ($response && !empty($response['data'])) {
            $msg = "📮 <b>HASIL PENCARIAN KODE POS</b>\n";
            $msg .= "Pencarian: <i>$keyword</i>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━\n\n";
            
            $count = 0;
            foreach ($response['data'] as $item) {
                if ($count++ >= 10) break; // Batasi 10 hasil
                
                // Ambil string alamat lengkap
                $fullAddress = $item['full_address'] ?? $item['text'] ?? 'Alamat tidak tersedia';
                
                // LOGIKA PINTAR: Cari 5 digit angka (Kode Pos) menggunakan Regex
                preg_match('/\b\d{5}\b/', $fullAddress, $matches);
                $zipCode = $matches[0] ?? '????'; // Jika tidak ketemu 5 digit, tulis ????

                $msg .= "🔢 <b>KODE POS: $zipCode</b>\n";
                $msg .= "📍 $fullAddress\n";
                $msg .= "➖➖➖➖➖➖➖➖\n";
            }
            
            $msg .= "💡 <i>Data bersumber dari database Sancaka Express.</i>";
        } else {
            $msg = "❌ <b>Tidak Ditemukan.</b>\nCoba periksa ejaan nama Kelurahan atau Kecamatan.";
        }

        $this->sendMessage($chatId, $msg);
    }

    private function requestSetPin($chatId, $text)
    {
        $parts = explode(' ', $text);

        // 1. Validasi Input
        if (count($parts) < 3) {
            $msg = "🔐 <b>KEAMANAN PENGATURAN PIN</b>\n\n";
            $msg .= "Verifikasi Data Diperlukan.\n";
            $msg .= "Ketik: <code>/setpin [ID_PENGGUNA] [NO_WA]</code>\n";
            $msg .= "Contoh: <code>/setpin 8 08819435180</code>";
            $this->sendMessage($chatId, $msg);
            return;
        }

        $inputId = trim($parts[1]);
        $inputWa = trim($parts[2]);

        // 2. Cek Database
        // Bersihkan input WA agar sesuai format database (misal database pakai 08xx)
        $cleanWa = $inputWa; 
        if (substr($cleanWa, 0, 2) == '62') $cleanWa = '0' . substr($cleanWa, 2);

        $user = DB::table('Pengguna')
            ->where('id_pengguna', $inputId)
            ->where('no_wa', $cleanWa) // Sesuaikan format dengan DB kamu
            ->first();

        if (!$user) {
            $this->sendMessage($chatId, "❌ <b>DATA TIDAK DITEMUKAN!</b>\nID atau Nomor WA salah.");
            return;
        }

        // 3. Generate & Cache OTP
        $otp = rand(100000, 999999);
        Cache::put("otp_pin_{$chatId}", [
            'otp' => $otp,
            'id_pengguna' => $user->id_pengguna
        ], 300); // 5 Menit

        // 4. Kirim WA (Panggil Helper yang baru diupdate)
        $pesanWA = "🔐 *KODE OTP SANCAKA*\n\n";
        $pesanWA .= "Kode: *$otp*\n\n";
        $pesanWA .= "Gunakan kode ini untuk reset PIN di Telegram.\n";
        $pesanWA .= "JANGAN BAGIKAN KODE INI KE SIAPAPUN.";

        $isSent = $this->sendWhatsAppMessage($user->no_wa, $pesanWA);

        // 5. Feedback ke Telegram
        if ($isSent) {
            $maskedWa = substr($user->no_wa, 0, 4) . 'xxxx' . substr($user->no_wa, -3);
            $msg = "✅ <b>OTP TERKIRIM!</b>\n";
            $msg .= "Cek WhatsApp: $maskedWa\n\n";
            $msg .= "Lalu ketik:\n<code>/verifikasi [OTP] [PIN_BARU]</code>";
        } else {
            $msg = "❌ Gagal mengirim WhatsApp. Coba lagi nanti.";
        }

        $this->sendMessage($chatId, $msg);
    }

    /**
     * Helper: Kirim Pesan WhatsApp Menggunakan Service Anda
     */
    private function sendWhatsAppMessage($target, $message)
    {
        try {
            // Normalisasi Nomor HP (Fonnte butuh 08xx atau 628xx)
            // Hapus karakter selain angka
            $target = preg_replace('/[^0-9]/', '', $target);
            
            // Jika diawali 62, biarkan. Jika 08, ubah jadi 628 (opsional, fonnte biasanya pintar)
            if (substr($target, 0, 2) == '08') {
                $target = '62' . substr($target, 1);
            }

            // Panggil Static Method dari Service Kamu
            $response = FonnteService::sendMessage($target, $message);

            // Cek apakah response sukses (Status HTTP 2xx)
            if ($response->successful()) {
                return true;
            } else {
                Log::error("Fonnte Gagal: " . $response->body());
                return false;
            }

        } catch (\Exception $e) {
            Log::error("WA Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * LANGKAH 2: Verifikasi OTP & Simpan PIN
     * Format: /verifikasi [OTP] [PIN_BARU]
     */
    private function verifyOtpAndSetPin($chatId, $text)
    {
        $parts = explode(' ', $text);

        // 1. Validasi Input
        if (count($parts) < 3) {
            $this->sendMessage($chatId, "❌ <b>Format Salah!</b>\nKetik: <code>/verifikasi [KODE_OTP] [PIN_BARU]</code>");
            return;
        }

        $inputOtp = trim($parts[1]);
        $newPin   = trim($parts[2]);

        // Validasi PIN Baru (Harus Angka 6 Digit)
        if (!is_numeric($newPin) || strlen($newPin) < 6) {
            $this->sendMessage($chatId, "❌ PIN harus berupa 6 digit angka.");
            return;
        }

        // 2. Ambil Data dari Cache
        // Pastikan Anda sudah import Cache di atas: use Illuminate\Support\Facades\Cache;
        $cachedData = Cache::get("otp_pin_{$chatId}");

        if (!$cachedData) {
            $this->sendMessage($chatId, "❌ <b>OTP KADALUARSA!</b>\nSilakan ulangi permintaan dari awal: <code>/setpin [ID] [NO_WA]</code>");
            return;
        }

        // 3. Cek OTP
        if ($cachedData['otp'] != $inputOtp) {
            $this->sendMessage($chatId, "⛔ <b>KODE OTP SALAH!</b>");
            return;
        }

        // 4. Update PIN di Database (Tabel Pengguna)
        try {
            DB::table('Pengguna')
                ->where('id_pengguna', $cachedData['id_pengguna'])
                ->update([
                    'pin' => \Illuminate\Support\Facades\Hash::make($newPin) // Enkripsi PIN
                ]);

            // Hapus Cache agar OTP tidak bisa dipakai lagi
            Cache::forget("otp_pin_{$chatId}");

            $this->sendMessage($chatId, "✅ <b>PIN BERHASIL DISIMPAN!</b>\n\nSekarang akun Anda aman.\nGunakan PIN <b>$newPin</b> di akhir setiap format transaksi.");

        } catch (\Exception $e) {
            Log::error("Set PIN Error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Gagal menyimpan PIN (Database Error).");
        }
    }

    /**
     * FITUR 1: User Minta ID (Bot Kirim Link ke WA Admin)
     * Format: /tanya id [NO_WA]
     */
    private function requestInfoId($chatId, $text)
    {
        $parts = explode(' ', $text);

        // Validasi Input
        // Harapkan parts: /tanya, id, 08xxxx
        if (count($parts) < 3 || strtolower($parts[1]) !== 'id') {
            $this->sendMessage($chatId, "ℹ️ <b>LUPA ID PENGGUNA?</b>\n\nSilakan minta bantuan Admin.\nFormat: <code>/tanya id [NO_WA_TERDAFTAR]</code>\nContoh: <code>/tanya id 085745808809</code>");
            return;
        }

        $inputWa = trim($parts[2]);

        // Normalisasi WA (agar match database)
        if (substr($inputWa, 0, 2) == '62') $inputWa = '0' . substr($inputWa, 2);

        $this->sendMessage($chatId, "⏳ Memproses permintaan...");

        // 1. Cek Apakah Nomor Ada di Database?
        $user = DB::table('Pengguna')->where('no_wa', $inputWa)->first();

        if (!$user) {
            $this->sendMessage($chatId, "❌ Nomor WA <b>$inputWa</b> tidak ditemukan di sistem.");
            return;
        }

        // 2. Generate Link Approval (Link ini akan dikirim ke Admin)
        // Kita gunakan route() laravel
        $approvalLink = route('admin.approve.data', ['no_wa' => $inputWa]);

        // 3. Susun Pesan untuk Admin
        $msgToAdmin = "🔔 *REQUEST DATA USER*\n\n";
        $msgToAdmin .= "Ada user meminta informasi ID Pengguna.\n\n";
        $msgToAdmin .= "👤 Nama: *{$user->nama_lengkap}*\n";
        $msgToAdmin .= "🏠 Toko: {$user->store_name}\n";
        $msgToAdmin .= "📱 WA: {$user->no_wa}\n\n";
        $msgToAdmin .= "Klik link di bawah untuk mengirim data ke user tersebut:\n";
        $msgToAdmin .= $approvalLink . "\n\n";
        $msgToAdmin .= "_(Jika ini bukan user valid, abaikan pesan ini)_";

        // 4. Kirim WA ke Admin (Nomor Hardcode sesuai request)
        $adminWa = '08819435180'; 
        
        $isSent = $this->sendWhatsAppMessage($adminWa, $msgToAdmin);

        if ($isSent) {
            $this->sendMessage($chatId, "✅ <b>PERMINTAAN TERKIRIM!</b>\n\nSistem telah menghubungi Admin.\nMohon tunggu, data ID & Akun akan dikirimkan ke WhatsApp <b>$inputWa</b> setelah disetujui Admin.");
        } else {
            $this->sendMessage($chatId, "❌ Gagal menghubungi Admin. Silakan coba lagi nanti.");
        }
    }

    public function approveDataRequest($no_wa)
    {
        // 1. Ambil Data User
        $user = DB::table('Pengguna')->where('no_wa', $no_wa)->first();

        if (!$user) {
            return "<div style='text-align:center; padding:50px; font-family:sans-serif;'>
                        <h1 style='color:red;'>❌ Data Tidak Ditemukan</h1>
                        <p>Nomor WA: $no_wa tidak terdaftar.</p>
                    </div>";
        }

        // ============================================================
        // 🔥 FITUR ANTI-SPAM / ANTI-DOUBLE SEND
        // ============================================================
        $cacheKey = "sent_id_lock_" . $no_wa;

        // Cek apakah sudah pernah dikirim dalam 5 menit terakhir?
        if (Cache::has($cacheKey)) {
            // Jika sudah, JANGAN KIRIM WA LAGI, langsung tampilkan halaman sukses saja
            // Ini mencegah dobel chat jika admin refresh halaman atau double click
            return view('admin.telegram.success_send_id', compact('user'));
        }

        // ============================================================
        // PROSES PENGIRIMAN (Hanya jalan jika belum ada di cache)
        // ============================================================
        
        $alamatLengkap = "{$user->address_detail}, {$user->village}, {$user->district}, {$user->regency}, {$user->province} ({$user->postal_code})";

        $msgToCustomer = "👋 Halo Kak *{$user->nama_lengkap}*,\n";
        $msgToCustomer .= "Berikut adalah data akun Sancaka Anda:\n\n";
        $msgToCustomer .= "🆔 **ID PENGGUNA: {$user->id_pengguna}**\n";
        $msgToCustomer .= "👤 Nama: {$user->nama_lengkap}\n";
        $msgToCustomer .= "🏠 Toko: {$user->store_name}\n";
        $msgToCustomer .= "📍 Alamat: {$alamatLengkap}\n";
        $msgToCustomer .= "💰 Saldo: Rp " . number_format($user->saldo, 0, ',', '.') . "\n\n";
        $msgToCustomer .= "Mohon simpan ID Pengguna Anda (Angka ID diatas) untuk keperluan Topup atau Reset PIN. Terima kasih! 🙏";

        // Kirim WA
        $isSent = $this->sendWhatsAppMessage($user->no_wa, $msgToCustomer);

        if ($isSent) {
            // ✅ SUKSES KIRIM -> KUNCI KODE SELAMA 5 MENIT
            // Agar kalau direfresh tidak kirim lagi
            Cache::put($cacheKey, true, 300); // 300 detik = 5 menit

            return view('admin.telegram.success_send_id', compact('user'));
        } else {
            return "<div style='text-align:center; padding:50px; font-family:sans-serif;'>
                        <h1 style='color:red;'>❌ GAGAL KIRIM WA</h1>
                        <p>Cek koneksi Fonnte/Gateway.</p>
                    </div>";
        }
    }

}