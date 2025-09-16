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
                    'status' => 'Selesai', // Status untuk pendaftaran
                    'timestamp' => $user->created_at,
                    'ip_address' => $user->ip_address ?? 'N/A',
                    'device' => $this->parseUserAgent($agent, $user->user_agent),
                    'latitude' => $user->latitude,
                    'longitude' => $user->longitude,
                ]);
            }
        }

        if (!$filter || $filter === 'order') {
            $newOrders = Pesanan::with('pembeli')
                ->select('id_pesanan', 'id_pengguna_pembeli', 'total_harga_barang', 'created_at', 'ip_address', 'user_agent', 'latitude', 'longitude', 'status_pesanan', 'sender_name')
                ->latest()->take(100)->get();
            foreach ($newOrders as $order) {
                $allActivities->push([
                    'user' => optional($order->pembeli)->nama_lengkap ?? $order->sender_name,
                    'description' => 'Aktivitas Pesanan',
                    'details' => 'Rp ' . number_format($order->total_harga_barang, 0, ',', '.'),
                    'type' => 'order',
                    'status' => $order->status_pesanan ?? 'Baru', // Menambahkan status pesanan
                    'timestamp' => $order->created_at,
                    'ip_address' => $order->ip_address ?? 'N/A',
                    'device' => $this->parseUserAgent($agent, $order->user_agent),
                    'latitude' => $order->latitude,
                    'longitude' => $order->longitude,
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
                    'status' => $topUp->status, // Menambahkan status top up
                    'timestamp' => $topUp->created_at,
                    'ip_address' => $topUp->ip_address ?? 'N/A',
                    'device' => $this->parseUserAgent($agent, $topUp->user_agent),
                    'latitude' => $topUp->latitude,
                    'longitude' => $topUp->longitude,
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
                    'status' => $scan->status, // Menambahkan status scan
                    'timestamp' => $scan->created_at,
                    'ip_address' => $scan->ip_address ?? 'N/A',
                    'device' => $this->parseUserAgent($agent, $scan->user_agent),
                    'latitude' => $scan->latitude,
                    'longitude' => $scan->longitude,
                ]);
            }
        }

        // Mengurutkan semua aktivitas dari yang terbaru
        $sortedActivities = $allActivities->sortByDesc('timestamp');

        // Membuat pagination secara manual
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
