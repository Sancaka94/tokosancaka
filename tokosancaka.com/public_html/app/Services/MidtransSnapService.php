<?php

namespace App\Services;

use App\Models\Api;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransSnapService
{
    protected $mode;
    protected $isProduction;
    protected $serverKey;
    protected $baseUrl;

    public function __construct()
    {
        // Moco config murni soko database setting admin sing wis mbok input mau
        $this->mode = Api::getValue('MIDTRANS_MODE', 'global', 'sandbox');
        $this->isProduction = ($this->mode === 'production');
        
        // Ambil Server Key soko database (Mid-server-...)
        $this->serverKey = Api::getValue('MIDTRANS_SERVER_KEY', $this->mode);

        // Base URL murni nggae rute SNAP API
        $this->baseUrl = $this->isProduction 
            ? 'https://app.midtrans.com/snap/v1/transactions' 
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /**
     * FUNGSI UTAMA: Njaluk SNAP Token soko Midtrans kanggo ngetokno halaman bayar (Popup / Redirect)
     */
    public function createSnapTransaction($orderId, $amount, $bankCode, $customerName, $customerPhone = null, $customerEmail = null)
    {
        // Bersihkan karakter aneh soko Invoice sesuai aturan Midtrans
        $cleanOrderId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $orderId);

        // Payload Body standar sesuai dokumentasi SNAP murni
        $payload = [
            'transaction_details' => [
                'order_id'     => $cleanOrderId,
                'gross_amount' => (int) $amount, // Kudu Integer, gak oleh desimal malih eror
            ],
            'customer_details' => [
                'first_name' => substr($customerName, 0, 40),
                'email'      => $customerEmail ?? 'customer@tokosancaka.com',
                'phone'      => $customerPhone ?? '081234567890',
            ],
            // Batasi payment method-e sesuai bank sing diklik nang frontend mau
            'enabled_payments' => [
                $this->mapBankToSnapMethod($bankCode)
            ],
            'credit_card' => [
                'secure' => true
            ]
        ];

        try {
            Log::info('LOG LOG: [SNAP] Mengirim Request Transaksi ke Midtrans...', [
                'order_id' => $cleanOrderId,
                'amount'   => $amount,
                'method'   => $payload['enabled_payments'][0]
            ]);

            // Hit API nggae Basic Auth (Username = Server Key, Password = Kosong)
            $response = Http::withBasicAuth($this->serverKey, '')
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, $payload);

            Log::info('LOG LOG: [SNAP] Respon dari Midtrans Diterima', [
                'status_code' => $response->status(),
                'body'        => $response->json()
            ]);

            if ($response->successful()) {
                return $response->json(); // Ngetokno token lan redirect_url soko Midtrans
            }

            throw new \Exception('Midtrans Reject: ' . ($response->json()['error_messages'][0] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('LOG LOG: [SNAP] Fatal Error saat create transaksi', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Helper: Nyocokno kode internal Sancaka dadi kode enabled_payments soko Midtrans SNAP
     */
    private function mapBankToSnapMethod($bankCode)
    {
        $bank = strtolower($bankCode);
        $mapping = [
            'bca'     => 'bca_va',
            'bni'     => 'bni_va',
            'bri'     => 'bri_va',
            'mandiri' => 'echannel', // Khusus Mandiri di SNAP nggae echannel / Bill Payment
            'permata' => 'permata_va'
        ];

        return $mapping[$bank] ?? 'bca_va';
    }
}