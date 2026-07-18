<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Class CustomerForgotPasswordController
 *
 * Controller ini menangani permintaan reset password untuk User (Tabel Pengguna) murni via Email.
 * Dilengkapi dengan kewajiban koordinat lokasi dan verifikasi Captcha.
 */
class CustomerForgotPasswordController extends Controller
{
    /**
     * Menampilkan form untuk meminta token reset password.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        // Sesuaikan dengan nama view Blade yang baru dibuat
        return view('auth.passwords.email'); 
    }

    /**
     * Menangani pengiriman token reset murni via Email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkRequest(Request $request)
    {
        Log::info('Permintaan reset password dimulai.', ['input' => $request->email]);

        // 1. Validasi Input (Email, GPS, Captcha, dan Turnstile)
        $request->validate([
            'email'     => 'required|email',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'captcha'   => 'required|captcha',
            'cf-turnstile-response' => 'required'
        ]);

        // 2. Cari User di Database berdasarkan Email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            Log::warning('Reset password gagal: Data tidak ditemukan.', ['input' => $request->email]);
            return back()->withErrors(['email' => 'Email ini tidak terdaftar di sistem kami.']);
        }

        // 3. Generate KODE OTP (Bukan Link Panjang)
        $otpCode = strtoupper(Str::random(6));

        $table = DB::getSchemaBuilder()->hasTable('password_reset_tokens') ? 'password_reset_tokens' : 'password_resets';
        DB::table($table)->updateOrInsert(
            ['email' => $user->email],
            ['token' => $otpCode, 'created_at' => now()]
        );

        // 4. Kirim OTP via Email
        try {
            $emailBody = "Halo {$user->nama_lengkap},\n\nKami menerima permintaan reset password akun Sancaka Anda. Berikut adalah KODE OTP Anda:\n\n{$otpCode}\n\nKode ini berlaku 5 menit. Abaikan email ini jika Anda tidak merasa memintanya.";
            
            Mail::raw($emailBody, function ($mail) use ($user) {
                $mail->to($user->email)->subject('Kode OTP Reset Password Sancaka');
            });
            
            Log::info('OTP Reset terkirim ke Email.', ['email' => $user->email, 'latitude' => $request->latitude, 'longitude' => $request->longitude]);
        } catch (\Exception $e) {
            Log::error('Gagal kirim OTP Reset via Email: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Terjadi kesalahan saat mengirim email verifikasi. Coba lagi nanti.']);
        }

        return redirect()->route('password.reset', [
            'token'      => 'verify-otp',
            'identifier' => $user->email 
        ])->with('status', 'Kode OTP rahasia telah dikirim ke Email Anda.');
    }
}