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
        // 1. Validasi input form
        $request->validate([
            'license_code' => 'required|string',
            'target_subdomain' => 'required|string',
            // 'user_id' => 'required|integer' // Pastikan user_id juga divalidasi
        ]);

        // Bersihkan kode dari spasi
        $cleanLicenseCode = strtoupper(str_replace(' ', '', $request->license_code));

        // 2. VALIDASI LAPIS 1: Cek Subdomain (Cari Tenant)
        $tenant = DB::table('tenants')->where('subdomain', $request->target_subdomain)->first();

        if (!$tenant) {
            return redirect()->back()->with('error', 'Validasi Gagal: Toko dengan subdomain tersebut tidak ditemukan.');
        }

       $user = DB::table('users')->where('id', $userId)->first();

        // --- TAMBAHKAN BARIS INI SEMENTARA ---
        dd([
            '1_NAMA_USER_YANG_LOGIN' => $user->name ?? 'Tidak ada nama',
            '2_TENANT_ID_MILIK_USER' => $user->tenant_id ?? 'KOSONG/NULL',
            '3_ID_TOKO_OPERATOR' => $tenant->id
        ]);
        // ------------------------------------

        if (!$userId) {
            return redirect()->back()->with('error', 'Validasi Gagal: Anda harus login untuk melakukan aktivasi.');
        }

        // Opsional tapi penting: Pastikan User ini benar-benar terdaftar di Tenant (Subdomain) tersebut
        // Sesuaikan nama kolom 'tenant_id' di tabel users Anda
        $user = DB::table('users')->where('id', $userId)->first();
        if ($user && $user->tenant_id !== $tenant->id) {
            return redirect()->back()->with('error', 'Validasi Gagal: Anda tidak memiliki otoritas atas subdomain ini.');
        }

        // 4. VALIDASI LAPIS 3: Cek Lisensi (Kode + Tenant + User)
        $licenseQuery = License::where('license_code', $cleanLicenseCode)
                               ->where('tenant_id', $tenant->id);

        // Jika Anda SUDAH menambahkan kolom 'user_id' di tabel 'licenses',
        // hapus tanda // pada baris di bawah ini:
        $licenseQuery->where('user_id', $userId);

        $license = $licenseQuery->first();

        if (!$license) {
            return redirect()->back()->with('error', 'Validasi Gagal: Lisensi tidak ditemukan atau tidak diperuntukkan bagi Subdomain/User Anda.');
        }

        // 5. Pengecekan status (apakah sudah dipakai?)
        if ($license->status !== 'available') {
            return redirect()->back()->with('error', 'Kode lisensi ini sudah pernah digunakan.');
        }

        // --- PROSES AKTIVASI (Jika semua validasi lolos) ---

        $durationDays = $license->duration_days ?? 30;
        $currentExpired = $tenant->expired_at ? \Carbon\Carbon::parse($tenant->expired_at) : now();
        if ($currentExpired->isPast()) {
            $currentExpired = now();
        }
        $newExpiredDate = $currentExpired->addDays($durationDays)->timezone('Asia/Jakarta');

        // Update tabel tenants
        DB::table('tenants')->where('id', $tenant->id)->update([
            'status' => 'active',
            'package' => $license->package_type ?? 'monthly',
            'expired_at' => $newExpiredDate,
            'updated_at' => now()
        ]);

        // Update tabel licenses
        $license->update([
            'status' => 'used',
            'used_by_tenant_id' => $tenant->id,
            'used_at' => now(),
            'expires_at' => $newExpiredDate
        ]);

        return redirect()->back()->with('success', 'Aktivasi Berhasil! Lisensi cocok dengan User dan Subdomain Anda.');
    }
}
