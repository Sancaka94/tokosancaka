<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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
            return redirect()->route('password.request')->withErrors(['phone' => 'Sesi tidak valid, silakan ulangi permintaan.']);
        }

        return view('auth.passwords.reset')->with(['identifier' => $identifier]);
    }

    public function reset(Request $request)
    {
        Log::info('Proses validasi OTP dan Reset Password dimulai.', ['identifier' => $request->identifier]);

        // 1. Validasi Input form
        $request->validate([
            'otp'                   => 'required|string',
            'identifier'            => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ], [
            'otp.required'          => 'Kode OTP wajib diisi.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
            'password.min'          => 'Password minimal 8 karakter.',
        ]);

        // 2. Cari user berdasarkan Email atau No WA
        $user = User::where('email', $request->identifier)->orWhere('no_wa', $request->identifier)->first();

        if (!$user) {
            return back()->withErrors(['otp' => 'Data pengguna tidak ditemukan.']);
        }

        // 3. Ambil dan Verifikasi OTP dari database
        $inputOtp = preg_replace('/\s+/', '', $request->otp);
        $table = DB::getSchemaBuilder()->hasTable('password_reset_tokens') ? 'password_reset_tokens' : 'password_resets';
        
        $resetRecord = DB::table($table)->where('email', $user->email ?? $user->no_wa)->first();

        if (!$resetRecord || strtoupper($resetRecord->token) !== strtoupper($inputOtp)) {
            Log::warning('Gagal: OTP salah atau expired.');
            return back()->withErrors(['otp' => 'Kode OTP salah atau sudah kedaluwarsa.']);
        }

        // 4. Update Password (Bcrypt)
        $user->password = bcrypt($request->password);
        $user->save();

        // 5. Bersihkan OTP dari tabel
        DB::table($table)->where('email', $user->email ?? $user->no_wa)->delete();

        // 6. Auto Login ke Dashboard
        Auth::guard('web')->login($user);
        Log::info('Reset Password sukses, otomatis login.', ['user_id' => $user->id_pengguna]);

        return redirect($this->redirectTo)
               ->with('success', 'Password berhasil diubah! Selamat datang kembali.');
    }
}