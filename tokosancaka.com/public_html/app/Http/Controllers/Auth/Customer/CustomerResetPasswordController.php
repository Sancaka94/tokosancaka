<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Tambahan: Facade Hash standar Laravel
use Carbon\Carbon; // Tambahan: Untuk validasi waktu kedaluwarsa OTP
use App\Models\User;

class CustomerResetPasswordController extends Controller
{
    /**
     * ✅ PERBAIKAN FINAL: Mengarahkan LANGSUNG ke dashboard.
     */
    protected $redirectTo = '/customer/dashboard';

    public function showResetForm(Request $request)
    {
        // Tangkap identifier dari URL untuk dikirim ke form
        $identifier = $request->query('identifier');
        
        if (!$identifier) {
            return redirect()->route('password.request')
                             ->withErrors(['phone' => 'Sesi tidak valid, silakan ulangi permintaan reset password.']);
        }

        return view('auth.passwords.reset')->with(['identifier' => $identifier]);
    }

    public function reset(Request $request)
    {
        Log::info('Proses validasi OTP dan Reset Password dimulai.', ['identifier' => $request->identifier]);

        // ====================================================================
        // 1. Validasi Input Form
        // ====================================================================
        $request->validate([
            'otp'                   => 'required|string',
            'identifier'            => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ], [
            'otp.required'          => 'Kode OTP wajib diisi.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
            'password.min'          => 'Password minimal 8 karakter.',
        ]);

        // ====================================================================
        // 2. Cari user berdasarkan Email atau No WA
        // ====================================================================
        $user = User::where('email', $request->identifier)
                    ->orWhere('no_wa', $request->identifier)
                    ->first();

        if (!$user) {
            Log::warning('Reset Gagal: Pengguna tidak ditemukan.', ['identifier' => $request->identifier]);
            return back()->withErrors(['otp' => 'Data pengguna tidak ditemukan di sistem.']);
        }

        // ====================================================================
        // 3. Ambil dan Verifikasi OTP dari Database
        // ====================================================================
        $inputOtp = preg_replace('/\s+/', '', $request->otp);
        $table = DB::getSchemaBuilder()->hasTable('password_reset_tokens') ? 'password_reset_tokens' : 'password_resets';
        
        // Gunakan email/no_wa yang sama persis seperti saat generate token di controller sebelumnya
        $identifierForToken = $user->email ?? $user->no_wa;
        $resetRecord = DB::table($table)->where('email', $identifierForToken)->first();

        // Cek Keberadaan dan Kecocokan OTP
        if (!$resetRecord || strtoupper($resetRecord->token) !== strtoupper($inputOtp)) {
            Log::warning('Reset Gagal: OTP salah.', ['identifier' => $identifierForToken]);
            return back()->withErrors(['otp' => 'Kode OTP salah. Silakan periksa kembali.']);
        }

        // Cek Masa Berlaku OTP (Maksimal 60 Menit)
        $tokenCreatedAt = Carbon::parse($resetRecord->created_at);
        if ($tokenCreatedAt->addMinutes(60)->isPast()) {
            // Hapus OTP yang sudah hangus agar database bersih
            DB::table($table)->where('email', $identifierForToken)->delete();
            
            Log::warning('Reset Gagal: OTP kedaluwarsa.', ['identifier' => $identifierForToken]);
            return back()->withErrors(['otp' => 'Kode OTP sudah kedaluwarsa (lebih dari 60 menit). Silakan minta ulang kode baru.']);
        }

        // ====================================================================
        // 4 & 5. Update Password & Bersihkan OTP (Dibungkus DB Transaction)
        // ====================================================================
        try {
            DB::transaction(function () use ($user, $request, $table, $identifierForToken) {
                // Update menggunakan Hash facade
                $user->password = Hash::make($request->password);
                $user->save();

                // Hapus token yang sudah terpakai
                DB::table($table)->where('email', $identifierForToken)->delete();
            });
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan sistem saat menyimpan password baru: ' . $e->getMessage());
            return back()->withErrors(['otp' => 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.']);
        }

        // ====================================================================
        // 6. Auto Login ke Dashboard
        // ====================================================================
        Auth::guard('web')->login($user);
        
        // Asumsi primary key model User Anda adalah id_pengguna atau id
        $userId = $user->id_pengguna ?? $user->id; 
        Log::info('Reset Password sukses, otomatis login.', ['user_id' => $userId]);

        return redirect($this->redirectTo)
               ->with('success', 'Password berhasil diubah! Selamat datang kembali.');
    }
}