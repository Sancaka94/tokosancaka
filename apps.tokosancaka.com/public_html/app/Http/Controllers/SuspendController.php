<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SuspendController extends Controller
{
    /**
     * Menampilkan halaman akun ditangguhkan (suspend).
     */
    public function index(Request $request)
    {
        // 1. CEK USER & TENANT
        $user = Auth::user();

        // Jika tidak login, redirect ke halaman login
        if (!$user) {
            return redirect('/percetakan/login');
        }

        // Jika login tapi tidak punya tenant
        if (!$user->tenant) {
            abort(403, 'Error: User tidak terhubung dengan tenant.');
        }

        $tenant = $user->tenant;

        // 2. LOGIC REDIRECT
        // Cek apakah masa aktif sudah lewat
        $isExpired = ($tenant->expired_at && now()->gt($tenant->expired_at));
        // Status aktif jika di database statusnya 'active' atau masa aktif belum lewat
        $isActive = ($tenant->status === 'active' || !$isExpired);

        // Jika akun sebenarnya AKTIF, tendang kembali ke dashboard
        if ($isActive) {
            $protocol = $request->secure() ? 'https://' : 'http://';
            $dashboardUrl = $protocol . $tenant->subdomain . '.tokosancaka.com/dashboard';
            
            return redirect()->away($dashboardUrl);
        }

        // 3. SIAPKAN DATA TANGGAL UNTUK JAVASCRIPT
        // Jika expired_at null, anggap expired hari ini agar timer jalan
        $expiredDate = $tenant->expired_at ? Carbon::parse($tenant->expired_at) : Carbon::now();
        
        // Batas hapus data = Tanggal expired + 30 Hari
        $deletionDate = $expiredDate->copy()->addDays(30);
        
        // Format ke ISO 8601 (Syarat mutlak agar terbaca oleh Javascript/AlpineJS)
        $isoDeletionDate = $deletionDate->format('c');

        // 4. TAMPILKAN HALAMAN SUSPEND (BLADE)
        // Catatan: Sesuaikan 'tenant.suspended' dengan nama folder dan file blade Anda.
        // Contoh: jika file Anda resources/views/errors/suspend.blade.php, tulis 'errors.suspend'
        return view('tenant.suspended', compact('tenant', 'isoDeletionDate'));
    }
}