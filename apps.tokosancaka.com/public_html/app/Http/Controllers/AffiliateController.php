<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

// Models
use App\Models\Affiliate;
use App\Models\Coupon;
use App\Models\Tenant; // <--- WAJIB ADA

class AffiliateController extends Controller
{
    protected $tenantId;

    public function __construct(Request $request)
    {
        // 1. Deteksi Subdomain untuk mengunci tenant_id
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // Jika tenant tidak ditemukan, default ke ID 1 (atau sesuaikan kebijakan Anda)
        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    /**
     * Dashboard Admin: Filter berdasarkan Tenant
     */
    public function index()
    {
        // Filter Affiliate berdasarkan tenant_id
        $affiliates = Affiliate::where('tenant_id', $this->tenantId)
            ->with(['coupon.orders' => function($query) {
                $query->where('payment_status', 'paid');
            }])->latest()->get();

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

    public function store(Request $request)
{
    // 1. Validasi Input
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'name'     => 'required|string|max:255',
        'whatsapp' => 'required|numeric',
        'pin'      => 'required|numeric|digits:6',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput()
            ->with('error', 'Cek kembali data Anda.');
    }

    // 2. Cek Duplikat WhatsApp HANYA di Tenant yang sedang aktif
    $exists = Affiliate::where('whatsapp', $request->whatsapp)
        ->where('tenant_id', $this->tenantId)
        ->exists();

    if ($exists) {
        return redirect()->back()
            ->with('error', 'Nomor WhatsApp sudah terdaftar di toko ini. Silakan login atau edit data.')
            ->withInput();
    }

    DB::beginTransaction();
    try {
        // 3. Simpan Data Affiliate dengan Tenant ID
        $affiliate = Affiliate::create([
            'tenant_id'           => $this->tenantId,
            'name'                => $request->name,
            'whatsapp'            => $request->whatsapp,
            'address'             => $request->address,
            'bank_name'           => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'pin'                 => Hash::make($request->pin), // Hash PIN untuk keamanan
            'coupon_code'         => 'PENDING',
            'balance'             => 0
        ]);

        // 4. Generate Kupon Unik (Format: SANCAKA-ID-DISKON)
        $discountValue = 30; // Default diskon 30%
        $finalCouponCode = "SANCAKA-" . $affiliate->id . "-" . $discountValue;

        $affiliate->update(['coupon_code' => $finalCouponCode]);

        // 5. Simpan ke Tabel Coupons dengan Tenant ID
        Coupon::create([
            'tenant_id'   => $this->tenantId,
            'code'        => $finalCouponCode,
            'type'        => 'percent',
            'value'       => $discountValue,
            'start_date'  => now(),
            'expiry_date' => now()->addYears(5),
            'description' => 'Partner: ' . $request->name . ' (Tenant ID: ' . $this->tenantId . ')',
            'is_active'   => true
        ]);

        // 6. Susun Pesan WhatsApp (Menggunakan Route Otomatis sesuai Subdomain)
        $targetUrl = route('orders.create', ['coupon' => $finalCouponCode]);

        $msg  = "🎉 *SELAMAT! PENDAFTARAN BERHASIL* 🎉\n\n";
        $msg .= "Halo Kak *{$request->name}*,\n";
        $msg .= "Akun Partner Anda di *" . request()->getHost() . "* sudah aktif.\n\n";

        $msg .= "📋 *DATA AKUN ANDA:*\n";
        $msg .= "🆔 ID Partner: *{$affiliate->id}*\n";
        $msg .= "📱 No. WA: {$request->whatsapp}\n";
        $msg .= "🔐 PIN: *{$request->pin}*\n\n";

        $msg .= "📢 *LINK PROMO ANDA* 📢\n";
        $msg .= "Gunakan link ini untuk disebarkan (Diskon Otomatis):\n";
        $msg .= "👉 {$targetUrl}\n\n";
        $msg .= "Semangat cari cuan, Kak! 🚀";

        // 7. Kirim Notifikasi via Fonnte
        $this->sendFonnte($request->whatsapp, $msg);

        DB::commit();
        return redirect()->back()->with('success', 'Berhasil! ID Partner dan PIN telah dikirim ke WhatsApp Anda.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Gagal Daftar Affiliate: ' . $e->getMessage());
        return redirect()->back()
            ->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage())
            ->withInput();
    }
}

    public function forgotPin(Request $request)
{
    $request->validate([
        'whatsapp' => 'required|numeric',
    ]);

    // KUNCI: Cari user berdasarkan WA DAN Tenant ID
    $affiliate = Affiliate::where('whatsapp', $request->whatsapp)
                          ->where('tenant_id', $this->tenantId)
                          ->first();

    if (!$affiliate) {
        return response()->json([
            'status' => 'error',
            'message' => 'Nomor WhatsApp tidak terdaftar di toko ini.'
        ], 404);
    }

    try {
        $newPin = rand(100000, 999999);
        $affiliate->update([
            'pin' => Hash::make($newPin)
        ]);

        $message = "🔑 *RESET PIN BERHASIL*\n\n";
        $message .= "Halo Kak *{$affiliate->name}*,\n";
        $message .= "PIN akun partner Anda di " . request()->getHost() . " telah direset.\n\n";
        $message .= "PIN BARU: *{$newPin}*\n\n";
        $message .= "Gunakan PIN ini untuk login.";

        $this->sendFonnte($affiliate->whatsapp, $message);

        return response()->json([
            'status' => 'success',
            'message' => 'PIN Baru telah dikirim ke WhatsApp Anda.'
        ]);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Gagal memproses.'], 500);
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
            $affiliate = Affiliate::where('tenant_id', $this->tenantId)
                        ->where(function($q) use ($request) {
                            $q->where('whatsapp', $request->login_key)
                              ->orWhere('id', $request->login_key);
                        })->first();

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
    $affiliates = Affiliate::where('tenant_id', $this->tenantId)
            ->with(['coupon.orders' => function($q) {
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



   public function updateAccountPublic(Request $request)
{
    try {
        $request->validate([
            'id' => 'required',
            'verification_pin' => 'required',
            'name' => 'required',
            'whatsapp' => 'required',
        ]);

        // KUNCI: Cari ID dengan filter Tenant ID
        $affiliate = Affiliate::where('id', $request->id)
                              ->where('tenant_id', $this->tenantId)
                              ->first();

        if (!$affiliate) {
            return redirect()->back()->with('error', 'Data tidak ditemukan atau akses ditolak.');
        }

        // --- Logika Verifikasi PIN ---
        $inputPin = $request->verification_pin;
        $dbPin    = $affiliate->pin;
        $isHashed = \Illuminate\Support\Facades\Hash::info($dbPin)['algoName'] !== 'unknown';
        $isValid  = false;

        if (!$isHashed) {
            if ($dbPin == $inputPin) {
                $isValid = true;
                $affiliate->update(['pin' => Hash::make($inputPin)]);
            }
        } else {
            if (Hash::check($inputPin, $dbPin)) { $isValid = true; }
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

}
