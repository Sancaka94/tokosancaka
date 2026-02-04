<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Services\DokuJokulService;

class RegisterTenantController extends Controller
{
    public function showForm()
    {
        return view('daftar-pos');
    }

    /**
     * Menangani pendaftaran Tenant Baru (SaaS POS)
     */
    public function register(Request $request, DokuJokulService $dokuService)
    {
        Log::info("================ START REGISTER SYSTEM ================");
        Log::info("LOG LOG: Memulai alur pendaftaran tenant baru.");

        try {
            // Pindahkan validasi ke dalam try-catch agar error terekam LOG
            $validatedData = $request->validate([
                'owner_name'    => 'required|string|max:255',
                'email'         => 'required|email|max:255',
                'whatsapp'      => 'required|string|min:9|max:15',
                'business_name' => 'required|string|max:255',
                'subdomain'     => 'required|alpha_dash|unique:tenants,subdomain',
                'package'       => 'required|in:trial,monthly,yearly',
                'password'      => 'required|min:8',
            ]);

            Log::info("LOG LOG: Validasi Berhasil.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("LOG LOG: VALIDASI GAGAL! Pesan: " . json_encode($e->errors()));
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error("LOG LOG: ERROR TAK TERDUGA: " . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }

        // 2. SETUP DATA AWAL (Waktu & Harga)
        $now = Carbon::now('Asia/Jakarta');
        $adminPhone = '085745808809'; // Nomor Server Bapak
        $userWa = $this->_normalizeWa($request->whatsapp);

        $prices = [
            'trial'   => 0,
            'monthly' => 100000,
            'yearly'  => 1000000
        ];
        $amount = $prices[$request->package];

        DB::beginTransaction();
        Log::info("LOG LOG: Database Transaction Started.");

        try {
            // 3. KALKULASI MASA AKTIF
            $status = ($request->package == 'trial') ? 'active' : 'inactive';
            $days = ($request->package == 'yearly') ? 365 : ($request->package == 'monthly' ? 30 : 14);
            $expiredAt = $now->copy()->addDays($days);

            // 4. SIMPAN TENANT (USAHA)
            $tenant = Tenant::create([
                'name'       => $request->business_name,
                'subdomain'  => strtolower($request->subdomain),
                'whatsapp'   => $request->whatsapp,
                'package'    => $request->package,
                'status'     => $status,
                'expired_at' => $expiredAt,
                'created_at' => $now,
            ]);
            Log::info("LOG LOG: Tenant berhasil dibuat: {$tenant->subdomain}");

            // 5. SIMPAN USER (ADMIN UTAMA TOKO)
            // Default Role langsung 'admin' agar bisa akses dashboard
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->owner_name,
                'email'     => $request->email,
                'phone'     => $userWa,
                'password'  => Hash::make($request->password),
                'role'      => 'admin', // <--- SUDAH DIGANTI KE ADMIN
                // Berikan akses penuh ke semua fitur karena dia boss tokonya
                'permissions' => ['dashboard', 'pos', 'products', 'reports', 'settings', 'finance'],
            ]);
            Log::info("LOG LOG: User Admin Toko berhasil dibuat: {$user->email}");

            DB::commit();
            Log::info("LOG LOG: DB Transaction Committed.");

            // 6. NOTIFIKASI WA UNTUK ADMIN (SERVER)
            $msgAdmin = "🔔 *ADA PENDAFTAR BARU!*\n\n";
            $msgAdmin .= "👤 Nama: {$request->owner_name}\n";
            $msgAdmin .= "🏪 Toko: {$request->business_name}\n";
            $msgAdmin .= "📦 Paket: " . strtoupper($request->package) . "\n";
            $msgAdmin .= "📱 WA: {$userWa}\n";
            $msgAdmin .= "🌐 Subdomain: {$tenant->subdomain}.tokosancaka.com\n";
            $msgAdmin .= "📅 Waktu: " . $now->format('d/m/Y H:i') . " WIB";
            $this->_sendFonnte($adminPhone, $msgAdmin);

            // 7. PROSES PEMBAYARAN DOKU (Jika bukan trial)
            if ($amount > 0) {
                Log::info("LOG LOG: Memproses integrasi DOKU untuk paket berbayar.");

                $invoiceNumber = 'SEWA-' . strtoupper($tenant->subdomain) . '-' . $now->format('ymdHis');
                $dokuData = [
                    'name'  => $request->owner_name,
                    'email' => $request->email,
                    'phone' => $userWa
                ];

                $paymentUrl = $dokuService->createPayment($invoiceNumber, $amount, $dokuData);

                if (!$paymentUrl) throw new \Exception("Gagal mendapatkan URL dari DOKU.");

                // Notifikasi WA ke User (Tagihan)
                $msgUser = "Halo Kak *{$request->owner_name}* 👋,\n\n";
                $msgUser .= "Terima kasih telah mendaftar di *Sancaka POS*.\n";
                $msgUser .= "Untuk mengaktifkan paket *" . strtoupper($request->package) . "*, silakan selesaikan pembayaran berikut:\n\n";
                $msgUser .= "💰 *Total: Rp " . number_format($amount, 0, ',', '.') . "*\n";
                $msgUser .= "🔗 *Link Pembayaran:* \n" . $paymentUrl . "\n\n";
                $msgUser .= "Akun otomatis aktif setelah pembayaran sukses.";
                $this->_sendFonnte($userWa, $msgUser);

                Log::info("LOG LOG: Redirect ke DOKU: {$paymentUrl}");
                return redirect()->away($paymentUrl);
            }

            // 8. FINISH (TRIAL)
            $targetUrl = 'http://' . $tenant->subdomain . '.tokosancaka.com/login';

            // Notifikasi WA Trial
            $msgTrial = "Selamat Kak *{$request->owner_name}*! 🎉\n\n";
            $msgTrial .= "Akun *Trial 14 Hari* Anda di Sancaka POS sudah aktif.\n";
            $msgTrial .= "Silakan akses dashboard Anda di:\n" . $targetUrl;
            $this->_sendFonnte($userWa, $msgTrial);

            Log::info("LOG LOG: Trial sukses. Redirect ke subdomain.");
            return redirect()->away($targetUrl)->with('success', 'Pendaftaran Berhasil! Akun Trial Aktif.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG LOG: FAILED REGISTER: " . $e->getMessage());
            return back()->with('error', 'Gagal mendaftar: ' . $e->getMessage());
        }
    }

   /**
     * Webhook DOKU untuk aktivasi otomatis
     */
    public function handleDokuWebhook(Request $request)
    {
        $content = $request->getContent();
        Log::info("LOG LOG: [WEBHOOK SEWA] Data Masuk: " . $content);

        $data = json_decode($content, true);
        $invoice = $data['order']['invoice_number'] ?? null;
        $status = $data['transaction']['status'] ?? null;

        if ($invoice && str_contains($invoice, 'SEWA-')) {
            if (strtoupper($status) === 'SUCCESS') {
                $parts = explode('-', $invoice);
                $subdomain = strtolower($parts[1]);

                // Mencari tenant berdasarkan subdomain
                $tenant = Tenant::where('subdomain', $subdomain)->first();

                if ($tenant && $tenant->status !== 'active') {
                    // 1. Update Status jadi Active
                    $tenant->update(['status' => 'active']);
                    Log::info("LOG LOG: Akun Tenant {$subdomain} AKTIF via Webhook.");

                    // 2. Normalisasi Nomor WhatsApp Admin & User
                    $adminPhone = $this->_normalizeWa('085745808809'); // Nomor Bapak
                    $userPhone = $this->_normalizeWa($tenant->whatsapp);

                    // 3. Susun Pesan untuk User
                    $msgUser = "💰 *PEMBAYARAN SEWA BERHASIL*\n\n" .
                               "Halo Owner *{$subdomain}*,\n" .
                               "Terima kasih, pembayaran sewa aplikasi percetakan telah kami terima.\n\n" .
                               "Status: *ACTIVE* ✅\n" .
                               "Link Login: https://{$subdomain}.tokosancaka.com/login\n\n" .
                               "_Silakan login menggunakan email dan password yang Anda daftarkan._";

                    // 4. Kirim WA ke User & Admin
                    if (!empty($userPhone)) {
                        $this->_sendFonnte($userPhone, $msgUser);
                    }

                    $this->_sendFonnte($adminPhone, "🔔 *INFO ADMIN*: Akun Tenant *{$subdomain}* baru saja AKTIF otomatis via DOKU.");
                }
            }
        }

        // Response standar DOKU Jokul
        return response()->json(['responseCode' => '2005600', 'responseMessage' => 'Success']);
    }

    /**
     * Helper: Kirim Pesan via Fonnte
     */
    private function _sendFonnte($target, $message)
    {
        $token = env('FONNTE_API_KEY') ?? env('FONNTE_KEY');
        if (!$token) {
            Log::warning("LOG LOG: Fonnte Token Kosong!");
            return;
        }

        try {
            // Gunakan nomor yang sudah dinormalisasi
            $response = \Illuminate\Support\Facades\Http::withHeaders(['Authorization' => $token])
                ->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);
            Log::info("LOG LOG: Fonnte Sent to {$target}. Respon: " . $response->body());
        } catch (\Exception $e) {
            Log::error("LOG LOG: Fonnte Error: " . $e->getMessage());
        }
    }

