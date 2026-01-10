<?php

namespace App\Http\Controllers;


use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Models
use App\Models\Affiliate;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Store; 
use App\Models\TopUp;
use App\Models\User;

// Services
use App\Services\DokuJokulService;
use App\Services\KiriminAjaService;
use App\Services\DanaSignatureService; // <--- TAMBAHKAN INI

// Services
use App\Services\FonnteService; 

use Illuminate\Support\Facades\Log;

class AffiliateController extends Controller
{
    /**
     * Menampilkan Dashboard Admin Afiliasi
     */
    public function index()
    {
        // 1. Ambil data afiliasi beserta statistik order yang SUDAH LUNAS (paid)
        $affiliates = Affiliate::with(['coupon.orders' => function($query) {
            $query->where('payment_status', 'paid'); 
        }])->latest()->get();

        // 2. Hitung Ringkasan Data
        $totalAffiliates = $affiliates->count();
        $totalTransactions = 0;
        $totalRevenueGenerated = 0;

        foreach($affiliates as $aff) {
            if($aff->coupon) {
                $orders = $aff->coupon->orders;
                $totalTransactions += $orders->count();
                $totalRevenueGenerated += $orders->sum('final_price');
            }
        }

        // 3. Generate QR Code Pendaftaran (Untuk Admin share ke calon partner)
        // Link ini mengarah ke Form Pendaftaran Partner
        $registerUrl = route('affiliate.create'); 
        $qrRegister = QrCode::size(150)->generate($registerUrl);

        return view('affiliate.index', compact(
            'affiliates', 
            'totalAffiliates', 
            'totalTransactions', 
            'totalRevenueGenerated',
            'qrRegister',
            'registerUrl'
        ));
    }
    
    /**
     * Halaman Form Pendaftaran Afiliasi (Public)
     */
    public function create()
    {
        return view('affiliate.register');
    }

    /**
     * PROSES DAFTAR (Kirim ID & PIN ke WA)
     */
    public function store(Request $request)
    {
        // 1. Validasi
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required',
            'whatsapp' => 'required|numeric',
            'pin' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('error', 'Cek kembali data Anda.');
        }

        // Cek Duplikat WA Manual
        if (Affiliate::where('whatsapp', $request->whatsapp)->exists()) {
             return redirect()->back()->with('error', 'Nomor WhatsApp sudah terdaftar. Silakan login/edit data.')->withInput();
        }

