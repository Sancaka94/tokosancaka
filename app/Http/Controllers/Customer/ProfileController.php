<?php

// Namespace disesuaikan dengan lokasi folder baru
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller; // <-- Ini penting agar bisa extends Controller
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use App\Models\User;
 use Illuminate\Support\Facades\Auth;
 
class ProfileController extends Controller
{
    /**
     * Menampilkan halaman untuk melihat profil pengguna.
     */
    public function show(Request $request)
    {
        // ✅ DIPERBAIKI: Menggunakan variabel 'user' agar konsisten dengan method edit
        return view('customer.profile.show', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Menampilkan form untuk mengedit profil pengguna.
     */
       public function edit(Request $request) { 
           return view('customer.profile.edit', [ 'user' => $request->user(), ]); 
           
       }
       
     public function update(Request $request)
    {
        $user = $request->user();
    
        try {
            $validated = $request->validate([
                'nama_lengkap'        => ['required', 'string', 'max:255'],
                'no_wa'               => ['required', 'string', 'max:20'],
                'store_name'          => ['nullable', 'string', 'max:255'],
                'store_logo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
                'bank_name'           => ['nullable', 'string', 'max:255'],
                'bank_account_name'   => ['nullable', 'string', 'max:255'],
                'bank_account_number' => ['nullable', 'string', 'max:255'],
                'province'            => ['required', 'string', 'max:255'],
                'regency'             => ['required', 'string', 'max:255'],
                'district'            => ['required', 'string', 'max:255'],
                'village'             => ['required', 'string', 'max:255'],
                'postal_code'         => ['nullable', 'string', 'max:10'],
                'address_detail'      => ['required', 'string'],
            ]);
    
            if ($request->hasFile('store_logo')) {
                $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
                $user->store_logo_path = $path;
            }
    
            $user->fill($validated);
            $user->save();
    
            return redirect()
                ->route('customer.profile.show')
                ->with('success', 'Profil Anda berhasil diperbarui.');
        } catch (\Throwable $e) {
            \Log::error('Update profile gagal: '.$e->getMessage());
    
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui profil. Silakan coba lagi.'.$e->getMessage());
        }
    }
     

    public function setup(Request $request, $token)
    {
        $user = User::where('setup_token', $token)->firstOrFail();
    
        if ($user->id_pengguna !== auth()->id()) {
            Auth::logout();
            return redirect()->route('login')->with('success', 'AKTIVASI AKUN, silakan login terlebih dahulu.');
        }
    
        return view('customer.profile.setup', [
            'user' => $user
        ]);
    }


    /**
     * Memperbarui informasi profil pengguna.
     */
    public function updateSetup(Request $request, $token)
    {
        $user = User::where('setup_token', $token)->firstOrFail();
    
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'no_wa' => ['required', 'string', 'max:20'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'store_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'regency' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'village' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'address_detail' => ['required', 'string'],
        ]);
    
        if ($request->hasFile('store_logo')) {
            $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
            $user->store_logo_path = $path;
        }
    
        $user->fill($validated);
        $user->profile_setup_at = now();
    
        $user->status = 'Aktif';
        $user->setup_token = null;
    
        $user->save();
    
        return redirect()->route('customer.profile.show')
                         ->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
