<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    // 1. Variabel penampung ID Tenant
    protected $tenantId;

    public function __construct(Request $request)
    {
        // 2. Deteksi Tenant dari Subdomain URL
        $host = $request->getHost();
        // Mengambil subdomain (misal: "toko1.domain.com" -> "toko1")
        $subdomain = explode('.', $host)[0];

        // 3. Cari data Tenant
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // 4. Simpan ID-nya. Default ke 1 jika tidak ditemukan (atau bisa throw 404)
        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    public function index()
    {
        // PERBAIKAN: Filter berdasarkan tenant_id
        $coupons = Coupon::where('tenant_id', $this->tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                // PERBAIKAN: Cek unique code TAPI khusus untuk tenant_id ini saja
                Rule::unique('coupons')->where(function ($query) {
                    return $query->where('tenant_id', $this->tenantId);
                }),
            ],
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        if ($request->type == 'percent' && $request->value > 100) {
            return back()->with('error', 'Diskon persentase tidak boleh lebih dari 100%.');
        }

        Coupon::create([
            'tenant_id' => $this->tenantId, // PERBAIKAN: Masukkan ID Tenant
            'code' => strtoupper($request->code),
            'type' => $request->type,
            'value' => $request->value,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'usage_limit' => $request->usage_limit,
            'expiry_date' => $request->expiry_date,
            'start_date' => now(),
            'is_active' => true
        ]);

        return back()->with('success', 'Kupon berhasil dibuat!');
    }

    public function update(Request $request, $id)
    {
        // PERBAIKAN: Cari kupon dengan ID tersebut DAN pastikan milik tenant ini
        $coupon = Coupon::where('tenant_id', $this->tenantId)
            ->where('id', $id)
            ->firstOrFail(); // Akan error 404 jika ID ada tapi milik tenant lain

        $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                // PERBAIKAN: Validasi unique ignore ID ini, dan scope tenant_id
                Rule::unique('coupons')->ignore($coupon->id)->where(function ($query) {
                    return $query->where('tenant_id', $this->tenantId);
                }),
            ],
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
        // PERBAIKAN: Pastikan hanya bisa menghapus milik tenant sendiri
        $coupon = Coupon::where('tenant_id', $this->tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $coupon->delete();

        return back()->with('success', 'Kupon berhasil dihapus.');
    }

    public function show($id)
    {
        return redirect()->route('coupons.index');
    }
}