        DB::beginTransaction();
        try {
            // 2. Simpan Data
            $affiliate = Affiliate::create([
                'name' => $request->name,
                'whatsapp' => $request->whatsapp,
                'address' => $request->address,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'pin' => Hash::make($request->pin), // Hash PIN untuk database
                'coupon_code' => 'PENDING',
                'balance' => 0
            ]);

            // 3. Generate Kupon (ID - 30%)
            $discountValue = 30; // Diskon 30%
            $finalCouponCode = "SANCAKA-" . $affiliate->id . "-" . $discountValue;
            
            $affiliate->update(['coupon_code' => $finalCouponCode]);

            Coupon::create([
                'code' => $finalCouponCode,
                'type' => 'percent',
                'value' => $discountValue,
                'start_date' => now(),
                'expiry_date' => now()->addYears(5),
                'description' => 'Partner: ' . $request->name,
                'is_active' => true
            ]);

            // 3. SUSUN PESAN WHATSAPP (LENGKAP ID & PIN)
            // Pastikan route 'orders.create' sudah didefinisikan di web.php
            // Hasil link akan menjadi: https://tokosancaka.com/.../orders/create?coupon=SANCAKA-10-150
            $targetUrl = route('orders.create', ['coupon' => $finalCouponCode]); 
            
            $msg  = "🎉 *SELAMAT! PENDAFTARAN BERHASIL* 🎉\n\n";
            $msg .= "Halo Kak *{$request->name}*,\n";
            $msg .= "Akun Partner Sancaka Anda sudah aktif.\n\n";
            
            $msg .= "📋 *DATA AKUN ANDA:*\n";
            $msg .= "🆔 ID Partner: *{$affiliate->id}* (Simpan ini!)\n";
            $msg .= "📱 No. WA: {$request->whatsapp}\n";
            $msg .= "🔐 PIN: *{$request->pin}*\n\n";
            
            $msg .= "⚠️ *PENTING:* Simpan ID dan PIN ini. Jika Anda lupa nomor HP, Anda bisa login menggunakan ID Partner.\n\n";

            // --- BAGIAN BARU: LINK UNTUK DISEBARKAN ---
            $msg .= "➖➖➖➖➖➖➖➖➖➖\n";
            $msg .= "📢 *TEMPLATE SEBAR LINK PROMO* 📢\n";
            $msg .= "(Salin pesan di bawah ini & sebarkan ke Teman/Sosmed)\n";
            $msg .= "➖➖➖➖➖➖➖➖➖➖\n\n";

            $msg .= "Mau cetak kebutuhan apapun dengan harga *DISKON SPESIAL*? 🤩\n\n";
            $msg .= "Pakai kode kupon diskon saya: *{$finalCouponCode}*\n";
            $msg .= "Atau langsung klik link ini untuk order (Diskon Otomatis):\n";
            $msg .= "👉 {$targetUrl}\n\n";
            $msg .= "Buruan order sekarang sebelum antri! 🏃💨\n";
            
            $msg .= "➖➖➖➖➖➖➖➖➖➖\n\n";
            $msg .= "Semangat Cari Cuan Ya Kak *{$request->name}* ! 🚀";

            // 5. Kirim WA (Gunakan fungsi CURL atau Service Anda)
            $this->sendFonnte($request->whatsapp, $msg);

            DB::commit();
            return redirect()->back()->with('success', 'Berhasil! ID Partner dan PIN telah dikirim ke WhatsApp Anda.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mendaftar: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * FITUR LUPA PIN (RESET VIA WA)
     */
    public function forgotPin(Request $request)
    {
        $request->validate([
            'whatsapp' => 'required|numeric',
        ]);

        // Cari user berdasarkan WA
        $affiliate = Affiliate::where('whatsapp', $request->whatsapp)->first();

        if (!$affiliate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor WhatsApp tidak terdaftar.'
            ], 404);
        }

        try {
            // 1. Generate PIN Baru (6 Angka Random)
            $newPin = rand(100000, 999999);

            // 2. Update di Database (Hash)
            $affiliate->update([
                'pin' => Hash::make($newPin)
            ]);

            // 3. Susun Pesan
            $message = "🔑 *RESET PIN BERHASIL*\n\n";
            $message .= "Halo Kak *{$affiliate->name}*,\n";
            $message .= "PIN akun partner Anda telah direset.\n\n";
            $message .= "PIN BARU: *{$newPin}*\n\n";
            $message .= "Silakan gunakan PIN ini untuk login. Segera ganti PIN Anda di menu Edit Data demi keamanan.";

            // 4. Kirim WA
            $this->sendFonnte($affiliate->whatsapp, $message);

            return response()->json([
                'status' => 'success',
                'message' => 'PIN Baru telah dikirim ke WhatsApp Anda.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API CEK AKUN (LOGIN DENGAN WA ATAU ID)
     */
    public function checkAccountPublic(Request $request)
    {
        try {
            // Ubah validasi: login_key bisa WA atau ID
            if (!$request->login_key || !$request->pin) {
                return response()->json(['status' => 'error', 'message' => 'Masukkan No. WA / ID dan PIN.'], 400);
            }

            // CARI BERDASARKAN WA ATAU ID
            $affiliate = Affiliate::where('whatsapp', $request->login_key)
                        ->orWhere('id', $request->login_key)
                        ->first();

            if (!$affiliate) {
                return response()->json(['status' => 'error', 'message' => 'Akun tidak ditemukan (Cek ID/WA).'], 404);
            }

            // AUTO-FIX PIN (Seperti request sebelumnya)
            $inputPin = $request->pin;
            $dbPin    = $affiliate->pin;
            $isHashed = \Illuminate\Support\Facades\Hash::info($dbPin)['algoName'] !== 'unknown';
            
            $pinValid = false;

            if (!$isHashed) {
                if ($dbPin == $inputPin) {
                    $pinValid = true;
                    $affiliate->update(['pin' => Hash::make($inputPin)]);
                }
            } else {
                if (Hash::check($inputPin, $dbPin)) {
                    $pinValid = true;
                }
            }

            if (!$pinValid) {
                return response()->json(['status' => 'error', 'message' => 'PIN Salah!'], 401);
            }

            return response()->json([
                'status' => 'success',
                'data' => $affiliate
            ]);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['status' => 'error', 'message' => 'Server Error.'], 500);
        }
    }

    /**
     * Halaman Cetak QR Code Spesifik Member
     */
    public function printQr($id)
    {
        $affiliate = Affiliate::findOrFail($id);

        // Generate Link langsung ke halaman Kasir (Order Create) dengan parameter kupon
        // Agar pelanggan tinggal scan dan langsung belanja
        $shopLinkWithCoupon = route('orders.create', ['coupon' => $affiliate->coupon_code]);

        // Generate QR Code berisi Link tersebut
        $qrCode = QrCode::size(300)->generate($shopLinkWithCoupon);

        return view('affiliate.print_qr', compact('affiliate', 'qrCode', 'shopLinkWithCoupon'));
    }

    // Tambahkan di App\Http\Controllers\AffiliateController.php

public function syncBalance()
{
    // 1. Ambil semua affiliate beserta data ordernya yang sudah LUNAS (paid)
    $affiliates = Affiliate::with(['coupon.orders' => function($q) {
        $q->where('payment_status', 'paid');
    }])->get();

    DB::beginTransaction();
    try {
        foreach ($affiliates as $aff) {
            if ($aff->coupon) {
                // 2. Hitung total omzet dari order yang menggunakan kupon ini
                $totalOmzet = $aff->coupon->orders->sum('final_price');
                
                // 3. Hitung Komisi (10%)
                $komisiSeharusnya = $totalOmzet * 0.10;

                // 4. Update Saldo Affiliate
                // PENTING: Ini akan me-reset saldo sesuai hitungan transaksi. 
                // Jika nanti ada fitur "Penarikan Dana", logikanya harus disesuaikan (dikurangi penarikan).
                $aff->update([
                    'balance' => $komisiSeharusnya
                ]);
            }
        }
        DB::commit();
        return redirect()->back()->with('success', 'Saldo Profit semua member berhasil disinkronisasi ulang!');
        
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
    }
}



    /**
     * API UPDATE DATA PUBLIC (AUTO FIX PIN PLAIN TEXT)
     */
    public function updateAccountPublic(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:affiliates,id',
                'verification_pin' => 'required',
                'name' => 'required',
                'whatsapp' => 'required',
            ]);

            $affiliate = Affiliate::find($request->id);
            
            // --- LOGIKA PERBAIKAN ERROR "Not Bcrypt" ---
            
            $inputPin = $request->verification_pin;
            $dbPin    = $affiliate->pin;
            $isHashed = \Illuminate\Support\Facades\Hash::info($dbPin)['algoName'] !== 'unknown';
            $isValid  = false;

            if (!$isHashed) {
                // Jika DB masih angka biasa, bandingkan langsung
                if ($dbPin == $inputPin) {
                    $isValid = true;
                    // Sekalian update jadi hash
                    $affiliate->update(['pin' => Hash::make($inputPin)]); 
                }
            } else {
                // Jika DB sudah hash, pakai Hash::check
                if (Hash::check($inputPin, $dbPin)) {
                    $isValid = true;
                }
            }

            if (!$isValid) {
                return redirect()->back()->with('error', 'Gagal Update: PIN Verifikasi Salah.');
            }

            // Update Data
            $dataToUpdate = [
                'name' => $request->name,
                'whatsapp' => $request->whatsapp,
                'address' => $request->address,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
            ];

            // Cek ganti PIN Baru
            if ($request->filled('new_pin')) {
                $request->validate(['new_pin' => 'numeric|digits:6']);
                $dataToUpdate['pin'] = Hash::make($request->new_pin);
            }

            $affiliate->update($dataToUpdate);

            return redirect()->back()->with('success', 'Data Berhasil Diperbarui!');

        } catch (\Exception $e) {
            Log::error('ERROR updateAccountPublic: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan server.');
        }
    }
    /**
     * 3. PROSES UPDATE PIN (KEAMANAN)
     */
    public function updatePin(Request $request)
    {
        // Ambil ID Affiliate yang sedang login
        $affiliateId = Session::get('affiliate_id'); // Sesuaikan dengan cara login Anda
        $affiliate = Affiliate::findOrFail($affiliateId);

        // Cek apakah user sudah punya PIN sebelumnya atau belum
        if (empty($affiliate->pin)) {
            // A. KONDISI: BUAT PIN BARU (Belum punya PIN)
            $request->validate([
                'new_pin'              => 'required|numeric|digits:6|confirmed', // confirmed cek field new_pin_confirmation
            ], [
                'new_pin.confirmed'    => 'Konfirmasi PIN tidak cocok.',
                'new_pin.digits'       => 'PIN harus 6 digit angka.'
            ]);

        } else {
            // B. KONDISI: GANTI PIN (Sudah punya PIN)
            $request->validate([
                'current_pin'          => 'required|numeric',
                'new_pin'              => 'required|numeric|digits:6|confirmed',
            ], [
                'new_pin.confirmed'    => 'Konfirmasi PIN Baru tidak cocok.',
            ]);

            // Cek apakah PIN Lama benar
            if (!Hash::check($request->current_pin, $affiliate->pin)) {
                return redirect()->back()->withErrors(['current_pin' => 'PIN Lama yang Anda masukkan salah!']);
            }
        }

        // Simpan PIN Baru (Dihash)
        $affiliate->update([
            'pin' => Hash::make($request->new_pin)
        ]);

        return redirect()->back()->with('success', 'PIN Keamanan berhasil diperbarui!');
    }

