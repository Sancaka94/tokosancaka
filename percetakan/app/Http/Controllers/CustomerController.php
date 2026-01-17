<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Tampilkan semua customer (READ)
     * Dilengkapi fitur pencarian.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Fitur Pencarian (Nama / WA)
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

    /**
     * Tampilkan Form Tambah (CREATE)
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Simpan Data Baru (STORE - Web Form Biasa)
     */
    public function store(Request $request)
    {
        // Validasi Input Lengkap
        $request->validate([
            'name'             => 'required|string|max:255',
            'whatsapp'         => 'required|string|unique:customers,whatsapp',
            'address'          => 'nullable|string',
            'province_id'      => 'nullable|integer',
            'city_id'          => 'nullable|integer',
            'district_id'      => 'nullable|integer',
            'subdistrict_id'   => 'nullable|integer',
            'postal_code'      => 'nullable|string|max:10',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
        ]);

        try {
            // Normalisasi WA
            $data = $request->all();
            $data['whatsapp'] = $this->normalizePhone($request->whatsapp);

            Customer::create($data);

            return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * Simpan Data Baru via AJAX (Khusus POS & Integrasi API)
     * Menangani Logic UpdateOrCreate dan Mapping ID Wilayah
     */
    public function storeAjax(Request $request)
    {
        // Validasi dasar
        $request->validate([
            'name'     => 'required|string|max:255',
            'whatsapp' => 'required|string',
        ]);

        try {
            // 1. Normalisasi Nomor WA (Hapus karakter aneh, ubah 62 ke 0)
            $wa = $this->normalizePhone($request->whatsapp);

            // 2. Mapping Data (Menangani input dari form biasa ATAU response API Logistik)
            // Kadang frontend kirim 'district_id', kadang 'destination_district_id'
            $districtId    = $request->district_id ?? $request->destination_district_id ?? null;
            $subdistrictId = $request->subdistrict_id ?? $request->destination_subdistrict_id ?? null;
            $postalCode    = $request->postal_code ?? $request->destination_zipcode ?? null;

            // Alamat bisa dari 'address' atau 'destination_text' (Full address string)
            $addressText   = $request->address ?? $request->destination_text ?? null;

            // 3. Simpan atau Update Data Pelanggan
            $customer = Customer::updateOrCreate(
                ['whatsapp' => $wa], // Cek berdasarkan WA unik
                [
                    'name'             => $request->name,
                    'address'          => $addressText,

                    // ID Wilayah (Penting untuk KiriminAja/Cek Ongkir ulang)
                    'province_id'      => $request->province_id ?? null,
                    'city_id'          => $request->city_id ?? null,
                    'district_id'      => $districtId,
                    'subdistrict_id'   => $subdistrictId,

                    // Nama Wilayah (Opsional, untuk display)
                    'province_name'    => $request->province_name ?? null,
                    'city_name'        => $request->city_name ?? null,
                    'district_name'    => $request->district_name ?? null,
                    'subdistrict_name' => $request->subdistrict_name ?? null,

                    'postal_code'      => $postalCode,

                    // Geolocation (Penting untuk GoSend/Grab)
                    'latitude'         => $request->latitude ?? null,
                    'longitude'        => $request->longitude ?? null,
                ]
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Data pelanggan berhasil disimpan.',
                'data'    => $customer
            ], 200);

        } catch (\Exception $e) {
            Log::error("Customer Store Ajax Error: " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan Detail (READ - Single)
     */
    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.show', compact('customer'));
    }

    /**
     * Form Edit (VIEW)
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update Data (UPDATE - Web Form Biasa)
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name'             => 'required|string|max:255',
            // Ignore unique validation for current user's ID
            'whatsapp'         => 'required|string|unique:customers,whatsapp,' . $id,
            'address'          => 'nullable|string',
            'district_id'      => 'nullable|integer',
            'subdistrict_id'   => 'nullable|integer',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
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

    /**
     * Hapus Data (DELETE)
     */
    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->delete();

            return redirect()->route('customers.index')->with('success', 'Pelanggan dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Pencarian Customer via JSON (Untuk Autocomplete di POS)
     */
    public function searchApi(Request $request)
    {
        $keyword = $request->get('q');

        if (!$keyword) {
            return response()->json([]);
        }

        $customers = Customer::where('name', 'like', "%$keyword%")
            ->orWhere('whatsapp', 'like', "%$keyword%")
            ->limit(10)
            ->get(['id', 'name', 'whatsapp', 'address', 'district_id', 'subdistrict_id']);

        return response()->json($customers);
    }

    /**
     * Helper Private: Normalisasi Nomor HP
     */
    private function normalizePhone($phone)
    {
        // Hapus karakter selain angka
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ubah 628xx menjadi 08xx
        if (substr($phone, 0, 2) == '62') {
            $phone = '0' . substr($phone, 2);
        }
        // Jika user input 8xx (tanpa 0), tambahkan 0
        elseif (substr($phone, 0, 1) == '8') {
            $phone = '0' . $phone;
        }

        return $phone;
    }
}
