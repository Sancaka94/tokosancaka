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

        // 🔥 PERBAIKAN: Pisahkan penamaan kolom berdasarkan jenis tabel
        if ($isAutokirim) {
            $pesanan->metode_pembayaran = $gateway;
        } else {
            $pesanan->payment_method = $gateway;
        }
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
                        // ========================================================
                        // 1. CARI DATA ORIGIN (PENGIRIM)
                        // ========================================================
                        $origin = AutoKirim::where('zip', trim($pesanan->pengirim_kodepos))->first();

                        // Fallback: Jika kodepos tidak akurat, cari ID Kecamatan dari riwayat Kontak
                        if (!$origin) {
                            $kontakPengirim = \App\Models\Kontak::where('no_hp', $pesanan->pengirim_hp)->first();
                            if ($kontakPengirim && $kontakPengirim->district_id) {
                                $origin = AutoKirim::where('district_id', $kontakPengirim->district_id)->first();
                            }
                        }

                        // ========================================================
                        // 2. CARI DATA DESTINATION (PENERIMA)
                        // ========================================================
                        $destination = AutoKirim::where('zip', trim($pesanan->penerima_kodepos))->first();

                        // Fallback: Jika kodepos tidak akurat, cari ID Kecamatan dari riwayat Kontak
                        if (!$destination) {
                            $kontakPenerima = \App\Models\Kontak::where('no_hp', $pesanan->penerima_hp)->first();
                            if ($kontakPenerima && $kontakPenerima->district_id) {
                                $destination = AutoKirim::where('district_id', $kontakPenerima->district_id)->first();
                            }
                        }

                        // ========================================================
                        // 3. VALIDASI FINAL (Cegah Crash "Attempt to read property")
                        // ========================================================
                        if (!$origin) {
                            throw new \Exception("Kodepos Pengirim ({$pesanan->pengirim_kodepos}) tidak dikenali server logistik. Solusi: Buka menu Edit Pesanan, ketik ulang dan pilih Kecamatan Pengirim dari dropdown, lalu Simpan.");
                        }
                        if (!$destination) {
                            throw new \Exception("Kodepos Penerima ({$pesanan->penerima_kodepos}) tidak dikenali server logistik. Solusi: Buka menu Edit Pesanan, ketik ulang dan pilih Kecamatan Penerima dari dropdown, lalu Simpan.");
                        }

                        // Jika aman, eksekusi API Autokirim
                        $autokirimCtrl = new \App\Http\Controllers\PesananAutokirimController();
                        $awbResult = $autokirimCtrl->_executeAutokirimApi($pesanan, $origin, $destination, null);

                        $pesanan->awb_number = $awbResult['awb'] ?? null;
                        $pesanan->tlc_code = $awbResult['reff_2'] ?? null;
                        $pesanan->reff_1 = $awbResult['reff_1'] ?? null;
                        $pesanan->pickup_point_code = $awbResult['pickup'] ?? null;
                        $pesanan->status = 'booking_created';

                        // 🔥 Gunakan metode_pembayaran
                        $pesanan->metode_pembayaran = 'cash';

                    } catch (\Exception $e) {
                        // Menangkap error dengan rapi tanpa membuat halaman blank
                        return back()->with('error', 'Gagal memproses API: ' . $e->getMessage());
                    }
                } else {
                    $pesanan->status = 'booking_created';
                    $pesanan->status_pesanan = 'PAID';
                    // 🔥 Gunakan payment_method
                    $pesanan->payment_method = 'Cash / Tunai';
                }

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
                        $pesanan->payment_url = $bcaResponse['qrContent'];
                    } else {
                        // Cek apakah tabel autokirim punya kolom payment_url agar tidak error 1054 lagi
                        if (\Illuminate\Support\Facades\Schema::hasColumn('pesanan_autokirims', 'payment_url')) {
                            $pesanan->payment_url = $bcaResponse['qrContent'];
                        }
                    }
                    $paymentUrl = $returnUrl; // Refresh halaman
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
            // 4. JIKA PILIH DANA REGULER BINDING (API DIRECT SEPERTI TOPUP CONTROLLER)
            elseif ($gateway === 'DANA') {
                Log::info('DANA START for Invoice Table: ' . $nomorInvoiceFix);

                $danaSignature = app(\App\Services\DanaSignatureService::class);

                // Set config dinamis DANA agar signature dan request tidak gagal
                $danaMode = Api::getValue('dana_production_mode', 'global', '0');
                $isProduction = ($danaMode == '1');

                if ($isProduction) {
                    $merchantIdConf = Api::getValue('dana_prod_merchant_id', 'production', env('DANA_PROD_MERCHANT_ID'));
                    $partnerIdConf  = Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID'));
                    $baseUrl        = 'https://api.saas.dana.id';

                    config([
                        'services.dana.merchant_id'   => $merchantIdConf,
                        'services.dana.x_partner_id'  => $partnerIdConf,
                        'services.dana.private_key'   => Api::getValue('dana_prod_private_key', 'production', env('DANA_PROD_PRIVATE_KEY')),
                        'services.dana.client_secret' => Api::getValue('dana_prod_client_secret', 'production', env('DANA_PROD_CLIENT_SECRET')),
                        'services.dana.base_url'      => $baseUrl,
                        'services.dana.dana_env'      => 'PRODUCTION'
                    ]);
                } else {
                    $merchantIdConf = Api::getValue('dana_sandbox_merchant_id', 'sandbox', env('DANA_MERCHANT_ID'));
                    $partnerIdConf  = Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID'));
                    $baseUrl        = 'https://api.sandbox.dana.id';

                    config([
                        'services.dana.merchant_id'   => $merchantIdConf,
                        'services.dana.x_partner_id'  => $partnerIdConf,
                        'services.dana.private_key'   => Api::getValue('dana_sandbox_private_key', 'sandbox', env('DANA_PRIVATE_KEY')),
                        'services.dana.client_secret' => Api::getValue('dana_sandbox_client_secret', 'sandbox', env('DANA_CLIENT_SECRET')),
                        'services.dana.base_url'      => $baseUrl,
                        'services.dana.dana_env'      => 'SANDBOX'
                    ]);
                }

                $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
                $expiryTime = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');

                $amountValue = number_format((float)$totalTagihan, 2, '.', '');

                $user = auth()->user();
                $userId = $user ? (string) $user->id_pengguna : 'GUEST' . rand(100, 999);
                $nickname = $user ? substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $user->nama_lengkap), 0, 40) : 'Customer Sancaka';

                $bodyArray = [
                    "partnerReferenceNo" => (string) $nomorInvoiceFix,
                    "merchantId"         => $merchantIdConf,
                    "amount"             => [
                        "value"    => $amountValue,
                        "currency" => "IDR"
                    ],
                    "validUpTo"          => $expiryTime,
                    "urlParams"          => [
                        [
                            "url"        => $returnUrl, // KEMBALI KE INVOICE BILA SUKSES
                            "type"       => "PAY_RETURN",
                            "isDeeplink" => "N"
                        ],
                        [
                            "url"        => url('/dana/notify'),
                            "type"       => "NOTIFICATION",
                            "isDeeplink" => "N"
                        ]
                    ],
                    "additionalInfo"     => [
                        "mcc"     => "5732",
                        "envInfo" => [
                            "sourcePlatform"    => "IPG",
                            "terminalType"      => "SYSTEM",
                            "orderTerminalType" => "WEB"
                        ],
                        "order"   => [
                            "orderTitle"        => "Pembayaran " . $nomorInvoiceFix,
                            "scenario"          => "REDIRECT",
                            "merchantTransType" => "01",
                            "buyer"             => [
                                "externalUserId"   => $userId,
                                "externalUserType" => "MERCHANT_USER",
                                "nickname"         => $nickname
                            ],
                            "goods"             => [
                                [
                                    "name"            => "Pembayaran Pesanan",
                                    "merchantGoodsId" => "ITEM" . $nomorInvoiceFix,
                                    "description"     => "Pembayaran Tagihan Invoice",
                                    "category"        => "DIGITAL_GOODS",
                                    "price"           => [
                                        "value"    => $amountValue,
                                        "currency" => "IDR"
                                    ],
                                    "unit"            => "pcs",
                                    "quantity"        => "1"
                                ]
                            ]
                        ]
                    ]
                ];

                $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                $accessToken = $danaSignature->getAccessToken();
                $path = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';
                $signature = $danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

                $headers = [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-PARTNER-ID'  => $partnerIdConf,
                    'X-EXTERNAL-ID' => Str::random(32),
                    'X-TIMESTAMP'   => $timestamp,
                    'X-SIGNATURE'   => $signature,
                    'Content-Type'  => 'application/json',
                    'CHANNEL-ID'    => '95221',
                    'ORIGIN'        => url('/'),
                ];

                $response = Http::withHeaders($headers)
                    ->withBody($jsonBody, 'application/json')
                    ->post($baseUrl . $path);

                $result = $response->json();
                Log::info('DANA Create Payment Result (Invoice):', $result ?? []);

                if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                    $redirectUrl = $result['webRedirectUrl'] ?? $result['appLinkUrl'] ?? null;
                    if ($redirectUrl) {
                        $paymentUrl = $redirectUrl;
                    } else {
                        return back()->with('error', 'Gagal memproses DANA: URL Pembayaran tidak diterbitkan.');
                    }
                } else {
                    $errorCode = $result['responseCode'] ?? 'N/A';
                    return back()->with('error', 'Gagal dari DANA: ' . ($result['responseMessage'] ?? 'Unknown Error') . ' (Code: ' . $errorCode . ')');
                }
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
                    if (!$isAutokirim) {
                        $pesanan->payment_url = $paymentUrl;
                    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('pesanan_autokirims', 'payment_url')) {
                        $pesanan->payment_url = $paymentUrl;
                    }
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
