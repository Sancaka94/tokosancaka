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
            'Pakaian / Fashion',
            'Elektronik & Gadget',
            'Dokumen / Surat',
            'Makanan Kering / Herbal',
            'Kosmetik & Kecantikan',
            'Aksesoris & Sparepart',
            'Lainnya'
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

    // ==========================================
    // AREA ADMIN: TABEL RIWAYAT TRANSAKSI LENGKAP
    // ==========================================
    public function indexAdmin(Request $request)
    {
        $query = PesananAutokirim::query();

        // PENCARIAN (Berdasarkan Resi, Order ID, Nama, HP)
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

        // FILTER STATUS
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // FILTER TANGGAL
        if ($request->filled('date_range')) {
            $dates = explode(' to ', str_replace([' - ', ' s.d. '], ' to ', $request->date_range));
            if (count($dates) >= 2) {
                $query->whereBetween('created_at', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
            } elseif (count($dates) == 1) {
                $query->whereDate('created_at', trim($dates[0]));
            }
        }

        // STATISTIK CARD
        $cardQuery = clone $query;
        $totalTransaksi = $cardQuery->count();
        $totalOngkir    = $cardQuery->sum('ongkir');
        $totalBerhasil  = (clone $cardQuery)->whereNotIn('status', ['menunggu_pembayaran', 'gagal', 'batal'])->count();
        $totalPending   = (clone $cardQuery)->whereIn('status', ['menunggu_pembayaran', 'gagal'])->count();

        $pesanan = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.pesanan_autokirim.index', compact(
            'pesanan', 'totalTransaksi', 'totalOngkir', 'totalBerhasil', 'totalPending'
        ));
    }

    // ==========================================
    // AREA CUSTOMER: TABEL RIWAYAT TRANSAKSI
    // ==========================================
    public function indexCustomer(Request $request)
    {
        // KUNCI UTAMA: Hanya ambil data milik user yang sedang login
        $query = PesananAutokirim::where('user_id', auth()->id());

        // PENCARIAN
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('awb_number', 'like', "%{$search}%")
                  ->orWhere('penerima_nama', 'like', "%{$search}%")
                  ->orWhere('penerima_hp', 'like', "%{$search}%");
            });
        }

        // FILTER STATUS
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // FILTER TANGGAL
        if ($request->filled('date_range')) {
            $dates = explode(' to ', str_replace([' - ', ' s.d. '], ' to ', $request->date_range));
            if (count($dates) >= 2) {
                $query->whereBetween('created_at', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
            } elseif (count($dates) == 1) {
                $query->whereDate('created_at', trim($dates[0]));
            }
        }

        // STATISTIK CARD KHUSUS USER LOGIN
        $cardQuery = clone $query;
        $totalTransaksi = $cardQuery->count();
        $totalOngkir    = $cardQuery->sum('ongkir');
        $totalBerhasil  = (clone $cardQuery)->whereNotIn('status', ['menunggu_pembayaran', 'gagal', 'batal'])->count();
        $totalPending   = (clone $cardQuery)->whereIn('status', ['menunggu_pembayaran', 'gagal'])->count();

        $pesanan = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('customer.pesanan_autokirim.index', compact(
            'pesanan', 'totalTransaksi', 'totalOngkir', 'totalBerhasil', 'totalPending'
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
                'panjang_cm'        => $request->panjang_cm ? (int) $request->panjang_cm : 1,
                'lebar_cm'          => $request->lebar_cm ? (int) $request->lebar_cm : 1,
                'tinggi_cm'         => $request->tinggi_cm ? (int) $request->tinggi_cm : 1,
                'asuransi'          => $isInsurance ? 1 : 0,
                'nilai_barang'      => $finalPrice,
                'kurir'             => $request->kurir_terpilih,
                'layanan'           => $request->layanan_terpilih,
                'ongkir'            => $totalTagihan,
                'awb_number'        => null,
                'metode_pembayaran' => $paymentMethod,
                'status'            => 'waiting_payment'
            ]);

            $paymentUrl = null;

            if (in_array($paymentMethod, ['potong_saldo', 'dana_binding'])) {
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
                    'pickup_point_code' => $awbResult['pickup'], // 🔥 SIMPAN KE DATABASE
                    'status'            => 'booking_created'
                ]);

                DB::commit();
                return redirect()->route('customer.pesanan-autokirim.create')->with('success', "Pesanan Berhasil! Nomor Resi: {$awbResult['awb']} (Metode: {$paymentMethod})");
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

        Log::info("LOG: [API AUTOKIRIM - INSERT PICKUP POINT] REQUEST:", $pickupPayload);

        $pickupResponse = Http::timeout(15)
            ->withToken($this->token)
            ->post("{$this->baseUrl}/api/pickup-point/insert", $pickupPayload);

        $pickupResult = $pickupResponse->json();
        Log::info("LOG: [API AUTOKIRIM - INSERT PICKUP POINT] RESPONSE:", $pickupResult ?? []);

        if (!$pickupResponse->successful() || empty($pickupResult['data']['pickup_point_code'])) {
            throw new Exception('Gagal mendaftarkan alamat jemput ke server logistik: ' . ($pickupResult['rd'] ?? 'Unknown Error'));
        }

        $pickupPointCode = (string) $pickupResult['data']['pickup_point_code'];
        $isSenderPp = $requestData ? (int) $requestData->input('is_sender_pp', 1) : 1;
        $serviceCode = $requestData ? (string) $requestData->service_code_terpilih : (string) $pesanan->layanan;
        $qtyInput = $requestData ? (string) $requestData->input('qty', '1') : '1';

        $orderPayload = [
            'service_code'      => $serviceCode,
            'reff_client_id'    => $pesanan->order_id,
            'pickup_point_code' => $pickupPointCode,
            'origin_id'         => (int) $origin->district_id,
            'destination_id'    => (int) $destination->district_id,
            'weight'            => (string) $pesanan->berat_gram,
            'qty'               => $qtyInput,
            'length'            => (int) $pesanan->panjang_cm,
            'width'             => (int) $pesanan->lebar_cm,
            'height'            => (int) $pesanan->tinggi_cm,
            'description'       => (string) $pesanan->deskripsi_barang,
            'remarks'           => (string) $pesanan->kategori_barang,
            'is_cod'            => false,
            'price'             => (int) $pesanan->nilai_barang > 0 ? (int) $pesanan->nilai_barang : 10000,
            'cod_value'         => 0,
            'is_sender_pp'      => $isSenderPp,
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
            'commodity' => ""
        ];

        Log::info("LOG: [API AUTOKIRIM - CREATE ORDER] REQUEST:", $orderPayload);

        $orderResponse = Http::timeout(15)
            ->withToken($this->token)
            ->post("{$this->baseUrl}/api/order", $orderPayload);

        $orderResult = $orderResponse->json();
        Log::info("LOG: [API AUTOKIRIM - CREATE ORDER] RESPONSE:", $orderResult ?? []);

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
                    'name'     => "Ongkos Kirim Autokirim ({$pesanan->kurir})",
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
                'orderTitle'          => "Ongkos Kirim Autokirim {$pesanan->order_id}",
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
            Log::info("LOG: [DANA PG RESPONSE]", $result ?? []);

            if ($response->successful() && isset($result['body']['checkoutUrl'])) {
                return $result['body']['checkoutUrl'];
            }

            Log::error("LOG: [DANA PG CREATE ORDER ERROR]", $result ?? []);
            return null;
        } catch (\Exception $e) {
            Log::error("LOG: [DANA PG EXCEPTION] " . $e->getMessage());
            return null;
        }
    }

    private function _processDanaBindingCharge($pesanan, $total)
    {
        $user = User::find(auth()->id());
        if (!$user || empty($user->dana_token)) {
            throw new Exception("Akun Anda belum mengikat (bind) token DANA. Silahkan hubungkan akun DANA terlebih dahulu di pengaturan profil.");
        }

        $mode = Api::getValue('DANA_MODE', 'global', 'sandbox');
        $baseUrl = $mode === 'production'
            ? 'https://api.dana.id/v1/acquirer/directdebit/pay.htm'
            : 'https://api-sandbox.dana.id/v1/acquirer/directdebit/pay.htm';

        $merchantId = Api::getValue('DANA_MERCHANT_ID', $mode);
        $secretKey  = Api::getValue('DANA_SECRET_KEY', $mode);

        if (empty($merchantId) || empty($secretKey)) {
            throw new Exception("Konfigurasi API DANA belum lengkap di database.");
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
                'orderTitle'      => "Bayar Ongkir Instan {$pesanan->order_id}",
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
            Log::info("LOG: [DANA BINDING RESPONSE]", $result ?? []);

            if ($response->successful() && isset($result['body']['resultInfo']['resultCode']) && $result['body']['resultInfo']['resultCode'] === 'SUCCESS') {
                Log::info("LOG: [DANA BINDING SUCCESS] Order ID: {$pesanan->order_id}");
                return true;
            }

            $errorMessage = $result['body']['resultInfo']['resultMsg'] ?? 'Gagal memotong saldo DANA Binding Anda.';
            Log::error("LOG: [DANA BINDING FAILED]", $result ?? []);
            throw new Exception($errorMessage);
        } catch (\Exception $e) {
            Log::error("LOG: [DANA BINDING EXCEPTION] " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    // ==========================================
    // AREA ADMIN: AKSI TOMBOL
    // ==========================================

    // 1. Cetak Resi (PDF / Print Image)
    public function cetakResi($id)
    {
        $pesanan = PesananAutokirim::findOrFail($id);
        return view('admin.pesanan_autokirim.cetak_resi', compact('pesanan'));
    }

    // 2. Tombol Cancel Pesanan
    public function cancelOrder($id)
    {
        $pesanan = PesananAutokirim::findOrFail($id);

        // Kunci tombol: Hanya bisa batal jika statusnya baru dibuat / menunggu pembayaran
        if (in_array($pesanan->status, ['booking_created', 'menunggu_pembayaran'])) {
            $pesanan->update(['status' => 'batal']);
            return redirect()->back()->with('success', 'Pesanan berhasil dibatalkan.');
        }

        return redirect()->back()->with('error', 'Pesanan tidak dapat dibatalkan karena sudah dalam perjalanan atau diproses ekspedisi.');
    }

    // 3. Hapus Satuan (Icon Sampah)
    public function destroy($id)
    {
        PesananAutokirim::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Data pesanan berhasil dihapus.');
    }

    // 4. Hapus Massal (Bulk Destroy)
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        if (!empty($ids)) {
            PesananAutokirim::whereIn('id', $ids)->delete();
            return redirect()->back()->with('success', count($ids) . ' pesanan berhasil dihapus secara massal.');
        }
        return redirect()->back()->with('error', 'Pilih minimal satu pesanan untuk dihapus.');
    }

    // ==========================================
    // AREA WEBHOOK: MENERIMA UPDATE STATUS DARI AUTOKIRIM
    // ==========================================
    public function handleWebhook(Request $request)
    {
        Log::info('WEBHOOK AUTOKIRIM DITERIMA:', $request->all());

        // Parameter sesuai dokumentasi Autokirim
        $refId = $request->input('ref_id'); // ID dari sistem Sancaka (order_id)
        $awb = $request->input('awb_number');
        $status = $request->input('transactions_stats'); // CREATE, MANIFESTED, DELIVERED, dll
        $desc = $request->input('transactions_desc');

        if (!$refId && !$awb) {
            return response()->json(['success' => false, 'message' => 'Invalid Payload: ref_id atau awb_number tidak ditemukan'], 400);
        }

        // Cari berdasarkan order_id atau awb_number
        $pesanan = PesananAutokirim::where('order_id', $refId)
            ->orWhere('awb_number', $awb)
            ->first();

        if ($pesanan) {
            // Update status pesanan di database Sancaka
            $pesanan->update([
                'status' => $status
                // Jika Anda punya kolom keterangan di DB, Anda bisa juga simpan $desc ke sana
            ]);

            Log::info("WEBHOOK AUTOKIRIM SUKSES: Update order {$pesanan->order_id} menjadi status: {$status} - {$desc}");
            return response()->json(['success' => true, 'message' => 'Status pesanan berhasil diperbarui'], 200);
        }

        Log::warning("WEBHOOK AUTOKIRIM GAGAL: Pesanan tidak ditemukan untuk ref_id: {$refId} / awb: {$awb}");
        return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
    }

}
