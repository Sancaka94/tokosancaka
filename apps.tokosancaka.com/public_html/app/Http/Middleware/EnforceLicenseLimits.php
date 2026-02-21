namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TenantDevice;
use App\Models\License;

class EnforceLicenseLimits
{
    public function handle(Request $request, Closure $next)
    {
        // Asumsi: Anda mendapatkan tenant yang sedang aktif (misal dari Auth atau Subdomain)
        $tenant = auth()->user()->tenant ?? null; 
        
        if (!$tenant) {
            return $next($request);
        }

        // Cari lisensi aktif dari tenant ini
        $activeLicense = License::where('tenant_id', $tenant->id)
            ->where('status', 'used')
            ->where('expires_at', '>', now())
            ->first();

        if (!$activeLicense) {
            abort(403, 'Lisensi Anda sudah habis. Silakan beli lisensi baru.');
        }

        $currentIp = $request->ip();
        $currentUserAgent = $request->userAgent();

        // Cek apakah device/IP ini sudah terdaftar sebelumnya
        $existingDevice = TenantDevice::where('tenant_id', $tenant->id)
            ->where('ip_address', $currentIp)
            ->where('user_agent', $currentUserAgent)
            ->first();

        if ($existingDevice) {
            $existingDevice->update(['last_accessed_at' => now()]);
            return $next($request); // Lolos, device sudah terdaftar
        }

        // Jika belum terdaftar, cek apakah kuota IP/Device masih ada
        $registeredDevicesCount = TenantDevice::where('tenant_id', $tenant->id)->count();
        $registeredIpsCount = TenantDevice::where('tenant_id', $tenant->id)->distinct('ip_address')->count();

        if ($registeredDevicesCount >= $activeLicense->max_devices || $registeredIpsCount >= $activeLicense->max_ips) {
            abort(403, 'Batas maksimal Device/IP telah tercapai. Lisensi Anda hanya untuk ' . $activeLicense->max_devices . ' device dan ' . $activeLicense->max_ips . ' IP. Anda tidak dapat login dari perangkat/jaringan ini.');
        }

        // Daftarkan device & IP baru
        TenantDevice::create([
            'tenant_id' => $tenant->id,
            'ip_address' => $currentIp,
            'user_agent' => $currentUserAgent,
        ]);

        return $next($request);
    }
}