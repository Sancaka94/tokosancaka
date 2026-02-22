<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // BENAR ✅
use App\Http\Middleware\EnforceLicenseLimits; // Pastikan middleware ini sudah dibuat
use Illuminate\Support\Facades\Log; // Untuk logging
use Illuminate\Support\Str;
use App\Models\License; // Pastikan nanti kita buat Model ini
use App\Models\Tenant; // Model Tenant untuk relasi jika diperlukan
use App\Models\User; // Model User untuk relasi jika diperlukan

class LicenseController extends Controller
{
    /**
     * Menampilkan halaman daftar lisensi untuk superadmin
     */
    public function index()
    {
        // Mengambil data lisensi dari database, urutkan dari yang terbaru
        $licenses = License::latest()->paginate(10);

        return view('superadmin.license.index', compact('licenses'));
    }

    /**
     * Memproses generate kode lisensi baru dengan format XXXX-XXXX-XXXX-XXXX
     */
    public function generate()
    {
        // Membuat string acak kapital 4 blok
        $code = strtoupper(Str::random(4)) . '-' .
                strtoupper(Str::random(4)) . '-' .
                strtoupper(Str::random(4)) . '-' .
                strtoupper(Str::random(4));

        // Simpan ke database
        License::create([
             'license_code' => $code, // <--- UBAH 'code' MENJADI 'license_code'
             'tenant_id' => null, // <--- Bisa diisi jika ingin langsung kaitkan dengan tenant tertentu
             'package_type' => 'basic', // <--- Contoh, bisa disesuaikan dengan paket yang Anda miliki
             'max_devices' => 5, // <--- Contoh, batas maksimal perangkat yang bisa menggunakan lisensi ini
             'max_ips' => 10, // <--- Contoh, batas maksimal IP yang bisa menggunakan lisensi ini
             'duration_days' => 30, // <--- Contoh, masa aktif lisensi dalam hari
             'status' => 'available',
        ]);

        return redirect()->back()->with('success', 'Kode lisensi baru berhasil di-generate!');
    }

    /**
     * Menghapus lisensi yang tidak diperlukan
     */
    public function destroy($id)
    {
        $license = License::findOrFail($id);
        $license->delete();

        return redirect()->back()->with('success', 'Kode lisensi berhasil dihapus.');
    }

    /**
     * Menampilkan halaman form redeem
     */
    public function showRedeemForm()
    {
        return view('superadmin.license.redeem');
    }

   public function processRedeem(Request $request)
    {
        Log::info("========================================");
        Log::info("🚀 MULAI PROSES REDEEM LISENSI");
        Log::info("Input IP: " . $request->ip());
        Log::info("Raw Input - Subdomain: {$request->target_subdomain}, Kode: {$request->license_code}");

        // 1. Validasi input form saja
        $request->validate([
            'license_code' => 'required|string',
            'target_subdomain' => 'required|string'
        ]);

        // Bersihkan spasi pada kode
        $cleanLicenseCode = strtoupper(str_replace(' ', '', $request->license_code));
        Log::info("Kode setelah dibersihkan: {$cleanLicenseCode}");

        try {
            // 2. Cari target tokonya berdasarkan Subdomain
            Log::info("🔍 Mencari tenant dengan subdomain: {$request->target_subdomain}");
            $tenant = DB::table('tenants')->where('subdomain', $request->target_subdomain)->first();

            if (!$tenant) {
                Log::warning("⚠️ Validasi Gagal: Tenant dengan subdomain '{$request->target_subdomain}' tidak ditemukan.");
                return redirect()->back()->with('error', 'Validasi Gagal: Toko dengan subdomain tersebut tidak ditemukan.');
            }
            Log::info("✅ Tenant ditemukan. ID Tenant: {$tenant->id}, Status saat ini: {$tenant->status}");

            // 3. VALIDASI UTAMA: Pastikan Kode Lisensi ada DAN memang milik ID Toko tersebut
            Log::info("🔍 Mencari lisensi '{$cleanLicenseCode}' untuk Tenant ID: {$tenant->id}");
            $license = License::withoutGlobalScopes()
                          ->where('license_code', $cleanLicenseCode)
                          ->where('tenant_id', $tenant->id)
                          ->first();

            if (!$license) {
                Log::warning("⚠️ Validasi Gagal: Lisensi '{$cleanLicenseCode}' tidak ditemukan atau bukan milik Tenant ID {$tenant->id}.");
                return redirect()->back()->with('error', 'Validasi Gagal: Kode lisensi tidak valid atau bukan diperuntukkan bagi subdomain Anda.');
            }
            Log::info("✅ Lisensi ditemukan. ID Lisensi: {$license->id}, Package: {$license->package_type}, Status: {$license->status}");

            // 4. Pengecekan status lisensi (apakah sudah pernah dipakai?)
            if ($license->status !== 'available') {
                Log::warning("⚠️ Validasi Gagal: Lisensi '{$cleanLicenseCode}' sudah berstatus '{$license->status}'.");
                return redirect()->back()->with('error', 'Kode lisensi ini sudah pernah digunakan.');
            }

            // --- 5. PROSES AKTIVASI ---
            Log::info("⏳ Memulai proses update data (Aktivasi)...");

            $durationDays = $license->duration_days ?? 30;
            $currentExpired = $tenant->expired_at ? \Carbon\Carbon::parse($tenant->expired_at) : now();

            if ($currentExpired->isPast()) {
                Log::info("ℹ️ Masa aktif sebelumnya sudah habis. Dihitung dari hari ini.");
                $currentExpired = now();
            } else {
                Log::info("ℹ️ Sisa masa aktif masih ada. Akan diakumulasi.");
            }

            $newExpiredDate = $currentExpired->addDays($durationDays)->timezone('Asia/Jakarta');
            Log::info("📅 Tanggal expired baru: {$newExpiredDate->format('Y-m-d H:i:s')} (+{$durationDays} hari)");

            // Update tabel tenants
            DB::table('tenants')->where('id', $tenant->id)->update([
                'status' => 'active',
                'package' => $license->package_type ?? 'monthly',
                'expired_at' => $newExpiredDate,
                'updated_at' => now()
            ]);
            Log::info("✅ Tabel 'tenants' berhasil diupdate.");

            // Update tabel licenses
            $license->update([
                'status' => 'used',
                'used_by_tenant_id' => $tenant->id,
                'used_at' => now(),
                'expires_at' => $newExpiredDate
            ]);
            Log::info("✅ Tabel 'licenses' berhasil diupdate (status menjadi 'used').");

            Log::info("🎉 PROSES REDEEM SELESAI & BERHASIL UNTUK SUBDOMAIN: {$request->target_subdomain}");
            Log::info("========================================");

            return redirect()->back()->with('success', 'Aktivasi Berhasil! Layanan SancakaPOS Anda telah diperpanjang.');

        } catch (\Exception $e) {
            // Jika ada error syntax atau database mati, akan tercatat di sini tanpa merusak aplikasi
            Log::error("❌ CRITICAL ERROR PADA PROSES REDEEM LISENSI:");
            Log::error($e->getMessage());
            Log::error($e->getLine() . ' - ' . $e->getFile());
            Log::info("========================================");

            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat memproses aktivasi. Silakan hubungi admin.');
        }
    }
}
