<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TenantDashboardController extends Controller
{
    /**
     * READ: Tampilkan Dashboard & Info Paket
     */
public function index()
{
    $user = Auth::user();
    // Jika diakses dari apps. (pusat), kita ambil tenant dari relasi user
    $tenant = $user->tenant; 

    if (!$tenant) {
        // Jika user memang belum punya toko, arahkan ke form pendaftaran
        return redirect()->away('https://apps.tokosancaka.com/daftar-pos')
                         ->with('error', 'Anda belum memiliki toko.');
    }

    // Hitung sisa hari
    $daysLeft = $tenant->expired_at ? now()->diffInDays($tenant->expired_at, false) : 0;

    return view('tenant.dashboard_area', compact('user', 'tenant', 'daysLeft'));
}

    /**
     * UPDATE: Tampilkan Form Edit Profil Toko
     */
    public function edit(Request $request)
    {
        $user = Auth::user();
        $tenant = $request->get('tenant');
        
        return view('tenant.settings', compact('user', 'tenant'));
    }

    /**
     * UPDATE: Simpan Perubahan Data
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $tenant = $request->get('tenant');

        $request->validate([
            'owner_name'    => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'whatsapp'      => 'required|string|max:20',
            'password'      => 'nullable|min:8|confirmed', 
        ]);

        // Update User (Owner)
        $user->update([
            'name' => $request->owner_name,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
        ]);

        // Update Tenant (Bisnis)
        $tenant->update([
            'name'     => $request->business_name,
            'whatsapp' => $request->whatsapp,
        ]);

        // FIX: Sertakan parameter 'subdomain' agar tidak UrlGenerationException
        return redirect()->route('tenant.dashboard', ['subdomain' => $tenant->subdomain])
                         ->with('success', 'Profil berhasil diperbarui!');
    }
}