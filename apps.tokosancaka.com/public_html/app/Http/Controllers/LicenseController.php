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

    /**
     * Memproses form redeem saat disubmit
     */
    public function processRedeem(Request $request)
    {
        // 1. Validasi input form
        $request->validate([
            'license_code' => 'required|string'
        ]);

        // 2. Cari kode di database
        $license = License::where('code', $request->license_code)->first();

        // 3. Pengecekan jika kode tidak ada
        if (!$license) {
            return redirect()->back()->with('error', 'Kode lisensi tidak valid atau tidak ditemukan.');
        }

        // 4. Pengecekan jika kode sudah dipakai
        if ($license->status !== 'available') {
            return redirect()->back()->with('error', 'Kode lisensi ini sudah pernah digunakan.');
        }

        // 5. Jika sukses, ubah status menjadi terpakai
        // Nanti Anda bisa sesuaikan 'used_by_tenant_id' dengan ID tenant/user yang sedang login
        $license->update([
            'status' => 'used',
            'used_by_tenant_id' => auth()->user()->id, // <-- Hilangkan comment ini nanti jika relasi tabel sudah siap
        ]);

        return redirect()->back()->with('success', 'Lisensi berhasil di-redeem dan fitur telah diaktifkan!');

        // LOG LOG - Safe block
    }
}
