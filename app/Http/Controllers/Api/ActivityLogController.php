<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\TopUp;
use App\Models\ScannedPackage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;
use Exception; // Tambahkan ini untuk error handling

class ActivityLogController extends Controller
{
    /**
     * Mengembalikan log aktivitas dalam format JSON dengan filter dan pagination.
     * Didesain untuk dikonsumsi oleh aplikasi seperti Flutter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $filter = $request->input('filter');
            $allActivities = collect([]);
            $agent = new Agent();

            // Mengambil data berdasarkan filter. Jika tidak ada filter, ambil semua.
            // Logika ini sama persis dengan controller web Anda.
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
                        'timestamp' => $user->created_at->toIso8601String(), // Format standar
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
                        'timestamp' => $order->created_at->toIso8601String(),
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
                        'timestamp' => $topUp->created_at->toIso8601String(),
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
                        'timestamp' => $scan->created_at->toIso8601String(),
                        'ip_address' => $scan->ip_address ?? 'N/A',
                        'device' => $this->parseUserAgent($agent, $scan->user_agent),
                        'latitude' => $scan->latitude,
                        'longitude' => $scan->longitude,
                        'maps_url' => ($scan->latitude && $scan->longitude) ? "https://www.google.com/maps?q={$scan->latitude},{$scan->longitude}" : null,
                    ]);
                }
            }

            $sortedActivities = $allActivities->sortByDesc('timestamp');

            // Pagination logic
            $perPage = $request->input('per_page', 25); // Izinkan Flutter menentukan jumlah item per halaman
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $currentPageItems = $sortedActivities->slice(($currentPage - 1) * $perPage, $perPage)->values();
            $paginatedActivities = new LengthAwarePaginator($currentPageItems, $sortedActivities->count(), $perPage, $currentPage, [
                'path' => $request->url(), // Gunakan URL request untuk path pagination
                'query' => $request->query(),
            ]);

            // INI BAGIAN UTAMA YANG BERBEDA: Mengembalikan response JSON
            return response()->json([
                'success' => true,
                'message' => 'Log aktivitas berhasil diambil.',
                'data' => $paginatedActivities
            ], 200);

        } catch (Exception $e) {
            // Penanganan error dasar jika terjadi masalah
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengembalikan 5 notifikasi terbaru dari setiap kategori.
     * Didesain untuk dropdown notifikasi di aplikasi Flutter.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function headerNotifications()
    {
        try {
            $allActivities = collect([]);
            $limit = 5; // Batas pengambilan data awal untuk setiap jenis aktivitas

            // 1. Mengambil data pendaftaran pengguna terbaru
            $userRegistrations = User::latest()->take($limit)->get();
            foreach ($userRegistrations as $user) {
                if ($user && $user->id_pengguna) {
                    $allActivities->push([
                        'id' => $user->id_pengguna,
                        'user' => $user->nama_lengkap ?? 'N/A',
                        'description' => 'Mendaftar sebagai ' . $user->role,
                        'details' => '',
                        'type' => 'user',
                        'timestamp' => $user->created_at->toIso8601String(),
                        'maps_url' => ($user->latitude && $user->longitude) ? "https://www.google.com/maps?q={$user->latitude},{$user->longitude}" : null,
                    ]);
                }
            }

            // 2. Mengambil data pesanan terbaru
            $newOrders = Pesanan::with('pembeli')->latest()->take($limit)->get();
            foreach ($newOrders as $order) {
                if ($order && $order->resi) {
                    $allActivities->push([
                        'id' => $order->resi,
                        'user' => optional($order->pembeli)->nama_lengkap ?? $order->sender_name,
                        'description' => 'Pesanan baru',
                        'details' => 'Total: Rp ' . number_format($order->total_harga_barang, 0, ',', '.'),
                        'type' => 'order',
                        'timestamp' => $order->created_at->toIso8601String(),
                        'maps_url' => ($order->latitude && $order->longitude) ? "https://www.google.com/maps?q={$order->latitude},{$order->longitude}" : null,
                    ]);
                }
            }

            // 3. Mengambil data top up saldo terbaru
            $topUps = TopUp::with('customer')->latest()->take($limit)->get();
            foreach ($topUps as $topUp) {
                if ($topUp) {
                    $allActivities->push([
                       'id' => $topUp->id,
                       'user' => optional($topUp->customer)->nama_lengkap ?? 'N/A',
                       'description' => 'Request Top Up',
                       'details' => 'Jumlah: Rp ' . number_format($topUp->amount, 0, ',', '.'),
                       'type' => 'topup',
                       'timestamp' => $topUp->created_at->toIso8601String(),
                       'maps_url' => ($topUp->latitude && $topUp->longitude) ? "https://www.google.com/maps?q={$topUp->latitude},{$topUp->longitude}" : null,
                   ]);
                }
            }

            // 4. Mengambil data scan paket terbaru
            $spxScans = ScannedPackage::with('user')->latest()->take($limit)->get();
            foreach ($spxScans as $scan) {
                if ($scan) {
                   $allActivities->push([
                       'id' => $scan->id,
                       'user' => optional($scan->user)->nama_lengkap ?? 'N/A',
                       'description' => 'Scan Resi SPX',
                       'details' => 'Resi: ' . $scan->resi_number,
                       'type' => 'scan',
                       'timestamp' => $scan->created_at->toIso8601String(),
                       'maps_url' => ($scan->latitude && $scan->longitude) ? "https://www.google.com/maps?q={$scan->latitude},{$scan->longitude}" : null,
                   ]);
               }
            }

            $sortedActivities = $allActivities->sortByDesc('timestamp')->values();

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil diambil.',
                'data' => $sortedActivities
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil notifikasi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mem-parsing string User-Agent menjadi informasi yang detail.
     * Fungsi helper ini tetap sama.
     */
    private function parseUserAgent(Agent $agent, $userAgentString)
    {
        if (empty($userAgentString)) {
            return 'Tidak Diketahui';
        }

        $agent->setUserAgent($userAgentString);

        $platform = $agent->platform();
        $browser = $agent->browser();

        $platformStr = $platform ?: 'Platform Tidak Dikenal';
        $browserStr = $browser ?: 'Browser Tidak Dikenal';

        if ($agent->isMobile()) {
            $device = $agent->device();
            if ($device && $device !== 'general mobile device') {
                 return "$browserStr di $device ($platformStr)";
            }
            return "$browserStr di $platformStr";
        }

        return "$browserStr di $platformStr";
    }
}

