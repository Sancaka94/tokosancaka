<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // <--- TAMBAHKAN INI
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Pesanan;
use App\Services\KiriminAjaService; // Pastikan Service ini terimport

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

                case '/resi':
                case '/lacak':
                    $this->trackResi($chatId, $text);
                    break;

                default:
                    $this->sendMessage($chatId, "⚠️ Perintah tidak dikenal. Ketik /menu untuk bantuan.");
                    break;
            }

        } catch (\Exception $e) {
            Log::error("Bot Error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Maaf, terjadi kesalahan sistem.");
        }

        return response('OK', 200);
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

    /**
     * Menampilkan Menu Utama (Humanis & Adaptif)
     */
    private function sendMenu($chatId, $name)
    {
        // 1. Logika Sapaan Waktu (Adaptif)
        $hour = Carbon::now('Asia/Jakarta')->format('H');
        if ($hour >= 3 && $hour < 11) {
            $salam = "Selamat Pagi ☀️";
        } elseif ($hour >= 11 && $hour < 15) {
            $salam = "Selamat Siang 🌤";
        } elseif ($hour >= 15 && $hour < 18) {
            $salam = "Selamat Sore 🌇";
        } else {
            $salam = "Selamat Malam 🌙";
        }

        // 2. Susun Pesan
        $msg = "$salam, Kak <b>$name</b>! 👋\n";
        $msg .= "Terima kasih telah setia bersama <b>Sancaka Express</b>. 🥰\n\n";
        
        $msg .= "Ada yang bisa kami bantu urus hari ini?\n";
        $msg .= "Silakan pilih layanan di bawah ini:\n\n";

        // --- SECTION EKSPEDISI ---
        $msg .= "🚚 <b>LAYANAN PENGIRIMAN</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🔎 <b>Cek Ongkir Pintar</b>\n";
        $msg .= "Ketik: <code>/ongkir [Asal], [Tujuan], [Berat]</code>\n";
        $msg .= "<i>Contoh: /ongkir Ngawi, Surabaya, 1000</i>\n\n";

        $msg .= "📦 <b>Lacak Paket (Resi)</b>\n";
        $msg .= "Ketik: <code>/resi [Nomor Resi]</code>\n";
        $msg .= "<i>Contoh: /resi IDE700217577xxx Atau SPX12345xxxx</i>\n\n";

        // --- TAMBAHKAN BAGIAN INI ---
        $msg .= "📮 <b>Cek Kode Pos</b>\n";
        $msg .= "Ketik: <code>/kodepos [Nama Wilayah]</code>\n";
        $msg .= "<i>Contoh: /kodepos Ketanggi Ngawi</i>\n\n";
        // ----------------------------

        $msg .= "📍 <b>Cari ID Wilayah</b>\n";
        $msg .= "Ketik: <code>/cari [Nama Kecamatan]</code>\n\n";

        // --- SECTION PPOB ---
        $msg .= "📱 <b>PPOB & PULSA</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= "💰 <b>Cek Saldo Agen</b>\n";
        $msg .= "Klik 👉 /saldo\n\n";
        
        $msg .= "🛒 <b>Isi Pulsa / Data</b>\n";
        $msg .= "Ketik: <code>/beli [Kode] [NomorHP]</code>\n";
        $msg .= "<i>Contoh: /beli TN10 08123456789</i>\n\n";

        // --- FOOTER ---
        $msg .= "➖➖➖➖➖➖➖➖➖➖\n";
        $msg .= "💬 <b>Butuh Bantuan CS?</b>\n";
        $msg .= "Hubungi Admin kami: @sancakaexpress\n";
        $msg .= "WA Admin +628819435180\n";
        $msg .= "<i>Kami siap melayani dengan sepenuh hati. 🙏</i>";

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

}