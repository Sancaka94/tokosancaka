<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Kontak;
use App\Models\User;
use App\Models\Keuangan; // <--- WAJIB ADA
use App\Models\TopUp; // Tetap import jika diperlukan di fungsi lain
use App\Models\Api; // <--- WAJIB TAMBAHKAN INI AGAR TRIPAY JALAN
use App\Services\FonnteService;
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService; // <-- PANGGIL SERVICE BARU
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
    public function index(Request $request)
    {
        // 1. Update Status Notifikasi (Tandai sudah dilihat)
        Pesanan::where('status', 'baru')
            ->where('telah_dilihat', false)
            ->update(['telah_dilihat' => true]);

        // =================================================================
        // STEP 1: QUERY GLOBAL (Berlaku untuk Tabel & Card)
        // =================================================================
        // Query ini akan menampung Search (JNE/Lion/Nama) dan Filter Tanggal

        $query = Pesanan::query();

        // A. LOGIC SEARCH (Nama, Resi, NoHP, DAN EKSPEDISI)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")
                    ->orWhere('nomor_invoice', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('receiver_name', 'like', "%{$search}%")
                    ->orWhere('sender_phone', 'like', "%{$search}%")
                    ->orWhere('receiver_phone', 'like', "%{$search}%")
                    // PENTING: Ini yang bikin card berubah saat ketik "JNE", "Lion", "JNT"
                    ->orWhere('expedition', 'like', "%{$search}%")
                    ->orWhere('status_pesanan', 'like', "%{$search}%");
            });
        }

        // B. LOGIC FILTER TOMBOL DASHBOARD (?ekspedisi=JNE)
        if ($request->has('ekspedisi') && $request->ekspedisi != '') {
            $filterKurir = $request->ekspedisi;
            $query->where(function($q) use ($filterKurir) {
                $q->where('expedition', 'LIKE', '%-' . $filterKurir . '-%')
                  ->orWhere('expedition', 'LIKE', $filterKurir . '-%');
            });
        }

        // C. LOGIC FILTER TANGGAL (Kebal Format URL)
        if ($request->filled('date_range')) {
            $rawDate = $request->date_range;
            // Normalisasi: Ubah " - " atau " s.d. " jadi " to "
            $normalizedDate = str_replace([' - ', ' s.d. '], ' to ', $rawDate);
            $dates = explode(' to ', $normalizedDate);

            if (count($dates) >= 2) {
                // Range Tanggal
                $startDate = trim($dates[0]) . ' 00:00:00';
                $endDate   = trim($dates[1]) . ' 23:59:59';
                $query->whereBetween('tanggal_pesanan', [$startDate, $endDate]);
            } elseif (count($dates) == 1) {
                // Satu Tanggal
                $query->whereDate('tanggal_pesanan', trim($dates[0]));
            }
        }

        // =================================================================
        // STEP 2: CLONE QUERY UNTUK CARD
        // =================================================================
        // Kita "foto" query di titik ini. Saat ini query sudah berisi:
        // "Cari JNE" + "Tanggal Sekian".
        // Kita pakai $cardQuery ini untuk menghitung total di kotak warna-warni.

        $cardQuery = clone $query;

        // =================================================================
        // STEP 3: HITUNG DATA CARD (PENDAPATAN & JUMLAH)
        // =================================================================
        // Menggunakan $cardQuery, jadi angkanya ikut berubah sesuai Search/Tanggal.

        $statusPickup  = ['Menunggu Pickup', 'Pembayaran Lunas (Gagal Auto-Resi)', 'Pembayaran Lunas (Error Kirim API)'];
        $statusDikirim = ['Diproses', 'Terkirim', 'Sedang Dikirim'];
        $statusGagal   = ['Batal', 'Kadaluarsa', 'Gagal Bayar', 'Dibatalkan'];

        // --- A. CARD PENDAPATAN (Rp) ---
        // (clone $cardQuery) -> Ambil query JNE tadi -> Tambahkan syarat status -> Hitung Total
        $incomeSelesai = (clone $cardQuery)->where('status_pesanan', 'Selesai')->sum('price');
        $incomePickup  = (clone $cardQuery)->whereIn('status_pesanan', $statusPickup)->sum('price');
        $incomeDikirim = (clone $cardQuery)->whereIn('status_pesanan', $statusDikirim)->sum('price');
        $incomeGagal   = (clone $cardQuery)->whereIn('status_pesanan', $statusGagal)->sum('price');

        // --- B. CARD JUMLAH (Qty) ---
        $countSelesai = (clone $cardQuery)->where('status_pesanan', 'Selesai')->count();
        $countPickup  = (clone $cardQuery)->whereIn('status_pesanan', $statusPickup)->count();
        $countDikirim = (clone $cardQuery)->whereIn('status_pesanan', $statusDikirim)->count();
        $countGagal   = (clone $cardQuery)->whereIn('status_pesanan', $statusGagal)->count();


        // =================================================================
        // STEP 4: LANJUTKAN QUERY UNTUK TABEL BAWAH
        // =================================================================
        // Filter "Status" (Tab Menu: Semua, Menunggu Pickup, dll) hanya berlaku untuk Tabel,
        // TIDAK BOLEH mengubah Card (supaya Card tetap jadi Summary utuh).

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Ambil Data Tabel (Pagination)
        $orders = $query->orderBy('tanggal_pesanan', 'desc')->paginate(15);
        $orders->appends($request->all()); // Agar filter tidak hilang saat pindah halaman

        // =================================================================
        // STEP 5: RETURN VIEW
        // =================================================================
        return view('admin.pesanan.index', compact(
            'orders',
            'incomeSelesai', 'incomePickup', 'incomeDikirim', 'incomeGagal',
            'countSelesai', 'countPickup', 'countDikirim', 'countGagal'
        ));
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
     * =========================================================================
     * FUNGSI STORE (TELAH DIMODIFIKASI UNTUK DOKU JOKUL)
     * =========================================================================
     */
    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        try {

            // 🔥 0. CEK IDEMPOTENCY KEY (PENGAMAN ANTI-DOBEL)
            // Ini mencegah admin menekan tombol submit 2x saat koneksi lambat
            $key = $request->input('idempotency_key');
            if ($key && Pesanan::where('idempotency_key', $key)->exists()) {
                // Jika kunci sudah ada, tolak request ini dan kembalikan ke index
                return redirect()->route('admin.pesanan.index')
                    ->with('warning', 'Pesanan ini sudah berhasil dibuat sebelumnya (Mencegah Dobel Input).');
            }

            DB::beginTransaction();
            // 1. Validasi
            $validatedData = $this->_validateOrderRequest($request);

            // 2. Simpan Kontak
            $this->_saveOrUpdateKontak($validatedData, 'sender', 'Pengirim');
            $this->_saveOrUpdateKontak($validatedData, 'receiver', 'Penerima');

            $validatedData['sender_phone_original'] = $request->input('sender_phone');
            $validatedData['receiver_phone_original'] = $request->input('receiver_phone');
            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone_original']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone_original']);


            // 3. Kalkulasi Biaya
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid_ongkir = $calculation['total_paid_ongkir'];
            $cod_value = $calculation['cod_value'];
            $shipping_cost = $calculation['shipping_cost'];
            $insurance_cost = $calculation['ansuransi_fee'];
            $cod_fee = $calculation['cod_fee'];


            // 4. Siapkan Data Pesanan
            $pesananData = $this->_preparePesananData($validatedData, $total_paid_ongkir, $request->ip(), $request->userAgent());
            $pesananData['shipping_cost'] = $shipping_cost;
            $pesananData['insurance_cost'] = ($validatedData['ansuransi'] == 'iya') ? $insurance_cost : 0;
            $pesananData['cod_fee'] = ($cod_value > 0) ? $cod_fee : 0;

            // 🔥 Masukkan Idempotency Key ke database agar tidak bisa dipakai lagi
            $pesananData['idempotency_key'] = $key;

            $pesanan = Pesanan::create($pesananData);

            $paymentUrl = null; // Inisialisasi URL Pembayaran

            // 5. Proses logika pembayaran spesifik
            // ======================================================
            // === MODIFIKASI DIMULAI: TAMBAHKAN LOGIKA DOKU JOKUL ===
            // ======================================================
            if ($validatedData['payment_method'] === 'Potong Saldo') {
                $customer = User::find($validatedData['customer_id']);

                if (!$customer) {
                    throw new Exception('Pelanggan untuk potong saldo tidak ditemukan.');
                }
                if ($customer->saldo < $total_paid_ongkir) {
                    throw new Exception('Saldo pelanggan tidak mencukupi.');
                }

                $customer->decrement('saldo', $total_paid_ongkir);
                $pesanan->customer_id = $customer->id_pengguna;
            }
            elseif (in_array($validatedData['payment_method'], ['COD', 'CODBARANG'])) {
                 // Ini adalah logika COD, biarkan kosong
                 // Akan ditangani oleh Langkah 6
            }
            else {
                // --- INI ADALAH BLOK PEMBAYARAN ONLINE (TRIPAY atau DOKU) ---

                $paymentGateway = 'tripay'; // Default

                // Tentukan gateway. Kita asumsikan DOKU akan mengirim 'DOKU_JOKUL'
                if (strtoupper($validatedData['payment_method']) === 'DOKU_JOKUL') {
                    $paymentGateway = 'doku';
                }

                if ($paymentGateway === 'doku') {
                    // --- PROSES VIA DOKU JOKUL ---
                    Log::info('Memulai proses DOKU (Jokul) dari Admin Panel untuk ' . $pesanan->nomor_invoice);

                    // Panggil DokuJokulService
                    $dokuService = new DokuJokulService();

                    $orderData = (object) [
                        'invoice_number' => $pesanan->nomor_invoice,
                        'amount' => $total_paid_ongkir // Total yang ditagih
                    ];

                    // Ambil email customer (logika dari _createTripayTransactionInternal)
                    $customerEmail = $validatedData['customer_email'] ?? null;
                    if (empty($customerEmail) && !empty($validatedData['customer_id'])) {
                         $customer = User::find($validatedData['customer_id']);
                         if ($customer && $customer->email) {
                             $customerEmail = $customer->email;
                         }
                    }
                    if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                         $customerEmail = 'customer+' . Str::random(5) . '@tokosancaka.com';
                    }

                    $customerData = (object) [
                        'name'  => $validatedData['receiver_name'], // Ambil dari data penerima
                        'email' => $customerEmail,
                        'phone' => $validatedData['receiver_phone'] // Ambil dari data penerima
                    ];

                    // Panggil service DOKU
                    $paymentUrl = $dokuService->createPayment($orderData->invoice_number, $orderData->amount);

                    if (empty($paymentUrl)) {
                        throw new Exception('Gagal membuat transaksi pembayaran DOKU.');
                    }

                    $pesanan->payment_url = $paymentUrl;

                } else {
                    // --- PROSES VIA TRIPAY (Logika Asli Anda) ---
                    Log::info('Memulai proses TRIPAY dari Admin Panel untuk ' . $pesanan->nomor_invoice);
                    $orderItemsPayload = $this->_prepareOrderItemsPayload($shipping_cost, $insurance_cost, $validatedData['ansuransi']);
                    $tripayResponse = $this->_createTripayTransactionInternal($validatedData, $pesanan, $total_paid_ongkir, $orderItemsPayload);

                    // ======================================================
                    // 🔥 MODIFIKASI: TANGKAP ERROR DAN KIRIM KE UI 🔥
                    // ======================================================
                    if (empty($tripayResponse['success'])) {
                        DB::rollBack(); // Batalkan database transaction

                        $pesanErrorTripay = $tripayResponse['message'] ?? 'Unknown Error from Tripay';

                        // Kembalikan ke form dengan Variable Spesifik untuk Modal
                        return redirect()->back()
                            ->withInput()
                            ->with('tripay_error_modal', $pesanErrorTripay);
                    }
                    // ======================================================
                    $paymentUrl = $tripayResponse['data']['checkout_url'] ?? null;
                    $pesanan->payment_url = $paymentUrl;
                }
            }
            // ======================================================
            // === MODIFIKASI SELESAI ===
            // ======================================================


            // 6. Proses KiriminAja HANYA jika COD/Saldo
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'Potong Saldo'])) {

                // --- MULAI BLOK YANG HILANG ---

            // Dapatkan data alamat lengkap (termasuk Lat/Lng) dari helper Anda
            $senderAddressData = $this->_getAddressData($request, 'sender');
            $receiverAddressData = $this->_getAddressData($request, 'receiver');

            // Panggil KiriminAja
            Log::info('Memulai create KiriminAja (Admin COD/Saldo) untuk ' . $pesanan->nomor_invoice);
            $kiriminResponse = $this->_createKiriminAjaOrder(
                $validatedData, $pesanan, $kirimaja, // Objek & data utama
                $senderAddressData, $receiverAddressData, // Data alamat
                $cod_value, $shipping_cost, $insurance_cost // Biaya dari _calculateTotalPaid
            );

            // Proses responsnya
            if (($kiriminResponse['status'] ?? false) !== true) {
                // Jika Gagal, update status pesanan agar mudah dilacak admin
                $pesanan->status = 'Gagal Kirim Resi';
                $pesanan->status_pesanan = 'Gagal Kirim Resi';
                Log::error('KiriminAja Order FAILED (Admin COD/Saldo): ' . ($kiriminResponse['text'] ?? 'Unknown error'), [
                    'invoice' => $pesanan->nomor_invoice,
                    'response' => $kiriminResponse
                ]);

                // Opsional: Anda bisa melempar exception agar transaksi di-rollback
                // throw new Exception('Gagal membuat pesanan di KiriminAja: ' . ($kiriminResponse['text'] ?? 'Error tidak diketahui'));

                    } else {
                    // ==========================================================
                    // 🔥 LOGIKA FIX: AMBIL REF SEPERTI PERCETAKAN 🔥
                    // ==========================================================
                    Log::info('KiriminAja Create Order RAW Response:', $kiriminResponse);
                    // 1. Coba ambil dari 'data' -> 'order_id' (Biasanya ini format KiriminAja v3)
                    // 2. Coba ambil dari 'id' (Format v4/Booking)
                    // 3. Coba ambil dari 'payment_ref'
                    // ==========================================================
                    // 🔥 UPDATE BARU: LOGIKA FIX BERDASARKAN LOG ASLI 🔥
                    // ==========================================================

                    // 1. Ambil Booking ID / Pickup Number (Ini yang VALID dari log Bapak)
                    $bookingId = $kiriminResponse['pickup_number']         // Prioritas 1: Pickup Number (XID-...)
                              ?? $kiriminResponse['id']                    // Prioritas 2: ID Root
                              ?? $kiriminResponse['data']['id']            // Prioritas 3: Data ID
                              ?? $kiriminResponse['payment_ref']           // Prioritas 4: Payment Ref
                              ?? ($kiriminResponse['details'][0]['order_id'] ?? null) // Prioritas 5: Details[0]
                              ?? null;

                    // 2. Ambil AWB Asli (Mungkin masih null)
                    $awbAsli = $kiriminResponse['awb']
                            ?? $kiriminResponse['data']['awb']
                            ?? ($kiriminResponse['details'][0]['awb'] ?? null) // Cek di dalam array details
                            ?? ($kiriminResponse['results'][0]['awb'] ?? null);

                    // 3. LOGIKA UTAMA: Prioritas AWB, jika kosong pakai Booking ID
                    $finalResi = !empty($awbAsli) ? $awbAsli : $bookingId;

                    // 4. Fallback Terakhir
                    if (empty($finalResi)) {
                         $finalResi = 'REF-' . $pesanan->nomor_invoice;
                         Log::warning('KiriminAja Response Raw (Masih Gagal):', $kiriminResponse);
                    }

                    // 7. Update Database
                    $pesanan->resi = $finalResi;

                    // Jika ada kolom shipping_ref, isi juga:
                    $pesanan->shipping_ref = $bookingId;

                    $pesanan->status = 'Pesanan Dibuat';
                    $pesanan->status_pesanan = 'Pesanan Dibuat';

                    // 8. Simpan DULU sebelum catat keuangan
                    $pesanan->save();

                    Log::info('KiriminAja SUCCESS', [
                        'invoice' => $pesanan->nomor_invoice,
                        'resi_set' => $finalResi
                    ]);

                    // =========================================================================
                    // HAPUS ATAU KOMENTARI BAGIAN INI DI PesananController.php method store()
                    // =========================================================================

                    /* // 9. Catat Keuangan  <-- HAPUS INI
                    if (method_exists($this, 'simpanKeKeuangan')) {
                        $this->simpanKeuangan($pesanan);
                    } else {
                        self::simpanKeuangan($pesanan);
                    }
                    */

                    if (isset($kiriminResponse['custom_warning'])) {
                        session()->flash('warning', $kiriminResponse['custom_warning']);
                    }

                }
                // --- AKHIR BLOK YANG HILANG ---
            }

            // 7. Simpan Finalisasi Harga
            $pesanan->price = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;
            $pesanan->save();

            DB::commit();

            // 8. Kirim notifikasi WhatsApp
            $notification_total = $pesanan->price;
            $this->_sendWhatsappNotification(
                $pesanan, $validatedData, $shipping_cost,
                (int) $pesanan->insurance_cost, (int) $pesanan->cod_fee,
                $notification_total, $request
            );
            $notifMessage = 'Pesanan baru ' . ($pesanan->resi ? 'dengan resi ' . $pesanan->resi : 'dengan invoice ' . $pesanan->nomor_invoice) . ' berhasil dibuat!';

            // 9. Arahkan pengguna
            if ($paymentUrl) {
                // Arahkan ke Tripay atau DOKU
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
            Log::error('Order Creation Failed: '. $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
        // UBAH NAMA VARIABEL DARI $order MENJADI $pesanan
        $pesanan = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->firstOrFail();

        $customers = User::orderBy('nama_lengkap', 'asc')->get();

        // Pastikan di compact tertulis 'pesanan' (bukan 'order')
        return view('admin.pesanan.edit', compact('pesanan', 'customers'));
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
        $request->validate(['status' => 'required|string|in:Terkirim,Batal,Diproses,Menunggu Pickup, Kadaluarsa, Gagal Bayar, Selesai']); // Tambahkan status lain jika perlu
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
            'tipe' => 'nullable|in:Pengirim,Penerima',
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
                'item_type' => 'required|integer',
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
        // TAMBAHKAN ->orWhere('shipping_ref', $resi)
        $pesanan = Pesanan::where('resi', $resi)
            ->orWhere('nomor_invoice', $resi)
            ->orWhere('shipping_ref', $resi) // <--- TAMBAHAN PENTING
            ->firstOrFail();

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
            Log::info('Tripay Callback (Pesanan SCK-): Already processed or not pending.', ['invoice' => $merchantRef, 'current_status' => $pesanan->status]);
            return; // Selesai
        }

        // Jika Pembayaran Lunas
        if ($status === 'PAID') {
            // --- TAMBAHAN LOG ---
            Log::info('Tripay Callback (Pesanan SCK-): Found Pesanan in correct state. Proceeding...', ['invoice' => $merchantRef]);

            $pesanan->status = 'paid'; // Tandai lunas dulu
            $pesanan->status_pesanan = 'paid';
            //$pesanan->payment_status = 'PAID'; // Simpan status dari Tripay
            $pesanan->save(); // Simpan status lunas

            Log::info('Tripay Callback (Pesanan SCK-): Status changed to PAID. Preparing KiriminAja call...', ['invoice' => $merchantRef]);

            // --- Logika Kirim ke KiriminAja SETELAH LUNAS ---
            try {
                // Buat instance baru untuk akses method private (lebih aman dari static context)
                $instance = new self();
                $validatedData = $pesanan->toArray(); // Ambil data lengkap dari model $pesanan
                Log::debug('Tripay Callback (Pesanan SCK-): Pesanan data prepared.', ['data_count' => count($validatedData)]); // Log data pesanan

                // Dapatkan data alamat yang diperlukan (bisa dari $pesanan)
                $senderAddressData = [
                    'lat' => $pesanan->sender_lat, 'lng' => $pesanan->sender_lng,
                    'kirimaja_data' => ['district_id' => $pesanan->sender_district_id, 'subdistrict_id' => $pesanan->sender_subdistrict_id, 'postal_code' => $pesanan->sender_postal_code]
                ];
                $receiverAddressData = [
                    'lat' => $pesanan->receiver_lat, 'lng' => $pesanan->receiver_lng,
                    'kirimaja_data' => ['district_id' => $pesanan->receiver_district_id, 'subdistrict_id' => $pesanan->receiver_subdistrict_id, 'postal_code' => $pesanan->receiver_postal_code]
                ];
                Log::debug('Tripay Callback (Pesanan SCK-): Address data prepared.', ['sender' => $senderAddressData, 'receiver' => $receiverAddressData]);

                // Ambil biaya yang sudah tersimpan di pesanan
                $cod_value = 0; // Pasti 0 karena ini callback Tripay
                $shipping_cost = (int) $pesanan->shipping_cost;
                $insurance_cost = (int) $pesanan->insurance_cost; // Biaya asuransi yg disimpan
                Log::debug('Tripay Callback (Pesanan SCK-): Cost data prepared.', ['cod' => $cod_value, 'ship' => $shipping_cost, 'ins' => $insurance_cost]);

                // Panggil method private _createKiriminAjaOrder melalui instance
                Log::info('Tripay Callback (Pesanan SCK-): Calling _createKiriminAjaOrder...', ['invoice' => $merchantRef]);
                $kiriminResponse = $instance->_createKiriminAjaOrder(
                    $validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData,
                    $cod_value, $shipping_cost, $insurance_cost // Gunakan biaya tersimpan
                );
                Log::info('Tripay Callback (Pesanan SCK-): KiriminAja response received.', ['response' => $kiriminResponse]); // Log response KiriminAja


                if (($kiriminResponse['status'] ?? false) !== true) {
                    Log::critical('Tripay Callback (Pesanan SCK-): KiriminAja Order FAILED!', ['invoice' => $merchantRef, 'response' => $kiriminResponse]);
                    $pesanan->status = 'Pembayaran Lunas (Gagal Auto-Resi)';
                    $pesanan->status_pesanan = 'Pembayaran Lunas (Gagal Auto-Resi)';
                    // TODO: Notifikasi Admin
                } else {

                    // ==========================================================
                    // 🔥 LOGIKA "SUKSES BAYAR = DAPAT REF/RESI" 🔥
                    // ==========================================================

                    // A. Ambil SHIPPING REF (Kode Booking) - Pasti Ada
                    $bookingId = $kiriminResponse['id']
                              ?? $kiriminResponse['data']['id']
                              ?? $kiriminResponse['payment_ref']
                              ?? null;

                    // B. Ambil AWB (Jika kebetulan langsung ada)
                    $awbAsli = $kiriminResponse['awb']
                            ?? $kiriminResponse['data']['awb']
                            ?? ($kiriminResponse['results'][0]['awb'] ?? null);

                    // C. Tentukan Nilai Kolom RESI (Jangan Boleh Null)
                    // Prioritas: AWB -> Kode Booking -> Fallback Manual
                    $finalResi = !empty($awbAsli) ? $awbAsli : $bookingId;
                    if (empty($finalResi)) $finalResi = 'REF-' . $pesanan->nomor_invoice;

                    // D. UPDATE DATABASE
                    $pesanan->resi = $finalResi; // Isi Resi (Bisa Ref/AWB)

                    // Jika Bapak punya kolom khusus 'shipping_ref', isi juga:
                    $pesanan->shipping_ref = $bookingId;

                    // 3. RUBAH STATUS MENJADI 'PESANAN DIBUAT'
                    $pesanan->status = 'Pesanan Dibuat';
                    $pesanan->status_pesanan = 'Pesanan Dibuat';

                    $pesanan->save(); // Simpan perubahan status & resi

                    // === INSERT KEUANGAN (Pakai new self() karena static) ===
                    self::simpanKeuangan($pesanan);
                }

                $pesanan->save();

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
            //$pesanan->payment_status = $status;
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
            'customer_id' => 'required_if:payment_method,Potong Saldo|nullable|exists:Pengguna,id_pengguna',
            'sender_lat' => 'nullable|numeric', 'sender_lng' => 'nullable|numeric',
            'receiver_lat' => 'nullable|numeric', 'receiver_lng' => 'nullable|numeric',
            'sender_note' => 'nullable|string|max:255', 'receiver_note' => 'nullable|string|max:255',
            'item_type' => 'required|integer',
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

            // --- PERBAIKAN DIMULAI DI SINI ---
            // Alamat lengkap gagal di geocode. Gunakan format sederhana
            // (Kelurahan/Desa + Kecamatan) seperti di controller publik.
            $simpleAddressQuery = implode(', ', array_filter([
                $request->input("{$type}_village"), // e.g., "Ketanggi"
                $request->input("{$type}_district"), // e.g., "Ngawi"
                $request->input("{$type}_regency")   // Tambahkan kabupaten agar lebih akurat
            ]));

            Log::info("Geocode fallback triggered for {$type}. Query: {$simpleAddressQuery}");

            $geo = $this->geocode($simpleAddressQuery); // Gunakan kueri sederhana
            if ($geo) {
                $lat = $geo['lat'];
                $lng = $geo['lng'];
                Log::info("Geocode fallback SUCCESS for {$type}. Lat: {$lat}, Lng: {$lng}");
            } else {
                Log::warning("Geocode fallback FAILED for {$type} with simple query.", ['query' => $simpleAddressQuery]);
            }
            // --- PERBAIKAN SELESAI ---
}

 $finalLat = (is_numeric($lat) && $lat != 0) ? (float)$lat : null;
 $finalLng = (is_numeric($lng) && $lng != 0) ? (float)$lng : null;

 return ['lat' => $finalLat, 'lng' => $finalLng, 'kirimaja_data' => $kirimajaAddr];
}

private function _saveOrUpdateKontak(array $data, string $prefix, string $tipe)
{
    if (!empty($data["save_{$prefix}"])) {

        // 1. Validasi data dasar
        $phoneKey = "{$prefix}_phone";
        if (!isset($data[$phoneKey])) {
            Log::warning("Gagal simpan kontak: Nomor HP kosong.", ['prefix' => $prefix]);
            return;
        }

        $sanitizedPhone = $this->_sanitizePhoneNumber($data[$phoneKey]);
        $name = $data["{$prefix}_name"] ?? null;
        $address = $data["{$prefix}_address"] ?? null;

        if (empty($sanitizedPhone) || empty($name) || empty($address)) {
            Log::warning("Gagal simpan kontak: Data (Nama/HP/Alamat) tidak lengkap.", ['prefix' => $prefix]);
            return;
        }

        // 2. Cari kontak yang ada
        $existingContact = Kontak::where('no_hp', $sanitizedPhone)->first();

        // 3. Tentukan nilai tipe
        $newTipe = $tipe;
        if ($existingContact) {
            if ($existingContact->tipe === 'Keduanya') {
                $newTipe = 'Keduanya';
            } elseif ($existingContact->tipe !== $tipe) {
                $newTipe = 'Keduanya';
            }
        }

        // 4. Simpan atau update kontak
        Kontak::updateOrCreate(
            ['no_hp' => $sanitizedPhone],
            [
                'nama'        => $name,
                'no_hp'       => $sanitizedPhone,
                'alamat'      => $address,
                'province'    => $data["{$prefix}_province"] ?? null,
                'regency'     => $data["{$prefix}_regency"] ?? null,
                'district'    => $data["{$prefix}_district"] ?? null,
                'village'     => $data["{$prefix}_village"] ?? null,
                'postal_code' => $data["{$prefix}_postal_code"] ?? null,
                'tipe'        => $newTipe
            ]
        );
    }
}


    private function _preparePesananData(array $validatedData, int $total_ongkir, string $ip, string $userAgent): array
    {
        // ... (fungsi _preparePesananData - pastikan prefix SCK-) ...
        do { $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6)); } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());
        // Ambil semua field yang relevan dari validasi
        $fieldsToSave = array_keys($this->_validateOrderRequest(request())); // Ambil keys dari validasi
        // Hapus field yang tidak ada di tabel pesanan atau tidak ingin disimpan langsung
        $fieldsToExclude = ['save_sender', 'save_receiver', 'customer_email', 'sender_phone_original', 'receiver_phone_original'];
        $fieldsToSave = array_diff($fieldsToSave, $fieldsToExclude);

        $pesananCoreData = collect($validatedData)->only($fieldsToSave)->all();

    // ============================================================
    // TAMBAHAN: MAPPING MANUAL KE total_harga_barang
    // ============================================================
    // Mengisi kolom 'total_harga_barang' dengan nilai dari input 'item_price'
    $pesananCoreData['total_harga_barang'] = $validatedData['item_price'];

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
        // 1. Dapatkan biaya murni dari string ekspedisi
        $parts = explode('-', $validatedData['expedition']); $count = count($parts);
        $cod_fee = 0; $ansuransi_fee = 0; $shipping_cost = 0;
        if ($count >= 6) { $cod_fee = (int) end($parts); $ansuransi_fee = (int) $parts[$count - 2]; $shipping_cost = (int) $parts[$count - 3]; }
        elseif ($count === 5) { $ansuransi_fee = (int) $parts[4]; $shipping_cost = (int) $parts[3]; }
        elseif ($count === 4) { $shipping_cost = (int) $parts[3]; }
        else { Log::warning('Format expedition tidak dikenal', ['exp' => $validatedData['expedition']]); }

        $item_price = (int)$validatedData['item_price'];
        $use_insurance = $validatedData['ansuransi'] == 'iya';

        // 2. Hitung $total_paid_ongkir (Ini HANYA untuk Saldo / Tripay)
        // Sesuai aturan Anda: (ongkir) atau (ongkir + asuransi)
        $total_paid_ongkir = $shipping_cost;
        if ($use_insurance) {
            $total_paid_ongkir += $ansuransi_fee;
        }

        // 3. Hitung $cod_value (Ini HANYA untuk COD / CODBARANG)
        $cod_value = 0;
        if ($validatedData['payment_method'] === 'CODBARANG') {
            // Aturan CODBARANG: "cod fee + ongkir + nilai barang" ( + asuransi jika dicentang)
            $cod_value = $item_price + $shipping_cost + $cod_fee;
            if ($use_insurance) {
                $cod_value += $ansuransi_fee;
            }

        } elseif ($validatedData['payment_method'] === 'COD') {
            // Aturan COD CUSTOM (COD):
            if ($use_insurance) {
                // "Pakai Asuransi: cod_fee + ongkir + nilai_barang + asuransi"
                $cod_value = $item_price + $shipping_cost + $ansuransi_fee + $cod_fee;
            } else {
                // "Tidak Asuransi: nilai_barang + cod_fee + ongkir"
                $cod_value = $item_price + $shipping_cost + $cod_fee; // <-- SUDAH DIPERBAIKI
            }
        }

        // 4. Kembalikan semua biaya murni dan total yang dihitung
        return compact('total_paid_ongkir', 'cod_value', 'shipping_cost', 'ansuransi_fee', 'cod_fee');
    }

   /**
     * FUNGSI UNTUK MEMBUAT ORDER DI KIRIMIN AJA
     */
    private function _createKiriminAjaOrder(
        array $data, Pesanan $order, KiriminAjaService $kirimaja,
        array $senderData, array $receiverData, int $cod_value,
        int $shipping_cost, int $insurance_cost
    ): array
    {
        $expeditionParts = explode('-', $data['expedition'] ?? '');
        $serviceGroup = $expeditionParts[0] ?? null;
        $courier = $expeditionParts[1] ?? null;
        $service_type = $expeditionParts[2] ?? null;

        if (empty($data['sender_address']) || empty($data['sender_phone']) || empty($data['sender_name']) ||
            empty($data['receiver_name']) || empty($data['receiver_phone']) || empty($data['receiver_address']) ||
            empty($data['item_description']) || !isset($data['item_price']) || !isset($data['weight']) ||
            !isset($data['item_type']) || empty($courier) || empty($service_type)) {
                Log::error('_createKiriminAjaOrder (Customer): Missing required data.', ['invoice' => $order->nomor_invoice]);
                return ['status' => false, 'text' => 'Data pesanan tidak lengkap untuk dikirim ke ekspedisi.'];
        }

        // ============================================================
        // LOGIKA FINAL: FEE (Min 2.500) + PPN 11% + PEMBULATAN 500
        // ============================================================

        $apiItemPrice = (float) $data['item_price'];
        $finalInsuranceAmount = ($data['ansuransi'] == 'iya') ? (int)$insurance_cost : 0;
        $finalCodValue = $cod_value;

        // JIKA METODE 'COD' (COD Ongkir):
        if (isset($data['payment_method']) && $data['payment_method'] === 'COD') {

            // 1. Tentukan Asuransi & Harga Barang untuk API
            if ($data['ansuransi'] == 'iya') {
                $apiItemPrice = (float) $data['item_price'];
                $finalInsuranceAmount = (int) $insurance_cost;
            } else {
                $apiItemPrice = 10000;
                $finalInsuranceAmount = 0;
            }

            // 2. Hitung Total Dasar (Ongkir + Asuransi)
            $totalBasic = (int)$shipping_cost + (int)$finalInsuranceAmount;

            // 3. Hitung COD Fee (3% dari Total Dasar, Minimal 2.500)
            // Contoh: 3% dari 72.000 = 2.160 -> Dipaksa jadi 2.500
            $calculatedFee = $totalBasic * 0.03;
            $codFeeValue = max(2500, $calculatedFee);

            // 4. Hitung PPN 11% HANYA DARI FEE COD
            $ppnFee = $codFeeValue * 0.11;

            // 5. Jumlahkan Semua (Total Mentah)
            $grandTotalMentah = $totalBasic + $codFeeValue + $ppnFee;

            // 6. LOGIKA PEMBULATAN (REQUEST BAPAK)
            // 1-499 -> 500 | 501-999 -> 1000
            $finalCodValue = (int) (ceil($grandTotalMentah / 500) * 500);

            // Update harga di database
            $order->price = $finalCodValue;
            $order->save();
        }
        // ============================================================

        if (in_array($serviceGroup, ['instant', 'sameday'])) {
            if (empty($senderData['lat']) || empty($senderData['lng']) || empty($receiverData['lat']) || empty($receiverData['lng'])) {
                return ['status' => false, 'text' => 'Koordinat alamat tidak valid untuk pengiriman instan/sameday.'];
            }

            $payload = [
                'service' => $courier, 'service_type' => $service_type, 'vehicle' => 'motor',
                'order_prefix' => $order->nomor_invoice,
                'packages' => [[
                    'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'],
                    'destination_lat' => $receiverData['lat'], 'destination_long' => $receiverData['lng'],
                    'destination_address' => $data['receiver_address'],'destination_address_note' => $data['receiver_note'] ?? '-',
                    'origin_name' => $data['sender_name'], 'origin_phone' => $data['sender_phone'],
                    'origin_lat' => $senderData['lat'], 'origin_long' => $senderData['lng'],
                    'origin_address' => $data['sender_address'], 'origin_address_note' => $data['sender_note'] ?? '-',
                    'shipping_price' => (int)$shipping_cost,
                    'item' => [
                        'name' => $data['item_description'], 'description' => 'Pesanan ' . $order->nomor_invoice,
                        'price' => (int)$data['item_price'], 'weight' => (int)$data['weight'],
                    ]
                ]]
            ];
            Log::info('KiriminAja Create Instant Order Payload (Customer):', $payload);
            return $kirimaja->createInstantOrder($payload);

        } else {
            $scheduleResponse = $kirimaja->getSchedules();
            // 1. Tentukan Kategori Dulu (Penting untuk Cek API)
            $category = ($data['service_type'] ?? $serviceGroup) === 'cargo' ? 'trucking' : 'regular';

            // 2. Default: Request Pickup 1 JAM DARI SEKARANG (BUFFER WAKTU)
            // SPX akan menolak jika request detik ini juga. Kita tambah 1 jam.
            // =================================================================
            // PERBAIKAN: BUFFER WAKTU +1 JAM
            // =================================================================
            // SPX/Ekspedisi menolak jika request pickup "detik ini juga".
            // Kita harus memajukan jam pickup minimal 1 jam kedepan.

            $bufferTime = \Carbon\Carbon::now()->addHour();
            $scheduleClock = $bufferTime->format('Y-m-d H:i:s');
            $pesanGeserJadwal = null;

            // =================================================================
            // 3. LOGIKA JADWAL PICKUP DINAMIS (BERDASARKAN CUT OFF TIME API)
            // =================================================================
            try {
                Log::info("START: Cek Jadwal Pickup Dinamis (Invoice: {$order->nomor_invoice})");

                // Ambil data kurir yang dipilih user
                $expParts = explode('-', $data['expedition'] ?? '');
                $targetCourier = $expParts[1] ?? null; // sicepat
                $targetService = $expParts[2] ?? null; // GOKIL

                if ($targetCourier && $targetService) {

                    // CEK METADATA KURIR KE API KIRIMINAJA (DUMMY REQUEST)
                    $pricingCheck = $kirimaja->getExpressPricing(
                        $senderData['kirimaja_data']['district_id'],
                        $senderData['kirimaja_data']['subdistrict_id'],
                        $receiverData['kirimaja_data']['district_id'],
                        $receiverData['kirimaja_data']['subdistrict_id'],
                        1000, 1, 1, 1, 1000,
                        $targetCourier, // Filter kurir spesifik
                        $category, 0
                    );

                    $apiCutOffTime = null;

                    // Cari Cut Off Time dari hasil API
                    if (isset($pricingCheck['results']) && is_array($pricingCheck['results'])) {
                        foreach ($pricingCheck['results'] as $res) {
                            if (strcasecmp($res['service'], $targetCourier) === 0 &&
                                strcasecmp($res['service_type'], $targetService) === 0) {
                                $apiCutOffTime = $res['cut_off_time'];
                                break;
                            }
                        }
                    }

                    // ==========================================================
                    // PERBAIKAN LOGIKA BANDING WAKTU
                    // ==========================================================
                    // Kita bandingkan BUFFER TIME (Rencana Pickup) vs CUTOFF

                    $jamRencanaPickup = $bufferTime->format('H:i:s');

                    if (!empty($apiCutOffTime)) {
                        // KASUS A: Ada Cut Off Time (Misal SPX 17:00:00)
                        Log::info("COMPARE: Rencana Pickup ($jamRencanaPickup) vs Batas Ekspedisi ($apiCutOffTime)");

                        if ($jamRencanaPickup > $apiCutOffTime) {
                             // Jika jam rencana (+1 jam) sudah lewat batas sore -> BESOK
                             $besok = strtotime('+1 day 09:00:00');
                             $scheduleClock = date('Y-m-d H:i:s', $besok);
                             $pesanGeserJadwal = "Jadwal Pickup digeser ke besok (" . date('d M H:i', $besok) . ") karena waktu persiapan mepet cutoff.";
                             Log::warning("DECISION: Pickup DIGESER ke Besok (Melewati Cutoff).");
                        } else {
                             Log::info("DECISION: Pickup TETAP Hari Ini ($scheduleClock).");
                        }
                    } else {
                        // KASUS B: Cut Off Time NULL (Bebas) - Cek Batas Wajar Malam (Safety Net)
                        if ($jamRencanaPickup > '21:00:00') {
                            $besok = strtotime('+1 day 09:00:00');
                            $scheduleClock = date('Y-m-d H:i:s', $besok);
                            $pesanGeserJadwal = "Jadwal Pickup digeser ke besok karena sudah terlalu larut malam.";
                            Log::warning("DECISION: Pickup DIGESER ke Besok (Safety Net > 21:00).");
                        }
                    }
                }
            } catch (Exception $e) {
                // Fallback jika API error
                Log::error("ERROR: Gagal Cek Cutoff Time API: " . $e->getMessage());
                if (date('H') >= 17) {
                     $besok = strtotime('+1 day 09:00:00');
                     $scheduleClock = date('Y-m-d H:i:s', $besok);
                }
            }
            // =================================================================

            // Hitung Berat Volumetrik
            $category = ($data['service_type'] ?? $serviceGroup) === 'cargo' ? 'trucking' : 'regular';

            $weightInput = (int) $data['weight'];
            $lengthInput = (int) ($data['length'] ?? 1);
            $widthInput = (int) ($data['width'] ?? 1);
            $heightInput = (int) ($data['height'] ?? 1);

            $volumetricWeight = 0;
            if ($lengthInput > 0 && $widthInput > 0 && $heightInput > 0) {
                $volumetricWeight = ($widthInput * $lengthInput * $heightInput) / ($category === 'trucking' ? 4000 : 6000) * 1000;
            }
            $finalWeight = max($weightInput, $volumetricWeight);

            if (empty($senderData['kirimaja_data']['district_id']) || empty($senderData['kirimaja_data']['subdistrict_id']) ||
                empty($receiverData['kirimaja_data']['district_id']) || empty($receiverData['kirimaja_data']['subdistrict_id']) ||
                empty($senderData['kirimaja_data']['postal_code']) || empty($receiverData['kirimaja_data']['postal_code'])) {
                    Log::error('_createKiriminAjaOrder (Customer): Missing KiriminAja address IDs.', ['invoice' => $order->nomor_invoice]);
                    return ['status' => false, 'text' => 'ID alamat KiriminAja tidak lengkap.'];
            }

            $payload = [
                'address' => $data['sender_address'], 'phone' => $data['sender_phone'], 'name' => $data['sender_name'],
                'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
                'zipcode' => $senderData['kirimaja_data']['postal_code'], 'schedule' => $scheduleClock,
                'platform_name' => 'tokosancaka.com', 'category' => $category,
                'latitude' => $senderData['lat'], 'longitude' => $senderData['lng'],
                'packages' => [[
                    'order_id' => $order->nomor_invoice, 'item_name' => $data['item_description'],
                    'package_type_id' => (int)$data['item_type'],
                    'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'],
                    'destination_address' => $data['receiver_address'],
                    'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                    'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                    'weight' => (int) ceil($finalWeight),
                    'width' => $widthInput, 'height' => $heightInput, 'length' => $lengthInput,

                    // --- DATA FINAL KE API ---
                    'item_value' => (int)$apiItemPrice,
                    'insurance_amount' => (int)$finalInsuranceAmount,
                    'cod' => (int)$finalCodValue,
                    // -------------------------

                    'service' => $courier, 'service_type' => $service_type,
                    'shipping_cost' => (int)$shipping_cost
                ]]
            ];
            Log::info('KiriminAja Create Express/Cargo Order Payload (Customer):', $payload);

            // PANGGIL API
    $apiResponse = $kirimaja->createExpressOrder($payload);

    // 🔥 TAMBAHAN: Masukkan pesan geser jadwal ke dalam array response API
    if ($pesanGeserJadwal) {
        $apiResponse['custom_warning'] = $pesanGeserJadwal;
    }

    return $apiResponse;

            //return $kirimaja->createExpressOrder($payload);
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
        // ==========================================================
        // 🔥 PERBAIKAN LOGIKA SWITCHING MODE TRIPAY (DB) 🔥
        // ==========================================================

        // 1. Ambil Mode Global dari Database
        // Pastikan namespace App\Models\Api sudah di-use di atas
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        // 2. Siapkan wadah variabel
        $baseUrl      = '';
        $apiKey       = '';
        $privateKey   = '';
        $merchantCode = '';

        // 3. Isi variabel berdasarkan MODE yang aktif di Database
        if ($mode === 'production') {
            $baseUrl      = 'https://tripay.co.id/api/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'production');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'production');
        } else {
            // Fallback ke Sandbox
            $baseUrl      = 'https://tripay.co.id/api-sandbox/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox');
        }

        // Cek Konfigurasi Lengkap
        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            Log::error('TRIPAY CONFIG MISSING (Mode: ' . $mode . ')');
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap.'];
        }

        // ==========================================================
        // AKHIR PERBAIKAN
        // ==========================================================

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
            'return_url' => route('admin.pesanan.show', ['resi' => $pesanan->nomor_invoice]), // Arahkan ke tracking
            'expired_time' => time() + (24 * 60 * 60), // 24 jam
            'signature' => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];
        Log::info('Tripay Create Transaction Payload (Internal):', $payload);
        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(60)
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

   /**
     * [PERBAIKAN] Menampilkan halaman riwayat scan dengan data, filter, dan paginasi.
     */
    public function riwayatScan(Request $request)
    {
        // Ambil parameter dari request, sesuai dengan form di view
        $search = $request->input('search');
        $range = $request->input('range');
        $perPage = $request->input('per_page', 10); // Default 10

        // Mulai query pada model Pesanan
        $query = Pesanan::query();

        // Filter status: Halaman ini hanya untuk riwayat yang SUDAH di-scan.
        // Berdasarkan view, statusnya adalah 'Diproses' atau 'Terkirim'.
        $query->whereIn('status_pesanan', ['Diproses', 'Terkirim']);

        // Terapkan filter pencarian (berdasarkan resi, resi aktual, dan nama)
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")
                  ->orWhere('resi_aktual', 'like', "%{$search}%")
                  ->orWhere('nomor_invoice', 'like', "%{$search}%")
                  ->orWhere('nama_pembeli', 'like', "%{$search}%"); // Sesuai kolom di view
            });
        }

        // Terapkan filter rentang tanggal (berdasarkan 'updated_at' seperti di view)
        if ($range === 'harian') {
            $query->whereDate('updated_at', Carbon::today());
        } elseif ($range === 'mingguan') {
            $query->whereBetween('updated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($range === 'bulanan') {
            $query->whereBetween('updated_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        }

        // Urutkan berdasarkan yang terbaru di-scan (updated_at) dan paginasi
        // Tambahkan withQueryString() agar filter tetap ada saat pindah halaman
        $scannedOrders = $query->orderBy('updated_at', 'desc')->paginate($perPage)->withQueryString();

        // Kirim data 'scannedOrders' ke view
        // Pastikan nama view-nya benar (dengan strip)
        return view('admin.pesanan.riwayat-scan', compact('scannedOrders'));
    }

    /**
     * =========================================================================
     * HANDLER WEBHOOK DOKU (JOKUL)
     * =========================================================================
     *
     * Method ini dipanggil oleh DokuWebhookController
     * saat notifikasi 'SUCCESS' diterima.
     *
     * @param array $data Data lengkap dari DOKU
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleDokuCallback(array $data)
    {
        $merchantRef = $data['order']['invoice_number'];
        $status = $data['transaction']['status']; // Seharusnya 'SUCCESS'

        Log::info('Processing DOKU Callback (di AdminPesananController)...', [
            'ref' => $merchantRef,
            'status' => $status
        ]);

        // Dapatkan service KiriminAja (sama seperti logika Tripay Anda)
        $kirimaja = app(KiriminAjaService::class);

        // Gunakan DB::transaction untuk keamanan, atau minimal lockForUpdate
        DB::beginTransaction();
        try {
            $pesanan = Pesanan::where('nomor_invoice', $merchantRef)->lockForUpdate()->first();

            if (!$pesanan) {
                Log::error('DOKU Callback (Admin): Pesanan Not found.', ['merchant_ref' => $merchantRef]);
                DB::rollBack();
                // Kirim 200 OK agar DOKU tidak kirim ulang
                return response()->json(['message' => 'Order not found, webhook ignored.'], 200);
            }

            if ($pesanan->status !== 'Menunggu Pembayaran') {
                Log::info('DOKU Callback (Admin): Already processed.', ['invoice' => $merchantRef, 'current_status' => $pesanan->status]);
                DB::rollBack();
                return response()->json(['message' => 'Already processed.'], 200);
            }

            // Status 'SUCCESS' dari DOKU sama dengan 'PAID' dari Tripay
            if ($status === 'SUCCESS') {
                Log::info('DOKU Callback (Admin): PAID. Preparing KiriminAja call...', ['invoice' => $merchantRef]);

                // Update status internal dulu
                $pesanan->status = 'paid';
                $pesanan->status_pesanan = 'paid';
                $pesanan->save(); // Simpan status 'paid'

                // Siapkan data untuk KiriminAja (ambil dari method private controller ini)
                // Kita asumsikan AdminPesananController punya method _createKiriminAjaOrder
                // yang sama dengan CustomerOrderController Anda

                $validatedData = $pesanan->toArray(); // Ambil data dari model

                $senderAddressData = [
                    'lat' => $pesanan->sender_lat, 'lng' => $pesanan->sender_lng,
                    'kirimaja_data' => ['district_id' => $pesanan->sender_district_id, 'subdistrict_id' => $pesanan->sender_subdistrict_id, 'postal_code' => $pesanan->sender_postal_code]
                ];
                $receiverAddressData = [
                    'lat' => $pesanan->receiver_lat, 'lng' => $pesanan->receiver_lng,
                    'kirimaja_data' => ['district_id' => $pesanan->receiver_district_id, 'subdistrict_id' => $pesanan->receiver_subdistrict_id, 'postal_code' => $pesanan->receiver_postal_code]
                ];

                $cod_value = 0; // Pasti 0 karena ini pembayaran online
                $shipping_cost = (int) $pesanan->shipping_cost;
                $insurance_cost = (int) $pesanan->insurance_cost;

                // Panggil method private _createKiriminAjaOrder
                // PERHATIKAN: Ini berasumsi method _createKiriminAjaOrder ada di AdminPesananController
                $kiriminResponse = $this->_createKiriminAjaOrder(
                    $validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData,
                    $cod_value, $shipping_cost, $insurance_cost
                );

                if (($kiriminResponse['status'] ?? false) !== true) {
                    Log::critical('DOKU Callback (Admin): KiriminAja Order FAILED!', ['invoice' => $merchantRef, 'response' => $kiriminResponse]);
                    $pesanan->status = 'Pembayaran Lunas (Gagal Auto-Resi)';
                    $pesanan->status_pesanan = 'Pembayaran Lunas (Gagal Auto-Resi)';
                } else {

                    // ==========================================================
                    // 🔥 LOGIKA "SUKSES BAYAR = DAPAT REF/RESI" 🔥
                    // ==========================================================

                    // A. Ambil SHIPPING REF (Kode Booking)
                    $bookingId = $kiriminResponse['id']
                              ?? $kiriminResponse['data']['id']
                              ?? $kiriminResponse['payment_ref']
                              ?? null;

                    // B. Ambil AWB
                    $awbAsli = $kiriminResponse['awb']
                            ?? $kiriminResponse['data']['awb']
                            ?? ($kiriminResponse['results'][0]['awb'] ?? null);

                    // C. Tentukan Nilai Kolom RESI
                    $finalResi = !empty($awbAsli) ? $awbAsli : $bookingId;
                    if (empty($finalResi)) $finalResi = 'REF-' . $pesanan->nomor_invoice;

                    // D. UPDATE DATABASE
                    $pesanan->resi = $finalResi; // Berikan Kode Ref/Booking

                    // 3. RUBAH STATUS MENJADI 'PESANAN DIBUAT' (Atau Menunggu Pickup)
                    $pesanan->status = 'Pesanan Dibuat';
                    $pesanan->status_pesanan = 'Pesanan Dibuat';

                    $pesanan->save(); // Simpan

                    // 4. CATAT KEUANGAN
                    self::simpanKeuangan($pesanan);
                }
                $pesanan->save();
                DB::commit(); // Commit semua perubahan

                // TODO: Panggil notifikasi WA "Lunas" Anda di sini
                // $this->_sendWhatsappNotification(...)

            } else {
                // Handle status lain jika perlu (misal FAILED, EXPIRED)
                Log::warning('DOKU Callback (Admin): Received non-success status.', ['ref' => $merchantRef, 'status' => $status]);
                DB::rollBack();
            }

            return response()->json(['message' => 'Webhook processed successfully.'], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("DOKU Callback (Admin): Exception during process.", [ 'ref' => $merchantRef, 'error' => $e->getMessage()]);
            // Kirim 500 agar DOKU mencoba lagi
            return response()->json(['message' => 'Internal server error during processing.'], 500);
        }
    }

    private function _sendWhatsappNotification(
        Pesanan $pesanan, array $validatedData, int $shipping_cost,
        int $ansuransi_fee, int $cod_fee, int $total_paid,
        Request $request
    ) {
        // 1. Ambil nomor HP
        $displaySenderPhone = $request->input('sender_phone') ?? $validatedData['sender_phone_original'] ?? $pesanan->sender_phone;
        $displayReceiverPhone = $request->input('receiver_phone') ?? $validatedData['receiver_phone_original'] ?? $pesanan->receiver_phone;

        // 2. LOGIKA HITUNG ULANG (AGAR SINKRON DENGAN BLADE & CONTROLLER)
        $pmClean = strtoupper(trim($pesanan->payment_method));
        $isCodOngkir = ($pmClean === 'COD');
        $isCodBarang = ($pmClean === 'CODBARANG');

        // Ambil Harga Barang Asli
        $realItemPrice = $validatedData['item_price'] ?? $pesanan->item_price ?? 0;

        if ($isCodOngkir) {
            // === RUMUS COD ONGKIR (Sesuai Request) ===

            // A. Aturan Harga Barang (> 1jt dianggap 10rb)
            if ($realItemPrice > 1000000) {
                $basisBarang = 10000;
            } else {
                $basisBarang = $realItemPrice;
            }

            // B. Hitung Fee Murni (3% dari Ongkir + BasisBarang)
            $basisHitung = $shipping_cost + $basisBarang;
            $feeHitung   = $basisHitung * 0.03;
            $feeCodMurni = max(2500, $feeHitung); // Minimal 2.500

            // C. Hitung PPN 11% dari Fee
            $ppnFee = $feeCodMurni * 0.11;

            // D. Hitung Total Mentah (Ongkir + Asuransi + Fee + PPN)
            $grandTotalMentah = $shipping_cost + $ansuransi_fee + $feeCodMurni + $ppnFee;

            // E. Pembulatan Kelipatan 500 ke Atas (Total Final)
            $finalTotal = (int) (ceil($grandTotalMentah / 500) * 500);

            // F. Tentukan Fee Layanan Tampilan (Sisa dari Total)
            // Agar di WA: Ongkir + Asuransi + Fee = Total (Klop)
            $finalCodFee = $finalTotal - $shipping_cost - $ansuransi_fee;

        } elseif ($isCodBarang) {
            // COD BARANG: Ambil data DB/Param apa adanya
            $finalCodFee = $cod_fee;
            $finalTotal  = $pesanan->price; // Total DB (Include Barang)
        } else {
            // TRANSFER: Ambil data DB
            $finalCodFee = $cod_fee;
            $finalTotal  = $pesanan->price;
        }

        // 3. Susun Detail Paket
        $detailPaket = "*Detail Paket:*\n";
        $detailPaket .= "Deskripsi: " . ($pesanan->item_description ?? '-') . "\n";
        $detailPaket .= "Berat: " . ($pesanan->weight ?? 0) . " Gram\n";

        $expeditionParts = explode('-', $pesanan->expedition ?? '');
        $exp_vendor = $expeditionParts[1] ?? '';
        $exp_service_type = $expeditionParts[2] ?? '';
        $service_display = trim(ucwords(strtolower(str_replace('_', ' ', $exp_vendor))) . ' ' . ucwords(strtolower(str_replace('_', ' ', $exp_service_type))));

        $detailPaket .= "Ekspedisi: " . ($service_display ?: '-') . "\n";
        $detailPaket .= "Layanan: " . ucwords($pesanan->service_type ?? '-');

        if ($pesanan->resi) {
            $detailPaket .= "\nResi: *" . $pesanan->resi . "*";
        } else {
            $detailPaket .= "\nResi: Menunggu Resi";
        }

        // 4. Susun Rincian Biaya
        $rincianBiaya = "*Rincian Biaya:*\n";

        // A. Nilai Barang
        if ($realItemPrice > 0) {
            $rincianBiaya .= "- Nilai Barang: Rp " . number_format($realItemPrice, 0, ',', '.');
            if ($isCodOngkir) {
                $rincianBiaya .= " (Tidak Masuk Tagihan COD)\n";
            } else {
                $rincianBiaya .= "\n";
            }
        }

        // B. Ongkir
        $rincianBiaya .= "- Ongkir: Rp " . number_format($shipping_cost, 0, ',', '.') . "\n";

        // C. Asuransi
        if ($ansuransi_fee > 0) {
            $rincianBiaya .= "- Asuransi: Rp " . number_format($ansuransi_fee, 0, ',', '.') . "\n";
        }

        // D. Biaya Layanan / COD
        // Tampilkan Fee yang sudah disesuaikan dengan pembulatan
        if ($finalCodFee > 0) {
            $rincianBiaya .= "- Biaya Layanan: Rp " . number_format($finalCodFee, 0, ',', '.') . "\n";
        }

        // 5. Tentukan Status Pembayaran
        $statusBayar = "⏳ Menunggu Pembayaran";

        if (in_array($pmClean, ['COD', 'CODBARANG'])) {
            $statusBayar = "⏳ Bayar di Tempat (COD)";
        } elseif ($pesanan->payment_method === 'Potong Saldo') {
            $statusBayar = "✅ Lunas via Saldo";
        } elseif ($pesanan->status_pesanan === 'PAID' || in_array($pesanan->status, ['Menunggu Pickup', 'Diproses', 'Terkirim', 'Pembayaran Lunas (Gagal Auto-Resi)', 'Pembayaran Lunas (Error Kirim API)'])) {
            $statusBayar = "✅ Lunas";
        } elseif (in_array($pesanan->status, ['Gagal Bayar', 'Kadaluarsa'])) {
            $statusBayar = "❌ Pembayaran Gagal/Kadaluarsa";
        }

        // 6. Susun Template Pesan Akhir
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
*Total Tagihan: Rp {TOTAL_BAYAR}*
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
                '{LINK_RESI}'
            ],
            [
                $pesanan->nomor_invoice,
                $pesanan->sender_name, $displaySenderPhone,
                $pesanan->receiver_name, $displayReceiverPhone,
                $detailPaket,
                $rincianBiaya,
                number_format($finalTotal, 0, ',', '.'), // Total Final yang sudah dibulatkan
                $statusBayar,
                $linkResi
            ],
            $messageTemplate
        );

        // ---------------------------------------------------------------------
        // 7. KIRIM PESAN (MODIFIKASI PUSHWA)
        // ---------------------------------------------------------------------
        $senderWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($pesanan->sender_phone));
        $receiverWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($pesanan->receiver_phone));

        try {
            // --- OPSI 1: PUSHWA (AKTIF) ---
            if ($senderWa) {
                $this->_sendPushWa($senderWa, $message);
            }
            if ($receiverWa) {
                $this->_sendPushWa($receiverWa, $message);
            }

            // --- OPSI 2: FONNTE (NON-AKTIF / CADANGAN) ---
            /*
            if ($senderWa) {
                FonnteService::sendMessage($senderWa, $message);
            }
            if ($receiverWa) {
                FonnteService::sendMessage($receiverWa, $message);
            }
            */

        } catch (Exception $e) {
            Log::error('WA Notification failed: ' . $e->getMessage(), ['invoice' => $pesanan->nomor_invoice]);
        }
    }

    /**
     * FUNGSI API: Kirim Resi via WhatsApp (Dipanggil AJAX dari View Cetak Resi)
     * Menangani request dari tombol "Kirim WA (Penerima/Pengirim)"
     */
    public function sendResiViaWhatsappApi(Request $request)
    {
        try {
            // 1. Validasi Input dari AJAX
            $request->validate([
                'resi'   => 'required|string',
                'target' => 'required|in:sender,receiver'
            ]);

            $resi = $request->input('resi');
            $target = $request->input('target');

            // 2. Cari Pesanan Berdasarkan Resi (Admin bisa akses semua pesanan)
            $pesanan = Pesanan::where('resi', $resi)->first();

            if (!$pesanan) {
                return response()->json(['status' => 'error', 'message' => 'Data pesanan dengan resi tersebut tidak ditemukan.']);
            }

            // 3. Tentukan Data Target
            $targetName = '';
            $targetPhoneRaw = '';
            $roleName = '';

            if ($target === 'receiver') {
                $targetName = $pesanan->receiver_name;
                $targetPhoneRaw = $pesanan->receiver_phone;
                $roleName = 'Penerima';
            } else {
                $targetName = $pesanan->sender_name;
                $targetPhoneRaw = $pesanan->sender_phone;
                $roleName = 'Pengirim';
            }

            // 4. Sanitize Nomor HP
            $targetPhone = $this->_sanitizePhoneNumber($targetPhoneRaw);

            // 5. Susun Pesan WhatsApp
            $trackingLink = "https://tokosancaka.com/tracking/search?resi=" . $pesanan->resi;

            $message = "Halo Kak {$targetName} ({$roleName}) 👋,\n\n";
            $message .= "Berikut adalah *Soft Copy Resi* untuk paket Anda:\n\n";
            $message .= "📜 No. Invoice: *{$pesanan->nomor_invoice}*\n";
            $message .= "📦 No. Resi: *{$pesanan->resi}*\n";
            $message .= "🚚 Ekspedisi: {$pesanan->expedition} ({$pesanan->service_type})\n";
            $message .= "⚖️ Berat: {$pesanan->weight} Gram\n\n";
            $message .= "🔍 *Lacak status paket secara real-time di sini:*\n";
            $message .= "{$trackingLink}\n\n";
            $message .= "Simpan bukti resi ini ya Kak. Terima kasih telah menggunakan Sancaka Express! 🙏";

            // 6. Kirim via Fonnte Service
            // Pastikan Anda sudah use App\Services\FonnteService di atas
            $response = FonnteService::sendMessage($targetPhone, $message);

            // 7. Return JSON
            return response()->json([
                'status' => 'success',
                'message' => "Resi berhasil dikirim ke WhatsApp {$roleName} ({$targetName}).",
                'debug' => $response
            ]);

        } catch (Exception $e) {
            Log::error("API WA Resi Error (Admin): " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pengiriman: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
 * Menampilkan halaman cetak thermal berdasarkan Resi atau Nomor Invoice.
 * URL: tokosancaka.com/{resi}/cetak_thermal
 */
public function cetakThermal($resi)
{
    // Mengambil data berdasarkan resi atau nomor invoice
    $pesanan = \App\Models\Pesanan::where('resi', $resi)
                ->orWhere('nomor_invoice', $resi)
                ->firstOrFail();

    // Kirim variabel $pesanan ke view
    return view('admin.pesanan.cetak_thermal', compact('pesanan'));
}

    /**
     * HELPER: Simpan Transaksi Keuangan (HANYA JIKA STATUS SUKSES)
     * Cash Basis: Pencatatan dilakukan saat paket benar-benar selesai.
     */
    // Hapus "Ke" agar sesuai dengan panggilan dari Webhook
    public static function simpanKeuangan(Pesanan $pesanan)
    {
        try {
            // ==========================================================
            // 1. VALIDASI STATUS (GATEKEEPER)
            // ==========================================================
            // Daftar status yang dianggap "Uang Masuk / Transaksi Selesai"
            $statusSukses = [
                'Selesai',
                'Terkirim',
                'Delivered',
                'Success',
                'Berhasil',
                'Finished'
            ];

            // Normalisasi status database agar huruf besar/kecil tidak masalah
            $currentStatus = ucwords(strtolower($pesanan->status_pesanan));

            // JIKA STATUS BELUM SUKSES -> STOP / JANGAN SIMPAN
            if (!in_array($currentStatus, $statusSukses)) {
                return;
            }

            // ==========================================================
            // 2. HITUNG DISKON & PROFIT
            // ==========================================================
            $ekspedisiRules = DB::table('Ekspedisi')->get();
            $diskonPersen = 0;
            $expStr = strtolower($pesanan->expedition);

            foreach ($ekspedisiRules as $rule) {
                if (str_contains($expStr, strtolower($rule->keyword))) {
                    $rules = json_decode($rule->diskon_rules, true);
                    if (is_array($rules)) {
                        foreach ($rules as $key => $val) {
                            if ($key !== 'default' && str_contains($expStr, $key)) {
                                $diskonPersen = $val;
                                break 2;
                            }
                        }
                        if (isset($rules['default'])) $diskonPersen = $rules['default'];
                    }
                    break;
                }
            }

            // ==========================================================
            // 3. HITUNG NOMINAL
            // ==========================================================
            $ongkirPublish = (float) $pesanan->shipping_cost; // Omzet
            $nilaiDiskon   = $ongkirPublish * $diskonPersen;  // Profit
            $modalReal     = $ongkirPublish - $nilaiDiskon;   // Beban Pokok

            // Validasi nominal
            if ($ongkirPublish <= 0) return;

            // Pastikan Resi Ada (Karena status sudah selesai, resi pasti ada)
            $resiFinal = $pesanan->resi ?? $pesanan->nomor_invoice;

            // ==========================================================
            // 4. EKSEKUSI PENYIMPANAN (UPDATE OR CREATE)
            // ==========================================================
            // Menggunakan updateOrCreate agar jika webhook terkirim 2x, data tidak dobel.

            // A. PEMASUKAN (Pendapatan Jasa)
            Keuangan::updateOrCreate(
                [
                    // Kunci Unik (Syarat agar tidak dobel)
                    'nomor_invoice' => $pesanan->nomor_invoice,
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Jasa Pengiriman'
                ],
                [
                    // Data yang diupdate/disimpan
                    'kode_akun'     => '4101', // Akun Pendapatan
                    'tanggal'       => now()->toDateString(), // Tanggal SELESAI (bukan tanggal order)
                    'unit_usaha'    => 'Ekspedisi',
                    'keterangan'    => "Pendapatan Resi: " . $resiFinal . " (" . $pesanan->expedition . ")",
                    'jumlah'        => $ongkirPublish,
                    'updated_at'    => now()
                ]
            );

            // B. PENGELUARAN (Beban/Modal ke Pusat)
            Keuangan::updateOrCreate(
                [
                    // Kunci Unik
                    'nomor_invoice' => $pesanan->nomor_invoice,
                    'jenis'         => 'Pengeluaran',
                    'kategori'      => 'Beban Ekspedisi'
                ],
                [
                    // Data yang diupdate/disimpan
                    'kode_akun'     => '5101', // Akun Beban
                    'tanggal'       => now()->toDateString(),
                    'unit_usaha'    => 'Ekspedisi',
                    'keterangan'    => "Setor Modal ke Pusat: " . $resiFinal . " (Profit " . ($diskonPersen * 100) . "%)",
                    'jumlah'        => $modalReal,
                    'updated_at'    => now()
                ]
            );

            Log::info("Keuangan CASH BASIS Tersimpan: Invoice {$pesanan->nomor_invoice} | Status: {$currentStatus}");

        } catch (Exception $e) {
            Log::error('Keuangan Error:', ['invoice' => $pesanan->nomor_invoice, 'msg' => $e->getMessage()]);
        }
    }

    public function getTripayChannels()
    {
        // 1. Ambil Config dari Database (Sama seperti logika sebelumnya)
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        // Cek Cache dulu biar tidak nembak API terus (Cache 24 jam)
        $cacheKey = 'tripay_channels_list_' . $mode;

        $channels = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 24, function () use ($mode) {
            $apiKey = '';
            $baseUrl = '';

            if ($mode === 'production') {
                $baseUrl = 'https://tripay.co.id/api/merchant/payment-channel';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            } else {
                $baseUrl = 'https://tripay.co.id/api-sandbox/merchant/payment-channel';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            }

            if (empty($apiKey)) return [];

            try {
                $response = \Illuminate\Support\Facades\Http::withToken($apiKey)->get($baseUrl);
                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal ambil channel Tripay: ' . $e->getMessage());
                return [];
            }
            return [];
        });

        return response()->json(['success' => true, 'data' => $channels]);
    }

} // Akhir Class
