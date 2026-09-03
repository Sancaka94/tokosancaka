<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\PesananAutokirim; // Tambahkan ini
use App\Models\AutoKirim; // Tambahkan ini
use App\Models\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoicePesananController extends Controller
{
    public function show($nomor_invoice)
    {
        // 1. CARI DI PESANAN REGULER DULU
        $pesanan = Pesanan::where('nomor_invoice', $nomor_invoice)
            ->orWhere('resi', $nomor_invoice)
            ->orWhere('resi_aktual', $nomor_invoice)
            ->first();

        // 2. JIKA TIDAK KETEMU, CARI DI PESANAN AUTOKIRIM
        $isAutokirim = false;
        if (!$pesanan) {
            $pesanan = PesananAutokirim::where('order_id', $nomor_invoice)
                ->orWhere('awb_number', $nomor_invoice)
                ->firstOrFail(); // Jika di Autokirim juga tidak ada, baru 404

            $isAutokirim = true;
        }

        // Tentukan status lunas atau belum
        $statusLunas = in_array(strtoupper($isAutokirim ? $pesanan->status : $pesanan->status_pesanan), [
            'PAID', 'LUNAS', 'SELESAI', 'TERKIRIM', 'BOOKING_CREATED',
            'MENUNGGU PICKUP', 'PESANAN DIBUAT', 'DIPROSES', 'SEDANG DIKIRIM'
        ]);

        // Tarik daftar Tripay Channels
        $tripayChannels = [];
        if (!$statusLunas && empty($pesanan->payment_url) && !in_array($pesanan->payment_method, ['COD', 'CODBARANG', 'Cash', 'Potong Saldo'])) {
            $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
            $apiKey = Api::getValue('TRIPAY_API_KEY', $mode);
            $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/merchant/payment-channel' : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

            try {
                $response = Http::withToken($apiKey)->timeout(10)->get($baseUrl);
                if ($response->successful()) {
                    $tripayChannels = $response->json()['data'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error("Gagal load channel Tripay di Invoice: " . $e->getMessage());
            }
        }

        return view('invoice_pesanan.show', compact('pesanan', 'statusLunas', 'tripayChannels'));
    }

    public function prosesPembayaran(Request $request, $nomor_invoice)
    {
        $request->validate(['payment_method' => 'required|string']);

        // 1. CARI DI PESANAN REGULER DULU
        $pesanan = Pesanan::where('nomor_invoice', $nomor_invoice)
            ->orWhere('resi', $nomor_invoice)
            ->orWhere('resi_aktual', $nomor_invoice)
            ->first();

        // 2. JIKA TIDAK KETEMU, CARI DI PESANAN AUTOKIRIM
        $isAutokirim = false;
        if (!$pesanan) {
            $pesanan = PesananAutokirim::where('order_id', $nomor_invoice)
                ->orWhere('awb_number', $nomor_invoice)
                ->firstOrFail(); // Jika di Autokirim juga tidak ada, baru 404

            $isAutokirim = true;
        }

        $gateway = $request->input('payment_method');
        $pesanan->payment_method = $gateway; // Simpan pilihan bank/ewallet customer ke DB
        $pesanan->save();

        // NORMALISASI VARIABEL KARENA NAMA KOLOMNYA BEDA
        $totalTagihan = $isAutokirim ? $pesanan->grand_total : $pesanan->price;
        $receiverName = $isAutokirim ? $pesanan->penerima_nama : $pesanan->receiver_name;
        $receiverPhone = $isAutokirim ? $pesanan->penerima_hp : $pesanan->receiver_phone;
        $tanggalPesanan = $isAutokirim ? $pesanan->created_at : $pesanan->tanggal_pesanan;
        $nomorInvoiceFix = $isAutokirim ? $pesanan->order_id : $pesanan->nomor_invoice;
        $pesananId = $pesanan->id;

        $paymentUrl = null;

        // INI URL KEMBALIAN AGAR DOKU/PAYPAL BALIK KE HALAMAN INVOICE
        $returnUrl = route('invoice.show', ['nomor_invoice' => $nomorInvoiceFix]);

        // ====================================================
        // INTERCEPTOR: KHUSUS METODE CASH (HANYA ADMIN)
        // ====================================================
        if (strtolower($gateway) === 'cash') {
            if (auth()->check() && (auth()->id() == 4 || optional(auth()->user())->id_pengguna == 4 || strtolower(optional(auth()->user())->role) == 'admin')) {

                if ($isAutokirim) {
                    try {
                        $origin = AutoKirim::where('zip', $pesanan->pengirim_kodepos)->first();
                        $destination = AutoKirim::where('zip', $pesanan->penerima_kodepos)->first();
                        $autokirimCtrl = new \App\Http\Controllers\PesananAutokirimController();

                        $awbResult = $autokirimCtrl->_executeAutokirimApi($pesanan, $origin, $destination, null);

                        $pesanan->awb_number = $awbResult['awb'] ?? null;
                        $pesanan->tlc_code = $awbResult['reff_2'] ?? null;
                        $pesanan->reff_1 = $awbResult['reff_1'] ?? null;
                        $pesanan->pickup_point_code = $awbResult['pickup'] ?? null;
                        $pesanan->status = 'booking_created';
                    } catch (\Exception $e) {
                        return back()->with('error', 'Gagal memproses API Logistik Pusat: ' . $e->getMessage());
                    }
                } else {
                    $pesanan->status = 'booking_created';
                    $pesanan->status_pesanan = 'PAID';
                }

                $pesanan->payment_method = 'Cash / Tunai';
                $pesanan->save();

                return redirect($returnUrl)->with('success', 'Pembayaran tunai berhasil diverifikasi oleh Admin & Resi diterbitkan.');
            } else {
                return back()->with('error', 'Akses ditolak! Metode Cash hanya dapat digunakan oleh Admin.');
            }
        }
        // ====================================================

        try {
            // 1. JIKA PILIH DOKU
            if ($gateway === 'DOKU_JOKUL') {
                $dokuService = new \App\Services\DokuJokulService();

                $customerData = [
                    'name' => $receiverName,
                    'email' => 'customer@tokosancaka.com',
                    'phone' => $receiverPhone
                ];

                $resDoku = $dokuService->createSpecificCheckoutPayment(
                    $nomorInvoiceFix,
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
                $bcaReference = date('Ymd', strtotime($tanggalPesanan)) . str_pad($pesananId, 8, '0', STR_PAD_LEFT);
                $bcaResponse = $bcaService->generateQrisMpm([
                    'partnerReferenceNo' => $bcaReference,
                    'amount'             => $totalTagihan,
                    'merchantId'         => '123456789',
                    'terminalId'         => 'A1234567',
                    'qrOption'           => 'A'
                ]);
                if (!empty($bcaResponse) && ($bcaResponse['responseCode'] ?? '') === '2004700') {
                    if (!$isAutokirim) {
                        $pesanan->shipping_ref = $bcaResponse['referenceNo'];
                    }
                    $paymentUrl = $returnUrl; // Refresh halaman
                    $pesanan->payment_url = $bcaResponse['qrContent'];
                }
            }
            // 3. JIKA PILIH PAYPAL
            elseif ($gateway === 'PAYPAL') {
                $mode = Api::getValue('PAYPAL_MODE', 'global', 'sandbox');
                $clientId = Api::getValue('PAYPAL_CLIENT_ID', $mode);
                $secret = Api::getValue('PAYPAL_SECRET', $mode);
                $baseUrl = $mode === 'production' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

                $usdAmount = round($totalTagihan / 15000, 2);

                $response = Http::withBasicAuth($clientId, $secret)
                    ->asForm()->post($baseUrl . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

                if ($response->successful()) {
                    $token = $response->json()['access_token'];

                    $orderRes = Http::withToken($token)->post($baseUrl . '/v2/checkout/orders', [
                        'intent' => 'CAPTURE',
                        'purchase_units' => [[
                            'reference_id' => $nomorInvoiceFix,
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
                return redirect()->route('dana.payment.create', $nomorInvoiceFix);
            }
            // 5. JIKA PILIH TRIPAY (OVO, DANA, VA MANDIRI, BNI, DLL)
            else {
                $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                $apiKey = Api::getValue('TRIPAY_API_KEY', $mode);
                $privateKey = Api::getValue('TRIPAY_PRIVATE_KEY', $mode);
                $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', $mode);
                $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';

                $payload = [
                    'method' => $gateway,
                    'merchant_ref' => $nomorInvoiceFix,
                    'amount' => $totalTagihan,
                    'customer_name' => $receiverName,
                    'customer_email' => 'customer+'.Str::random(5).'@tokosancaka.com',
                    'customer_phone' => $receiverPhone,
                    'order_items' => [['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim & Layanan', 'price' => $totalTagihan, 'quantity' => 1]],
                    'return_url' => $returnUrl, // KEMBALI KE INVOICE BILA SUKSES
                    'expired_time' => time() + (24 * 60 * 60),
                    'signature' => hash_hmac('sha256', $merchantCode . $nomorInvoiceFix . $totalTagihan, $privateKey),
                ];

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload);
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
                    $pesanan->payment_url = $paymentUrl;
                }
                $pesanan->save();
                return redirect()->away($paymentUrl);
            }

        } catch (\Exception $e) {
            Log::error("Invoice Payment Error: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat memuat metode pembayaran.');
        }

        return back()->with('error', 'Gagal mengarahkan ke halaman pembayaran.');
    }
}
