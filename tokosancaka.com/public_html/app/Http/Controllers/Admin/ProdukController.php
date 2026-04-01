<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IakPricelistPrepaid; // Model yang benar
use Illuminate\Support\Facades\Log; // Import Log Laravel
use Illuminate\Support\Facades\Auth;

class ProdukController extends Controller
{
    /**
     * Menampilkan daftar produk (READ)
     */
    public function index(Request $request)
    {
        // LOG LOG
        $currentTab = $request->input('tab', 'pulsa');
        $search = $request->input('search');
        $status = $request->input('status');

        $query = IakPricelistPrepaid::where('type', $currentTab);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('operator', 'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $products = $query->latest()->paginate(15);

        return view('admin.produk.index', compact('products', 'currentTab'));
    }

    /**
     * Menampilkan form tambah produk (CREATE)
     */
    public function create(Request $request)
    {
        // LOG LOG
        $currentTab = $request->input('tab', 'pulsa');
        return view('admin.produk.create', compact('currentTab'));
    }

    /**
     * Menyimpan produk baru ke database (STORE)
     */
    public function store(Request $request)
    {
        // LOG LOG
        $request->validate([
            'operator'    => 'required|string',
            'code'        => 'required|unique:iak_pricelist_prepaid,code',
            'description' => 'required|string|max:255',
            'type'        => 'required|string',
            'price'       => 'required|numeric|min:0',
            'status'      => 'required',
        ]);

        $product = IakPricelistPrepaid::create([
            'operator'    => $request->operator,
            'code'        => $request->code,
            'description' => $request->description,
            'type'        => $request->type,
            'price'       => $request->price,
            'status'      => $request->status,
        ]);

        // Mencatat log aktivitas
        Log::info("PRODUK_IAK: Admin " . (Auth::user()->name ?? 'System') . " menambahkan produk baru [" . $product->code . "]");

        return redirect()->route('admin.produk.index', ['tab' => $request->type])
                         ->with('success', 'Produk berhasil ditambahkan!');
    }

    /**
     * Menampilkan form edit produk (EDIT)
     */
    public function edit($id)
    {
        // LOG LOG
        $product = IakPricelistPrepaid::findOrFail($id);
        return view('admin.produk.edit', compact('product'));
    }

    /**
     * Memperbarui data produk di database (UPDATE)
     */
    public function update(Request $request, $id)
    {
        // LOG LOG
        $product = IakPricelistPrepaid::findOrFail($id);

        $request->validate([
            'operator'    => 'required|string',
            'code'        => 'required|unique:iak_pricelist_prepaid,code,' . $product->id,
            'description' => 'required|string|max:255',
            'type'        => 'required|string',
            'price'       => 'required|numeric|min:0',
            'status'      => 'required',
        ]);

        $oldPrice = $product->price;
        $product->update($request->all());

        // Mencatat log aktivitas perubahan
        Log::info("PRODUK_IAK: Admin " . (Auth::user()->name ?? 'System') . " mengubah produk [" . $product->code . "]. Harga: $oldPrice -> " . $product->price);

        return redirect()->route('admin.produk.index', ['tab' => $request->type])
                         ->with('success', 'Produk berhasil diperbarui!');
    }

    /**
     * Menghapus produk (DELETE)
     */
    public function destroy($id)
    {
        // LOG LOG
        $product = IakPricelistPrepaid::findOrFail($id);
        $code = $product->code;
        $type = $product->type;

        $product->delete();

        // Mencatat log penghapusan (Warning)
        Log::warning("PRODUK_IAK: Admin " . (Auth::user()->name ?? 'System') . " MENGHAPUS produk [" . $code . "]");

        return redirect()->route('admin.produk.index', ['tab' => $type])
                         ->with('success', 'Produk berhasil dihapus!');
    }
}
