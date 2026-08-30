<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoicePesananController extends Controller
{
    public function show($nomor_invoice)
    {
        $pesanan = Pesanan::where('nomor_invoice', $nomor_invoice)->firstOrFail();
        $statusLunas = in_array(strtoupper($pesanan->status_pesanan), ['PAID', 'LUNAS', 'SELESAI', 'TERKIRIM']);

        // Tarik daftar Tripay Channels (Virtual Account, E-Wallet, Alfamart, dll)
        $tripayChannels = [];
        if (!$statusLunas && empty($pesanan->payment_url) && !in_array($pesanan->payment_method, ['COD', 'CODBARANG', 'Cash', 'Potong Saldo'])) {
            $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
            $apiKey = Api::getValue('TRIPAY_API_KEY', $mode);
            $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/merchant/payment-channel' : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';
            
            try {
                $response = Http::withToken($apiKey)->get($baseUrl);
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
        $pesanan = Pesanan::where('nomor_invoice', $nomor_invoice)->firstOrFail();
        
        $gateway = $request->input('payment_method');
        $pesanan->payment_method = $gateway; // Simpan pilihan bank/ewallet customer ke DB
        $pesanan->save();
        
        $totalTagihan = $pesanan->price; // Gunakan total final yang sudah dikalkulasi sistem
        $paymentUrl = null;

        try {
            // 1. JIKA PILIH DOKU
            if ($gateway === 'DOKU_JOKUL') {
                $dokuService = new \App\Services\DokuJokulService();
                $paymentUrl = $dokuService->createPayment($pesanan->nomor_invoice, $totalTagihan);
            } 
            // 2. JIKA PILIH BCA QRIS
            elseif ($gateway === 'BCA_QRIS') {
                $bcaService = app(\App\Http\Controllers\BcaController::class);
                $bcaReference = date('Ymd', strtotime($pesanan->tanggal_pesanan)) . str_pad($pesanan->id, 8, '0', STR_PAD_LEFT);
                $bcaResponse = $bcaService->generateQrisMpm([
                    'partnerReferenceNo' => $bcaReference,
                    'amount'             => $totalTagihan,
                    'merchantId'         => '123456789',
                    'terminalId'         => 'A1234567',
                    'qrOption'           => 'A'
                ]);
                if (!empty($bcaResponse) && ($bcaResponse['responseCode'] ?? '') === '2004700') {
                    $pesanan->shipping_ref = $bcaResponse['referenceNo'];
                    $paymentUrl = route('invoice.show', ['nomor_invoice' => $pesanan->nomor_invoice]);
                    $pesanan->payment_url = $bcaResponse['qrContent']; // Simpan string QR
                }
            } 
            // 3. JIKA PILIH TRIPAY (OVO, DANA, VA MANDIRI, BNI, DLL)
            else {
                $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                $apiKey = Api::getValue('TRIPAY_API_KEY', $mode);
                $privateKey = Api::getValue('TRIPAY_PRIVATE_KEY', $mode);
                $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', $mode);
                $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';

                $payload = [
                    'method' => $gateway,
                    'merchant_ref' => $pesanan->nomor_invoice,
                    'amount' => $totalTagihan,
                    'customer_name' => $pesanan->receiver_name,
                    'customer_email' => 'customer+'.Str::random(5).'@tokosancaka.com',
                    'customer_phone' => $pesanan->receiver_phone,
                    'order_items' => [['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim & Layanan', 'price' => $totalTagihan, 'quantity' => 1]],
                    'return_url' => route('invoice.show', ['nomor_invoice' => $pesanan->nomor_invoice]),
                    'expired_time' => time() + (24 * 60 * 60),
                    'signature' => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $totalTagihan, $privateKey),
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