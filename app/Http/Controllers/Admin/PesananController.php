<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Kontak;
use App\Models\User;
use App\Services\FonnteService;
use App\Services\KiriminAjaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Exports\PesanansExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PesananController extends Controller
{
    /**
     * Menampilkan daftar semua pesanan dengan filter dan pencarian.
     */
    public function index(Request $request)
    {
        // Tandai pesanan 'baru' sebagai 'telah_dilihat'
        Pesanan::where('status', 'baru')
            ->where('telah_dilihat', false)
            ->update(['telah_dilihat' => true]);
                    
        $query = Pesanan::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")
                    ->orWhere('nomor_invoice', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('receiver_name', 'like', "%{$search}%")
                    ->orWhere('sender_phone', 'like', "%{$search}%")
                    ->orWhere('receiver_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->latest()->paginate(10); 
        return view('admin.pesanan.index', compact('orders'));
    }

    /**
     * Menampilkan form untuk membuat pesanan baru.
     */
    public function create()
    {
        // Mengambil semua pengguna untuk pilihan dropdown 'Potong Saldo'
        $customers = User::orderBy('nama_lengkap', 'asc')->get();
        return view('admin.pesanan.create', compact('customers'));
    }

    /**
     * Menyimpan pesanan baru ke database.
     */
    public function store(Request $request, KiriminAjaService $kirimaja)
    {
         

        
        try {

            DB::beginTransaction();
            // 1. Validasi semua input dari form
            $validatedData = $this->_validateOrderRequest($request);
            
            // 2. Simpan atau perbarui kontak jika dicentang
            $this->_saveOrUpdateKontak($validatedData, 'sender', 'Pengirim');
            $this->_saveOrUpdateKontak($validatedData, 'receiver', 'Penerima');

            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone']);
            
            // 3. Kalkulasi semua biaya berdasarkan metode pembayaran yang dipilih
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid_ongkir = $calculation['total_paid_ongkir']; // Biaya ongkir murni
            $cod_value = $calculation['cod_value'];   // Total yang harus ditagih kurir jika COD/CODBARANG
            
            // 4. Siapkan data dan buat entri pesanan awal di database
            $pesananData = $this->_preparePesananData($validatedData, $total_paid_ongkir, $request->ip(), $request->userAgent());
            $pesanan = Pesanan::create($pesananData);

           // 5. Proses logika pembayaran spesifik (disesuaikan untuk Customer)
if ($validatedData['payment_method'] === 'Potong Saldo') {
    $customer = User::where('id_pengguna', $validatedData['customer_id'])->first();

    if (!$customer) {
        throw new Exception('Pelanggan tidak ditemukan.');
    }

    if ($customer->saldo < $total_paid_ongkir) {
        throw new Exception('Saldo pelanggan tidak mencukupi.');
    }

    $customer->decrement('saldo', $total_paid_ongkir);

    // simpan relasi sesuai kolom di tabel pesanan
    $pesanan->customer_id = $customer->id_pengguna; // atau $customer->getKey() kalau primaryKey diset di model
}



            
            // 6. Proses pembuatan order ke API Ekspedisi atau Payment Gateway
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'Potong Saldo'])) {
                $senderAddressData = $this->_getAddressData($request, 'sender');
                $receiverAddressData = $this->_getAddressData($request, 'receiver');
                
                $kiriminResponse = $this->_createKiriminAjaOrder($validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData, $cod_value);
                
                if (($kiriminResponse['status'] ?? false) !== true) {
                    throw new Exception($kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.'));
                }
                
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            } else { // Logika untuk pembayaran online via Tripay
                $orderItemsPayload = $this->_prepareOrderItemsPayload($calculation['shipping_cost'], $calculation['ansuransi_fee'], $validatedData['ansuransi']);
                $response = $this->_createTripayTransaction($validatedData, $pesanan, $total_paid_ongkir, $orderItemsPayload);

                if (empty($response['success'])) {
                    throw new Exception('Gagal membuat transaksi pembayaran. Pesan: ' . ($response['message'] ?? 'Tidak ada pesan.'));
                }

                $pesanan->payment_url = $response['data']['checkout_url'];
            }
            
            // 7. Simpan finalisasi data pesanan
            if ($cod_value > 0) {
                $pesanan->price = $cod_value;
            } else {
                $pesanan->price = $total_paid_ongkir;
            }
            $pesanan->save();
            DB::commit();

            // 8. Kirim notifikasi WhatsApp
            $notification_total = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;
            $this->_sendWhatsappNotification($pesanan, $validatedData, $calculation['shipping_cost'], $calculation['ansuransi_fee'], $calculation['cod_fee'], $notification_total);
            $notifMessage = 'Pesanan baru dengan resi ' . ($pesanan->resi ?? $pesanan->nomor_invoice) . ' berhasil dibuat!';
            
            // 9. Arahkan pengguna ke halaman sukses atau pembayaran
            if (!empty($pesanan->payment_url)) {
                return redirect()->away($pesanan->payment_url);
            }
            
            return redirect()->route('admin.pesanan.index')->with('success', $notifMessage);

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('daily')->error('Order Creation Failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function show($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.show', compact('order'));
    }

    public function edit($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.edit', compact('order'));
    }

    public function update(Request $request, $resi)
    {
        $validatedData = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'item_description' => 'required|string',
            'weight' => 'required|numeric',
            'payment_method' => 'required|string',
            'expedition' => 'required|string',
        ]);
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        $order->update($validatedData);
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil diperbarui.');
    }

    public function destroy($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        $order->delete();
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil dihapus.');
    }

    public function updateStatus(Request $request, $resi)
    {
        $request->validate(['status' => 'required|string|in:Terkirim,Batal,Diproses,Menunggu Pickup']);
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        $pesanan->update([
            'status' => $request->status,
            'status_pesanan' => $request->status,
        ]);
        return redirect()->back()->with('success', 'Status pesanan ' . $resi . ' berhasil diubah menjadi "' . $request->status . '".');
    }

    public function exportExcel() 
    {
        return Excel::download(new PesanansExport(Pesanan::all()), 'semua-pesanan.xlsx');
    }

    public function exportPdf() 
    {
        $orders = Pesanan::all();
        $pdf = PDF::loadView('admin.pesanan.pdf', ['orders' => $orders]);
        return $pdf->download('semua-pesanan.pdf');
    }

    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate(['search' => 'required|string|min:3']);
        try {
            $results = $kirimaja->searchAddress($request->input('search'));
            return response()->json($results['data'] ?? []);
        } catch (Exception $e) {
            Log::channel('daily')->error('KiriminAja Address Search Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data alamat.'], 500);
        }
    }
    
    public function searchKontak(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
            'tipe'   => 'nullable|in:Pengirim,Penerima',
        ]);

        $query = Kontak::query()
            ->where(function ($q) use ($request) {
                $q->where(DB::raw('LOWER(nama)'), 'LIKE', '%' . strtolower($request->input('search')) . '%')
                    ->orWhere('no_hp', 'LIKE', "%{$request->input('search')}%");
            });

        if ($request->filled('tipe')) {
            $query->where(fn($q) => $q->where('tipe', $request->input('tipe'))->orWhere('tipe', 'Keduanya'));
        }

        return response()->json($query->limit(10)->get());
    }
    
    public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
    {
        try {
            $validated = $request->validate([
                'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
                'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
                'item_price' => 'required|numeric', 'weight' => 'required|numeric',
                'service_type' => 'required|string',
            ]);

            $senderData = $this->_getAddressData($request, 'sender');
            $receiverData = $this->_getAddressData($request, 'receiver');
            
            if (in_array($request->service_type, ['instant', 'sameday']) && (!$senderData['lat'] || !$receiverData['lat'])) {
                return response()->json(['status' => false, 'message' => 'Koordinat alamat tidak ditemukan, tidak dapat menghitung ongkir instan/sameday.'], 422);
            }
    
            $itemValue = $request->item_price; 
            $options = [];
            
            $isMandatory = in_array((int) $request->item_type, [1, 3, 4, 8]) ? 1 : 0;
            
            if($isMandatory && $request->ansuransi == 'tidak') {
                return response()->json(['status' => false, 'message' => 'Wajib ada asuransi.'], 422);
            }

            if (in_array($request->service_type, ['instant', 'sameday'])) {
                $options = $kirimaja->getInstantPricing($senderData['lat'], $senderData['lng'], $request->sender_address, $receiverData['lat'], $receiverData['lng'], $request->receiver_address, $request->weight, $itemValue, 'motor');
            } else { 
                $category = $request->service_type === 'cargo' ? 'trucking' : 'regular';
                $options = $kirimaja->getExpressPricing($validated['sender_district_id'], $validated['sender_subdistrict_id'], $validated['receiver_district_id'], $validated['receiver_subdistrict_id'], $request->weight, $request->length ?? 1, $request->width ?? 1, $request->height ?? 1, $itemValue, null, $category, $request->ansuransi == 'iya' ? 1 : 0);
            }
            
            return response()->json($options);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function count()
    {
        $count = Pesanan::where('status', 'baru')->where('telah_dilihat', false)->count();
        return response()->json(['count' => $count]);
    }

    public function cetakResiThermal(string $resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }

    // --- PRIVATE HELPER METHODS ---

    private function _validateOrderRequest(Request $request): array
    {
        return $request->validate([
            'sender_name' => 'required|string|max:255', 'sender_phone' => 'required|string|min:9|max:20', 'sender_address' => 'required|string',
            'sender_province' => 'required|string|max:100', 'sender_regency' => 'required|string|max:100', 'sender_district' => 'required|string|max:100',
            'sender_village' => 'required|string|max:100', 'sender_postal_code' => 'required|string|max:10', 'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|min:9|max:20', 'receiver_address' => 'required|string', 'receiver_province' => 'required|string|max:100',
            'receiver_regency' => 'required|string|max:100', 'receiver_district' => 'required|string|max:100', 'receiver_village' => 'required|string|max:100',
            'receiver_postal_code' => 'required|string|max:10', 'item_description' => 'required|string|max:255', 'item_price' => 'required|numeric|min:1000',
            'weight' => 'required|numeric|min:1', 'service_type' => 'required|string|in:regular,express,sameday,instant,cargo', 'expedition' => 'required|string',
            'payment_method' => 'required|string', 'ansuransi' => 'required|string|in:iya,tidak', 'pengirim_id' => 'nullable|integer|exists:kontaks,id',
            'penerima_id' => 'nullable|integer|exists:kontaks,id', 'length' => 'nullable|numeric|min:0', 'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0', 'item_type' => 'required|integer', 'save_sender' => 'nullable', 'save_receiver' => 'nullable',
            'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
            'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
            'customer_id' => 'required_if:payment_method,Potong Saldo|nullable|exists:Pengguna,id_pengguna',
            'sender_lat' => 'nullable|numeric', 'sender_lng' => 'nullable|numeric',
            'receiver_lat' => 'nullable|numeric', 'receiver_lng' => 'nullable|numeric',
            'sender_note' => 'nullable|string|max:255', 'receiver_note' => 'nullable|string|max:255',
            'item_type' => 'required|integer|exists:package_types,id',

        ]);
    }
    
    private function geocode(string $address): ?array
{
    try {
        $response = Http::timeout(10)
            ->withHeaders([
                'User-Agent' => 'SancakaCargo/1.0 (admin@tokosancaka.com)',
            ])
            ->get("https://nominatim.openstreetmap.org/search", [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'id'
            ]);

        $json = $response->json();
        return !empty($json[0])
            ? ['lat' => (float) $json[0]['lat'], 'lng' => (float) $json[0]['lon']]
            : null;

    } catch (\Exception $e) {
        Log::error("Geocoding failed: " . $e->getMessage(), ['address' => $address]);
        return null;
    }
}

private function _getAddressData(Request $request, string $type): array
{
    $lat = $request->input("{$type}_lat");
    $lng = $request->input("{$type}_lng");

    $kirimajaAddr = [
        'district_id' => $request->input("{$type}_district_id"),
        'subdistrict_id' => $request->input("{$type}_subdistrict_id"),
        'postal_code' => $request->input("{$type}_postal_code"),
    ];

    // jika lat/lng kosong → coba geocode
    if ((!$lat || !$lng)) {
        // 1. coba full address (lebih akurat)
        if ($request->filled("{$type}_address")) {
            $full = $request->input("{$type}_address") . ', ' . $request->input("{$type}_regency") . ', Indonesia';
            if ($geo = $this->geocode($full)) {
                $lat = $geo['lat'];
                $lng = $geo['lng'];
            }
        }

        // 2. kalau gagal, fallback ke village,district,regency,province,postal
        if ((!$lat || !$lng)) {
            $parts = [
                $request->input("{$type}_village"),
                $request->input("{$type}_district"),
                $request->input("{$type}_regency"),
                $request->input("{$type}_province"),
                $request->input("{$type}_postal_code"),
            ];
            $query = implode(', ', array_filter($parts)) . ', Indonesia';
            if ($geo = $this->geocode($query)) {
                $lat = $geo['lat'];
                $lng = $geo['lng'];
            }
        }
    }

    // log biar gampang debug
    Log::info("Geocode result for {$type}", [
        'lat' => $lat,
        'lng' => $lng,
        'address' => $request->input("{$type}_address"),
        'parts' => $request->only("{$type}_village","{$type}_district","{$type}_regency","{$type}_province","{$type}_postal_code")
    ]);

    return ['lat' => $lat, 'lng' => $lng, 'kirimaja_data' => $kirimajaAddr];
}


    private function _saveOrUpdateKontak(array $data, string $prefix, string $tipe)
    {
        if (!empty($data["save_{$prefix}"])) {
            Kontak::updateOrCreate(
                ['no_hp' => $this->_sanitizePhoneNumber($data["{$prefix}_phone"])],
                [
                    'nama'        => $data["{$prefix}_name"],
                    'alamat'      => $data["{$prefix}_address"],
                    'province'    => $data["{$prefix}_province"],
                    'regency'     => $data["{$prefix}_regency"],
                    'district'    => $data["{$prefix}_district"],
                    'village'     => $data["{$prefix}_village"],
                    'postal_code' => $data["{$prefix}_postal_code"],
                    'tipe'        => $tipe,
                ]
            );
        }
    }
    
    private function _preparePesananData(array $validatedData, int $total_ongkir, string $ip, string $userAgent): array
    {
        do {
            $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6));
        } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

        return array_merge(
            collect($validatedData)->only([
                'sender_name', 'sender_phone', 'sender_address', 'sender_province', 'sender_regency', 
                'sender_district', 'sender_village', 'sender_postal_code', 'receiver_name', 
                'receiver_phone', 'receiver_address', 'receiver_province', 'receiver_regency', 
                'receiver_district', 'receiver_village', 'receiver_postal_code', 'item_description', 
                'item_price', 'weight', 'length', 'width', 'height', 'service_type', 'expedition', 
                'payment_method', 'ansuransi', 'item_type', 'customer_id'
            ])->all(),
            [
                'nomor_invoice' => $nomorInvoice,
                'price' => $total_ongkir,
                'status' => 'Menunggu Pembayaran',
                'status_pesanan' => 'Menunggu Pembayaran',
                'tanggal_pesanan' => now(),
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'kontak_pengirim_id' => $validatedData['pengirim_id'] ?? null,
                'kontak_penerima_id' => $validatedData['penerima_id'] ?? null,
            ]
        );
    }

    private function _calculateTotalPaid(array $validatedData): array
    {
        // PERBAIKAN: Mengurai biaya dari string dengan lebih andal, dari belakang.
        $parts = explode('-', $validatedData['expedition']);
        $count = count($parts);

        // Mengambil 3 nilai terakhir sebagai biaya, yang posisinya selalu konsisten.
        $cod_fee       = ($count > 2) ? (int)end($parts) : 0;
        $ansuransi_fee = ($count > 3) ? (int)$parts[$count - 2] : 0;
        $shipping_cost = ($count > 4) ? (int)$parts[$count - 3] : 0;
        
        $cod_value = 0;

        $total_paid_ongkir = $shipping_cost;
        if ($validatedData['ansuransi'] == 'iya') {
            $total_paid_ongkir += $ansuransi_fee;
        }

        if ($validatedData['payment_method'] === 'CODBARANG') {
            $cod_value = (int)$validatedData['item_price'] + $total_paid_ongkir + $cod_fee;
        } elseif ($validatedData['payment_method'] === 'COD') {
            $cod_value = $total_paid_ongkir + $cod_fee;
        }
        
        return compact('total_paid_ongkir', 'cod_value', 'shipping_cost', 'ansuransi_fee', 'cod_fee');
    }
    
    private function _createKiriminAjaOrder(array $data, Pesanan $pesanan, KiriminAjaService $kirimaja, array $senderData, array $receiverData, int $cod_value): array
    {
        // PERBAIKAN: Mengurai string 'expedition' dengan logika yang sama seperti _calculateTotalPaid
        $expeditionParts = explode('-', $data['expedition']);
        $count = count($expeditionParts);

        $serviceGroup = $expeditionParts[0] ?? null;
        $courier      = $expeditionParts[1] ?? null;
        $service_type = $expeditionParts[2] ?? null;
        $shipping_cost = ($count > 4) ? (int)$expeditionParts[$count - 3] : 0;

        if (in_array($serviceGroup, ['instant', 'sameday'])) { 
            $payload = [
                'service' => $courier,
                'service_type' => $service_type,
                'vehicle' => 'motor',
                'order_prefix' => $pesanan->nomor_invoice,
                'packages' => [
                    [
                        'destination_name' => $data['receiver_name'],
                        'destination_phone' => $data['receiver_phone'],
                        'destination_lat' => $receiverData['lat'],
                        'destination_long' => $receiverData['lng'],
                        'destination_address' => $data['receiver_address'],
                        'destination_address_note' => $data['receiver_note'] ?? '-',
                        'origin_name' => $data['sender_name'],
                        'origin_phone' => $data['sender_phone'],
                        'origin_lat' => $senderData['lat'],
                        'origin_long' => $senderData['lng'],
                        'origin_address' => $data['sender_address'],
                        'origin_address_note' => $data['sender_note'] ?? '-',
                        'shipping_price' => (int)$shipping_cost,
                        'item' => [
                            'name' => $data['item_description'],
                            'description' => 'Pesanan dari pelanggan',
                            'price' => (int)$data['item_price'],
                            'weight' => (int)$data['weight'],
                        ]
                    ]
                ]
            ];
            return $kirimaja->createInstantOrder($payload);
        } else {
            $schedule = $kirimaja->getSchedules();
            $payload = [
                'address' => $data['sender_address'], 'phone' => $data['sender_phone'], 'name' => $data['sender_name'],
                'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
                'zipcode' => $senderData['kirimaja_data']['postal_code'], 'schedule' => $schedule['clock'], 'platform_name' => 'tokosancaka.com',
                'packages' => [[
                    'order_id' => $pesanan->nomor_invoice, 'item_name' => $data['item_description'], 'package_type_id' => (int)$data['item_type'],
                    'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'], 'destination_address' => $data['receiver_address'],
                    'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                    'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                    'weight' => $data['weight'], 'width' => $data['width'] ?? 1, 'height' => $data['height'] ?? 1, 'length' => $data['length'] ?? 1,
                    'item_value' => (int)$data['item_price'], 'service' => $courier, 'service_type' => $service_type,
                    'insurance_amount' => ($data['ansuransi'] == 'iya') ? (int)$data['item_price'] : 0, 'cod' => $cod_value,
                    'shipping_cost' => (int)$shipping_cost
                ]]
            ];
            Log::info('KiriminAja Create Order Payload:', $payload);
            return $kirimaja->createExpressOrder($payload);
        }
    }

    private function _prepareOrderItemsPayload(int $shipping_cost, int $ansuransi_fee, string $ansuransi_choice): array
    {
        $payload = [['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1]];
        if ($ansuransi_choice == 'iya' && $ansuransi_fee > 0) {
            $payload[] = ['sku' => 'INSURANCE', 'name' => 'Biaya Asuransi', 'price' => $ansuransi_fee, 'quantity' => 1];
        }
        return $payload;
    }

    private function _createTripayTransaction(array $data, Pesanan $pesanan, int $total, array $orderItems): array
    {
        $apiKey = config('tripay.api_key');
        $privateKey = config('tripay.private_key');
        $merchantCode = config('tripay.merchant_code');
        
        $payload = [
            'method'         => $data['payment_method'],
            'merchant_ref'   => $pesanan->nomor_invoice,
            'amount'         => $total,
            'customer_name'  => $data['receiver_name'],
            'customer_email' => 'customer@sancakacargo.com', 
            'customer_phone' => $data['receiver_phone'],
            'order_items'    => $orderItems,
            'expired_time'   => time() + (24 * 60 * 60),
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];
        
        Log::info('Tripay Create Transaction Payload:', $payload);

        $baseUrl = config('tripay.mode') === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        
        try {
            return Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload)->json();
        } catch (Exception $e) {
            Log::error('Tripay API Connection Failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Tidak dapat terhubung ke server pembayaran.'];
        }
    }
    
    private function _sanitizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (Str::startsWith($phone, '62')) {
            return '0' . substr($phone, 2);
        }
        return Str::startsWith($phone, '0') ? $phone : '0' . $phone;
    }

    private function _sendWhatsappNotification(Pesanan $pesanan, array $validatedData, int $shipping_cost, int $ansuransi_fee, int $cod_fee, int $total_paid, KiriminAjaService $kirimaja)
{
    // 1️⃣ Hitung berat volumetrik via KirimAja jika dimensi tersedia
    $weightToUse = $validatedData['weight'] ?? 0;
    if (!empty($validatedData['length']) && !empty($validatedData['width']) && !empty($validatedData['height'])) {
        try {
            $response = $kirimaja->calculateVolumetricWeight(
                $validatedData['length'],
                $validatedData['width'],
                $validatedData['height'],
                $validatedData['weight']
            );
            $weightToUse = $response['charged_weight'] ?? $weightToUse;
        } catch (\Exception $e) {
            Log::error('KiriminAja Volumetric Calculation Failed: ' . $e->getMessage());
            // fallback ke berat aktual
        }
    }

    // Format berat
    $beratFormatted = number_format($weightToUse, 0, ',', '.') . " Gram";
    if ($weightToUse >= 1000) {
        $beratFormatted .= " (" . number_format($weightToUse / 1000, 2, ',', '.') . " Kg)";
    }

    // 2️⃣ Bangun string "Detail Paket"
    $detailPaket = "*Detail Paket:*\n";
    $detailPaket .= "Deskripsi Barang: " . ($validatedData['item_description'] ?? '-') . "\n";
    $detailPaket .= "Total Berat: " . $beratFormatted . "\n";
    

    if (!empty($validatedData['length']) && !empty($validatedData['width']) && !empty($validatedData['height'])) {
        $detailPaket .= "Dimensi: {$validatedData['length']} x {$validatedData['width']} x {$validatedData['height']} cm\n";
    }

    $expeditionParts = explode('-', $validatedData['expedition']);
    $expeditionName = $expeditionParts[1] ?? $validatedData['expedition'];
    $detailPaket .= "Ekspedisi: " . ucwords($expeditionName) . "\n";
    $detailPaket .= "Layanan: " . ucwords($validatedData['service_type']);

    // 3️⃣ Bangun rincian biaya
    $rincianBiaya = "*Rincian Biaya:*\n";
    $rincianBiaya .= "- Ongkir: Rp " . number_format($shipping_cost, 0, ',', '.') . "\n";
    $rincianBiaya .= "- Nilai Barang: Rp " . number_format($validatedData['item_price'], 0, ',', '.');
    if ($ansuransi_fee > 0) $rincianBiaya .= "\n- Asuransi: Rp " . number_format($ansuransi_fee, 0, ',', '.');
    if ($cod_fee > 0) $rincianBiaya .= "\n- COD Fee: Rp " . number_format($cod_fee, 0, ',', '.');

    // 4️⃣ Template pesan
    $messageTemplate = <<<TEXT
*Terimakasih Ya Kak Atas Orderannya 🙏*

Berikut adalah Nomor Order ID / Nomor Invoice Kakak:
*{NOMOR_INVOICE}*

📦 Dari: *{SENDER_NAME}* ( {SENDER_PHONE} )
➡️ Ke: *{RECEIVER_NAME}* ( {RECEIVER_PHONE} )

----------------------------------------
{DETAIL_PAKET}
----------------------------------------
{RINCIAN_BIAYA}
----------------------------------------
*Total Bayar: Rp {TOTAL_BAYAR}*
----------------------------------------


Semoga Paket Kakak aman dan selamat sampai tujuan. ✅

Cek resi dengan klik link berikut:
https://tokosancaka.com/tracking/search?resi={NOMOR_INVOICE}

*Manajemen Sancaka*
TEXT;

    $message = str_replace(
        ['{NOMOR_INVOICE}','{SENDER_NAME}','{SENDER_PHONE}','{RECEIVER_NAME}','{RECEIVER_PHONE}','{TOTAL_BAYAR}','{RINCIAN_BIAYA}','{DETAIL_PAKET}'],
        [$pesanan->nomor_invoice,$validatedData['sender_name'],$validatedData['sender_phone'],$validatedData['receiver_name'],$validatedData['receiver_phone'],number_format($total_paid,0,',','.'),$rincianBiaya,$detailPaket],
        $messageTemplate
    );

    // 5️⃣ Kirim WA
    $senderWa = '62' . substr($this->_sanitizePhoneNumber($validatedData['sender_phone']), 1);
    $receiverWa = '62' . substr($this->_sanitizePhoneNumber($validatedData['receiver_phone']), 1);

    try {
        FonnteService::sendMessage($senderWa, $message);
        FonnteService::sendMessage($receiverWa, $message);
    } catch (\Exception $e) {
        Log::error('Fonnte Service sendMessage failed: ' . $e->getMessage());
    }
}

}

