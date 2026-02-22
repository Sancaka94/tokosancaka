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
        $db = DB::connection('mysql_second');

        // Filter status (held = Tertahan, released = Sudah Cair)
        $status = $request->query('status', 'held');

        // Ambil data order yang masuk escrow
        $orders = $db->table('orders')
            ->join('tenants', 'orders.tenant_id', '=', 'tenants.id')
            // Join ke tabel users untuk mendapatkan nama pemilik toko (Asumsi role admin/owner = Kasir Utama)
            ->leftJoin('users', function($join) {
                $join->on('tenants.id', '=', 'users.tenant_id')
                     ->whereIn('users.role', ['admin', 'owner']); // Sesuaikan role owner di SancakaPOS
            })
            ->where('orders.is_escrow', 1)
            ->where('orders.escrow_status', $status)
            ->select(
                'orders.id', 'orders.order_number', 'orders.final_price', 'orders.status as order_status',
                'orders.shipping_ref', 'orders.booking_ref', 'orders.escrow_status', 'orders.created_at',
                'tenants.name as store_name', 'tenants.id as tenant_id',
                'users.name as owner_name', 'users.id as owner_user_id'
            )
            // Group by order id untuk menghindari duplikat jika ada lebih dari 1 admin di satu tenant
            ->groupBy('orders.id')
            ->orderBy('orders.created_at', 'desc')
            ->paginate(20);

        // Ambil produk untuk masing-masing order
        $orderIds = $orders->pluck('id');
        $orderItems = [];
        if ($orderIds->count() > 0) {
            $items = $db->table('order_details')
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
        $db = DB::connection('mysql_second');

        try {
            $db->beginTransaction();

            // 1. Cari Order
            $order = $db->table('orders')->where('id', $order_id)->lockForUpdate()->first();

            if (!$order) throw new \Exception("Pesanan tidak ditemukan.");
            if ($order->is_escrow != 1) throw new \Exception("Ini bukan pesanan Escrow Marketplace.");
            if ($order->escrow_status === 'released') throw new \Exception("Dana sudah dicairkan sebelumnya.");

            // 2. Cari Owner/Admin Tenant tersebut
            $tenantOwner = $db->table('users')
                              ->where('tenant_id', $order->tenant_id)
                              ->orderBy('id', 'asc') // Ambil user pertama yang dibuat (biasanya owner)
                              ->first();

            if (!$tenantOwner) throw new \Exception("Pemilik toko (Tenant ID: {$order->tenant_id}) tidak ditemukan.");

            // 3. Tambah Saldo Owner
            $db->table('users')->where('id', $tenantOwner->id)->increment('saldo', $order->final_price);

            // 4. Update Status Escrow Order
            $db->table('orders')->where('id', $order->id)->update([
                'escrow_status' => 'released',
                'note' => $order->note . "\n[ESCROW] Dana Rp ".number_format($order->final_price)." DICAIRKAN ke ".$tenantOwner->name." pada ".now()->timezone('Asia/Jakarta'),
                'updated_at' => now()->timezone('Asia/Jakarta')
            ]);

            $db->commit();
            Log::info("💰 ESCROW RELEASED: Order {$order->order_number} sebesar Rp {$order->final_price} dicairkan ke {$tenantOwner->name}.");

            return redirect()->back()->with('success', "Berhasil! Dana Rp " . number_format($order->final_price, 0, ',', '.') . " telah masuk ke saldo toko {$tenantOwner->name}.");

        } catch (\Exception $e) {
            $db->rollBack();
            Log::error("❌ ESCROW RELEASE FAILED: " . $e->getMessage());
            return redirect()->back()->with('error', "Gagal mencairkan dana: " . $e->getMessage());
        }
    }
}
