<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register-tenant'); // Buatkan view blade ini nanti
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:100|unique:tenants,subdomain|alpha_dash',
            'admin_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 1. Buat Tenant
        $tenant = Tenant::create([
            'name' => $request->company_name,
            'subdomain' => strtolower($request->subdomain),
        ]);

        // 2. Buat User Admin untuk Tenant Tersebut
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->admin_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        // Login otomatis (opsional)
        // auth()->login($user);

        return redirect("http://{$tenant->subdomain}.sancaka.my.id/login")->with('success', 'Pendaftaran berhasil. Silakan login di subdomain Anda.');
    }
}
