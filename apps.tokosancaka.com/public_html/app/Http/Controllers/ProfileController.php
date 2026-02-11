<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage; // [PENTING] Tambahkan ini untuk hapus/upload foto
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile data (Read Only).
     */
    public function index(Request $request): View
    {
        return view('profile.index', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // 1. Isi data text dasar (Name, Email, Phone) dari validasi
        $request->user()->fill($request->validated());

        // 2. LOGIC UPLOAD LOGO / FOTO PROFIL
        if ($request->hasFile('logo')) {
            // A. Hapus foto lama jika ada (dan bukan default/avatar UI)
            if ($request->user()->logo) {
                Storage::disk('public')->delete($request->user()->logo);
            }

            // B. Simpan foto baru ke folder 'profile-photos' di storage public
            // Hasilnya path string: "profile-photos/namafile.jpg"
            $path = $request->file('logo')->store('profile-photos', 'public');

            // C. Update kolom logo di database user
            $request->user()->logo = $path;
        }

        // 3. LOGIC MANUAL NO WA (Opsional, jika tidak ter-cover oleh fill)
        // Pastikan 'phone' ada di rules ProfileUpdateRequest
        if ($request->has('phone')) {
            $request->user()->phone = $request->phone;
        }

        // 4. Reset Verifikasi Email jika Email berubah
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // 5. Simpan Perubahan
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // [OPSIONAL] Hapus foto profil saat akun dihapus
        if ($user->logo) {
            Storage::disk('public')->delete($user->logo);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
