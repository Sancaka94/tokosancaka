<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\PesananAutokirim;
use App\Models\AutoKirim;
use App\Models\Api;
use App\Models\User;
use App\Helpers\ShippingHelper;
use App\Services\DokuJokulService;
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
        $kategoriBarang = [
            'Pakaian / Fashion', 'Elektronik & Gadget', 'Dokumen / Surat',
            'Makanan Kering / Herbal', 'Kosmetik & Kecantikan', 'Aksesoris & Sparepart', 'Lainnya'
        ];

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
            ],
            [
                'id'          => 'tripay_qris',
                'nama'        => 'QRIS by Tripay (Gopay, OVO, Dana, ShopeePay)',
                'icon'        => 'fa-solid fa-qrcode text-green-600',
                'deskripsi'   => 'Scan barcode via aplikasi e-wallet atau m-banking'
            ],
            [
                'id'          => 'tripay_va_bca',
                'nama'        => 'BCA Virtual Account (Tripay)',
                'icon'        => 'fa-solid fa-building-columns text-blue-800',
                'deskripsi'   => 'Konfirmasi otomatis 24/7'
            ],
            [
                'id'          => 'tripay_va_mandiri',
                'nama'        => 'Mandiri Virtual Account (Tripay)',
                'icon'        => 'fa-solid fa-building-columns text-yellow-600',
                'deskripsi'   => 'Konfirmasi otomatis 24/7'
            ],
            [
                'id'          => 'tripay_va_bri',
                'nama'        => 'BRI Virtual Account (BRIVA by Tripay)',
                'icon'        => 'fa-solid fa-building-columns text-blue-500',
                'deskripsi'   => 'Konfirmasi otomatis 24/7'
            ]
        ];

        return view('customer.pesanan_autokirim.create', compact('kategoriBarang', 'metodePembayaran'));
    }

    public function indexAdmin(Request $request)
    {
        $query = PesananAutokirim::with('user')->whereHas('user', function($q) {
            $q->where('role', 'agent');
        });

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
        $querySukses    = clone $query->whereNotIn('status', $statusPending);

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
        $kurir = strtolower($pesanan->kurir ?? '');
        $layanan = strtolower($pesanan->layanan ?? '');
        $isCod = in_array(strtolower($pesanan->metode_pembayaran), ['cod', 'codbarang']);

        $bestMatch = null;
        $highestScore = -1;

        foreach ($rates as $rate) {
            $serviceStr = strtolower($rate->service);
            $score = 0;

            $kurirMapping = [
                'j&t' => 'jnt', 'jne' => 'jne', 'id express' => 'idx', 'sicepat' => 'sicepat', 'sap' => 'sap', 'ninja' => 'ninja', 'anteraja' => 'anteraja'
            ];

            $isBrandMatched = false;
            foreach ($kurirMapping as $key => $val) {
                if (str_contains($kurir, $key) && str_contains($serviceStr, $val)) {
                    $score += 5;
                    $isBrandMatched = true;
                    break;
                }
            }

            if (!$isBrandMatched) continue;

            $layananKeys = explode(' ', str_replace(['-', ' '], ' ', $layanan));
            foreach ($layananKeys as $k) {
                if (strlen($k) > 2 && str_contains($serviceStr, $k)) $score += 3;
            }

            if ($isCod && str_contains($serviceStr, 'cod')) {
                $score += 4;
            } elseif (!$isCod && !str_contains($serviceStr, 'cod')) {
                $score += 2;
            }

            if ($score > $highestScore && $score > 0) {
                $highestScore = $score;
                $bestMatch = $rate;
            }
        }

        $persenCashbackPusat = 0;
        if ($bestMatch) {
            $persenCashbackPusat = floatval($bestMatch->cashback ?? 0);
        }

        $totalCashback = $pesanan->ongkir * ($persenCashbackPusat / 100);
        $komisiAgen    = $totalCashback * 0.40;
        $labaSancaka   = $totalCashback * 0.60;

        return (object)[
            'total_cashback'  => $totalCashback,
            'komisi_agen'     => $komisiAgen,
            'laba_sancaka'    => $labaSancaka,
            'persen_cashback' => $persenCashbackPusat
        ];
    }

    public function indexCustomer(Request $request)
    {
        $query = PesananAutokirim::where('user_id', auth()->id());

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
        $querySukses   = clone $query->whereNotIn('status', $statusPending);

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
            $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
            $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
            $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

            // KITA CASTING SEMUA NUMBER MENJADI (int) AGAR JSONNYA BENAR
            $payload = [
                'origin_id'         => (int) $origin_id,
                'destination_id'    => (int) $destination_id,
                'weight'            => (string) $weightApi, // INI KUNCINYA: (int) menghilangkan tanda kutip pada JSON API
                'length'            => (int) ($request->panjang_cm > 0 ? $request->panjang_cm : 10),
                'width'             => (int) ($request->lebar_cm > 0 ? $request->lebar_cm : 10),
                'height'            => (int) ($request->tinggi_cm > 0 ? $request->tinggi_cm : 10),
                'is_sender_pp'      => (int) $isSenderPp,
                'additional'        => [
                    'commodity'     => $commodityCode
                ]
            ];

            Log::info("LOG LOG: [API AUTOKIRIM - CEK ONGKIR] REQUEST:", $payload);

            $response = Http::timeout(15)
                ->withToken($token)
                ->post("{$baseUrl}/api/v2/check-price", $payload);

            $result = $response->json();

            Log::info("LOG LOG: [API AUTOKIRIM - CEK ONGKIR] RESPONSE:", $result ?? []);

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
            Log::error("LOG LOG: [API AUTOKIRIM - CEK ONGKIR] ERROR: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kendala jaringan saat menghubungi server logistik.']);
        }
    }

    public function store(Request $request)
    {
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
            'ongkir_terpilih'       => 'required|numeric|min:1'
        ], [
            'pengirim_alamat.min' => 'Alamat Pengirim terlalu pendek! Wajib menuliskan nama jalan, nomor rumah/gedung, atau RT/RW (Min. 15 karakter).',
            'penerima_alamat.min' => 'Alamat Penerima terlalu pendek! Wajib menuliskan nama jalan, nomor rumah/gedung, atau RT/RW (Min. 15 karakter).',
        ]);

        $origin = AutoKirim::where('district_id', $request->pengirim_district_id)->first();
        $destination = AutoKirim::where('district_id', $request->penerima_district_id)->first();

        if (!$origin || !$destination) {
            return redirect()->back()->withInput()->with('error', 'Wilayah pengirim atau penerima tidak valid.');
        }

        $localOrderId = (string) (date('ymdHis') . mt_rand(1000, 9999));
        $totalTagihan = (int) $request->ongkir_terpilih;
        $paymentMethod = $request->metode_pembayaran;

        $hargaBarangInput = (int) $request->nilai_barang;
        $finalPrice = $hargaBarangInput > 0 ? $hargaBarangInput : 10000;
        $isInsurance = $request->has('asuransi');

        DB::beginTransaction();
        try {
            $rates = DB::table('data_auto_kirims')->get();

            $kalkulasiData = (object) [
                'kurir' => $request->kurir_terpilih,
                'layanan' => $request->layanan_terpilih,
                'metode_pembayaran' => $paymentMethod,
                'ongkir' => $totalTagihan
            ];

            $profit = $this->hitungProfit($kalkulasiData, $rates);

            $pesanan = PesananAutokirim::create([
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
                'panjang_cm'        => $request->panjang_cm ? (int) $request->panjang_cm : 10,
                'lebar_cm'          => $request->lebar_cm ? (int) $request->lebar_cm : 10,
                'tinggi_cm'         => $request->tinggi_cm ? (int) $request->tinggi_cm : 10,
                'asuransi'          => $isInsurance ? 1 : 0,
                'nilai_barang'      => $finalPrice,
                'kurir'             => $request->kurir_terpilih,
                'layanan'           => $request->layanan_terpilih,
                'ongkir'            => $totalTagihan,
                'awb_number'        => null,
                'metode_pembayaran' => $paymentMethod,
                'status'            => 'waiting_payment',
                'total_cashback'    => $profit->total_cashback,
                'laba_sistem'       => $profit->laba_sancaka,
                'komisi_agen'       => $profit->komisi_agen
            ]);

            $paymentUrl = null;

            if (in_array($paymentMethod, ['potong_saldo', 'dana_binding', 'cod_barang', 'cod_ongkir'])) {

                if ($paymentMethod === 'potong_saldo') {
                    $user = User::find(auth()->id());
                    if (!$user) {
                        throw new Exception('Anda harus login terlebih dahulu untuk menggunakan metode Potong Saldo.');
                    }
                    if ($user->saldo < $totalTagihan) {
                        throw new Exception('Saldo akun Anda tidak mencukupi. Silahkan isi ulang atau pilih metode pembayaran lainnya.');
                    }
                    $user->decrement('saldo', $totalTagihan);
                    Log::info("LOG: [POTONG SALDO SUKSES] User ID {$user->id} dipotong Rp {$totalTagihan} untuk Order ID {$localOrderId}");
                } elseif ($paymentMethod === 'dana_binding') {
                    $this->_processDanaBindingCharge($pesanan, $totalTagihan);
                }

                $awbResult = $this->_executeAutokirimApi($pesanan, $origin, $destination, $request);

                $pesanan->update([
                    'awb_number'        => $awbResult['awb'],
                    'tlc_code'          => $awbResult['tlc'],
                    'pickup_point_code' => $awbResult['pickup'],
                    'status'            => 'booking_created'
                ]);

                DB::commit();

                $metodeTampil = str_replace('_', ' ', strtoupper($paymentMethod));
                return redirect()->route('customer.pesanan-autokirim.create')->with('success', "Pesanan Berhasil! Nomor Resi: {$awbResult['awb']} (Metode: {$metodeTampil})");

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
                    $paymentUrl = $this->_createDanaPgTransaction($pesanan, $totalTagihan);
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
                    return redirect()->away($paymentUrl);
                }

                throw new Exception('URL Pembayaran tidak ditemukan dari server Payment Gateway.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG: [PESANAN AUTOKIRIM - STORE ERROR] " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function _executeAutokirimApi($pesanan, $origin, $destination, $requestData = null)
    {
        $pickupPayload = [
            'name'              => (string) trim($pesanan->pengirim_nama),
            'phone'             => (string) trim($pesanan->pengirim_hp),
            'address'           => (string) trim($pesanan->pengirim_alamat),
            'email'             => auth()->user()->email ?? 'customer@tokosancaka.com',
            'longitude'         => "",
            'latitude'          => "",
            'district_id'       => (int) $origin->district_id,
            'is_member_deposit' => false
        ];

        $pickupResponse = Http::timeout(15)
            ->withToken($this->token)
            ->post("{$this->baseUrl}/api/pickup-point/insert", $pickupPayload);

        $pickupResult = $pickupResponse->json();

        if (!$pickupResponse->successful() || empty($pickupResult['data']['pickup_point_code'])) {
            throw new Exception('Gagal mendaftarkan alamat jemput ke server logistik: ' . ($pickupResult['rd'] ?? 'Unknown Error'));
        }

        $pickupPointCode = (string) $pickupResult['data']['pickup_point_code'];

        $isSenderPp = $requestData ? (int) $requestData->input('is_sender_pp', 1) : 1;
        $qtyInput = $requestData ? (string) $requestData->input('qty', 1) : "1";
        $serviceCode = $requestData ? (string) $requestData->service_code_terpilih : (string) $pesanan->layanan;

        $isCod = in_array(strtolower($pesanan->metode_pembayaran), ['cod', 'codbarang', 'cod_barang', 'cod_ongkir']);

        $codValue = 0;
        if ($isCod) {
            if (strtolower($pesanan->metode_pembayaran) === 'cod_ongkir') {
                $codValue = (int) $pesanan->ongkir;
            } else {
                $codValue = (int) $pesanan->nilai_barang;
            }
        }

        // PASTIKAN BERAT GRAM ADALAH INTEGER MURNI SAAT CREATE ORDER
        $beratGram = (int) $pesanan->berat_gram;
        $weightApi = $beratGram > 0 ? $beratGram : 1000;

        $orderPayload = [
            'service_code'      => $serviceCode,
            'reff_client_id'    => $pesanan->order_id,
            'pickup_point_code' => $pickupPointCode,
            'origin_id'         => (int) $origin->district_id,
            'destination_id'    => (int) $destination->district_id,
            'weight'            => (string) $weightApi, // INI KUNCINYA: Integer
            'qty'               => (string) $qtyInput,
            'length'            => (int) ($pesanan->panjang_cm > 0 ? $pesanan->panjang_cm : 10),
            'width'             => (int) ($pesanan->lebar_cm > 0 ? $pesanan->lebar_cm : 10),
            'height'            => (int) ($pesanan->tinggi_cm > 0 ? $pesanan->tinggi_cm : 10),
            'description'       => (string) $pesanan->deskripsi_barang,
            'remarks'           => (string) $pesanan->kategori_barang,
            'price'             => (int) ($pesanan->nilai_barang > 0 ? $pesanan->nilai_barang : 10000),
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

        Log::info("LOG: [API AUTOKIRIM - CREATE ORDER] REQUEST:", $orderPayload);

        $orderResponse = Http::timeout(15)
            ->withToken($this->token)
            ->post("{$this->baseUrl}/api/order", $orderPayload);

        $orderResult = $orderResponse->json();

        if ($orderResponse->successful() && isset($orderResult['rc']) && $orderResult['rc'] === '00') {
            return [
                'success' => true,
                'awb'     => $orderResult['data']['awb'] ?? 'AWB-PENDING',
                'tlc'     => $orderResult['data']['reff_2'] ?? null,
                'pickup'  => $pickupPointCode
            ];
        }

        throw new Exception('Gagal membuat pesanan di server logistik: ' . ($orderResult['rd'] ?? 'Unknown Error'));
    }

    private function _createTripayTransaction($pesanan, $total, $channelCode)
    {
        $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $baseUrl = $mode === 'production'
            ? 'https://tripay.co.id/api/transaction/create'
            : 'https://tripay.co.id/api-sandbox/transaction/create';

        $apiKey       = Api::getValue('TRIPAY_API_KEY', $mode);
        $privateKey   = Api::getValue('TRIPAY_PRIVATE_KEY', $mode);
        $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', $mode);

        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            return ['success' => false, 'message' => 'Konfigurasi API Tripay belum lengkap di database.'];
        }

        $userEmail = auth()->user()->email ?? 'customer+' . Str::random(5) . '@tokosancaka.com';

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
                    'name'     => "Ongkos Kirim ({$pesanan->kurir})",
                    'price'    => $total,
                    'quantity' => 1
                ]
            ],
            'return_url'     => route('customer.pesanan-autokirim.create'),
            'expired_time'   => time() + (24 * 60 * 60),
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->order_id . $total, $privateKey),
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(30)->withoutVerifying()->post($baseUrl, $payload);
            return $response->json();
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Koneksi ke server Tripay gagal: ' . $e->getMessage()];
        }
    }

    private function _createDanaPgTransaction($pesanan, $total)
    {
        $mode = Api::getValue('DANA_MODE', 'global', 'sandbox');
        $baseUrl = $mode === 'production'
            ? 'https://api.dana.id/v1/acquirer/order/create.htm'
            : 'https://api-sandbox.dana.id/v1/acquirer/order/create.htm';

        $merchantId = Api::getValue('DANA_MERCHANT_ID', $mode);
        $secretKey  = Api::getValue('DANA_SECRET_KEY', $mode);

        if (empty($merchantId) || empty($secretKey)) {
            Log::error("LOG: [DANA PG ERROR] Merchant ID atau Secret Key belum dikonfigurasi.");
            return null;
        }

        $timestamp = date('c');
        $payload = [
            'head' => [
                'version'      => '2.0',
                'function'     => 'dana.acquirer.order.create',
                'clientId'     => $merchantId,
                'reqTime'      => $timestamp,
                'reqMsgId'     => (string) Str::uuid(),
            ],
            'body' => [
                'orderTitle'          => "Ongkir Autokirim {$pesanan->order_id}",
                'orderAmount'         => [
                    'currency' => 'IDR',
                    'value'    => (string) $total,
                ],
                'merchantTransId'     => $pesanan->order_id,
                'merchantId'          => $merchantId,
                'orderMemo'           => "Pengiriman {$pesanan->kurir} - {$pesanan->layanan}",
                'returnUrl'           => route('customer.pesanan-autokirim.create'),
                'notifyUrl'           => url('/api/callback/dana'),
                'productCode'         => '51051000100000000001',
            ]
        ];

        $jsonPayload = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $jsonPayload, $secretKey, true));

        try {
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Client-Id'     => $merchantId,
                'Signature'     => $signature,
                'Request-Time'  => $timestamp,
            ])->timeout(30)->post($baseUrl, $payload);

            $result = $response->json();

            if ($response->successful() && isset($result['body']['checkoutUrl'])) {
                return $result['body']['checkoutUrl'];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function _processDanaBindingCharge($pesanan, $total)
    {
        $user = User::find(auth()->id());
        if (!$user || empty($user->dana_token)) {
            throw new Exception("Akun Anda belum mengikat (bind) token DANA.");
        }

        $mode = Api::getValue('DANA_MODE', 'global', 'sandbox');
        $baseUrl = $mode === 'production'
            ? 'https://api.dana.id/v1/acquirer/directdebit/pay.htm'
            : 'https://api-sandbox.dana.id/v1/acquirer/directdebit/pay.htm';

        $merchantId = Api::getValue('DANA_MERCHANT_ID', $mode);
        $secretKey  = Api::getValue('DANA_SECRET_KEY', $mode);

        if (empty($merchantId) || empty($secretKey)) {
            throw new Exception("Konfigurasi API DANA belum lengkap.");
        }

        $timestamp = date('c');
        $payload = [
            'head' => [
                'version'      => '2.0',
                'function'     => 'dana.acquirer.directdebit.pay',
                'clientId'     => $merchantId,
                'reqTime'      => $timestamp,
                'reqMsgId'     => (string) Str::uuid(),
            ],
            'body' => [
                'merchantId'      => $merchantId,
                'merchantTransId' => $pesanan->order_id,
                'orderAmount'     => [
                    'currency' => 'IDR',
                    'value'    => (string) $total,
                ],
                'payMethod'       => 'BALANCE',
                'userToken'       => $user->dana_token,
                'orderTitle'      => "Bayar Ongkir {$pesanan->order_id}",
                'notifyUrl'       => url('/api/callback/dana'),
            ]
        ];

        $jsonPayload = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $jsonPayload, $secretKey, true));

        try {
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Client-Id'     => $merchantId,
                'Signature'     => $signature,
                'Request-Time'  => $timestamp,
            ])->timeout(30)->post($baseUrl, $payload);

            $result = $response->json();

            if ($response->successful() && isset($result['body']['resultInfo']['resultCode']) && $result['body']['resultInfo']['resultCode'] === 'SUCCESS') {
                return true;
            }

            $errorMessage = $result['body']['resultInfo']['resultMsg'] ?? 'Gagal memotong saldo DANA Binding Anda.';
            throw new Exception($errorMessage);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
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

            Log::info("LOG LOG: [API AUTOKIRIM - CANCEL] Memulai proses cancel untuk Order ID: {$pesanan->order_id}");

            try {
                // Panggil Konfigurasi API
                $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
                $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
                $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

                // Menggunakan order_id karena saat Create Order kita mengirimnya sebagai reff_client_id
                $payload = [
                    'reff_1' => (string) $pesanan->order_id
                ];

                Log::info("LOG LOG: [API AUTOKIRIM - CANCEL] REQUEST PAYLOAD:", $payload);

                // Hit API Cancel Autokirim
                $response = Http::timeout(15)
                    ->withToken($token)
                    ->post("{$baseUrl}/api/cancel", $payload);

                $result = $response->json();

                Log::info("LOG LOG: [API AUTOKIRIM - CANCEL] RESPONSE:", $result ?? []);

                if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {

                    // Jika sukses dari API, baru ubah status di database lokal
                    $pesanan->update(['status' => 'batal']);

                    // [TAMBAHAN WAJIB]: Kembalikan saldo jika menggunakan potong_saldo
                    if ($pesanan->metode_pembayaran === 'potong_saldo') {
                        $userToRefund = User::find($pesanan->user_id);
                        if ($userToRefund) {
                            $userToRefund->increment('saldo', $pesanan->ongkir);
                            Log::info("LOG: [REFUND SALDO] Berhasil mengembalikan Rp {$pesanan->ongkir} ke User ID {$pesanan->user_id} untuk Order ID {$pesanan->order_id}");
                        }
                    }

                    Log::info("LOG LOG: [API AUTOKIRIM - CANCEL] BERHASIL membatalkan Order ID: {$pesanan->order_id}");
                    return redirect()->back()->with('success', 'Pesanan berhasil dibatalkan di sistem logistik.');
                }

                // Jika API Autokirim menolak cancel
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

        $pesanan = PesananAutokirim::where('order_id', $refId)
            ->orWhere('awb_number', $awb)
            ->first();

        if ($pesanan) {
            // Cegah double refund dengan mengecek status lama
            $statusLama = $pesanan->status;

            $pesanan->update([
                'status' => $status
            ]);

            // Jika status baru adalah batal/gagal dan sebelumnya bukan batal
            if (in_array(strtolower($status), ['batal', 'gagal', 'cancelled']) && !in_array(strtolower($statusLama), ['batal', 'gagal', 'cancelled'])) {
                if ($pesanan->metode_pembayaran === 'potong_saldo') {
                    $userToRefund = User::find($pesanan->user_id);
                    if ($userToRefund) {
                        $userToRefund->increment('saldo', $pesanan->ongkir);
                        Log::info("LOG: [WEBHOOK REFUND] Saldo dikembalikan via Webhook untuk Order ID {$pesanan->order_id}");
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'Status pesanan berhasil diperbarui'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
    }


}
