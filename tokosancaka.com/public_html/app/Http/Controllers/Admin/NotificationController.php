<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\TopUp;
use App\Models\ScannedPackage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth; // <-- DITAMBAHKAN: Diperlukan untuk mengambil user
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Helper aman untuk menjalankan query count dengan cache.
     * Diperbarui agar tidak meng-cache error.
     */
    private function getCount($cacheKey, $queryClosure)
    {
        try {
            // Cache hasil selama 1 menit
            return Cache::remember($cacheKey, 60, function () use ($queryClosure) {
                return $queryClosure();
            });
        } catch (\Exception $e) {
            // Catat error dan kembalikan 0
            Log::error("Notification count failed for key [{$cacheKey}]: " . $e->getMessage());
            return 0; // Kembalikan 0 jika error
        }
    }

    /**
     * Mengambil jumlah notifikasi yang BELUM DIBACA untuk ikon lonceng.
     * Disesuaikan agar mengambil notifikasi milik admin yang sedang login.
     */
    public function count()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['count' => 0]);
        }
        
        $count = $this->getCount('admin_unread_count_' . $user->id, function () use ($user) {
            return $user->unreadNotifications()->count();
        });
        
        return response()->json(['count' => $count]);
    }

    /**
     * Get the count of new user registrations.
     */
    public function registrationsCount()
    {
        $count = $this->getCount('notifications_registrations_count', function () {
            return User::where('status', 'Tidak Aktif')->count();
        });
        return response()->json(['count' => $count]);
    }

    /**
     * Get the count of new orders (pesanan).
     */
    public function pesananCount()
    {
        $count = $this->getCount('notifications_pesanan_count', function () {
            return Pesanan::where('status_pesanan', 'Menunggu Pickup')->count();
        });
        return response()->json(['count' => $count]);
    }

    /**
     * Get the count of pending balance requests (saldo).
     */
    public function saldoRequestsCount()
    {
        $count = $this->getCount('notifications_saldo_requests_count', function () {
            return TopUp::where('status', 'pending')->count();
        });
        return response()->json(['count' => $count]);
    }

    /**
     * Get the count of today's scanned packages (SPX).
     */
    public function spxScansCount() //
    {
        $count = $this->getCount('notifications_spx_scans_count', function () {
            return ScannedPackage::whereDate('created_at', today())->count();
        });
        return response()->json(['count' => $count]);
    }

    /**
     * Get the count of today's scanned packages.
     */
    public function riwayatScanCount()
    {
        $count = $this->getCount('notifications_riwayat_scan_count', function () {
            return ScannedPackage::whereDate('created_at', today())->count();
        });
        return response()->json(['count' => $count]);
    }

    // ===================================================================
    // FUNGSI-FUNGSI YANG HILANG (DITAMBAHKAN)
    // ===================================================================
    // Fungsi-fungsi ini diperlukan oleh JavaScript di admin.blade.php
    // untuk mengisi dropdown lonceng notifikasi.
    // ===================================================================

    /**
     * Mengambil notifikasi yang BELUM DIBACA (untuk dropdown header).
     * Ini dipanggil oleh JavaScript `loadInitialNotifications()`
     */
    public function getUnread(Request $request)
    {
        $user = $request->user();

        // Ambil 5 notifikasi terbaru yang belum dibaca
        $notifications = $user->unreadNotifications()->limit(5)->get();

        // Hitung JUMLAH total notifikasi yang belum dibaca
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Menampilkan halaman "Semua Notifikasi".
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(3);

        if ($request->wantsJson()) {
            return response()->json($notifications);
        }
        
        // Pastikan Anda punya view admin/notifikasi/index.blade.php
        // atau ubah 'admin.notifikasi.index' ke nama view Anda
        return view('admin.notifikasi.index', compact('notifications'));
    }

    /**
     * Menandai satu notifikasi spesifik sebagai "sudah dibaca".
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            $newCount = $user->unreadNotifications()->count();

            return response()->json([
                'status' => 'success',
                'message' => 'Notifikasi ditandai sebagai dibaca.',
                'unread_count' => $newCount
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Notifikasi tidak ditemukan.'
        ], 404);
    }

 /**
     * ===================================================================
     * [PERBAIKAN UTAMA]
     * Menandai SEMUA notifikasi sebagai "sudah dibaca".
     * ===================================================================
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        // [FIX 4 - PENYEBAB CRASH]
        // $user->unreadNotifications() -> mengembalikan OBJEK QUERY BUILDER (Relasi MorphMany)
        // $user->unreadNotifications   -> mengembalikan KOLEKSI (Collection) dari notifikasi
        //
        // Method 'markAsRead()' hanya ada di KOLEKSI.
        // Anda harus menghapus tanda kurung ()
        $user->unreadNotifications->markAsRead();

        // [FIX 5 - LOGIKA FORMULIR]
        // Kode asli mengembalikan JSON, tapi Anda mengirim dari <form> HTML biasa.
        // <form> HTML biasa mengharapkan REDIRECT, bukan JSON.
        // Jadi, kita redirect kembali ke halaman notifikasi.
        
        // Hapus kode JSON ini:
        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'Semua notifikasi ditandai sebagai dibaca.',
        //     'unread_count' => 0
        // ]);

        // Ganti dengan redirect:
        return redirect()->route('admin.notifications.index')
                         ->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }
}