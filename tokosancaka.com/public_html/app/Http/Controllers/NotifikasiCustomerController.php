<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class NotifikasiCustomerController
 *
 * Controller ini menangani pengambilan data notifikasi yang *sudah ada* di database
 * dan mengelola status 'read' (sudah dibaca).
 *
 * Pengiriman notifikasi real-time ditangani oleh NotifikasiUmum.php
 * dan penerimaannya ditangani oleh Laravel Echo di frontend.
 */
class NotifikasiCustomerController extends Controller
{
    /**
     * =====================================================================
     * (DIHAPUS) Method __construct()
     * =====================================================================
     *
     * Kita tidak lagi memerlukan ini.
     * Middleware 'auth' sudah diterapkan di file routes/web.php
     * pada Route::middleware(['auth'])->group(...);
     *
     */
    /*
    public function __construct()
    {
        // Menerapkan middleware auth ke semua method di controller ini
        $this->middleware('auth');
    }
    */

    /**
     * Mengambil notifikasi yang BELUM DIBACA (untuk dropdown header).
     * Ini dipanggil oleh JavaScript saat halaman pertama kali dimuat,
     * sebelum Echo/Reverb mengambil alih.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnread(Request $request)
    {
        // Menggunakan $request->user() untuk mendapatkan pengguna yang 
        // sedang login (dari guard manapun yang aktif)
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
     * Mengambil semua notifikasi (terbaca & belum) dengan paginasi.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil semua notifikasi, diurutkan dari yang terbaru,
        // dan bagi per halaman (misal: 15 notifikasi per halaman)
        $notifications = $user->notifications()->paginate(15);

        // Jika request adalah AJAX (misal: "load more"), kirim JSON
        if ($request->wantsJson()) {
            return response()->json($notifications);
        }

        // Jika tidak, tampilkan view Blade
        // Anda perlu membuat file view ini di:
        // resources/views/notifikasi/index.blade.php
        return view('notifikasi.index', compact('notifications'));
    }

    /**
     * Menandai satu notifikasi spesifik sebagai "sudah dibaca".
     *
     * @param Request $request
     * @param string $id ID notifikasi
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();

        // Cari notifikasi berdasarkan ID, tapi HANYA di antara
        // notifikasi milik pengguna yang sedang login.
        $notification = $user->notifications()->find($id);

        if ($notification) {
            // Tandai sebagai sudah dibaca
            $notification->markAsRead();

            // Kirim kembali jumlah unread terbaru
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
     * Menandai SEMUA notifikasi yang belum dibaca sebagai "sudah dibaca".
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        // Gunakan method bawaan Laravel untuk menandai semua
        $user->unreadNotifications()->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Semua notifikasi ditandai sebagai dibaca.',
            'unread_count' => 0 // Pasti 0
        ]);
    }
}