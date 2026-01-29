<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Tenant; // <--- WAJIB TAMBAH

class MemberOrderController extends Controller
{
    protected $tenantId;

    public function __construct(Request $request)
    {
        // Deteksi Subdomain agar member hanya melihat riwayat di toko ini saja
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    public function index()
    {
        $member = Auth::guard('member')->user();

        // Filter berdasarkan WhatsApp DAN tenant_id
        $orders = Order::where('tenant_id', $this->tenantId) // <--- KUNCI TOKO
                       ->where('customer_phone', $member->whatsapp)
                       ->orderBy('created_at', 'desc')
                       ->paginate(10);

        return view('member.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $member = Auth::guard('member')->user();

        // Pastikan order ini milik member DAN terdaftar di toko ini
        $order = Order::with(['items', 'coupon'])
                      ->where('tenant_id', $this->tenantId) // <--- KUNCI KEAMANAN
                      ->where('customer_phone', $member->whatsapp)
                      ->where('id', $id)
                      ->firstOrFail();

        return view('member.orders.show', compact('order'));
    }
}
