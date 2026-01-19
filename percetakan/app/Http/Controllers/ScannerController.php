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

    public function handleScan(Request $request)
    {
        // Terima Barcode DAN Qty dari HP
        $request->validate([
            'barcode' => 'required|string',
            'qty'     => 'required|numeric' // Wajib ada jumlah
        ]);

        $barcode = $request->barcode;
        $qty     = $request->qty;

        // Kirim Event BarcodeScanned dengan Data Lengkap
        // Pastikan Event BarcodeScanned sudah support $qty (Lihat Tahap 3)
        try {
            broadcast(new \App\Events\BarcodeScanned($barcode, $qty))->toOthers();

            return response()->json(['status' => 'success', 'message' => 'Terkirim ke Laptop!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal Broadcast']);
        }
    }

}
