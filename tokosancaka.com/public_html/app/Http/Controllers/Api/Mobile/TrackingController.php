<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pesanan;

class TrackingController extends Controller
{
    // LOG LOG: Lacak Resi via API
    public function track($resi)
    {
        // Cari resi di database
        $pesanan = Pesanan::where('nomor_invoice', $resi)->first();

        if (!$pesanan) {
            return response()->json([
                'success' => false,
                'message' => 'Resi tidak ditemukan.'
            ], 404);
        }

        // Asumsi Bapak punya tabel atau relasi tracking history
        // $history = $pesanan->trackingHistory;

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => $pesanan->nomor_invoice,
                'status' => $pesanan->status_pesanan,
                'ekspedisi' => $pesanan->expedition,
                'penerima' => $pesanan->nama_penerima,
                // 'history' => $history
            ]
        ], 200);
    }
}
