<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\PesananAutokirim;
use App\Models\AutoKirim;
use App\Models\Api;
use App\Helpers\ShippingHelper; // 🔥 Import Helper Logo Ekspedisi

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
    // AREA CUSTOMER: HALAMAN FORM (100% DINAMIS)
    // ==========================================
    public function createCustomer()
    {
        // 1. DINAMIS: Kategori Barang
        $kategoriBarang = [
            'Pakaian / Fashion',
            'Elektronik & Gadget',
            'Dokumen / Surat',
            'Makanan Kering / Herbal',
            'Kosmetik & Kecantikan',
            'Aksesoris & Sparepart',
            'Lainnya'
        ];

        // 2. DINAMIS: Metode Pembayaran (Mudah ditambah tanpa edit Blade)
        $metodePembayaran = [
            [
                'id'          => 'saldo_wallet',
                'nama'        => 'Saldo Akun / Wallet',
                'icon'        => 'fa-solid fa-wallet text-blue-600',
                'deskripsi'   => 'Potong saldo akun otomatis (Proses Instan)'
            ],
            [
                'id'          => 'qris',
                'nama'        => 'QRIS (Gopay, OVO, Dana, ShopeePay)',
                'icon'        => 'fa-solid fa-qrcode text-green-600',
                'deskripsi'   => 'Scan barcode via aplikasi e-wallet / m-banking'
            ],
            [
                'id'          => 'va_bca',
                'nama'        => 'BCA Virtual Account',
                'icon'        => 'fa-solid fa-building-columns text-blue-800',
                'deskripsi'   => 'Konfirmasi otomatis 24/7'
            ],
            [
                'id'          => 'va_mandiri',
                'nama'        => 'Mandiri Virtual Account',
                'icon'        => 'fa-solid fa-building-columns text-yellow-600',
                'deskripsi'   => 'Konfirmasi otomatis 24/7'
            ],
            [
                'id'          => 'va_bri',
                'nama'        => 'BRI Virtual Account (BRIVA)',
                'icon'        => 'fa-solid fa-building-columns text-blue-500',
                'deskripsi'   => 'Konfirmasi otomatis 24/7'
            ]
        ];

        return view('customer.pesanan_autokirim.create', compact('kategoriBarang', 'metodePembayaran'));
    }

    // ==========================================
    // AREA ADMIN: TABEL RIWAYAT
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

        $data = AutoKirim::where('district_name', 'like', "%{$keyword}%")
            ->orWhere('regency_name', 'like', "%{$keyword}%")
            ->orWhere('zip', 'like', "%{$keyword}%")
            ->select('district_id', 'district_name', 'regency_name', 'province_name', 'zip')
            ->limit(100)
            ->get();

        return response()->json($data);
    }

    // ==========================================
    // API 2: CEK ONGKIR (AJAX)
    // ==========================================
    public function cekOngkirAjax(Request $request)
    {
        $origin_id      = $request->origin_id;
        $destination_id = $request->destination_id;
        $berat          = $request->berat_gram;
        $qty            = $request->input('qty', 1);
        $isSenderPp     = $request->input('is_sender_pp', 1);

        if (empty($origin_id) || empty($destination_id)) {
            return response()->json(['success' => false, 'message' => 'Wilayah asal atau tujuan tidak valid.']);
        }

        $payload = [
            'origin_id'      => (int) $origin_id,
            'destination_id' => (int) $destination_id,
            'weight'         => (string) $berat,
            'length'         => $request->panjang_cm ? (int) $request->panjang_cm : 1,
            'width'          => $request->lebar_cm ? (int) $request->lebar_cm : 1,
            'height'         => $request->tinggi_cm ? (int) $request->tinggi_cm : 1,
            'is_sender_pp'   => (int) $isSenderPp,
        ];

        try {
            $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
            $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
            $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

            Log::info("LOG: [API AUTOKIRIM - CEK ONGKIR] REQUEST:", $payload);

            $response = Http::timeout(15)
                ->withToken($token)
                ->post("{$baseUrl}/api/v2/check-price", $payload);

            $result = $response->json();
            Log::info("LOG: [API AUTOKIRIM - CEK ONGKIR] RESPONSE:", $result ?? []);

            if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {
                $flatOngkir = [];

                foreach ($result['data'] as $courier) {
                    if (!isset($courier['service_detail']) || empty($courier['service_detail'])) {
                        continue;
                    }

                    $parsedCourier = ShippingHelper::parseShippingMethod($courier['courier_code'] ?? $courier['courier_name']);

                    foreach ($courier['service_detail'] as $service) {
                        $totalHarga = (int) $service['price'] * (int) $qty;

                        $flatOngkir[] = [
                            'kurir'          => $parsedCourier['courier_name'],
                            'logo_url'       => $parsedCourier['logo_url'],
                            'kode_kurir'     => $courier['courier_code'],
                            'layanan'        => $service['service_group'] . ' - ' . $service['service'],
                            'kode_layanan'   => $service['service_code'],
                            'harga_satuan'   => (int) $service['price'],
                            'harga'          => $totalHarga,
                            'estimasi'       => $service['duration'],
                            'etd'            => $service['etd'] ?? '-',
                            'asuransi_rate'  => $service['insurance'],
                            'is_pickup'      => $service['is_pickup'] ?? false,
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
            Log::error("LOG: [API AUTOKIRIM - CEK ONGKIR] ERROR: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kendala jaringan saat menghubungi server logistik.']);
        }
    }

   // ==========================================
    // API 3: CREATE ORDER (+ AUTO INSERT PICKUP POINT & VALID PRICE)
    // ==========================================
    public function store(Request $request)
    {
        // 1. Validasi Ketat
        $request->validate([
            'service_code_terpilih' => 'required',
            'pengirim_nama'         => 'required|string|max:50',
            'pengirim_hp'           => 'required|string|min:9|max:15',
            'pengirim_district_id'  => 'required',
            'pengirim_alamat'       => 'required|string|min:15',
            'penerima_nama'         => 'required|string|max:50',
            'penerima_hp'           => 'required|string|min:9|max:15',
            'penerima_district_id'  => 'required',
            'penerima_alamat'       => 'required|string|min:15',
            'berat_gram'            => 'required|numeric|min:1',
            'qty'                   => 'required|numeric|min:1',
            'is_sender_pp'          => 'required|in:0,1',
            'metode_pembayaran'     => 'required|string',
        ], [
            'pengirim_alamat.min' => 'Alamat Pengirim terlalu pendek! Wajib menuliskan nama jalan, nomor rumah/gedung, atau RT/RW (Min. 15 karakter).',
            'penerima_alamat.min' => 'Alamat Penerima terlalu pendek! Wajib menuliskan nama jalan, nomor rumah/gedung, atau RT/RW (Min. 15 karakter).',
        ]);

        $origin = AutoKirim::where('district_id', $request->pengirim_district_id)->first();
        $destination = AutoKirim::where('district_id', $request->penerima_district_id)->first();

        if (!$origin || !$destination) {
            return redirect()->back()->withInput()->with('error', 'Wilayah pengirim atau penerima tidak valid.');
        }

        $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
        $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
        $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

        try {
            // =========================================================================
            // 🔥 LANGKAH 1: AUTO-INSERT PICKUP POINT UNTUK MENDAPATKAN KODE (KM12345)
            // =========================================================================
            $pickupPayload = [
                'name'              => (string) trim($request->pengirim_nama),
                'phone'             => (string) trim($request->pengirim_hp),
                'address'           => (string) trim($request->pengirim_alamat),
                'email'             => auth()->user()->email ?? 'customer@tokosancaka.com', // Fallback email
                'longitude'         => "",
                'latitude'          => "",
                'district_id'       => (int) $origin->district_id,
                'is_member_deposit' => false
            ];

            Log::info("LOG: [API AUTOKIRIM - INSERT PICKUP POINT] REQUEST:", $pickupPayload);

            $pickupResponse = Http::timeout(15)
                ->withToken($token)
                ->post("{$baseUrl}/api/pickup-point/insert", $pickupPayload);

            $pickupResult = $pickupResponse->json();
            Log::info("LOG: [API AUTOKIRIM - INSERT PICKUP POINT] RESPONSE:", $pickupResult ?? []);

            // Pastikan kita berhasil mendapatkan pickup_point_code
            if (!$pickupResponse->successful() || empty($pickupResult['data']['pickup_point_code'])) {
                Log::error("LOG: Gagal mendapatkan Pickup Point Code dari Autokirim.");
                return redirect()->back()->withInput()->with('error', 'Gagal mendaftarkan alamat jemput ke server logistik: ' . ($pickupResult['rd'] ?? 'Unknown Error'));
            }

            $pickupPointCode = (string) $pickupResult['data']['pickup_point_code'];
            Log::info("LOG: Berhasil mendapatkan Pickup Point Code: {$pickupPointCode}");

            // =========================================================================
            // 🔥 LANGKAH 2: EKSEKUSI CREATE ORDER DENGAN PARAMETER YANG SUDAH LENGKAP
            // =========================================================================
            $localOrderId = (string) (date('ymdHis') . mt_rand(1000, 9999)); // Full 16 Digit Angka
            $isInsurance  = $request->has('asuransi');
            $isSenderPp   = (int) $request->input('is_sender_pp', 1);

            // Menentukan nominal price (Wajib ada & > 0 sesuai arahan CS Mas Dhani)
            $hargaBarangInput = (int) $request->nilai_barang;
            $finalPrice       = $hargaBarangInput > 0 ? $hargaBarangInput : 10000; // Minimal Rp 10.000 agar tidak reject

            $payload = [
                'service_code'      => (string) $request->service_code_terpilih,
                'reff_client_id'    => $localOrderId,
                'pickup_point_code' => $pickupPointCode, // 🔥 MASUKKAN KODE HASIL DARI LANGKAH 1
                'origin_id'         => (int) $origin->district_id,
                'destination_id'    => (int) $destination->district_id,
                'weight'            => (string) $request->berat_gram,
                'qty'               => (string) $request->input('qty', '1'),
                'length'            => $request->panjang_cm ? (int) $request->panjang_cm : 1,
                'width'             => $request->lebar_cm ? (int) $request->lebar_cm : 1,
                'height'            => $request->tinggi_cm ? (int) $request->tinggi_cm : 1,
                'description'       => (string) $request->deskripsi_barang,
                'remarks'           => (string) $request->kategori_barang,
                'is_cod'            => false,
                'price'             => $finalPrice, // 🔥 WAJIB DIKIRIM WALAUPUN TANPA ASURANSI
                'cod_value'         => 0,
                'is_sender_pp'      => $isSenderPp,
                'is_insurance'      => $isInsurance,
                'from' => [
                    'name'    => (string) trim($request->pengirim_nama),
                    'phone'   => (string) trim($request->pengirim_hp),
                    'address' => (string) trim($request->pengirim_alamat),
                ],
                'to' => [
                    'name'    => (string) trim($request->penerima_nama),
                    'phone'   => (string) trim($request->penerima_hp),
                    'address' => (string) trim($request->penerima_alamat),
                ],
                'commodity'         => ""
            ];

            Log::info("LOG: [API AUTOKIRIM - CREATE ORDER] REQUEST:", $payload);

            $response = Http::timeout(15)
                ->withToken($token)
                ->post("{$baseUrl}/api/order", $payload);

            $result = $response->json();
            Log::info("LOG: [API AUTOKIRIM - CREATE ORDER] RESPONSE:", $result ?? []);

            if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {
                $awbNumber = $result['data']['awb'] ?? null;

                // Simpan ke Database
                PesananAutokirim::create([
                    'user_id'           => auth()->id() ?? null,
                    'order_id'          => $localOrderId,
                    'pengirim_nama'     => $request->pengirim_nama,
                    'pengirim_hp'       => $request->pengirim_hp,
                    'pengirim_alamat'   => $request->pengirim_alamat,
                    'pengirim_kodepos'  => $origin->zip,
                    'penerima_nama'     => $request->penerima_nama,
                    'penerima_hp'       => $request->penerima_hp,
                    'penerima_alamat'   => $request->penerima_alamat,
                    'penerima_kodepos'  => $destination->zip,
                    'deskripsi_barang'  => $request->deskripsi_barang,
                    'kategori_barang'   => $request->kategori_barang,
                    'berat_gram'        => $request->berat_gram,
                    'panjang_cm'        => $request->panjang_cm ? (int) $request->panjang_cm : 1,
                    'lebar_cm'          => $request->lebar_cm ? (int) $request->lebar_cm : 1,
                    'tinggi_cm'         => $request->tinggi_cm ? (int) $request->tinggi_cm : 1,
                    'asuransi'          => $isInsurance ? 1 : 0,
                    'nilai_barang'      => $finalPrice,
                    'kurir'             => $request->kurir_terpilih,
                    'layanan'           => $request->layanan_terpilih,
                    'ongkir'            => $request->ongkir_terpilih ?? 0,
                    'awb_number'        => $awbNumber,
                    'metode_pembayaran' => $request->metode_pembayaran,
                    'status'            => 'booking_created'
                ]);

                return redirect()->route('customer.pesanan-autokirim.create')->with('success', "Pesanan Berhasil! Nomor Resi: {$awbNumber} (Metode: {$request->metode_pembayaran})");
            }

            Log::error("LOG: [API AUTOKIRIM - CREATE ORDER] FAILED FROM SERVER: ", $result ?? []);
            return redirect()->back()->withInput()->with('error', 'Gagal membuat pesanan: ' . ($result['rd'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error("LOG: [API AUTOKIRIM - EXCEPTION] ERROR: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem saat menghubungi logistik. Pastikan internet server stabil.');
        }
    }
}
