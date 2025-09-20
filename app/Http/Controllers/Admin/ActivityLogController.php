<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\TopUp;
use App\Models\ScannedPackage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;

class ActivityLogController extends Controller
{
    /**
     * Menampilkan halaman log aktivitas dengan filter dan pagination.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter');
        $allActivities = collect([]);
        $agent = new Agent();

        // Mengambil data berdasarkan filter. Jika tidak ada filter, ambil semua.
        if (!$filter || $filter === 'user') {
            $userRegistrations = User::withTrashed()
                ->whereIn('role', ['Pelanggan', 'Toko', 'Admin']) 
                ->select('id_pengguna', 'nama_lengkap', 'role', 'created_at', 'ip_address', 'user_agent', 'latitude', 'longitude')
                ->latest()->take(100)->get();
            foreach ($userRegistrations as $user) {
                $allActivities->push([
                    'user' => $user->nama_lengkap ?? 'User Dihapus',
                    'description' => 'Mendaftar sebagai ' . $user->role,
                    'details' => '',
                    'type' => 'user',
                    'status' => 'Selesai',
                    'timestamp' => $user->created_at,
                    'ip_address' => $user->ip_address ?? 'N/A',
                    'device' => $this->parseUserAgent($agent, $user->user_agent),
                    'latitude' => $user->latitude,
                    'longitude' => $user->longitude,
                    'maps_url' => ($user->latitude && $user->longitude) ? "https://www.google.com/maps?q={$user->latitude},{$user->longitude}" : null,
                ]);
            }
        }

        if (!$filter || $filter === 'order') {
            $newOrders = Pesanan::with('pembeli')
                ->select('id_pesanan', 'id_pengguna_pembeli', 'resi', 'total_harga_barang', 'created_at', 'ip_address', 'user_agent', 'latitude', 'longitude', 'status_pesanan', 'sender_name')
                ->latest()->take(100)->get();
            foreach ($newOrders as $order) {
                $allActivities->push([
                    'user' => optional($order->pembeli)->nama_lengkap ?? $order->sender_name,
                    'description' => 'Aktivitas Pesanan',
                    'details' => 'Rp ' . number_format($order->total_harga_barang, 0, ',', '.'),
                    'type' => 'order',
                    'status' => $order->status_pesanan ?? 'Baru',
                    'timestamp' => $order->created_at,
                    'ip_address' => $order->ip_address ?? 'N/A',
                    'device' => $this->parseUserAgent($agent, $order->user_agent),
                    'latitude' => $order->latitude,
                    'longitude' => $order->longitude,
                    'maps_url' => ($order->latitude && $order->longitude) ? "https://www.google.com/maps?q={$order->latitude},{$order->longitude}" : null,
                ]);
            }
        }

        if (!$filter || $filter === 'topup') {
            $topUps = TopUp::with('customer')
                ->select('id', 'customer_id', 'amount', 'created_at', 'ip_address', 'user_agent', 'latitude', 'longitude', 'status')
                ->latest()->take(100)->get();
            foreach ($topUps as $topUp) {
                $allActivities->push([
                    'user' => optional($topUp->customer)->nama_lengkap ?? 'Pelanggan Dihapus',
                    'description' => 'Top Up Saldo',
                    'details' => 'Rp ' . number_format($topUp->amount, 0, ',', '.'),
                    'type' => 'topup',
                    'status' => $topUp->status,
                    'timestamp' => $topUp->created_at,
                    'ip_address' => $topUp->ip_address ?? 'N/A',
                    'device' => $this->parseUserAgent($agent, $topUp->user_agent),
                    'latitude' => $topUp->latitude,
                    'longitude' => $topUp->longitude,
                    'maps_url' => ($topUp->latitude && $topUp->longitude) ? "https://www.google.com/maps?q={$topUp->latitude},{$topUp->longitude}" : null,
                ]);
            }
        }

        if (!$filter || $filter === 'scan') {
            $spxScans = ScannedPackage::with(['user', 'kontak'])
                ->select('id', 'user_id', 'kontak_id', 'resi_number', 'created_at', 'ip_address', 'user_agent', 'latitude', 'longitude', 'status')
                ->latest()->take(100)->get();
            
            foreach ($spxScans as $scan) {
                $userName = optional($scan->kontak)->nama ?? optional($scan->user)->nama_lengkap ?? 'Nama Tidak Ditemukan';
                $allActivities->push([
                    'user' => $userName,
                    'description' => 'Scan Resi SPX',
                    'details' => $scan->resi_number,
                    'type' => 'scan',
                    'status' => $scan->status,
                    'timestamp' => $scan->created_at,
                    'ip_address' => $scan->ip_address ?? 'N/A',
                    'device' => $this->parseUserAgent($agent, $scan->user_agent),
                    'latitude' => $scan->latitude,
                    'longitude' => $scan->longitude,
                    'maps_url' => ($scan->latitude && $scan->longitude) ? "https://www.google.com/maps?q={$scan->latitude},{$scan->longitude}" : null,
                ]);
            }
        }

        $sortedActivities = $allActivities->sortByDesc('timestamp');
        $perPage = 25;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $sortedActivities->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedActivities = new LengthAwarePaginator($currentPageItems, $sortedActivities->count(), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]);

        return view('admin.activity-log', [ 
            'activities' => $paginatedActivities,
            'currentFilter' => $filter
        ]);
    }

    /**
     * ✅ DIPERBARUI: Mengambil 5 log aktivitas terbaru dari setiap kategori,
     * lalu mengurutkan semuanya tanpa memotongnya lagi.
     */
    public function getHeaderNotifications()
    {
        $allActivities = collect([]);
        $limit = 5; // Batas pengambilan data awal untuk setiap jenis aktivitas agar efisien

        // 1. Mengambil data PENDAFTARAN pengguna terbaru
        $userRegistrations = User::latest()->take($limit)->get();
        foreach ($userRegistrations as $user) {
            if ($user && $user->id_pengguna) {
                $allActivities->push((object)[
                    'icon' => 'fa-solid fa-user-plus text-blue-500',
                    'title' => 'Pengguna baru: ' . ($user->nama_lengkap ?? 'N/A'),
                    'details' => 'Mendaftar sebagai ' . $user->role,
                    'url' => route('admin.customers.edit', $user->id_pengguna),
                    'created_at' => $user->created_at,
                    'maps_url' => ($user->latitude && $user->longitude) ? "https://www.google.com/maps?q={$user->latitude},{$user->longitude}" : null,
                ]);
            }
        }

        // 2. Mengambil data PESANAN terbaru
        $newOrders = Pesanan::with('pembeli')->latest()->take($limit)->get();
        foreach ($newOrders as $order) {
            if ($order && $order->resi) {
                $allActivities->push((object)[
                    'icon' => 'fa-solid fa-box text-green-500',
                    'title' => 'Pesanan baru dari ' . (optional($order->pembeli)->nama_lengkap ?? $order->sender_name),
                    'details' => 'Total: Rp ' . number_format($order->total_harga_barang, 0, ',', '.'),
                    'url' => route('admin.pesanan.show', $order->resi),
                    'created_at' => $order->created_at,
                    'maps_url' => ($order->latitude && $order->longitude) ? "https://www.google.com/maps?q={$order->latitude},{$order->longitude}" : null,
                ]);
            }
        }
        
        // 3. Mengambil data TOP UP SALDO terbaru
        $topUps = TopUp::with('customer')->latest()->take($limit)->get();
        foreach ($topUps as $topUp) {
            if ($topUp) {
                 $allActivities->push((object)[
                    'icon' => 'fa-solid fa-wallet text-orange-500',
                    'title' => 'Request Top Up dari ' . (optional($topUp->customer)->nama_lengkap ?? 'N/A'),
                    'details' => 'Jumlah: Rp ' . number_format($topUp->amount, 0, ',', '.'),
                    'url' => route('admin.saldo.requests.index'),
                    'created_at' => $topUp->created_at,
                    'maps_url' => ($topUp->latitude && $topUp->longitude) ? "https://www.google.com/maps?q={$topUp->latitude},{$topUp->longitude}" : null,
                ]);
            }
        }
        
        // 4. Mengambil data SCAN PAKET terbaru
        $spxScans = ScannedPackage::with('user')->latest()->take($limit)->get();
        foreach ($spxScans as $scan) {
             if ($scan) {
                $allActivities->push((object)[
                    'icon' => 'fa-solid fa-barcode text-purple-500',
                    'title' => 'Scan Resi oleh ' . (optional($scan->user)->nama_lengkap ?? 'N/A'),
                    'details' => 'Resi: ' . $scan->resi_number,
                    'url' => route('admin.spx_scans.index'),
                    'created_at' => $scan->created_at,
                    'maps_url' => ($scan->latitude && $scan->longitude) ? "https://www.google.com/maps?q={$scan->latitude},{$scan->longitude}" : null,
                ]);
            }
        }

        // Urutkan semua aktivitas yang terkumpul (hingga 20 notifikasi) dan kembalikan semuanya.
        return $allActivities->sortByDesc('created_at');
    }

    /**
     * Mem-parsing string User-Agent menjadi informasi yang detail.
     */
    private function parseUserAgent(Agent $agent, $userAgentString)
    {
        if (empty($userAgentString)) {
            return 'Tidak Diketahui';
        }

        $agent->setUserAgent($userAgentString);

        $platform = $agent->platform();
        $platformVersion = $agent->version($platform);
        $browser = $agent->browser();
        $browserVersion = $agent->version($browser);
        $platformStr = $platform && $platformVersion ? "$platform $platformVersion" : ($platform ?: 'Platform Tidak Dikenal');
        $browserStr = $browser && $browserVersion ? "$browser $browserVersion" : ($browser ?: 'Browser Tidak Dikenal');

        if ($agent->isMobile()) {
            $device = $agent->device();
            if ($device && $device !== 'general mobile device') {
                 return "$browserStr on $device ($platformStr)";
            }
            return "$browserStr on $platformStr";
        }
        
        return "$browserStr on $platformStr";
    }
}

