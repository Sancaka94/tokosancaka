<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Kontak;
use App\Models\User;
use App\Models\TopUp; // Tetap import jika diperlukan di fungsi lain
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

        // Urutkan berdasarkan tanggal terbaru
        $orders = $query->orderBy('tanggal_pesanan', 'desc')->paginate(15);
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

            // Simpan nomor asli sebelum sanitasi untuk notifikasi WA
            $validatedData['sender_phone_original'] = $request->input('sender_phone');
            $validatedData['receiver_phone_original'] = $request->input('receiver_phone');

            // Sanitasi nomor telepon sebelum digunakan lebih lanjut
            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone_original']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone_original']);


            // 3. Kalkulasi semua biaya berdasarkan metode pembayaran yang dipilih
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid_ongkir = $calculation['total_paid_ongkir']; // Biaya ongkir + asuransi (jika ada)
            $cod_value = $calculation['cod_value'];   // Total yang harus ditagih kurir jika COD/CODBARANG
            $shipping_cost = $calculation['shipping_cost']; // Ongkir murni
            $insurance_cost = $calculation['ansuransi_fee']; // Biaya asuransi murni
            $cod_fee = $calculation['cod_fee']; // Biaya COD murni


            // 4. Siapkan data dan buat entri pesanan awal di database
            $pesananData = $this->_preparePesananData($validatedData, $total_paid_ongkir, $request->ip(), $request->userAgent());
            // Tambahkan biaya terpisah ke data pesanan agar tersimpan
            $pesananData['shipping_cost'] = $shipping_cost;
            $pesananData['insurance_cost'] = ($validatedData['ansuransi'] == 'iya') ? $insurance_cost : 0;
            $pesananData['cod_fee'] = ($cod_value > 0) ? $cod_fee : 0;
            $pesanan = Pesanan::create($pesananData);

            $paymentUrl = null; // Inisialisasi URL Pembayaran

            // 5. Proses logika pembayaran spesifik
            if ($validatedData['payment_method'] === 'Potong Saldo') {
                $customer = User::find($validatedData['customer_id']); // Gunakan find() lebih baik

                if (!$customer) {
                    throw new Exception('Pelanggan untuk potong saldo tidak ditemukan.');
                }

                if ($customer->saldo < $total_paid_ongkir) {
                    throw new Exception('Saldo pelanggan tidak mencukupi.');
                }

                $customer->decrement('saldo', $total_paid_ongkir);
                $pesanan->customer_id = $customer->id_pengguna; // Simpan relasi
                // Status akan diupdate setelah KiriminAja sukses
            }
            elseif (!in_array($validatedData['payment_method'], ['COD', 'CODBARANG'])) {
                 // Pembayaran Online via Tripay (Non-COD, Non-Saldo)
                // Hanya buat tagihan Tripay, JANGAN push ke KiriminAja dulu
                $orderItemsPayload = $this->_prepareOrderItemsPayload($shipping_cost, $insurance_cost, $validatedData['ansuransi']);

                // Panggil _createTripayTransaction dari sini (gunakan Http Client)
                $tripayResponse = $this->_createTripayTransactionInternal($validatedData, $pesanan, $total_paid_ongkir, $orderItemsPayload);

                if (empty($tripayResponse['success'])) {
                    throw new Exception('Gagal membuat transaksi pembayaran. Pesan: ' . ($tripayResponse['message'] ?? 'Tidak ada pesan.'));
                }

                // Dapatkan checkout_url dari response Tripay
                $paymentUrl = $tripayResponse['data']['checkout_url'] ?? null;
                $pesanan->payment_url = $paymentUrl; // Simpan payment URL
                // Status tetap 'Menunggu Pembayaran'
            }

            // 6. Proses pembuatan order ke API KiriminAja HANYA jika COD/Saldo
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'Potong Saldo'])) {
                // Langsung push ke KiriminAja
                $senderAddressData = $this->_getAddressData($request, 'sender');
                $receiverAddressData = $this->_getAddressData($request, 'receiver');

                $kiriminResponse = $this->_createKiriminAjaOrder(
                    $validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData,
                    $cod_value, $shipping_cost, $insurance_cost // Kirim biaya terpisah
                );

                if (($kiriminResponse['status'] ?? false) !== true) {
                    $errorMessage = $kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.');
                    throw new Exception($errorMessage);
                }

                // Update status dan resi jika KiriminAja sukses
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            }

            // 7. Simpan finalisasi data pesanan (harga total dan update lain)
            // 'price' sebaiknya adalah total yang harus dibayar customer
            $pesanan->price = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;
            $pesanan->save(); // Simpan semua perubahan (status, resi, harga, customer_id, payment_url)
            DB::commit();

            // 8. Kirim notifikasi WhatsApp (setelah commit)
            // --- PERBAIKAN: Cast $pesanan->insurance_cost dan $pesanan->cod_fee ke integer ---
            $notification_total = $pesanan->price; // Total bayar final
            $this->_sendWhatsappNotification(
                $pesanan,
                $validatedData,
                $shipping_cost,
                (int) $pesanan->insurance_cost, // Cast ke int
                (int) $pesanan->cod_fee,         // Cast ke int
                $notification_total,
                $request
            );
            $notifMessage = 'Pesanan baru ' . ($pesanan->resi ? 'dengan resi ' . $pesanan->resi : 'dengan invoice ' . $pesanan->nomor_invoice) . ' berhasil dibuat!';


            // 9. Arahkan pengguna
            if ($paymentUrl) {
                // Jika ada URL pembayaran Tripay, redirect ke sana
                return redirect()->away($paymentUrl);
            }

            // Ke index jika COD/Saldo
            return redirect()->route('admin.pesanan.index')->with('success', $notifMessage);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validasi gagal saat membuat pesanan:', $e->errors());
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Order Creation Failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }


    public function show($resi)
    {
        // PERBAIKAN: Gunakan parameter {resi} yang diterima route
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        return view('admin.pesanan.show', compact('order'));
    }

    public function edit($resi)
    {
        // PERBAIKAN: Gunakan parameter {resi} yang diterima route
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        $customers = User::orderBy('nama_lengkap', 'asc')->get();
        return view('admin.pesanan.edit', compact('order', 'customers'));
    }

    public function update(Request $request, $resi)
    {
        // ... (fungsi update tetap sama) ...
        $validatedData = $request->validate([
             'sender_name' => 'required|string|max:255',
             'sender_phone' => 'required|string|max:20',
             'sender_address' => 'required|string',
             'receiver_name' => 'required|string|max:255',
             'receiver_phone' => 'required|string|max:20',
             'receiver_address' => 'required|string',
             'item_description' => 'required|string',
             'weight' => 'required|numeric|min:1',
         ]);
        // PERBAIKAN: Gunakan parameter {resi} yang diterima route
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($request->input('sender_phone'));
        $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($request->input('receiver_phone'));
        $order->update($validatedData);
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . ($order->resi ?? $order->nomor_invoice) . ' berhasil diperbarui.');
    }

    public function destroy($resi)
    {
        // ... (fungsi destroy tetap sama) ...
        // PERBAIKAN: Gunakan parameter {resi} yang diterima route
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        $invoice = $order->nomor_invoice;
        $order->delete();
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $invoice . ' berhasil dihapus.');
    }

    public function updateStatus(Request $request, $resi)
    {
         // PERBAIKAN: Gunakan parameter {resi} yang diterima route
        $request->validate(['status' => 'required|string|in:Terkirim,Batal,Diproses,Menunggu Pickup, Kadaluarsa, Gagal Bayar']);
        $pesanan = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        $pesanan->update([
             'status' => $request->status,
             'status_pesanan' => $request->status,
         ]);
        return redirect()->back()->with('success', 'Status pesanan ' . ($pesanan->resi ?? $pesanan->nomor_invoice) . ' berhasil diubah menjadi "' . $request->status . '".');
    }

    public function exportExcel()
    {
        // ... (fungsi exportExcel tetap sama) ...
        return Excel::download(new PesanansExport(Pesanan::all()), 'semua-pesanan-' . date('Ymd') . '.xlsx');
    }

    public function exportPdf()
    {
         // ... (fungsi exportPdf tetap sama) ...
        $orders = Pesanan::all();
        $pdf = PDF::loadView('admin.pesanan.pdf', ['orders' => $orders])->setPaper('a4', 'landscape');
        return $pdf->download('semua-pesanan-' . date('Ymd') . '.pdf');
    }

    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        // ... (fungsi searchAddressApi tetap sama) ...
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
        // ... (fungsi searchKontak tetap sama) ...
         $request->validate([
             'search' => 'required|string|min:2',
             'tipe'   => 'nullable|in:Pengirim,Penerima',
         ]);
         $query = Kontak::query()->where(function ($q) use ($request) {
             $searchLower = strtolower($request->input('search'));
             $q->where(DB::raw('LOWER(nama)'), 'LIKE', '%' . $searchLower . '%')
               ->orWhere('no_hp', 'LIKE', "%{$request->input('search')}%");
         });
         if ($request->filled('tipe')) {
             $query->where(fn($q) => $q->where('tipe', $request->input('tipe'))->orWhere('tipe', 'Keduanya'));
         }
         return response()->json($query->limit(10)->get());
    }

    public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
    {
        // ... (fungsi cek_Ongkir tetap sama) ...
        try {
             $validated = $request->validate([
                 'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
                 'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
                 'item_price' => 'required|numeric|min:1',
                 'weight' => 'required|numeric|min:1',
                 'service_type' => 'required|string|in:regular,express,sameday,instant,cargo',
                 'item_type' => 'required|integer|exists:package_types,id',
                 'ansuransi' => 'required|string|in:iya,tidak',
                 'length' => 'nullable|numeric|min:1', 'width' => 'nullable|numeric|min:1', 'height' => 'nullable|numeric|min:1',
                 'sender_lat' => 'nullable|numeric', 'sender_lng' => 'nullable|numeric',
                 'receiver_lat' => 'nullable|numeric', 'receiver_lng' => 'nullable|numeric',
                 'sender_address' => 'required_if:service_type,instant,sameday|nullable|string',
                 'receiver_address' => 'required_if:service_type,instant,sameday|nullable|string',
             ]);
             // ... (logika cek ongkir) ...
             return response()->json($options);
         }
        catch (ValidationException $e) {
             Log::error('Cek Ongkir Validation Error:', ['errors' => $e->errors()]);
             return response()->json(['status' => false, 'message' => 'Input tidak valid.', 'errors' => $e->errors()], 422);
         }
        catch (Exception $e) {
             Log::error('Cek Ongkir General Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
             return response()->json(['status' => false, 'message' => 'Terjadi kesalahan saat cek ongkir: ' . $e->getMessage()], 500);
         }
    }

    public function count()
    {
        // ... (fungsi count tetap sama) ...
        $count = Pesanan::where('status', 'baru')->where('telah_dilihat', false)->count();
        return response()->json(['count' => $count]);
    }

    public function cetakResiThermal(string $resi)
    {
         // ... (fungsi cetakResiThermal tetap sama) ...
        $pesanan = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }

    // --- BATAS FUNGSI PUBLIK ---


    /**
     * =========================================================================
     * FUNGSI PROSESOR CALLBACK (DIPANGGIL OLEH CHECKOUT CONTROLLER)
     * =========================================================================
     * Memproses callback khusus untuk Pesanan (yang dibuat dari Admin Panel).
     * HARUS public static agar bisa dipanggil dari CheckoutController.
     */
    public static function processPesananCallback($merchantRef, $status, $callbackData)
    {
        Log::info('Processing Pesanan Callback (SCK-)...', ['ref' => $merchantRef, 'status' => $status]);
        $kirimaja = app(KiriminAjaService::class);
        $pesanan = Pesanan::where('nomor_invoice', $merchantRef)->lockForUpdate()->first();

        if (!$pesanan) {
            Log::error('Tripay Callback (Pesanan SCK-): Pesanan Not found in DB.', ['merchant_ref' => $merchantRef]);
            return;
        }

        if ($pesanan->status !== 'Menunggu Pembayaran') {
            Log::info('Tripay Callback (Pesanan SCK-): Already processed or not pending.', ['invoice' => $merchantRef, 'current_status' => $pesanan->status]);
            return;
        }

        if ($status === 'PAID') {
            Log::info('Tripay Callback (Pesanan SCK-): Found Pesanan in correct state. Proceeding...', ['invoice' => $merchantRef]);
            $pesanan->status = 'paid'; // Status sementara sebelum KiriminAja
            $pesanan->status_pesanan = 'paid';
            // HAPUS -> $pesanan->payment_status = 'PAID';
            $pesanan->save();
            Log::info('Tripay Callback (Pesanan SCK-): Status changed to PAID. Preparing KiriminAja call...', ['invoice' => $merchantRef]);

            try {
                $instance = new self();
                $validatedData = $pesanan->toArray();
                Log::debug('Tripay Callback (Pesanan SCK-): Pesanan data prepared.', ['data_count' => count($validatedData)]);
                $senderAddressData = [
                     'lat' => $pesanan->sender_lat, 'lng' => $pesanan->sender_lng,
                     'kirimaja_data' => ['district_id' => $pesanan->sender_district_id, 'subdistrict_id' => $pesanan->sender_subdistrict_id, 'postal_code' => $pesanan->sender_postal_code]
                 ];
                $receiverAddressData = [
                     'lat' => $pesanan->receiver_lat, 'lng' => $pesanan->receiver_lng,
                     'kirimaja_data' => ['district_id' => $pesanan->receiver_district_id, 'subdistrict_id' => $pesanan->receiver_subdistrict_id, 'postal_code' => $pesanan->receiver_postal_code]
                 ];
                Log::debug('Tripay Callback (Pesanan SCK-): Address data prepared.', ['sender' => $senderAddressData, 'receiver' => $receiverAddressData]);
                $cod_value = 0;
                $shipping_cost = (int) $pesanan->shipping_cost;
                $insurance_cost = (int) $pesanan->insurance_cost;
                Log::debug('Tripay Callback (Pesanan SCK-): Cost data prepared.', ['cod' => $cod_value, 'ship' => $shipping_cost, 'ins' => $insurance_cost]);
                Log::info('Tripay Callback (Pesanan SCK-): Calling _createKiriminAjaOrder...', ['invoice' => $merchantRef]);
                $kiriminResponse = $instance->_createKiriminAjaOrder(
                    $validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData,
                    $cod_value, $shipping_cost, $insurance_cost
                );
                Log::info('Tripay Callback (Pesanan SCK-): KiriminAja response received.', ['response' => $kiriminResponse]);

                if (($kiriminResponse['status'] ?? false) !== true) {
                    Log::critical('Tripay Callback (Pesanan SCK-): KiriminAja Order FAILED!', ['invoice' => $merchantRef, 'response' => $kiriminResponse]);
                    $pesanan->status = 'Pembayaran Lunas (Gagal Auto-Resi)';
                    $pesanan->status_pesanan = 'Pembayaran Lunas (Gagal Auto-Resi)';
                } else {
                    $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
                    $pesanan->status = 'Menunggu Pickup';
                    $pesanan->status_pesanan = 'Menunggu Pickup';
                    Log::info('Tripay Callback (Pesanan SCK-): KiriminAja Order SUCCESS. Status updated.', ['invoice' => $merchantRef, 'resi' => $pesanan->resi]);
                }
                $pesanan->save();
                Log::info('Tripay Callback (Pesanan SCK-): Final Pesanan status saved.', ['invoice' => $merchantRef, 'final_status' => $pesanan->status]);

            } catch (Exception $e) {
                 Log::error("Tripay Callback (Pesanan SCK-): Exception during KiriminAja process for PAID order.", [ 'ref' => $merchantRef, 'error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile() ]);
                 $pesanan->status = 'Pembayaran Lunas (Error Kirim API)';
                 $pesanan->status_pesanan = 'Pembayaran Lunas (Error Kirim API)';
                 $pesanan->save();
            }

        } elseif (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
            Log::info('Tripay Callback (Pesanan SCK-): Payment FAILED/EXPIRED.', ['invoice' => $merchantRef, 'status' => $status]);
            $pesanan->status = ($status === 'EXPIRED') ? 'Kadaluarsa' : 'Gagal Bayar';
            $pesanan->status_pesanan = ($status === 'EXPIRED') ? 'Kadaluarsa' : 'Gagal Bayar';
            // HAPUS -> $pesanan->payment_status = $status;
            $pesanan->save();
        } else {
             Log::warning('Tripay Callback (Pesanan SCK-): Received unknown status.', ['ref' => $merchantRef, 'status' => $status]);
        }
    }


    // --- PRIVATE HELPER METHODS ---

    private function _validateOrderRequest(Request $request): array
    {
        // ... (fungsi _validateOrderRequest tetap sama) ...
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
             'customer_email' => 'nullable|email',
         ]);
    }

    private function geocode(string $address): ?array
    {
        // ... (fungsi geocode tetap sama) ...
        try { /* ... */ } catch (\Illuminate\Http\Client\ConnectionException $e) { /* ... */ } catch (\Exception $e) { /* ... */ }
    }

    private function _getAddressData(Request $request, string $type): array
    {
        // ... (fungsi _getAddressData tetap sama) ...
         $lat = $request->input("{$type}_lat"); $lng = $request->input("{$type}_lng");
         /* ... logika geocode jika perlu ... */
         return ['lat' => $finalLat, 'lng' => $finalLng, 'kirimaja_data' => $kirimajaAddr];
    }

    private function _saveOrUpdateKontak(array $data, string $prefix, string $tipe)
    {
         // ... (fungsi _saveOrUpdateKontak tetap sama) ...
        if (!empty($data["save_{$prefix}"])) { /* ... */ }
    }

    private function _preparePesananData(array $validatedData, int $total_ongkir, string $ip, string $userAgent): array
    {
        // --- PERBAIKAN: Pastikan $pesananCoreData terdefinisi ---
        do {
            $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6));
        } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

        $fieldsToSave = array_keys($this->_validateOrderRequest(request()));
        $fieldsToExclude = ['save_sender', 'save_receiver', 'expedition', 'customer_email', 'sender_phone_original', 'receiver_phone_original'];
        $fieldsToSave = array_diff($fieldsToSave, $fieldsToExclude);

        // --- BARIS YANG DITAMBAHKAN KEMBALI ---
        $pesananCoreData = collect($validatedData)->only($fieldsToSave)->all();

        return array_merge($pesananCoreData, [
            'nomor_invoice' => $nomorInvoice,
            'status' => 'Menunggu Pembayaran',
            'status_pesanan' => 'Menunggu Pembayaran',
            'tanggal_pesanan' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'kontak_pengirim_id' => $validatedData['pengirim_id'] ?? null,
            'kontak_penerima_id' => $validatedData['penerima_id'] ?? null,
        ]);
    }


    private function _calculateTotalPaid(array $validatedData): array
    {
        // --- PERBAIKAN: Inisialisasi variabel ---
        $cod_fee = 0;
        $ansuransi_fee = 0;
        $shipping_cost = 0;
        $total_paid_ongkir = 0; // Inisialisasi di sini
        $cod_value = 0;

        $parts = explode('-', $validatedData['expedition']);
        $count = count($parts);

        // ... (logika parsing $parts tetap sama) ...
        if ($count >= 6) { $cod_fee = (int) end($parts); $ansuransi_fee = (int) $parts[$count - 2]; $shipping_cost = (int) $parts[$count - 3]; }
        elseif ($count === 5) { $ansuransi_fee = (int) $parts[4]; $shipping_cost = (int) $parts[3]; }
        elseif ($count === 4) { $shipping_cost = (int) $parts[3]; }
        else { Log::warning('Format expedition tidak dikenal', ['exp' => $validatedData['expedition']]); }

        // Hitung ulang $total_paid_ongkir berdasarkan hasil parsing
        $total_paid_ongkir = $shipping_cost;
        if ($validatedData['ansuransi'] == 'iya' && $ansuransi_fee > 0) {
            $total_paid_ongkir += $ansuransi_fee;
        } elseif ($validatedData['ansuransi'] == 'iya' && $ansuransi_fee <= 0) {
            Log::warning('Asuransi dipilih "iya" tapi biaya asuransi 0 dari string expedition', ['expedition' => $validatedData['expedition']]);
        }

        // ... (logika hitung $cod_value tetap sama) ...
        if ($validatedData['payment_method'] === 'CODBARANG') { $cod_value = (int)$validatedData['item_price'] + $total_paid_ongkir + $cod_fee; }
        elseif ($validatedData['payment_method'] === 'COD') { $cod_value = $total_paid_ongkir + $cod_fee; }

        return compact('total_paid_ongkir', 'cod_value', 'shipping_cost', 'ansuransi_fee', 'cod_fee');
    }


    private function _createKiriminAjaOrder(
        array $data, Pesanan $pesanan, KiriminAjaService $kirimaja,
        array $senderData, array $receiverData, int $cod_value,
        int $shipping_cost, int $insurance_cost // Terima biaya terpisah
    ): array
    {
        // ... (fungsi _createKiriminAjaOrder tetap sama) ...
         /* ... validasi data ... */
         if (in_array($serviceGroup, ['instant', 'sameday'])) { /* ... payload instant ... */ }
         else { /* ... payload express/cargo ... */ }
    }

    private function _prepareOrderItemsPayload(int $shipping_cost, int $ansuransi_fee, string $ansuransi_choice): array
    {
        // ... (fungsi _prepareOrderItemsPayload tetap sama) ...
         $payload = [['sku' => 'SHIPPING', /* ... */]];
         if ($ansuransi_choice == 'iya' && $ansuransi_fee > 0) { /* ... */ }
         return $payload;
    }

     // Helper internal untuk memanggil Tripay dari store()
    private function _createTripayTransactionInternal(array $data, Pesanan $pesanan, int $total, array $orderItems): array
    {
        // ... (fungsi _createTripayTransactionInternal - pastikan route benar) ...
        $apiKey = config('tripay.api_key'); $privateKey = config('tripay.private_key'); $merchantCode = config('tripay.merchant_code'); $mode = config('tripay.mode', 'sandbox');
        if ($total <= 0) return ['success' => false, 'message' => 'Jumlah tidak valid.'];

        $customerEmail = $data['customer_email'] ?? null;
        /* ... logika fallback email ... */

        $payload = [
            'method' => $data['payment_method'], 'merchant_ref' => $pesanan->nomor_invoice, 'amount' => $total,
            'customer_name' => $data['receiver_name'], 'customer_email' => $customerEmail, 'customer_phone' => $data['receiver_phone'],
            'order_items' => $orderItems,
             // --- Pastikan route 'admin.pesanan.show' menerima parameter 'resi' ---
            'return_url' => route('admin.pesanan.show', ['resi' => $pesanan->nomor_invoice]), // Arahkan ke detail pesanan admin
            'expired_time' => time() + (1 * 60 * 60), // 1 jam
            'signature' => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey), // Perbaiki hash sha256
        ];
        Log::info('Tripay Create Transaction Payload (Internal):', $payload);
        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->timeout(30)->withoutVerifying()->post($baseUrl, $payload);
            /* ... handle response & error ... */
             if (!$response->successful()) { /* log error */ return ['success' => false, 'message' => 'Gagal hubungi server (HTTP: ' . $response->status() . ').']; }
             $responseData = $response->json();
             if (!isset($responseData['success']) || $responseData['success'] !== true) { /* log error */ return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan.']; }
             return $responseData;
        } catch (\Illuminate\Http\Client\ConnectionException $e) { /* log error */ return ['success' => false, 'message' => 'Tidak dapat terhubung.'];
        } catch (Exception $e) { /* log error */ return ['success' => false, 'message' => 'Kesalahan internal.']; }
    }


    private function _sanitizePhoneNumber(string $phone): string
    {
        // ... (fungsi _sanitizePhoneNumber tetap sama) ...
         $phone = preg_replace('/[^0-9]/', '', $phone); /* ... logika sanitasi ... */ return $phone;
    }

    private function _sendWhatsappNotification(
        Pesanan $pesanan, array $validatedData, int $shipping_cost,
        int $ansuransi_fee, int $cod_fee, int $total_paid,
        Request $request // Tambahkan $request di sini
    ) {
        // ... (fungsi _sendWhatsappNotification - pastikan route tracking.search ada) ...
        $displaySenderPhone = $request->input('sender_phone') ?? $validatedData['sender_phone_original'] ?? $pesanan->sender_phone;
        $displayReceiverPhone = $request->input('receiver_phone') ?? $validatedData['receiver_phone_original'] ?? $pesanan->receiver_phone;

        /* ... siapkan $detailPaket, $rincianBiaya, $statusBayar ... */
         $detailPaket = "*Detail Paket:*\n"; $detailPaket .= "Deskripsi: " . ($pesanan->item_description ?? '-') . "\n"; /* ... */;
         $rincianBiaya = "*Rincian Biaya:*\n- Ongkir: Rp " . number_format($shipping_cost, 0, ',', '.'); /* ... */;
         $statusBayar = "⏳ Menunggu Pembayaran"; /* ... logika status ... */;

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
Status Pembayaran: {STATUS_BAYAR}
----------------------------------------

Semoga Paket Kakak aman dan selamat sampai tujuan. ✅

Cek status pesanan/resi dengan klik link berikut:
*{TRACKING_LINK}*

*Manajemen Sancaka*
TEXT;

        // --- Pastikan route 'tracking.search' ada dan menerima 'resi' ---
        $trackingLink = '-'; // Default jika route tidak ada
        try {
            $trackingLink = route('tracking.search', ['resi' => ($pesanan->resi ?? $pesanan->nomor_invoice)]);
        } catch (\Exception $e) {
            Log::error("Route 'tracking.search' tidak ditemukan saat membuat link WA.", ['error' => $e->getMessage()]);
        }


        $message = str_replace(
            [
                '{NOMOR_INVOICE}', '{SENDER_NAME}', '{SENDER_PHONE}', '{RECEIVER_NAME}', '{RECEIVER_PHONE}',
                '{DETAIL_PAKET}', '{RINCIAN_BIAYA}', '{TOTAL_BAYAR}', '{STATUS_BAYAR}',
                '{TRACKING_LINK}' // Ganti placeholder lama
            ],
            [
                $pesanan->nomor_invoice, $pesanan->sender_name, $displaySenderPhone,
                $pesanan->receiver_name, $displayReceiverPhone, $detailPaket, $rincianBiaya,
                number_format($total_paid, 0, ',', '.'), $statusBayar,
                $trackingLink // Masukkan link tracking yang sudah dibuat
            ],
            $messageTemplate
        );

        /* ... kirim WA via Fonnte ... */
        $senderWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($pesanan->sender_phone));
        $receiverWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($pesanan->receiver_phone));
        try { /* ... FonnteService::sendMessage ... */ } catch (Exception $e) { /* log error */ }
    }


} // Akhir Class

