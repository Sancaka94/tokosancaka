<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GeneretBarcodeController extends Controller
{
    public function create()
    {
        return view('barcode.create');
    }

    public function generate(Request $request)
    {
        // Validasi input
        $request->validate([
            'url' => 'required|url'
        ], [
            'url.required' => 'URL wajib diisi.',
            'url.url' => 'Format URL tidak valid.'
        ]);

        // Generate 2D Barcode (QR Code) format PNG lalu ubah menjadi Base64 string
        // Base64 digunakan agar gambar bisa langsung dirender di browser dan di-download dengan mudah
        $barcode = base64_encode(QrCode::format('png')->size(300)->margin(2)->generate($request->url));

        return view('barcode.create', [
            'barcode' => $barcode,
            'url' => $request->url
        ]);
    }
}
