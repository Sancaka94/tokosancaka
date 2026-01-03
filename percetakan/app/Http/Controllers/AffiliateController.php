<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Affiliate;
use App\Models\Coupon;
use App\Services\FonnteService;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Pastikan library sudah diinstall

class AffiliateController extends Controller
{
    public function index()
    {
        // 1. Ambil data afiliasi
        $affiliates = Affiliate::with(['coupon.orders' => function($query) {
            $query->where('payment_status', 'paid'); 
        }])->latest()->get();

        // 2. Hitung Ringkasan
        $totalAffiliates = $affiliates->count();
        $totalTransactions = 0;
        $totalRevenueGenerated = 0;

        foreach($affiliates as $aff) {
            if($aff->coupon) {
                $orders = $aff->coupon->orders;
                $totalTransactions += $orders->count();
                $totalRevenueGenerated += $orders->sum('final_price');
            }
        }

        // ============================================================
        // 3. GENERATE QR CODE PENDAFTARAN (Untuk Admin Sebarkan)
        // ============================================================
        // Link statis sesuai request Anda
        $registerUrl = 'https://tokosancaka.com/percetakan/public/join-partner'; 
        
        // Generate QR Code (Ukuran 150px)
        $qrRegister = QrCode::size(150)->generate($registerUrl);

        return view('affiliate.index', compact(
            'affiliates', 
            'totalAffiliates', 
            'totalTransactions', 
            'totalRevenueGenerated',
            'qrRegister', // Kirim variable QR gambar
            'registerUrl' // Kirim variable Link text
        ));
    }
    
    // Halaman Form Pendaftaran (Pastikan route-nya '/join-partner')
    public function create()
    {
        return view('affiliate.register');
    }

    public function store(Request $request) 
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
            // Generate Kode Unik
            $firstName = explode(' ', trim($request->name))[0];
            $cleanName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $firstName));
            $couponCode = 'DISKON-' . $cleanName . rand(100, 999);

            // Simpan Data
            $affiliate = Affiliate::create([
                'name' => $request->name,
                'address' => $request->address,
                'whatsapp' => $request->whatsapp,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'coupon_code' => $couponCode,
            ]);

            // Buat Kupon
            Coupon::create([
                'code' => $couponCode,
                'type' => 'percent', 
                'value' => 5, // Diskon 5%
                'start_date' => now(),
                'expiry_date' => now()->addYears(5), 
                'description' => 'Kupon Afiliasi dari ' . $request->name
            ]);

            // Siapkan Link Toko Anda
            $shopLink = "https://tokosancaka.com/percetakan/public/";

            // Pesan WA
            $message = "Halo {$request->name}! 👋\n\n";
            $message .= "Selamat! Anda resmi menjadi Partner Afiliasi Kami.\n\n";
            $message .= "Berikut adalah KODE KUPON Khusus Anda:\n";
            $message .= "*{$couponCode}*\n\n";
            $message .= "Link Website Toko:\n" . $shopLink . "\n\n"; 
            $message .= "Sebarkan kode ini. Setiap orang yang membeli menggunakan kode ini akan mendapat diskon, dan Anda akan mendapatkan komisi!\n\n";
            $message .= "Semangat Cuan! 🚀";

            // Kirim WA
            FonnteService::sendMessage($request->whatsapp, $message);

            DB::commit();

            return redirect()->back()->with('success', 'Pendaftaran Berhasil! Kode Kupon telah dikirim ke WhatsApp Anda.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Method Cetak QR Code Spesifik untuk Member
     */
    public function printQr($id)
    {
        $affiliate = Affiliate::findOrFail($id);

        // ============================================================
        // LINK KHUSUS MEMBER (Domain Anda + Parameter Kupon)
        // Hasil: https://tokosancaka.com/percetakan/public/?coupon=KODEUNIK
        // ============================================================
        $baseUrl = 'https://tokosancaka.com/percetakan/public/';
        $shopLinkWithCoupon = $baseUrl . '?coupon=' . $affiliate->coupon_code;

        // Generate QR Code berisi Link Member
        $qrCode = QrCode::size(300)->generate($shopLinkWithCoupon);

        return view('affiliate.print_qr', compact('affiliate', 'qrCode', 'shopLinkWithCoupon'));
    }
}