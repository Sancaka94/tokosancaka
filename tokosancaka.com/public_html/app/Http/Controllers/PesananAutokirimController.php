<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Models\PesananAutokirim;
use App\Models\AutoKirim;
use App\Models\Api;
use App\Models\User;
use App\Helpers\ShippingHelper;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Cache; // Wajib untuk fitur Idempotency
use App\Services\KiriminAjaService;
use Exception;

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
        return $this->_renderCreateForm('customer');
    }

    public function createAdmin()
    {
        return $this->_renderCreateForm('admin');
    }

    private function _renderCreateForm($roleType)
    {
        $kategoriBarang = [
            'Pakaian / Fashion', 'Elektronik & Gadget', 'Dokumen / Surat',
            'Makanan Kering / Herbal', 'Kosmetik & Kecantikan', 'Aksesoris & Sparepart', 'Lainnya'
        ];

        // 1. Metode Pembayaran Dasar (Internal & DANA/DOKU)
        $metodePembayaran = [
            [
                'id'          => 'potong_saldo',
                'nama'        => 'Potong Saldo Akun / Wallet',
                'icon'        => 'fa-solid fa-wallet text-blue-600',
                'deskripsi'   => 'Potong saldo otomatis dari akun Anda (Proses Instan)'
            ],
            [
                'id'          => 'dana_binding',
                'nama'        => 'DANA (One-Click Binding)',
                'icon'        => 'fa-solid fa-mobile-screen-button text-blue-500',
                'deskripsi'   => 'Bayar instan dengan akun DANA yang sudah terhubung'
            ],
            [
                'id'          => 'dana_pg',
                'nama'        => 'DANA Payment Gateway',
                'icon'        => 'fa-solid fa-qrcode text-blue-400',
                'deskripsi'   => 'Redirect ke aplikasi atau web DANA untuk pembayaran'
            ],
            [
                'id'          => 'doku_jokul',
                'nama'        => 'DOKU Payment Gateway',
                'icon'        => 'fa-solid fa-shield-halved text-red-600',
                'deskripsi'   => 'Bayar via DOKU (Kartu Kredit, VA, Retail, E-Wallet)'
            ]
        ];

        // --- TAMBAHAN KODE: METODE CASH KHUSUS ADMIN ---
        if ($roleType === 'admin') {
            array_unshift($metodePembayaran, [
                'id'          => 'cash',
                'nama'        => 'Cash / Tunai',
                'icon'        => 'fa-solid fa-money-bill-wave text-emerald-600',
                'deskripsi'   => 'Terima tunai dari pelanggan. Resi (AWB) langsung terbit tanpa potong saldo.'
            ]);
        }
        // ------------------------------------------------

        // 2. MENGAMBIL METODE TRIPAY SECARA DINAMIS DARI API
        $currentMode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $cacheKey = 'tripay_channels_list_' . $currentMode;

        $tripayChannels = json_decode(Redis::get($cacheKey), true);

        if (!$tripayChannels) {
            if ($currentMode === 'production') {
                $baseUrl = 'https://tripay.co.id/api';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            } else {
                $baseUrl = 'https://tripay.co.id/api-sandbox';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            }

            try {
                $response = \Illuminate\Support\Facades\Http::withToken($apiKey)->timeout(10)->get($baseUrl . '/merchant/payment-channel');

                if ($response->successful()) {
                    $tripayChannels = $response->json()['data'] ?? [];
                    Redis::setex($cacheKey, 86400, json_encode($tripayChannels));
                } else {
                    $tripayChannels = [];
                }
            } catch (\Exception $e) {
                $tripayChannels = [];
            }
        }

        // 3. Gabungkan Data Tripay ke List Metode Pembayaran
        foreach ($tripayChannels as $channel) {
            if ($channel['active']) {
                $metodePembayaran[] = [
                    'id'        => 'tripay_' . $channel['code'],
                    'nama'      => $channel['name'],
                    'icon'      => $channel['icon_url'],
                    'deskripsi' => 'Biaya Admin Tripay: Rp ' . number_format($channel['total_fee']['flat'] ?? 0, 0, ',', '.')
                ];
            }
        }

        if ($roleType === 'admin') {
            return view('admin.pesanan-autokirim.create', compact('kategoriBarang', 'metodePembayaran'));
        }

        return view('customer.pesanan_autokirim.create', compact('kategoriBarang', 'metodePembayaran'));
    }

    public function indexAdmin(Request $request)
    {
        $query = PesananAutokirim::with('user');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('awb_number', 'like', "%{$search}%")
                  ->orWhere('pengirim_nama', 'like', "%{$search}%")
                  ->orWhere('penerima_nama', 'like', "%{$search}%")
                  ->orWhere('pengirim_hp', 'like', "%{$search}%")
                  ->orWhere('penerima_hp', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('store_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_range')) {
            $dates = explode(' to ', str_replace([' - ', ' s.d. '], ' to ', $request->date_range));
            if (count($dates) >= 2) {
                $query->whereBetween('created_at', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
            } elseif (count($dates) == 1) {
                $query->whereDate('created_at', trim($dates[0]));
            }
        }

        $totalTransaksi = (clone $query)->count();
        $totalOngkir    = (clone $query)->sum('ongkir');

        $statusPending  = ['batal', 'gagal', 'menunggu_pembayaran'];
        $querySukses    = (clone $query)->whereNotIn('status', $statusPending);

        $stats = [
            'total_berhasil' => (clone $querySukses)->count(),
            'total_pending'  => (clone $query)->whereIn('status', $statusPending)->count(),
            'cashback_pusat' => (clone $querySukses)->sum('total_cashback'),
            'komisi_agen'    => (clone $querySukses)->sum('komisi_agen'),
            'laba_sancaka'   => (clone $querySukses)->sum('laba_sistem'),
        ];

        $pesanan = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.pesanan_autokirim.index', compact(
            'pesanan', 'totalTransaksi', 'totalOngkir', 'stats'
        ));
    }

    private function hitungProfit($pesanan, $rates)
    {
        // 1. Standarisasi input dari request (Huruf kecil & ubah underscore/strip jadi spasi)
        $kurirInput = strtolower(trim($pesanan->kurir ?? ''));
        $layananInput = str_replace(['_', '-'], ' ', strtolower(trim($pesanan->layanan ?? '')));

        // 2. KAMUS ALIAS LENGKAP (API ke Database)
        // Kiri: Format dari API logistik | Kanan: Format di tabel data_auto_kirims
        $aliasMap = [
            // J&T Express & Cargo
            'jnt ez'             => 'jnt reg',
            'jnt eco'            => 'jnt eco',
            'jnt jnd'            => 'jnt jnd',
            'jnt jsd'            => 'jnt jsd',
            'jntcargo ft'        => 'jntcargo ft',

            // ID Express
            'idx std'            => 'idx reguler',
            'idx truck'          => 'idx truck',
            'idx lite'           => 'idx lite',

            // AnterAja
            'anteraja reguler'   => 'anteraja reg',
            'anteraja nextday'   => 'anteraja nextday',
            'anteraja sameday'   => 'anteraja sameday',

            // Lion Parcel
            'lionparcel reguler' => 'lionparcel regpack',
            'lionparcel bigpack' => 'lionparcel bigpack',
            'lionparcel onepack' => 'lionparcel onepack',
            'lionparcel jagopack'=> 'lionparcel jagopack',

            // SAPX (SAP Express)
            'sap udrreg'         => 'sap reguler',
            'sap udrons'         => 'sap oneday',
            'sap lite'           => 'sap lite',
            'sap regulerdarat'   => 'sap regulerdarat',

            // SiCepat Express
            'sicepat reg'        => 'sicepat reguler', // Jaga-jaga jika API kirim 'reg'
            'sicepat best'       => 'sicepat best',
            'sicepat gokil'      => 'sicepat gokil',

            // Ninja Express
            'ninja standard'     => 'ninja standard',
            'ninja cargo'        => 'ninja cargo',

            // JNE Express
            'jne reg'            => 'jne reg',
            'jne oke'            => 'jne oke',
            'jne yes'            => 'jne yes',
            'jne jtr'            => 'jne jtr',
            'jne ctc'            => 'jne ctc',
            'jne ctcjtr'         => 'jne ctcjtr',
            'jne ctcyes'         => 'jne ctcyes',

            // POS AJA
            'pos reguler'        => 'pos reguler',
            'pos nextday'        => 'pos nextday',
            'pos cargo'          => 'pos cargo',

            // Paxel
            'paxel sameday'      => 'paxel sameday',
            'paxel oneday'       => 'paxel oneday',
            'paxel reguler'      => 'paxel reguler',
            'paxel big'          => 'paxel big',
            'paxel amplop'       => 'paxel amplop',

            // SPX & LEX
            'spx standard'       => 'spx standard',
            'lex standard'       => 'lex standard',

            // Sentral Cargo
            'sc del'             => 'sc del',
            'sc dnel'            => 'sc dnel',
            'sc uel'             => 'sc uel',
            'sc unel'            => 'sc unel',
        ];

        // Terapkan kamus alias ke layanan yang masuk dari API
        foreach ($aliasMap as $apiTerm => $dbTerm) {
            // Kita replace jika string dari API mengandung key dari dictionary kita
            if (str_contains($layananInput, trim($apiTerm))) {
                $layananInput = str_replace(trim($apiTerm), trim($dbTerm), $layananInput);
                break; // Stop loop jika sudah ketemu match-nya
            }
        }

        // Cek mode COD dari input user
        $isCod = in_array(strtolower(trim($pesanan->metode_pembayaran)), ['cod', 'codbarang', 'cod_barang', 'cod_ongkir']);

        $bestMatch = null;
        $highestScore = -1;

        foreach ($rates as $rate) {
            $dbBrand = strtolower(trim($rate->brand_logistik ?? ''));
            $dbService = str_replace(['_', '-'], ' ', strtolower(trim($rate->service ?? '')));

            $score = 0;
            $isBrandMatched = false;

            // --- 3. PENCARIAN BRAND (KURIR) ---
            if ($dbBrand !== '' && (str_contains($kurirInput, $dbBrand) || str_contains($dbBrand, $kurirInput))) {
                $score += 20;
                $isBrandMatched = true;
            } elseif ($dbBrand !== '' && similar_text($kurirInput, $dbBrand, $perc) && $perc > 75) {
                $score += 15;
                $isBrandMatched = true;
            }

            // Skip iterasi jika nama kurir/brand tidak cocok
            if (!$isBrandMatched) continue;

            // --- 4. PENCARIAN SERVICE (LAYANAN) ---
            // Pencocokan langsung (Contoh: "jnt reg" vs "jnt cod reg")
            if (str_contains($dbService, $layananInput) || str_contains($layananInput, $dbService)) {
                $score += 20;
            } else {
                // Pencocokan per kata (Fuzzy logic)
                $layananKeys = explode(' ', $layananInput);
                foreach ($layananKeys as $k) {
                    if (strlen($k) > 2 && str_contains($dbService, $k)) {
                        $score += 5; // Skor per kata yang cocok
                    }
                }
            }

            // --- 5. FILTER REGULER VS COD ---
            $isDbServiceCod = str_contains($dbService, 'cod');

            if ($isCod && $isDbServiceCod) {
                $score += 50;
            } elseif (!$isCod && !$isDbServiceCod) {
                $score += 50;
            } elseif ($isCod !== $isDbServiceCod) {
                // Penalti ekstrim jika User Request COD tapi meloop baris Reguler (atau sebaliknya)
                $score -= 100;
            }

            // Tetapkan pemenang skor tertinggi
            if ($score > $highestScore && $score > 0) {
                $highestScore = $score;
                $bestMatch = $rate;
            }
        }

        // --- 6. KALKULASI CASHBACK ---
        $persenCashbackPusat = $bestMatch ? floatval($bestMatch->cashback ?? 0) : 0;

        $user = auth()->user();
        $agenFeePercentage = $user ? floatval($user->fee_autokirim ?? 40) : 40;

        $ongkir = floatval($pesanan->ongkir ?? 0);
        $totalCashback = $ongkir * ($persenCashbackPusat / 100);
        $komisiAgen    = $totalCashback * ($agenFeePercentage / 100);
        $labaSancaka   = $totalCashback - $komisiAgen;

        return (object)[
            'total_cashback'  => $totalCashback,
            'komisi_agen'     => $komisiAgen,
            'laba_sancaka'    => $labaSancaka,
            'persen_cashback' => $persenCashbackPusat,
            'matched_service' => $bestMatch->service ?? 'Unmatched',
            'matched_brand'   => $bestMatch->brand_logistik ?? 'Unmatched',
            'debug_score'     => $highestScore
        ];
    }

    public function indexCustomer(Request $request)
    {
        $query = PesananAutokirim::with('user')->where('user_id', auth()->id());

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('awb_number', 'like', "%{$search}%")
                  ->orWhere('pengirim_nama', 'like', "%{$search}%")
                  ->orWhere('penerima_nama', 'like', "%{$search}%")
                  ->orWhere('pengirim_hp', 'like', "%{$search}%")
                  ->orWhere('penerima_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('date_range')) {
            $dates = explode(' to ', str_replace([' - ', ' s.d. '], ' to ', $request->date_range));
            if (count($dates) >= 2) {
                $query->whereBetween('created_at', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
            } elseif (count($dates) == 1) {
                $query->whereDate('created_at', trim($dates[0]));
            }
        }

        $totalTransaksi = (clone $query)->count();
        $totalOngkir    = (clone $query)->sum('ongkir');

        $now = now();
        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay()->startOfDay();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();

        $statusPending = ['batal', 'gagal', 'menunggu_pembayaran'];
        $querySukses   = (clone $query)->whereNotIn('status', $statusPending);

        $komisiTotal        = (clone $querySukses)->sum('komisi_agen');
        $komisiHariIni      = (clone $querySukses)->where('created_at', '>=', $today)->sum('komisi_agen');
        $komisiKemarin      = (clone $querySukses)->whereBetween('created_at', [$yesterday, $today->copy()->subSecond()])->sum('komisi_agen');
        $komisiBulanIni     = (clone $querySukses)->where('created_at', '>=', $thisMonth)->sum('komisi_agen');
        $komisiBulanKemarin = (clone $querySukses)->whereBetween('created_at', [$lastMonth, $thisMonth->copy()->subSecond()])->sum('komisi_agen');

        $komisi = [
            'total'         => $komisiTotal,
            'hari_ini'      => $komisiHariIni,
            'kemarin'       => $komisiKemarin,
            'bulan_ini'     => $komisiBulanIni,
            'bulan_kemarin' => $komisiBulanKemarin
        ];

        $growthHarian = $komisi['kemarin'] > 0 ? (($komisi['hari_ini'] - $komisi['kemarin']) / $komisi['kemarin']) * 100 : ($komisi['hari_ini'] > 0 ? 100 : 0);
        $growthBulanan = $komisi['bulan_kemarin'] > 0 ? (($komisi['bulan_ini'] - $komisi['bulan_kemarin']) / $komisi['bulan_kemarin']) * 100 : ($komisi['bulan_ini'] > 0 ? 100 : 0);

        $pesanan = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('customer.pesanan_autokirim.index', compact(
            'pesanan', 'totalTransaksi', 'totalOngkir', 'komisi', 'growthHarian', 'growthBulanan'
        ));
    }

    /**
     * API KiriminAja Address Search + JOIN dengan DB Autokirim
     */
    public function searchAddressAjax(Request $request, \App\Services\KiriminAjaService $kiriminAja)
    {
        $query = $request->get('q');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            // 1. Tembak API KiriminAja
            $apiResponse = $kiriminAja->searchAddress($query);
            $mappedData = [];

            $searchDistricts = [];
            $searchZips = [];

            // 2A. Jika API KiriminAja MENGEMBALIKAN DATA
            if (isset($apiResponse['data']) && !empty($apiResponse['data'])) {
                foreach ($apiResponse['data'] as $item) {
                    $addressParts = array_map('trim', explode(',', $item['full_address'] ?? ''));

                    $village  = $addressParts[0] ?? 'N/A';
                    $district = $addressParts[1] ?? 'N/A';
                    $regency  = $addressParts[2] ?? 'N/A';
                    $province = $addressParts[3] ?? 'N/A';
                    $zip      = $addressParts[4] ?? 'N/A';

                    $cleanRegency = str_replace(['KABUPATEN ', 'KOTA '], '', strtoupper($regency));

                    $mappedData[] = [
                        'province'             => $province,
                        'regency'              => $regency,
                        'clean_regency'        => $cleanRegency,
                        'district'             => $district,
                        'village'              => $village,
                        'postal_code'          => $zip,
                        'full_address_display' => $item['full_address'] ?? 'Alamat Tidak Terstruktur',
                    ];

                    $searchDistricts[] = $district;
                    // Kumpulkan kodepos untuk pencocokan yang lebih akurat
                    if ($zip !== 'N/A' && is_numeric($zip)) {
                        $searchZips[] = $zip;
                    }
                }
            }
            // 2B. SMART FALLBACK: Jika API KiriminAja KOSONG & User mengetik Kodepos (Angka)
            else {
                if (is_numeric($query) && strlen($query) == 5) {
                    $localSearch = \Illuminate\Support\Facades\DB::table('auto_kirims')
                        ->where('zip', $query)
                        ->limit(10)
                        ->get();

                    foreach($localSearch as $loc) {
                         $mappedData[] = [
                            'province'             => $loc->province_name,
                            'regency'              => $loc->regency_name,
                            'clean_regency'        => strtoupper($loc->regency_name),
                            'district'             => $loc->district_name,
                            'village'              => '', // Dari DB lokal biasanya tidak sedetail kelurahan
                            'postal_code'          => $loc->zip,
                            'district_id'          => $loc->district_id, // Langsung dapat ID
                            'full_address_display' => "{$loc->district_name}, {$loc->regency_name}, {$loc->province_name}, {$loc->zip}",
                        ];
                    }
                    return response()->json($mappedData);
                }

                // Jika bukan angka dan KiriminAja kosong
                return response()->json([]);
            }

            // 3. Query ke DB Lokal (Mencari berdasarkan Nama Kecamatan ATAU Kodepos)
            $autoKirimDbs = \Illuminate\Support\Facades\DB::table('auto_kirims')
                ->where(function($q) use ($searchDistricts, $searchZips) {
                    if (!empty($searchDistricts)) {
                        $q->whereIn('district_name', array_unique($searchDistricts));
                    }
                    if (!empty($searchZips)) {
                        $q->orWhereIn('zip', array_unique($searchZips));
                    }
                })
                ->get();

            // 4. Join Data & Filter
            $processedData = collect($mappedData)->map(function ($item) use ($autoKirimDbs) {
                $match = $autoKirimDbs->first(function ($dbItem) use ($item) {

                    // PRIORITAS 1: Pencocokan Berdasarkan Kodepos (Paling Akurat)
                    if (!empty($item['postal_code']) && $item['postal_code'] !== 'N/A') {
                        if ($dbItem->zip == $item['postal_code']) {
                            return true;
                        }
                    }

                    // PRIORITAS 2: Pencocokan Berdasarkan String Teks
                    $dbDistrict = strtoupper($dbItem->district_name);
                    $dbRegency = strtoupper($dbItem->regency_name);
                    $reqDistrict = strtoupper($item['district']);

                    return str_contains($dbDistrict, $reqDistrict) &&
                           str_contains($dbRegency, $item['clean_regency']);
                });

                unset($item['clean_regency']);
                $item['district_id'] = $match ? $match->district_id : null;

                return $item;
            })->filter(function($item) {
                // Sembunyikan alamat yang distric_id-nya tidak ditemukan agar tidak error saat submit
                return !is_null($item['district_id']);
            })->values();

            return response()->json($processedData);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG KiriminAja x Autokirim API Error: " . $e->getMessage());
            return response()->json([], 500);
        }
    }

    // ========================================================
    // PERBAIKAN: WEIGHT DI PASTIKAN INTEGER MURNI (TANPA KUTIP)
    // ========================================================
    public function cekOngkirAjax(Request $request)
    {
        $origin_id      = $request->origin_id;
        $destination_id = $request->destination_id;

        $qty            = (string) $request->input('qty', 1);
        $isSenderPp     = (int) $request->input('is_sender_pp', 1);
        $commodityCode  = $request->input('kategori_barang', 'OTH001');

        // PASTIKAN BERAT GRAM ADALAH INTEGER MURNI
        $beratGram = (int) $request->berat_gram;
        $weightApi = $beratGram > 0 ? $beratGram : 1000;

        if (empty($origin_id) || empty($destination_id)) {
            return response()->json(['success' => false, 'message' => 'Wilayah asal atau tujuan tidak valid.']);
        }

        try {
            // [PERBAIKAN]: Gunakan Centralized Config
            $config = $this->getAutokirimConfig();
            $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

            $payload = [
                'origin_id'         => (int) $origin_id,
                'destination_id'    => (int) $destination_id,
                'weight'            => (string) $weightApi,
                'length'            => (int) ($request->panjang_cm > 0 ? $request->panjang_cm : 10),
                'width'             => (int) ($request->lebar_cm > 0 ? $request->lebar_cm : 10),
                'height'            => (int) ($request->tinggi_cm > 0 ? $request->tinggi_cm : 10),
                'is_sender_pp'      => (int) $isSenderPp,
                'additional'        => [
                    'commodity'     => $commodityCode
                ]
            ];

            \Illuminate\Support\Facades\Log::info("LOG LOG: [API CEK ONGKIR] (API: {$config->mode} | APP: {$appMode}) REQUEST:", $payload);

            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->withToken($config->token)
                ->post("{$config->base_url}/api/v2/check-price", $payload);

            $result = $response->json();

            \Illuminate\Support\Facades\Log::info("LOG LOG: [API CEK ONGKIR] (API: {$config->mode} | APP: {$appMode}) RESPONSE:", $result ?? []);

            if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {
                $flatOngkir = [];

                foreach ($result['data'] as $courier) {
                    if (!isset($courier['service_detail']) || empty($courier['service_detail'])) {
                        continue;
                    }

                    $parsedCourier = ShippingHelper::parseShippingMethod($courier['courier_code'] ?? $courier['courier_name']);

                    foreach ($courier['service_detail'] as $service) {
                        $totalHarga = (int) $service['price'] * $qty;

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
                            'asuransi_rate'  => $service['insurance'] ?? 0,
                            'fee_cod'        => $service['fee_cod'] ?? 0,
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
            \Illuminate\Support\Facades\Log::error("LOG LOG: [API CEK ONGKIR] ERROR: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kendala jaringan saat menghubungi server logistik.']);
        }
    }

  public function store(Request $request)
    {
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

        $userRole = strtolower(auth()->user()->role ?? 'pelanggan');
        $redirectRoute = ($userRole === 'admin') ? 'admin.pesanan-autokirim.create' : 'customer.pesanan-autokirim.create';
        $redirectUrl   = route($redirectRoute);
        
        $indexRoute    = ($userRole === 'admin') ? 'admin.pesanan-autokirim.index' : 'customer.pesanan-autokirim.index';
        $indexUrl      = route($indexRoute);

        // 1. TAMBAHKAN LOG INI UNTUK PAYLOAD INPUT USER
        \Illuminate\Support\Facades\Log::info("LOG LOG: [CREATE ORDER - USER INPUT PAYLOAD] ($appMode)", [
            'user_id' => auth()->id() ?? 'guest',
            'ip'      => $request->ip(),
            'payload' => $request->all() // Menangkap semua data form dalam bentuk JSON
        ]);

        $request->validate([
            'service_code_terpilih' => 'required',
            'pengirim_nama'         => 'required|string|max:50',
            'pengirim_hp'           => 'required|string|min:9|max:15',
            'pengirim_district_id'  => 'required',
            'pengirim_kodepos'      => 'required|string',
            'pengirim_alamat'       => 'required|string|min:15',
            'penerima_nama'         => 'required|string|max:50',
            'penerima_hp'           => 'required|string|min:9|max:15',
            'penerima_district_id'  => 'required',
            'penerima_kodepos'      => 'required|string',
            'penerima_alamat'       => 'required|string|min:15',
            'berat_gram'            => 'required|numeric|min:1',
            'qty'                   => 'required|numeric|min:1',
            'is_sender_pp'          => 'required|in:0,1',
            'metode_pembayaran'     => 'required|string',
            'ongkir_terpilih'       => 'required|numeric|min:1'
        ], [
            'pengirim_alamat.min' => 'Alamat Pengirim terlalu pendek! Wajib menuliskan nama jalan, nomor rumah/gedung, atau RT/RW (Min. 15 karakter).',
            'penerima_alamat.min' => 'Alamat Penerima terlalu pendek! Wajib menuliskan nama jalan, nomor rumah/gedung, atau RT/RW (Min. 15 karakter).',
        ]);

        // [FITUR IDEMPOTENCY BERBASIS REDIS]: Kunci request selama 10 detik
        // Gunakan IP Address jika user belum login (Guest)
        $lockKey = 'create_order_user_' . (auth()->id() ?? md5(request()->ip()));

        // Redis SETNX: Set key hanya jika belum ada
        if (!Redis::setnx($lockKey, 'locked')) {
            return redirect()->back()->with('error', 'Pesanan Anda sedang diproses. Mohon jangan klik tombol submit berkali-kali.');
        }

        // Pasang timer kedaluwarsa 10 detik
        Redis::expire($lockKey, 10);

        try {
            $origin = AutoKirim::where('district_id', $request->pengirim_district_id)->first();
            $destination = AutoKirim::where('district_id', $request->penerima_district_id)->first();

            if (!$origin || !$destination) {
                return redirect()->back()->withInput()->with('error', 'Wilayah pengirim atau penerima tidak valid.');
            }

            $localOrderId = (string) (date('ymdHis') . mt_rand(1000, 9999));
            $ongkirDasar  = (int) $request->ongkir_terpilih; // Ongkir asli untuk laporan profit
            $paymentMethod = $request->metode_pembayaran;

            $hargaBarangInput = (int) $request->nilai_barang;
            $finalPrice = $hargaBarangInput > 0 ? $hargaBarangInput : 10000;
            $isInsurance = $request->has('asuransi');

            $isCod = in_array(strtolower($paymentMethod), ['cod', 'codbarang', 'cod_barang', 'cod_ongkir']);

            $rateAsuransi = (float) $request->input('rate_asuransi', 0);
            $rateCod = (float) $request->input('rate_cod', 0);

            $feeAsuransi = 0;
            $feeCod = 0;

            // Jika pilih asuransi (IYA), hitung biayanya. Jika tidak, tetap 0.
            if ($isInsurance && $finalPrice > 0) {
                $feeAsuransi = round($finalPrice * $rateAsuransi);
            }

            if ($isCod) {
                $baseCod = $ongkirDasar;
                if ($paymentMethod === 'cod_barang') {
                    $baseCod += $finalPrice;
                }

                $feeCod = round($baseCod * $rateCod);
                $minFee = stripos($request->kurir_terpilih, 'sicepat') !== false ? 2000 : 1500;
                if ($feeCod > 0 && $feeCod < $minFee) {
                    $feeCod = $minFee;
                }
            }

            // PERHITUNGAN GRAND TOTAL AKHIR
            if ($isCod) {
                $totalTagihan = $ongkirDasar + $feeAsuransi + $feeCod;
                if ($paymentMethod === 'cod_barang') {
                    $totalTagihan += $finalPrice;
                }
            } else {
                // UNTUK NON-COD: Otomatis Ongkir + Asuransi (Jika Asuransi Tidak, maka + 0)
                $totalTagihan = $ongkirDasar + $feeAsuransi;
            }

            DB::beginTransaction(); // Baris ini dan ke bawah tetap sama persis seperti kode asli Anda
            try {
                $rates = DB::table('data_auto_kirims')->get();

                $kalkulasiData = (object) [
                    'kurir' => $request->kurir_terpilih,
                    'layanan' => $request->layanan_terpilih,
                    'metode_pembayaran' => $paymentMethod,
                    'ongkir' => $ongkirDasar
                ];

                $profit = $this->hitungProfit($kalkulasiData, $rates);

                $pesanan = PesananAutokirim::create([
                    'user_id'           => auth()->id() ?? null,
                    'order_id'          => $localOrderId,
                    'pengirim_nama'     => $request->pengirim_nama,
                    'pengirim_hp'       => $request->pengirim_hp,
                    'pengirim_alamat'   => $request->pengirim_alamat,
                    'pengirim_kodepos'  => $request->pengirim_kodepos,
                    'penerima_nama'     => $request->penerima_nama,
                    'penerima_hp'       => $request->penerima_hp,
                    'penerima_alamat'   => $request->penerima_alamat,
                    'penerima_kodepos'  => $request->penerima_kodepos,
                    'deskripsi_barang'  => $request->deskripsi_barang,
                    'kategori_barang'   => $request->kategori_barang,
                    'berat_gram'        => $request->berat_gram,
                    'panjang_cm'        => $request->panjang_cm ? (int) $request->panjang_cm : 10,
                    'lebar_cm'          => $request->lebar_cm ? (int) $request->lebar_cm : 10,
                    'tinggi_cm'         => $request->tinggi_cm ? (int) $request->tinggi_cm : 10,
                    'asuransi'          => $isInsurance ? 1 : 0,
                    'nilai_barang'      => $finalPrice,
                    'kurir'             => $request->kurir_terpilih,
                    'layanan'           => $request->layanan_terpilih,
                    'ongkir'            => $ongkirDasar,
                    'grand_total'       => $totalTagihan,
                    'awb_number'        => null,
                    'metode_pembayaran' => $paymentMethod,
                    'status'            => 'waiting_payment',
                    'total_cashback'    => $profit->total_cashback,
                    'laba_sistem'       => $profit->laba_sancaka,
                    'komisi_agen'       => $profit->komisi_agen
                ]);

                // ==========================================================
                // HYBRID SYSTEM: MANUAL & OTOMATIS SIMPAN KONTAK
                // (TARUH DI SINI AGAR TIDAK TER-SKIP OLEH RETURN PEMBAYARAN)
                // ==========================================================
                if ($request->input('simpan_pengirim') == 1 || $request->input('simpan_pengirim') == 'on') {
                    \App\Models\Kontak::updateOrCreate(
                        ['no_hp' => $request->pengirim_hp, 'user_id' => auth()->id()],
                        ['nama' => $request->pengirim_nama, 'alamat' => $request->pengirim_alamat, 'district_id' => $request->pengirim_district_id, 'tipe' => 'Pengirim']
                    );
                } else {
                    \App\Models\Kontak::firstOrCreate(
                        ['no_hp' => $request->pengirim_hp, 'user_id' => auth()->id()],
                        ['nama' => $request->pengirim_nama, 'alamat' => $request->pengirim_alamat, 'district_id' => $request->pengirim_district_id, 'tipe' => 'Pengirim']
                    );
                }

                if ($request->input('simpan_penerima') == 1 || $request->input('simpan_penerima') == 'on') {
                    \App\Models\Kontak::updateOrCreate(
                        ['no_hp' => $request->penerima_hp, 'user_id' => auth()->id()],
                        ['nama' => $request->penerima_nama, 'alamat' => $request->penerima_alamat, 'district_id' => $request->penerima_district_id, 'tipe' => 'Penerima']
                    );
                } else {
                    \App\Models\Kontak::firstOrCreate(
                        ['no_hp' => $request->penerima_hp, 'user_id' => auth()->id()],
                        ['nama' => $request->penerima_nama, 'alamat' => $request->penerima_alamat, 'district_id' => $request->penerima_district_id, 'tipe' => 'Penerima']
                    );
                }
                // ==========================================================

                $paymentUrl = null;

                if (in_array($paymentMethod, ['potong_saldo', 'dana_binding', 'cod_barang', 'cod_ongkir', 'cash'])) {

                    if ($paymentMethod === 'potong_saldo') {
                        // [FITUR IDEMPOTENCY]: Pessimistic Lock untuk mengunci baris user
                        $user = User::lockForUpdate()->find(auth()->id());

                        if (!$user) {
                            throw new Exception('Anda harus login terlebih dahulu untuk menggunakan metode Potong Saldo.');
                        }
                        if ($user->saldo < $totalTagihan) {
                            throw new Exception('Saldo akun Anda tidak mencukupi. Silahkan isi ulang atau pilih metode pembayaran lainnya.');
                        }
                        $user->decrement('saldo', $totalTagihan);
                        Log::info("LOG: [POTONG SALDO SUKSES] ($appMode) User ID {$user->id} dipotong Rp {$totalTagihan} untuk Order ID {$localOrderId}");
                    } elseif ($paymentMethod === 'dana_binding') {
                        $this->_processDanaBindingCharge($pesanan, $totalTagihan, $redirectUrl); // <-- Tambahkan $redirectUrl
                    } elseif ($paymentMethod === 'cash') {
                        // Proteksi ganda: Pastikan yang eksekusi benar-benar admin
                        if ($userRole !== 'admin') {
                            throw new Exception('Akses Ditolak: Metode Cash hanya tersedia untuk Admin Sancaka.');
                        }
                        Log::info("LOG LOG: [PEMBAYARAN CASH] ($appMode) Order ID {$localOrderId} dibayar tunai oleh Admin. Lanjut eksekusi logistik.");
                    }

                    $awbResult = $this->_executeAutokirimApi($pesanan, $origin, $destination, $request);

                    $pesanan->update([
                        'awb_number'        => $awbResult['awb'] ?? null,
                        'tlc_code'          => $awbResult['reff_2'] ?? null,
                        'reff_1'            => $awbResult['reff_1'] ?? null,
                        'pickup_point_code' => $awbResult['pickup'] ?? null,
                        'status'            => 'booking_created'
                    ]);

                    // ==========================================================
                    // MENYIMPAN KODE PICKUP POINT KE DATABASE KONTAK & PENGGUNA
                    // ==========================================================
                    if (!empty($awbResult['pickup'])) {
                        $kodePickup = $awbResult['pickup'];

                        // 1. Update ke tabel kontaks (berdasarkan nomor HP pengirim)
                        \App\Models\Kontak::where('user_id', auth()->id())
                            ->where('no_hp', trim($request->pengirim_hp))
                            ->update(['pickup_point_code' => $kodePickup]);

                        // 2. Update ke tabel Pengguna JIKA nomor HP pengirim sama dengan nomor WA user yang login
                        $userLogin = auth()->user();
                        if ($userLogin && $userLogin->no_wa === trim($request->pengirim_hp)) {
                            // Gunakan primary key tabel Anda (misal id_pengguna)
                            $primaryKey = $userLogin->getKeyName();
                            \App\Models\User::where($primaryKey, $userLogin->$primaryKey)
                                ->update(['pickup_point_code' => $kodePickup]);
                        }
                    }
                    // ==========================================================

                    Log::info("LOG LOG: [DATABASE UPDATED] Pesanan {$localOrderId} sukses diupdate. REFF_1: " . ($awbResult['reff_1'] ?? 'KOSONG'));

                    // ==========================================================
                    // AUTO-SAVE KONTAK (Cek Nama, HP, dan Alamat agar tidak dobel)
                    // ==========================================================

                    // 1. Simpan Pengirim
                    \App\Models\Kontak::firstOrCreate(
                        [
                            // Syarat pencarian: Jika 4 kombinasi data ini sudah ada, JANGAN buat baru
                            'user_id' => auth()->id(),
                            'nama'    => trim($request->pengirim_nama),
                            'no_hp'   => trim($request->pengirim_hp),
                            'alamat'  => trim($request->pengirim_alamat),
                            'tipe'    => 'Pengirim'
                        ],
                        [
                            // Data pelengkap jika ternyata belum ada dan harus buat baru
                            'email'       => $request->pengirim_email,
                            'district_id' => $request->pengirim_district_id,
                        ]
                    );

                    // 2. Simpan Penerima
                    \App\Models\Kontak::firstOrCreate(
                        [
                            // Syarat pencarian: Jika 4 kombinasi data ini sudah ada, JANGAN buat baru
                            'user_id' => auth()->id(),
                            'nama'    => trim($request->penerima_nama),
                            'no_hp'   => trim($request->penerima_hp),
                            'alamat'  => trim($request->penerima_alamat),
                            'tipe'    => 'Penerima'
                        ],
                        [
                            // Data pelengkap jika ternyata belum ada dan harus buat baru
                            'email'       => $request->penerima_email,
                            'district_id' => $request->penerima_district_id,
                        ]
                    );
                    // ==========================================================

                    DB::commit();

                    $metodeTampil = str_replace('_', ' ', strtoupper($paymentMethod));

                    // 2. TAMBAHKAN LOG INI UNTUK RESPONSE SUKSES
                    \Illuminate\Support\Facades\Log::info("LOG LOG: [CREATE ORDER - SUCCESS RESPONSE]", [
                        'order_id'       => $localOrderId,
                        'awb'            => $awbResult['awb'] ?? null,
                        'payment_method' => $metodeTampil,
                        'status'         => 'success_redirect'
                    ]);

                    $this->notifyExpoOrderBaru($localOrderId, auth()->id());

                    $this->notifyAdminOrderBaru($localOrderId, $request->pengirim_nama, $request->kurir_terpilih, $request->layanan_terpilih);

                    // PENGGUNAAN DINAMIS REDIRECT
                    return redirect()->route($redirectRoute)->with('success', "Pesanan Berhasil! Nomor Resi: {$awbResult['awb']} (Metode: {$metodeTampil})");

                } else {
                    if ($paymentMethod === 'doku_jokul') {
                        Log::info("LOG: [DOKU JOKUL] Memulai pembuatan transaksi untuk Order ID {$localOrderId}");
                        $dokuService = new DokuJokulService();
                        $paymentUrl = $dokuService->createPayment($localOrderId, $totalTagihan);
                        if (empty($paymentUrl)) {
                            throw new Exception('Gagal membuat transaksi pembayaran di sistem DOKU Jokul.');
                        }
                    } elseif ($paymentMethod === 'dana_pg') {
                        Log::info("LOG: [DANA PG] Memulai pembuatan transaksi untuk Order ID {$localOrderId}");
                        $paymentUrl = $this->_createDanaPgTransaction($pesanan, $totalTagihan, $redirectUrl); // <-- Tambahkan $redirectUrl
                        if (empty($paymentUrl)) {
                            throw new Exception('Gagal membuat transaksi di sistem DANA Payment Gateway.');
                        }
                    } elseif (Str::startsWith($paymentMethod, 'tripay_')) {
                        Log::info("LOG: [TRIPAY] Memulai pembuatan transaksi untuk Order ID {$localOrderId}");
                        $tripayChannel = strtoupper(str_replace('tripay_', '', $paymentMethod));
                        $tripayResponse = $this->_createTripayTransaction($pesanan, $totalTagihan, $tripayChannel);
                        if (empty($tripayResponse['success'])) {
                            throw new Exception($tripayResponse['message'] ?? 'Gagal membuat tagihan pembayaran di sistem Tripay.');
                        }
                        $paymentUrl = $tripayResponse['data']['checkout_url'] ?? null;
                    }

                    if ($paymentUrl && Schema::hasColumn('pesanan_autokirims', 'payment_url')) {
                        $pesanan->update(['payment_url' => $paymentUrl]);
                    }

                    DB::commit();

                   if ($paymentUrl) {
                        // 3. TAMBAHKAN LOG INI UNTUK RESPONSE REDIRECT PG
                        \Illuminate\Support\Facades\Log::info("LOG LOG: [CREATE ORDER - PG REDIRECT RESPONSE]", [
                            'order_id'    => $localOrderId,
                            'payment_url' => $paymentUrl,
                            'status'      => 'redirect_to_payment_gateway'
                        ]);

                        return redirect()->away($paymentUrl);
                    }

                    throw new Exception('URL Pembayaran tidak ditemukan dari server Payment Gateway.');
                }

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("LOG: [PESANAN AUTOKIRIM - STORE ERROR] ($appMode) " . $e->getMessage());
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        } finally {
            // [FITUR IDEMPOTENCY REDIS]: Hapus kunci agar bisa transaksi lagi
            Redis::del($lockKey);
        }
    }


    private function _createDanaPgTransaction($pesanan, $total, $redirectUrl = null)
    {
        // 1. Ambil config dinamis persis seperti di CheckoutController
        $danaMode = \App\Models\Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            $merchantIdConf = \App\Models\Api::getValue('dana_prod_merchant_id', 'production');
            $partnerIdConf  = \App\Models\Api::getValue('dana_prod_client_id', 'production');
            $privateKey     = \App\Models\Api::getValue('dana_prod_private_key', 'production');
            $clientSecret   = \App\Models\Api::getValue('dana_prod_client_secret', 'production');
            $publicKey      = \App\Models\Api::getValue('dana_prod_public_key', 'production');
            $baseUrl        = 'https://api.saas.dana.id';
        } else {
            $merchantIdConf = \App\Models\Api::getValue('dana_sandbox_merchant_id', 'sandbox');
            $partnerIdConf  = \App\Models\Api::getValue('dana_sandbox_client_id', 'sandbox');
            $privateKey     = \App\Models\Api::getValue('dana_sandbox_private_key', 'sandbox');
            $clientSecret   = \App\Models\Api::getValue('dana_sandbox_client_secret', 'sandbox');
            $publicKey      = \App\Models\Api::getValue('dana_sandbox_public_key', 'sandbox');
            $baseUrl        = 'https://api.sandbox.dana.id';
        }

        if (empty($merchantIdConf) || empty($privateKey)) {
            Log::error("LOG: [DANA PG ERROR] Merchant ID atau Private Key belum dikonfigurasi di database.");
            return null;
        }

        // Timpa config runtime untuk DanaSignatureService
        config([
            'services.dana.merchant_id'   => $merchantIdConf,
            'services.dana.client_id'     => $partnerIdConf,
            'services.dana.x_partner_id'  => $partnerIdConf,
            'services.dana.private_key'   => $privateKey,
            'services.dana.public_key'    => $publicKey,
            'services.dana.client_secret' => $clientSecret,
            'services.dana.base_url'      => $baseUrl,
            'services.dana.dana_env'      => $isProduction ? 'PRODUCTION' : 'SANDBOX',
            'services.dana.origin'        => url('/')
        ]);

        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $pesanan->order_id);
        $timestamp    = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo    = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$total, 2, '.', '');
        $path         = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "validUpTo"          => $validUpTo,
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "urlParams"          => [
                [
                    "url"        => $redirectUrl ?? route('customer.pesanan-autokirim.create'), // URL Return Autokirim
                    "type"       => "PAY_RETURN",
                    "isDeeplink" => "N"
                ],
                [
                    "url"        => url('/api/callback/dana'),
                    "type"       => "NOTIFICATION",
                    "isDeeplink" => "N"
                ]
            ],
            "additionalInfo"     => [
                "mcc" => "5732",
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB"
                ],
                "order" => [
                    "orderTitle"        => substr("Ongkir Autokirim " . $cleanInvoice, 0, 64),
                    "scenario"          => "REDIRECT",
                    "merchantTransType" => "01",
                    "buyer" => [
                        "externalUserId"   => (string) ($pesanan->user_id ?? 'GUEST'.rand(100,999)),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $pesanan->pengirim_nama ?? 'Customer'), 0, 64),
                    ],
                    "goods" => [
                        [
                            "name"            => "Pembayaran Ongkir",
                            "merchantGoodsId" => substr("AK" . $cleanInvoice, 0, 64),
                            "description"     => "Pengiriman " . $pesanan->kurir . " - " . $pesanan->layanan,
                            "category"        => "DIGITAL_GOODS",
                            "price"           => ["value" => $amountValue, "currency" => "IDR"],
                            "unit"            => "pcs",
                            "quantity"        => "1"
                        ]
                    ]
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            // Panggil Service DANA yang sama seperti di CheckoutController
            $danaSignature = app(\App\Services\DanaSignatureService::class);
            $accessToken   = $danaSignature->getAccessToken();
            $signature     = $danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

            $headers = [
                'Authorization'  => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'   => $partnerIdConf,
                'X-EXTERNAL-ID'  => (string) time() . \Illuminate\Support\Str::random(6),
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'Content-Type'   => 'application/json',
                'CHANNEL-ID'     => '95221',
                'ORIGIN'         => url('/'),
            ];

            Log::info("LOG: [DANA PG AUTOKIRIM] Mengirim Request API...", ['URL' => $baseUrl . $path]);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            Log::info("LOG: [DANA PG AUTOKIRIM] Respon API DANA:", $result ?? []);

            if ($response->successful() && isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                return $result['webRedirectUrl'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("LOG: [DANA PG AUTOKIRIM] Exception: " . $e->getMessage());
            return null;
        }
    }

    private function _processDanaBindingCharge($pesanan, $total, $redirectUrl = null)
    {
        $user = \App\Models\User::find(auth()->id());

        // 1. SESUAIKAN KOLOM DENGAN DATABASE (dana_access_token)
        $tokenCustomer = $user->dana_access_token ?? null;

        if (!$user || empty($tokenCustomer)) {
            throw new \Exception("Akun Anda belum mengikat (bind) token DANA. Silakan hubungkan di pengaturan profil.");
        }

        // 2. Ambil config dinamis persis seperti di CheckoutController
        $danaMode = \App\Models\Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            $merchantIdConf = \App\Models\Api::getValue('dana_prod_merchant_id', 'production');
            $partnerIdConf  = \App\Models\Api::getValue('dana_prod_client_id', 'production');
            $privateKey     = \App\Models\Api::getValue('dana_prod_private_key', 'production');
            $clientSecret   = \App\Models\Api::getValue('dana_prod_client_secret', 'production');
            $publicKey      = \App\Models\Api::getValue('dana_prod_public_key', 'production');
            $baseUrl        = 'https://api.saas.dana.id';
        } else {
            $merchantIdConf = \App\Models\Api::getValue('dana_sandbox_merchant_id', 'sandbox');
            $partnerIdConf  = \App\Models\Api::getValue('dana_sandbox_client_id', 'sandbox');
            $privateKey     = \App\Models\Api::getValue('dana_sandbox_private_key', 'sandbox');
            $clientSecret   = \App\Models\Api::getValue('dana_sandbox_client_secret', 'sandbox');
            $publicKey      = \App\Models\Api::getValue('dana_sandbox_public_key', 'sandbox');
            $baseUrl        = 'https://api.sandbox.dana.id';
        }

        if (empty($merchantIdConf) || empty($privateKey)) {
            throw new \Exception("Konfigurasi API DANA belum lengkap.");
        }

        // Timpa config runtime untuk DanaSignatureService
        config([
            'services.dana.merchant_id'   => $merchantIdConf,
            'services.dana.client_id'     => $partnerIdConf,
            'services.dana.x_partner_id'  => $partnerIdConf,
            'services.dana.private_key'   => $privateKey,
            'services.dana.public_key'    => $publicKey,
            'services.dana.client_secret' => $clientSecret,
            'services.dana.base_url'      => $baseUrl,
            'services.dana.dana_env'      => $isProduction ? 'PRODUCTION' : 'SANDBOX',
            'services.dana.origin'        => url('/')
        ]);

        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $pesanan->order_id);
        $timestamp    = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo    = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$total, 2, '.', '');

        // Endpoint SNAP BI untuk Direct Debit
        $path         = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "validUpTo"          => $validUpTo,
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "urlParams"          => [
                [
                    "url"        => $redirectUrl ?? route('customer.pesanan-autokirim.create'),
                    "type"       => "PAY_RETURN",
                    "isDeeplink" => "N"
                ],
                [
                    "url"        => url('/api/callback/dana'),
                    "type"       => "NOTIFICATION",
                    "isDeeplink" => "N"
                ]
            ],
            "payOptionDetails"   => [
                [
                    "payMethod"   => "BALANCE",
                    "payOption"   => "BALANCE",
                    "transAmount" => [
                        "value"    => $amountValue,
                        "currency" => "IDR"
                    ]
                ]
            ],
            "additionalInfo"     => [
                "mcc" => "5732",
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB"
                ],
                "order" => [
                    "orderTitle"        => substr("Bayar Ongkir " . $cleanInvoice, 0, 64),
                    "scenario"          => "DIRECT_DEBIT", // Wajib Direct Debit untuk Binding
                    "merchantTransType" => "01",
                    "buyer" => [
                        // 3. SESUAIKAN KOLOM DENGAN DATABASE (id_pengguna & nama_lengkap)
                        "externalUserId"   => (string) ($user->id_pengguna ?? $user->id),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $user->nama_lengkap ?? 'Customer'), 0, 64),
                    ]
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $danaSignature = app(\App\Services\DanaSignatureService::class);
            $accessToken   = $danaSignature->getAccessToken();
            $signature     = $danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

            $headers = [
                'Authorization'          => 'Bearer ' . $accessToken,
                'Authorization-Customer' => 'Bearer ' . $tokenCustomer, // Kunci utama: Token Binding User
                'X-PARTNER-ID'           => $partnerIdConf,
                'X-EXTERNAL-ID'          => (string) time() . \Illuminate\Support\Str::random(6),
                'X-TIMESTAMP'            => $timestamp,
                'X-SIGNATURE'            => $signature,
                'Content-Type'           => 'application/json',
                'CHANNEL-ID'             => '95221',
                'ORIGIN'                 => url('/'),
            ];

            \Illuminate\Support\Facades\Log::info("LOG LOG: [DANA BINDING AUTOKIRIM] Memulai Request Auto-Debit...", ['URL' => $baseUrl . $path]);

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            \Illuminate\Support\Facades\Log::info("LOG LOG: [DANA BINDING AUTOKIRIM] Respon API:", $result ?? []);

            if ($response->successful() && isset($result['responseCode']) && $result['responseCode'] === '2005400') {

                // Mencegah resi tercetak otomatis jika DANA mendadak meminta verifikasi PIN
                if (!empty($result['webRedirectUrl'])) {
                    throw new \Exception("DANA meminta verifikasi PIN keamanan. Silakan ubah metode pembayaran menjadi 'DANA Payment Gateway' untuk menginput PIN.");
                }

                return true; // Lunas seketika tanpa PIN
            }

            $errorMessage = $result['responseMessage'] ?? 'Gagal memotong saldo DANA Binding Anda.';
            throw new \Exception($errorMessage);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function cetakResi($id)
    {
        $pesanan = PesananAutokirim::findOrFail($id);
        return view('admin.pesanan_autokirim.cetak_resi', compact('pesanan'));
    }

 public function cancelOrder($id)
    {
        $pesanan = PesananAutokirim::findOrFail($id);

        if (in_array($pesanan->status, ['booking_created', 'menunggu_pembayaran'])) {

            // 🔥 LOG REF 3: CEK PAYLOAD SEBELUM CANCEL
            Log::info("LOG LOG: [API AUTOKIRIM - CANCEL INIT] Memulai cancel untuk Order ID: {$pesanan->order_id}. REFF_1 di database adalah: " . ($pesanan->reff_1 ?? 'KOSONG'));

            try {
                $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
                $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
                $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

                // WAJIB pakai reff_1 sesuai dokumentasi API Autokirim
                if (empty($pesanan->reff_1)) {
                     return redirect()->back()->with('error', 'Gagal membatalkan. REFF_1 belum tersedia dari pusat logistik.');
                }

                $payload = [
                    'reff_1' => (int) $pesanan->reff_1 // Menggunakan reff_1
                ];

                Log::info("LOG LOG: [API AUTOKIRIM - CANCEL] REQUEST PAYLOAD:", $payload);

                $response = Http::timeout(15)
                    ->withToken($token)
                    ->post("{$baseUrl}/api/cancel", $payload);

                $result = $response->json();

                Log::info("LOG LOG: [API AUTOKIRIM - CANCEL] RESPONSE:", $result ?? []);

               if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {

                    // 1. Ubah status jadi batal
                    $pesanan->update(['status' => 'batal']);

                    // 2. Kembalikan modal/ongkir (Grand Total + Komisi Agen)
                    if ($pesanan->metode_pembayaran === 'potong_saldo') {
                        $userToRefund = User::find($pesanan->user_id);
                        if ($userToRefund) {
                            $totalRefund = $pesanan->grand_total + $pesanan->komisi_agen;
                            $userToRefund->increment('saldo', $totalRefund);
                            Log::info("LOG: [REFUND SALDO] Berhasil mengembalikan Rp {$totalRefund} (Modal + Komisi) ke User ID {$pesanan->user_id}");
                        }
                    }

                    Log::info("LOG LOG: [API AUTOKIRIM - CANCEL] BERHASIL membatalkan Order ID: {$pesanan->order_id}");
                    return redirect()->back()->with('success', 'Pesanan berhasil dibatalkan di sistem logistik dan saldo telah dikembalikan.');
                }

                Log::error("LOG LOG: [API AUTOKIRIM - CANCEL] DITOLAK API: " . ($result['rd'] ?? 'Unknown Error'));
                return redirect()->back()->with('error', 'Gagal membatalkan di server logistik: ' . ($result['rd'] ?? 'Error API'));

            } catch (\Exception $e) {
                Log::error("LOG LOG: [API AUTOKIRIM - CANCEL] ERROR JARINGAN: " . $e->getMessage());
                return redirect()->back()->with('error', 'Terjadi kendala jaringan saat membatalkan ke server logistik.');
            }
        }

        return redirect()->back()->with('error', 'Status pesanan saat ini tidak dapat dibatalkan.');
    }

    public function destroy($id)
    {
        PesananAutokirim::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Data pesanan berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        if (!empty($ids)) {
            PesananAutokirim::whereIn('id', $ids)->delete();
            return redirect()->back()->with('success', count($ids) . ' pesanan berhasil dihapus secara massal.');
        }
        return redirect()->back()->with('error', 'Pilih minimal satu pesanan untuk dihapus.');
    }

   public function handleWebhook(Request $request)
    {
        Log::info('WEBHOOK AUTOKIRIM DITERIMA:', $request->all());

        $refId = $request->input('ref_id');
        $awb = $request->input('awb_number');
        $status = $request->input('transactions_stats');
        $desc = $request->input('transactions_desc');

        if (!$refId && !$awb) {
            return response()->json(['success' => false, 'message' => 'Invalid Payload'], 400);
        }

        DB::beginTransaction();
        try {
            // [FITUR IDEMPOTENCY]: lockForUpdate() mengunci row ini selama transaksi berjalan.
            // Mencegah double hit webhook yang datang bersamaan agar eksekusi tidak saling tabrak.
            $pesanan = PesananAutokirim::where('order_id', $refId)
                ->orWhere('awb_number', $awb)
                ->lockForUpdate()
                ->first();

            if ($pesanan) {
                $statusLama = strtolower($pesanan->status);
                $statusBaru = strtolower($status);

                $pesanan->update([
                    'status' => $status
                ]);

                $kumpulanStatusGagal = ['batal', 'gagal', 'cancelled', 'returned'];
                $kumpulanStatusSukses = ['terkirim', 'selesai', 'sukses', 'delivered', 'success', 'completed'];

                // =======================================================
                // 1. [SKENARIO GAGAL/BATAL] KEMBALIKAN MODAL ONGIR UTUH
                // =======================================================
                if (in_array($statusBaru, $kumpulanStatusGagal) && !in_array($statusLama, $kumpulanStatusGagal)) {
                    if ($pesanan->metode_pembayaran === 'potong_saldo') {
                        $userToRefund = User::lockForUpdate()->find($pesanan->user_id);
                        if ($userToRefund) {
                            $userToRefund->increment('saldo', $pesanan->grand_total);
                            Log::info("LOG: [WEBHOOK REFUND] Saldo dikembalikan UTUH sebesar Rp {$pesanan->grand_total} via Webhook untuk Order ID {$pesanan->order_id}");
                        }
                    }
                }

                // =======================================================
                // 2. 🔥 [SKENARIO SUKSES] CAIRKAN KOMISI AGEN
                // =======================================================
                if (in_array($statusBaru, $kumpulanStatusSukses) && !in_array($statusLama, $kumpulanStatusSukses)) {
                    if ($pesanan->komisi_agen > 0) {
                        $userAgen = User::lockForUpdate()->find($pesanan->user_id);
                        if ($userAgen) {
                            $userAgen->increment('saldo', $pesanan->komisi_agen);
                            Log::info("LOG LOG: [WEBHOOK KOMISI CAIR] Hore! Saldo komisi cair Rp {$pesanan->komisi_agen} ke User ID {$pesanan->user_id} karena Order ID {$pesanan->order_id} berstatus {$statusBaru}.");
                        }
                    }
                }

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Status pesanan berhasil diperbarui'], 200);
            }

            DB::commit();
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG: [WEBHOOK ERROR] " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    private function _createTripayTransaction($pesanan, $total, $channelCode)
    {
        // 1. Cek Mode Apa yang Aktif di Database (Sandbox / Production)
        $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        $baseUrl      = '';
        $apiKey       = '';
        $privateKey   = '';
        $merchantCode = '';

        // 2. Isi Kredensial Berdasarkan Mode
        if ($mode === 'production') {
            $baseUrl      = 'https://tripay.co.id/api/transaction/create';
            $apiKey       = Api::getValue('TRIPAY_API_KEY', 'production');
            $privateKey   = Api::getValue('TRIPAY_PRIVATE_KEY', 'production');
            $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', 'production');
        } else {
            $baseUrl      = 'https://tripay.co.id/api-sandbox/transaction/create';
            $apiKey       = Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $privateKey   = Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox');
            $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox');
        }

        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            return ['success' => false, 'message' => 'Konfigurasi API Tripay belum lengkap di database.'];
        }

        $userEmail = auth()->user()->email ?? 'customer+' . Str::random(5) . '@tokosancaka.com';

        // 3. Setup Payload untuk Autokirim
        $payload = [
            'method'         => $channelCode,
            'merchant_ref'   => $pesanan->order_id,
            'amount'         => $total,
            'customer_name'  => $pesanan->pengirim_nama,
            'customer_email' => $userEmail,
            'customer_phone' => $pesanan->pengirim_hp,
            'order_items'    => [
                [
                    'sku'      => 'ONGKIR-AK',
                    'name'     => "Ongkos Kirim ({$pesanan->kurir} - {$pesanan->layanan})",
                    'price'    => $total,
                    'quantity' => 1
                ]
            ],
            'return_url'     => route('customer.pesanan-autokirim.index'),
            'expired_time'   => time() + (24 * 60 * 60),
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->order_id . $total, $privateKey),
        ];

        // --- TAMBAHKAN LOG REQUEST DI SINI ---
        // (Sengaja tidak melog 'signature' utuh demi keamanan credential)
        \Illuminate\Support\Facades\Log::info("LOG LOG: [API TRIPAY - CREATE TRANSACTION] REQUEST PAYLOAD:", [
            'method'       => $payload['method'],
            'merchant_ref' => $payload['merchant_ref'],
            'amount'       => $payload['amount'],
            'return_url'   => $payload['return_url']
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(30)->withoutVerifying()->post($baseUrl, $payload);

            $body = $response->json();

            // --- TAMBAHKAN LOG RESPONSE DI SINI ---
            \Illuminate\Support\Facades\Log::info("LOG LOG: [API TRIPAY - CREATE TRANSACTION] RESPONSE:", $body ?? []);

            // Pengecekan status sukses dari Tripay
            if ($response->successful() && ($body['success'] ?? false) === true) {
                return ['success' => true, 'data' => $body['data']];
            }

            \Illuminate\Support\Facades\Log::error('LOG LOG: [API TRIPAY - CREATE TRANSACTION] ERROR DARI TRIPAY:', ['response' => $body]);
            return ['success' => false, 'message' => $body['message'] ?? 'Gagal membuat tagihan pembayaran di sistem Tripay.'];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG LOG: [API TRIPAY - CREATE TRANSACTION] CONNECTION EXCEPTION: " . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi ke server Tripay gagal: ' . $e->getMessage()];
        }
    }

    /**
     * =========================================================================
     * PROCESSOR WEBHOOK DARI PAYMENT GATEWAY (TRIPAY / DOKU / DANA)
     * =========================================================================
     */
    public function processPaymentCallback($orderId, $status, $data = [])
    {
        Log::info("LOG LOG: [WEBHOOK AUTOKIRIM] Memulai proses update pembayaran untuk Order ID: {$orderId} | Status: {$status}");

        DB::beginTransaction();
        try {
            // Gunakan Pessimistic Lock agar aman dari double webhook
            $pesanan = PesananAutokirim::lockForUpdate()->where('order_id', $orderId)->first();

            if (!$pesanan) {
                Log::warning("LOG LOG: [WEBHOOK AUTOKIRIM] Pesanan tidak ditemukan.");
                DB::rollBack();
                return;
            }

            // Cegah proses berulang jika sudah berhasil dibooking sebelumnya
            if (in_array(strtolower($pesanan->status), ['batal', 'gagal', 'booking_created'])) {
                Log::info("LOG LOG: [WEBHOOK AUTOKIRIM] Pesanan sudah diproses sebelumnya (Status saat ini: {$pesanan->status}). Skip.");
                DB::rollBack();
                return;
            }

            if ($status === 'PAID') {
                try {
                    // 1. Ambil data origin & destination berdasarkan kode pos
                    $origin = AutoKirim::where('zip', $pesanan->pengirim_kodepos)->first();
                    $destination = AutoKirim::where('zip', $pesanan->penerima_kodepos)->first();

                    if (!$origin || !$destination) {
                        throw new Exception("Kode Pos origin/destination tidak ditemukan di database.");
                    }

                    // 2. Eksekusi Booking ke API Server Autokirim
                    $awbResult = $this->_executeAutokirimApi($pesanan, $origin, $destination);

                    // 3. Simpan Resi dan Ubah Status
                    $pesanan->update([
                        'awb_number'        => $awbResult['awb'],
                        'tlc_code'          => $awbResult['tlc'],
                        'pickup_point_code' => $awbResult['pickup'],
                        'status'            => 'booking_created', // Lunas & Resi Terbit
                        'updated_at'        => now()
                    ]);

                    Log::info("LOG LOG: ✅ [WEBHOOK AUTOKIRIM] Sukses! Booking API berhasil via Payment Gateway.", ['AWB' => $awbResult['awb']]);

                } catch (Exception $e) {
                    // Jika pelanggan sudah bayar tapi API Autokirim gangguan/error
                    Log::error("LOG LOG: ❌ [WEBHOOK AUTOKIRIM] Pembayaran sukses, TAPI Gagal Booking API. Perlu Cek Manual!", ['error' => $e->getMessage()]);
                    $pesanan->update(['status' => 'paid_but_failed_booking']);
                }
            }
            elseif (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
                $pesanan->update(['status' => 'gagal']);
                Log::info("LOG LOG: [WEBHOOK AUTOKIRIM] Pesanan digagalkan karena status Tripay: {$status}");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG LOG: [WEBHOOK AUTOKIRIM] Fatal Error: " . $e->getMessage());
        }
    }

  /**
     * =========================================================================
     * 1. API: INSERT PICKUP POINT
     * =========================================================================
     */
    private function insertPickupPointApi($data)
    {
        $config = $this->getAutokirimConfig();
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

        try {
            // Mapping Payload Sesuai Dokumentasi
            $payload = [
                "name"              => (string) $data['name'],
                "phone"             => (string) $data['phone'],
                "address"           => (string) $data['address'],
                "email"             => (string) ($data['email'] ?? ''),
                "longitude"         => (string) ($data['longitude'] ?? ''),
                "latitude"          => (string) ($data['latitude'] ?? ''),
                "district_id"       => (int) $data['district_id'], // Wajib Integer
                "is_member_deposit" => isset($data['is_member_deposit']) ? (bool) $data['is_member_deposit'] : false // Wajib Boolean
            ];

            \Illuminate\Support\Facades\Log::info("LOG LOG: [API INSERT PICKUP] (API: {$config->mode} | APP: {$appMode}) REQUEST:", $payload);

            $response = \Illuminate\Support\Facades\Http::timeout(15)->withToken($config->token)
                ->post("{$config->base_url}/api/pickup-point/insert", $payload);

            $result = $response->json();
            \Illuminate\Support\Facades\Log::info("LOG LOG: [API INSERT PICKUP] (API: {$config->mode} | APP: {$appMode}) RESPONSE:", $result ?? []);

            return $result; // Mengembalikan full array response
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG LOG: [API INSERT PICKUP] (APP: {$appMode}) ERROR: " . $e->getMessage());
            return ['rc' => '500', 'rd' => 'Network/System Error: ' . $e->getMessage()];
        }
    }

    /**
     * =========================================================================
     * 2. API: UPDATE PICKUP POINT
     * =========================================================================
     */
    private function updatePickupPoint($pickupCode, $data)
    {
        $config = $this->getAutokirimConfig();
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

        try {
            // Mapping Payload Sesuai Dokumentasi
            $payload = [
                "name"              => (string) $data['name'],
                "phone"             => (string) $data['phone'],
                "district_id"       => (int) $data['district_id'], // Wajib Integer
                "address"           => (string) $data['address'],
                "longitude"         => (string) ($data['longitude'] ?? ''),
                "latitude"          => (string) ($data['latitude'] ?? ''),
                "pickup_point_code" => (string) $pickupCode
            ];

            \Illuminate\Support\Facades\Log::info("LOG LOG: [API UPDATE PICKUP] (API: {$config->mode} | APP: {$appMode}) REQUEST:", $payload);

            $response = \Illuminate\Support\Facades\Http::timeout(15)->withToken($config->token)
                ->post("{$config->base_url}/api/pickup-point/update", $payload);

            $result = $response->json();
            \Illuminate\Support\Facades\Log::info("LOG LOG: [API UPDATE PICKUP] (API: {$config->mode} | APP: {$appMode}) RESPONSE:", $result ?? []);

            return ($response->successful() && isset($result['rc']) && $result['rc'] === '00');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG LOG: [API UPDATE PICKUP] (APP: {$appMode}) ERROR: " . $e->getMessage());
            return false;
        }
    }

    /**
     * =========================================================================
     * 3. API: FIND PICKUP POINT
     * =========================================================================
     */
    private function findPickupPoint($pickupCode)
    {
        $config = $this->getAutokirimConfig();
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

        try {
            // Mapping Payload Sesuai Dokumentasi
            $payload = [
                'pickup_point_code' => (string) $pickupCode
            ];

            \Illuminate\Support\Facades\Log::info("LOG LOG: [API FIND PICKUP] (API: {$config->mode} | APP: {$appMode}) REQUEST:", $payload);

            $response = \Illuminate\Support\Facades\Http::timeout(10)->withToken($config->token)
                ->post("{$config->base_url}/api/pickup-point/find", $payload);

            $result = $response->json();
            \Illuminate\Support\Facades\Log::info("LOG LOG: [API FIND PICKUP] (API: {$config->mode} | APP: {$appMode}) RESPONSE:", $result ?? []);

            return ($response->successful() && isset($result['rc']) && $result['rc'] === '00');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG LOG: [API FIND PICKUP] (APP: {$appMode}) ERROR: " . $e->getMessage());
            return false;
        }
    }

    /**
     * =========================================================================
     * 4. API: DELETE PICKUP POINT
     * =========================================================================
     */
    public function deletePickupPointApi($pickupCode)
    {
        $config = $this->getAutokirimConfig();
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

        try {
            // Mapping Payload Sesuai Dokumentasi
            $payload = [
                'pickup_point_code' => (string) $pickupCode
            ];

            \Illuminate\Support\Facades\Log::info("LOG LOG: [API DELETE PICKUP] (API: {$config->mode} | APP: {$appMode}) REQUEST:", $payload);

            $response = \Illuminate\Support\Facades\Http::timeout(10)->withToken($config->token)
                ->post("{$config->base_url}/api/pickup-point/delete", $payload);

            $result = $response->json();
            \Illuminate\Support\Facades\Log::info("LOG LOG: [API DELETE PICKUP] (API: {$config->mode} | APP: {$appMode}) RESPONSE:", $result ?? []);

            return ($response->successful() && isset($result['rc']) && $result['rc'] === '00');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG LOG: [API DELETE PICKUP] (APP: {$appMode}) ERROR: " . $e->getMessage());
            return false;
        }
    }


   public function generatePickupPointAjax(Request $request)
    {
        $request->validate([
            'pengirim_nama' => 'required|string',
            'pengirim_hp' => 'required|string',
            'pengirim_alamat' => 'required|string',
            'pengirim_district_id' => 'required'
        ]);

        $noHpPengirim = trim($request->pengirim_hp);
        $districtId = (int) $request->pengirim_district_id;

        // [FITUR IDEMPOTENCY] Mengunci eksekusi AJAX selama 10 detik
        $lockKey = 'generate_pickup_lock_' . md5($noHpPengirim . $districtId);
        if (!\Illuminate\Support\Facades\Redis::setnx($lockKey, 'locked')) {
            return response()->json(['success' => false, 'message' => 'Sedang memproses permintaan Anda, harap tunggu...']);
        }
        \Illuminate\Support\Facades\Redis::expire($lockKey, 10);

        try {
            $config = $this->getAutokirimConfig();
            $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';
            $user = auth()->user();

            $alamatPengirim = trim($request->pengirim_alamat);
            $namaPengirim = trim($request->pengirim_nama);
            $emailPengirim = trim($request->pengirim_email) ?: ($user->email ?? 'customer@tokosancaka.com');

            $payloadData = [
                'name'              => (string) $namaPengirim,
                'phone'             => (string) $noHpPengirim,
                'address'           => (string) $alamatPengirim,
                'email'             => (string) $emailPengirim,
                'longitude'         => "",
                'latitude'          => "",
                'district_id'       => $districtId,
                'is_member_deposit' => false
            ];

            // 1. CEK CACHE REDIS
            $dataStr = strtolower($noHpPengirim . $districtId . $alamatPengirim . $namaPengirim);
            $redisPickupKey = "pickup_point_hash_" . md5($dataStr);
            $pickupPointCode = \Illuminate\Support\Facades\Redis::get($redisPickupKey);

            if ($pickupPointCode) {
                return response()->json(['success' => true, 'pickup_point_code' => $pickupPointCode]);
            }

            $isDataIdentikDenganUserAuth = ($user && trim($user->no_wa) === $noHpPengirim);
            $userSama = $isDataIdentikDenganUserAuth ? $user : null;

            // 2. CARI DI DATABASE LOKAL HANYA BERDASARKAN NOMOR HP
            $kontak = \App\Models\Kontak::where('no_hp', $noHpPengirim)
                ->whereNotNull('pickup_point_code')
                ->orderBy('updated_at', 'desc')
                ->first();

            $existingPickupCode = $kontak->pickup_point_code ?? ($userSama->pickup_point_code ?? null);

            // 3. JIKA SUDAH PUNYA KODE LOKAL -> WAJIB UPDATE API
            if ($existingPickupCode) {
                if ($this->findPickupPoint($existingPickupCode)) {
                    \Illuminate\Support\Facades\Log::info("LOG: [AJAX PICKUP] Kode lokal {$existingPickupCode} ditemukan. Sinkronisasi API...");
                    if ($this->updatePickupPoint($existingPickupCode, $payloadData)) {
                        $pickupPointCode = $existingPickupCode;
                    } else {
                        throw new \Exception('Gagal mensinkronkan pembaruan alamat ke server logistik.');
                    }
                } else {
                    if ($kontak) $kontak->update(['pickup_point_code' => null]);
                    if ($userSama) \App\Models\User::where($userSama->getKeyName(), $userSama->id)->update(['pickup_point_code' => null]);
                    $existingPickupCode = null;
                }
            }

            // 4. JIKA TIDAK ADA KODE LOKAL -> INSERT BARU
            if (!$pickupPointCode) {
                \Illuminate\Support\Facades\Log::info("LOG: [AJAX PICKUP] REQUEST Insert Baru API:", $payloadData);

                $pickupResponse = \Illuminate\Support\Facades\Http::timeout(15)->withToken($config->token)
                    ->post("{$config->base_url}/api/pickup-point/insert", $payloadData);

                $pickupResult = $pickupResponse->json();
                \Illuminate\Support\Facades\Log::info("LOG: [AJAX PICKUP] FULL RESPONSE API:", $pickupResult ?? []);

                // Ekstraksi kode secara agresif
                $codeFromApi = $pickupResult['data']['pickup_point_code'] ?? $pickupResult['pickup_point_code'] ?? null;

                // SELAMATKAN KODE: Berhasil Insert (00) ATAUPUN Terdaftar (01/02)
                if (isset($pickupResult['rc']) && in_array($pickupResult['rc'], ['00', '01', '02'])) {
                    if (!empty($codeFromApi)) {
                        $pickupPointCode = (string) $codeFromApi;

                        // Jika 01/02, paksa Update agar alamat server sinkron dengan form
                        if (in_array($pickupResult['rc'], ['01', '02'])) {
                            \Illuminate\Support\Facades\Log::warning("LOG: [AJAX PICKUP] Menerima RC {$pickupResult['rc']}. Menyelamatkan kode {$pickupPointCode} dan sinkronisasi Update...");
                            $this->updatePickupPoint($pickupPointCode, $payloadData);
                        } else {
                            \Illuminate\Support\Facades\Log::info("LOG: [AJAX PICKUP] Sukses Insert Baru. Kode: {$pickupPointCode}");
                        }
                    } else {
                        // Kondisi jika API merespons 01/02 tapi TIDAK memberikan kode akses
                        throw new \Exception(($pickupResult['rd'] ?? 'Terdaftar') . ' (Server pusat menahan kode akses. Solusi: Gunakan nomor HP lain)');
                    }
                } else {
                    throw new \Exception($pickupResult['rd'] ?? 'Gagal mendaftarkan alamat jemput ke server logistik.');
                }
            }

            // 5. SIMPAN KE REDIS DAN DATABASE (Anti N+1)
            if ($pickupPointCode) {
                \Illuminate\Support\Facades\Redis::setex($redisPickupKey, 2592000, $pickupPointCode);

                if ($userSama) {
                    \App\Models\User::where($userSama->getKeyName(), $userSama->id)->update([
                        'pickup_point_code' => $pickupPointCode, 'address_detail' => $alamatPengirim, 'nama_lengkap' => $namaPengirim
                    ]);
                }
                if ($user) {
                    \App\Models\Kontak::updateOrCreate(
                        ['no_hp' => $noHpPengirim, 'user_id' => auth()->id()],
                        ['nama' => $namaPengirim, 'alamat' => $alamatPengirim, 'district_id' => $districtId, 'tipe' => 'Pengirim', 'pickup_point_code' => $pickupPointCode, 'email' => $emailPengirim]
                    );
                }
            }

            return response()->json(['success' => true, 'pickup_point_code' => $pickupPointCode]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG: [AJAX PICKUP] ERROR: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        } finally {
            \Illuminate\Support\Facades\Redis::del($lockKey);
        }
    }


    public function _executeAutokirimApi($pesanan, $origin, $destination, $requestData = null)
    {
        $config = $this->getAutokirimConfig();
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';
        $user = auth()->user();

        $noHpPengirim = trim($pesanan->pengirim_hp);
        $alamatPengirim = trim($pesanan->pengirim_alamat);
        $namaPengirim = trim($pesanan->pengirim_nama);
        $districtId = (int) $origin->district_id;
        $emailPengirim = $user->email ?? 'customer@tokosancaka.com';

        $payloadData = [
            'name'              => (string) $namaPengirim,
            'phone'             => (string) $noHpPengirim,
            'address'           => (string) $alamatPengirim,
            'email'             => (string) $emailPengirim,
            'longitude'         => "",
            'latitude'          => "",
            'district_id'       => $districtId,
            'is_member_deposit' => false
        ];

        // 1. Cek Redis
        $dataStr = strtolower($noHpPengirim . $districtId . $alamatPengirim . $namaPengirim);
        $redisPickupKey = "pickup_point_hash_" . md5($dataStr);
        $pickupPointCode = \Illuminate\Support\Facades\Redis::get($redisPickupKey);

        if ($pickupPointCode) {
            if (!$this->findPickupPoint($pickupPointCode)) {
                \Illuminate\Support\Facades\Log::warning("LOG: [EXECUTE] Kode Redis {$pickupPointCode} MATI. Hapus Cache...");
                \Illuminate\Support\Facades\Redis::del($redisPickupKey);
                $pickupPointCode = null;
            }
        }

        if (!$pickupPointCode) {
            $isDataIdentikDenganUserAuth = ($user && trim($user->no_wa) === $noHpPengirim);
            $userSama = $isDataIdentikDenganUserAuth ? $user : null;

            // 2. Cek Database Lokal HANYA dari Nomor HP
            $kontak = \App\Models\Kontak::where('no_hp', $noHpPengirim)
                ->whereNotNull('pickup_point_code')
                ->orderBy('updated_at', 'desc')
                ->first();

            $existingPickupCode = $kontak->pickup_point_code ?? ($userSama->pickup_point_code ?? null);

            // 3. Wajib Update jika sudah punya kode lokal
            if ($existingPickupCode) {
                if ($this->findPickupPoint($existingPickupCode)) {
                    \Illuminate\Support\Facades\Log::info("LOG: [EXECUTE] Ditemukan kode lokal {$existingPickupCode}. Hit API UPDATE sinkronisasi...");
                    if ($this->updatePickupPoint($existingPickupCode, $payloadData)) {
                        $pickupPointCode = $existingPickupCode;
                    } else {
                        throw new \Exception('Gagal memperbarui sinkronisasi alamat jemput di server logistik.');
                    }
                } else {
                    if ($kontak) $kontak->update(['pickup_point_code' => null]);
                    if ($userSama) \App\Models\User::where($userSama->getKeyName(), $userSama->id)->update(['pickup_point_code' => null]);
                }
            }

            // 4. Insert Baru & Tangkap Error 01/02
            if (!$pickupPointCode) {
                \Illuminate\Support\Facades\Log::info("LOG: [API INSERT] REQUEST Insert Baru:", $payloadData);

                $pickupResponse = \Illuminate\Support\Facades\Http::timeout(15)->withToken($config->token)
                    ->post("{$config->base_url}/api/pickup-point/insert", $payloadData);

                $pickupResult = $pickupResponse->json();
                \Illuminate\Support\Facades\Log::info("LOG: [API INSERT] FULL RESPONSE API:", $pickupResult ?? []);

                $codeFromApi = $pickupResult['data']['pickup_point_code'] ?? $pickupResult['pickup_point_code'] ?? null;

                // SELAMATKAN KODE: Berhasil Insert (00) ATAUPUN Terdaftar (01/02)
                if (isset($pickupResult['rc']) && in_array($pickupResult['rc'], ['00', '01', '02'])) {
                    if (!empty($codeFromApi)) {
                        $pickupPointCode = (string) $codeFromApi;

                        if (in_array($pickupResult['rc'], ['01', '02'])) {
                            \Illuminate\Support\Facades\Log::warning("LOG: [API INSERT] Menyelamatkan kode {$pickupPointCode} dari respon error {$pickupResult['rc']}. Memaksa Update...");
                            $this->updatePickupPoint($pickupPointCode, $payloadData);
                        }
                    } else {
                        throw new \Exception(($pickupResult['rd'] ?? 'Terdaftar') . ' (Server pusat menahan kode akses. Solusi: Gunakan nomor HP lain)');
                    }
                } else {
                    throw new \Exception('Gagal mendaftarkan alamat jemput: ' . ($pickupResult['rd'] ?? 'Unknown Error API'));
                }

                // SIMPAN KE DATABASE LOKAL
                if ($pickupPointCode) {
                    if ($userSama) {
                        \App\Models\User::where($userSama->getKeyName(), $userSama->id)->update([
                            'pickup_point_code' => $pickupPointCode, 'address_detail' => $alamatPengirim, 'nama_lengkap' => $namaPengirim
                        ]);
                    }
                    if ($user) {
                        \App\Models\Kontak::updateOrCreate(
                            ['no_hp' => $noHpPengirim, 'user_id' => auth()->id()],
                            ['nama' => $namaPengirim, 'alamat' => $alamatPengirim, 'district_id' => $districtId, 'tipe' => 'Pengirim', 'pickup_point_code' => $pickupPointCode, 'email' => $emailPengirim]
                        );
                    }
                }
            }

            \Illuminate\Support\Facades\Redis::setex($redisPickupKey, 2592000, $pickupPointCode);
        }

        // ==========================================================
        // EKSEKUSI PEMBUATAN PESANAN (CREATE ORDER)
        // ==========================================================
        $isSenderPp = $requestData ? (int) $requestData->input('is_sender_pp', 1) : 1;
        $qtyInput = $requestData ? (string) $requestData->input('qty', 1) : "1";
        $serviceCode = $requestData ? (string) $requestData->service_code_terpilih : (string) $pesanan->layanan;

        $isCod = in_array(strtolower($pesanan->metode_pembayaran), ['cod', 'codbarang', 'cod_barang', 'cod_ongkir']);
        $codValue = $isCod ? ($requestData ? (int) $requestData->grand_total : 0) : 0;

        $beratGram = (int) $pesanan->berat_gram;
        $weightApi = $beratGram > 0 ? $beratGram : 1000;

        $orderPayload = [
            'service_code'      => $serviceCode,
            'reff_client_id'    => $pesanan->order_id,
            'pickup_point_code' => $pickupPointCode,
            'origin_id'         => $districtId,
            'destination_id'    => (int) $destination->district_id,
            'weight'            => (string) $weightApi,
            'qty'               => (string) $qtyInput,
            'length'            => (int) ($pesanan->panjang_cm > 0 ? $pesanan->panjang_cm : 10),
            'width'             => (int) ($pesanan->lebar_cm > 0 ? $pesanan->lebar_cm : 10),
            'height'            => (int) ($pesanan->tinggi_cm > 0 ? $pesanan->tinggi_cm : 10),
            'description'       => (string) $pesanan->deskripsi_barang,
            'remarks'           => (string) $pesanan->kategori_barang,
            'price'             => (int) ($pesanan->nilai_barang > 0 ? $pesanan->nilai_barang : 1000),
            'is_cod'            => $isCod,
            'cod_value'         => $codValue,
            'is_sender_pp'      => (int) $isSenderPp,
            'is_insurance'      => (bool) $pesanan->asuransi,
            'from' => [
                'name'    => (string) trim($pesanan->pengirim_nama),
                'phone'   => (string) trim($pesanan->pengirim_hp),
                'address' => (string) trim($pesanan->pengirim_alamat),
            ],
            'to' => [
                'name'    => (string) trim($pesanan->penerima_nama),
                'phone'   => (string) trim($pesanan->penerima_hp),
                'address' => (string) trim($pesanan->penerima_alamat),
            ],
            'commodity' => (string) $pesanan->kategori_barang,
        ];

        $orderResponse = \Illuminate\Support\Facades\Http::timeout(15)
            ->withToken($config->token)
            ->post("{$config->base_url}/api/order", $orderPayload);

        $orderResult = $orderResponse->json();

        if (!$orderResponse->successful() || !isset($orderResult['rc']) || $orderResult['rc'] !== '00') {
            $errorMsg = $orderResult['rd'] ?? 'Unknown Error';

            if (stripos($errorMsg, 'Pickup Point Code tidak ditemukan') !== false) {
                 \Illuminate\Support\Facades\Redis::del($redisPickupKey);
                 throw new \Exception("Sistem logistik pusat mereset data. Silakan klik 'SUBMIT KIRIM SEKARANG' satu kali lagi untuk memulihkan sesi Anda.");
            }

            throw new \Exception('Gagal membuat pesanan di server logistik: ' . $errorMsg);
        }

        $awb    = $orderResult['data']['awb'] ?? 'AWB-PENDING';
        $reff_1 = $orderResult['data']['reff_1'] ?? null;
        $reff_2 = $orderResult['data']['reff_2'] ?? null;

        return [
            'success' => true,
            'awb'     => $awb,
            'reff_1'  => $reff_1,
            'reff_2'  => $reff_2,
            'pickup'  => $pickupPointCode
        ];
    }


   private function getAutokirimConfig()
    {
        // 1. Ambil mode dari database
        $rawMode = \App\Models\Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
        $mode = strtolower(trim($rawMode));

        if (!in_array($mode, ['sandbox', 'production'])) {
            $mode = 'sandbox'; // Fallback aman
        }

        // 2. Ambil Base URL & Token sesuai mode aktif
        $baseUrl = \App\Models\Api::getValue('AUTOKIRIM_BASE_URL', $mode);
        $token = \App\Models\Api::getValue('AUTOKIRIM_TOKEN', $mode);

        // 3. Fallback Base URL jika di database kosong
        if (empty($baseUrl)) {
            $baseUrl = ($mode === 'production') ? 'https://api.autokirim.com' : 'https://api-dev.autokirim.com';
        }

        // 4. Fallback Token jika di database kosong tapi modenya aktif
        if (empty($token) && $mode === 'production') {
            \Illuminate\Support\Facades\Log::warning("LOG: [API CONFIG] Token Production Kosong! Turun ke Sandbox.");
            $mode = 'sandbox';
            $baseUrl = \App\Models\Api::getValue('AUTOKIRIM_BASE_URL', 'sandbox', 'https://api-dev.autokirim.com');
            $token = \App\Models\Api::getValue('AUTOKIRIM_TOKEN', 'sandbox', '');
        }

        return (object) [
            'mode'     => strtoupper($mode),
            'base_url' => rtrim($baseUrl, '/'),
            'token'    => $token
        ];
    }

    /**
     * =========================================================================
     * PUSH NOTIFICATION KE BROWSER ADMIN & EXPO (FCM v1 + REDIS)
     * =========================================================================
     */
    private function notifyAdminOrderBaru($orderId, $customerName, $kurir, $layanan)
    {
        try {
            $adminId = 4;
            $admin = \Illuminate\Support\Facades\DB::table('Pengguna')
                        ->where('id_pengguna', $adminId)
                        ->select('fcm_token', 'fcm_token_debug')
                        ->first();

            if (!$admin) return;

            // 1. DATA NOTIFIKASI
            $notifId = 'NTF-' . time() . '-' . rand(100, 999);
            $title = '📦 Pesanan Baru Masuk!';
            $body = "Pelanggan {$customerName} membuat pesanan {$orderId} via {$kurir} {$layanan}.";

            $notifData = [
                'notif_id' => $notifId,
                'order_id' => $orderId,
                'title'    => $title,
                'body'     => $body,
                'time'     => now()->timezone('Asia/Jakarta')->format('d M Y H:i:s'),
                'read'     => false
            ];

            // 2. SIMPAN KE REDIS (Untuk Riwayat Notifikasi di Header Bell)
            $redisKey = "admin_notifications_{$adminId}";
            \Illuminate\Support\Facades\Redis::lpush($redisKey, json_encode($notifData));
            \Illuminate\Support\Facades\Redis::ltrim($redisKey, 0, 99); // Batasi maksimal 100 notif

            // 3. PUSH KE FIREBASE CLOUD MESSAGING (FCM HTTP v1)
            $accessToken = $this->getGoogleAccessToken(); // Menggunakan helper dari controller Anda
            $projectId = 'sancaka-express'; // Sesuaikan dengan Project ID Firebase Anda

            $tokensToTry = [];
            if (!empty($admin->fcm_token)) $tokensToTry[] = $admin->fcm_token;
            if (!empty($admin->fcm_token_debug)) $tokensToTry[] = $admin->fcm_token_debug;

            if ($accessToken && count($tokensToTry) > 0) {
                foreach ($tokensToTry as $tokenStr) {
                    // ===================================================
                    // 🚀 TARUH KODE ANDA DI SINI
                    // ===================================================
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type'  => 'application/json',
                    ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $tokenStr,
                            'notification' => [
                                'title' => '📦 Pesanan Baru Masuk!',
                                'body'  => "Ada pesanan baru $orderId dari {$customerName}."
                            ],
                            'webpush' => [
                                'fcm_options' => [
                                    'link' => url('/admin/pesanan-autokirim')
                                ]
                            ],
                            'data' => [
                                'action'   => 'new_order',
                                'order_id' => (string) $orderId
                            ]
                        ]
                    ]);
                    // ===================================================

                    if ($response->successful()) {
                        \Illuminate\Support\Facades\Log::info("LOG: [NOTIF ADMIN] Berhasil kirim FCM Realtime ke Browser Admin (ID 4) untuk Order $orderId");
                        break; // Stop jika 1 token sudah berhasil agar tidak double
                    }
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG: [NOTIF ADMIN ERROR] " . $e->getMessage());
        }
    }

    /**
     * =========================================================================
     * PUSH NOTIFICATION KE APLIKASI MOBILE (EXPO REACT NATIVE)
     * =========================================================================
     */
    private function notifyExpoOrderBaru($orderId, $customerId)
    {
        try {
            $pushMessages = [];

            // 1. Notif ke HP Pelanggan (Jika pelanggan punya aplikasi Sancaka)
            if ($customerId) {
                $customer = \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', $customerId)->first();
                if ($customer && !empty($customer->expo_token)) {
                    $pushMessages[] = [
                        'to'    => $customer->expo_token,
                        'title' => '📦 Pesanan Berhasil!',
                        'body'  => "Hore! Pesanan $orderId Anda berhasil dibuat & sedang diproses sistem.",
                        'sound' => 'default',
                        'data'  => ['order_id' => $orderId, 'action' => 'new_order_created']
                    ];
                }
            }

            // 2. Notif ke HP Admin Sancaka (ID 4)
            $admin = \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', 4)->first();
            if ($admin && !empty($admin->expo_token)) {
                $pushMessages[] = [
                    'to'    => $admin->expo_token,
                    'title' => '🔔 Pesanan Autokirim Masuk!',
                    'body'  => "Ada pesanan baru $orderId masuk dari Website.",
                    'sound' => 'default',
                    'data'  => ['order_id' => $orderId, 'action' => 'admin_new_order']
                ];
            }

            // 3. Eksekusi tembak API Expo
            if (!empty($pushMessages)) {
                \Illuminate\Support\Facades\Http::withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://exp.host/--/api/v2/push/send', $pushMessages);

                \Illuminate\Support\Facades\Log::info("LOG LOG: [EXPO PUSH] Notifikasi sukses dikirim ke App Mobile untuk Order $orderId");
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG LOG: [EXPO PUSH ERROR] " . $e->getMessage());
        }
    }

    /**
     * Helper: Generate Access Token FCM V1 (Google Auth)
     */
    private function getGoogleAccessToken()
    {
        return \Illuminate\Support\Facades\Cache::remember('fcm_access_token_web', 3000, function () {
            $jsonKeyPath = storage_path('app/firebase-auth.json');

            if (!file_exists($jsonKeyPath)) {
                \Illuminate\Support\Facades\Log::error("FCM Token: File firebase-auth.json tidak ditemukan di storage/app/");
                return null;
            }

            $keyData = json_decode(file_get_contents($jsonKeyPath), true);
            if (!$keyData || !isset($keyData['private_key'])) {
                \Illuminate\Support\Facades\Log::error("FCM Token: Format JSON firebase-auth.json tidak valid.");
                return null;
            }

            try {
                // JALUR NINJA (Raw JWT - Cepat tanpa perlu install library tambahan)
                $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
                $now = time();
                $claim = json_encode([
                    'iss' => $keyData['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'exp' => $now + 3600,
                    'iat' => $now
                ]);

                $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
                $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));

                $signature = '';
                openssl_sign($base64UrlHeader . '.' . $base64UrlClaim, $signature, $keyData['private_key'], 'SHA256');
                $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

                $jwt = $base64UrlHeader . '.' . $base64UrlClaim . '.' . $base64UrlSignature;

                $response = \Illuminate\Support\Facades\Http::timeout(5)->asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ]);

                if ($response->successful() && $response->json('access_token')) {
                    return $response->json('access_token');
                }
            } catch (\Throwable $th) {
                \Illuminate\Support\Facades\Log::warning("FCM Token: Generate token gagal - " . $th->getMessage());
            }

            return null;
        });
    }

    /**
     * Menyimpan Token FCM dari Browser Web Admin (Berbasis Session Auth)
     */
    public function saveFcmTokenWeb(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // Pastikan hanya Admin / User ID 4 yang boleh menyimpan token via Web
            if ($user->id_pengguna != 4 && $user->role !== 'Admin') {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $request->validate([
                'fcm_token' => 'required|string'
            ]);

            // Bersihkan token dari akun lain agar tidak terduplikasi (Leak Prevention)
            \Illuminate\Support\Facades\DB::table('Pengguna')
                ->where('fcm_token', $request->fcm_token)
                ->update(['fcm_token' => null]);

            // Simpan ke akun Admin yang sedang login
            \Illuminate\Support\Facades\DB::table('Pengguna')
                ->where('id_pengguna', $user->id_pengguna)
                ->update(['fcm_token' => $request->fcm_token]);

            return response()->json(['success' => true, 'message' => 'FCM Token (Web) berhasil disimpan.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal simpan FCM Token Web: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

}
