<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User; // Pastikan sesuai dengan model tabel Pengguna Anda
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PenggunaController extends Controller
{
    /**
     * Menampilkan daftar pengguna untuk aplikasi mobile admin
     */
    public function index(Request $request)
    {
        // KUNCI AKSES API: Memastikan hanya Admin ID 4 yang bisa request data ini (Jika menggunakan auth token)
        $user = $request->user();
        if ($user && $user->id_pengguna != 4) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = User::orderBy('created_at', 'desc');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%");
            });
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Menyetujui pendaftaran (Approve)
     */
    public function approve(Request $request, $id)
    {
        $user = User::where('id_pengguna', $id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        $user->status = 'Aktif';
        $user->save();

        // Kirim Email Notifikasi Disetujui
        try {
            $email = $user->email;
            $subject = "Pendaftaran Disetujui - Toko Sancaka";
            $bodyHtml = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2>Selamat, {$user->nama_lengkap}!</h2>
                    <p>Pendaftaran Anda di <strong>Toko Sancaka</strong> telah disetujui oleh Admin.</p>
                    <p>Sekarang Anda sudah dapat login dan menggunakan fitur aplikasi kami.</p>
                    <br>
                    <p>Terima kasih,<br><strong>Tim Toko Sancaka</strong></p>
                </div>
            ";

            Mail::html($bodyHtml, function ($message) use ($email, $subject) {
                $message->to($email)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

        } catch (\Exception $e) {
            Log::error("Gagal kirim email approve ke {$user->email}: " . $e->getMessage());
            // Email gagal dikirim tapi status tetap diubah
        }

        return response()->json([
            'success' => true,
            'message' => "Pengguna {$user->nama_lengkap} berhasil disetujui dan email notifikasi telah dikirim."
        ]);
    }

    /**
     * Menolak pendaftaran (Reject)
     */
    public function reject(Request $request, $id)
    {
        $user = User::where('id_pengguna', $id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        $user->status = 'Ditolak';
        $user->save();

        // Kirim Email Notifikasi Ditolak
        try {
            $email = $user->email;
            $subject = "Pemberitahuan Pendaftaran - Toko Sancaka";
            $bodyHtml = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2>Mohon Maaf, {$user->nama_lengkap}</h2>
                    <p>Pendaftaran akun Anda di <strong>Toko Sancaka</strong> saat ini tidak dapat kami setujui.</p>
                    <p>Hal ini mungkin disebabkan oleh data yang tidak valid atau tidak memenuhi kriteria layanan kami.</p>
                    <p>Jika ini adalah kesalahan, silakan hubungi Admin kami melalui WhatsApp.</p>
                    <br>
                    <p>Terima kasih,<br><strong>Tim Toko Sancaka</strong></p>
                </div>
            ";

            Mail::html($bodyHtml, function ($message) use ($email, $subject) {
                $message->to($email)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

        } catch (\Exception $e) {
            Log::error("Gagal kirim email reject ke {$user->email}: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "Pendaftaran {$user->nama_lengkap} berhasil ditolak dan email notifikasi telah dikirim."
        ]);
    }

    /**
     * Menghapus pengguna (Hapus Permanen)
     */
    public function destroy($id)
    {
        $user = User::where('id_pengguna', $id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus permanen.'
        ]);
    }
}
