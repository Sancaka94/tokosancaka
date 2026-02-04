<?php

namespace App\Http\Controllers;

use App\Services\FonnteService;
use Illuminate\Http\Request;
use App\Models\Tenant; // Pastikan import model Tenant
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    /**
     * Mengirim notifikasi bantuan ke Admin Sancaka saat tenant disuspend.
     */
    public function hubungiAdmin(Request $request)
    {
        // Data Admin Pusat Sancaka
        $adminPhone = "085745808809";
        $domain = $request->getHost();

        // Mengambil data tenant untuk detail pesan jika diperlukan
        $tenant = DB::table('tenants')->where('subdomain', explode('.', $domain)[0])->first();
        $namaToko = $tenant ? $tenant->name : 'Tidak Diketahui';

        $message = "*PERMINTAAN BANTUAN (SUSPENDED)*\n\n" .
                   "Domain: *$domain*\n" .
                   "Nama Toko: *$namaToko*\n" .
                   "Status: User menekan tombol bantuan di halaman 403.\n\n" .
                   "Mohon segera tindak lanjuti di dashboard pusat CV. Sancaka Karya Hutama.";

        try {
            // Menggunakan layanan Fonnte yang sudah terbukti sukses sebelumnya
            FonnteService::sendMessage($adminPhone, $message);

            // Mencatat aktivitas ke LOG LOG sesuai instruksi
            Log::info("HELP REQUEST: Tenant $domain telah mengirim permintaan bantuan ke admin.");

            return back()->with('success_wa', 'Permintaan bantuan berhasil dikirim! Admin Sancaka akan segera menghubungi Anda.');
        } catch (\Exception $e) {
            // Mencatat error ke log sistem
            Log::error("HELP REQUEST ERROR: Gagal mengirim pesan untuk $domain. Error: " . $e->getMessage());

            return back()->with('error_wa', 'Gagal mengirim pesan otomatis. Silakan hubungi manual di 085745808809.');
        }
    }

    /**
     * Menampilkan halaman akun ditangguhkan (suspended)
     */
    public function suspended()
    {
        // Pastikan file view ini ada di: resources/views/tenant/suspended.blade.php
        return view('tenant.suspended');
    }
}
