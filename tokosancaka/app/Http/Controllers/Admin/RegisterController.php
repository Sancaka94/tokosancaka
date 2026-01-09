<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class RegisterController extends Controller
{
    /**
     * Menampilkan form registrasi.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Menangani permintaan pendaftaran (method ini dipanggil oleh route).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        // 1. Validasi input dari form
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users|unique:registration_requests',
            'no_wa' => 'required|string|min:10',
            'store_nama' => 'required|string|max:255',
        ]);

        // 2. Buat token unik untuk verifikasi
        $token = Str::random(60);

        // 3. Simpan permintaan pendaftaran ke database
        DB::table('registration_requests')->insert([
            'nama' => $request->input('nama'),
            'email' => $request->input('email'),
            'no_wa' => $request->input('no_wa'),
            'store_nama' => $request->input('store_nama'),
            'token' => $token,
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Siapkan link dan pesan notifikasi untuk admin
        $verificationLink = route('register.verify', ['token' => $token]);
        $adminWhatsapp = '6285745808809'; // Ganti dengan nomor WA admin yang sebenarnya
        
        $message  = "🔔 *Verifikasi Pendaftaran Baru*\n\n";
        $message .= "Halo Admin, ada permintaan pendaftaran baru yang perlu disetujui.\n\n";
        $message .= "--------------------------------------------------\n";
        $message .= "*DETAIL PENDAFTAR:*\n";
        $message .= "👤 *Nama:* " . $request->input('nama') . "\n";
        $message .= "📧 *Email:* " . $request->input('email') . "\n";
        $message .= "🏪 *Toko:* " . $request->input('store_nama') . "\n";
        $message .= "📱 *No. WA:* " . $request->input('no_wa') . "\n";
        $message .= "--------------------------------------------------\n\n";
        $message .= "Untuk menyetujui dan membuat akun untuk pengguna ini, silakan klik link di bawah ini:\n\n";
        $message .= "👇 *LINK PERSETUJUAN (Magic Link)* 👇\n";
        $message .= $verificationLink . "\n\n";
        $message .= "_Link ini hanya valid selama 24 jam dan hanya bisa digunakan satu kali._";

        $whatsappUrl = "https://wa.me/{$adminWhatsapp}?text=" . urlencode($message);

        // 5. Kembalikan ke halaman registrasi dengan pesan sukses dan URL WhatsApp
        return redirect()->route('register')
            ->with('success', 'Permintaan pendaftaran Anda telah dikirim! Silakan klik tombol di bawah untuk mengirim link verifikasi ke Admin.')
            ->with('whatsapp_url', $whatsappUrl);
    }

    /**
     * Memverifikasi token, membuat user, dan menampilkan hasilnya.
     *
     * @param  string  $token
     * @return \Illuminate\View\View
     */
    public function verify($token)
    {
        $requestData = DB::table('registration_requests')->where('token', $token)->first();

        // Cek jika token tidak ada atau sudah kedaluwarsa
        if (!$requestData || now()->greaterThan($requestData->expires_at)) {
            return view('auth.verification-result', ['status' => 'error', 'message' => 'Link verifikasi tidak valid atau sudah kedaluwarsa.']);
        }

        // Cek jika email sudah terdaftar di tabel users
        if (User::where('email', $requestData->email)->exists()) {
            DB::table('registration_requests')->where('token', $token)->delete();
            return view('auth.verification-result', ['status' => 'error', 'message' => 'Gagal: Pengguna dengan email ini sudah terdaftar.']);
        }

        // Buat password acak untuk pengguna baru
        $randomPassword = Str::random(8);

        // Buat pengguna baru
        $user = User::create([
            'name' => $requestData->nama,
            'email' => $requestData->email,
            'password' => Hash::make($randomPassword),
            'no_wa' => $requestData->no_wa,
            'role' => 'pelanggan', 
            'is_verified' => 1,
        ]);

        // Hapus permintaan dari tabel setelah berhasil
        DB::table('registration_requests')->where('token', $token)->delete();

        // Tampilkan halaman hasil verifikasi dengan detail login
        return view('auth.verification-result', [
            'status' => 'success',
            'message' => 'Pengguna ' . e($user->name) . ' berhasil dibuat!',
            'details' => 'Silakan kirim detail login berikut ke nomor WA mereka (' . e($user->no_wa) . '):',
            'email' => $user->email,
            'password' => $randomPassword
        ]);
    }
}