    // Fungsi Helper Kirim WA (Jika belum punya class service)
    private function sendFonnte($target, $message) {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://api.fonnte.com/send',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => array(
                'target' => $target,
                'message' => $message,
              ),
              CURLOPT_HTTPHEADER => array(
                'Authorization: ' . env('FONNTE_TOKEN', 'ynMyPswSKr14wdtXMJF7') // Ganti dengan token fonnte Anda
              ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        } catch(\Exception $e) {}
    }

    /**
     * MENAMPILKAN FORM EDIT (ADMIN)
     */
    public function edit($id)
    {
        // Ambil data affiliate beserta kuponnya
        $affiliate = Affiliate::with('coupon')->findOrFail($id);
        
        return view('affiliate.edit', compact('affiliate'));
    }

    /**
     * PROSES UPDATE DATA (ADMIN)
     * Bisa ubah semua data termasuk Saldo dan Kupon
     */
    public function update(Request $request, $id)
    {
        $affiliate = Affiliate::findOrFail($id);

        // 1. Validasi Data
        $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp' => 'required|numeric|unique:affiliates,whatsapp,' . $id, // Ignore unique untuk ID ini
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|numeric',
            'balance' => 'required|numeric', // Admin bisa koreksi saldo
            'coupon_code' => 'required|string', // Admin bisa ganti kode kupon custom
            'pin' => 'nullable|numeric|digits:6', // Opsional, diisi jika ingin reset PIN
        ]);

        DB::beginTransaction();
        try {
            // 2. Siapkan data update dasar
            $dataToUpdate = [
                'name' => $request->name,
                'whatsapp' => $request->whatsapp,
                'address' => $request->address,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'balance' => $request->balance,
                'coupon_code' => $request->coupon_code,
            ];

            // 3. Cek jika PIN diisi, maka update PIN (Hash ulang)
            if ($request->filled('pin')) {
                $dataToUpdate['pin'] = Hash::make($request->pin);
            }

            // 4. Update Tabel Affiliate
            $affiliate->update($dataToUpdate);

            // 5. Update Tabel Coupons (PENTING: Agar kode kupon sinkron)
            // Jika kode kupon di tabel affiliate berubah, di tabel coupon juga harus berubah
            if ($affiliate->coupon) {
                $affiliate->coupon->update([
                    'code' => $request->coupon_code,
                    'description' => 'Partner: ' . $request->name // Update nama di deskripsi kupon juga
                ]);
            } else {
                // Jika belum punya kupon, buatkan baru (jaga-jaga error data lama)
                Coupon::create([
                    'code' => $request->coupon_code,
                    'type' => 'percent',
                    'value' => 30, // Default value jika create baru
                    'start_date' => now(),
                    'expiry_date' => now()->addYears(5),
                    'description' => 'Partner: ' . $request->name,
                    'is_active' => true
                ]);
            }

            DB::commit();
            return redirect()->route('affiliate.index')->with('success', 'Data Affiliate berhasil diperbarui sepenuhnya!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * HAPUS AFFILIATE (Opsional jika butuh)
     */
    public function destroy($id)
    {
        $affiliate = Affiliate::findOrFail($id);
        
        // Hapus kupon terkait dulu
        if($affiliate->coupon) {
            $affiliate->coupon->delete();
        }
        
        $affiliate->delete();

        return redirect()->route('affiliate.index')->with('success', 'Affiliate berhasil dihapus.');
    }

    // 1. START BINDING (LOGIKA AWAL BOS)
    public function startBinding(Request $request)
    {
        Log::info('[BINDING] Memulai proses redirect ke DANA Portal...');
        
        $affiliateId = $request->affiliate_id ?? 11;

        $queryParams = [
            'partnerId'   => config('services.dana.x_partner_id'),
            'timestamp'   => now('Asia/Jakarta')->toIso8601String(),
            'externalId'  => 'BIND-' . $affiliateId . '-' . time(),
            'merchantId'  => config('services.dana.merchant_id'),
            'redirectUrl' => config('services.dana.redirect_url_oauth'), 
            'state'       => 'ID-' . $affiliateId,
            'scopes'      => 'QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE',
        ];

        return redirect("https://m.sandbox.dana.id/d/portal/oauth?" . http_build_query($queryParams));
    }

   public function handleCallback(Request $request)
{
    Log::info('[DANA CALLBACK] Mendapatkan Auth Code:', $request->all());

    $authCode = $request->input('auth_code');
    $state = $request->input('state');
    // Ambil ID Affiliate dari state, default ke 11 jika tidak ada
    $affiliateId = $state ? str_replace('ID-', '', $state) : 11;

    if (!$authCode) {
        return redirect()->route('dana.dashboard')->with('error', 'Auth Code Kosong');
    }

    // 1. Simpan Auth Code-nya dulu ke database sebagai jejak awal
    DB::table('affiliates')->where('id', $affiliateId)->update([
        'dana_auth_code' => $authCode,
        'updated_at' => now()
    ]);

    try {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $clientId = config('services.dana.x_partner_id');
        $externalId = (string) time();
        
        // Signature B2B2C: ClientID|Timestamp
        $stringToSign = $clientId . "|" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $path = '/v1.0/access-token/b2b2c.htm';
        $body = [
            'grantType' => 'authorization_code',
            'authCode' => $authCode,
            'additionalInfo' => (object)[]
        ];

        $response = Http::withHeaders([
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => $clientId,
            'X-CLIENT-KEY'  => $clientId,
            'X-EXTERNAL-ID' => $externalId,
            'Content-Type'  => 'application/json'
        ])->post('https://api.sandbox.dana.id' . $path, $body);

        $result = $response->json();
        // Kode sukses DANA Sandbox seringkali 2007400 untuk B2B2C
        $successCodes = ['2001100', '2007400'];

        if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {
            // A. UPDATE TOKEN KE DATABASE (Prioritas Utama)
            DB::table('affiliates')->where('id', $affiliateId)->update([
                'dana_access_token' => $result['accessToken'],
                'updated_at' => now()
            ]);

            // B. CATAT KE RIWAYAT TRANSAKSI (Gunakan try-catch agar tidak crash jika DB error)
            try {
                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $affiliateId,
                    'type' => 'BINDING',
                    'reference_no' => $externalId,
                    'phone' => '-', 
                    'amount' => 0,
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);
            } catch (\Exception $dbEx) {
                Log::error('[DANA CALLBACK] Gagal simpan log transaksi: ' . $dbEx->getMessage());
            }

            return redirect()->route('dana.dashboard')->with('success', '✅ Akun Berhasil Terhubung!');
        }

        // JIKA GAGAL TUKAR TOKEN
        try {
            DB::table('dana_transactions')->insert([
                'affiliate_id' => $affiliateId,
                'type' => 'BINDING',
                'reference_no' => $externalId,
                'phone' => '-',
                'amount' => 0,
                'status' => 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);
        } catch (\Exception $dbEx) {
            Log::error('[DANA CALLBACK] Gagal simpan log error: ' . $dbEx->getMessage());
        }

        Log::error('[EXCHANGE FAILED]', $result);
        return redirect()->route('dana.dashboard')->with('error', 'Gagal Tukar Token: ' . ($result['responseMessage'] ?? 'Unknown Error'));

    } catch (\Exception $e) {
        Log::error('[DANA CALLBACK] System Error:', ['msg' => $e->getMessage()]);
        return redirect()->route('dana.dashboard')->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}

    // 3. CEK SALDO USER (LOGIKA SNAP 2001100 - TIDAK DIRUBAH)
    public function checkBalance(Request $request)
    {
        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        $accessToken = $request->access_token ?? $aff->dana_access_token;

        if (!$accessToken) return back()->with('error', 'Token Kosong.');

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/balance-inquiry.htm';
        $body = [
            'partnerReferenceNo' => 'BAL' . time(),
            'balanceTypes' => ['BALANCE'],
            'additionalInfo' => ['accessToken' => $accessToken]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $response = Http::withHeaders([
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time(),
            'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION',
            'CHANNEL-ID'    => '95221',
            'ORIGIN'        => config('services.dana.origin'),
            'Authorization-Customer' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json'
        ])->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();

        if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
            $amount = $result['accountInfos'][0]['availableBalance']['value'];
            // Simpan ke dana_user_balance (Pemisah Profit)
            DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_user_balance' => $amount, 'updated_at' => now()]);
            return back()->with('success', 'Saldo Real DANA Terupdate!');
        }
        return back()->with('error', 'Gagal: ' . ($result['responseMessage'] ?? 'Error'));
    }

    // 4. CEK SALDO MERCHANT (LOGIKA OPEN API V2.0 - TIDAK DIRUBAH)
    public function checkMerchantBalance(Request $request)
    {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $payload = ["request" => ["head" => ["version" => "2.0", "function" => "dana.merchant.queryMerchantResource", "clientId" => config('services.dana.x_partner_id'), "clientSecret" => config('services.dana.client_secret'), "reqTime" => $timestamp, "reqMsgId" => (string) Str::uuid(), "reserve" => "{}"], "body" => ["requestMerchantId" => config('services.dana.merchant_id'), "merchantResourceInfoList" => ["MERCHANT_DEPOSIT_BALANCE"]]]];

        $jsonToSign = json_encode($payload['request'], JSON_UNESCAPED_SLASHES);
        $signature = $this->generateSignature($jsonToSign);
        
        $response = Http::post('https://api.sandbox.dana.id/dana/merchant/queryMerchantResource.htm', ["request" => $payload['request'], "signature" => $signature]);
        $res = $response->json();

        if (isset($res['response']['body']['resultInfo']['resultStatus']) && $res['response']['body']['resultInfo']['resultStatus'] === 'S') {
            $val = json_decode($res['response']['body']['merchantResourceInformations'][0]['value'], true);
            DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_merchant_balance' => $val['amount']]);
            return back()->with('success', 'Saldo Merchant Terupdate!');
        }
        return back()->with('error', 'Gagal Cek Merchant');
    }

    private function generateSignature($stringToSign) {
        $privateKey = config('services.dana.private_key');
        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }

   public function topupSaldo(Request $request)
{
    Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', ['affiliate_id' => $request->affiliate_id]);

    // 1. Ambil data affiliate
    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
    
    // 2. Validasi Saldo Profit
    if (!$aff || $aff->balance < $request->amount) {
        Log::warning('[DANA TOPUP] Saldo Tidak Cukup atau Affiliate Tidak Ditemukan');
        return back()->with('error', 'Gagal: Saldo profit tidak mencukupi.');
    }

    // 3. Sanitasi Nomor HP (Ubah 08xx jadi 628xx)
    $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone ?? $aff->whatsapp);
    if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);

    // 4. Siapkan Data Request
    $timestamp = now('Asia/Jakarta')->toIso8601String();
    $path = '/v1.0/emoney/customer-top-up.htm';
    $partnerRef = 'TP' . time() . Str::random(4);
    
    $body = [
        'partnerReferenceNo' => $partnerRef,
        'amount' => [
            'value' => number_format((float)$request->amount, 2, '.', ''),
            'currency' => 'IDR'
        ],
        'beneficiaryAccountNo' => $cleanPhone,
        'additionalInfo' => (object)[] 
    ];

    $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
    $hashedBody = strtolower(hash('sha256', $jsonBody));
    $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
    $signature = $this->generateSignature($stringToSign);

    // 5. Definisikan Headers
    $headers = [
        'X-TIMESTAMP'   => $timestamp,
        'X-SIGNATURE'   => $signature,
        'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
        'X-EXTERNAL-ID' => (string) time() . Str::random(4),
        'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION',
        'CHANNEL-ID'    => '95221',
        'Content-Type'  => 'application/json',
        'Authorization-Customer' => 'Bearer ' . $aff->dana_access_token
    ];

    try {
        Log::info('[DANA TOPUP] Mengirim Request...', ['headers' => $headers]);

        $response = Http::withHeaders($headers)
            ->withBody($jsonBody, 'application/json')
            ->post('https://api.sandbox.dana.id' . $path);
            
        $result = $response->json();

        Log::info('[DANA TOPUP] Respon Diterima', ['status' => $response->status(), 'result' => $result]);

        // 6. Cek Keberhasilan (Status 200 di Sandbox seringkali result-nya null)
        if ($response->successful()) {
            
            // A. Potong Saldo Profit Affiliate
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

            // B. Catat ke Audit Log Transaksi
            DB::table('dana_transactions')->insert([
                'affiliate_id' => $aff->id,
                'type' => 'TOPUP',
                'reference_no' => $partnerRef,
                'phone' => $cleanPhone,
                'amount' => $request->amount,
                'status' => 'SUCCESS',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            // C. Kirim Notifikasi WhatsApp ke USER (Affiliate)
            $pesanUser = "✅ *PENCAIRAN PROFIT BERHASIL*\n\n";
            $pesanUser = "Halo " . $aff->name . ",\n";
            $pesanUser = "Pencairan profit Anda ke DANA telah sukses.\n\n";
            $pesanUser = "*Detail:* \n";
            $pesanUser = "▪️ Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n";
            $pesanUser = "▪️ No. DANA: " . $cleanPhone . "\n";
            $pesanUser = "▪️ Ref ID: " . $partnerRef . "\n";
            $pesanUser = "▪️ Waktu: " . now()->format('d/m H:i') . " WIB\n\n";
            $pesanUser = "Saldo profit Anda telah otomatis terpotong. Terima kasih!";
            
            $this->sendWhatsApp($cleanPhone, $pesanUser);

            // D. Kirim Notifikasi ke ADMIN (Nomor Bos)
            $pesanAdmin = "📢 *LAPORAN TOPUP SUKSES*\n\n";
            $pesanAdmin = "Affiliate: " . $aff->name . " (ID: " . $aff->id . ")\n";
            $pesanAdmin = "Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n";
            $pesanAdmin = "Tujuan: " . $cleanPhone . "\n";
            $pesanAdmin = "Status: Saldo Berhasil Dipotong.";
            
            $this->sendWhatsApp('6285745808809', $pesanAdmin); // Nomor Admin Bos

            Log::info('[DANA TOPUP] BERHASIL & WA TERKIRIM');
            
            return back()->with('success', '💸 Topup Berhasil, Saldo Dipotong, dan WA Terkirim!');
        }

        return back()->with('error', 'Gagal dari DANA: ' . ($result['responseMessage'] ?? 'Respon Server Error'));

    } catch (\Exception $e) {
        Log::error('[DANA TOPUP] Exception!', ['msg' => $e->getMessage()]);
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}

public function accountInquiry(Request $request)
{
    // --- [LOG 1] INPUT REQUEST ---
    Log::info('[DANA INQUIRY] Start Process', [
        'affiliate_id' => $request->affiliate_id, 
        'amount' => $request->amount,
        'ip' => $request->ip()
    ]);

    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
    if (!$aff) {
        Log::error('[DANA INQUIRY] Affiliate Not Found', ['id' => $request->affiliate_id]);
        return back()->with('error', 'Affiliate tidak ditemukan.');
    }

    // --- [LOG 2] SANITASI NOMOR HP ---
    $rawPhone = $request->phone ?? $aff->whatsapp;
    $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
    if (substr($cleanPhone, 0, 1) === '0') {
        $cleanPhone = '62' . substr($cleanPhone, 1);
    }
    Log::info('[DANA INQUIRY] Phone Sanitized', ['original' => $rawPhone, 'clean' => $cleanPhone]);

    $timestamp = now('Asia/Jakarta')->toIso8601String();
    $path = '/v1.0/emoney/account-inquiry.htm';
    $amountValue = $request->amount ?? 10000;

    $body = [
        "partnerReferenceNo" => "INQ" . time() . Str::random(5),
        "customerNumber"     => $cleanPhone,
        "amount" => [
            "value"    => number_format((float)$amountValue, 2, '.', ''),
            "currency" => "IDR"
        ],
        "transactionDate" => $timestamp,
        "additionalInfo"  => [
            "fundType"           => "AGENT_TOPUP_FOR_USER_SETTLE",
            "externalDivisionId" => "", 
            "chargeTarget"       => "MERCHANT", 
            "customerId"         => ""
        ]
    ];

    // --- [LOG 3] SIGNATURE PROCESS ---
    $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hashedBody = strtolower(hash('sha256', $jsonBody));
    $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
    $signature = $this->generateSignature($stringToSign);

    Log::info('[DANA INQUIRY] Security Detail', [
        'path' => $path,
        'stringToSign' => $stringToSign,
        'signature' => $signature
    ]);

    $headers = [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $aff->dana_access_token,
        'X-TIMESTAMP'   => $timestamp,
        'X-SIGNATURE'   => $signature,
        'ORIGIN'        => config('services.dana.origin'),
        'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
        'X-EXTERNAL-ID' => (string) time() . Str::random(5),
        'X-IP-ADDRESS'  => $request->ip() ?? '127.0.0.1',
        'X-DEVICE-ID'   => 'DANA-DASHBOARD-01',
        'CHANNEL-ID'    => '95221'
    ];

    try {
        // --- [LOG 4] SENDING REQUEST ---
        Log::info('[DANA INQUIRY] Sending Request to DANA', ['body' => $body]);

        $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id' . $path);
        $result = $response->json();

        // --- [LOG 5] RESPONSE RECEIVED ---
        Log::info('[DANA INQUIRY] Response Received', ['status' => $response->status(), 'result' => $result]);

        $resCode = $result['responseCode'] ?? '5003700';
        $resMsg = $result['responseMessage'] ?? 'Unexpected response';

        // --- [DATABASE 1] CATAT KE TABEL TRANSAKSI (AUDIT LOG) ---
        DB::table('dana_transactions')->insert([
            'affiliate_id' => $request->affiliate_id,
            'type' => 'INQUIRY',
            'reference_no' => $body['partnerReferenceNo'],
            'phone' => $cleanPhone,
            'amount' => $amountValue,
            'status' => in_array($resCode, ['2000000', '2003700']) ? 'SUCCESS' : 'FAILED',
            'response_payload' => json_encode($result),
            'created_at' => now()
        ]);

        // --- [MAPPING] RESPOND CODE SESUAI DOKUMENTASI BOS ---
        $responseMapping = [
            '2003700' => '✅ SUCCESS: Account Inquiry processed.',
            '4003700' => '❌ FAILED: Bad Request (General).',
            '4003701' => '❌ FAILED: Invalid Field Format.',
            '4003702' => '❌ FAILED: Invalid Mandatory Field.',
            '4013700' => '❌ UNAUTHORIZED: General Auth Error.',
            '4013701' => '❌ UNAUTHORIZED: Invalid B2B Token.',
            '4013702' => '❌ UNAUTHORIZED: Invalid Customer Token.',
            '4033702' => '⚠️ TEST CASE: Exceeds Amount Limit (21jt).',
            '4033705' => '❌ FAILED: Do Not Honor (Abnormal Status).',
            '4033714' => '❌ FAILED: Insufficient Funds (Merchant).',
            '4033718' => '❌ FAILED: Inactive Account.',
            '4043711' => '❌ FAILED: Invalid Account/Not Found.',
            '4293700' => '❌ FAILED: Too Many Requests.',
            '5003701' => '❌ FAILED: Internal Server Error.',
        ];

        $displayMsg = $responseMapping[$resCode] ?? "[$resCode] $resMsg";

        // --- [DATABASE 2] UPDATE NAMA KE TABEL AFFILIATES ---
        if (in_array($resCode, ['2000000', '2003700'])) {
            $customerName = $result['additionalInfo']['customerName'] ?? 'Akun Valid';
            
            DB::table('affiliates')->where('id', $request->affiliate_id)->update([
                'dana_user_name' => $customerName,
                'updated_at' => now()
            ]);

            // --- [FONNTE 1] WA KE USER ---
            $pesanUser = "🛡️ *Sancaka DANA Center - Verifikasi*\n\n";
            $pesanUser .= "Halo *" . $aff->name . "*,\n";
            $pesanUser .= "Akun DANA Anda berhasil diverifikasi.\n\n";
            $pesanUser .= "▪️ Nama: *" . $customerName . "*\n";
            $pesanUser .= "▪️ No. DANA: " . $cleanPhone . "\n";
            $pesanUser .= "▪️ Status: ✅ *AKUN VALID*\n\n";
            $pesanUser .= "Terima kasih!";
            $this->sendWhatsApp($cleanPhone, $pesanUser);

            return back()->with('success', $displayMsg);
        }

        // --- [FONNTE 2] WA KE ADMIN (NOMOR BOS) UNTUK ERROR/TEST CASE ---
        $pesanAdmin = "📢 *DANA INQUIRY NOTIFICATION*\n\n";
        $pesanAdmin .= "▪️ Affiliate: " . $aff->name . "\n";
        $pesanAdmin .= "▪️ Target: " . $cleanPhone . "\n";
        $pesanAdmin .= "▪️ Nominal: Rp " . number_format($amountValue, 0, ',', '.') . "\n";
        $pesanAdmin .= "▪️ Result: " . $displayMsg . "\n";
        $pesanAdmin .= "▪️ Waktu: " . now()->format('H:i:s') . " WIB";
        $this->sendWhatsApp('6285745808809', $pesanAdmin);

        return back()->with('error', $displayMsg);

    } catch (\Exception $e) {
        // --- [LOG 6] EXCEPTION ERROR ---
        Log::error('[DANA INQUIRY] Exception!', ['message' => $e->getMessage()]);
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}

public function handleWebhook(Request $request)
{
    Log::info('========== DANA WEBHOOK INCOMING ==========', $request->all());
    
    $head = $request->input('request.head');
    $body = $request->input('request.body');

    if ($head['function'] === 'dana.acquiring.order.finishNotify') {
        $merchantTransId = $body['merchantTransId'];
        $status = $body['acquirementStatus']; // Contoh: CLOSED, FAILED, SUCCESS

        // Ambil data transaksi dari audit log kita
        $trx = DB::table('dana_transactions')->where('reference_no', $merchantTransId)->first();

        if ($trx) {
            // Update Status di Audit Log
            DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => $status]);

            // LOGIKA REFUND: Jika statusnya CLOSED (timeout) atau FAILED
            if (in_array($status, ['CLOSED', 'FAILED']) && $trx->status === 'SUCCESS') {
                DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $trx->amount);
                
                // Tandai di audit log bahwa ini sudah direfund
                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'REFUNDED']);
                Log::info('[WEBHOOK] Saldo Profit Berhasil Direfund!', ['affiliate' => $trx->affiliate_id]);
            }
        }
    }

    return response()->json(['response' => ['head' => ['resultCode' => 'SUCCESS']]]);
}

