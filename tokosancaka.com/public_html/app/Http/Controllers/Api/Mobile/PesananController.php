<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pesanan; // Pastikan model ini di-import
use Illuminate\Support\Str;

class PesananController extends Controller
{
    // LOG LOG: API Simpan Pesanan Satuan
    public function storeSingle(Request $request)
    {
        $request->validate([
            'nama_pengirim' => 'required|string',
            'no_wa_pengirim' => 'required|string',
            'nama_penerima' => 'required|string',
            'alamat_penerima' => 'required|string',
            'berat' => 'required|numeric',
            // Tambahkan validasi lain sesuai kebutuhan
        ]);

        try {
            // Simulasi simpan ke database
            $pesanan = new Pesanan();
            $pesanan->user_id = $request->user()->id;
            $pesanan->nomor_invoice = 'INV-MBL-' . strtoupper(Str::random(6));
            // $pesanan->nama_pengirim = $request->nama_pengirim;
            // ... (Mapping data lainnya ke kolom tabel Bapak)
            // $pesanan->save();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat!',
                'data' => [
                    'invoice' => $pesanan->nomor_invoice
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    // LOG LOG: API Riwayat Pesanan
    public function riwayat(Request $request)
    {
        $pesanan = Pesanan::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pesanan
        ], 200);
    }
}
