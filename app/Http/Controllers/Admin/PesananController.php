<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Kontak;
use App\Models\Ekspedisi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Exports\PesanansExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

// Import Service KiriminAja dan Exception
use App\Services\KiriminAjaService;
use Exception;
use Illuminate\Validation\ValidationException;

class PesananController extends Controller
{
    /**
     * Menampilkan daftar semua pesanan dengan fitur pencarian dan filter.
     */
    public function index(Request $request)
    {
        Pesanan::where('status_pesanan', 'baru')->where('telah_dilihat', false)->update(['telah_dilihat' => true]);
        
        $query = Pesanan::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('nama_pembeli', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('telepon_pembeli', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status_pesanan', $request->input('status'));
        }

        $orders = $query->latest('tanggal_pesanan')->paginate(10); 
        
        return view('admin.pesanan.index', compact('orders'));
    }

    /**
     * Menampilkan form dan mengirim data pelanggan untuk dropdown.
     */
    public function create()
    {
        $customers = User::where('role', 'Pelanggan')->orderBy('nama_lengkap')->get();
        return view('admin.pesanan.create', compact('customers'));
    }

    /**
     * Menyimpan pesanan baru, memotong saldo jika perlu, dan mendaftarkan pengiriman ke KiriminAja.
     */
    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        DB::beginTransaction();
        try {
            // Validasi input, ditambahkan field ID alamat untuk KiriminAja
            $validatedData = $this->_validateOrderRequest($request);

            // Parsing detail ekspedisi dari value yang dikirim (contoh: JNE-REG-15000-500-0)
            list($serviceGroup, $courier, $service, $shipping_cost, $ansuransi_fee, $cod_fee) = array_pad(explode('-', $validatedData['expedition']), 6, 0);

            $shipping_cost = (int) $shipping_cost;
            $ansuransi_fee = (int) $ansuransi_fee;
            $cod_fee = (int) $cod_fee;
            
            // Hitung total biaya berdasarkan item, ongkir, dan asuransi
            $base_total = (int)$validatedData['item_price'] + $shipping_cost;
            if ($validatedData['ansuransi'] == 'iya') {
                $base_total += $ansuransi_fee;
            }
            
            $total_paid = $base_total;
            $cod_value = 0;

            // Sesuaikan total bayar dan nilai COD jika metode pembayaran adalah COD
            if ($validatedData['payment_method'] === 'COD') {
                $total_paid = $shipping_cost + $cod_fee;
                $cod_value = (int)$validatedData['item_price'];
            } elseif ($validatedData['payment_method'] === 'CODBARANG') {
                $total_paid = $base_total + $cod_fee;
                $cod_value = $total_paid;
            }

            // Logika Potong Saldo (jika pembayaran menggunakan saldo)
            if ($validatedData['payment_method'] === 'Potong Saldo') {
                $customer = User::find($validatedData['id_pengguna_pembeli']);
                if (!$customer || $customer->saldo < $total_paid) {
                    throw new Exception('Saldo pelanggan tidak valid atau tidak mencukupi.');
                }
                $customer->saldo -= $total_paid;
                $customer->save();
            }

            // Siapkan data pesanan untuk disimpan ke database
            $pesananData = $this->_preparePesananData($validatedData, $total_paid, $request->ip(), $request->userAgent());
            $pesanan = Pesanan::create($pesananData);

            // Buat pesanan di sistem KiriminAja
            $kiriminResponse = $this->_createKiriminAjaOrder($request, $pesanan, $kirimaja, $cod_value);

            // Cek respons dari KiriminAja
            if (isset($kiriminResponse['status']) && $kiriminResponse['status'] !== true) {
                // Coba ambil pesan error yang lebih detail dari respons API
                $errorDetails = '';
                if (!empty($kiriminResponse['errors']) && is_array($kiriminResponse['errors'])) {
                    $errorMessages = array_column($kiriminResponse['errors'], 'text');
                    $errorDetails = ': ' . implode(', ', $errorMessages);
                }
                $errorMessage = ($kiriminResponse['text'] ?? 'Gagal membuat order di sistem ekspedisi') . $errorDetails;
                throw new Exception($errorMessage);
            }

            // Jika berhasil, update pesanan dengan resi asli dan status baru
            $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? null;
            $pesanan->status = 'Menunggu Pickup';
            $pesanan->status_pesanan = 'Menunggu Pickup';
            $pesanan->save();

            // Simpan kontak jika dicentang
            $this->_saveContacts($request);

            DB::commit();

            return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan berhasil dibuat dengan Resi: ' . $pesanan->resi);

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Helper untuk validasi request data pesanan.
     */
    private function _validateOrderRequest(Request $request): array
    {
        return $request->validate([
            'id_pengguna_pembeli' => 'required_if:payment_method,Potong Saldo|nullable|exists:users,id',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'sender_district_id' => 'required|integer',
            'sender_subdistrict_id' => 'required|integer',
            'sender_postal_code' => 'required|string|max:10',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'receiver_district_id' => 'required|integer',
            'receiver_subdistrict_id' => 'required|integer',
            'receiver_postal_code' => 'required|string|max:10',
            'service_type' => 'required|string',
            'expedition' => 'required|string',
            'payment_method' => 'required|string',
            'item_description' => 'required|string',
            'weight' => 'required|numeric|min:1',
            'item_price' => 'required|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'item_type' => 'required|integer',
            'ansuransi' => 'required|string|in:iya,tidak',
            'save_sender' => 'nullable',
            'save_receiver' => 'nullable',
        ]);
    }
    
    /**
     * Helper untuk menyiapkan data pesanan sebelum disimpan.
     */
    private function _preparePesananData(array $validatedData, int $total, string $ip, string $userAgent): array
    {
        do { $nomorInvoice = 'SCK-SANCAKA-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)); } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

        $sender_address_parts = explode(', ', $validatedData['sender_address']);
        $receiver_address_parts = explode(', ', $validatedData['receiver_address']);

        return [
            'id_pengguna_pembeli' => $validatedData['id_pengguna_pembeli'] ?? null,
            'nomor_invoice' => $nomorInvoice,
            'sender_name' => $validatedData['sender_name'],
            'sender_phone' => $validatedData['sender_phone'],
            'sender_address' => $validatedData['sender_address'],
            'sender_village' => trim($sender_address_parts[0] ?? ''),
            'sender_district' => trim($sender_address_parts[1] ?? ''),
            'sender_regency' => trim($sender_address_parts[2] ?? ''),
            'sender_province' => trim($sender_address_parts[3] ?? ''),
            'sender_postal_code' => $validatedData['sender_postal_code'],
            'receiver_name' => $validatedData['receiver_name'],
            'nama_pembeli' => $validatedData['receiver_name'],
            'receiver_phone' => $validatedData['receiver_phone'],
            'telepon_pembeli' => $validatedData['receiver_phone'],
            'receiver_address' => $validatedData['receiver_address'],
            'alamat_pengiriman' => $validatedData['receiver_address'],
            'receiver_village' => trim($receiver_address_parts[0] ?? ''),
            'receiver_district' => trim($receiver_address_parts[1] ?? ''),
            'receiver_regency' => trim($receiver_address_parts[2] ?? ''),
            'receiver_province' => trim($receiver_address_parts[3] ?? ''),
            'receiver_postal_code' => $validatedData['receiver_postal_code'],
            'tujuan' => trim($receiver_address_parts[2] ?? ''),
            'item_description' => $validatedData['item_description'],
            'total_harga_barang' => $validatedData['item_price'],
            'weight' => $validatedData['weight'],
            'length' => $validatedData['length'] ?? 1,
            'width' => $validatedData['width'] ?? 1,
            'height' => $validatedData['height'] ?? 1,
            'service_type' => $validatedData['service_type'],
            'expedition' => $validatedData['expedition'],
            'payment_method' => $validatedData['payment_method'],
            'item_type' => $validatedData['item_type'],
            'price' => $total,
            'status' => 'Menunggu Pembayaran',
            'status_pesanan' => 'Menunggu Pembayaran',
            'tanggal_pesanan' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ];
    }
    
    /**
     * Helper untuk menyimpan kontak ke database jika dicentang.
     */
    private function _saveContacts(Request $request): void
    {
        $contactData = function ($prefix, $request) {
            $address = $request->input("{$prefix}_address");
            $parts = array_map('trim', explode(',', $address));
            return [
                'nama' => $request->input("{$prefix}_name"),
                'alamat' => $address,
                'village' => $parts[0] ?? null,
                'district' => $parts[1] ?? null,
                'regency' => $parts[2] ?? null,
                'province' => $parts[3] ?? null,
                'postal_code' => $request->input("{$prefix}_postal_code"),
            ];
        };
        if ($request->has('save_sender')) {
            Kontak::updateOrCreate(
                ['no_hp' => $request->sender_phone],
                array_merge($contactData('sender', $request), ['tipe' => 'Pengirim'])
            );
        }
        if ($request->has('save_receiver')) {
            Kontak::updateOrCreate(
                ['no_hp' => $request->receiver_phone],
                array_merge($contactData('receiver', $request), ['tipe' => 'Penerima'])
            );
        }
    }
    
    /**
     * Helper untuk membuat pesanan di KiriminAja.
     */
    private function _createKiriminAjaOrder(Request $request, Pesanan $pesanan, KiriminAjaService $kirimaja, int $cod_value): array
    {
        list(,, $service_type, $shipping_cost) = array_pad(explode('-', $request->expedition), 4, null);
        list($serviceGroup, $courier) = array_pad(explode('-', $request->expedition), 2, null);

        if (in_array($serviceGroup, ['instant', 'sameday'])) {
            throw new Exception("Layanan Instant/Sameday belum didukung di halaman admin.");
        }
        
        // --- PERBAIKAN LOGIKA JADWAL PICKUP ---
        // Tentukan jadwal pickup sebelum membuat payload.
        $pickup_schedule = null;
        $schedule_response = $kirimaja->getSchedules();

        // Cek jika API call berhasil dan ada jadwal tersedia.
        if (!empty($schedule_response['status']) && $schedule_response['status'] === true && !empty($schedule_response['schedules'])) {
            // Gunakan jadwal pertama yang tersedia.
            $pickup_schedule = $schedule_response['schedules'][0]['clock'];
        }

        // Jika tidak ada jadwal dari API, buat jadwal fallback untuk hari kerja berikutnya pukul 10:00.
        if (is_null($pickup_schedule)) {
            $pickup_time = now()->addDay();
            // Jika besok adalah hari Minggu, atur pickup untuk hari Senin.
            if ($pickup_time->isSunday()) {
                $pickup_time->addDay();
            }
            $pickup_schedule = $pickup_time->setTime(10, 0, 0)->format('Y-m-d H:i:s');
        }
        // --- AKHIR PERBAIKAN ---
        
        $payload = [
            'address' => $request->sender_address,
            'phone' => $request->sender_phone,
            'name' => $request->sender_name,
            'kecamatan_id' => $request->sender_district_id,
            'kelurahan_id' => $request->sender_subdistrict_id,
            'zipcode' => $request->sender_postal_code,
            'schedule' => $pickup_schedule, // Jadwal sekarang wajib diisi dengan nilai valid atau fallback
            'platform_name' => 'TOKOSANCAKA.COM',
            'packages' => [[
                'order_id' => $pesanan->nomor_invoice,
                'item_name' => $request->item_description,
                'package_type_id' => (int)$request->item_type,
                'destination_name' => $request->receiver_name,
                'destination_phone' => $request->receiver_phone,
                'destination_address' => $request->receiver_address,
                'destination_kecamatan_id' => $request->receiver_district_id,
                'destination_kelurahan_id' => $request->receiver_subdistrict_id,
                'destination_zipcode' => $request->receiver_postal_code,
                'weight' => (int)$request->weight,
                'width' => (int)($request->width ?? 1),
                'height' => (int)($request->height ?? 1),
                'length' => (int)($request->length ?? 1),
                'item_value' => (int)$request->item_price,
                'shipping_cost' => (int)$shipping_cost,
                'service' => $courier,
                'service_type' => $service_type,
                'insurance_amount' => ($request->ansuransi == 'iya') ? (int)$request->item_price : 0,
                'cod' => $cod_value
            ]]
        ];
        
        return $kirimaja->createExpressOrder($payload);
    }

    /**
     * Menampilkan detail satu pesanan.
     */
    public function show($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.show', compact('pesanan'));
    }

    /**
     * Menampilkan form untuk mengedit pesanan.
     */
    public function edit($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.edit', compact('pesanan'));
    }

    /**
     * Menampilkan form untuk scan resi aktual.
     */
    public function showScanForm($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        $ekspedisiList = Ekspedisi::all();
        return view('admin.pesanan.scan-aktual', compact('pesanan', 'ekspedisiList'));
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
            'service_type' => 'required|string',
            'expedition' => 'required|string',
            'payment_method' => 'required|string',
            'item_description' => 'required|string',
            'weight' => 'required|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'kelengkapan' => 'nullable|array',
        ]);
        
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        $validatedData['kelengkapan'] = $request->has('kelengkapan') ? json_encode($request->input('kelengkapan')) : $pesanan->kelengkapan;
        $pesanan->update($validatedData);
        
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil diperbarui.');
    }

    /**
     * Menghapus pesanan dari database.
     */
    public function destroy($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        $pesanan->delete();
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil dihapus.');
    }

    /**
     * Memperbarui status pesanan secara manual.
     */
    public function updateStatus(Request $request, $resi)
    {
        $request->validate(['status' => 'required|string|in:Terkirim,Batal,Diproses,Menunggu Pickup']);
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        $pesanan->update(['status_pesanan' => $request->status]);
        
        return redirect()->back()->with('success', 'Status pesanan ' . $resi . ' berhasil diubah menjadi "' . $request->status . '".');
    }

    /**
     * Memperbarui resi aktual dan total ongkir.
     */
    public function updateResiAktual(Request $request, $resi)
    {
        $request->validate([
            'resi_aktual' => 'required|string|max:255',
            'jasa_ekspedisi_aktual' => 'required|string|max:255',
            'total_harga_barang' => 'required|numeric',
        ]);

        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();

        $pesanan->resi_aktual = $request->input('resi_aktual');
        $pesanan->jasa_ekspedisi_aktual = $request->input('jasa_ekspedisi_aktual');
        $pesanan->total_harga_barang = $request->input('total_harga_barang');
        $pesanan->status_pesanan = 'Diproses';
        $pesanan->save();

        return redirect()->route('admin.pesanan.index')->with('success', 'Resi aktual berhasil diperbarui.');
    }

    /**
     * Menampilkan halaman cetak untuk resi standar.
     */
    public function cetakResi($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.cetak', compact('pesanan'));
    }

    /**
     * Menampilkan halaman cetak untuk resi thermal.
     */
    public function cetakResiThermal($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }
    
    /**
     * Menghitung jumlah pesanan baru untuk notifikasi.
     */
    public function count()
    {
        $count = Pesanan::where('status_pesanan', 'baru')->where('telah_dilihat', false)->count();
        return response()->json(['count' => $count]);
    }
    
    /**
     * Menampilkan riwayat pesanan yang sudah di-scan.
     */
    public function riwayatScan(Request $request)
    {
        $query = Pesanan::whereNotNull('resi_aktual')->latest('updated_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")
                  ->orWhere('resi_aktual', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('nama_pembeli', 'like', "%{$search}%");
            });
        }
        
        $scannedOrders = $query->paginate(15)->withQueryString();
        
        return view('admin.pesanan.riwayat-scan', compact('scannedOrders'));
    }
}

