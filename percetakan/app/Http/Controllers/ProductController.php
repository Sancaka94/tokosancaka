<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index() {
        $products = Product::orderBy('id', 'desc')->get();
        return view('dashboard', compact('products'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required',
            'base_price' => 'required|numeric',
            'unit' => 'required'
        ]);

        Product::create([
            'name' => $request->name,
            'base_price' => $request->base_price,
            'unit' => $request->unit,
            'stock_status' => 'available'
        ]);

        return back()->with('success', 'Produk berhasil diposting!');
    }
}