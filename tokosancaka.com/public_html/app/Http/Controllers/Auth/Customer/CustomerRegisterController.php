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
        $user = User::create([
            'store_name'   => $data['store_name'],
            'nama_lengkap' => $data['nama_lengkap'],
            'email'        => $data['email'],
            'no_wa'        => $data['no_wa'],
            'password'     => $data['password'], // hash di mutator User
            'role'         => 'Pelanggan',
            'is_verified'  => 1,
            'status'       => 'Tidak Aktif',
        ]);

        // 🔑 Generate OTP 6 Karakter (Kombinasi Huruf Kapital & Angka)
        $otp = strtoupper(Str::random(6));
        $user->setup_token = $otp;
        $user->save();

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
            Log::error('Gagal mengirim notifikasi pendaftaran: ' . $e->getMessage());
        }
        // --- SELESAI TAMBAHAN KODE ---

        // 🔑 Pesan WhatsApp dikirim berupa OTP, BUKAN link setup lagi
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

        // Kirim Pesan WA
        FonnteService::sendMessage($noWa, $message);
        FonnteService::sendMessage('085745808809', $message);

        return $user;
    }

    /**
     * Override redirect setelah register berhasil.
     */
    protected function registered(Request $request, $user)
    {
        // 1. Logout paksa bawaan Laravel agar tidak ter-login otomatis (Mencegah error merah)
        Auth::logout();

        // 2. Simpan nomor WA ke session sementara untuk dicek di halaman verifikasi OTP
        session(['otp_no_wa' => $user->no_wa]);

        // 3. Arahkan langsung ke halaman form verifikasi OTP
        return redirect()->route('customer.otp.form');
    }
}
