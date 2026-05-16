<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // BENAR ✅
use App\Http\Middleware\EnforceLicenseLimits; // Pastikan middleware ini sudah dibuat
use Illuminate\Support\Facades\Log; // Untuk logging
use Illuminate\Support\Str;
use App\Models\License; // Pastikan nanti kita buat Model ini
use App\Models\Tenant; // Model Tenant untuk relasi jika diperlukan
use App\Models\User; // Model User untuk relasi jika diperlukan

class SuspendController extends Controller
{
    protected $tenantId;

    public function __construct(Request $request)
    {
        // Deteksi Subdomain untuk mengunci data dashboard
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    public function index(Request $request)
        {
            dd("Controller Suspend Berhasil Dieksekusi!");
            // 1. AMBIL TENANT BERDASARKAN SUBDOMAIN DI URL BROWSER
            $host = $request->getHost(); // Contoh: gerai.tokosancaka.com
            $subdomain = explode('.', $host)[0]; // Mengambil 'gerai'

            // Cari tenant berdasarkan subdomain yang sedang dibuka
            $tenant = \DB::table('tenants')->where('subdomain', $subdomain)->first();

            // Jika tenant tidak ditemukan di database
            if (!$tenant) {
                abort(404, 'Tenant tidak ditemukan.');
            }

            // 2. CEK LOGIC REDIRECT BERDASARKAN DATA TENANT YANG DIAKSES
            $isExpired = ($tenant->expired_at && now()->gt($tenant->expired_at));
            $isActive = ($tenant->status === 'active' && !$isExpired); // Menggunakan && agar lebih ketat

            // Jika akun sebenarnya AKTIF, kembalikan ke dashboard subdomain tersebut
            if ($isActive) {
                $protocol = $request->secure() ? 'https://' : 'http://';
                $dashboardUrl = $protocol . $tenant->subdomain . '.tokosancaka.com/dashboard';
                
                return redirect()->away($dashboardUrl);
            }

            // 3. SIAPKAN DATA TANGGAL UNTUK JAVASCRIPT
            $expiredDate = $tenant->expired_at ? Carbon::parse($tenant->expired_at) : Carbon::now();
            $deletionDate = $expiredDate->copy()->addDays(30);
            $isoDeletionDate = $deletionDate->format('c');

            // 4. TAMPILKAN HALAMAN SUSPEND
            return view('tenant.suspended', compact('tenant', 'isoDeletionDate'));
        }
}