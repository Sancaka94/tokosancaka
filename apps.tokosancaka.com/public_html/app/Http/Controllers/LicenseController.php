<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\License;
use App\Models\TenantDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LicenseController extends Controller
{
    // Menampilkan halaman input form lisensi
    public function showRedeemForm()
    {
        // Pastikan Anda membuat file view ini nanti: resources/views/tenant/redeem-license.blade.php
        return view('tenant.redeem-license'); 
    }

    // Memproses kode lisensi yang dimasukkan user
    public function redeem(Request $request)
    {
        $request->validate([
            'license_code' => 'required|string'
        ]);

        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return back()->with('error', 'Data Tenant tidak ditemukan.');
        }

        // Cari lisensi yang statusnya masih 'available'
        $license = License::where('license_code', $request->license_code)
                          ->where('status', 'available')
                          ->first();

        if (!$license) {
            return back()->with('error', 'Kode lisensi tidak valid atau sudah pernah digunakan.');
        }

        // 1. Update status lisensi menjadi 'used'
        $now = Carbon::now('Asia/Jakarta');
        $expiresAt = $now->copy()->addDays($license->duration_days);

        $license->update([
            'tenant_id' => $tenant->id,
            'status' => 'used',
            'used_at' => $now,
            'expires_at' => $expiresAt,
        ]);

        // 2. Update masa aktif di tabel Tenant utama
        $tenant->update([
            'status' => 'active',
            'expired_at' => $expiresAt,
            // Opsional: Anda bisa update paket tenant di sini jika ganti paket
            'package' => $license->package_type == '3_device_3_ip' ? 'premium' : 'standard', 
        ]);

        // 3. PENTING: Hapus history device/IP lama
        // Karena user beli lisensi baru, kita "reset" device-nya agar dia bisa login
        // menggunakan IP/Device baru sesuai kuota lisensi yang baru.
        TenantDevice::where('tenant_id', $tenant->id)->delete();

        Log::info("LOG LOG: Tenant {$tenant->name} berhasil redeem lisensi {$license->license_code}. Device direset.");

        // Redirect ke dashboard POS
        return redirect()->route('dashboard')->with('success', 'Lisensi berhasil diperbarui! Silakan gunakan aplikasi.');
    }
}