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
        DB::beginTransaction();
        try {
            // 1. Validasi semua input dari form
            $validatedData = $this->_validateOrderRequest($request);
            
            // 2. Simpan atau perbarui kontak jika dicentang
            $this->_saveOrUpdateKontak($validatedData, 'sender', 'Pengirim');
            $this->_saveOrUpdateKontak($validatedData, 'receiver', 'Penerima');

            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone']);
            
            // 3. Kalkulasi total biaya berdasarkan metode pembayaran
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid = $calculation['total_paid'];
            $cod_value = $calculation['cod_value'];
            
            // 4. Siapkan data dan buat entri pesanan awal di database
            $pesananData = $this->_preparePesananData($validatedData, $total_paid, $request->ip(), $request->userAgent());
            $pesanan = Pesanan::create($pesananData);

            // 5. Proses logika pembayaran spesifik
            // === LOGIKA UTAMA UNTUK POTONG SALDO ===
            if ($validatedData['payment_method'] === 'Potong Saldo') {
                $customer = User::findOrFail($validatedData['customer_id']);
                
                if ($customer->saldo < $total_paid) {
                    throw new Exception('Saldo pelanggan tidak mencukupi untuk melakukan transaksi ini.');
                }
                
                $customer->decrement('saldo', $total_paid);
                
                $pesanan->customer_id = $customer->id;
                $pesanan->sender_name = $customer->nama_lengkap;
                $validatedData['sender_name'] = $customer->nama_lengkap; // Update untuk payload API
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
                $response = $this->_createTripayTransaction($validatedData, $pesanan, $total_paid, $orderItemsPayload);

                if (empty($response['success'])) {
                    throw new Exception('Gagal membuat transaksi pembayaran. Pesan: ' . ($response['message'] ?? 'Tidak ada pesan.'));
                }

                $pesanan->payment_url = $response['data']['checkout_url'];
            }
            
            // 7. Simpan finalisasi data pesanan
            $pesanan->price = $total_paid;
            $pesanan->save();
            DB::commit();

            // 8. Kirim notifikasi WhatsApp
            $waStatus = $this->_sendWhatsappNotification($pesanan, $validatedData, $calculation, $total_paid);
            $notifMessage = 'Pesanan baru dengan resi ' . ($pesanan->resi ?? $pesanan->nomor_invoice) . ' berhasil dibuat!';
            $notifMessage .= ' ' . $waStatus['message'];
            
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

    // ... sisa method lainnya (show, edit, update, dll) tidak berubah ...

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

        // PERBAIKAN: Menggunakan 'only' untuk keamanan dan stabilitas
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

    private function _calculateTotalPaid(array $validatedData): array
    {
        list(,,,, $shipping_cost, $ansuransi_fee, $cod_fee) = array_pad(explode('-', $validatedData['expedition']), 7, 0);

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
        
        return compact('total_paid', 'cod_value', 'shipping_cost', 'ansuransi_fee', 'cod_fee');
    }
    
    private function _createKiriminAjaOrder(array $data, Pesanan $pesanan, KiriminAjaService $kirimaja, array $senderData, array $receiverData, int $cod_value): array
    {
        list(,$courier, $service_type) = array_pad(explode('-', $data['expedition']), 3, null);
        
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

        // PERBAIKAN: Menambahkan log untuk payload API
        Log::info('KiriminAja Create Order Payload:', $payload);

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
            'customer_email' => 'customer@sancakacargo.com', // Ganti dengan email valid jika ada
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

    private function _sendWhatsappNotification(Pesanan $pesanan, array $data, array $calculation, int $total): array
    {
        $rincian = ["- Ongkir: Rp " . number_format($calculation['shipping_cost'], 0, ',', '.')];
        if ($data['item_price'] > 0) $rincian[] = "- Nilai Barang: Rp " . number_format($data['item_price'], 0, ',', '.');
        if ($calculation['ansuransi_fee'] > 0) $rincian[] = "- Asuransi: Rp " . number_format($calculation['ansuransi_fee'], 0, ',', '.');
        if ($calculation['cod_fee'] > 0) $rincian[] = "- COD Fee: Rp " . number_format($calculation['cod_fee'], 0, ',', '.');
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
            [$pesanan->nomor_invoice, $pesanan->resi ?? $pesanan->nomor_invoice, $data['sender_name'], $data['sender_phone'], $data['receiver_name'], $data['receiver_phone'], $rincianText, number_format($total, 0, ',', '.'), $data['item_description'], $data['weight'], $data['expedition'], $data['service_type']],
            $messageTemplate
        );

        $senderWa = '62' . substr($this->_sanitizePhoneNumber($data['sender_phone']), 1);
        $receiverWa = '62' . substr($this->_sanitizePhoneNumber($data['receiver_phone']), 1);
        
        try {
            $senderStatus = FonnteService::sendMessage($senderWa, $message);
            $receiverStatus = FonnteService::sendMessage($receiverWa, $message);
        } catch (Exception $e) {
            Log::error('Fonnte Service sendMessage failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengirim notifikasi WA.'];
        }
        
        $allSuccess = ($senderStatus['success'] ?? false) && ($receiverStatus['success'] ?? false);
        
        return [
            'success' => $allSuccess,
            'message' => "WA Pengirim: " . ($senderStatus['message'] ?? 'Gagal') . " | Penerima: " . ($receiverStatus['message'] ?? 'Gagal')
        ];
    }
}

