<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Services\FonnteService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Mail;

use App\Notifications\NotifikasiUmum;
use Laravel\Socialite\Facades\Socialite; // <-- TAMBAHAN: Import Socialite
use Illuminate\Http\RedirectResponse;    // <-- TAMBAHAN: Untuk tipe return redirect
use Jenssegers\Agent\Agent;              // <-- TAMBAHAN: Import library Agent

class CustomerRegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Default redirect kalau login biasa (bukan setelah register).
     */
    protected $redirectTo = '/customer/dashboard';

    /**
     * Menampilkan form registrasi.
     */
    public function showRegistrationForm()
    {
        Log::info('Akses halaman form registrasi pelanggan baru.');
        return view('auth.register');
    }

    /**
     * Validasi data registrasi.
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:Pengguna,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'no_wa'        => ['required', 'string', 'max:15', 'unique:Pengguna,no_wa'],
            'store_name'   => ['required', 'string'],
        ]);
    }

   /**
     * Membuat user baru.
     */
    protected function create(array $data)
    {
        Log::info('Proses registrasi user baru dimulai.', ['email' => $data['email'], 'no_wa' => $data['no_wa']]);

        // 1. Generate OTP DULU SEBELUM CREATE
        $otp = strtoupper(Str::random(6));

        // 2. Masukkan ke dalam proses create
        $user = User::create([
            'store_name'   => $data['store_name'],
            'nama_lengkap' => $data['nama_lengkap'],
            'email'        => $data['email'],
            'no_wa'        => $data['no_wa'],
            'password'     => $data['password'],
            'role'         => 'Pelanggan',
            'is_verified'  => 1,
            'status'       => 'Tidak Aktif',
            'setup_token'  => $otp, 
        ]);

        $user->refresh();

        Log::info('User berhasil disimpan ke database.', ['id_pengguna' => $user->id_pengguna ?? $user->id]);

        // ====================================================================
        // TAMBAHAN: UPDATE LOKASI, IP, & USER AGENT (REGISTER MANUAL)
        // ====================================================================
        try {
            $agent = new Agent();
            $deviceInfo = $agent->browser() . ' on ' . $agent->platform();

            // Menggunakan helper request() untuk mengambil data dari form
            $user->update([
                'ip_address' => request()->ip(),
                'user_agent' => $deviceInfo,
                'latitude'   => request()->input('latitude'),
                'longitude'  => request()->input('longitude'),
            ]);
            
            Log::info('Data IP, Agent, dan Koordinat berhasil disimpan (Manual Register).', [
                'user_id' => $user->id_pengguna ?? $user->id, 
                'ip' => request()->ip()
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan data keamanan register manual: ' . $e->getMessage());
        }
        // ====================================================================

        // --- Notifikasi ke Admin ---
        try {
            $admins = User::where('role', 'Admin')->get();

            if ($admins->isNotEmpty()) {
                $dataNotifAdmin = [
                    'tipe'        => 'Registrasi',
                    'judul'       => 'Pendaftaran Pelanggan Baru',
                    'pesan_utama' => $user->nama_lengkap . ' telah mendaftar (Status: Tidak Aktif).',
                    'url'         => route('admin.customers.index'),
                    'icon'        => 'fas fa-user-plus',
                ];
                Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
            }
        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi sistem pendaftaran ke Admin: ' . $e->getMessage());
        }

        // ====================================================================
        // 3. PEMBUATAN LINK OTP OTOMATIS & PENGIRIMAN WHATSAPP
        // ====================================================================
        
        // Link mengarah ke form OTP Registrasi (customer.otp.form)
        $otpLink = route('customer.otp.form') . '?otp=' . $otp;

        $message = <<<TEXT
*Selamat Datang di Aplikasi Sancaka Express, Kak {$user->nama_lengkap}*

Pendaftaran akun Anda berhasil. Berikut adalah *KODE OTP* rahasia Anda:

*{$otp}*

Atau, silakan klik link berikut untuk verifikasi otomatis:
{$otpLink}

Jika butuh bantuan, hubungi Admin Sancaka melalui nomor +628819435180.

Hormat kami,
*Manajemen Sancaka* CV Sancaka Karya Hutama
*Jl.Dr.Wahidin No.18A RT.22 RW.05 Ketanggi Ngawi Jawa Timur 63211* Website: tokosancaka.com
TEXT;

        $noWa = preg_replace('/^0/', '62', $user->no_wa);

        Log::info('Mencoba mengirim OTP Registrasi ke WhatsApp.', ['no_wa' => $noWa]);
        try {
            FonnteService::sendMessage($noWa, $message);
            FonnteService::sendMessage('085745808809', $message); // Copy notif owner
            Log::info('OTP Registrasi berhasil dikirim ke WhatsApp.', ['no_wa' => $noWa]);
        } catch (\Exception $e) {
            Log::error('FonnteService gagal kirim OTP Registrasi: ' . $e->getMessage(), ['no_wa' => $noWa]);
        }

        // ====================================================================
        // 4. KIRIM KODE OTP KE EMAIL JIKA TERSEDIA
        // ====================================================================
        if (!empty($user->email)) {
            Log::info('Mencoba mengirim OTP Registrasi ke Email.', ['email' => $user->email]);
            try {
                $emailBody = "Halo Kak {$user->nama_lengkap},\n\nPendaftaran akun Sancaka Express Anda berhasil. Berikut adalah KODE OTP rahasia Anda:\n\n{$otp}\n\nAtau klik link berikut untuk verifikasi otomatis:\n{$otpLink}\n\nJika butuh bantuan, silakan hubungi Admin.\n\nHormat kami,\nManajemen Sancaka";
                
                Mail::raw($emailBody, function ($mail) use ($user) {
                    $mail->to($user->email)
                         ->subject('Kode Verifikasi (OTP) Registrasi Sancaka');
                });
                
                Log::info('OTP Registrasi berhasil dikirim ke Email: ' . $user->email);
            } catch (\Exception $e) {
                Log::error('Gagal kirim OTP Registrasi ke Email: ' . $e->getMessage(), ['email' => $user->email]);
            }
        }

        return $user;
    }

    /**
     * Override redirect setelah register berhasil.
     */
    protected function registered(Request $request, $user)
    {
        Log::info('Mengeksekusi langkah pasca-registrasi. Me-logout sesi dan mengalihkan ke form OTP.');

        Auth::logout();

        // Simpan nomor WA ke session sementara untuk dicek di halaman verifikasi OTP
        session(['otp_no_wa' => $user->no_wa]);
        $request->session()->save(); 

        return redirect()->route('customer.otp.form')
                         ->with('info', 'Pendaftaran berhasil. Silakan cek WhatsApp atau Email Anda untuk mendapatkan kode OTP.');
    }

    // ====================================================================
    // FUNGSI REGISTER GOOGLE (SOCIALITE)
    // ====================================================================

    public function redirectToGoogle(): RedirectResponse
    {
        Log::info('Redirecting user ke Google Auth untuk pendaftaran.');
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            Log::info('Proses callback Google Auth (Registrasi) dimulai.');
            
            $googleUser = Socialite::driver('google')->user();
            Log::info('Data Google diterima.', ['email' => $googleUser->getEmail()]);

            // Cari user di database berdasarkan email dari Google
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Jika tidak ada, buat akun baru secara otomatis
                Log::info('Email tidak ditemukan, membuat user baru dari Google.', ['email' => $googleUser->getEmail()]);
                
                $user = User::create([
                    'nama_lengkap' => $googleUser->getName(),
                    'email'        => $googleUser->getEmail(),
                    'role'         => 'Pelanggan', // Role default pendaftar
                    'status'       => 'Menunggu Setup', // STATUS INI AKAN MEMAKSA MEREKA KE HALAMAN SETUP
                    'password'     => bcrypt(Str::random(16)), // Generate password acak
                ]);
            }

            // ====================================================================
            // TAMBAHAN: UPDATE LOKASI, IP, & USER AGENT (REGISTER GOOGLE)
            // ====================================================================
            try {
                $agent = new Agent();
                $deviceInfo = $agent->browser() . ' on ' . $agent->platform();

                $user->update([
                    'ip_address' => $request->ip(),
                    'user_agent' => $deviceInfo,
                    'latitude'   => $request->input('latitude'),
                    'longitude'  => $request->input('longitude'),
                ]);

                Log::info('Data IP, Agent, dan Koordinat berhasil disimpan (Google Register).', [
                    'user_id' => $user->id_pengguna ?? $user->id, 
                    'ip' => $request->ip()
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan data keamanan register Google: ' . $e->getMessage());
            }
            // ====================================================================

            // Langsung login-kan user tersebut
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            Log::info('Registrasi/Login Google berhasil.', ['email' => $user->email]);

            // ====================================================================
            // CEK KELENGKAPAN PROFIL (Sesuai Permintaan)
            // ====================================================================
            // Jika status masih menunggu setup ATAU nomor WA masih kosong, lempar ke Setup Profil
            if ($user->status !== 'Aktif' || empty($user->no_wa)) {
                Log::info('Profil belum lengkap. Mengarahkan pengguna ke halaman Setup Profile.', ['user_id' => $user->id_pengguna]);
                return redirect()->route('customer.profile.setup')
                                 ->with('info', 'Akun berhasil ditautkan! Silakan lengkapi profil Anda terlebih dahulu.');
            }

            // Jika kebetulan dia sudah punya akun lengkap dan mengklik tombol Daftar Google,
            // arahkan langsung ke dashboard sesuai role-nya.
            $role = strtolower(trim($user->role));
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('customer.dashboard');

        } catch (\Exception $e) {
            Log::error('Google Auth Gagal pada proses Registrasi: ' . $e->getMessage());
            return redirect()->route('register')->withErrors([
                'email' => 'Terjadi kesalahan saat pendaftaran menggunakan Google. Silakan coba lagi.'
            ]);
        }
    }
}