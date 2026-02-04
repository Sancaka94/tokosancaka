<?php



namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\Rule;




class LoginController extends Controller

{

    /**

     * Menampilkan form login terpadu.

     */

    public function showLoginForm()

    {

        return view('auth.login');

    }



    /**

     * Menangani proses login untuk admin dan customer.

     */

    public function login(Request $request)

    {

        // 1. Validasi input, termasuk tipe login

        $request->validate([

            'email' => 'required|email',

            'password' => 'required|string',

            'type' => ['required', Rule::in(['customer', 'admin'])],

        ]);



        $credentials = $request->only('email', 'password');

        $remember = $request->filled('remember');



        // 2. Tentukan guard dan redirect path berdasarkan tipe

        if ($request->type === 'admin') {

            $guard = 'admin';

            $redirectRoute = 'admin.dashboard';

        } else {

            $guard = 'web';

            $redirectRoute = 'home';

        }



        // 3. Coba lakukan login

        if (Auth::guard($guard)->attempt($credentials, $remember)) {

            $request->session()->regenerate();

            return redirect()->intended(route($redirectRoute));

        }



        // 4. Jika gagal, kembali dengan pesan error

        return back()->withErrors([

            'email' => 'Email atau password yang Anda masukkan salah.',

        ])->withInput($request->only('email', 'remember', 'type'));

    }



    /**

     * Menangani proses logout customer (logout admin punya controller sendiri).

     */

    public function logout(Request $request)

    {

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');

    }
    
    /**
     * [BARU] Menambahkan pengecekan status 'Aktif' saat login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        // Ambil kredensial default (biasanya email & password)
        $credentials = $request->only($this->username(), 'password');
        
        // Tambahkan syarat kustom: status harus 'Aktif'
        $credentials['status'] = 'Aktif'; 
        
        return $credentials;
    }

}

