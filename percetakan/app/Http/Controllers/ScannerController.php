<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\BarcodeScanned;

class ScannerController extends Controller
{
    /**
     * Menampilkan halaman scanner di HP.
     */
    public function index()
    {
        return view('mobile-scanner');
    }

    /**
     * Menerima data scan dari HP dan memancarkannya ke Laptop.
     */
    public function handleScan(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'barcode' => 'required|string'
        ]);

        // 2. Kirim sinyal Realtime ke Channel 'pos-channel'
        broadcast(new BarcodeScanned($request->barcode));

        // 3. Beri respon sukses ke HP
        return response()->json([
            'status' => 'success',
            'message' => 'Barcode terkirim: ' . $request->barcode,
            'code' => $request->barcode
        ]);
    }
}