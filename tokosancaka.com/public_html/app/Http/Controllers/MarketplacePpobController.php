<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\IakPricelistPrepaid;  // Wajib panggil model IAK Prabayar
use App\Models\IakPricelistPostpaid; // Wajib panggil model IAK Pascabayar

class MarketplacePpobController extends Controller
{
    /**
     * Menampilkan halaman utama Marketplace PPOB (Form Pembelian Pulsa/Data)
     */
    public function index()
    {
        Log::info('LOG LOG - User accessing PPOB Marketplace page');

        // 1. Ambil data produk PRABAYAR dari database IAK
        $pricelistPrepaid = IakPricelistPrepaid::whereIn('status', ['Active', 'active', '1', 1])
                                ->orderBy('type')
                                ->orderBy('operator')
                                ->get();

        // 2. Ambil data produk PASCABAYAR dari database IAK
        $pricelist = IakPricelistPostpaid::where('status', 1)
                                ->orderBy('type')
                                ->get();

        // 3. Kirim variabel $pricelistPrepaid dan $pricelist ke Blade!
        return view('ppob.dana.index', compact('pricelistPrepaid', 'pricelist'));
    }

    /*
    |--------------------------------------------------------------------------
    | CATATAN ARSITEKTUR UNTUK DEVELOPER:
    |--------------------------------------------------------------------------
    | Fungsi checkout() yang sebelumnya ada di sini TELAH DIHAPUS.
    | Seluruh pemrosesan transaksi (Validasi form, Potong Saldo,
    | pembuatan Invoice, dan Hit API Tripay) sekarang ditangani secara
    | terpusat oleh:
    |
    | Class  : App\Http\Controllers\CheckoutController ATAU PpobIakController
    | Method : storePpobDanaPayment / store
    | Route  : POST /ppob/pay atau /ppob/store
    |
    */
}
