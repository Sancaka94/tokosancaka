<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MemberProfileController extends Controller
{
    public function index()
    {
        $member = Auth::guard('member')->user();
        return view('member.settings.index', compact('member'));
    }

    // Update Data Diri
    public function update(Request $request)
    {
        $member = Auth::guard('member')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|numeric',
        ]);

        $member->update([
            'name' => $request->name,
            'address' => $request->address,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
        ]);

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    // Ganti PIN
    public function updatePin(Request $request)
    {
        $request->validate([
            'current_pin' => 'required',
            'new_pin' => 'required|numeric|digits:6|confirmed', // Konfirmasi PIN baru
        ]);

        $member = Auth::guard('member')->user();

        // Cek PIN Lama
        if (!Hash::check($request->current_pin, $member->pin)) {
            return back()->withErrors(['current_pin' => 'PIN lama salah!']);
        }

        // Update PIN Baru
        $member->update([
            'pin' => Hash::make($request->new_pin)
        ]);

        return back()->with('success', 'PIN keamanan berhasil diganti!');
    }
}