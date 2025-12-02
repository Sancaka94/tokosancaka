<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DigiflazzService;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting; // Pastikan Model Setting di-import

class PpobController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    // --- PERBAIKAN DI SINI (SESUAI TABEL DATABASE ANDA) ---
    private function getWebLogo()
    {
        // Cari setting dengan key 'logo'
        $setting = Setting::where('key', 'logo')->first();
        
        // Ambil value-nya (path gambar), atau null jika tidak ketemu
        return $setting ? $setting->value : null;
    }
    // ------------------------------------------------------

    public function index()
    {
        $weblogo = $this->getWebLogo();
        return view('ppob.index', compact('weblogo'));
    }

    public function pulsa()
    {
        $weblogo = $this->getWebLogo();

        // Cache price list selama 60 menit
        $products = Cache::remember('digiflazz_pulsa', 60 * 60, function () {
            $allProducts = $this->digiflazz->getPriceList('prepaid');
            
            // Filter hanya kategori Pulsa
            return collect($allProducts)->filter(function ($item) {
                return $item['category'] === 'Pulsa' && $item['buyer_product_status'] === true && $item['seller_product_status'] === true;
            })->values();
        });

        // Kelompokkan berdasarkan Operator
        $operators = $products->groupBy('brand')->keys();

        return view('ppob.pulsa', compact('products', 'operators', 'weblogo'));
    }

    public function pln()
    {
        $weblogo = $this->getWebLogo();
        return view('ppob.pln', compact('weblogo'));
    }
}