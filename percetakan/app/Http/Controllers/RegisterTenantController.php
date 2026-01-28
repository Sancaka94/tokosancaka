<?php

namespace App\Http\Controllers;

use App\Models\User;   // Pastikan Model ini mengarah ke tabel users POS
use App\Models\Tenant; // Pastikan Model ini mengarah ke tabel tenants POS
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterTenantController extends Controller
{
    public function showForm()
    {
        return view('daftar-percetakan');
    }

    public function register(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|max:255', // Cek unique manual jika perlu
            'business_name' => 'required|string|max:255',
            'subdomain' => 'required|alpha_dash', // Kita cek unique di blok try-catch
            'password' => 'required|min:8',
        ]);

        DB::beginTransaction();
        try {
            // Cek manual apakah subdomain sudah dipakai di tabel tenants
            // (Asumsi Tenant model ada di app ini atau disetting koneksinya)
            if (Tenant::where('subdomain', $request->subdomain)->exists()) {
                return back()->withErrors(['subdomain' => 'Subdomain ini sudah digunakan orang lain.']);
            }

            // 2. Buat Tenant Baru
            $tenant = Tenant::create([
                'name' => $request->business_name,
                'subdomain' => strtolower($request->subdomain),
                'category' => 'percetakan',
            ]);

            // 3. Buat User Owner Baru
            User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->owner_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
            ]);

            DB::commit();

            // 4. Redirect ke Subdomain Baru
            // Contoh: https://gemini.tokosancaka.com/percetakan/login
            $protocol = $request->secure() ? 'https://' : 'http://';
            $targetUrl = $protocol . $tenant->subdomain . '.tokosancaka.com/percetakan/login';

            return redirect()->away($targetUrl);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mendaftar: ' . $e->getMessage());
        }
    }
}
