<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;   // 🔥 INI DIA TERSANGKANYA, SUDAH DITAMBAHKAN!
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\PesananAutokirim;
use App\Models\AutoKirim; // Tabel lokal kodepos & district_id
use App\Models\Api; // Tabel konfigurasi API

class PesananAutokirimController extends Controller
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
        $this->baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
        $this->token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');
    }

    // ==========================================
    // AREA CUSTOMER (Halaman Form)
    // ==========================================
    public function createCustomer()
    {
        return view('customer.pesanan_autokirim.create');
    }

    // ==========================================
    // AREA ADMIN (Tabel Riwayat)
    // ==========================================
    public function indexAdmin(Request $request)
    {
        $pesanan = PesananAutokirim::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.pesanan_autokirim.index', compact('pesanan'));
    }

    // ==========================================
    // API 1: PENCARIAN ALAMAT (AJAX)
    // ==========================================
    public function searchAddressAjax(Request $request)
    {
        $keyword = $request->query('q');
        if (!$keyword || strlen($keyword) < 3) {
            return response()->json([]);
        }

        // Karena kita sudah menambahkan facade DB, kita bisa pakai Eloquent atau DB murni dengan aman
        $data = AutoKirim::where('district_name', 'like', "%{$keyword}%")
            ->orWhere('regency_name', 'like', "%{$keyword}%")
            ->orWhere('zip', 'like', "%{$keyword}%")
            ->select('district_id', 'district_name', 'regency_name', 'province_name', 'zip')
            ->limit(100)
            ->get();

        return response()->json($data);
    }

    // ==========================================
    // API 2: CEK ONGKIR KE SERVER AUTOKIRIM
    // ==========================================
    public function cekOngkirAjax(Request $request)
    {
        // Langsung tangkap district_id dari frontend
        $origin_id = $request->origin_id;
        $destination_id = $request->destination_id;
        $berat = $request->berat_gram;

        if (empty($origin_id) || empty($destination_id)) {
            return response()->json(['success' => false, 'message' => 'Wilayah asal atau tujuan tidak valid. Pastikan Anda memilih dari dropdown.']);
        }

        // Siapkan Payload Persis Sesuai Dokumentasi Autokirim V1.1
        $payload = [
            'origin_id'      => (int) $origin_id,
            'destination_id' => (int) $destination_id,
            'weight'         => (string) $berat,
            'length'         => $request->panjang_cm ? (int) $request->panjang_cm : 1,
            'width'          => $request->lebar_cm ? (int) $request->lebar_cm : 1,
            'height'         => $request->tinggi_cm ? (int) $request->tinggi_cm : 1,
            'is_sender_pp'   => 1, // Wajib 1 agar pickup point menyesuaikan origin_id pengirim
        ];

        try {
            // Ambil kredensial API
            $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
            $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
            $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

            // LOG LOG: Catat Payload Request Cek Ongkir
            Log::info("LOG LOG: [API AUTOKIRIM - CEK ONGKIR] REQUEST PAYLOAD:", $payload);

            // Tembak Endpoint POST /api/v2/check-price
            $response = Http::timeout(15)
                            ->withToken($token)
                            ->post("{$baseUrl}/api/v2/check-price", $payload);

            $result = $response->json();

            // LOG LOG: Catat Response API Cek Ongkir
            Log::info("LOG LOG: [API AUTOKIRIM - CEK ONGKIR] RESPONSE API:", $result ?? []);

            // Parsing Nested Array dari Autokirim jika Sukses (rc == '00')
            if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {
                $flatOngkir = [];

                foreach ($result['data'] as $courier) {
                    if (!isset($courier['service_detail']) || empty($courier['service_detail'])) {
                        continue;
                    }

                    foreach ($courier['service_detail'] as $service) {
                        $flatOngkir[] = [
                            'kurir'        => $courier['courier_name'],
                            'layanan'      => $service['service_group'] . ' - ' . $service['service'],
                            'kode_layanan' => $service['service_code'],
                            'harga'        => $service['price'],
                            'estimasi'     => $service['duration'],
                            'asuransi_fee' => $service['insurance'],
                        ];
                    }
                }

                return response()->json([
                    'success' => true,
                    'data'    => $flatOngkir
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['rd'] ?? 'Layanan tidak tersedia untuk rute tersebut.'
            ]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: [API AUTOKIRIM - CEK ONGKIR] ERROR EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kendala jaringan saat menghubungi server logistik.']);
        }
    }

    // ==========================================
    // API 3: CREATE ORDER KE AUTOKIRIM & SIMPAN DB
    // ==========================================
    public function store(Request $request)
    {
        // Sesuaikan validasi dengan inputan district_id yang baru
        $request->validate([
            'service_code_terpilih' => 'required',
            'pengirim_nama'         => 'required',
            'pengirim_hp'           => 'required',
            'pengirim_district_id'  => 'required',
            'penerima_nama'         => 'required',
            'penerima_hp'           => 'required',
            'penerima_district_id'  => 'required',
            'berat_gram'            => 'required|numeric',
        ]);

        // Karena frontend sekarang mengirim district_id, kita cari data wilayah aslinya (kodepos dll) dari DB
        $origin = AutoKirim::where('district_id', $request->pengirim_district_id)->first();
        $destination = AutoKirim::where('district_id', $request->penerima_district_id)->first();

        if (!$origin || !$destination) {
            return redirect()->back()->withInput()->with('error', 'Wilayah pengirim atau penerima tidak valid. Mohon pilih dari dropdown yang tersedia.');
        }

        $localOrderId = 'AK-' . strtoupper(Str::random(8));
        $isInsurance = $request->has('asuransi');

        // 1. Susun Payload Order Sesuai Dokumen V1.1
        $payload = [
            'service_code'   => $request->service_code_terpilih,
            'reff_client_id' => $localOrderId, // ID Referensi sistem kita
            'origin_id'      => (int) $origin->district_id,
            'destination_id' => (int) $destination->district_id,
            'weight'         => (string) $request->berat_gram,
            'qty'            => 1, // Fix 1 koli untuk form ini
            'length'         => $request->panjang_cm ? (int) $request->panjang_cm : 1,
            'width'          => $request->lebar_cm ? (int) $request->lebar_cm : 1,
            'height'         => $request->tinggi_cm ? (int) $request->tinggi_cm : 1,
            'description'    => $request->deskripsi_barang,
            'remarks'        => $request->kategori_barang,
            'is_cod'         => false,
            'cod_value'      => 0,
            'is_sender_pp'   => 1, // Pickup dari alamat pengirim
            'is_insurance'   => $isInsurance,
            'from' => [
                'name'    => $request->pengirim_nama,
                'phone'   => $request->pengirim_hp,
                'address' => $request->pengirim_alamat,
            ],
            'to' => [
                'name'    => $request->penerima_nama,
                'phone'   => $request->penerima_hp,
                'address' => $request->penerima_alamat,
            ]
        ];

        // Autokirim mewajibkan field 'price' jika asuransi aktif
        if ($isInsurance) {
            $payload['price'] = (int) $request->nilai_barang;
        }

        try {
            // Ambil Kredensial Terbaru
            $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
            $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
            $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

            // LOG LOG: Catat Payload Request Create Order
            Log::info("LOG LOG: [API AUTOKIRIM - CREATE ORDER] REQUEST PAYLOAD:", $payload);

            // 2. Tembak Endpoint POST /api/order
            $response = Http::timeout(15)
                            ->withToken($token)
                            ->post("{$baseUrl}/api/order", $payload);

            $result = $response->json();

            // LOG LOG: Catat Response API Create Order
            Log::info("LOG LOG: [API AUTOKIRIM - CREATE ORDER] RESPONSE API:", $result ?? []);

            // 3. Jika API Autokirim merespon Sukses (rc = '00')
            if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {

                // Ambil data penting dari Autokirim
                $awbNumber = $result['data']['awb'] ?? null;
                $reff1 = $result['data']['reff_1'] ?? null;

                // 4. Simpan ke Database Lokal menggunakan Eloquent
                PesananAutokirim::create([
                    'user_id'          => auth()->id() ?? null,
                    'order_id'         => $localOrderId,

                    'pengirim_nama'    => $request->pengirim_nama,
                    'pengirim_hp'      => $request->pengirim_hp,
                    'pengirim_alamat'  => $request->pengirim_alamat,
                    'pengirim_kodepos' => $origin->zip, // Kita ambil zip aslinya dari tabel

                    'penerima_nama'    => $request->penerima_nama,
                    'penerima_hp'      => $request->penerima_hp,
                    'penerima_alamat'  => $request->penerima_alamat,
                    'penerima_kodepos' => $destination->zip, // Kita ambil zip aslinya dari tabel

                    'deskripsi_barang' => $request->deskripsi_barang,
                    'kategori_barang'  => $request->kategori_barang,
                    'berat_gram'       => $request->berat_gram,
                    'panjang_cm'       => $request->panjang_cm ?? 0,
                    'lebar_cm'         => $request->lebar_cm ?? 0,
                    'tinggi_cm'        => $request->tinggi_cm ?? 0,
                    'asuransi'         => $isInsurance ? 1 : 0,
                    'nilai_barang'     => $request->nilai_barang ?? 0,

                    'kurir'            => $request->kurir_terpilih,
                    'layanan'          => $request->layanan_terpilih,
                    'ongkir'           => $request->ongkir_terpilih ?? 0,
                    'awb_number'       => $awbNumber,

                    'status'           => 'booking_created'
                ]);

                return redirect()->route('customer.pesanan-autokirim.create')->with('success', "Pesanan Berhasil! Nomor Resi: {$awbNumber}");
            }

            // GAGAL dari sisi server Autokirim
            Log::error("LOG LOG: [API AUTOKIRIM - CREATE ORDER] FAILED FROM SERVER: ", $result ?? []);
            return redirect()->back()->withInput()->with('error', 'Gagal membuat pesanan: ' . ($result['rd'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error("LOG LOG: [API AUTOKIRIM - CREATE ORDER] ERROR EXCEPTION: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem saat menghubungi logistik. Pastikan internet server stabil.');
        }
    }
}
