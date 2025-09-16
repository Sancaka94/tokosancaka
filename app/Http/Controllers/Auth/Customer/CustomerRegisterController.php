<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

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
            'store_name'        => ['required', 'string'],
        ]);
    }

    /**
     * Membuat user baru.
     */
    protected function create(array $data)
    {
        $user = User::create([
            'store_name' => $data['store_name'],
            'nama_lengkap' => $data['nama_lengkap'],
            'email'        => $data['email'],
            'no_wa'        => $data['no_wa'],
            'password'     => $data['password'], // hash di mutator User
            'role'         => 'Pelanggan',
            'is_verified'  => 1,
            'status'       => 'Tidak Aktif',
        ]);

        // Generate token setup
        $token = Str::random(40);
        $user->setup_token = $token;
        $user->save();

        $link = url("/customer/profile/setup/{$token}");
        $message = <<<TEXT
*Selamat Datang di Aplikasi Sancaka Express, Kak {$user->nama_lengkap}*

Apabila Anda mengalami kendala atau memiliki pertanyaan, silakan hubungi Admin Sancaka melalui nomor *0881-9435-180*.

Berikut adalah Link Pendaftaran Kakak {$user->nama_lengkap}, kemudian agar pendaftaran berhasil klik link di bawah ini dan lengkapi datanya:

{$link}

Hormat kami,  
*Manajemen Sancaka*  
CV Sancaka Karya Hutama  
*Jl.Dr.Wahidin No.18A RT.22 RW.05 Ketanggi Ngawi Jawa Timur 63211*  
Website: tokosancaka.com
TEXT;

        $noWa = preg_replace('/^0/', '62', $user->no_wa);
        \App\Services\FonnteService::sendMessage($noWa, $message);

        return $user; 
    }

    /**
     * Override redirect setelah register berhasil.
     */
    protected function registered(Request $request, $user)
    {
        return redirect()->route('register.success', ['no_wa' => $user->no_wa]);
    }
}
