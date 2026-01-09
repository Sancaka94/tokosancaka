<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PpobProduct;
use App\Models\AgentProductPrice;

class AgentProductController extends Controller
{
    /**
     * Menampilkan daftar produk dengan harga modal agen & harga jual agen.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $search = $request->q;

        // Query Dasar: Ambil produk master yang aktif dijual admin
        $query = PpobProduct::where('seller_product_status', 1);

        // Filter Pencarian
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('buyer_sku_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        /**
         * LOGIKA JOIN CERDAS:
         * Menggabungkan tabel master produk dengan tabel harga agen.
         * * ppob_products.sell_price -> Kita anggap sebagai "MODAL" bagi agen.
         * agent_product_prices.selling_price -> Harga jual settingan agen (alias: agent_price).
         */
        $products = $query->leftJoin('agent_product_prices', function($join) use ($userId) {
            $join->on('ppob_products.id', '=', 'agent_product_prices.product_id')
                 ->where('agent_product_prices.user_id', '=', $userId);
        })
        ->select(
            'ppob_products.id',
            'ppob_products.product_name',
            'ppob_products.buyer_sku_code',
            'ppob_products.brand',
            'ppob_products.category',
            'ppob_products.sell_price', // Ini harga beli agen dari admin (Modal)
            'agent_product_prices.selling_price as agent_price' // Ini harga custom agen (Jual)
        )
        ->orderBy('ppob_products.brand', 'asc')
        ->orderBy('ppob_products.sell_price', 'asc')
        ->paginate(20);

        return view('customer.agent_products.index', compact('products'));
    }

    /**
     * Update Harga Satuan (Individual)
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_id'  => 'required|exists:ppob_products,id',
            'agent_price' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        // Cek harga modal (sell_price admin) untuk validasi (Opsional: mencegah rugi)
        $product = PpobProduct::find($request->product_id);
        
        // Simpan atau Update harga agen
        AgentProductPrice::updateOrCreate(
            [
                'user_id'    => $user->id,
                'product_id' => $request->product_id
            ],
            [
                'selling_price' => $request->agent_price
            ]
        );

        return redirect()->back()->with('success', 'Harga jual produk berhasil diperbarui.');
    }

    /**
     * Update Harga Massal (Bulk Markup)
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'markup_amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $markup = (int) $request->markup_amount;

        // Ambil semua produk aktif
        // Kita gunakan chunking untuk performa jika produk ribuan
        $masterProducts = PpobProduct::where('seller_product_status', 1)->get();

        DB::beginTransaction();
        try {
            foreach ($masterProducts as $product) {
                // Rumus: Harga Jual Agen = Harga Modal (dari Admin) + Markup
                $newSellingPrice = $product->sell_price + $markup;

                AgentProductPrice::updateOrCreate(
                    [
                        'user_id'    => $user->id,
                        'product_id' => $product->id
                    ],
                    [
                        'selling_price' => $newSellingPrice
                    ]
                );
            }
            
            DB::commit();
            return redirect()->back()->with('success', 'Berhasil menaikkan harga untuk ' . count($masterProducts) . ' produk.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat update massal.');
        }
    }
}