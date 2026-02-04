<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    protected $tenantId;

    /**
     * Konstruktor: Deteksi Subdomain secara otomatis
     */
    public function __construct(Request $request)
    {
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // Kunci Tenant ID agar data tidak bocor antar subdomain
        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    /**
     * Tampilkan daftar customer - Filtered by Tenant
     */
    public function index(Request $request)
    {
        $query = Customer::where('tenant_id', $this->tenantId);

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(10);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    /**
     * Simpan Pelanggan via Web Form - Full Data
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'whatsapp'       => [
                'required',
                Rule::unique('customers')->where('tenant_id', $this->tenantId)
            ],
            'address'        => 'nullable|string',
            'province_id'    => 'nullable|integer',
            'city_id'        => 'nullable|integer',
            'district_id'    => 'nullable|integer',
            'subdistrict_id' => 'nullable|integer',
            'postal_code'    => 'nullable|string|max:10',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
        ]);

        try {
            $data = $request->all();
            $data['whatsapp']  = $this->normalizePhone($request->whatsapp);
            $data['tenant_id'] = $this->tenantId; // Kunci identitas tenant

            Customer::create($data);

            return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * Simpan Pelanggan via AJAX (POS) - Menangani Mapping Logistik
     * Bagian ini mengembalikan semua kode Mapping Wilayah Bapak yang sempat hilang
     */
    public function storeAjax(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'whatsapp' => 'required|string',
        ]);

        try {
            $wa = $this->normalizePhone($request->whatsapp);

            // LOGIKA MAPPING WILAYAH (Kembali Lengkap sesuai kode asli Bapak)
            $districtId    = $request->district_id ?? $request->destination_district_id ?? null;
            $subdistrictId = $request->subdistrict_id ?? $request->destination_subdistrict_id ?? null;
            $postalCode    = $request->postal_code ?? $request->destination_zipcode ?? null;
            $addressText   = $request->address ?? $request->destination_text ?? null;

            $customer = Customer::updateOrCreate(
                [
                    'whatsapp'  => $wa,
                    'tenant_id' => $this->tenantId // Kunci agar tidak menimpa data toko lain
                ],
                [
                    'name'             => $request->name,
                    'address'          => $addressText,
                    'province_id'      => $request->province_id ?? null,
                    'city_id'          => $request->city_id ?? null,
                    'district_id'      => $districtId,
                    'subdistrict_id'   => $subdistrictId,
                    'province_name'    => $request->province_name ?? null,
                    'city_name'        => $request->city_name ?? null,
                    'district_name'    => $request->district_name ?? null,
                    'subdistrict_name' => $request->subdistrict_name ?? null,
                    'postal_code'      => $postalCode,
                    'latitude'         => $request->latitude ?? null,
                    'longitude'        => $request->longitude ?? null,
                    'assigned_coupon'  => $request->assigned_coupon ?? null,
                ]
            );

            return response()->json(['status' => 'success', 'data' => $customer], 200);

        } catch (\Exception $e) {
            Log::error("Customer Store Ajax Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $customer = Customer::where('tenant_id', $this->tenantId)->findOrFail($id);
        return view('customers.show', compact('customer'));
    }

    public function edit($id)
    {
        $customer = Customer::where('tenant_id', $this->tenantId)->findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::where('tenant_id', $this->tenantId)->findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'whatsapp' => [
                'required',
                Rule::unique('customers')->where('tenant_id', $this->tenantId)->ignore($id)
            ],
            'address'        => 'nullable|string',
            'province_id'    => 'nullable|integer',
            'city_id'        => 'nullable|integer',
            'district_id'    => 'nullable|integer',
            'subdistrict_id' => 'nullable|integer',
        ]);

        try {
            $data = $request->all();
            $data['whatsapp'] = $this->normalizePhone($request->whatsapp);

            $customer->update($data);

            return redirect()->route('customers.index')->with('success', 'Data pelanggan diperbarui.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Customer::where('tenant_id', $this->tenantId)->findOrFail($id);
            $customer->delete();
            return redirect()->route('customers.index')->with('success', 'Pelanggan dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data.');
        }
    }

    /**
     * API Pencarian (Autocomplete di Kasir)
     */
    public function searchApi(Request $request)
    {
        $keyword = $request->get('q');
        if (!$keyword) return response()->json([]);

        $customers = Customer::where('tenant_id', $this->tenantId)
            ->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%")
                  ->orWhere('whatsapp', 'like', "%$keyword%");
            })
            ->limit(10)
            ->select([
                'id', 'name', 'whatsapp', 'address', 'province_id', 'city_id',
                'district_id', 'subdistrict_id', 'latitude', 'longitude', 'assigned_coupon'
            ])
            ->get();

        return response()->json($customers);
    }

    private function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 2) == '62') {
            $phone = '0' . substr($phone, 2);
        } elseif (substr($phone, 0, 1) == '8') {
            $phone = '0' . $phone;
        }
        return $phone;
    }
}
