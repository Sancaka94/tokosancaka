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
        $customers = User::where('role', 'user')->orderBy('nama_lengkap', 'asc')->get();
        return view('admin.pesanan.create', compact('customers'));
    }

    /**
     * Menyimpan pesanan baru ke database.
     */
    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        DB::beginTransaction();
        try {
            $validatedData = $this->_validateOrderRequest($request);
            
            $this->_saveOrUpdateKontak($validatedData, 'sender', 'Pengirim');
            $this->_saveOrUpdateKontak($validatedData, 'receiver', 'Penerima');

            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone']);
            
            list($serviceGroup, $courier, $service, $shipping_cost, $ansuransi_fee, $cod_fee) = array_pad(explode('-', $validatedData['expedition']), 6, 0);
            
            $shipping_cost = (int) $shipping_cost;
            $ansuransi_fee = (int) $ansuransi_fee;
            $cod_fee = (int) $cod_fee;

            $total_paid = 0;
            $cod_value = 0;

            if ($validatedData['payment_method'] === 'CODBARANG') {
                $total_paid = (int)$validatedData['item_price'] + $shipping_cost + $cod_fee;
                if ($validatedData['ansuransi'] == 'iya') {
                    $total_paid += $ansuransi_fee;
                }
                $cod_value = $total_paid;
            } elseif ($validatedData['payment_method'] === 'COD') {
                $total_paid = $shipping_cost + $cod_fee;
                if ($validatedData['ansuransi'] == 'iya') {
                    $total_paid += $ansuransi_fee;
                }
                $cod_value = $total_paid;
            } else { // Termasuk 'Potong Saldo' dan metode Tripay
                $total_paid = $shipping_cost;
                if ($validatedData['ansuransi'] == 'iya') {
                    $total_paid += $ansuransi_fee;
                }
            }
            
            $pesananData = $this->_preparePesananData($validatedData, $total_paid, $request->ip(), $request->userAgent());
            $pesanan = Pesanan::create($pesananData);

            if ($validatedData['payment_method'] === 'Potong Saldo') {
                $customer = User::findOrFail($validatedData['customer_id']);
                
                if ($customer->saldo < $total_paid) {
                    throw new Exception('Saldo pelanggan tidak mencukupi untuk melakukan transaksi ini.');
                }
                
                $customer->saldo -= $total_paid;
                $customer->save();
                
                $pesanan->customer_id = $customer->id;
            }
            
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'Potong Saldo'])) {
                $senderAddressData = $this->_getAddressData($request, 'sender');
                $receiverAddressData = $this->_getAddressData($request, 'receiver');
                
                $kiriminResponse = $this->_createKiriminAjaOrder($validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData, $cod_value);
                
                if ($kiriminResponse['status'] !== true) {
                    throw new Exception($kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.'));
                }
                
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            } else { // Logika untuk pembayaran online via Tripay
                $tripay_amount = $total_paid;
                $orderItemsPayload = $this->_prepareOrderItemsPayload($shipping_cost, $ansuransi_fee, $validatedData['ansuransi']);
                $response = $this->_createTripayTransaction($validatedData, $pesanan, $tripay_amount, $orderItemsPayload);

                Log::channel('daily')->info('Tripay Response: ', $response ?? ['message' => 'No response from Tripay']);
                
                if (empty($response['success'])) {
                    throw new Exception('Gagal membuat transaksi pembayaran online. Pesan dari Server: ' . ($response['message'] ?? 'Tidak ada pesan.'));
                }

                $pesanan->payment_url = $response['data']['checkout_url'];
            }
            
            $pesanan->price = $total_paid;
            $pesanan->save();
            DB::commit();

            $waStatus = $this->_sendWhatsappNotification($pesanan, $validatedData, $shipping_cost, $ansuransi_fee, $cod_fee, $total_paid);
            
            $notifMessage = 'Pesanan baru dengan resi ' . ($pesanan->resi ?? $pesanan->nomor_invoice) . ' berhasil dibuat!';
            $notifMessage .= ' ' . $waStatus['message'];
            
            if (!empty($pesanan->payment_url)) return redirect()->away($pesanan->payment_url);
            
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

    /**
     * Menampilkan detail spesifik pesanan.
     */
    public function show($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.show', compact('order'));
    }

    /**
     * Menampilkan form untuk mengedit pesanan.
     */
    public function edit($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.edit', compact('order'));
    }

    /**
     * Memperbarui data pesanan di database.
     */
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

    /**
     * Menghapus pesanan dari database.
     */
    public function destroy($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        $order->delete();
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil dihapus.');
    }

    /**
     * API endpoint untuk mencari alamat via KiriminAja.
     */
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
    
    /**
     * API endpoint untuk mencari kontak.
     */
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
    
    /**
     * API endpoint untuk cek ongkos kirim.
     */
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
            
            $category = $request->service_type === 'cargo' ? 'trucking' : 'regular';
            $options = $kirimaja->getExpressPricing(
                $validated['sender_district_id'], $validated['sender_subdistrict_id'], 
                $validated['receiver_district_id'], $validated['receiver_subdistrict_id'], 
                $request->weight, $request->length ?? 1, $request->width ?? 1, $request->height ?? 1, 
                $request->item_price, null, $category, $request->ansuransi == 'iya' ? 1 : 0
            );
            
            return response()->json($options);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API endpoint untuk menghitung pesanan baru yang belum dilihat.
     */
    public function count()
    {
        $count = Pesanan::where('status', 'baru')->where('telah_dilihat', false)->count();
        return response()->json(['count' => $count]);
    }

    /**
     * Mencetak resi thermal.
     */
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
            'customer_id' => 'required_if:payment_method,Potong Saldo|nullable|exists:users,id',
        ]);
    }
    
    private function _getAddressData(Request $request, string $type): array
    {
        $lat = $request->input("{$type}_lat");
        $lng = $request->input("{$type}_lng");
        
        $kirimajaAddr = [
            'district_id' => $request->input("{$type}_district_id"),
            'subdistrict_id' => $request->input("{$type}_subdistrict_id"),
            'postal_code' => $request->input("{$type}_postal_code")
        ];
        
        if ((!$lat || !$lng) && $request->filled("{$type}_village")) {
             $fullAddress = implode(', ', array_filter([$request->input("{$type}_village"), $request->input("{$type}_district"), $request->input("{$type}_regency"), $request->input("{$type}_province")]));
             if ($geo = $this->geocode($fullAddress)) {
                 $lat = $geo['lat'];
                 $lng = $geo['lng'];
             }
        }
        return ['lat' => $lat, 'lng' => $lng, 'kirimaja_data' => $kirimajaAddr];
    }

    private function geocode(string $address): ?array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => 'SancakaCargo/1.0'])->get("https://nominatim.openstreetmap.org/search", [
                'q' => $address,
                'format' => 'json',
                'limit' => 1
            ])->json();
            return !empty($response[0]) ? ['lat' => (float) $response[0]['lat'], 'lng' => (float) $response[0]['lon']] : null;
        } catch (Exception $e) {
            Log::error("Geocoding failed: " . $e->getMessage());
            return null;
        }
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
    
    private function _preparePesananData(array $validatedData, int $total, string $ip, string $userAgent): array
    {
        do {
            $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6));
        } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

        return array_merge(
            collect($validatedData)->except(['save_sender', 'save_receiver', 'pengirim_id', 'penerima_id'])->all(),
            [
                'nomor_invoice' => $nomorInvoice,
                'price' => $total,
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
    
    private function _createKiriminAjaOrder(array $data, Pesanan $pesanan, KiriminAjaService $kirimaja, array $senderData, array $receiverData, int $cod_value): array
    {
        list($serviceGroup, $courier, $service_type) = array_pad(explode('-', $data['expedition']), 3, null);
        
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
                'insurance_amount' => ($data['ansuransi'] == 'iya') ? (int)$data['item_price'] : 0, 'cod' => $cod_value
            ]]
        ];
        return $kirimaja->createExpressOrder($payload);
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
            'customer_email' => 'customer@example.com', // Ganti dengan email valid jika ada
            'customer_phone' => $data['receiver_phone'],
            'order_items'    => $orderItems,
            'expired_time'   => time() + (24 * 60 * 60),
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];

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

    private function _sendWhatsappNotification(Pesanan $pesanan, array $data, int $shipping, int $insurance, int $cod_fee, int $total): array
    {
        $rincian = ["- Ongkir: Rp " . number_format($shipping, 0, ',', '.')];
        if ($data['item_price'] > 0) $rincian[] = "- Nilai Barang: Rp " . number_format($data['item_price'], 0, ',', '.');
        if ($insurance > 0) $rincian[] = "- Asuransi: Rp " . number_format($insurance, 0, ',', '.');
        if ($cod_fee > 0) $rincian[] = "- COD Fee: Rp " . number_format($cod_fee, 0, ',', '.');
        $rincianText = implode("\n", $rincian);

        $messageTemplate = <<<TEXT
*Terima Kasih Atas Orderannya 🙏*

Invoice: *{INVOICE}*
Resi: *{RESI}*

📦 Dari: *{SENDER_NAME}* ({SENDER_PHONE})
➡️ Ke: *{RECEIVER_NAME}* ({RECEIVER_PHONE})

----------------------------------------
*Rincian Biaya:*
{RINCIAN}
----------------------------------------
*Total Bayar: Rp {TOTAL}*
----------------------------------------
*Detail Paket:*
Deskripsi: {DESKRIPSI}
Berat: {BERAT} gr
Ekspedisi: {EKSPEDISI}
Layanan: {LAYANAN}
----------------------------------------

Semoga paket Anda aman sampai tujuan. ✅

Cek resi di:
https://tokosancaka.com/tracking/search?resi={RESI}

*Manajemen Sancaka*
TEXT;
        
        $message = str_replace(
            ['{INVOICE}', '{RESI}', '{SENDER_NAME}', '{SENDER_PHONE}', '{RECEIVER_NAME}', '{RECEIVER_PHONE}', '{RINCIAN}', '{TOTAL}', '{DESKRIPSI}', '{BERAT}', '{EKSPEDISI}', '{LAYANAN}'],
            [$pesanan->nomor_invoice, $pesanan->resi ?? 'N/A', $data['sender_name'], $data['sender_phone'], $data['receiver_name'], $data['receiver_phone'], $rincianText, number_format($total, 0, ',', '.'), $data['item_description'], $data['weight'], $data['expedition'], $data['service_type']],
            $messageTemplate
        );

        $senderWa = '62' . substr($this->_sanitizePhoneNumber($data['sender_phone']), 1);
        $receiverWa = '62' . substr($this->_sanitizePhoneNumber($data['receiver_phone']), 1);
        
        $senderStatus = FonnteService::sendMessage($senderWa, $message);
        $receiverStatus = FonnteService::sendMessage($receiverWa, $message);
        
        $allSuccess = ($senderStatus['success'] ?? false) && ($receiverStatus['success'] ?? false);
        
        return [
            'success' => $allSuccess,
            'message' => "Notifikasi WA Pengirim: " . ($senderStatus['message'] ?? 'Gagal') . " | Penerima: " . ($receiverStatus['message'] ?? 'Gagal')
        ];
    }
}

