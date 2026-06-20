<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class OtpController extends Controller
{
    // Method untuk menampilkan view OTP yang desainnya kotak-kotak tadi
    public function showOtpForm(Request $request)
    {
        // Cegah akses jika tidak ada session OTP
        if (!$request->session()->has('auth_otp_user_id')) {
            return redirect()->route('login')->with('error', 'Sesi login tidak valid. Silakan login ulang.');
        }

        return view('customer.otp'); // Sesuaikan dengan lokasi file blade Anda
    }

    // Method aksi form ketika input OTP di submit
    public function processOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6'
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Format kode OTP tidak valid (harus 6 karakter).'
        ]);

        $sessionOtp = session('auth_otp_code');
        $expiresAt = session('auth_otp_expires_at');
        $userId = session('auth_otp_user_id');

        // 1. Cek apakah sesi valid
        if (!$sessionOtp || !$userId) {
            return redirect()->route('login')->with('error', 'Sesi telah berakhir, silakan login ulang.');
        }

        // 2. Cek apakah waktu OTP habis
        if (now()->greaterThan($expiresAt)) {
            session()->forget(['auth_otp_code', 'auth_otp_expires_at', 'auth_otp_user_id']);
            return redirect()->route('login')->with('error', 'Kode OTP sudah kadaluarsa (lebih dari 5 menit). Silakan login ulang.');
        }

        // 3. Cek apakah OTP yang diinput cocok dengan Session
        if (strtoupper($request->otp) !== strtoupper($sessionOtp)) {
            // Kembali ke halaman form OTP (SweetAlert error akan muncul karena ada with('error'))
            return back()->with('error', 'Kode OTP yang Anda masukkan salah. Silakan periksa kembali.');
        }

        // 4. JIKA OTP BENAR -> RESMIKAN LOGINNYA
        Auth::loginUsingId($userId);

        // Bersihkan session OTP agar tidak disalahgunakan
        session()->forget(['auth_otp_code', 'auth_otp_expires_at', 'auth_otp_user_id']);
        $request->session()->regenerate();

        $user = Auth::user();

        // 5. OPTIONAL: Kirim pesan Welcome yang tadinya ada di Login Controller
        try {
            $noWa = preg_replace('/^0/', '62', $user->no_wa);
            // $message = $this->buildWelcomeMessage($user); // Panggil fungsi pesan Anda di sini
            // \App\Services\FonnteService::sendMessage($noWa, $message); 
        } catch (\Exception $e) {
            \Log::error('Gagal kirim pesan welcome: ' . $e->getMessage());
        }

        // 6. Redirect ke dashboard
        $role = strtolower(trim($user->role));
        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        
        return redirect()->route('customer.dashboard');
    }
}