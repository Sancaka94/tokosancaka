<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use illuminate\Support\Facades\DB;
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
    $request->validate([
        'license_code' => 'required|string',
        'target_subdomain' => 'required|string'
    ]);

    // 1. Bersihkan spasi
    $cleanLicenseCode = strtoupper(str_replace(' ', '', $request->license_code));

    // ==== TAMBAHKAN BLOK KODE INI SEMENTARA ====
    // Ini akan menghentikan proses dan menampilkan isi database ke layar
    $semuaKodeDiDatabase = \App\Models\License::pluck('license_code')->toArray();

    dd([
        '1_KODE_YANG_DITANGKAP_DARI_FORM' => $cleanLicenseCode,
        '2_APAKAH_KODE_ADA_DI_DB?' => in_array($cleanLicenseCode, $semuaKodeDiDatabase) ? 'YA, ADA!' : 'TIDAK ADA!',
        '3_ISI_TABEL_LICENSES_MENURUT_LARAVEL' => $semuaKodeDiDatabase
    ]);
    // ===========================================

        // 3. Pengecekan jika kode tidak ada
        $license = License::where('license_code', $cleanLicenseCode)->first();
        if (!$license) {
            return redirect()->back()->with('error', 'Kode lisensi tidak valid atau tidak ditemukan.');
        }

        // 4. Pengecekan jika kode sudah dipakai
        if ($license->status !== 'available') {
            return redirect()->back()->with('error', 'Kode lisensi ini sudah pernah digunakan.');
        }

        // 5. Cari data Tenant berdasarkan subdomain (Gunakan Default DB)
        $tenant = DB::table('tenants')->where('subdomain', $request->target_subdomain)->first();

        if (!$tenant) {
            return redirect()->back()->with('error', 'Toko dengan subdomain tersebut tidak ditemukan.');
        }

        // 6. Hitung perpanjangan masa aktif
        $durationDays = $license->duration_days ?? 30; // Default 30 hari

        $currentExpired = $tenant->expired_at ? \Carbon\Carbon::parse($tenant->expired_at) : now();
        if ($currentExpired->isPast()) {
            $currentExpired = now();
        }

        $newExpiredDate = $currentExpired->addDays($durationDays)->timezone('Asia/Jakarta');

        // 7. Update status & expired_at di tabel tenants
        DB::table('tenants')->where('id', $tenant->id)->update([
            'status' => 'active',
            'package' => $license->package_type ?? 'monthly',
            'expired_at' => $newExpiredDate,
            'updated_at' => now()
        ]);

        // 8. PERBAIKAN: Ubah status lisensi menjadi terpakai & ISI SEMUA KOLOM NULL
        $license->update([
            'status' => 'used',
            'used_by_tenant_id' => $tenant->id, // Mengganti NULL menjadi ID Toko yang memakai
            'used_at' => now(),                 // Mengganti NULL menjadi waktu saat ini
            'expires_at' => $newExpiredDate     // <--- TAMBAHKAN INI: Mengganti NULL menjadi tanggal expired
        ]);

        return redirect()->back()->with('success', 'Lisensi berhasil di-redeem dan fitur telah diaktifkan!');
    }
}
