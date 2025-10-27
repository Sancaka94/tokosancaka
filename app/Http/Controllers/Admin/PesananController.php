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

            // 3. Sanitasi Nomor HP
            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone']);
            
            // 4. Kalkulasi semua biaya
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid_ongkir = $calculation['total_paid_ongkir']; // Biaya ongkir + asuransi (jika ada)
            $cod_value = $calculation['cod_value'];           // Total yang harus ditagih kurir jika COD
            
            // 5. Siapkan data dan buat entri pesanan awal di database
            $pesananData = $this->_preparePesananData($validatedData, $total_paid_ongkir, $request->ip(), $request->userAgent());
            $pesanan = Pesanan::create($pesananData);

            // 6. Ambil data alamat lengkap (termasuk lat/lng jika ada)
            $senderAddressData = $this->_getAddressData($request, 'sender');
            $receiverAddressData = $this->_getAddressData($request, 'receiver');

            // 7. Proses logika pembayaran spesifik (Potong Saldo atau Ekspedisi)
            if ($validatedData['payment_method'] === 'Potong Saldo') {
                $customer = User::where('id_pengguna', $validatedData['customer_id'])->first();
                if (!$customer) { throw new Exception('Pelanggan tidak ditemukan.'); }
                if ($customer->saldo < $total_paid_ongkir) { throw new Exception('Saldo pelanggan tidak mencukupi.'); }
                
                $customer->decrement('saldo', $total_paid_ongkir);
                $pesanan->customer_id = $customer->id_pengguna;
            }

            // 8. Proses pembuatan order ke API Ekspedisi atau Payment Gateway
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'Potong Saldo'])) {
                // *** BAGIAN 1: LANGSUNG KE KIRIMIN AJA ***
                // Langsung push ke KiriminAja
                
                // Gunakan biaya yang sudah dihitung di $calculation
                $shipping_cost = $calculation['shipping_cost'];
                $insurance_cost = ($validatedData['ansuransi'] == 'iya') ? $calculation['ansuransi_fee'] : 0;

                $kiriminResponse = $this->_createKiriminAjaOrder(
                    $validatedData, 
                    $pesanan, 
                    $kirimaja, 
                    $senderAddressData, 
                    $receiverAddressData, 
                    $cod_value,
                    $shipping_cost, // <-- Ongkir murni
                    $insurance_cost // <-- Biaya asuransi murni
                );
                
                if (($kiriminResponse['status'] ?? false) !== true) {
                    throw new Exception($kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.'));
                }
                
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            
            } else { 
                // *** BAGIAN 1: KE TRIPAY DULU ***
                // Hanya buat tagihan Tripay, JANGAN push ke KiriminAja
                $orderItemsPayload = $this->_prepareOrderItemsPayload($calculation['shipping_cost'], $calculation['ansuransi_fee'], $validatedData['ansuransi']);
                $response = $this->_createTripayTransaction($validatedData, $pesanan, $total_paid_ongkir, $orderItemsPayload);

                if (empty($response['success'])) {
                    throw new Exception('Gagal membuat transaksi pembayaran. Pesan: ' . ($response['message'] ?? 'Tidak ada pesan.'));
                }

                $pesanan->payment_url = $response['data']['checkout_url'];
                // Status tetap 'Menunggu Pembayaran' (sudah diatur di _preparePesananData)
            }
            
            // 9. Simpan finalisasi data pesanan
            if ($cod_value > 0) {
                $pesanan->price = $cod_value;
            } else {
                $pesanan->price = $total_paid_ongkir;
            }
            $pesanan->save(); // Simpan perubahan status, resi, harga, customer_id
            DB::commit();

            // 10. Kirim notifikasi WhatsApp (setelah commit)
            $notification_total = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;
            $this->_sendWhatsappNotification($pesanan, $validatedData, $calculation['shipping_cost'], $calculation['ansuransi_fee'], $calculation['cod_fee'], $notification_total);
            $notifMessage = 'Pesanan baru dengan resi ' . ($pesanan->resi ?? $pesanan->nomor_invoice) . ' berhasil dibuat!';
            
            // 11. Arahkan pengguna ke halaman sukses atau pembayaran
            if (!empty($pesanan->payment_url)) {
                return redirect()->away($pesanan->payment_url); // Ke Tripay
            }
            
            // Ke index jika COD/Saldo
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
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        return view('admin.pesanan.show', compact('order'));
    }

    public function edit($resi)
    {
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
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
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        $order->update($validatedData);
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil diperbarui.');
    }

    public function destroy($resi)
    {
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        $order->delete();
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil dihapus.');
    }

    public function updateStatus(Request $request, $resi)
    {
        $request->validate(['status' => 'required|string|in:Terkirim,Batal,Diproses,Menunggu Pickup']);
        $pesanan = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
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
                'item_type' => 'required|integer|exists:package_types,id', // Validasi item_type
                'ansuransi' => 'required|string|in:iya,tidak', // Validasi ansuransi
                'length' => 'nullable|numeric|min:1', // Validasi dimensi opsional
                'width' => 'nullable|numeric|min:1',
                'height' => 'nullable|numeric|min:1',
            ]);

            $senderData = $this->_getAddressData($request, 'sender');
            $receiverData = $this->_getAddressData($request, 'receiver');
            
            // Periksa koordinat HANYA jika service_type adalah instant/sameday
            if (in_array($validated['service_type'], ['instant', 'sameday']) && (empty($senderData['lat']) || empty($receiverData['lat']))) {
                return response()->json(['status' => false, 'message' => 'Koordinat alamat pengirim atau penerima tidak ditemukan/valid, tidak dapat menghitung ongkir instan/sameday.'], 422);
            }
    
            $itemValue = $validated['item_price']; 
            $options = [];
            
            // Asuransi wajib berdasarkan item_type
            $mandatoryTypes = [1, 3, 4, 8]; // Sesuaikan ID package_types jika berbeda
            $isMandatory = in_array((int) $validated['item_type'], $mandatoryTypes);
            
            if($isMandatory && $validated['ansuransi'] == 'tidak') {
                return response()->json(['status' => false, 'message' => 'Jenis barang ini wajib menggunakan asuransi.'], 422);
            }

            // Tentukan apakah menggunakan asuransi
            $useInsurance = ($validated['ansuransi'] == 'iya') ? 1 : 0;

            if (in_array($validated['service_type'], ['instant', 'sameday'])) {
                // Pastikan lat/lng valid sebelum memanggil API
                if (empty($senderData['lat']) || empty($senderData['lng']) || empty($receiverData['lat']) || empty($receiverData['lng'])) {
                    return response()->json(['status' => false, 'message' => 'Koordinat tidak valid untuk ongkir instan/sameday.'], 422);
                }
                $options = $kirimaja->getInstantPricing(
                    $senderData['lat'], $senderData['lng'], $request->sender_address, 
                    $receiverData['lat'], $receiverData['lng'], $request->receiver_address, 
                    $validated['weight'], $itemValue, 'motor' // Asumsi motor
                );
            } else { // regular, express, cargo
                $category = $validated['service_type'] === 'cargo' ? 'trucking' : 'regular';
                // Gunakan dimensi dari request jika ada, fallback ke 1
                $length = $request->input('length', 1);
                $width = $request->input('width', 1);
                $height = $request->input('height', 1);
                
                $options = $kirimaja->getExpressPricing(
                    $validated['sender_district_id'], $validated['sender_subdistrict_id'], 
                    $validated['receiver_district_id'], $validated['receiver_subdistrict_id'], 
                    $validated['weight'], 
                    $length, $width, $height, // Gunakan dimensi
                    $itemValue, 
                    null, // Biarkan KiriminAja mengembalikan semua kurir
                    $category, 
                    $useInsurance // Gunakan variabel asuransi
                );
            }
            
            // Log request dan response untuk debugging
            Log::info('Cek Ongkir Request:', $request->all());
            Log::info('Cek Ongkir KiriminAja Options:', ['options' => $options]);

            return response()->json($options);
        } catch (ValidationException $e) {
            Log::error('Cek Ongkir Validation Error:', ['errors' => $e->errors()]);
            return response()->json(['status' => false, 'message' => 'Input tidak valid.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Cek Ongkir General Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['status' => false, 'message' => 'Terjadi kesalahan saat cek ongkir: ' . $e->getMessage()], 500);
        }
    }

    public function count()
    {
        $count = Pesanan::where('status', 'baru')->where('telah_dilihat', false)->count();
        return response()->json(['count' => $count]);
    }

    public function cetakResiThermal(string $resi)
    {
        // Cari berdasarkan resi ATAU nomor_invoice
        $pesanan = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }

    // --- BATAS FUNGSI PUBLIK ---

    /**
     * =========================================================================
     * FUNGSI WEBHOOK / CALLBACK (JEMBATAN PENGGABUNG)
     * =========================================================================
     *
     * Menerima callback/webhook dari Tripay ketika status pembayaran berubah.
     * Ini adalah "jembatan" yang Anda minta.
     */
    public function TripayCallback(Request $request, KiriminAjaService $kirimaja)
    {
        // 1. Ambil data JSON dari body request
        $json = $request->getContent();
        $data = json_decode($json, true);

        Log::info('Tripay Callback Received:', $data ?? ['raw' => $json]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Tripay Callback: Invalid JSON received.');
            return response()->json(['success' => false, 'message' => 'Invalid JSON'], 400);
        }

        // 2. Validasi Signature (Sangat Penting!)
        $privateKey = config('tripay.private_key');
        $callbackSignature = $request->header('X-Callback-Signature');
        $expectedSignature = hash_hmac('sha256', $json, $privateKey);

        if (!$callbackSignature || !hash_equals($expectedSignature, $callbackSignature)) {
            Log::warning('Tripay Callback: Invalid Signature.', [
                'received' => $callbackSignature,
                'expected' => $expectedSignature
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid Signature'], 401);
        }

        // 3. Proses Data Callback
        $merchantRef = $data['merchant_ref'] ?? null;
        $status = $data['status'] ?? null;

        if (!$merchantRef || !$status) {
            Log::warning('Tripay Callback: Missing merchant_ref or status.', ['data' => $data]);
            return response()->json(['success' => false, 'message' => 'Missing required data'], 400);
        }

        DB::beginTransaction();
        try {
            // 4. Cari Pesanan di database
            // Kunci pesanan agar tidak terjadi race condition
            $pesanan = Pesanan::where('nomor_invoice', $merchantRef)->lockForUpdate()->first();

            if (!$pesanan) {
                Log::error('Tripay Callback: Pesanan not found.', ['merchant_ref' => $merchantRef]);
                DB::rollBack(); // Tidak perlu rollback jika hanya 'not found', tapi aman
                return response()->json(['success' => true, 'message' => 'Pesanan not found, ignoring.']);
            }

            // 5. Cek Status Pembayaran
            if ($status === 'PAID') {
                // Cek apakah pesanan sudah diproses sebelumnya (penting!)
                if ($pesanan->status !== 'Menunggu Pembayaran') {
                    Log::info('Tripay Callback: Pesanan already processed, skipping.', ['invoice' => $merchantRef, 'status' => $pesanan->status]);
                    DB::commit(); // Commit saja, anggap sukses
                    return response()->json(['success' => true, 'message' => 'Already processed']);
                }

                // *** INILAH PENGGABUNGANNYA! ***
                // *** BAGIAN 2: BUAT ORDER KIRIMIN AJA SETELAH LUNAS ***
                Log::info('Tripay Callback: Payment PAID. Creating KiriminAja order...', ['invoice' => $merchantRef]);

                // 6. Siapkan data untuk KiriminAja dari pesanan yang DISIMPAN
                // Ini penting agar data alamatnya konsisten
                $validatedData = $pesanan->toArray(); // Ambil semua data dari model
                
                // Ambil data alamat spesifik
                $senderAddressData = [
                    'lat' => $pesanan->sender_lat, 'lng' => $pesanan->sender_lng,
                    'kirimaja_data' => [
                        'district_id' => $pesanan->sender_district_id,
                        'subdistrict_id' => $pesanan->sender_subdistrict_id,
                        'postal_code' => $pesanan->sender_postal_code,
                    ]
                ];
                $receiverAddressData = [
                    'lat' => $pesanan->receiver_lat, 'lng' => $pesanan->receiver_lng,
                    'kirimaja_data' => [
                        'district_id' => $pesanan->receiver_district_id,
                        'subdistrict_id' => $pesanan->receiver_subdistrict_id,
                        'postal_code' => $pesanan->receiver_postal_code,
                    ]
                ];
                
                // Hitung ulang biaya dari string 'expedition'
                $calculation = $this->_calculateTotalPaid($validatedData);
                $cod_value = 0; // Set 0 karena ini pembayaran online
                $shipping_cost = $calculation['shipping_cost'];
                $insurance_cost = ($validatedData['ansuransi'] == 'iya') ? $calculation['ansuransi_fee'] : 0;

                // 7. Panggil API KiriminAja
                $kiriminResponse = $this->_createKiriminAjaOrder(
                    $validatedData,
                    $pesanan,
                    $kirimaja,
                    $senderAddressData,
                    $receiverAddressData,
                    $cod_value,
                    $shipping_cost,
                    $insurance_cost
                );

                if (($kiriminResponse['status'] ?? false) !== true) {
                    // Jika gagal, JANGAN rollback. Log error agar admin bisa proses manual.
                    // Pembayaran sudah masuk, jangan batalkan.
                    Log::critical('Tripay Callback: KiriminAja Order FAILED after payment!', [
                        'invoice' => $merchantRef,
                        'response' => $kiriminResponse
                    ]);
                    // Simpan status khusus agar admin tahu
                    $pesanan->status = 'Pembayaran Lunas (Gagal Auto-Resi)';
                    $pesanan->status_pesanan = 'Pembayaran Lunas (Gagal Auto-Resi)';
                    // TODO: Kirim notifikasi ke admin di sini
                } else {
                    // 8. SUKSES! Simpan Resi dan update status
                    $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
                    $pesanan->status = 'Menunggu Pickup';
                    $pesanan->status_pesanan = 'Menunggu Pickup';
                    Log::info('Tripay Callback: KiriminAja Order SUCCESS.', ['invoice' => $merchantRef, 'resi' => $pesanan->resi]);
                }

                $pesanan->payment_status = 'PAID'; // Tambah kolom ini jika ada
                $pesanan->save();
            
            } elseif (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
                // Pembayaran Gagal atau Kadaluarsa
                Log::info('Tripay Callback: Payment FAILED/EXPIRED.', ['invoice' => $merchantRef, 'status' => $status]);
                
                // Hanya update jika statusnya masih 'Menunggu Pembayaran'
                if ($pesanan->status === 'Menunggu Pembayaran') {
                    $pesanan->status = ($status === 'EXPIRED') ? 'Kadaluarsa' : 'Gagal Bayar';
                    $pesanan->status_pesanan = ($status === 'EXPIRED') ? 'Kadaluarsa' : 'Gagal Bayar';
                    $pesanan->payment_status = $status;
                    $pesanan->save();
                }
            }
            
            // 9. Commit transaksi database
            DB::commit();
            return response()->json(['success' => true]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::critical('Tripay Callback: CRITICAL ERROR in processing.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            // Kembalikan error 500 agar Tripay mencoba lagi (jika dikonfigurasi)
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
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
            'height' => 'nullable|numeric|min:0', 
            'save_sender' => 'nullable', 'save_receiver' => 'nullable',
            'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
            'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
            'customer_id' => 'required_if:payment_method,Potong Saldo|nullable|exists:pengguna,id_pengguna',
            'sender_lat' => 'nullable|numeric', 'sender_lng' => 'nullable|numeric',
            'receiver_lat' => 'nullable|numeric', 'receiver_lng' => 'nullable|numeric',
            'sender_note' => 'nullable|string|max:255', 'receiver_note' => 'nullable|string|max:255',
            'item_type' => 'required|integer|exists:package_types,id',
            // Tambahkan validasi 'customer_email' jika tidak ada di model User
            'customer_email' => 'nullable|email', 
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

            if (!$response->successful()) {
                Log::error("Geocoding HTTP error: " . $response->status(), ['address' => $address, 'body' => $response->body()]);
                return null;
            }

            $json = $response->json();
            
            if (empty($json)) {
                Log::warning("Geocoding returned empty array for address: " . $address);
                return null;
            }

            if (!isset($json[0]['lat']) || !isset($json[0]['lon'])) {
                Log::error("Geocoding response missing lat/lon", ['address' => $address, 'response' => $json]);
                return null;
            }

            return !empty($json[0])
                ? ['lat' => (float) $json[0]['lat'], 'lng' => (float) $json[0]['lon']]
                : null;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Geocoding connection failed: " . $e->getMessage(), ['address' => $address]);
            return null;
        } catch (\Exception $e) {
            Log::error("Geocoding general error: " . $e->getMessage(), ['address' => $address, 'trace' => $e->getTraceAsString()]);
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

        $fullAddressQuery = null; // Untuk logging
        $partsQuery = null; // Untuk logging

        // jika lat/lng kosong atau tidak valid -> coba geocode
        if (!is_numeric($lat) || !is_numeric($lng) || $lat == 0 || $lng == 0) {
            Log::info("Geocoding needed for {$type} address", ['current_lat' => $lat, 'current_lng' => $lng]);
            $lat = null; // Reset lat/lng agar geocoding dijalankan
            $lng = null;

            // 1. coba full address (lebih akurat)
            if ($request->filled("{$type}_address") && $request->filled("{$type}_regency")) {
                $fullAddressQuery = $request->input("{$type}_address") . ', ' . $request->input("{$type}_village") . ', ' . $request->input("{$type}_district") . ', ' . $request->input("{$type}_regency") . ', ' . $request->input("{$type}_province") . ', Indonesia';
                if ($geo = $this->geocode($fullAddressQuery)) {
                    $lat = $geo['lat'];
                    $lng = $geo['lng'];
                    Log::info("Geocode successful (using full address) for {$type}", ['lat' => $lat, 'lng' => $lng]);
                } else {
                    Log::warning("Geocode failed (using full address) for {$type}", ['query' => $fullAddressQuery]);
                }
            }

            // 2. kalau gagal atau full address tidak lengkap, fallback ke parts
            if ((!$lat || !$lng)) {
                $parts = [
                    $request->input("{$type}_village"),
                    $request->input("{$type}_district"),
                    $request->input("{$type}_regency"),
                    $request->input("{$type}_province"),
                    $request->input("{$type}_postal_code"),
                ];
                // Hanya gunakan parts yang tidak kosong
                $filteredParts = array_filter($parts, fn($value) => !is_null($value) && $value !== '');
                if (!empty($filteredParts)) {
                    $partsQuery = implode(', ', $filteredParts) . ', Indonesia';
                    if ($geo = $this->geocode($partsQuery)) {
                        $lat = $geo['lat'];
                        $lng = $geo['lng'];
                        Log::info("Geocode successful (using parts) for {$type}", ['lat' => $lat, 'lng' => $lng]);
                    } else {
                        Log::warning("Geocode failed (using parts) for {$type}", ['query' => $partsQuery]);
                    }
                } else {
                    Log::warning("Geocode skipped for {$type} (not enough address parts provided)");
                }
            }
        } else {
            Log::info("Using provided lat/lng for {$type}", ['lat' => $lat, 'lng' => $lng]);
        }
        
        $finalLat = (is_numeric($lat) && $lat != 0) ? (float)$lat : null;
        $finalLng = (is_numeric($lng) && $lng != 0) ? (float)$lng : null;

        return ['lat' => $finalLat, 'lng' => $finalLng, 'kirimaja_data' => $kirimajaAddr];
    }

    private function _saveOrUpdateKontak(array $data, string $prefix, string $tipe)
    {
        if (!empty($data["save_{$prefix}"])) {
            $sanitizedPhone = $this->_sanitizePhoneNumber($data["{$prefix}_phone"]);
            if (empty($sanitizedPhone)) {
                Log::warning("Attempted to save contact with invalid phone number for {$prefix}", ['data' => $data]);
                return; // Jangan simpan jika nomor HP tidak valid
            }
            
            Kontak::updateOrCreate(
                ['no_hp' => $sanitizedPhone],
                [
                    'nama'        => $data["{$prefix}_name"],
                    'no_hp'       => $sanitizedPhone, 
                    'alamat'      => $data["{$prefix}_address"],
                    'province'    => $data["{$prefix}_province"],
                    'regency'     => $data["{$prefix}_regency"],
                    'district'    => $data["{$prefix}_district"],
                    'village'     => $data["{$prefix}_village"],
                    'postal_code' => $data["{$prefix}_postal_code"],
                    'tipe'        => function ($existing) use ($tipe) {
                        if ($existing && $existing->tipe === 'Keduanya') return 'Keduanya';
                        if ($existing && $existing->tipe !== $tipe) return 'Keduanya';
                        return $tipe;
                    },
                ]
            );
        }
    }
    
    private function _preparePesananData(array $validatedData, int $total_ongkir, string $ip, string $userAgent): array
    {
        do {
            $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6));
        } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

        $fieldsToSave = [
            'sender_name', 'sender_phone', 'sender_address', 'sender_province', 'sender_regency', 
            'sender_district', 'sender_village', 'sender_postal_code', 
            'receiver_name', 'receiver_phone', 'receiver_address', 'receiver_province', 'receiver_regency', 
            'receiver_district', 'receiver_village', 'receiver_postal_code', 
            'item_description', 'item_price', 'weight', 'length', 'width', 'height', 
            'service_type', 'expedition', 'payment_method', 'ansuransi', 'item_type', 
            'customer_id',
            'sender_district_id', 'sender_subdistrict_id', 
            'receiver_district_id', 'receiver_subdistrict_id',
            'sender_lat', 'sender_lng', 
            'receiver_lat', 'receiver_lng',
            'sender_note', 'receiver_note',
            'pengirim_id', 'penerima_id'
        ];
        
        $pesananCoreData = collect($validatedData)->only($fieldsToSave)->all();
        
        return array_merge(
            $pesananCoreData,
            [
                'nomor_invoice' => $nomorInvoice,
                'status' => 'Menunggu Pembayaran', // Status awal untuk SEMUA
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
        $parts = explode('-', $validatedData['expedition']);
        $count = count($parts);

        $cod_fee       = 0;
        $ansuransi_fee = 0;
        $shipping_cost = 0;

        if ($count >= 6) { // Format lengkap (6+ parts)
            $cod_fee       = (int) ($parts[$count - 1] ?? 0); // Ambil dari akhir
            $ansuransi_fee = (int) ($parts[$count - 2] ?? 0);
            $shipping_cost = (int) ($parts[$count - 3] ?? 0);
        } elseif ($count === 5) { // Format non-COD (5 parts)
            $ansuransi_fee = (int) ($parts[4] ?? 0);
            $shipping_cost = (int) ($parts[3] ?? 0);
        } elseif ($count === 4) { // Bisa non-COD non-Asuransi, ATAU instant
            $shipping_cost = (int) ($parts[3] ?? 0);
        } else {
            Log::warning('Format string expedition tidak dikenal saat kalkulasi biaya:', ['expedition' => $validatedData['expedition']]);
        }
        
        $cod_value = 0; // Total yang ditagih kurir

        // Total ongkir yang dibayar PEMBUAT ORDER (ongkir + asuransi jika dipilih)
        $total_paid_ongkir = $shipping_cost;
        if ($validatedData['ansuransi'] == 'iya') {
            if ($ansuransi_fee > 0) {
                $total_paid_ongkir += $ansuransi_fee;
            } else {
                Log::warning('Asuransi dipilih "iya" tapi biaya asuransi 0 dari string expedition', ['expedition' => $validatedData['expedition']]);
            }
        }
        
        // Hitung total tagihan kurir jika COD
        if ($validatedData['payment_method'] === 'CODBARANG') {
            if ($cod_fee <= 0 && $count < 6) {
                Log::error('Metode CODBARANG dipilih tapi COD Fee tidak ditemukan', ['expedition' => $validatedData['expedition']]);
            }
            $cod_value = (int)$validatedData['item_price'] + $total_paid_ongkir + $cod_fee;
        } elseif ($validatedData['payment_method'] === 'COD') { // Hanya tagih ongkir + cod_fee
            if ($cod_fee <= 0 && $count < 6) {
                Log::error('Metode COD dipilih tapi COD Fee tidak ditemukan', ['expedition' => $validatedData['expedition']]);
            }
            $cod_value = $total_paid_ongkir + $cod_fee;
        }
        
        Log::info('Kalkulasi Biaya:', [
            'expedition_string' => $validatedData['expedition'],
            'parsed_shipping' => $shipping_cost,
            'parsed_insurance' => $ansuransi_fee,
            'parsed_cod_fee' => $cod_fee,
            'ansuransi_selected' => $validatedData['ansuransi'],
            'payment_method' => $validatedData['payment_method'],
            'total_paid_ongkir' => $total_paid_ongkir, // Yg dibayar non-COD / Saldo
            'cod_value_to_collect' => $cod_value // Yg ditagih kurir
        ]);

        return compact('total_paid_ongkir', 'cod_value', 'shipping_cost', 'ansuransi_fee', 'cod_fee');
    }
    
    /**
     * PERBAIKAN: Fungsi ini sekarang menerima biaya ongkir & asuransi
     * agar tidak parsing string 2x
     */
    private function _createKiriminAjaOrder(
        array $data, 
        Pesanan $pesanan, 
        KiriminAjaService $kirimaja, 
        array $senderData, 
        array $receiverData, 
        int $cod_value,
        int $shipping_cost,  // <-- Parameter Baru
        int $insurance_cost // <-- Parameter Baru
    ): array
    {
        $expeditionParts = explode('-', $data['expedition']);
        $serviceGroup = $expeditionParts[0] ?? null;
        $courier      = $expeditionParts[1] ?? null;
        $service_type = $expeditionParts[2] ?? null;
        
        if (in_array($serviceGroup, ['instant', 'sameday'])) {
            if (empty($senderData['lat']) || empty($senderData['lng']) || empty($receiverData['lat']) || empty($receiverData['lng'])) {
                Log::error("Lat/Lng tidak valid saat mencoba membuat order instant/sameday", ['invoice' => $pesanan->nomor_invoice]);
                return ['status' => false, 'text' => 'Koordinat alamat tidak valid untuk pengiriman instan/sameday.'];
            }

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
                        'shipping_price' => (int)$shipping_cost, // <-- Gunakan parameter
                        'item' => [
                            'name' => $data['item_description'],
                            'description' => 'Pesanan dari pelanggan',
                            'price' => (int)$data['item_price'],
                            'weight' => (int)$data['weight'],
                        ]
                    ]
                ]
            ];
            Log::info('KiriminAja Create Instant Order Payload:', $payload);
            return $kirimaja->createInstantOrder($payload);

        } else { // Express, Regular, Cargo
            $scheduleResponse = $kirimaja->getSchedules();
            $scheduleClock = $scheduleResponse['clock'] ?? null;
            
            $category = ($data['service_type'] ?? $serviceGroup) === 'cargo' ? 'trucking' : 'regular';
            
            // Hitung final weight (termasuk volumetrik jika dimensi ada)
            $weightInput = (int) $data['weight'];
            $lengthInput = (int) ($data['length'] ?? 1);
            $widthInput = (int) ($data['width'] ?? 1);
            $heightInput = (int) ($data['height'] ?? 1);
            
            $volumetricWeight = 0;
            if ($lengthInput > 0 && $widthInput > 0 && $heightInput > 0) {
                $volumetricWeight = ($widthInput * $lengthInput * $heightInput) / ($category === 'trucking' ? 4000 : 6000) * 1000;
            }
            $finalWeight = max($weightInput, $volumetricWeight);
            
            // Tentukan jumlah asuransi
            $insuranceAmount = 0;
            if ($data['ansuransi'] == 'iya') {
                if ($insurance_cost > 0) { // Gunakan asuransi jika ada biayanya
                    $insuranceAmount = (int)$data['item_price'];
                } else {
                     Log::warning("Asuransi dipilih 'iya' tapi biaya asuransi 0.", ['invoice' => $pesanan->nomor_invoice]);
                }
            }

            $payload = [
                'address' => $data['sender_address'], 
                'phone' => $data['sender_phone'], 
                'name' => $data['sender_name'],
                'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 
                'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
                'zipcode' => $senderData['kirimaja_data']['postal_code'], 
                'schedule' => $scheduleClock,
                'platform_name' => 'tokosancaka.com',
                'category' => $category,
                'latitude' => $senderData['lat'],
                'longitude' => $senderData['lng'],
                'packages' => [[
                    'order_id' => $pesanan->nomor_invoice, 
                    'item_name' => $data['item_description'], 
                    'package_type_id' => (int)$data['item_type'],
                    'destination_name' => $data['receiver_name'], 
                    'destination_phone' => $data['receiver_phone'], 
                    'destination_address' => $data['receiver_address'],
                    'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 
                    'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                    'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                    'weight' => ceil($finalWeight),
                    'width' => $widthInput, 
                    'height' => $heightInput, 
                    'length' => $lengthInput,
                    'item_value' => (int)$data['item_price'],
                    'service' => $courier, 
                    'service_type' => $service_type,
                    'insurance_amount' => $insuranceAmount,
                    'cod' => $cod_value,
                    'shipping_cost' => (int)$shipping_cost // <-- Gunakan parameter
                ]]
            ];
            Log::info('KiriminAja Create Express Order Payload:', $payload);
            return $kirimaja->createExpressOrder($payload);
        }
    }

    private function _prepareOrderItemsPayload(int $shipping_cost, int $ansuransi_fee, string $ansuransi_choice): array
    {
        $payload = [['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1]];
        if ($ansuransi_choice == 'iya' && $ansuransi_fee > 0) {
            $payload[] = ['sku' => 'INSURANCE', 'name' => 'Biaya Asuransi', 'price' => $ansuransi_fee, 'quantity' => 1];
        } elseif ($ansuransi_choice == 'iya' && $ansuransi_fee <= 0) {
            Log::warning("Asuransi dipilih 'iya' tapi biaya asuransi 0, item asuransi tidak ditambahkan ke Tripay.", ['shipping_cost' => $shipping_cost]);
        }
        return $payload;
    }

    private function _createTripayTransaction(array $data, Pesanan $pesanan, int $total, array $orderItems): array
    {
        $apiKey = config('tripay.api_key');
        $privateKey = config('tripay.private_key');
        $merchantCode = config('tripay.merchant_code');
        $mode = config('tripay.mode', 'sandbox');

        if ($total <= 0) {
            Log::error("Attempted to create Tripay transaction with zero or negative amount.", ['invoice' => $pesanan->nomor_invoice, 'total' => $total]);
            return ['success' => false, 'message' => 'Jumlah pembayaran tidak valid.'];
        }

        // Cek apakah email pelanggan ada di data, jika tidak, coba ambil dari User (jika Potong Saldo) atau default
        $customerEmail = $data['customer_email'] ?? 'customer@tokosancaka.com'; // Default
        if (empty($data['customer_email']) && !empty($data['customer_id'])) {
             $customer = User::find($data['customer_id']);
             if ($customer && $customer->email) {
                 $customerEmail = $customer->email;
             }
        }
        // Fallback jika email masih tidak valid
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
             $customerEmail = 'customer+' . Str::random(5) . '@tokosancaka.com';
        }
        
        $payload = [
            'method'         => $data['payment_method'],
            'merchant_ref'   => $pesanan->nomor_invoice,
            'amount'         => $total,
            'customer_name'  => $data['receiver_name'], // Gunakan nama penerima
            'customer_email' => $customerEmail,
            'customer_phone' => $data['receiver_phone'], // Gunakan telp penerima
            'order_items'    => $orderItems,
            'return_url'     => route('tracking.show', ['resi' => $pesanan->nomor_invoice]),
            'expired_time'   => time() + (1 * 60 * 60), // 1 jam
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];
        
        Log::info('Tripay Create Transaction Payload:', $payload);

        $baseUrl = $mode === 'production' 
            ? 'https://tripay.co.id/api/transaction/create' 
            : 'https://tripay.co.id/api-sandbox/transaction/create';
        
        try {
            // --- PERCOBAAN PERBAIKAN ---
            // Coba tambahkan ->withoutVerifying() untuk bypass cek SSL
            // Ini adalah langkah debugging untuk masalah koneksi di server
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(30)
                ->withoutVerifying() // <-- PERUBAHAN DI SINI
                ->post($baseUrl, $payload);

            if (!$response->successful()) {
                Log::error('Tripay API Request Failed (HTTP Error):', [
                    'status' => $response->status(), 
                    'body' => $response->body(),
                    'payload' => $payload
                ]);
                return ['success' => false, 'message' => 'Gagal menghubungi server pembayaran (HTTP: ' . $response->status() . '). Coba lagi nanti.'];
            }
            
            $responseData = $response->json();
            Log::info('Tripay Create Transaction Response:', $responseData ?? ['raw' => $response->body()]);

            if (!isset($responseData['success'])) {
                Log::error('Tripay API Response missing "success" key:', ['response' => $responseData]);
                return ['success' => false, 'message' => 'Respon tidak valid dari server pembayaran.'];
            }

            return $responseData;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Tripay API Connection Failed: ' . $e->getMessage(), ['payload' => $payload]);
            // --- PERCOBAAN PERBAIKAN ---
            // Berikan pesan error yang lebih spesifik jika kita mematikan verifikasi
            $message = 'Tidak dapat terhubung ke server pembayaran. Periksa koneksi Anda.';
            if (str_contains(strtolower($e->getMessage()), 'ssl') || str_contains(strtolower($e->getMessage()), 'certificate')) {
                $message = 'Koneksi gagal karena masalah verifikasi SSL di server PHP. (Bypass SSL dicoba).';
            }
            return ['success' => false, 'message' => $message];
        } catch (Exception $e) {
            Log::error('Tripay API General Error: ' . $e->getMessage(), ['payload' => $payload, 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => 'Terjadi kesalahan internal saat menghubungi server pembayaran.'];
        }
    }
    
    private function _sanitizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (Str::startsWith($phone, '62')) {
            if (Str::startsWith(substr($phone, 2), '0')) {
                return '0' . substr($phone, 3); 
            }
            return '0' . substr($phone, 2);
        }
        if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) {
            return '0' . $phone;
        }
        return $phone; 
    }

    private function _sendWhatsappNotification(
        Pesanan $pesanan,
        array $validatedData,
        int $shipping_cost,
        int $ansuransi_fee,
        int $cod_fee,
        int $total_paid
    ) {
        if (empty($validatedData['sender_phone']) || empty($validatedData['receiver_phone'])) {
            Log::warning('Nomor telepon pengirim atau penerima kosong, notifikasi WA dibatalkan.', ['invoice' => $pesanan->nomor_invoice]);
            return;
        }

        // 1. Detail Paket
        $detailPaket = "*Detail Paket:*\n";
        $detailPaket .= "Deskripsi Barang: " . ($validatedData['item_description'] ?? '-') . "\n";
        $detailPaket .= "Berat: " . ($validatedData['weight'] ?? 0) . " Gram\n";

        if (!empty($validatedData['length']) && !empty($validatedData['width']) && !empty($validatedData['height'])) {
            $detailPaket .= "Dimensi: {$validatedData['length']} x {$validatedData['width']} x {$validatedData['height']} cm\n";
        }

        $expeditionParts = explode('-', $validatedData['expedition']);
        $exp_vendor = $expeditionParts[1] ?? '';
        $exp_service_type = $expeditionParts[2] ?? '';
        $service_display = trim(ucwords(strtolower(str_replace('_', ' ', $exp_vendor))) . ' ' . ucwords(strtolower(str_replace('_', ' ', $exp_service_type))));
        
        $detailPaket .= "Ekspedisi: " . $service_display . "\n";
        $detailPaket .= "Layanan: " . ucwords($validatedData['service_type']);
        if ($pesanan->resi) {
            $detailPaket .= "\nResi: *" . $pesanan->resi . "*";
        }

        // 2. Rincian Biaya
        $rincianBiaya = "*Rincian Biaya:*\n";
        $rincianBiaya .= "- Ongkir: Rp " . number_format($shipping_cost, 0, ',', '.') . "\n";
        $rincianBiaya .= "- Nilai Barang: Rp " . number_format($validatedData['item_price'], 0, ',', '.');
        if ($ansuransi_fee > 0) {
            $rincianBiaya .= "\n- Asuransi: Rp " . number_format($ansuransi_fee, 0, ',', '.');
        }
        if ($cod_fee > 0) {
            $rincianBiaya .= "\n- Biaya COD: Rp " . number_format($cod_fee, 0, ',', '.');
        }

        // 3. Status Pembayaran
        $statusBayar = '';
        if ($pesanan->payment_method === 'COD' || $pesanan->payment_method === 'CODBARANG') {
            $statusBayar = "⏳ *Bayar di Tempat (COD)*";
        } elseif ($pesanan->payment_method === 'Potong Saldo') {
            $statusBayar = "✅ *Lunas via Saldo*";
        } else { // Tripay
            $statusBayar = "⏳ *Menunggu Pembayaran*";
        }

        // 4. Template Pesan
        $messageTemplate = <<<TEXT
*Terimakasih Ya Kak Atas Orderannya 🙏*

Berikut adalah Nomor Order ID / Nomor Invoice Kakak:
*{NOMOR_INVOICE}*

📦 Dari: *{SENDER_NAME}* ( {SENDER_PHONE} )
➡️ Ke: *{RECEIVER_NAME}* ( {RECEIVER_PHONE} )

----------------------------------------
{DETAIL_PAKET}
----------------------------------------
{RINCIAN_BIYA}
----------------------------------------
*Total Bayar: Rp {TOTAL_BAYAR}*
Status Pembayaran: {STATUS_BAYAR}
----------------------------------------

Semoga Paket Kakak aman dan selamat sampai tujuan. ✅

Cek status pesanan/resi dengan klik link berikut:
https://tokosancaka.com/tracking/search?resi={LINK_RESI}

*Manajemen Sancaka*
TEXT;

        // 5. Replace placeholder
        $linkResi = $pesanan->resi ?? $pesanan->nomor_invoice;
        $message = str_replace(
            [
                '{NOMOR_INVOICE}', 
                '{SENDER_NAME}', '{SENDER_PHONE}', 
                '{RECEIVER_NAME}', '{RECEIVER_PHONE}', 
                '{TOTAL_BAYAR}',
                '{RINCIAN_BIYA}', // <-- PERBAIKAN: Nama placeholder salah, harusnya {RINCIAN_BIAYA}
                '{DETAIL_PAKET}',
                '{STATUS_BAYAR}',
                '{LINK_RESI}'
            ],
            [
                $pesanan->nomor_invoice, 
                $validatedData['sender_name'], $validatedData['sender_phone'],
                $validatedData['receiver_name'], $validatedData['receiver_phone'],
                number_format($total_paid, 0, ',', '.'),
                $rincianBiaya, // <-- Ini variabel yang benar
                $detailPaket,
                $statusBayar,
                $linkResi
            ],
            $messageTemplate
        );

        // 6. Sanitasi nomor telepon untuk Fonnte & kirim pesan
        $senderWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($validatedData['sender_phone'])); 
        $receiverWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($validatedData['receiver_phone']));

        try {
            if ($senderWa) FonnteService::sendMessage($senderWa, $message);
            if ($receiverWa) FonnteService::sendMessage($receiverWa, $message);
            Log::info("Notifikasi WA Awal Terkirim untuk Invoice: " . $pesanan->nomor_invoice);
        } catch (Exception $e) {
            Log::error('Fonnte Service sendMessage (initial) failed: ' . $e->getMessage(), ['invoice' => $pesanan->nomor_invoice]);
        }
    }

} // Akhir Class