private function sendWhatsApp($to, $message)
{
    $token = "ynMyPswSKr14wdtXMJF7"; // Ganti dengan token Fonte bos
    
    // Pastikan nomor format 62...
    $to = preg_replace('/[^0-9]/', '', $to);
    if (substr($to, 0, 1) === '0') $to = '62' . substr($to, 1);

    Log::info('[FONTE] Mengirim pesan ke ' . $to);

    try {
        $response = Http::withHeaders([
            'Authorization' => $token
        ])->post('https://api.fonnte.com/send', [
            'target' => $to,
            'message' => $message,
            'countryCode' => '62', // optional
        ]);

        Log::info('[FONTE] Respon:', $response->json());
        return $response->json();
    } catch (\Exception $e) {
        Log::error('[FONTE] Error: ' . $e->getMessage());
        return false;
    }
}

public function customerTopup(Request $request)
{
    // --- [LOG 1] START PROCESS ---
    Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', [
        'affiliate_id' => $request->affiliate_id,
        'target_phone' => $request->phone,
        'amount' => $request->amount,
        'ip' => $request->ip()
    ]);

    // Ambil Data Affiliate
    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
    if (!$aff) {
        Log::error('[DANA TOPUP] Affiliate Tidak Ditemukan', ['id' => $request->affiliate_id]);
        return back()->with('error', 'Affiliate tidak terdaftar di sistem.');
    }

    // --- [LOG 2] SANITASI NOMOR & NOMINAL ---
    $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
    if (substr($cleanPhone, 0, 1) === '0') { $cleanPhone = '62' . substr($cleanPhone, 1); }
    
    $timestamp = now('Asia/Jakarta')->toIso8601String();
    $partnerRef = (string) time() . Str::random(8); // Sesuai partnerReferenceNo di dokumen
    $valStr = number_format((float)$request->amount, 2, '.', '');

    // --- [BODY: HARUS SESUAI DOKUMEN BOS] ---
    $body = [
    "partnerReferenceNo" => $partnerRef,
    "customerNumber"     => $cleanPhone,
    "amount" => [
        "value"    => $valStr,
        "currency" => "IDR"
    ],
    "feeAmount" => [
        "value"    => "0.00", // Di Sandbox real, fee biasanya 0.00
        "currency" => "IDR"
    ],
    "transactionDate" => $timestamp,
    "sessionId"       => (string) Str::uuid(),
    "categoryId"      => "6",
    "notes"           => "Topup Sancaka",
    "additionalInfo"  => [
        // PAKAI INI SAJA (Standar Merchant Disbursement)
        "accountType"  => "NAME_DEPOSIT",
        "fundType"     => "AGENT_TOPUP_FOR_USER_SETTLE",
        "chargeTarget" => "MERCHANT" // Ganti DIVISION ke MERCHANT
        // externalDivisionId dan customerId DIHAPUS karena pemicu PARAM_ILLEGAL
    ]
];

    // --- [LOG 4] SIGNATURE & SECURITY ---
    $path = '/v1.0/emoney/topup.htm';
    $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
    $hashedBody = strtolower(hash('sha256', $jsonBody));
    $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
    $signature = $this->generateSignature($stringToSign);

    $headers = [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $aff->dana_access_token,
        'X-TIMESTAMP'   => $timestamp,
        'X-SIGNATURE'   => $signature,
        'ORIGIN'        => config('services.dana.origin'),
        'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
        'X-EXTERNAL-ID' => (string) time() . Str::random(6),
        'X-IP-ADDRESS'  => $request->ip(),
        'X-DEVICE-ID'   => 'SANCAKA-DANA-01',
        'CHANNEL-ID'    => '95221'
    ];

    try {
        Log::info('[DANA TOPUP] Mengirim Request ke DANA API', ['body' => $body]);
        
        $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id' . $path);
        $result = $response->json();
        
        $resCode = $result['responseCode'] ?? '5003801';

        // --- [SMART MATCHING] COCOKKAN DENGAN DATABASE dana_response_codes ---
        $library = DB::table('dana_response_codes')
                    ->where('response_code', $resCode)
                    ->where('category', 'TOPUP')
                    ->first();

        // Jika kode baru, catat otomatis
        if (!$library) {
            DB::table('dana_response_codes')->insert([
                'response_code' => $resCode,
                'category'      => 'TOPUP',
                'message_title' => 'New Code Detected',
                'description'   => $result['responseMessage'] ?? 'Unknown Error',
                'solution'      => 'Cek Dokumentasi DANA',
                'is_success'    => false,
                'created_at'    => now()
            ]);
            $library = DB::table('dana_response_codes')->where('response_code', $resCode)->where('category', 'TOPUP')->first();
        }

        // --- [DATABASE] CATAT KE dana_transactions ---
        DB::table('dana_transactions')->insert([
            'affiliate_id' => $aff->id,
            'type' => 'TOPUP',
            'reference_no' => $partnerRef,
            'phone' => $cleanPhone,
            'amount' => $request->amount,
            'status' => $library->is_success ? 'SUCCESS' : 'FAILED',
            'response_payload' => json_encode($result),
            'created_at' => now()
        ]);

        // --- [FONNTE] SETUP WHATSAPP ---
        $waToken = "ynMyPswSKr14wdtXMJF7";
        $adminWA = "6285745808809";

        if ($library->is_success) {
            // Update balance jika sukses (Opsional)
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

            // WA ke User
            $msgUser = "✅ *TOPUP BERHASIL*\n\nHalo *{$aff->name}*,\nTopup DANA ke {$cleanPhone} senilai Rp " . number_format($request->amount) . " berhasil.\n\nRef ID: {$partnerRef}\nWaktu: " . now()->format('d/m H:i') . " WIB\nTerima kasih!";
            $this->sendWhatsApp($cleanPhone, $msgUser, $waToken);
            
            Log::info('[DANA TOPUP] Berhasil', ['code' => $resCode]);
            return back()->with('dana_report', $library)->with('success', 'Topup Berhasil Diuraikan!');
        } else {
            // WA ke Admin (Error/Test Case)
            $msgAdmin = "⚠️ *DANA TOPUP ALERT*\n\nAffiliate: {$aff->name}\nTarget: {$cleanPhone}\nNominal: Rp " . number_format($request->amount) . "\nResponse: [{$resCode}] {$library->message_title}\nDesc: {$library->description}\n\nMohon dicek segera!";
            $this->sendWhatsApp($adminWA, $msgAdmin, $waToken);

            Log::error('[DANA TOPUP] Gagal/Error Response', ['result' => $result]);
            return back()->with('dana_report', $library)->with('error', 'Gagal: ' . $library->message_title);
        }

    } catch (\Exception $e) {
        Log::error('[DANA TOPUP] EXCEPTION ERROR', ['msg' => $e->getMessage()]);
        
        // WA Error ke Admin
        $this->sendWhatsApp("6285745808809", "🚨 *SYSTEM ERROR TOPUP*\nMsg: " . $e->getMessage(), "ynMyPswSKr14wdtXMJF7");
        
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}

}