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
    $request->validate([
        'owner_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'business_name' => 'required|string|max:255',
        'subdomain' => 'required|alpha_dash|unique:tenants,subdomain',
        'password' => 'required|min:8',
        'package' => 'required|in:trial,monthly,yearly', // Tambahkan ini
    ]);

    DB::beginTransaction();
    try {
        // Tentukan masa berlaku
        $days = 14; // Default trial
        if ($request->package == 'monthly') $days = 30;
        if ($request->package == 'yearly') $days = 365;

        // 1. Buat Tenant
        $tenant = Tenant::create([
            'name' => $request->business_name,
            'subdomain' => strtolower($request->subdomain),
            'category' => 'percetakan',
            'package' => $request->package,
            'expired_at' => now()->addDays($days),
            'status' => 'active',
        ]);

        // 2. Buat User Owner
        User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->owner_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'owner',
        ]);

        DB::commit();

        // Redirect ke login subdomain
        $targetUrl = 'http://' . $tenant->subdomain . '.tokosancaka.com/percetakan/login';
        return redirect()->away($targetUrl);

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}
}
