<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DigiflazzService;
use Illuminate\Support\Facades\Cache;

class PpobController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    public function index()
    {
        return view('ppob.index');
    }

    public function pulsa()
    {
        // Cache price list selama 60 menit agar tidak request ke API terus menerus (hemat bandwidth & cepat)
        $products = Cache::remember('digiflazz_pulsa', 60 * 60, function () {
            $allProducts = $this->digiflazz->getPriceList('prepaid');
            
            // Filter hanya kategori Pulsa
            return collect($allProducts)->filter(function ($item) {
                return $item['category'] === 'Pulsa' && $item['buyer_product_status'] === true && $item['seller_product_status'] === true;
            })->values();
        });

        // Kelompokkan berdasarkan Operator (Telkomsel, Indosat, dll)
        $operators = $products->groupBy('brand')->keys();

        return view('ppob.pulsa', compact('products', 'operators'));
    }
    
    // ... method pln() dll
}