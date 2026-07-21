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
use App\Helpers\ShippingHelper; // 🔥 1. IMPORT HELPER LOGISTIC

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

    public function createCustomer()
    {
        return view('customer.pesanan_autokirim.create');
    }

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
    // API 2: CEK ONGKIR KE SERVER AUTOKIRIM (+ HELPER LOGO)
    // ==========================================
    public function cekOngkirAjax(Request $request)
    {
        $origin_id = $request->origin_id;
        $destination_id = $request->destination_id;
        $berat = $request->berat_gram;

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
            'is_sender_pp'   => 1,
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

                    // 🔥 2. EKSEKUSI HELPER: Cari spesifikasi logo berdasarkan kode/nama kurir
                    $parsedCourier = ShippingHelper::parseShippingMethod($courier['courier_code'] ?? $courier['courier_name']);

                    foreach ($courier['service_detail'] as $service) {
                        $flatOngkir[] = [
                            'kurir'          => $parsedCourier['courier_name'], // Gunakan nama resmi hasil normalisasi Helper
                            'logo_url'       => $parsedCourier['logo_url'],     // Ambil logo resmi dari Helper
                            'kode_kurir'     => $courier['courier_code'],
                            'layanan'        => $service['service_group'] . ' - ' . $service['service'],
                            'kode_layanan'   => $service['service_code'],
                            'harga'          => (int) $service['price'],
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
    // API 3: CREATE ORDER KE AUTOKIRIM & SIMPAN DB
    // ==========================================
    public function store(Request $request)
    {
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

        $origin = AutoKirim::where('district_id', $request->pengirim_district_id)->first();
        $destination = AutoKirim::where('district_id', $request->penerima_district_id)->first();

        if (!$origin || !$destination) {
            return redirect()->back()->withInput()->with('error', 'Wilayah pengirim atau penerima tidak valid. Mohon pilih dari dropdown yang tersedia.');
        }

        $localOrderId = 'AK-' . strtoupper(Str::random(8));
        $isInsurance = $request->has('asuransi');

        $payload = [
            'service_code'   => $request->service_code_terpilih,
            'reff_client_id' => $localOrderId,
            'origin_id'      => (int) $origin->district_id,
            'destination_id' => (int) $destination->district_id,
            'weight'         => (string) $request->berat_gram,
            'qty'            => 1,
            'length'         => $request->panjang_cm ? (int) $request->panjang_cm : 1,
            'width'          => $request->lebar_cm ? (int) $request->lebar_cm : 1,
            'height'         => $request->tinggi_cm ? (int) $request->tinggi_cm : 1,
            'description'    => $request->deskripsi_barang,
            'remarks'        => $request->kategori_barang,
            'is_cod'         => false,
            'cod_value'      => 0,
            'is_sender_pp'   => 1,
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

        if ($isInsurance) {
            $payload['price'] = (int) $request->nilai_barang;
        }

        try {
            $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
            $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
            $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

            Log::info("LOG: [API AUTOKIRIM - CREATE ORDER] REQUEST:", $payload);

            $response = Http::timeout(15)
                ->withToken($token)
                ->post("{$baseUrl}/api/order", $payload);

            $result = $response->json();
            Log::info("LOG: [API AUTOKIRIM - CREATE ORDER] RESPONSE:", $result ?? []);

            if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {
                $awbNumber = $result['data']['awb'] ?? null;

                PesananAutokirim::create([
                    'user_id'          => auth()->id() ?? null,
                    'order_id'         => $localOrderId,
                    'pengirim_nama'    => $request->pengirim_nama,
                    'pengirim_hp'      => $request->pengirim_hp,
                    'pengirim_alamat'  => $request->pengirim_alamat,
                    'pengirim_kodepos' => $origin->zip,
                    'penerima_nama'    => $request->penerima_nama,
                    'penerima_hp'      => $request->penerima_hp,
                    'penerima_alamat'  => $request->penerima_alamat,
                    'penerima_kodepos' => $destination->zip,
                    'deskripsi_barang' => $request->deskripsi_barang,
                    'kategori_barang'  => $request->kategori_barang,
                    'berat_gram'       => $request->berat_gram,
                    'panjang_cm'       => $request->panjang_cm ? (int) $request->panjang_cm : 1,
                    'lebar_cm'         => $request->lebar_cm ? (int) $request->lebar_cm : 1,
                    'tinggi_cm'        => $request->tinggi_cm ? (int) $request->tinggi_cm : 1,
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

            Log::error("LOG: [API AUTOKIRIM - CREATE ORDER] FAILED FROM SERVER: ", $result ?? []);
            return redirect()->back()->withInput()->with('error', 'Gagal membuat pesanan: ' . ($result['rd'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error("LOG: [API AUTOKIRIM - CREATE ORDER] ERROR: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem saat menghubungi logistik. Pastikan internet server stabil.');
        }
    }
}
