<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PpobTransaction; // Gunakan model transaksi untuk mencatat potongan

class AgentRegistrationController extends Controller
{
    public function index()
{
    // Get ID of currently logged in user
    $userId = Auth::id(); 
    
    // Fetch fresh data from DB to ensure balance is 100% accurate real-time
    $user = \App\Models\User::find($userId); 

    if ($user->role === 'agent') {
        return redirect()->route('agent.products.index');
    }

    return view('customer.agent_registration.index', compact('user'));
}

public function register(Request $request)
{
    $userId = Auth::id();
    // Lock header for transaction safety if high concurrency (optional but good)
    $user = \App\Models\User::where('id_pengguna', $userId)->first();
        $syaratSaldo = 2000000; // 2 Juta
        $biayaServer = 100000;  // 100 Ribu

        // 1. Cek Apakah Saldo Cukup
        if ($user->saldo < $syaratSaldo) {
            return redirect()->back()->with('error', 'Saldo tidak mencukupi. Minimal saldo Rp ' . number_format($syaratSaldo) . ' untuk mendaftar.');
        }

        DB::beginTransaction();
        try {
            // 2. Potong Saldo 100.000
            $user->decrement('saldo', $biayaServer);

            // 3. Update Role User jadi 'agent'
            $user->update(['role' => 'agent']);

            // 4. Catat Riwayat Transaksi (Agar user tau uangnya kemana)
            // Sesuaikan dengan struktur tabel transaksi Anda
            // Contoh menggunakan tabel mutation atau transaction log
            // Disini saya pakai contoh log sederhana, sesuaikan dengan tabel Anda
            
            // Opsi: Simpan log mutasi saldo (jika ada tabel mutasi)
            // Mutation::create([...]); 

            DB::commit();

            return redirect()->route('agent.products.index')->with('success', 'Selamat! Anda sekarang adalah Agen Resmi Sancaka.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}