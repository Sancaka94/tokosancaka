<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Models
use App\Models\Affiliate;
use App\Models\Coupon;

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

            // 3. Generate Kupon (ID - 5%)
            $discountValue = 5;
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

            // 4. SUSUN PESAN WHATSAPP (LENGKAP ID & PIN)
            $targetUrl = route('orders.create', ['coupon' => $finalCouponCode]); // Ganti route sesuai sistem order Anda
            
            $msg  = "🎉 *SELAMAT! PENDAFTARAN BERHASIL* 🎉\n\n";
            $msg .= "Halo Kak *{$request->name}*,\n";
            $msg .= "Akun Partner Sancaka Anda sudah aktif.\n\n";
            
            $msg .= "📋 *DATA AKUN ANDA:*\n";
            $msg .= "🆔 ID Partner: *{$affiliate->id}* (Simpan ini!)\n";
            $msg .= "📱 No. WA: {$request->whatsapp}\n";
            $msg .= "🔐 PIN: *{$request->pin}*\n\n";
            
            $msg .= "⚠️ *PENTING:* Simpan ID dan PIN ini. Jika Anda lupa nomor HP, Anda bisa login menggunakan ID Partner.\n\n";

            $msg .= "🎫 *KODE KUPON:* *{$finalCouponCode}*\n";
            $msg .= "🔗 *LINK JUALAN:* {$targetUrl}\n\n";
            $msg .= "Semangat Cuan! 🚀";

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

}