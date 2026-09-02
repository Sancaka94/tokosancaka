<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NotaExport;

class NotaController extends Controller
{
    public function index()
    {
        $notas = Nota::with('items')->orderBy('created_at', 'desc')->paginate(10);
        return view('nota.index', compact('notas'));
    }

    public function create()
    {
        $no_nota = 'NOTA-' . date('Ymd') . '-' . rand(1000, 9999);
        return view('nota.create', compact('no_nota'));
    }

   public function store(Request $request)
    {
        $request->validate([
            'no_nota'       => 'required|unique:notas',
            'kepada'        => 'required|string|max:255',
            'tanggal'       => 'required|date',
            'nama_pembeli'  => 'required|string|max:255',
            'no_hp_pembeli' => 'required|string|min:9|max:20',
            'email_pembeli' => 'nullable|email|max:150', // Tambahan Validasi Email
            'nama_penjual'  => 'required|string|max:255',
            'ttd_pembeli'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ttd_penjual'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'barang.*.nama'      => 'required|string',
            'barang.*.banyaknya' => 'required|numeric|min:1',
            'barang.*.harga'     => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $path_ttd_pembeli = $request->hasFile('ttd_pembeli') ? $request->file('ttd_pembeli')->store('uploads/ttd', 'public') : null;
            $path_ttd_penjual = $request->hasFile('ttd_penjual') ? $request->file('ttd_penjual')->store('uploads/ttd', 'public') : null;

            $nota = Nota::create([
                'no_nota'       => $request->no_nota,
                'kepada'        => $request->kepada,
                'tanggal'       => $request->tanggal,
                'nama_pembeli'  => $request->nama_pembeli,
                'no_hp_pembeli' => $request->no_hp_pembeli,
                'email_pembeli' => $request->email_pembeli, // Simpan Email
                'nama_penjual'  => $request->nama_penjual,
                'ttd_pembeli'   => $path_ttd_pembeli,
                'ttd_penjual'   => $path_ttd_penjual,
                'total_harga'   => 0,
            ]);

            $total_harga = 0;

            foreach ($request->barang as $item) {
                $jumlah = $item['banyaknya'] * $item['harga'];
                $total_harga += $jumlah;

                NotaItem::create([
                    'nota_id'     => $nota->id,
                    'nama_barang' => $item['nama'],
                    'banyaknya'   => $item['banyaknya'],
                    'harga'       => $item['harga'],
                    'jumlah'      => $jumlah,
                ]);
            }

            $nota->update(['total_harga' => $total_harga]);

            DB::commit();

            // ========================================================
            // 🔥 LOGIKA KIRIM EMAIL OTOMATIS JIKA EMAIL DIISI 🔥
            // ========================================================
            if (!empty($request->email_pembeli)) {
                try {
                    $paymentLink = url('/nota/pay/' . $nota->no_nota);
                    $subject = "Tagihan Pembayaran Nota #" . $nota->no_nota . " - SANCAKA EXPRESS";

                    // Ekstraksi PIN 4 angka terakhir nomor HP
                    $hpPembeli = preg_replace('/[^0-9]/', '', $nota->no_hp_pembeli);
                    $pinRahasia = substr($hpPembeli, -4);
                    if (strlen($pinRahasia) < 4) $pinRahasia = str_pad($pinRahasia, 4, '0', STR_PAD_LEFT);

                    // Desain Email HTML (Responsif)
                    $bodyHtml = "
                    <div style='font-family: Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;'>
                        <div style='background: #111827; padding: 20px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0; font-size: 20px;'>SANCAKA EXPRESS</h2>
                        </div>
                        <div style='padding: 30px;'>
                            <p style='color: #374151; font-size: 16px;'>Halo <strong>{$nota->nama_pembeli}</strong>,</p>
                            <p style='color: #4b5563; line-height: 1.5;'>Berikut adalah tagihan pembayaran untuk pesanan Anda dengan nomor nota <strong>{$nota->no_nota}</strong>.</p>

                            <div style='background-color: #f3f4f6; padding: 20px; border-radius: 8px; text-align: center; margin: 25px 0;'>
                                <p style='margin: 0; color: #6b7280; font-size: 14px;'>Total Tagihan</p>
                                <p style='margin: 5px 0 0 0; color: #dc2626; font-size: 24px; font-weight: bold;'>Rp " . number_format($nota->total_harga, 0, ',', '.') . "</p>
                            </div>

                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$paymentLink}' target='_blank' style='background-color: #10b981; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block;'>Lihat & Bayar Tagihan</a>
                            </div>

                            <div style='background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 4px;'>
                                <p style='margin: 0; color: #92400e; font-size: 14px;'><strong>PENTING:</strong> Halaman tagihan diamankan menggunakan PIN.</p>
                                <p style='margin: 5px 0 0 0; color: #b45309; font-size: 14px;'>Gunakan <strong>4 Angka Terakhir Nomor HP Anda</strong> ({$pinRahasia}) untuk membuka tagihan.</p>
                            </div>
                        </div>
                        <div style='background-color: #f9fafb; padding: 15px; text-align: center; border-top: 1px solid #e5e7eb;'>
                            <p style='margin: 0; color: #9ca3af; font-size: 12px;'>Terima kasih telah mempercayakan layanan Sancaka Express.</p>
                        </div>
                    </div>";

                    // Eksekusi Kirim Email via SMTP
                    \Illuminate\Support\Facades\Mail::html($bodyHtml, function ($message) use ($request, $subject) {
                        $message->to($request->email_pembeli)
                                ->subject($subject)
                                ->from(config('mail.from.address', 'admin@tokosancaka.com'), config('mail.from.name', 'Admin Sancaka'));
                    });

                    // (Opsional & Direkomendasikan) Simpan log ini ke kotak 'Sent' di fitur Email Admin Anda
                    \App\Models\Email::create([
                        'user_id'      => \Illuminate\Support\Facades\Auth::id(),
                        'folder'       => 'sent',
                        'from_name'    => config('mail.from.name', 'Admin Sancaka'),
                        'from_address' => config('mail.from.address', 'admin@tokosancaka.com'),
                        'to_address'   => $request->email_pembeli,
                        'subject'      => $subject,
                        'body'         => $bodyHtml,
                        'is_starred'   => false,
                        'read_at'      => now(),
                    ]);

                    \Illuminate\Support\Facades\Log::info("Email Nota $nota->no_nota sukses dikirim ke {$request->email_pembeli}");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal kirim email invoice: ' . $e->getMessage());
                }
            }

            // Tambahkan success_nota_no untuk kebutuhan Link URL di frontend
            return back()
                ->with('success', 'Nota berhasil dibuat! ' . (!empty($request->email_pembeli) ? 'Link tagihan telah dikirim ke Email pelanggan.' : 'Kirim link pembayaran secara manual.'))
                ->with('success_nota_no', $nota->no_nota)
                ->with('success_nota_id', $nota->id);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $nota = Nota::with('items')->findOrFail($id);
        return view('nota.show', compact('nota'));
    }

    public function edit($id)
    {
        $nota = Nota::with('items')->findOrFail($id);
        return view('nota.edit', compact('nota'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kepada'        => 'required|string|max:255',
            'tanggal'       => 'required|date',
            'nama_pembeli'  => 'required|string|max:255',
            'no_hp_pembeli' => 'required|string|min:9|max:20', // Tambahan No HP
            'nama_penjual'  => 'required|string|max:255',
            'ttd_pembeli'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ttd_penjual'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'barang.*.nama'      => 'required|string',
            'barang.*.banyaknya' => 'required|numeric|min:1',
            'barang.*.harga'     => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            $nota = Nota::findOrFail($id);

            if ($request->hasFile('ttd_pembeli')) {
                if ($nota->ttd_pembeli) Storage::disk('public')->delete($nota->ttd_pembeli);
                $nota->ttd_pembeli = $request->file('ttd_pembeli')->store('uploads/ttd', 'public');
            }

            if ($request->hasFile('ttd_penjual')) {
                if ($nota->ttd_penjual) Storage::disk('public')->delete($nota->ttd_penjual);
                $nota->ttd_penjual = $request->file('ttd_penjual')->store('uploads/ttd', 'public');
            }

            $nota->kepada = $request->kepada;
            $nota->tanggal = $request->tanggal;
            $nota->nama_pembeli = $request->nama_pembeli;
            $nota->no_hp_pembeli = $request->no_hp_pembeli; // Update No HP
            $nota->nama_penjual = $request->nama_penjual;
            $nota->save();

            $nota->items()->delete();

            $total_harga = 0;
            foreach ($request->barang as $item) {
                $jumlah = $item['banyaknya'] * $item['harga'];
                $total_harga += $jumlah;

                NotaItem::create([
                    'nota_id'     => $nota->id,
                    'nama_barang' => $item['nama'],
                    'banyaknya'   => $item['banyaknya'],
                    'harga'       => $item['harga'],
                    'jumlah'      => $jumlah,
                ]);
            }

            $nota->update(['total_harga' => $total_harga]);

            DB::commit();
            return redirect()->route('nota.index')->with('success', 'Nota berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $nota = Nota::findOrFail($id);

        if ($nota->ttd_pembeli) Storage::disk('public')->delete($nota->ttd_pembeli);
        if ($nota->ttd_penjual) Storage::disk('public')->delete($nota->ttd_penjual);

        $nota->delete();

        return redirect()->route('nota.index')->with('success', 'Nota berhasil dihapus!');
    }

    public function exportPdf()
    {
        $notas = Nota::with('items')->orderBy('tanggal', 'desc')->get();
        $pdf = Pdf::loadView('nota.pdf', compact('notas'));
        return $pdf->download('Laporan_Riwayat_Nota.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new NotaExport, 'Laporan_Riwayat_Nota.xlsx');
    }

    public function downloadNota($id)
    {
        $nota = Nota::with('items')->findOrFail($id);
        $pdf = Pdf::loadView('nota.receipt_pdf', compact('nota'))->setPaper('A5', 'portrait');
        return $pdf->download('Nota_' . $nota->no_nota . '.pdf');
    }

    public function paymentPage($no_nota)
    {
        $nota = Nota::with('items')->where('no_nota', $no_nota)->firstOrFail();

        // 1. Ekstraksi 4 angka terakhir untuk PIN
        $hpPembeli = preg_replace('/[^0-9]/', '', $nota->no_hp_pembeli);
        $pinRahasia = substr($hpPembeli, -4);
        if (strlen($pinRahasia) < 4) $pinRahasia = str_pad($pinRahasia, 4, '0', STR_PAD_LEFT);

        // 2. Kisi-kisi bintang untuk frontend
        $panjangHp = strlen($hpPembeli);
        $tampilDepan = substr($hpPembeli, 0, 7);
        $jumlahBintang = $panjangHp > 7 ? $panjangHp - 7 : 4;
        $kisiKisiHp = $tampilDepan . str_repeat('*', $jumlahBintang);

        // 3. Ambil data Tripay Channels dari API/Cache (Sama seperti CheckoutController)
        $currentMode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $cacheKey = 'tripay_channels_list_' . $currentMode;

        $tripayChannels = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 24, function () use ($currentMode) {
            if ($currentMode === 'production') {
                $baseUrl = 'https://tripay.co.id/api';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            } else {
                $baseUrl = 'https://tripay.co.id/api-sandbox';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            }
            try {
                $response = \Illuminate\Support\Facades\Http::withToken($apiKey)->timeout(10)->get($baseUrl . '/merchant/payment-channel');
                if ($response->successful()) { return $response->json()['data'] ?? []; }
            } catch (\Exception $e) {}
            return [];
        });

        return view('nota.payment_page', compact('nota', 'pinRahasia', 'kisiKisiHp', 'tripayChannels'));
    }

    /**
     * FUNGSI BARU: Memproses Pilihan Pembayaran dari Halaman Nota
     */
    public function prosesBayar(Request $request, $no_nota)
    {
        $request->validate(['payment_method' => 'required|string']);

        $nota = Nota::where('no_nota', $no_nota)->firstOrFail();

        $gateway = $request->input('payment_method');

        // Simpan pilihan bank/ewallet customer ke DB
        $nota->payment_method = $gateway;
        $nota->save();

        $totalTagihan = $nota->total_harga;
        $paymentUrl = null;

        // URL KEMBALIAN AGAR DOKU/PAYPAL/TRIPAY BALIK KE HALAMAN NOTA INI
        $returnUrl = url('/nota/pay/' . $nota->no_nota);

        try {
            // 1. JIKA PILIH DOKU
            if ($gateway === 'DOKU_JOKUL') {
                $dokuService = new \App\Services\DokuJokulService();

                $customerData = [
                    'name'  => $nota->nama_pembeli,
                    'email' => 'customer@tokosancaka.com',
                    'phone' => $nota->no_hp_pembeli
                ];

                $resDoku = $dokuService->createSpecificCheckoutPayment(
                    $nota->no_nota,
                    $totalTagihan,
                    $customerData,
                    'DOKU_JOKUL',
                    null,
                    $returnUrl
                );

                if (isset($resDoku['success']) && $resDoku['success'] === true) {
                    $paymentUrl = $resDoku['payment_url'];
                } else {
                    return back()->with('error', 'Gagal membuat tagihan DOKU: ' . ($resDoku['message'] ?? 'Unknown Error'));
                }
            }
            // 2. JIKA PILIH BCA QRIS
            elseif ($gateway === 'BCA_QRIS') {
                $bcaService = app(\App\Http\Controllers\BcaController::class);
                $bcaReference = date('Ymd', strtotime($nota->tanggal)) . str_pad($nota->id, 8, '0', STR_PAD_LEFT);
                $bcaResponse = $bcaService->generateQrisMpm([
                    'partnerReferenceNo' => $bcaReference,
                    'amount'             => $totalTagihan,
                    'merchantId'         => '123456789',
                    'terminalId'         => 'A1234567',
                    'qrOption'           => 'A'
                ]);
                if (!empty($bcaResponse) && ($bcaResponse['responseCode'] ?? '') === '2004700') {
                    $paymentUrl = $returnUrl; // Refresh halaman
                    $nota->payment_url = $bcaResponse['qrContent'];
                }
            }
            // 3. JIKA PILIH PAYPAL
            elseif ($gateway === 'PAYPAL') {
                $mode = \App\Models\Api::getValue('PAYPAL_MODE', 'global', 'sandbox');
                $clientId = \App\Models\Api::getValue('PAYPAL_CLIENT_ID', $mode);
                $secret = \App\Models\Api::getValue('PAYPAL_SECRET', $mode);
                $baseUrl = $mode === 'production' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

                $usdAmount = round($totalTagihan / 15000, 2);

                $response = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $secret)
                    ->asForm()->post($baseUrl . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

                if ($response->successful()) {
                    $token = $response->json()['access_token'];
                    $orderRes = \Illuminate\Support\Facades\Http::withToken($token)->post($baseUrl . '/v2/checkout/orders', [
                        'intent' => 'CAPTURE',
                        'purchase_units' => [[
                            'reference_id' => $nota->no_nota,
                            'amount' => ['currency_code' => 'USD', 'value' => (string) $usdAmount]
                        ]],
                        'payment_source' => [
                            'paypal' => [
                                'experience_context' => [
                                    'return_url' => $returnUrl,
                                    'cancel_url' => $returnUrl
                                ]
                            ]
                        ]
                    ]);

                    if ($orderRes->successful()) {
                        foreach ($orderRes->json()['links'] as $link) {
                            if ($link['rel'] === 'payer-action' || $link['rel'] === 'approve') {
                                $paymentUrl = $link['href'];
                                break;
                            }
                        }
                    } else {
                        return back()->with('error', 'Gagal memproses PayPal: ' . $orderRes->body());
                    }
                } else {
                    return back()->with('error', 'Kredensial PayPal tidak valid atau belum diatur di sistem.');
                }
            }
            // 4. JIKA PILIH DANA REGULER BINDING
            elseif ($gateway === 'DANA') {
                return redirect()->route('dana.payment.create', $nota->no_nota);
            }
            // 5. JIKA PILIH TRIPAY (OVO, DANA, VA MANDIRI, BNI, DLL)
            else {
                $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                $apiKey = \App\Models\Api::getValue('TRIPAY_API_KEY', $mode);
                $privateKey = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', $mode);
                $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', $mode);
                $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';

                $payload = [
                    'method' => $gateway,
                    'merchant_ref' => $nota->no_nota,
                    'amount' => $totalTagihan,
                    'customer_name' => $nota->nama_pembeli,
                    'customer_email' => 'customer+'.\Illuminate\Support\Str::random(5).'@tokosancaka.com',
                    'customer_phone' => $nota->no_hp_pembeli,
                    'order_items' => [['sku' => 'NOTA', 'name' => 'Pembayaran Nota ' . $nota->no_nota, 'price' => $totalTagihan, 'quantity' => 1]],
                    'return_url' => $returnUrl,
                    'expired_time' => time() + (24 * 60 * 60),
                    'signature' => hash_hmac('sha256', $merchantCode . $nota->no_nota . $totalTagihan, $privateKey),
                ];

                $response = \Illuminate\Support\Facades\Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload);
                $resData = $response->json();

                if (isset($resData['success']) && $resData['success'] === true) {
                    $paymentUrl = $resData['data']['checkout_url'];
                } else {
                    return back()->with('error', 'Gagal memproses metode pembayaran ini: ' . ($resData['message'] ?? 'Unknown Error'));
                }
            }

            // Simpan Link Pembayaran & Redirect Customer
            if ($paymentUrl) {
                if ($gateway !== 'BCA_QRIS') {
                    $nota->payment_url = $paymentUrl;
                }
                $nota->save();
                return redirect()->away($paymentUrl);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Nota Payment Error: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat memuat metode pembayaran.');
        }

        return back()->with('error', 'Gagal mengarahkan ke halaman pembayaran.');
    }

   /**
     * FUNGSI BARU: Memproses Callback dari Webhook (DANA, DOKU, Tripay, dll)
     */
    public static function processCallback($no_nota, $status)
    {
        try {
            if (in_array(strtoupper($status), ['PAID', 'SUCCESS'])) {

                // Gunakan Query Builder langsung agar kebal terhadap batasan $fillable model
                $affected = \Illuminate\Support\Facades\DB::table('notas')
                    ->where('no_nota', $no_nota)
                    ->update([
                        'status' => 'PAID',
                        'updated_at' => now()
                    ]);

                if ($affected) {
                    \Illuminate\Support\Facades\Log::info("✅ [NOTA] DB UPDATE SUKSES: $no_nota menjadi PAID.");
                } else {
                    \Illuminate\Support\Facades\Log::warning("⚠️ [NOTA] Webhook memicu fungsi, tapi Nota $no_nota tidak ditemukan di tabel.");
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("❌ [NOTA] Gagal memproses callback: " . $e->getMessage());
        }
    }

    /**
     * FUNGSI BARU: Mengirim ulang / mengirim manual link tagihan nota via Ajax dari daftar tabel
     */
    public function sendEmailInvoiceAjax(Request $request)
    {
        $request->validate([
            'no_nota' => 'required|string',
            'email'   => 'required|email'
        ]);

        try {
            $nota = Nota::where('no_nota', $request->no_nota)->firstOrFail();

            // Perbarui email pelanggan di tabel Nota sekalian
            $nota->email_pembeli = $request->email;
            $nota->save();

            $paymentLink = url('/nota/pay/' . $nota->no_nota);
            $subject = "Tagihan Pembayaran Nota #" . $nota->no_nota . " - SANCAKA EXPRESS";

            // Ekstraksi PIN
            $hpPembeli = preg_replace('/[^0-9]/', '', $nota->no_hp_pembeli);
            $pinRahasia = substr($hpPembeli, -4);
            if (strlen($pinRahasia) < 4) $pinRahasia = str_pad($pinRahasia, 4, '0', STR_PAD_LEFT);

            // Desain Email HTML (Sama seperti yang ada di fungsi store)
            $bodyHtml = "
            <div style='font-family: Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;'>
                <div style='background: #111827; padding: 20px; text-align: center;'>
                    <h2 style='color: #ffffff; margin: 0; font-size: 20px;'>SANCAKA EXPRESS</h2>
                </div>
                <div style='padding: 30px;'>
                    <p style='color: #374151; font-size: 16px;'>Halo <strong>{$nota->nama_pembeli}</strong>,</p>
                    <p style='color: #4b5563; line-height: 1.5;'>Berikut adalah link akses untuk melihat dan memproses tagihan Anda dengan nomor nota <strong>{$nota->no_nota}</strong>.</p>

                    <div style='background-color: #f3f4f6; padding: 20px; border-radius: 8px; text-align: center; margin: 25px 0;'>
                        <p style='margin: 0; color: #6b7280; font-size: 14px;'>Total Tagihan</p>
                        <p style='margin: 5px 0 0 0; color: #dc2626; font-size: 24px; font-weight: bold;'>Rp " . number_format($nota->total_harga, 0, ',', '.') . "</p>
                    </div>

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$paymentLink}' target='_blank' style='background-color: #10b981; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block;'>Lihat & Bayar Tagihan</a>
                    </div>

                    <div style='background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 4px;'>
                        <p style='margin: 0; color: #92400e; font-size: 14px;'><strong>PENTING:</strong> Halaman tagihan diamankan menggunakan PIN.</p>
                        <p style='margin: 5px 0 0 0; color: #b45309; font-size: 14px;'>Gunakan <strong>4 Angka Terakhir Nomor HP Anda</strong> ({$pinRahasia}) untuk membuka tagihan.</p>
                    </div>
                </div>
                <div style='background-color: #f9fafb; padding: 15px; text-align: center; border-top: 1px solid #e5e7eb;'>
                    <p style='margin: 0; color: #9ca3af; font-size: 12px;'>Terima kasih telah mempercayakan layanan Sancaka Express.</p>
                </div>
            </div>";

            // Eksekusi Kirim Email
            \Illuminate\Support\Facades\Mail::html($bodyHtml, function ($message) use ($request, $subject) {
                $message->to($request->email)
                        ->subject($subject)
                        ->from(config('mail.from.address', 'admin@tokosancaka.com'), config('mail.from.name', 'Admin Sancaka'));
            });

            // LOG LOG: Simpan riwayat email terkirim agar masuk di Dashboard Email
            \App\Models\Email::create([
                'user_id'      => \Illuminate\Support\Facades\Auth::id(),
                'folder'       => 'sent',
                'from_name'    => config('mail.from.name', 'Admin Sancaka'),
                'from_address' => config('mail.from.address', 'admin@tokosancaka.com'),
                'to_address'   => $request->email,
                'subject'      => $subject,
                'body'         => $bodyHtml,
                'is_starred'   => false,
                'read_at'      => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Tagihan berhasil dikirim ke Email pelanggan.']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal kirim tagihan manual via Ajax: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

}
