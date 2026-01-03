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
     * Proses Simpan Pendaftaran (LOGIKA UTAMA)
     */
    public function store(Request $request) 
    {
        // 1. Validasi Input
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'whatsapp' => 'required|numeric|unique:affiliates,whatsapp',
            'bank_name' => 'required|string',
            'bank_account_number' => 'required|numeric',
        ], [
            'whatsapp.unique' => 'Nomor WhatsApp ini sudah terdaftar sebagai partner.',
        ]);

        DB::beginTransaction();
        try {
            // 2. Tentukan Besaran Diskon (Contoh: 5%)
            $discountValue = 5; 

            // 3. Simpan Data Afiliasi DULU dengan kode sementara
            // Kita butuh simpan dulu agar Database memberikan ID (Nomor Urut)
            $affiliate = Affiliate::create([
                'name' => $request->name,
                'address' => $request->address,
                'whatsapp' => $request->whatsapp,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'coupon_code' => 'PENDING', // Kode sementara
            ]);

            // 4. Generate Kode Kupon Format: SANCAKA-{ID}-{DISKON}
            // Contoh: Pendaftar ke-12 akan jadi SANCAKA-12-5
            $finalCouponCode = "SANCAKA-" . $affiliate->id . "-" . $discountValue;

            // 5. Update Data Afiliasi dengan Kode yang Benar
            $affiliate->update([
                'coupon_code' => $finalCouponCode
            ]);

            // 6. Buat Data Kupon di Tabel Coupons
            Coupon::create([
                'code' => $finalCouponCode,
                'type' => 'percent', // Tipe persen
                'value' => $discountValue, // Nilai 5
                'start_date' => now(),
                'expiry_date' => now()->addYears(5), 
                'description' => 'Kupon Partner: ' . $request->name . ' (ID: ' . $affiliate->id . ')',
                'is_active' => true
            ]);

            // 7. Generate Link Khusus (Langsung ke Kasir + Auto Kupon)
            // Hasil: https://domain.com/orders/create?coupon=SANCAKA-12-5
            $targetUrl = route('orders.create', ['coupon' => $finalCouponCode]);

            // 8. Susun Pesan WhatsApp
            $message = "Halo Partner *{$request->name}*! 👋\n\n";
            $message .= "Selamat! Anda resmi terdaftar sebagai Partner Afiliasi Sancaka.\n";
            $message .= "No. Registrasi: *#{$affiliate->id}*\n\n";
            
            $message .= "🎫 KODE KUPON ANDA:\n";
            $message .= "*{$finalCouponCode}*\n";
            $message .= "(Diskon {$discountValue}% untuk pelanggan)\n\n";
            
            $message .= "👇 *LINK KHUSUS ANDA (SEBARKAN INI):* 👇\n";
            $message .= $targetUrl . "\n\n";
            
            $message .= "Siapapun yang klik link di atas akan otomatis mendapatkan Diskon, dan Anda mendapatkan Komisi!\n\n";
            $message .= "Semangat Cuan! 🚀";

            // 9. Kirim WhatsApp
            FonnteService::sendMessage($request->whatsapp, $message);

            DB::commit();

            return redirect()->back()->with('success', 'Pendaftaran Berhasil! Kode Kupon: ' . $finalCouponCode . ' telah dikirim ke WhatsApp Anda.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
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
     * API CEK AKUN (AUTO FIX PIN PLAIN TEXT)
     */
    public function checkAccountPublic(Request $request)
    {
        Log::info('>>> START CHECK ACCOUNT PUBLIC <<<');

        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'whatsapp' => 'required',
                'pin' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Data tidak lengkap.'], 400);
            }

            $affiliate = Affiliate::where('whatsapp', $request->whatsapp)->first();

            if (!$affiliate) {
                return response()->json(['status' => 'error', 'message' => 'Nomor WhatsApp tidak ditemukan.'], 404);
            }

            // --- LOGIKA PERBAIKAN ERROR "Not Bcrypt" ---
            
            $inputPin = $request->pin;
            $dbPin    = $affiliate->pin;

            // Cek apakah data di DB adalah Hash Valid atau Angka Biasa?
            $isHashed = \Illuminate\Support\Facades\Hash::info($dbPin)['algoName'] !== 'unknown';

            $isValid = false;

            if (!$isHashed) {
                // KASUS 1: PIN di DB masih Angka Biasa (Penyebab Error Anda)
                if ($dbPin == $inputPin) {
                    $isValid = true;
                    // AUTO-FIX: Langsung update ke Hash biar besok tidak error lagi
                    $affiliate->update(['pin' => Hash::make($inputPin)]);
                    Log::info('AUTO-FIX: Mengubah PIN plain text menjadi Hash untuk ID: ' . $affiliate->id);
                }
            } else {
                // KASUS 2: PIN sudah terenkripsi (Normal)
                if (Hash::check($inputPin, $dbPin)) {
                    $isValid = true;
                }
            }

            if (!$isValid) {
                return response()->json(['status' => 'error', 'message' => 'PIN Keamanan Salah!'], 401);
            }

            return response()->json([
                'status' => 'success',
                'data' => $affiliate
            ]);

        } catch (\Exception $e) {
            Log::error('ERROR checkAccountPublic: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()], 500);
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

}