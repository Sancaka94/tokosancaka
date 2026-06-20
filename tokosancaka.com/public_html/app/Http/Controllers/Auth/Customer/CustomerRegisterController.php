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
use Illuminate\Support\Facades\Auth; // 🔑 Wajib ditambahkan untuk mematikan auto-login
use Illuminate\Support\Facades\Mail; // 🔑 TAMBAHAN IMPORT UNTUK FITUR KIRIM EMAIL

use App\Notifications\NotifikasiUmum;

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
            'setup_token'  => $otp, // 🔑 MASUKKAN OTP DI SINI
        ]);

        Log::info('User berhasil disimpan ke database.', ['id_pengguna' => $user->id_pengguna ?? $user->id]);

        // --- Notifikasi ke Admin (Kode Tetap) ---
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
        // 3. KIRIM KODE OTP KE WHATSAPP (Via Fonnte)
        // ====================================================================
        $message = <<<TEXT
*Selamat Datang di Aplikasi Sancaka Express, Kak {$user->nama_lengkap}*

Pendaftaran akun Anda berhasil. Berikut adalah *KODE OTP* rahasia Anda:

*{$otp}*

Silakan masukkan 6 digit kode OTP di atas pada halaman verifikasi untuk melanjutkan pengaturan profil Anda. Jika butuh bantuan, hubungi Admin Sancaka melalui nomor +628819435180.

Hormat kami,
*Manajemen Sancaka* CV Sancaka Karya Hutama
*Jl.Dr.Wahidin No.18A RT.22 RW.05 Ketanggi Ngawi Jawa Timur 63211* Website: tokosancaka.com
TEXT;

        $noWa = preg_replace('/^0/', '62', $user->no_wa);

        Log::info('Mencoba mengirim OTP Registrasi ke WhatsApp.', ['no_wa' => $noWa]);
        try {
            FonnteService::sendMessage($noWa, $message);
            FonnteService::sendMessage('085745808809', $message); // (Opsional) Mengirim copy notifikasi ke owner
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
                $emailBody = "Halo Kak {$user->nama_lengkap},\n\nPendaftaran akun Sancaka Express Anda berhasil. Berikut adalah KODE OTP rahasia Anda:\n\n{$otp}\n\nSilakan masukkan 6 digit kode OTP di atas pada halaman verifikasi untuk melanjutkan. Jika butuh bantuan, silakan hubungi Admin.\n\nHormat kami,\nManajemen Sancaka";
                
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

        // 1. Logout paksa bawaan Laravel agar tidak ter-login otomatis (Mencegah error merah)
        Auth::logout();

        // 2. Simpan nomor WA ke session sementara untuk dicek di halaman verifikasi OTP
        session(['otp_no_wa' => $user->no_wa]);
        $request->session()->save(); // Pastikan sesi tersimpan instan ke server

        // 3. Arahkan langsung ke halaman form verifikasi OTP (Milik Registrasi)
        return redirect()->route('customer.otp.form')
                         ->with('info', 'Pendaftaran berhasil. Silakan cek WhatsApp atau Email Anda untuk mendapatkan kode OTP.');
    }
}