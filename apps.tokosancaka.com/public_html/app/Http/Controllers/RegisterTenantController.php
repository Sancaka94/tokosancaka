<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\License; // [TAMBAHAN: Import Model License]
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Str; // [TAMBAHAN: Import Str untuk generate kode]
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

        // Validasi tetap sama...
        try {
            $validatedData = $request->validate([
                'owner_name'    => 'required|string|max:255',
                'email'         => 'required|email|max:255',
                'whatsapp'      => 'required|string|min:9|max:15',
                'business_name' => 'required|string|max:255',
                'subdomain'     => 'required|alpha_dash|unique:tenants,subdomain',
                'package'       => 'required|in:trial,monthly,yearly',
                'password'      => 'required|min:8',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // 2. SETUP DATA AWAL
        $now = Carbon::now('Asia/Jakarta');
        $adminPhone = '085745808809';
        $userWa = $this->_normalizeWa($request->whatsapp);

        $prices = [
            'trial'   => 0,
            'monthly' => 100000,
            'yearly'  => 1000000
        ];
        $amount = $prices[$request->package];

        DB::beginTransaction();

        try {
            // 3. KALKULASI MASA AKTIF
            $status = ($request->package == 'trial') ? 'active' : 'inactive';
            $days = ($request->package == 'yearly') ? 365 : ($request->package == 'monthly' ? 30 : 14);
            $expiredAt = $now->copy()->addDays($days);

            // 4. SIMPAN TENANT
            // Simpan subdomain dalam huruf kecil agar konsisten saat dicek Cloudflare
            $tenant = Tenant::create([
                'name'       => $request->business_name,
                'subdomain'  => strtolower($request->subdomain),
                'whatsapp'   => $request->whatsapp,
                'package'    => $request->package,
                'status'     => $status,
                'expired_at' => $expiredAt,
                'created_at' => $now,
            ]);

            // 5. SIMPAN USER
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->owner_name,
                'email'     => $request->email,
                'phone'     => $userWa,
                'password'  => Hash::make($request->password),
                'role'      => 'admin',
                'permissions' => ['dashboard', 'pos', 'products', 'reports', 'settings', 'finance'],
            ]);

            // [TAMBAHAN: Buat Lisensi Pertama Jika Trial]
            $licenseCode = 'TRIAL-' . strtoupper(Str::random(8)); // Simpan di variabel agar bisa dipakai di pesan WA
            if ($request->package == 'trial') {
                License::create([
                    'license_code'  => $licenseCode,
                    'tenant_id'     => $tenant->id,
                    'package_type'  => 'trial',
                    'max_devices'   => 1,
                    'max_ips'       => 1,
                    'duration_days' => $days,
                    'status'        => 'used', // Langsung 'used' agar user tidak perlu aktivasi manual lagi di dalam app
                    'used_at'       => $now,
                    'expires_at'    => $expiredAt,
                ]);
            }

            DB::commit();

            // 6. NOTIFIKASI WA UNTUK ADMIN (SERVER)
            // [PERBAIKAN] Gunakan Domain Utama untuk notifikasi admin agar bisa dicek manual
            $msgAdmin = "🔔 *ADA PENDAFTAR BARU!*\n\n";
            $msgAdmin .= "👤 Nama: {$request->owner_name}\n";
            $msgAdmin .= "🏪 Toko: {$request->business_name}\n";
            $msgAdmin .= "🌐 Subdomain: {$tenant->subdomain}.tokosancaka.com\n"; // URL ini akan ditangkap Cloudflare Worker nanti
            $this->_sendFonnte($adminPhone, $msgAdmin);

            // 7. PROSES PEMBAYARAN DOKU (Jika bukan trial)
            if ($amount > 0) {
                $invoiceNumber = 'SEWA-' . strtoupper($tenant->subdomain) . '-' . $now->format('ymdHis');
                $dokuData = [
                    'name'  => $request->owner_name,
                    'email' => $request->email,
                    'phone' => $userWa
                ];

                $paymentUrl = $dokuService->createPayment($invoiceNumber, $amount, $dokuData);

                // Kirim WA Tagihan...
                $msgUser = "Halo Kak *{$request->owner_name}* 👋,\n\n";
                $msgUser .= "Link Pembayaran: \n" . $paymentUrl . "\n";
                $this->_sendFonnte($userWa, $msgUser);

                // [PERBAIKAN] Gunakan away() untuk memastikan user keluar dari aplikasi kita menuju DOKU
                return redirect()->away($paymentUrl);
            }

          // 8. FINISH (TRIAL)
            if ($request->package == 'trial') {
                $targetUrl = 'https://' . $tenant->subdomain . '.tokosancaka.com/login';

                $msgTrial = "Selamat Ya Kak *{$request->owner_name}*! 🎉\n\n";
                $msgTrial .= "Akun Trial 14 hari Anda sudah aktif.\n";
                $msgTrial .= "----------------------------------\n";
                $msgTrial .= "🔑 *KODE AKTIVASI:* `{$licenseCode}`\n"; // Menampilkan kode ke user
                $msgTrial .= "🌐 *LINK LOGIN:* {$targetUrl}\n";
                $msgTrial .= "----------------------------------\n\n";
                $msgTrial .= "Gunakan kode di atas jika aplikasi meminta aktivasi saat pertama kali login.";
                
                $this->_sendFonnte($userWa, $msgTrial);

                Log::info("LOG LOG: Trial sukses mengirim kode {$licenseCode} ke {$userWa}");

                return redirect()->away($targetUrl);
                }

            } catch (\Exception $e) {
            
            DB::rollBack();
            Log::error("LOG LOG: FAILED REGISTER: " . $e->getMessage());
            return back()->with('error', 'Gagal mendaftar: ' . $e->getMessage());
        }
    }

   /**
     * Webhook DOKU
     */
    public function handleDokuWebhook(Request $request)
    {
        // Logika Webhook sudah aman karena tidak bergantung URL, tapi bergantung Invoice Number
        // Bagian ini tidak perlu diubah, hanya pastikan URL di WA User menggunakan HTTPS

        $content = $request->getContent();
        $data = json_decode($content, true);
        $invoice = $data['order']['invoice_number'] ?? null;
        $status = $data['transaction']['status'] ?? null;

        if ($invoice && str_contains($invoice, 'SEWA-')) {
            if (strtoupper($status) === 'SUCCESS') {
                $parts = explode('-', $invoice);
                $subdomain = strtolower($parts[1]);

                $tenant = Tenant::where('subdomain', $subdomain)->first();

                if ($tenant && $tenant->status !== 'active') {
                    
                    // [TAMBAHAN: Kalkulasi expired berdasarkan paket untuk Lisensi Baru]
                    $now = Carbon::now('Asia/Jakarta');
                    $days = ($tenant->package == 'yearly') ? 365 : 30;
                    $newExpiredAt = $now->copy()->addDays($days);
                    
                    $tenant->update([
                        'status' => 'active',
                        'expired_at' => $newExpiredAt // [TAMBAHAN: Update masa aktif tenant]
                    ]);

                    // [TAMBAHAN: Generate Lisensi Aktif setelah bayar lunas]
                    // Anda bisa menyesuaikan jumlah device/ip sesuai produk DOKU nanti
                    License::create([
                        'license_code'  => 'PRO-' . strtoupper(Str::random(10)),
                        'tenant_id'     => $tenant->id,
                        'package_type'  => '1_device_1_ip', // Default
                        'max_devices'   => 1,
                        'max_ips'       => 1,
                        'duration_days' => $days,
                        'status'        => 'used',
                        'used_at'       => $now,
                        'expires_at'    => $newExpiredAt,
                    ]);

                    $userPhone = $this->_normalizeWa($tenant->whatsapp);

                    // [PERBAIKAN] Pastikan Link Login di WA menggunakan HTTPS
                    $msgUser = "💰 *PEMBAYARAN BERHASIL*\n\n" .
                               "Status: *ACTIVE* ✅\n" .
                               "Link Login: https://{$subdomain}.tokosancaka.com/login\n\n"; // WAJIB HTTPS

                    if (!empty($userPhone)) {
                        $this->_sendFonnte($userPhone, $msgUser);
                    }
                }
            }
        }
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

    // ==============================================================================
    // FUNGSI MANAJEMEN TENANT (UNTUK ADMIN PUSAT)
    // ==============================================================================

    /**
     * Menampilkan detail lengkap satu tenant
     */
    public function show($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        // Asumsi Anda juga ingin melihat daftar user dari tenant ini
        $users = User::where('tenant_id', $tenant->id)->get();
        
        // Asumsi Anda ingin melihat riwayat lisensi tenant ini
        $licenses = License::where('tenant_id', $tenant->id)->get();

        return view('admin.tenant-show', compact('tenant', 'users', 'licenses'));
    }

    /**
     * Menampilkan form edit tenant
     */
    public function edit($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        return view('admin.tenant-edit', compact('tenant'));
    }

    /**
     * Memproses update data tenant
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        // Validasi input
        $request->validate([
            'name'       => 'required|string|max:255',
            'whatsapp'   => 'required|string|max:20',
            'package'    => 'required|in:trial,monthly,yearly',
            'status'     => 'required|in:active,inactive,suspended',
            'expired_at' => 'required|date',
        ]);

        try {
            // Update data tenant
            $tenant->update([
                'name'       => $request->name,
                'whatsapp'   => $request->whatsapp,
                'package'    => $request->package,
                'status'     => $request->status,
                'expired_at' => $request->expired_at,
            ]);

            Log::info("LOG LOG: Tenant {$tenant->name} diupdate oleh Admin Pusat.");

            return redirect()->route('tenants.index')->with('success', 'Data tenant berhasil diperbarui!');
            
        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal update tenant: " . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus tenant beserta data terkait
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $tenant = Tenant::findOrFail($id);
            $tenantName = $tenant->name;

            // Hapus semua User yang terkait dengan tenant ini
            User::where('tenant_id', $tenant->id)->delete();
            
            // Hapus semua Lisensi yang terkait
            License::where('tenant_id', $tenant->id)->delete();
            
            // Hapus data tenant utama
            $tenant->delete();

            DB::commit();

            Log::info("LOG LOG: Tenant {$tenantName} dan semua datanya berhasil dihapus permanen oleh Admin.");

            return redirect()->route('tenants.index')->with('success', "Tenant {$tenantName} berhasil dihapus permanen!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG LOG: Gagal menghapus tenant: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }
    
}