    /**
     * Helper: Normalisasi nomor ke format 08 (Sesuai spek Fonnte Bapak)
     */
    private function _normalizeWa($phone)
    {
        if (!$phone) return null;

        // 1. Hapus semua karakter yang bukan angka
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // 2. Jika diawali '62', ubah menjadi '0'
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }
        // 3. Jika diawali '8' (langsung angka 8), tambahkan '0' di depan
        elseif (str_starts_with($phone, '8')) {
            $phone = '0' . $phone;
        }

        // Pastikan hasil akhirnya diawali dengan '08'
        return $phone;
    }

    public function listTenants(Request $request)
    {
        // 1. HAPUS 'with('domains')'. Cukup Tenant::query()
        $query = Tenant::query(); 

        // 2. Fitur Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")       // Nama Toko
                  ->orWhere('subdomain', 'like', "%{$search}%") // Subdomain (Kolom biasa)
                  ->orWhere('whatsapp', 'like', "%{$search}%")  // WA
                  ->orWhere('package', 'like', "%{$search}%");  // Paket
            });
        }

        // 3. Ambil data dengan Pagination
        $tenants = $query->latest()->paginate(10);

        // 4. Return ke View
        return view('admin.tenant-list', [
            'tenants' => $tenants,
            'total_tenants' => Tenant::count()
        ]);
    }
}
