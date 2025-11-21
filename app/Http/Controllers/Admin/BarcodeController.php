<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
// Asumsi Anda menggunakan library milon/laravel-barcode
use DNS2D;

class BarcodeController extends Controller
{
    public function generateBarcode(Request $request)
    {
        $resi = $request->input('resi');
        $size = $request->input('size', 8); // Ukuran default 8x8 untuk zoom
        
        if (!$resi) {
            return response()->json(['error' => 'Resi tidak ditemukan'], 400);
        }

        // Hasilkan SVG Barcode dengan ukuran lebih besar
        $svg = DNS2D::getBarcodeSVG($resi, 'DATAMATRIX', $size, $size);

        return response()->json(['svg' => $svg]);
    }
}