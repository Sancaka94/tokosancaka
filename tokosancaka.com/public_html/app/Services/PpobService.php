<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PpobService
{
    public function topUp($productCode, $destinationNumber, $refId)
    {
        // Contoh request ke Provider PPOB (Sesuaikan dengan dokumentasi provider Anda)
        $response = Http::post(env('PPOB_API_URL') . '/transaction', [
            'username' => env('PPOB_USERNAME'),
            'sign'     => md5(env('PPOB_USERNAME') . env('PPOB_API_KEY') . $refId),
            'buyer_sku_code' => $productCode,
            'customer_no' => $destinationNumber,
            'ref_id' => $refId,
        ]);

        return $response->json();
    }
}