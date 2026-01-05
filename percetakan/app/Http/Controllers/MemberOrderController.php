<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class MemberOrderController extends Controller
{
    public function index()
    {
        $member = Auth::guard('member')->user();
        
        // Ambil semua order, urutkan terbaru, dan paginate (10 per halaman)
        $orders = Order::where('customer_phone', $member->whatsapp)
                       ->orderBy('created_at', 'desc')
                       ->paginate(10);

        return view('member.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $member = Auth::guard('member')->user();
        
        // Pastikan order ini milik member yang sedang login
        $order = Order::with(['items', 'coupon']) // Load relasi items
                      ->where('customer_phone', $member->whatsapp)
                      ->where('id', $id)
                      ->firstOrFail();

        return view('member.orders.show', compact('order'));
    }
}