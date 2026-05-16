<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class SuspendController extends Controller
{
    protected $tenantId;

    public function __construct(Request $request)
    {
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    public function index(Request $request)
    {
        // 1. AMBIL TENANT BERDASARKAN SUBDOMAIN
        $host = $request->getHost(); 
        $subdomain = explode('.', $host)[0]; 

        $tenant = DB::table('tenants')->where('subdomain', $subdomain)->first();

        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        // 2. CEK LISENSI TERAKHIR DARI TABEL LICENSES
        // Mencari lisensi yang statusnya 'used' dan tanggal expired paling jauh
        $latestLicense = DB::table('licenses')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'used')
            ->orderBy('expires_at', 'desc')
            ->first();

        // 3. TENTUKAN TANGGAL EXPIRED (Prioritas: Tabel Licenses -> Tabel Tenants -> Waktu Saat Ini)
        if ($latestLicense && $latestLicense->expires_at) {
            $expiredDate = Carbon::parse($latestLicense->expires_at)->timezone('Asia/Jakarta');
        } elseif ($tenant->expired_at) {
            $expiredDate = Carbon::parse($tenant->expired_at)->timezone('Asia/Jakarta');
        } else {
            $expiredDate = Carbon::now()->timezone('Asia/Jakarta');
        }

        // 4. CEK LOGIC REDIRECT
        $isExpired = now()->timezone('Asia/Jakarta')->gt($expiredDate);
        $isActive = ($tenant->status === 'active' && !$isExpired); 

        // Jika akun ternyata AKTIF (user iseng buka URL ini), kembalikan ke dashboard
        if ($isActive) {
            $protocol = $request->secure() ? 'https://' : 'http://';
            $dashboardUrl = $protocol . $tenant->subdomain . '.tokosancaka.com/dashboard';
            
            return redirect()->away($dashboardUrl);
        }

        // 5. SIAPKAN DATA TANGGAL UNTUK JAVASCRIPT (TIMER ALPINE.JS)
        // Tambah 30 hari dari tanggal expired sebagai batas hapus permanen
        $deletionDate = $expiredDate->copy()->addDays(30);
        
        // Gunakan toIso8601String() agar mutlak terbaca oleh new Date() di JS
        $isoDeletionDate = $deletionDate->toIso8601String();

        // 6. TAMPILKAN HALAMAN SUSPEND
        return view('tenant.suspended', compact('tenant', 'latestLicense', 'isoDeletionDate'));
    }
}