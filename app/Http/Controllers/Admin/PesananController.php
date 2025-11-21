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
            // Gunakan biaya terpisah untuk notifikasi yang lebih akurat
            $notification_total = $pesanan->price; // Total bayar final
            $this->_sendWhatsappNotification($pesanan, $validatedData, $shipping_cost, $pesanan->insurance_cost, $pesanan->cod_fee, $notification_total, $request); // Kirim $request untuk ambil nomor asli
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
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        return view('admin.pesanan.show', compact('order'));
    }

    public function edit($resi)
    {
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        // Anda mungkin perlu mengambil data customer lagi jika form edit butuh dropdown
        $customers = User::orderBy('nama_lengkap', 'asc')->get();
        return view('admin.pesanan.edit', compact('order', 'customers'));
    }

    public function update(Request $request, $resi)
    {
         // Validasi bisa lebih spesifik jika perlu
        $validatedData = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            // Tambahkan validasi field alamat lain jika bisa diedit
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            // Tambahkan validasi field alamat lain jika bisa diedit
            'item_description' => 'required|string',
            'weight' => 'required|numeric|min:1',
             // Hati-hati jika mengubah payment method atau expedition setelah dibuat
            // 'payment_method' => 'required|string',
            // 'expedition' => 'required|string',
        ]);
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
         // Sanitasi nomor telepon sebelum update
        $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($request->input('sender_phone')); // Ambil dari request
        $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($request->input('receiver_phone')); // Ambil dari request

        $order->update($validatedData);
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . ($order->resi ?? $order->nomor_invoice) . ' berhasil diperbarui.');
    }

    public function destroy($resi)
    {
         // Hati-hati saat menghapus pesanan, mungkin perlu soft delete atau validasi status
        $order = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        $invoice = $order->nomor_invoice;
        // Pertimbangkan logika tambahan sebelum delete (misal: batalkan di KiriminAja jika sudah dibuat)
        $order->delete();
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $invoice . ' berhasil dihapus.');
    }

    public function updateStatus(Request $request, $resi)
    {
        $request->validate(['status' => 'required|string|in:Terkirim,Batal,Diproses,Menunggu Pickup, Kadaluarsa, Gagal Bayar']); // Tambahkan status lain jika perlu
        $pesanan = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();
        // Pertimbangkan validasi alur status (misal: tidak bisa ubah dari Terkirim ke Menunggu Pickup)
        $pesanan->update([
            'status' => $request->status,
            'status_pesanan' => $request->status, // Samakan kedua kolom status
        ]);
        // Mungkin perlu kirim notifikasi update status ke pelanggan?
        return redirect()->back()->with('success', 'Status pesanan ' . ($pesanan->resi ?? $pesanan->nomor_invoice) . ' berhasil diubah menjadi "' . $request->status . '".');
    }

    public function exportExcel()
    {
        return Excel::download(new PesanansExport(Pesanan::all()), 'semua-pesanan-' . date('Ymd') . '.xlsx');
    }

    public function exportPdf()
    {
        $orders = Pesanan::all();
        $pdf = PDF::loadView('admin.pesanan.pdf', ['orders' => $orders])->setPaper('a4', 'landscape'); // Contoh set landscape
        return $pdf->download('semua-pesanan-' . date('Ymd') . '.pdf');
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
                $searchLower = strtolower($request->input('search'));
                $q->where(DB::raw('LOWER(nama)'), 'LIKE', '%' . $searchLower . '%')
                  ->orWhere('no_hp', 'LIKE', "%{$request->input('search')}%"); // no_hp biasanya sudah bersih
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
                'item_price' => 'required|numeric|min:1', // Harga barang bisa 0? Minimal 1?
                'weight' => 'required|numeric|min:1',
                'service_type' => 'required|string|in:regular,express,sameday,instant,cargo',
                'item_type' => 'required|integer|exists:package_types,id',
                'ansuransi' => 'required|string|in:iya,tidak',
                'length' => 'nullable|numeric|min:1',
                'width' => 'nullable|numeric|min:1',
                'height' => 'nullable|numeric|min:1',
                 // Ambil lat/lng dari request jika ada (penting untuk instant)
                'sender_lat' => 'nullable|numeric', 'sender_lng' => 'nullable|numeric',
                'receiver_lat' => 'nullable|numeric', 'receiver_lng' => 'nullable|numeric',
                 // Ambil alamat teks juga untuk instant
                'sender_address' => 'required_if:service_type,instant,sameday|nullable|string',
                'receiver_address' => 'required_if:service_type,instant,sameday|nullable|string',
            ]);

            // Dapatkan Lat/Lng dari request atau coba geocode jika instant/sameday
            $senderLat = $validated['sender_lat'] ?? null;
            $senderLng = $validated['sender_lng'] ?? null;
            $receiverLat = $validated['receiver_lat'] ?? null;
            $receiverLng = $validated['receiver_lng'] ?? null;

            // Asuransi wajib berdasarkan item_type
            $mandatoryTypes = [1, 3, 4, 8]; // Sesuaikan ID package_types jika berbeda
            $isMandatory = in_array((int) $validated['item_type'], $mandatoryTypes);

            if($isMandatory && $validated['ansuransi'] == 'tidak') {
                return response()->json(['status' => false, 'message' => 'Jenis barang ini wajib menggunakan asuransi.'], 422);
            }
            $useInsurance = ($validated['ansuransi'] == 'iya') ? 1 : 0;
            $itemValue = $validated['item_price'];

            $options = [];

            if (in_array($validated['service_type'], ['instant', 'sameday'])) {
                 // Coba geocode jika lat/lng kosong
                if (!$senderLat || !$senderLng) {
                     $geo = $this->geocode($validated['sender_address']);
                     if ($geo) { $senderLat = $geo['lat']; $senderLng = $geo['lng']; }
                }
                if (!$receiverLat || !$receiverLng) {
                     $geo = $this->geocode($validated['receiver_address']);
                     if ($geo) { $receiverLat = $geo['lat']; $receiverLng = $geo['lng']; }
                }

                if (empty($senderLat) || empty($senderLng) || empty($receiverLat) || empty($receiverLng)) {
                    return response()->json(['status' => false, 'message' => 'Koordinat alamat pengirim atau penerima tidak valid/ditemukan untuk ongkir instan/sameday.'], 422);
                }

                $options = $kirimaja->getInstantPricing(
                    $senderLat, $senderLng, $validated['sender_address'],
                    $receiverLat, $receiverLng, $validated['receiver_address'],
                    $validated['weight'], $itemValue, 'motor' // Asumsi motor
                );
            } else { // regular, express, cargo
                $category = $validated['service_type'] === 'cargo' ? 'trucking' : 'regular';
                $length = $request->input('length', 1);
                $width = $request->input('width', 1);
                $height = $request->input('height', 1);

                $options = $kirimaja->getExpressPricing(
                    $validated['sender_district_id'], $validated['sender_subdistrict_id'],
                    $validated['receiver_district_id'], $validated['receiver_subdistrict_id'],
                    $validated['weight'],
                    $length, $width, $height,
                    $itemValue,
                    null, // Biarkan KiriminAja mengembalikan semua kurir
                    $category,
                    $useInsurance
                );
            }

            // Log request dan response untuk debugging
            Log::info('Cek Ongkir Request:', $request->all());
            Log::info('Cek Ongkir KiriminAja Options:', ['options' => $options]);

            // Filter hasil yang tidak valid (harga 0 atau status false di dalam results)
             if (isset($options['status']) && $options['status'] === true && isset($options['results'])) {
                 $options['results'] = array_filter($options['results'], function($opt) {
                     // Pastikan final_price ada, numerik, dan lebih dari 0
                     return isset($opt['final_price']) && is_numeric($opt['final_price']) && $opt['final_price'] > 0;
                 });
                 // Jika setelah filter hasilnya kosong
                 if (empty($options['results'])) {
                     Log::warning('Cek Ongkir: Tidak ada opsi valid ditemukan setelah filter.', ['initial_options' => $options]);
                     // Kembalikan pesan error yang lebih jelas
                     return response()->json(['status' => false, 'message' => 'Tidak ada layanan pengiriman yang tersedia untuk rute atau parameter ini.'], 404);
                 }
             } elseif (!isset($options['status']) || $options['status'] !== true) {
                 // Jika status utama dari API adalah false
                 Log::error('Cek Ongkir: API KiriminAja mengembalikan status false.', ['response' => $options]);
                 $errorMessage = $options['text'] ?? 'Gagal mengambil data ongkir dari ekspedisi.';
                 // Jangan tampilkan pesan error API mentah ke user
                 return response()->json(['status' => false, 'message' => 'Gagal mengambil data ongkir. Silakan coba lagi.'], 500);
             }


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
        // Gunakan KiriminAjaService dari service container
        $kirimaja = app(KiriminAjaService::class);

        // --- PERBAIKAN: Gunakan 'nomor_invoice' bukan 'invoice_number' ---
        $pesanan = Pesanan::where('nomor_invoice', $merchantRef)->lockForUpdate()->first();

        if (!$pesanan) {
            // Log error tapi jangan throw exception agar tidak menyebabkan rollback global
            Log::error('Tripay Callback (Pesanan SCK-): Pesanan Not found in DB.', ['merchant_ref' => $merchantRef]);
            // Kembalikan saja agar proses di CheckoutController bisa commit
            return;
        }

        // Hanya proses jika status masih 'Menunggu Pembayaran'
        if ($pesanan->status !== 'Menunggu Pembayaran') {
            Log::info('Tripay Callback (Pesanan SCK-): Already processed or not pending.', ['invoice' => $merchantRef, 'status' => $pesanan->status]);
            return; // Selesai
        }

        // Jika Pembayaran Lunas
        if ($status === 'PAID') {
            $pesanan->status = 'paid'; // Tandai lunas dulu
            $pesanan->status_pesanan = 'paid';
            $pesanan->payment_status = 'PAID'; // Simpan status dari Tripay
            $pesanan->save(); // Simpan status lunas

            Log::info('Tripay Callback (Pesanan SCK-): Payment PAID. Creating KiriminAja order...', ['invoice' => $merchantRef]);

            // --- Logika Kirim ke KiriminAja SETELAH LUNAS ---
            try {
                // Buat instance baru untuk akses method private (lebih aman dari static context)
                $instance = new self();
                $validatedData = $pesanan->toArray(); // Ambil data lengkap dari model $pesanan

                // Dapatkan data alamat yang diperlukan (bisa dari $pesanan)
                $senderAddressData = [
                    'lat' => $pesanan->sender_lat, 'lng' => $pesanan->sender_lng,
                    'kirimaja_data' => ['district_id' => $pesanan->sender_district_id, 'subdistrict_id' => $pesanan->sender_subdistrict_id, 'postal_code' => $pesanan->sender_postal_code]
                ];
                $receiverAddressData = [
                    'lat' => $pesanan->receiver_lat, 'lng' => $pesanan->receiver_lng,
                    'kirimaja_data' => ['district_id' => $pesanan->receiver_district_id, 'subdistrict_id' => $pesanan->receiver_subdistrict_id, 'postal_code' => $pesanan->receiver_postal_code]
                ];

                // Ambil biaya yang sudah tersimpan di pesanan
                $cod_value = 0; // Pasti 0 karena ini callback Tripay
                $shipping_cost = (int) $pesanan->shipping_cost;
                $insurance_cost = (int) $pesanan->insurance_cost; // Biaya asuransi yg disimpan

                // Panggil method private _createKiriminAjaOrder melalui instance
                $kiriminResponse = $instance->_createKiriminAjaOrder(
                    $validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData,
                    $cod_value, $shipping_cost, $insurance_cost // Gunakan biaya tersimpan
                );


                if (($kiriminResponse['status'] ?? false) !== true) {
                    Log::critical('Tripay Callback (Pesanan SCK-): KiriminAja Order FAILED!', ['invoice' => $merchantRef, 'response' => $kiriminResponse]);
                    $pesanan->status = 'Pembayaran Lunas (Gagal Auto-Resi)';
                    $pesanan->status_pesanan = 'Pembayaran Lunas (Gagal Auto-Resi)';
                    // TODO: Notifikasi Admin
                } else {
                    $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
                    $pesanan->status = 'Menunggu Pickup';
                    $pesanan->status_pesanan = 'Menunggu Pickup';
                    Log::info('Tripay Callback (Pesanan SCK-): KiriminAja Order SUCCESS.', ['invoice' => $merchantRef, 'resi' => $pesanan->resi]);
                    // TODO: Kirim Notifikasi WA ke customer (jika perlu)
                    // Perlu instance untuk panggil _sendWhatsappNotification
                    // $instance->_sendWhatsappNotification($pesanan, $validatedData, $shipping_cost, $insurance_cost, 0, $pesanan->price, new Request()); // Buat Request kosong?
                }
                $pesanan->save(); // Simpan resi dan status akhir

            } catch (Exception $e) {
                 Log::error("Tripay Callback (Pesanan SCK-): Exception during KiriminAja process for PAID order.", [ 'ref' => $merchantRef, 'error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile() ]);
                 // Status tetap 'paid', tapi beri status error agar mudah dilacak
                 $pesanan->status = 'Pembayaran Lunas (Error Kirim API)';
                 $pesanan->status_pesanan = 'Pembayaran Lunas (Error Kirim API)';
                 $pesanan->save();
                 // TODO: Notifikasi Admin
            }
             // --- Akhir Logika Kirim ke KiriminAja ---

        // Jika Pembayaran Gagal/Kadaluarsa
        } elseif (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
            Log::info('Tripay Callback (Pesanan SCK-): Payment FAILED/EXPIRED.', ['invoice' => $merchantRef, 'status' => $status]);
            $pesanan->status = ($status === 'EXPIRED') ? 'Kadaluarsa' : 'Gagal Bayar';
            $pesanan->status_pesanan = ($status === 'EXPIRED') ? 'Kadaluarsa' : 'Gagal Bayar';
            $pesanan->payment_status = $status;
            $pesanan->save();
        } else {
             Log::warning('Tripay Callback (Pesanan SCK-): Received unknown status.', ['ref' => $merchantRef, 'status' => $status]);
        }
    }


    // --- PRIVATE HELPER METHODS ---

    private function _validateOrderRequest(Request $request): array
    {
        // Pastikan validasi tetap sama
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
            'customer_email' => 'nullable|email', // Tambahkan jika perlu kirim email customer ke Tripay
        ]);
    }

    private function geocode(string $address): ?array
    {
        // ... (fungsi geocode tetap sama) ...
        try {
            $response = Http::timeout(10)->withHeaders(['User-Agent' => 'SancakaCargo/1.0 (admin@tokosancaka.com)'])
                ->get("https://nominatim.openstreetmap.org/search", ['q' => $address, 'format' => 'json', 'limit' => 1, 'countrycodes' => 'id']);
            // ... (handle response & error) ...
             if (!$response->successful() || empty($response[0]) || !isset($response[0]['lat']) || !isset($response[0]['lon'])) {
                  Log::warning("Geocoding failed for address: " . $address, ['response_status' => $response->status(), 'response_body' => $response->body()]);
                  return null;
             }
            return ['lat' => (float) $response[0]['lat'], 'lng' => (float) $response[0]['lon']];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Geocoding connection failed: " . $e->getMessage(), ['address' => $address]); return null;
        } catch (\Exception $e) {
            Log::error("Geocoding general error: " . $e->getMessage(), ['address' => $address]); return null;
        }
    }

    private function _getAddressData(Request $request, string $type): array
    {
        // ... (fungsi _getAddressData tetap sama) ...
        $lat = $request->input("{$type}_lat"); $lng = $request->input("{$type}_lng");
        $kirimajaAddr = ['district_id' => $request->input("{$type}_district_id"),'subdistrict_id' => $request->input("{$type}_subdistrict_id"),'postal_code' => $request->input("{$type}_postal_code"),];
        if (!is_numeric($lat) || !is_numeric($lng) || $lat == 0 || $lng == 0) {
            $fullAddressQuery = $request->input("{$type}_address") . ', ' . $request->input("{$type}_village") . ', ' . $request->input("{$type}_district") . ', ' . $request->input("{$type}_regency") . ', ' . $request->input("{$type}_province") . ', Indonesia';
            $geo = $this->geocode($fullAddressQuery);
            if ($geo) { $lat = $geo['lat']; $lng = $geo['lng']; }
            else { /* fallback ke parts jika perlu */ }
        }
        $finalLat = (is_numeric($lat) && $lat != 0) ? (float)$lat : null;
        $finalLng = (is_numeric($lng) && $lng != 0) ? (float)$lng : null;
        return ['lat' => $finalLat, 'lng' => $finalLng, 'kirimaja_data' => $kirimajaAddr];
    }

    private function _saveOrUpdateKontak(array $data, string $prefix, string $tipe)
    {
        // ... (fungsi _saveOrUpdateKontak tetap sama) ...
        if (!empty($data["save_{$prefix}"])) {
            $sanitizedPhone = $this->_sanitizePhoneNumber($data["{$prefix}_phone"]); if (empty($sanitizedPhone)) return;
            Kontak::updateOrCreate(['no_hp' => $sanitizedPhone],[ /* ... data ... */ ]);
        }
    }

    private function _preparePesananData(array $validatedData, int $total_ongkir, string $ip, string $userAgent): array
    {
        // ... (fungsi _preparePesananData - pastikan prefix SCK-) ...
        do { $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6)); } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());
         // Ambil semua field yang relevan dari validasi
        $fieldsToSave = array_keys($this->_validateOrderRequest(request())); // Ambil keys dari validasi
        // Hapus field yang tidak ada di tabel pesanan atau tidak ingin disimpan langsung
        $fieldsToExclude = ['save_sender', 'save_receiver', 'expedition', 'customer_email'];
        $fieldsToSave = array_diff($fieldsToSave, $fieldsToExclude);

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
             // Tambahkan kolom payment_status jika belum ada di $fillable Pesanan
             // 'payment_status' => null,
        ]);
    }

    private function _calculateTotalPaid(array $validatedData): array
    {
        // ... (fungsi _calculateTotalPaid - parsing expedition string) ...
        $parts = explode('-', $validatedData['expedition']); $count = count($parts);
        $cod_fee = 0; $ansuransi_fee = 0; $shipping_cost = 0;
        if ($count >= 6) { $cod_fee = (int) end($parts); $ansuransi_fee = (int) $parts[$count - 2]; $shipping_cost = (int) $parts[$count - 3]; }
        elseif ($count === 5) { $ansuransi_fee = (int) $parts[4]; $shipping_cost = (int) $parts[3]; }
        elseif ($count === 4) { $shipping_cost = (int) $parts[3]; }
        else { Log::warning('Format expedition tidak dikenal', ['exp' => $validatedData['expedition']]); }
        $total_paid_ongkir = $shipping_cost;
        if ($validatedData['ansuransi'] == 'iya' && $ansuransi_fee > 0) { $total_paid_ongkir += $ansuransi_fee; }
        $cod_value = 0;
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
        // ... (fungsi _createKiriminAjaOrder - gunakan $shipping_cost & $insurance_cost dari parameter) ...
        $expeditionParts = explode('-', $data['expedition']);
        $serviceGroup = $expeditionParts[0] ?? null; $courier = $expeditionParts[1] ?? null; $service_type = $expeditionParts[2] ?? null;

        if (in_array($serviceGroup, ['instant', 'sameday'])) {
            if (empty($senderData['lat']) || empty($senderData['lng']) || empty($receiverData['lat']) || empty($receiverData['lng'])) {
                 return ['status' => false, 'text' => 'Koordinat alamat tidak valid untuk pengiriman instan/sameday.'];
            }
             $payload = [
                 'service' => $courier, 'service_type' => $service_type, 'vehicle' => 'motor',
                 'order_prefix' => $pesanan->nomor_invoice,
                 'packages' => [[
                     'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'],
                     'destination_lat' => $receiverData['lat'], 'destination_long' => $receiverData['lng'],
                     'destination_address' => $data['receiver_address'],'destination_address_note' => $data['receiver_note'] ?? '-',
                     'origin_name' => $data['sender_name'], 'origin_phone' => $data['sender_phone'],
                     'origin_lat' => $senderData['lat'], 'origin_long' => $senderData['lng'],
                     'origin_address' => $data['sender_address'], 'origin_address_note' => $data['sender_note'] ?? '-',
                     'shipping_price' => (int)$shipping_cost,
                     'item' => [
                         'name' => $data['item_description'], 'description' => 'Pesanan ' . $pesanan->nomor_invoice,
                         'price' => (int)$data['item_price'], 'weight' => (int)$data['weight'],
                     ]
                 ]]
             ];
             Log::info('KiriminAja Create Instant Order Payload:', $payload);
            return $kirimaja->createInstantOrder($payload);

        } else { // Express, Regular, Cargo
            $scheduleResponse = $kirimaja->getSchedules(); $scheduleClock = $scheduleResponse['clock'] ?? null;
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

             // Tentukan insuranceAmount berdasarkan $insurance_cost (dari parameter) > 0
             // Nilai yang diasuransikan adalah harga barang
            $insuranceAmount = ($insurance_cost > 0) ? (int)$data['item_price'] : 0;

             $payload = [
                 'address' => $data['sender_address'], 'phone' => $data['sender_phone'], 'name' => $data['sender_name'],
                 'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
                 'zipcode' => $senderData['kirimaja_data']['postal_code'], 'schedule' => $scheduleClock,
                 'platform_name' => 'tokosancaka.com', 'category' => $category,
                 'latitude' => $senderData['lat'], 'longitude' => $senderData['lng'],
                 'packages' => [[
                     'order_id' => $pesanan->nomor_invoice, 'item_name' => $data['item_description'],
                     'package_type_id' => (int)$data['item_type'],
                     'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'],
                     'destination_address' => $data['receiver_address'],
                     'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                     'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                     'weight' => ceil($finalWeight), // Bulatkan ke atas
                     'width' => $widthInput, 'height' => $heightInput, 'length' => $lengthInput,
                     'item_value' => (int)$data['item_price'], // Harga barang
                     'service' => $courier, 'service_type' => $service_type,
                     'insurance_amount' => $insuranceAmount, // Jumlah nilai yg diasuransikan
                     'cod' => $cod_value, // Gunakan $cod_value dari parameter
                     'shipping_cost' => (int)$shipping_cost // Gunakan $shipping_cost dari parameter
                 ]]
             ];
              Log::info('KiriminAja Create Express/Cargo Order Payload:', $payload);
            return $kirimaja->createExpressOrder($payload);
        }
    }

    private function _prepareOrderItemsPayload(int $shipping_cost, int $ansuransi_fee, string $ansuransi_choice): array
    {
        // ... (fungsi _prepareOrderItemsPayload tetap sama) ...
         $payload = [['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1]];
         if ($ansuransi_choice == 'iya' && $ansuransi_fee > 0) {
             $payload[] = ['sku' => 'INSURANCE', 'name' => 'Biaya Asuransi', 'price' => $ansuransi_fee, 'quantity' => 1];
         }
         return $payload;
    }

     // Helper internal untuk memanggil Tripay dari store()
    private function _createTripayTransactionInternal(array $data, Pesanan $pesanan, int $total, array $orderItems): array
    {
        $apiKey = config('tripay.api_key'); $privateKey = config('tripay.private_key'); $merchantCode = config('tripay.merchant_code'); $mode = config('tripay.mode', 'sandbox');
        if ($total <= 0) return ['success' => false, 'message' => 'Jumlah tidak valid.'];

        // Ambil email customer jika ada, jika tidak, fallback
        $customerEmail = $data['customer_email'] ?? null;
        if (empty($customerEmail) && !empty($data['customer_id'])) {
             $customer = User::find($data['customer_id']);
             if ($customer && $customer->email) {
                 $customerEmail = $customer->email;
             }
        }
        // Fallback jika masih kosong atau tidak valid
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $customerEmail = 'customer+' . Str::random(5) . '@tokosancaka.com';
        }

        $payload = [
            'method' => $data['payment_method'], 'merchant_ref' => $pesanan->nomor_invoice, 'amount' => $total,
            'customer_name' => $data['receiver_name'], // Gunakan nama penerima
            'customer_email' => $customerEmail,
            'customer_phone' => $data['receiver_phone'], // Gunakan telp penerima
            'order_items' => $orderItems,
            'return_url' => route('tracking.show', ['resi' => $pesanan->nomor_invoice]), // Arahkan ke tracking
            'expired_time' => time() + (1 * 60 * 60), // 1 jam
            'signature' => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];
        Log::info('Tripay Create Transaction Payload (Internal):', $payload);
        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                          ->timeout(30)
                          ->withoutVerifying() // Tetap pakai jika perlu
                          ->post($baseUrl, $payload);

             if (!$response->successful()) {
                  Log::error('Gagal menghubungi Tripay (HTTP Error)', ['status' => $response->status(), 'body' => $response->body()]);
                  return ['success' => false, 'message' => 'Gagal menghubungi server pembayaran (HTTP: ' . $response->status() . ').'];
             }
             $responseData = $response->json();
             Log::info('Tripay Create Transaction Response (Internal):', $responseData);

             if (!isset($responseData['success']) || $responseData['success'] !== true) {
                  Log::error('Tripay mengembalikan respon gagal', ['response' => $responseData]);
                  return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan pembayaran.'];
             }
             return $responseData; // Kembalikan response JSON sukses
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             Log::error('Koneksi ke Tripay gagal', ['error' => $e->getMessage()]);
             return ['success' => false, 'message' => 'Tidak dapat terhubung ke server pembayaran.'];
        } catch (Exception $e) {
             Log::error('Error saat membuat transaksi Tripay', ['error' => $e->getMessage()]);
             return ['success' => false, 'message' => 'Terjadi kesalahan internal saat proses pembayaran.'];
        }
    }


    private function _sanitizePhoneNumber(string $phone): string
    {
        // ... (fungsi _sanitizePhoneNumber tetap sama) ...
         $phone = preg_replace('/[^0-9]/', '', $phone);
         // Handle +62
         if (Str::startsWith($phone, '62')) {
            // Jika setelah 62 adalah 0 (misal 6208...), hapus 0 nya -> 08...
            if (Str::startsWith(substr($phone, 2), '0')) {
                 return '0' . substr($phone, 3);
            }
            // Jika setelah 62 langsung angka (misal 628...), ubah jadi 08...
            return '0' . substr($phone, 2);
         }
         // Handle jika tidak diawali 0 tapi diawali 8 (misal 812...)
         if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) {
             return '0' . $phone;
         }
         // Kembalikan nomor jika sudah diawali 0 atau biarkan apa adanya
         return $phone;
    }

    private function _sendWhatsappNotification(
    Pesanan $pesanan, array $validatedData, int $shipping_cost,
    int $ansuransi_fee, int $cod_fee, int $total_paid,
    Request $request
) {
    // Ambil nomor telepon yang paling akurat
    $displaySenderPhone = $request->input('sender_phone') 
        ?? $validatedData['sender_phone_original'] 
        ?? $pesanan->sender_phone;
    $displayReceiverPhone = $request->input('receiver_phone') 
        ?? $validatedData['receiver_phone_original'] 
        ?? $pesanan->receiver_phone;

    // ========================
    // DETAIL PAKET
    // ========================
    $detailPaket = "*Detail Paket:*\n";
    $detailPaket .= "Deskripsi: " . ($pesanan->item_description ?? '-') . "\n";
    $detailPaket .= "Berat: " . ($pesanan->weight ?? 0) . " Gram\n";
    if ($pesanan->length && $pesanan->width && $pesanan->height) {
        $detailPaket .= "Dimensi: {$pesanan->length}x{$pesanan->width}x{$pesanan->height} cm\n";
    }

    // Ekspedisi dan layanan
    $expeditionParts = explode('-', $pesanan->expedition ?? '');
    $exp_vendor = $expeditionParts[1] ?? '';
    $exp_service_type = $expeditionParts[2] ?? '';
    $service_display = trim(ucwords(strtolower(str_replace('_', ' ', $exp_vendor))) . ' ' . ucwords(strtolower(str_replace('_', ' ', $exp_service_type))));
    $detailPaket .= "Ekspedisi: " . ($service_display ?: '-') . "\n";
    $detailPaket .= "Layanan: " . ucwords($pesanan->service_type ?? '-');
    $detailPaket .= "\nResi: *" . ($pesanan->resi ?? '-') . "*";

    // ========================
    // RINCIAN BIAYA DINAMIS
    // ========================
    $rincianBiaya = "*Rincian Biaya:*\n";

    if ($shipping_cost > 0) $rincianBiaya .= "- Ongkir: Rp " . number_format($shipping_cost, 0, ',', '.') . "\n";

    $itemPrice = $validatedData['item_price'] ?? $pesanan->item_price ?? 0;
    if ($itemPrice > 0) $rincianBiaya .= "- Nilai Barang: Rp " . number_format($itemPrice, 0, ',', '.') . "\n";

    if ($ansuransi_fee > 0) $rincianBiaya .= "- Asuransi: Rp " . number_format($ansuransi_fee, 0, ',', '.') . "\n";
    if ($cod_fee > 0) $rincianBiaya .= "- Biaya COD: Rp " . number_format($cod_fee, 0, ',', '.') . "\n";

    // ========================
    // LOGIKA STATUS BAYAR & TEKS
    // ========================
    $statusBayar = "⏳ Menunggu Pembayaran";
    $pembayaranLabel = "";

    // 1️⃣ QRIS / Tripay / non-COD
    if ($pesanan->payment_status === 'PAID' || 
        in_array($pesanan->status, ['Menunggu Pickup', 'Diproses', 'Terkirim', 'Pembayaran Lunas (Gagal Auto-Resi)', 'Pembayaran Lunas (Error Kirim API)'])) {
        $statusBayar = "✅ Lunas";
        $pembayaranLabel = "Metode Pembayaran: *{$pesanan->payment_method}*";

    // 2️⃣ COD / CODBARANG
    } elseif (in_array($pesanan->payment_method, ['COD', 'CODBARANG'])) {
        $statusBayar = "⏳ Bayar di Tempat (COD)";
        $pembayaranLabel = "Metode Pembayaran: *Bayar di Tempat*";

    // 3️⃣ Potong Saldo
    } elseif ($pesanan->payment_method === 'Potong Saldo') {
        $statusBayar = "✅ Lunas via Saldo";
        $pembayaranLabel = "Metode Pembayaran: *Potong Saldo*";

    // 4️⃣ Gagal / Kadaluarsa
    } elseif (in_array($pesanan->status, ['Gagal Bayar', 'Kadaluarsa'])) {
        $statusBayar = "❌ Pembayaran Gagal/Kadaluarsa";
        $pembayaranLabel = "Metode Pembayaran: *{$pesanan->payment_method ?? '-'}*";
    }

    // ========================
    // PESAN UTAMA (DINAMIS)
    // ========================
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
{PEMBAYARAN_LABEL}
Status Pembayaran: {STATUS_BAYAR}
----------------------------------------

Semoga Paket Kakak aman dan selamat sampai tujuan. ✅

Cek status pesanan/resi dengan klik link berikut:
https://tokosancaka.com/tracking/search?resi={LINK_RESI}

*Manajemen Sancaka*
TEXT;

    $linkResi = $pesanan->resi ?? $pesanan->nomor_invoice;
    $message = str_replace(
        [
            '{NOMOR_INVOICE}',
            '{SENDER_NAME}', '{SENDER_PHONE}',
            '{RECEIVER_NAME}', '{RECEIVER_PHONE}',
            '{DETAIL_PAKET}',
            '{RINCIAN_BIAYA}',
            '{TOTAL_BAYAR}',
            '{STATUS_BAYAR}',
            '{LINK_RESI}',
            '{PEMBAYARAN_LABEL}'
        ],
        [
            $pesanan->nomor_invoice,
            $pesanan->sender_name, $displaySenderPhone,
            $pesanan->receiver_name, $displayReceiverPhone,
            $detailPaket,
            trim($rincianBiaya),
            number_format($total_paid, 0, ',', '.'),
            $statusBayar,
            $linkResi,
            $pembayaranLabel
        ],
        $messageTemplate
    );

    // ========================
    // KIRIM WHATSAPP (FONNTE)
    // ========================
    $senderWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($pesanan->sender_phone));
    $receiverWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($pesanan->receiver_phone));

    try {
        Log::info("Mengirim WA Notif ke Pengirim ($senderWa) untuk {$pesanan->nomor_invoice}");
        if ($senderWa) FonnteService::sendMessage($senderWa, $message);

        Log::info("Mengirim WA Notif ke Penerima ($receiverWa) untuk {$pesanan->nomor_invoice}");
        if ($receiverWa) FonnteService::sendMessage($receiverWa, $message);

        Log::info("Notifikasi WA Terkirim (atau attempt) untuk Invoice: " . $pesanan->nomor_invoice);
    } catch (Exception $e) {
        Log::error('Fonnte Service sendMessage failed: ' . $e->getMessage(), ['invoice' => $pesanan->nomor_invoice]);
    }
}


} // Akhir Class

