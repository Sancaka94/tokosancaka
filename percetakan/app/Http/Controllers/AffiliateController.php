<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Affiliate; // Buat model ini nanti
use App\Models\Coupon;
use App\Services\FonteeService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AffiliateController extends Controller
{
    // Halaman Form Publik
    public function create()
    {
        return view('affiliate.register');
    }

    // Proses Simpan
    public function store(Request $request, FonteeService $fontee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'whatsapp' => 'required|numeric|unique:affiliates,whatsapp',
            'bank_name' => 'required|string',
            'bank_account_number' => 'required|numeric',
        ], [
            'whatsapp.unique' => 'Nomor WhatsApp ini sudah terdaftar sebagai afiliasi.',
        ]);

        DB::beginTransaction();
        try {
            // 1. Generate Kode Unik
            // Ambil nama depan, hapus spasi, tambah angka random
            $firstName = explode(' ', trim($request->name))[0];
            $cleanName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $firstName));
            $couponCode = 'DISKON-' . $cleanName . rand(100, 999);

            // 2. Simpan Data Afiliator
            $affiliate = Affiliate::create([
                'name' => $request->name,
                'address' => $request->address,
                'whatsapp' => $request->whatsapp,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'coupon_code' => $couponCode,
            ]);

            // 3. Daftarkan Kode ke Tabel Coupon (Agar bisa dipakai buyer)
            Coupon::create([
                'code' => $couponCode,
                'type' => 'percent', // Misal diskon persen
                'value' => 5,        // Diskon 5% untuk pembeli
                'description' => 'Kupon Afiliasi dari ' . $request->name
            ]);

            // 4. Siapkan Pesan WA
            $message = "Halo {$request->name}! 👋\n\n";
            $message .= "Selamat! Anda resmi menjadi Partner Afiliasi Kami.\n\n";
            $message .= "Berikut adalah KODE KUPON Khusus Anda:\n";
            $message .= "*{$couponCode}*\n\n";
            $message .= "Sebarkan kode ini. Setiap orang yang membeli menggunakan kode ini akan mendapat diskon, dan Anda akan mendapatkan komisi!\n\n";
            $message .= "Semangat Cuan! 🚀";

            // 5. Kirim WA via Fontee
            $fontee->sendMessage($request->whatsapp, $message);

            DB::commit();

            return redirect()->back()->with('success', 'Pendaftaran Berhasil! Kode Kupon telah dikirim ke WhatsApp Anda.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }
}