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
        $tenant = $user->tenant; // Mengambil data tenant milik user ini

        if (!$tenant) {
            return redirect()->back()->with('error', 'Data Tenant tidak ditemukan.');
        }

        // Hitung sisa hari
        $daysLeft = 0;
        if ($tenant->expired_at) {
            $daysLeft = Carbon::now()->diffInDays(Carbon::parse($tenant->expired_at), false);
        }

        return view('dashboard', compact('user', 'tenant', 'daysLeft'));
    }

    /**
     * UPDATE: Tampilkan Form Edit Profil Toko
     */
    public function edit()
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        return view('tenant.settings', compact('user', 'tenant'));
    }

    /**
     * UPDATE: Simpan Perubahan Data
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $request->validate([
            'owner_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:20',
            'password' => 'nullable|min:8|confirmed', // Opsional ganti password
        ]);

        // Update User (Owner)
        $user->name = $request->owner_name;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // Update Tenant (Bisnis)
        $tenant->update([
            'name' => $request->business_name,
            'whatsapp' => $request->whatsapp,
            // Subdomain & Paket biasanya tidak boleh diedit user sembarangan
        ]);

        return redirect()->route('tenant.dashboard')->with('success', 'Profil berhasil diperbarui!');
    }
}
