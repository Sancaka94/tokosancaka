<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;

class InvoicePesananController extends Controller
{
    public function show($nomor_invoice)
    {
        // Cari pesanan berdasarkan nomor invoice
        $pesanan = Pesanan::where('nomor_invoice', $nomor_invoice)->firstOrFail();

        // Tentukan status lunas atau belum
        $statusLunas = in_array(strtoupper($pesanan->status_pesanan), ['PAID', 'LUNAS', 'SELESAI', 'TERKIRIM']);

        // Mengarah ke folder: resources/views/invoice_pesanan/show.blade.php
        return view('invoice_pesanan.show', compact('pesanan', 'statusLunas'));
    }
}
