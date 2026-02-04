<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // --- MULAI TAMBAHAN KODE ---
        // Ambil data tenant yang sudah disiapkan oleh TenantMiddleware
        $tenantId = null;

        // Cek apakah ada data 'current_tenant' di request (dari middleware)
        if ($request->current_tenant) {
            // Karena di middleware disimpan sebagai array: (array) $tenant
            $tenantData = $request->current_tenant;
            $tenantId = $tenantData['id'] ?? null;
        }
        // --- SELESAI TAMBAHAN KODE ---

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenantId, // <--- Masukkan ID tenant ke sini
            'role' => 'customer', // <--- Default user yang daftar sendiri adalah Customer
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
