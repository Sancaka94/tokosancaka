<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
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

        $users = $query->paginate(15);

        return response()->json([
            'success'      => true,
            'data'         => $users->items(),
            'current_page' => $users->currentPage(),
            'last_page'    => $users->lastPage()
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = User::where('id_pengguna', $id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);

        $user->status = 'Aktif';
        $user->save();

        try {
            $email = $user->email;
            $subject = "Pendaftaran Disetujui - Toko Sancaka";
            $bodyHtml = "<h2>Selamat, {$user->nama_lengkap}!</h2><p>Pendaftaran Anda di <strong>Toko Sancaka</strong> telah disetujui oleh Admin. Anda sudah dapat login dan menggunakan fitur aplikasi kami.</p><br><p>Terima kasih,<br><strong>Tim Toko Sancaka</strong></p>";
            Mail::html($bodyHtml, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject)->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) { Log::error("Email error: " . $e->getMessage()); }

        return response()->json(['success' => true, 'message' => "Pengguna {$user->nama_lengkap} berhasil disetujui."]);
    }

    public function reject(Request $request, $id)
    {
        $user = User::where('id_pengguna', $id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);

        $user->status = 'Ditolak';
        $user->save();

        try {
            $email = $user->email;
            $subject = "Pemberitahuan Pendaftaran - Toko Sancaka";
            $bodyHtml = "<h2>Mohon Maaf, {$user->nama_lengkap}</h2><p>Pendaftaran akun Anda saat ini tidak dapat kami setujui karena tidak memenuhi kriteria layanan.</p><br><p>Terima kasih,<br><strong>Tim Toko Sancaka</strong></p>";
            Mail::html($bodyHtml, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject)->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) { Log::error("Email error: " . $e->getMessage()); }

        return response()->json(['success' => true, 'message' => "Pendaftaran {$user->nama_lengkap} berhasil ditolak."]);
    }

    // ====================================================
    // FITUR BARU: FREEZE / UNFREEZE
    // ====================================================
    public function freeze(Request $request, $id)
    {
        $user = User::where('id_pengguna', $id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);

        $user->status = 'Dibekukan';
        $user->save();

        try {
            $email = $user->email;
            $subject = "Pemberitahuan Keamanan - Akun Dibekukan Sementara";
            $bodyHtml = "<h2>Halo, {$user->nama_lengkap}</h2><p>Kami mendeteksi aktivitas yang memerlukan peninjauan pada akun Anda. Untuk menjaga keamanan, <strong>akun Anda saat ini dibekukan sementara</strong>.</p><p>Anda tidak akan dapat melakukan login hingga akun diaktifkan kembali. Silakan hubungi Admin kami via WhatsApp untuk penyelesaian.</p><br><p>Terima kasih,<br><strong>Tim Toko Sancaka</strong></p>";
            Mail::html($bodyHtml, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject)->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) { Log::error("Email error: " . $e->getMessage()); }

        return response()->json(['success' => true, 'message' => "Akun {$user->nama_lengkap} berhasil dibekukan."]);
    }

    public function unfreeze(Request $request, $id)
    {
        $user = User::where('id_pengguna', $id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);

        $user->status = 'Aktif';
        $user->save();

        try {
            $email = $user->email;
            $subject = "Akun Telah Diaktifkan Kembali - Toko Sancaka";
            $bodyHtml = "<h2>Halo, {$user->nama_lengkap}</h2><p>Kabar baik! <strong>Akun Anda telah diaktifkan kembali</strong> oleh Admin.</p><p>Anda sekarang dapat login dan bertransaksi seperti biasa.</p><br><p>Terima kasih,<br><strong>Tim Toko Sancaka</strong></p>";
            Mail::html($bodyHtml, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject)->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) { Log::error("Email error: " . $e->getMessage()); }

        return response()->json(['success' => true, 'message' => "Akun {$user->nama_lengkap} berhasil diaktifkan kembali."]);
    }
    // ====================================================

    public function destroy($id)
    {
        $user = User::where('id_pengguna', $id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        $user->delete();
        return response()->json(['success' => true, 'message' => 'Pengguna berhasil dihapus permanen.']);
    }
}