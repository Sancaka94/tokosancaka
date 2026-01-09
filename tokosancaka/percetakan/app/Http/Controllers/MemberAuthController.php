<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order; // Pastikan Model Order diimport

class MemberAuthController extends Controller
{
    /**
     * 1. Tampilkan Halaman Form Login
     */
    public function showLoginForm()
    {
        // Cek jika sudah login, langsung lempar ke dashboard
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.dashboard');
        }

        return view('member.auth.login');
    }

    /**
     * 2. Proses Eksekusi Login
     */
    public function login(Request $request)
    {
        // Validasi Input
        $request->validate([
            'whatsapp' => 'required|numeric',
            'pin'      => 'required|string',
        ]);

        // Siapkan Credentials
        // PENTING: Key 'password' di sini wajib ada karena Auth Laravel
        // akan menggunakan value ini untuk dicocokkan dengan hash di database.
        // Walaupun kolom database namanya 'pin', input user tetap kita labeli 'password' di array ini.
        $credentials = [
            'whatsapp' => $request->whatsapp,
            'password' => $request->pin, 
            'is_active' => 1 // Opsional: Pastikan hanya member aktif yg bisa masuk
        ];

        // Coba Login menggunakan Guard 'member'
        // $request->filled('remember') mengecek checkbox "Ingat Saya"
        if (Auth::guard('member')->attempt($credentials, $request->filled('remember'))) {
            
            // Regenerasi Session ID untuk keamanan (Fixation Attack)
            $request->session()->regenerate();

            // Redirect ke dashboard member
            return redirect()->intended(route('member.dashboard'));
        }

        // Jika Gagal Login (Balik ke halaman login dengan error)
        return back()->withErrors([
            'whatsapp' => 'Nomor WhatsApp atau PIN salah, atau akun dinonaktifkan.',
        ])->withInput($request->only('whatsapp'));
    }

    /**
     * 3. Halaman Dashboard Member
     */
    public function dashboard()
    {
        // Ambil data member yang sedang login dari guard 'member'
        $member = Auth::guard('member')->user();

        // Ambil Riwayat Pesanan
        // Logika: Mencari order dimana 'customer_phone' sama dengan 'whatsapp' member
        $orders = Order::where('customer_phone', $member->whatsapp)
                       ->orderBy('created_at', 'desc')
                       ->take(10) // Ambil 10 transaksi terakhir
                       ->get();

        return view('member.dashboard', compact('member', 'orders'));
    }

    /**
     * 4. Proses Logout
     */
    public function logout(Request $request)
    {
        // Logout hanya dari guard 'member'
        Auth::guard('member')->logout();

        // Invalidate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect ke halaman login member
        return redirect()->route('member.login');
    }
}