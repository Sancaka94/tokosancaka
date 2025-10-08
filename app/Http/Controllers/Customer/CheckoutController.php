<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Menampilkan halaman checkout.
     */
    public function index()
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('katalog.index')->with('error', 'Keranjang Anda kosong, tidak bisa melanjutkan ke checkout.');
        }

        return view('customer.checkout.index', compact('cart'));
    }

    /**
     * Proses checkout (logika pembayaran bisa ditambahkan di sini).
     */
    public function process(Request $request)
    {
        // Logika proses checkout bisa ditambahkan di sini.
        // Misalnya, validasi data, simpan pesanan ke database, integrasi dengan gateway pembayaran, dll.

        // Setelah proses checkout selesai, kosongkan keranjang
        session()->forget('cart');

        return redirect()->route('katalog.index')->with('success', 'Checkout berhasil! Terima kasih telah berbelanja.');
    }
}
