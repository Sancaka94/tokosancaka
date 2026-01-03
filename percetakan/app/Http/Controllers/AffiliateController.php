<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Affiliate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AffiliateController extends Controller
{
    public function index()
    {
        return view('affiliate.register'); // Sesuaikan nama file view Anda
    }

    // 1. DAFTAR BARU
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'whatsapp' => 'required|unique:affiliates,whatsapp',
            'pin' => 'required|numeric|digits:6', // PIN Wajib saat daftar
        ]);

        // Logic simpan data baru (sesuaikan dengan logic Anda)
        Affiliate::create([
            'name' => $request->name,
            'whatsapp' => $request->whatsapp,
            'address' => $request->address,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'pin' => Hash::make($request->pin), // Hash PIN
            'balance' => 0
        ]);

        return redirect()->back()->with('success', 'Pendaftaran Berhasil! Silakan ingat PIN Anda.');
    }

    // 2. CEK AKUN (VALIDASI WA & PIN UNTUK EDIT)
    public function checkAccountPublic(Request $request)
    {
        $request->validate([
            'whatsapp' => 'required',
            'pin' => 'required'
        ]);

        // Cari berdasarkan WA
        $affiliate = Affiliate::where('whatsapp', $request->whatsapp)->first();

        if (!$affiliate) {
            return response()->json(['status' => 'error', 'message' => 'Nomor WhatsApp tidak ditemukan.'], 404);
        }

        // Cek PIN
        if (!Hash::check($request->pin, $affiliate->pin)) {
            return response()->json(['status' => 'error', 'message' => 'PIN Salah!'], 401);
        }

        // Jika Sukses, kembalikan data untuk diisi ke Form
        return response()->json([
            'status' => 'success',
            'data' => $affiliate
        ]);
    }

    // 3. UPDATE DATA (PUBLIC DENGAN VALIDASI PIN ULANG)
    public function updateAccountPublic(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:affiliates,id',
            'verification_pin' => 'required', // PIN Lama harus dikirim lagi untuk keamanan
            'name' => 'required',
            'whatsapp' => 'required',
            // Validasi lainnya...
        ]);

        $affiliate = Affiliate::find($request->id);

        // Security Check Terakhir: Pastikan PIN benar sebelum update DB
        // Ini mencegah orang memanipulasi ID via Inspect Element
        if (!Hash::check($request->verification_pin, $affiliate->pin)) {
            return redirect()->back()->with('error', 'Validasi Gagal: PIN Keamanan tidak cocok.');
        }

        // Update Data
        $affiliate->update([
            'name' => $request->name,
            'whatsapp' => $request->whatsapp, // Jika ganti WA, pastikan unique check di validasi
            'address' => $request->address,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
        ]);
        
        // Jika User mau ganti PIN Baru (Optional)
        if($request->filled('new_pin')) {
             $request->validate(['new_pin' => 'numeric|digits:6']);
             $affiliate->update(['pin' => Hash::make($request->new_pin)]);
        }

        return redirect()->back()->with('success', 'Data Berhasil Diperbarui!');
    }
}