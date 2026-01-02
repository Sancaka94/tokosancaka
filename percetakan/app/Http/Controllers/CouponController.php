<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->paginate(10);
        return view('coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons,code|max:50',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        // Validasi tambahan: Jika persen, maks 100
        if ($request->type == 'percent' && $request->value > 100) {
            return back()->with('error', 'Diskon persentase tidak boleh lebih dari 100%.');
        }

        Coupon::create([
            'code' => strtoupper($request->code),
            'type' => $request->type,
            'value' => $request->value,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'usage_limit' => $request->usage_limit,
            'expiry_date' => $request->expiry_date,
            'start_date' => now(), // Default mulai sekarang
            'is_active' => true
        ]);

        return back()->with('success', 'Kupon berhasil dibuat!');
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons')->ignore($coupon->id)],
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        if ($request->type == 'percent' && $request->value > 100) {
            return back()->with('error', 'Diskon persentase tidak boleh lebih dari 100%.');
        }

        $coupon->update([
            'code' => strtoupper($request->code),
            'type' => $request->type,
            'value' => $request->value,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'usage_limit' => $request->usage_limit,
            'expiry_date' => $request->expiry_date,
        ]);

        return back()->with('success', 'Kupon berhasil diperbarui!');
    }

    public function destroy($id)
    {
        Coupon::findOrFail($id)->delete();
        return back()->with('success', 'Kupon berhasil dihapus.');
    }
}