<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EscrowController extends Controller
{
   /**
     * Menampilkan Halaman Tabel Pencairan Dana
     */
    public function index(Request $request)
    {
        // Filter status (held = Tertahan, released = Sudah Cair)
        $status = $request->query('status', 'held');

        // Menggunakan koneksi default dan Subquery untuk menghindari error GROUP BY (Strict Mode)
        $orders = DB::table('orders')
            ->join('tenants', 'orders.tenant_id', '=', 'tenants.id')
            ->where('orders.is_escrow', 1)
            ->where('orders.escrow_status', $status)
            ->select(
                'orders.id', 'orders.order_number', 'orders.final_price', 'orders.status as order_status',
                'orders.shipping_ref', 'orders.booking_ref', 'orders.escrow_status', 'orders.created_at',
                'orders.shipping_cost', // <--- INI OBATNYA (KITA PANGGIL ONGKIRNYA)
                'tenants.name as store_name', 'tenants.id as tenant_id'
            )
            // Ambil Nama Owner menggunakan Subquery (Mencegah Duplicate tanpa Group By)
            ->addSelect(['owner_name' => DB::table('users')
                ->select('name')
                ->whereColumn('tenant_id', 'tenants.id')
                ->whereIn('role', ['admin', 'owner'])
                ->orderBy('id', 'asc')
                ->limit(1)
            ])
            // Ambil ID Owner menggunakan Subquery
            ->addSelect(['owner_user_id' => DB::table('users')
                ->select('id')
                ->whereColumn('tenant_id', 'tenants.id')
                ->whereIn('role', ['admin', 'owner'])
                ->orderBy('id', 'asc')
                ->limit(1)
            ])
            ->orderBy('orders.created_at', 'desc')
            ->paginate(20);

        $orderIds = $orders->pluck('id');
        $orderItems = [];

        if ($orderIds->count() > 0) {
            $items = DB::table('order_details')
                        ->whereIn('order_id', $orderIds)
                        ->select('order_id', 'product_name', 'quantity')
                        ->get();

            foreach ($items as $item) {
                $orderItems[$item->order_id][] = "{$item->quantity}x {$item->product_name}";
            }
        }

        return view('admin.escrow.index', compact('orders', 'orderItems', 'status'));
    }

    /**
     * Proses Buka Kran (Pencairan Dana ke Saldo Tenant)
     */
    public function release(Request $request, $order_id)
    {
        try {
            DB::beginTransaction();

            $order = DB::table('orders')->where('id', $order_id)->lockForUpdate()->first();

            if (!$order) throw new \Exception("Pesanan tidak ditemukan.");
            if ($order->is_escrow != 1) throw new \Exception("Ini bukan pesanan Escrow Marketplace.");
            if ($order->escrow_status === 'released') throw new \Exception("Dana sudah dicairkan sebelumnya.");

            $tenantOwner = DB::table('users')
                              ->where('tenant_id', $order->tenant_id)
                              ->orderBy('id', 'asc')
                              ->first();

            if (!$tenantOwner) throw new \Exception("Pemilik toko (Tenant ID: {$order->tenant_id}) tidak ditemukan.");

            // [PERBAIKAN] Hitung Dana Bersih yang dicairkan (Total Pembayaran dikurangi Ongkir)
            $danaBersih = $order->final_price - $order->shipping_cost;

            // 3. Tambah Saldo Owner (HANYA DANA BERSIH/HARGA BARANG)
            DB::table('users')->where('id', $tenantOwner->id)->increment('saldo', $danaBersih);

            // 4. Update Status Escrow Order
            DB::table('orders')->where('id', $order->id)->update([
                'escrow_status' => 'released',
                'note' => $order->note . "\n[ESCROW] Dana Bersih Produk Rp ".number_format($danaBersih)." DICAIRKAN ke ".$tenantOwner->name." pada ".now()->timezone('Asia/Jakarta'),
                'updated_at' => now()->timezone('Asia/Jakarta')
            ]);

            DB::commit();
            Log::info("💰 ESCROW RELEASED: Order {$order->order_number} sebesar Rp {$order->final_price} dicairkan ke {$tenantOwner->name}.");

            return redirect()->back()->with('success', "Berhasil! Dana Rp " . number_format($order->final_price, 0, ',', '.') . " telah masuk ke saldo toko {$tenantOwner->name}.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ ESCROW RELEASE FAILED: " . $e->getMessage());
            return redirect()->back()->with('error', "Gagal mencairkan dana: " . $e->getMessage());
        }
    }
}
