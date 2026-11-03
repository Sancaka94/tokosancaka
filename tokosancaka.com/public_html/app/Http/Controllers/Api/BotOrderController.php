<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Pesanan;
use App\Models\User;
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use App\Models\BotOrderDraft;
use Exception;
use Throwable;

class BotOrderController extends Controller
{
    protected $kirimaja;

    public function __construct(KiriminAjaService $kirimaja)
    {
        $this->kirimaja = $kirimaja;
    }

    /**
     * Endpoint utama untuk menerima order dari Bot AI (Hermes)
     */
    public function createOrder(Request $request)
    {
        // =====================================================================
        // 1. GATEKEEPER: Validasi Keamanan (Hanya Bot Hermes yang bisa akses)
        // =====================================================================
        $botSecret = (string) config('services.bot_order.secret');
        $providedSecret = (string) $request->header('X-Bot-Secret', '');

        // Fail-closed: tanpa secret terkonfigurasi, endpoint selalu menolak.
        if ($botSecret === '' || $providedSecret === '' || !hash_equals($botSecret, $providedSecret)) {
            Log::warning('Akses API Bot ditolak (kredensial tidak valid). IP: ' . $request->ip());
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        Log::info('Menerima payload order dari Bot Hermes.', ['ip' => $request->ip(), 'fields' => array_keys($request->all())]);

        // =====================================================================
        // 2. VALIDASI DATA JSON DARI HERMES
        // =====================================================================
        $validated = $request->validate([
            'sender_name'      => 'required|string|max:100',
            'sender_phone'     => 'required|string|max:20',
            'sender_address'   => 'required|string|min:10',
            'receiver_name'    => 'required|string|max:100',
            'receiver_phone'   => 'required|string|max:20',
            'receiver_address' => 'required|string|min:10',
            'item_description' => 'required|string|max:255',
            'weight'           => 'required|numeric|min:1', // dalam gram
            'payment_method'   => 'nullable|string' // Default: TRIPAY
        ]);

        DB::beginTransaction();
        try {
            // =====================================================================
            // 3. AUTO-RESOLVE ALAMAT (Teks -> ID Kecamatan KiriminAja)
            // =====================================================================
            $senderLocation = $this->_resolveAddress($validated['sender_address']);
            $receiverLocation = $this->_resolveAddress($validated['receiver_address']);

            if (!$senderLocation || !$receiverLocation) {
                throw new Exception("Sistem tidak dapat memetakan teks alamat ke kode area ekspedisi. Pastikan nama Kecamatan/Kota jelas.");
            }

            // =====================================================================
            // 4. CEK ONGKIR TERBAIK (Otomatis pilih Regular termurah)
            // =====================================================================
            $itemValue = 10000; // Default nilai barang jika tidak disebutkan
            $shippingCost = $this->_getBestShippingCost(
                $senderLocation['district_id'],
                $senderLocation['subdistrict_id'],
                $receiverLocation['district_id'],
                $receiverLocation['subdistrict_id'],
                $validated['weight'],
                $itemValue
            );

            if (!$shippingCost) {
                throw new Exception("Tidak ada layanan ekspedisi yang tersedia untuk rute pengiriman ini.");
            }

            // =====================================================================
            // 5. SIAPKAN & SIMPAN DATA PESANAN (Tabel Pesanan)
            // =====================================================================
            // Cek apakah customer sudah terdaftar sebagai user berdasarkan No HP
            $sanitizedPhone = $this->_sanitizePhoneNumber($validated['sender_phone']);
            $customer = User::where('no_hp', $sanitizedPhone)->first();
            $customerId = $customer ? $customer->id_pengguna : null;

            do {
                $nomorInvoice = 'SCK-BOT-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

            $order = Pesanan::create([
                'nomor_invoice'           => $nomorInvoice,
                'id_pengguna_pembeli'     => $customerId,
                'customer_id'             => $customerId,
                'status'                  => 'Menunggu Pembayaran',
                'status_pesanan'          => 'Menunggu Pembayaran',
                'tanggal_pesanan'         => now(),
                'ip_address'              => $request->ip(),
                'user_agent'              => 'Bot/Hermes Proxmox',

                // Data Pengirim
                'sender_name'             => $validated['sender_name'],
                'sender_phone'            => $sanitizedPhone,
                'sender_address'          => $validated['sender_address'],
                'sender_district_id'      => $senderLocation['district_id'],
                'sender_subdistrict_id'   => $senderLocation['subdistrict_id'],
                'sender_postal_code'      => $senderLocation['postal_code'],

                // Data Penerima
                'receiver_name'           => $validated['receiver_name'],
                'receiver_phone'          => $this->_sanitizePhoneNumber($validated['receiver_phone']),
                'receiver_address'        => $validated['receiver_address'],
                'receiver_district_id'    => $receiverLocation['district_id'],
                'receiver_subdistrict_id' => $receiverLocation['subdistrict_id'],
                'receiver_postal_code'    => $receiverLocation['postal_code'],

                // Data Paket & Biaya
                'item_description'        => $validated['item_description'],
                'item_price'              => $itemValue,
                'weight'                  => $validated['weight'],
                'service_type'            => $shippingCost['service_type'],
                'expedition'              => "regular-{$shippingCost['courier']}-{$shippingCost['service_type']}-{$shippingCost['cost']}-0-0-0",
                'payment_method'          => strtoupper($validated['payment_method'] ?? 'TRIPAY'),
                'shipping_cost'           => $shippingCost['cost'],
                'price'                   => $shippingCost['cost'], // Total yang dibayar (Ongkir saja)
                'ansuransi'               => 'tidak',
                'insurance_cost'          => 0,
                'cod_fee'                 => 0,
                'item_type'               => 1 // Default Paket Umum
            ]);

            // =====================================================================
            // 6. GENERATE LINK PEMBAYARAN TRIPAY (Open Payment / Single Checkout)
            // =====================================================================
            $paymentUrl = $this->_generateTripayUrl($order, $shippingCost['cost'], $customer->email ?? null);

            if (!$paymentUrl) {
                throw new Exception("Gagal membuat tautan pembayaran di sistem Tripay.");
            }

            $order->payment_url = $paymentUrl;
            $order->save();

            DB::commit();

            // =====================================================================
            // 7. KEMBALIKAN RESPONSE KE BOT HERMES
            // =====================================================================
            Log::info("Order via Bot sukses dibuat. Invoice: {$nomorInvoice}");
            return response()->json([
                'status'       => 'success',
                'invoice'      => $order->nomor_invoice,
                'courier'      => strtoupper($shippingCost['courier']) . ' - ' . $shippingCost['service_type'],
                'total_weight' => $order->weight,
                'total_cost'   => $order->price,
                'payment_url'  => $paymentUrl
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('BotOrderController Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint READ-ONLY: cek ongkir KiriminAja.
     * Tidak membuat Pesanan, tidak menyentuh database, tidak memanggil Tripay.
     */
    public function checkShippingCost(Request $request)
    {
        // 1. GATEKEEPER (identik dengan createOrder: config + hash_equals)
        $botSecret = (string) config('services.bot_order.secret');
        $providedSecret = (string) $request->header('X-Bot-Secret', '');

        if ($botSecret === '' || $providedSecret === '' || !hash_equals($botSecret, $providedSecret)) {
            Log::warning('Akses API Bot cek-ongkir ditolak (kredensial tidak valid). IP: ' . $request->ip());
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        // 2. VALIDASI INPUT
        $validated = $request->validate([
            'sender_address'   => 'required|string|min:3|max:255',
            'receiver_address' => 'required|string|min:3|max:255',
            'weight'           => 'required|numeric|min:1|max:300000',
            'item_value'       => 'nullable|numeric|min:0',
            'all'              => 'nullable|boolean',
            'phone_number'     => 'nullable|string|max:20',
            'wa'               => 'nullable|boolean',
        ]);

        $weight = (int) ceil((float) $validated['weight']);
        $itemValue = (int) ($validated['item_value'] ?? 10000);

        // 3. PEMETAAN ALAMAT (KiriminAja)
        try {
            $senderLocation = $this->_resolveAddress($validated['sender_address']);
            if (!$senderLocation) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Alamat asal tidak ditemukan. Sertakan nama kecamatan dan kabupaten/kota.',
                    'address' => $validated['sender_address'],
                ], 422);
            }

            $receiverLocation = $this->_resolveAddress($validated['receiver_address']);
            if (!$receiverLocation) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Alamat tujuan tidak ditemukan. Sertakan nama kecamatan dan kabupaten/kota.',
                    'address' => $validated['receiver_address'],
                ], 422);
            }
        } catch (Throwable $e) {
            Log::error('Bot cek-ongkir: pemetaan alamat gagal: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Layanan pencarian alamat sedang tidak tersedia. Coba lagi nanti.',
            ], 502);
        }

        // 4. CEK ONGKIR TERBAIK (baca-saja, tanpa booking)
        $wantAll = (bool) ($validated['all'] ?? $request->boolean('all'));
        $allOptions = [];
        try {
            $allOptions = $this->_getShippingOptions(
                $senderLocation['district_id'],
                $senderLocation['subdistrict_id'],
                $receiverLocation['district_id'],
                $receiverLocation['subdistrict_id'],
                $weight,
                $itemValue
            );
            $shippingCost = $this->_pickCheapest($allOptions);
        } catch (Throwable $e) {
            Log::error('Bot cek-ongkir: query ongkir gagal: ' . $e->getMessage());
            $shippingCost = null;
            $allOptions = [];
        }

        if (!$shippingCost) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ongkos kirim tidak tersedia untuk rute ini. Periksa kembali kecamatan asal/tujuan atau berat paket.',
                'asal'    => $senderLocation['full_text'] ?? $validated['sender_address'],
                'tujuan'  => $receiverLocation['full_text'] ?? $validated['receiver_address'],
                'berat'   => $weight,
            ], 404);
        }

        // 5. RESPON
        $payload = [
            'status'        => 'success',
            'asal'          => $senderLocation['full_text'] ?? $validated['sender_address'],
            'tujuan'        => $receiverLocation['full_text'] ?? $validated['receiver_address'],
            'berat'         => $weight,
            'berat_satuan'  => 'gram',
            'kurir'         => strtoupper((string) $shippingCost['courier']),
            'layanan'       => (string) $shippingCost['service_type'],
            'ongkir'        => (int) $shippingCost['cost'],
            'ongkir_format' => 'Rp ' . number_format((int) $shippingCost['cost'], 0, ',', '.'),
            'catatan'       => 'Ongkir estimasi reguler termurah. Tidak ada pesanan yang dibuat.',
        ];

        // all=1: sertakan seluruh opsi ekspedisi (diurutkan dari termurah)
        if ($wantAll) {
            $payload['jumlah_ekspedisi'] = count($allOptions);
            $payload['ekspedisi'] = $allOptions;
        }

        // wa=true -> kirim daftar kurir interaktif ke WhatsApp (API.co.id).
        // Nomor tujuan: phone_number, fallback APICOID_DEFAULT_PHONE (nomor bot toko).
        $wantWa = (bool) ($validated['wa'] ?? $request->boolean('wa'));
        $phoneInput = trim((string) ($validated['phone_number'] ?? ''));
        if ($phoneInput === '' && $wantWa) {
            $phoneInput = trim((string) config('services.apicoid.default_phone'));
        }
        if ($phoneInput !== '') {
            $payload['whatsapp'] = $this->_sendShippingListToWhatsApp($phoneInput, $allOptions, [
                'asal'   => $payload['asal'],
                'tujuan' => $payload['tujuan'],
                'weight' => $weight,
            ]);
        }

        return response()->json($payload, 200);
    }


    /**
     * Endpoint bot: hitung ongkir lalu WAJIB kirim list tombol ke nomor pengirim.
     * Dipakai Hermes saat pelanggan bertanya ongkir di WhatsApp.
     */
    public function botKirimTombolOngkir(Request $request)
    {
        if (!$this->_botAuthorized($request)) {
            Log::warning('Bot kirim-tombol ditolak: kredensial tidak valid. IP: ' . $request->ip());

            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        $validated = $request->validate([
            'sender_address'   => 'required|string|min:3|max:255',
            'receiver_address' => 'required|string|min:3|max:255',
            'weight'           => 'required|numeric|min:1|max:300000',
            'item_value'       => 'nullable|numeric|min:0',
            'phone_number'     => 'required|string|min:8|max:20',
        ]);

        $weight = (int) ceil((float) $validated['weight']);
        $itemValue = (int) ($validated['item_value'] ?? 10000);

        try {
            $sender = $this->_resolveAddress($validated['sender_address']);
            $receiver = $this->_resolveAddress($validated['receiver_address']);
        } catch (Throwable $e) {
            Log::error('Bot kirim-tombol: pemetaan alamat gagal: ' . $e->getMessage());
            $sender = $receiver = null;
        }

        if (!$sender || !$receiver) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Alamat asal atau tujuan tidak ditemukan. Sertakan kecamatan dan kabupaten/kota.',
            ], 422);
        }

        $options = $this->_getShippingOptions(
            $sender['district_id'],
            $sender['subdistrict_id'],
            $receiver['district_id'],
            $receiver['subdistrict_id'],
            $weight,
            $itemValue
        );

        if (empty($options)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak ada layanan ekspedisi untuk rute tersebut.',
                'asal'    => $sender['full_text'] ?? null,
                'tujuan'  => $receiver['full_text'] ?? null,
            ], 404);
        }

        $wa = $this->_sendShippingListToWhatsApp($validated['phone_number'], $options, [
            'asal'   => $sender['full_text'] ?? $validated['sender_address'],
            'tujuan' => $receiver['full_text'] ?? $validated['receiver_address'],
            'weight' => $weight,
        ]);

        $payload = [
            'status'           => 'success',
            'asal'             => $sender['full_text'] ?? $validated['sender_address'],
            'tujuan'           => $receiver['full_text'] ?? $validated['receiver_address'],
            'berat'            => $weight,
            'berat_satuan'     => 'gram',
            'jumlah_ekspedisi' => count($options),
            'termurah'         => $options[0],
            'ekspedisi'        => array_slice($options, 0, 9),
            'whatsapp'         => $wa,
        ];

        // Bila list tombol gagal terkirim, jangan balas sukses.
        if (empty($wa['whatsapp_sent'])) {
            $payload['status'] = 'error';
            $payload['message'] = $wa['message'] ?? 'Daftar kurir gagal dikirim ke WhatsApp.';

            return response()->json($payload, 502);
        }

        return response()->json($payload, 200);
    }

    /**
     * Endpoint bot: lacak resi / order id (read-only, tanpa menyentuh pesanan).
     */
    public function botTracking(Request $request)
    {
        if (!$this->_botAuthorized($request)) {
            Log::warning('Bot tracking ditolak: kredensial tidak valid. IP: ' . $request->ip());
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        $validated = $request->validate([
            'resi' => 'required|string|min:4|max:64',
        ]);

        // Hanya karakter yang wajar untuk nomor resi — cegah input aneh ke API mitra.
        $resi = preg_replace('/[^A-Za-z0-9\-._]/', '', $validated['resi']);
        if ($resi === '' || strlen($resi) < 4) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Nomor resi tidak valid.',
            ], 422);
        }

        try {
            $result = $this->kirimaja->trackPackage($resi);
        } catch (Throwable $e) {
            Log::error('Bot tracking error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Layanan pelacakan sedang tidak tersedia.',
            ], 502);
        }

        if (empty($result['status'])) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['text'] ?? 'Resi tidak ditemukan.',
                'resi'    => $resi,
            ], 404);
        }

        $summary = (array) ($result['data']['summary'] ?? []);
        $histories = [];
        foreach (array_slice((array) ($result['data']['histories'] ?? []), 0, 20) as $h) {
            if (!is_array($h)) {
                continue;
            }
            $histories[] = [
                'waktu'    => $h['created_at'] ?? $h['time'] ?? null,
                'status'   => $h['status'] ?? $h['desc'] ?? null,
                'catatan'  => $h['note'] ?? $h['description'] ?? null,
                'lokasi'   => $h['location'] ?? null,
            ];
        }

        return response()->json([
            'status'          => 'success',
            'resi'            => $resi,
            'ringkasan'       => [
                'kurir'   => $summary['courier'] ?? $summary['service'] ?? null,
                'layanan' => $summary['service_type'] ?? null,
                'status'  => $summary['status'] ?? null,
                'pengirim' => $summary['sender_name'] ?? null,
                'penerima' => $summary['receiver_name'] ?? null,
            ],
            'riwayat'         => $histories,
            'jumlah_riwayat'  => count($histories),
        ], 200);
    }

    /**
     * Endpoint bot: daftar semua ekspedisi (read-only, TIDAK mengirim WhatsApp).
     */
    public function botShippingList(Request $request)
    {
        if (!$this->_botAuthorized($request)) {
            Log::warning('Bot ongkir-list ditolak: kredensial tidak valid. IP: ' . $request->ip());
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        $validated = $request->validate([
            'sender_address'   => 'required|string|min:3|max:255',
            'receiver_address' => 'required|string|min:3|max:255',
            'weight'           => 'required|numeric|min:1|max:300000',
            'item_value'       => 'nullable|numeric|min:0',
        ]);

        $weight = (int) ceil((float) $validated['weight']);
        $itemValue = (int) ($validated['item_value'] ?? 10000);

        try {
            $sender = $this->_resolveAddress($validated['sender_address']);
            $receiver = $this->_resolveAddress($validated['receiver_address']);
        } catch (Throwable $e) {
            Log::error('Bot ongkir-list: pemetaan alamat gagal: ' . $e->getMessage());
            $sender = $receiver = null;
        }

        if (!$sender || !$receiver) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Alamat asal atau tujuan tidak ditemukan. Sertakan kecamatan dan kabupaten/kota.',
            ], 422);
        }

        $options = $this->_getShippingOptions(
            $sender['district_id'],
            $sender['subdistrict_id'],
            $receiver['district_id'],
            $receiver['subdistrict_id'],
            $weight,
            $itemValue
        );

        if (empty($options)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ongkos kirim tidak tersedia untuk rute ini.',
                'asal'    => $sender['full_text'] ?? null,
                'tujuan'  => $receiver['full_text'] ?? null,
            ], 404);
        }

        return response()->json([
            'status'           => 'success',
            'asal'             => $sender['full_text'] ?? $validated['sender_address'],
            'tujuan'           => $receiver['full_text'] ?? $validated['receiver_address'],
            'berat'            => $weight,
            'berat_satuan'     => 'gram',
            'jumlah_ekspedisi' => count($options),
            'ekspedisi'        => $options,
            'catatan'          => 'Tarif estimasi. Tidak ada pesanan dibuat.',
        ], 200);
    }

    /**
     * Endpoint bot: siapkan ringkasan pengiriman (validasi + ongkir terpilih).
     * READ-ONLY — tidak membuat Pesanan dan tidak membuat transaksi pembayaran.
     */
    public function botKirimPaket(Request $request)
    {
        if (!$this->_botAuthorized($request)) {
            Log::warning('Bot kirim-paket ditolak: kredensial tidak valid. IP: ' . $request->ip());
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        $validated = $request->validate([
            'sender_name'      => 'required|string|min:2|max:100',
            'sender_phone'     => 'required|string|min:8|max:20',
            'sender_address'   => 'required|string|min:3|max:255',
            'receiver_name'    => 'required|string|min:2|max:100',
            'receiver_phone'   => 'required|string|min:8|max:20',
            'receiver_address' => 'required|string|min:3|max:255',
            'weight'           => 'required|numeric|min:1|max:300000',
            'item_value'       => 'nullable|numeric|min:0',
            'kurir'            => 'nullable|string|max:30',
            'kode'             => 'nullable|string|max:30',
        ]);

        $weight = (int) ceil((float) $validated['weight']);
        $itemValue = (int) ($validated['item_value'] ?? 10000);

        try {
            $sender = $this->_resolveAddress($validated['sender_address']);
            $receiver = $this->_resolveAddress($validated['receiver_address']);
        } catch (Throwable $e) {
            Log::error('Bot kirim-paket: pemetaan alamat gagal: ' . $e->getMessage());
            $sender = $receiver = null;
        }

        if (!$sender || !$receiver) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Alamat asal atau tujuan tidak ditemukan. Sertakan kecamatan dan kabupaten/kota.',
            ], 422);
        }

        $options = $this->_getShippingOptions(
            $sender['district_id'],
            $sender['subdistrict_id'],
            $receiver['district_id'],
            $receiver['subdistrict_id'],
            $weight,
            $itemValue
        );

        if (empty($options)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak ada layanan ekspedisi untuk rute tersebut.',
            ], 404);
        }

        $wantedKurir = strtoupper(trim((string) ($validated['kurir'] ?? '')));
        $wantedKode = strtoupper(trim((string) ($validated['kode'] ?? '')));

        $chosen = null;
        foreach ($options as $opt) {
            if ($wantedKurir !== '' && strtoupper((string) $opt['kurir']) !== $wantedKurir) {
                continue;
            }
            if ($wantedKode !== '' && strtoupper((string) $opt['kode']) !== $wantedKode) {
                continue;
            }
            $chosen = $opt;
            break;
        }

        if (!$chosen) {
            $chosen = $options[0];
        }

        return response()->json([
            'status'        => 'success',
            'pengirim'      => [
                'nama'  => $validated['sender_name'],
                'phone' => $validated['sender_phone'],
                'alamat' => $sender['full_text'] ?? null,
            ],
            'penerima'      => [
                'nama'  => $validated['receiver_name'],
                'phone' => $validated['receiver_phone'],
                'alamat' => $receiver['full_text'] ?? null,
            ],
            'berat'         => $weight,
            'berat_satuan'  => 'gram',
            'nilai_barang'  => $itemValue,
            'terpilih'      => $chosen,
            'ringkasan_biaya' => [
                'ongkir' => (int) $chosen['ongkir'],
                'ongkir_format' => 'Rp ' . number_format((int) $chosen['ongkir'], 0, ',', '.'),
                'total'  => (int) $chosen['ongkir'],
                'total_format' => 'Rp ' . number_format((int) $chosen['ongkir'], 0, ',', '.'),
            ],
            'catatan'       => 'Ringkasan estimasi. Belum ada pesanan dibuat dan belum ada pembayaran.',
        ], 200);
    }

    /**
     * Gatekeeper bot: fail-closed, perbandingan constant-time.
     */
    private function _botAuthorized(Request $request): bool
    {
        $botSecret = (string) config('services.bot_order.secret');
        $providedSecret = (string) $request->header('X-Bot-Secret', '');

        return $botSecret !== '' && $providedSecret !== '' && hash_equals($botSecret, $providedSecret);
    }

    /**
     * Endpoint tambahan: cek ongkir dan kirim pilihan kurir interaktif ke WhatsApp.
     * Tidak membuat Pesanan dan tidak memanggil Tripay.
     */
    public function sendShippingOptionsWhatsApp(Request $request)
    {
        $validated = $request->validate([
            'phone_number'     => 'required|string|max:20',
            'sender_address'   => 'required|string|min:3|max:255',
            'receiver_address' => 'required|string|min:3|max:255',
            'weight'           => 'required|numeric|min:1|max:300000',
            'item_value'       => 'nullable|numeric|min:0',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone_number']);
        if (Str::startsWith($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (Str::startsWith($phone, '8')) {
            $phone = '62' . $phone;
        }

        if (!Str::startsWith($phone, '62') || strlen($phone) < 10) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor WhatsApp harus menggunakan format Indonesia, contoh 6281234567890.',
            ], 422);
        }

        try {
            $senderLocation = $this->_resolveAddress($validated['sender_address']);
            $receiverLocation = $this->_resolveAddress($validated['receiver_address']);

            if (!$senderLocation || !$receiverLocation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Alamat asal atau tujuan tidak ditemukan. Sertakan kecamatan dan kabupaten/kota.',
                ], 422);
            }

            $weight = (int) ceil((float) $validated['weight']);
            $itemValue = (int) ($validated['item_value'] ?? 10000);

            $options = $this->_getShippingOptions(
                $senderLocation['district_id'],
                $senderLocation['subdistrict_id'],
                $receiverLocation['district_id'],
                $receiverLocation['subdistrict_id'],
                $weight,
                $itemValue
            );

            if (empty($options)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada layanan ekspedisi untuk rute tersebut.',
                ], 404);
            }

            $token = Str::lower(Str::random(12));
            $cacheKey = 'sck_wa_shipping_quote:' . $token;

            Cache::put($cacheKey, [
                'phone' => $phone,
                'asal' => $senderLocation['full_text'] ?? $validated['sender_address'],
                'tujuan' => $receiverLocation['full_text'] ?? $validated['receiver_address'],
                'weight' => $weight,
                'options' => $options,
            ], now()->addMinutes(20));

            $sent = $this->_sendWhatsAppShippingList($phone, $token, 0, $options);

            if (!$sent) {
                Cache::forget($cacheKey);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengirim daftar kurir melalui API.co.id. Periksa API key dan respons gateway.',
                ], 502);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar pilihan kurir interaktif berhasil dikirim ke WhatsApp.',
                'jumlah_ekspedisi' => count($options),
                'berat_gram' => $weight,
                'quote_token' => $token,
                'berlaku_menit' => 20,
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp shipping list error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyiapkan pilihan ongkir.',
            ], 500);
        }
    }

    /**
     * Endpoint tambahan: menerima callback pilihan list dari webhook API.co.id.
     * ID pilihan: sckq|TOKEN|INDEX atau scknext|TOKEN|PAGE.
     */
    public function handleWhatsAppShippingWebhook(Request $request)
    {
        $webhookSecret = (string) config('services.apicoid.webhook_secret');
        $providedSecret = (string) $request->header('X-WhatsApp-Webhook-Secret', '');

        if ($webhookSecret !== '' &&
            ($providedSecret === '' || !hash_equals($webhookSecret, $providedSecret))) {
            Log::warning('Webhook WhatsApp ditolak: secret tidak valid.');
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // Menoleransi beberapa bentuk payload webhook. Cocokkan dengan payload aktual
        // dari dashboard API.co.id sebelum produksi.
        $interactiveId = data_get($request->all(), 'data.interactive_response.id')
            ?? data_get($request->all(), 'data.interactive.list_reply.id')
            ?? data_get($request->all(), 'data.message.interactive.list_reply.id')
            ?? data_get($request->all(), 'data.raw.interactive.list_reply.id')
            ?? data_get($request->all(), 'interactive_response.id');

        if (!$interactiveId || !is_string($interactiveId)) {
            return response()->json(['status' => 'ignored']);
        }

        $parts = explode('|', $interactiveId);
        if (count($parts) !== 3 || !in_array($parts[0], ['sckq', 'scknext'], true)) {
            return response()->json(['status' => 'ignored']);
        }

        [, $token, $value] = $parts;
        $quote = Cache::get('sck_wa_shipping_quote:' . $token);

        if (!$quote || empty($quote['phone']) || empty($quote['options'])) {
            return response()->json([
                'status' => 'expired',
                'message' => 'Sesi pilihan ongkir sudah kedaluwarsa. Silakan cek ongkir kembali.',
            ]);
        }

        if ($parts[0] === 'scknext') {
            $page = max(0, (int) $value);
            $sent = $this->_sendWhatsAppShippingList(
                $quote['phone'],
                $token,
                $page,
                $quote['options']
            );

            return response()->json(['status' => $sent ? 'success' : 'error']);
        }

        $index = (int) $value;
        if (!array_key_exists($index, $quote['options'])) {
            return response()->json(['status' => 'invalid_selection'], 422);
        }

        $selected = $quote['options'][$index];

        // Simpan pilihan kurir ke draft MySQL dan mulai pengisian data bertahap
        $this->_startDraftFromQuote($quote['phone'], $token, $selected, $quote);

        $message = "✅ *Kurir dipilih: " . $selected['kurir'] . " - " . ($selected['layanan'] ?: $selected['kode']) . "*\n"
            . "💰 Ongkir: *Rp " . number_format((int) $selected['ongkir'], 0, ',', '.') . "* (" . (int) $quote['weight'] . " gram)\n\n"
            . "Sekarang lengkapi data pengiriman secara bertahap (8 langkah).\n\n"
            . "👤 *Langkah 1/8: Nama Pengirim*\n"
            . "Ketik nama lengkap pengirim:";

        $sent = $this->_sendWhatsAppText($quote['phone'], $message);

        Log::info('Pilihan kurir WhatsApp diterima.', [
            'quote_token' => $token,
            'courier' => $selected['kurir'],
            'service' => $selected['kode'],
            'cost' => $selected['ongkir'],
        ]);

        return response()->json([
            'status' => $sent ? 'success' : 'error',
            'selected' => [
                'kurir' => $selected['kurir'],
                'layanan' => $selected['layanan'],
                'kode' => $selected['kode'],
                'ongkir' => $selected['ongkir'],
                'etd' => $selected['etd'],
            ],
        ], $sent ? 200 : 502);
    }

    /**
     * Mengirim maksimal 9 layanan + 1 pilihan halaman berikutnya.
     * Batas 10 row total menjaga kompatibilitas list message WhatsApp.
     */
    private function _sendWhatsAppShippingList(string $phone, string $token, int $page, array $options): bool
    {
        $perPage = 9;
        $offset = $page * $perPage;
        $pageOptions = array_slice($options, $offset, $perPage);

        if (empty($pageOptions)) {
            return false;
        }

        $rows = [];
        foreach ($pageOptions as $localIndex => $option) {
            $index = $offset + $localIndex;
            $title = trim($option['kurir'] . ' ' . ($option['layanan'] ?: $option['kode']));
            $title = Str::limit($title, 24, '');

            $description = 'Rp ' . number_format((int) $option['ongkir'], 0, ',', '.');
            if (!empty($option['etd'])) {
                $description .= ' | ' . $option['etd'] . ' hari';
            }
            if (!empty($option['cod'])) {
                $description .= ' | COD';
            }

            $rows[] = [
                'id' => 'sckq|' . $token . '|' . $index,
                'title' => $title !== '' ? $title : 'Layanan ' . ($index + 1),
                'description' => Str::limit($description, 72, ''),
            ];
        }

        if (($offset + $perPage) < count($options)) {
            $rows[] = [
                'id' => 'scknext|' . $token . '|' . ($page + 1),
                'title' => 'Lihat pilihan berikutnya',
                'description' => 'Menampilkan layanan kurir selanjutnya',
            ];
        }

        $interactive = [
            'type' => 'list',
            'header' => [
                'type' => 'text',
                'text' => 'SANCAKA EXPRESS',
            ],
            'body' => [
                'text' => "📦 *Pilihan layanan pengiriman*\n"
                    . "Asal: " . ($this->_getCachedQuoteValue($token, 'asal') ?? '-') . "\n"
                    . "Tujuan: " . ($this->_getCachedQuoteValue($token, 'tujuan') ?? '-') . "\n"
                    . "Berat: " . ($this->_getCachedQuoteValue($token, 'weight') ?? '-') . " gram\n\n"
                    . "Silakan pilih kurir dan layanan yang Anda inginkan.",
            ],
            'footer' => [
                'text' => 'Estimasi dan tarif dapat berubah',
            ],
            'action' => [
                'button' => 'Pilih Kurir',
                'sections' => [
                    [
                        'title' => 'Layanan Pengiriman',
                        'rows' => $rows,
                    ],
                ],
            ],
        ];

        $payload = [
            'phone_number' => $phone,
            'channel' => 'whatsapp',
            'message_type' => 'interactive',
            'interactive' => $interactive,
        ];

        $phoneNumberId = config('services.apicoid.phone_number_id');
        if (!empty($phoneNumberId)) {
            $payload['whatsapp_phone_number_id'] = $phoneNumberId;
        }

        return $this->_sendWhatsAppPayload($payload);
    }

    private function _getCachedQuoteValue(string $token, string $field)
    {
        $quote = Cache::get('sck_wa_shipping_quote:' . $token);
        return $quote[$field] ?? null;
    }

    /**
     * Daftar channel pembayaran DOKU yang ditawarkan lewat WhatsApp.
     * Key = id tombol (sckpay|KEY), value = label + payment_method_types DOKU.
     */
    public const WA_PAYMENT_CHANNELS = [
        'QRIS'    => ['label' => 'QRIS (semua e-wallet)', 'types' => ['QRIS']],
        'BCA'     => ['label' => 'VA Bank BCA', 'types' => ['VIRTUAL_ACCOUNT_BCA']],
        'MANDIRI' => ['label' => 'VA Bank Mandiri', 'types' => ['VIRTUAL_ACCOUNT_BANK_MANDIRI']],
        'BRI'     => ['label' => 'VA Bank BRI', 'types' => ['VIRTUAL_ACCOUNT_BRI']],
        'BNI'     => ['label' => 'VA Bank BNI', 'types' => ['VIRTUAL_ACCOUNT_BNI']],
        'PERMATA' => ['label' => 'VA Bank Permata', 'types' => ['VIRTUAL_ACCOUNT_BANK_PERMATA']],
        'CIMB'    => ['label' => 'VA Bank CIMB Niaga', 'types' => ['VIRTUAL_ACCOUNT_BANK_CIMB']],
        'BSI'     => ['label' => 'VA Bank Syariah Indonesia', 'types' => ['VIRTUAL_ACCOUNT_BANK_SYARIAH_MANDIRI']],
        'DANAMON' => ['label' => 'VA Bank Danamon', 'types' => ['VIRTUAL_ACCOUNT_BANK_DANAMON']],
        'ALFAMART' => ['label' => 'Alfamart', 'types' => ['ONLINE_TO_OFFLINE_ALFA']],
    ];

    /**
     * Kirim menu pilihan channel pembayaran (list interaktif) ke WhatsApp.
     */
    private function _sendPaymentChannelList(string $phone, string $invoice, int $amount, string $token)
    {
        $rows = [];
        foreach (self::WA_PAYMENT_CHANNELS as $key => $channel) {
            $rows[] = [
                'id'          => 'sckpay|' . $token . '|' . $key,
                'title'       => Str::limit($channel['label'], 24, ''),
                'description' => 'Bayar via ' . $channel['label'],
            ];
        }

        // List message maksimal 10 baris.
        $rows = array_slice($rows, 0, 10);

        $payload = [
            'phone_number' => $phone,
            'channel'      => 'whatsapp',
            'message_type' => 'interactive',
            'interactive'  => [
                'type'   => 'list',
                'header' => ['type' => 'text', 'text' => 'SANCAKA EXPRESS'],
                'body'   => [
                    'text' => "💳 *Pilih Metode Pembayaran*\n\n"
                        . "🧾 Invoice: *{$invoice}*\n"
                        . "💰 Total: *Rp " . number_format($amount, 0, ',', '.') . "*\n\n"
                        . "Silakan pilih bank VA atau QRIS untuk melanjutkan pembayaran.",
                ],
                'footer' => ['text' => 'Tagihan berlaku 60 menit'],
                'action' => [
                    'button'   => 'Pilih Pembayaran',
                    'sections' => [[
                        'title' => 'Metode Pembayaran',
                        'rows'  => $rows,
                    ]],
                ],
            ],
        ];

        $phoneNumberId = config('services.apicoid.phone_number_id');
        if (!empty($phoneNumberId)) {
            $payload['whatsapp_phone_number_id'] = $phoneNumberId;
        }

        return $this->_sendWhatsAppPayload($payload);
    }

    /**
     * Wrapper publik untuk kirim teks WhatsApp (dipakai DokuWebhookController).
     */
    public function sendWhatsAppTextPublic(string $phone, string $message): bool
    {
        return $this->_sendWhatsAppText($phone, $message);
    }

    private function _sendWhatsAppText(string $phone, string $message): bool
    {
        return $this->_sendWhatsAppPayload([
            'phone_number' => $phone,
            'channel' => 'whatsapp',
            'message_type' => 'text',
            'content' => $message,
        ]);
    }

    // =====================================================================
    // STATE MACHINE: PENGISIAN DATA BERTAHAP + KONFIRMASI + PEMBAYARAN
    // =====================================================================

    /**
     * Webhook pesan masuk dari API.co.id.
     *
     * Menangani tiga jenis payload:
     * 1. List reply  -> id "sckq|TOKEN|INDEX" (pilih kurir)  => buat draft, tanya nama pengirim
     * 2. Button reply-> id "sckgo|TOKEN" (Ya, Lanjutkan)      => buat invoice + DOKU
     *                   id "sckedit|TOKEN" (Ubah Data)        => mulai ulang pengisian
     * 3. Text        -> input bertahap sesuai kolom `step` pada draft
     */
    public function botWhatsAppInbound(Request $request)
    {
        $webhookSecret = (string) config('services.apicoid.webhook_secret');
        $providedSecret = (string) $request->header('X-WhatsApp-Webhook-Secret', '');

        if ($webhookSecret !== '' &&
            ($providedSecret === '' || !hash_equals($webhookSecret, $providedSecret))) {
            Log::warning('Webhook inbound WhatsApp ditolak: secret tidak valid.');

            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        $phone = data_get($payload, 'data.from')
            ?? data_get($payload, 'data.phone_number')
            ?? data_get($payload, 'data.sender')
            ?? data_get($payload, 'from')
            ?? data_get($payload, 'phone_number');

        if (!$phone) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_phone']);
        }

        $phone = $this->_normalizeWaPhone((string) $phone);

        $interactiveId = data_get($payload, 'data.interactive_response.id')
            ?? data_get($payload, 'data.interactive.button_reply.id')
            ?? data_get($payload, 'data.interactive.list_reply.id')
            ?? data_get($payload, 'data.message.interactive.button_reply.id')
            ?? data_get($payload, 'data.message.interactive.list_reply.id')
            ?? data_get($payload, 'data.raw.interactive.list_reply.id')
            ?? data_get($payload, 'interactive_response.id');

        // 1. Interaktif: pilihan kurir / tombol konfirmasi
        if (is_string($interactiveId) && $interactiveId !== '') {
            if (str_starts_with($interactiveId, 'sckq|') || str_starts_with($interactiveId, 'scknext|')) {
                return $this->handleWhatsAppShippingWebhook($request);
            }

            if (str_starts_with($interactiveId, 'sckgo|')) {
                return $this->_handleConfirmSelection($phone, substr($interactiveId, 6));
            }

            if (str_starts_with($interactiveId, 'sckedit|')) {
                return $this->_handleEditRequest($phone);
            }

            if (str_starts_with($interactiveId, 'sckpay|')) {
                return $this->_handlePaySelection($phone, substr($interactiveId, 7));
            }

            return response()->json(['status' => 'ignored', 'reason' => 'unknown_interactive_id']);
        }

        // 2. Teks biasa: input bertahap
        $text = trim((string) (
            data_get($payload, 'data.message.text.body')
            ?? data_get($payload, 'data.text.body')
            ?? data_get($payload, 'data.text')
            ?? data_get($payload, 'text')
            ?? data_get($payload, 'message')
            ?? ''
        ));

        if ($text === '') {
            return response()->json(['status' => 'ignored', 'reason' => 'no_text']);
        }

        return $this->_handleStepInput($phone, $text);
    }

    /**
     * Menyimpan pilihan kurir ke draft dan memulai pengisian data.
     */
    private function _startDraftFromQuote(string $phone, string $token, array $selected, array $quote)
    {
        $draft = BotOrderDraft::updateOrCreate(
            ['phone_number' => $phone],
            [
                'session_token'    => $token,
                'step'             => 'AWAITING_SENDER_NAME',
                'courier_code'     => strtoupper((string) ($selected['kurir'] ?? '')),
                'courier_service'  => (string) (($selected['layanan'] ?? '') ?: ($selected['kode'] ?? '')),
                'shipping_cost'    => (int) ($selected['ongkir'] ?? 0),
                'weight'           => (int) ($quote['weight'] ?? 0),
                'origin_text'      => $quote['asal'] ?? null,
                'destination_text' => $quote['tujuan'] ?? null,
                'sender_name'      => null,
                'sender_phone'     => null,
                'sender_address'   => null,
                'receiver_name'    => null,
                'receiver_phone'   => null,
                'receiver_address' => null,
                'item_description' => null,
                'item_value'       => 0,
                'invoice_number'   => null,
                'payment_url'      => null,
                'raw_payload'      => ['selected' => $selected],
            ]
        );

        return $draft;
    }

    /**
     * Handler teks bertahap: satu langkah per pesan.
     */
    private function _handleStepInput(string $phone, string $text)
    {
        $draft = BotOrderDraft::where('phone_number', $phone)->first();

        if (!$draft) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_active_draft']);
        }

        $text = Str::limit($text, 250, '');

        switch ($draft->step) {
            case 'AWAITING_SENDER_NAME':
                $draft->sender_name = $text;
                $draft->step = 'AWAITING_SENDER_PHONE';
                $this->_sendWhatsAppText($phone, "✅ Nama pengirim: *{$text}*\n\n📱 *Langkah 2/8: No. HP Pengirim*\nKetik nomor HP pengirim (contoh: 0857xxxxxxx)\natau ketik *sama* untuk memakai nomor WhatsApp ini.");
                break;

            case 'AWAITING_SENDER_PHONE':
                $value = strtolower($text) === 'sama' ? $phone : $this->_normalizeWaPhone($text);
                if (strlen(preg_replace('/\D/', '', $value)) < 9) {
                    $this->_sendWhatsAppText($phone, "⚠️ Nomor HP tidak valid. Ketik ulang nomor HP pengirim (contoh: 0857xxxxxxx).");

                    return response()->json(['status' => 'invalid_input', 'step' => $draft->step]);
                }
                $draft->sender_phone = $value;
                $draft->step = 'AWAITING_SENDER_ADDRESS';
                $this->_sendWhatsAppText($phone, "✅ No. HP pengirim tersimpan.\n\n🏠 *Langkah 3/8: Alamat Pengirim*\nKetik alamat lengkap penjemputan paket (jalan, RT/RW, kelurahan, kecamatan, kota).");
                break;

            case 'AWAITING_SENDER_ADDRESS':
                if (strlen($text) < 10) {
                    $this->_sendWhatsAppText($phone, "⚠️ Alamat terlalu pendek. Mohon ketik alamat lengkap (minimal 10 karakter).");

                    return response()->json(['status' => 'invalid_input', 'step' => $draft->step]);
                }
                $draft->sender_address = $text;
                $draft->step = 'AWAITING_RECEIVER_NAME';
                $this->_sendWhatsAppText($phone, "✅ Alamat pengirim tersimpan.\n\n👤 *Langkah 4/8: Nama Penerima*\nKetik nama lengkap penerima paket.");
                break;

            case 'AWAITING_RECEIVER_NAME':
                $draft->receiver_name = $text;
                $draft->step = 'AWAITING_RECEIVER_PHONE';
                $this->_sendWhatsAppText($phone, "✅ Nama penerima: *{$text}*\n\n📱 *Langkah 5/8: No. HP Penerima*\nKetik nomor HP penerima.");
                break;

            case 'AWAITING_RECEIVER_PHONE':
                $value = $this->_normalizeWaPhone($text);
                if (strlen(preg_replace('/\D/', '', $value)) < 9) {
                    $this->_sendWhatsAppText($phone, "⚠️ Nomor HP tidak valid. Ketik ulang nomor HP penerima.");

                    return response()->json(['status' => 'invalid_input', 'step' => $draft->step]);
                }
                $draft->receiver_phone = $value;
                $draft->step = 'AWAITING_RECEIVER_ADDRESS';
                $this->_sendWhatsAppText($phone, "✅ No. HP penerima tersimpan.\n\n🏠 *Langkah 6/8: Alamat Penerima*\nKetik alamat lengkap tujuan (jalan, RT/RW, kelurahan, kecamatan, kota).");
                break;

            case 'AWAITING_RECEIVER_ADDRESS':
                if (strlen($text) < 10) {
                    $this->_sendWhatsAppText($phone, "⚠️ Alamat terlalu pendek. Mohon ketik alamat lengkap (minimal 10 karakter).");

                    return response()->json(['status' => 'invalid_input', 'step' => $draft->step]);
                }
                $draft->receiver_address = $text;
                $draft->step = 'AWAITING_ITEM_DESC';
                $this->_sendWhatsAppText($phone, "✅ Alamat penerima tersimpan.\n\n📦 *Langkah 7/8: Isi Paket*\nKetik deskripsi isi paket (contoh: baju kaos 2 pcs).");
                break;

            case 'AWAITING_ITEM_DESC':
                $draft->item_description = $text;
                $draft->step = 'AWAITING_ITEM_VALUE';
                $this->_sendWhatsAppText($phone, "✅ Isi paket: *{$text}*\n\n💰 *Langkah 8/8: Nilai Barang*\nKetik nilai barang dalam rupiah (contoh: 150000). Nilai ini untuk asuransi pengiriman.");
                break;

            case 'AWAITING_ITEM_VALUE':
                $value = (int) preg_replace('/\D/', '', $text);
                if ($value <= 0) {
                    $this->_sendWhatsAppText($phone, "⚠️ Nilai barang tidak valid. Ketik angka, contoh: 150000.");

                    return response()->json(['status' => 'invalid_input', 'step' => $draft->step]);
                }
                $draft->item_value = $value;
                $draft->step = 'AWAITING_CONFIRMATION';
                $draft->save();

                return $this->_sendSummaryConfirmation($draft);

            case 'AWAITING_CONFIRMATION':
                $this->_sendWhatsAppText($phone, "Silakan tekan tombol *Ya, Lanjutkan* pada ringkasan di atas, atau *Ubah Data* untuk mengisi ulang.");

                return response()->json(['status' => 'awaiting_confirmation']);
        }

        $draft->save();

        return response()->json(['status' => 'success', 'step' => $draft->step]);
    }

    /**
     * Kirim ringkasan lengkap + tombol konfirmasi.
     */
    private function _sendSummaryConfirmation(BotOrderDraft $draft)
    {
        $phone = $draft->phone_number;
        $rp = fn ($v) => 'Rp ' . number_format((int) $v, 0, ',', '.');

        $body = "📋 *RINGKASAN PESANAN*\n"
            . "----------------------------------\n"
            . "🚚 Kurir: *{$draft->courier_code} - {$draft->courier_service}*\n"
            . "⚖️ Berat: *{$draft->weight} gram*\n"
            . "💰 Ongkir: *{$rp($draft->shipping_cost)}*\n"
            . "----------------------------------\n"
            . "📤 *PENGIRIM*\n"
            . "Nama: {$draft->sender_name}\n"
            . "HP: {$draft->sender_phone}\n"
            . "Alamat: {$draft->sender_address}\n"
            . "----------------------------------\n"
            . "📥 *PENERIMA*\n"
            . "Nama: {$draft->receiver_name}\n"
            . "HP: {$draft->receiver_phone}\n"
            . "Alamat: {$draft->receiver_address}\n"
            . "----------------------------------\n"
            . "📦 Isi: {$draft->item_description}\n"
            . "💵 Nilai barang: *{$rp($draft->item_value)}*\n"
            . "----------------------------------\n"
            . "Periksa kembali data di atas. Tekan *Ya, Lanjutkan* untuk membuat tagihan pembayaran.";

        $sent = $this->_sendWhatsAppButtons($phone, $body, [
            ['id' => 'sckgo|' . $draft->session_token, 'title' => 'Ya, Lanjutkan'],
            ['id' => 'sckedit|' . $draft->session_token, 'title' => 'Ubah Data'],
        ]);

        return response()->json(['status' => $sent ? 'success' : 'error', 'step' => 'AWAITING_CONFIRMATION']);
    }

    /**
     * Tombol "Ubah Data": reset draft ke langkah pertama.
     */
    private function _handleEditRequest(string $phone)
    {
        $draft = BotOrderDraft::where('phone_number', $phone)->first();

        if (!$draft) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_active_draft']);
        }

        $draft->fill([
            'step'             => 'AWAITING_SENDER_NAME',
            'sender_name'      => null,
            'sender_phone'     => null,
            'sender_address'   => null,
            'receiver_name'    => null,
            'receiver_phone'   => null,
            'receiver_address' => null,
            'item_description' => null,
            'item_value'       => 0,
        ])->save();

        $this->_sendWhatsAppText($phone, "✏️ Data direset. Mari isi ulang.\n\n👤 *Langkah 1/8: Nama Pengirim*\nKetik nama lengkap pengirim:");

        return response()->json(['status' => 'success', 'step' => $draft->step]);
    }

    /**
     * Tombol "Ya, Lanjutkan": buat invoice, lalu kirim menu pilihan pembayaran.
     */
    private function _handleConfirmSelection(string $phone, string $token)
    {
        $draft = BotOrderDraft::where('phone_number', $phone)->first();

        if (!$draft) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_active_draft']);
        }

        if ($token !== '' && $draft->session_token !== null && $token !== $draft->session_token) {
            return response()->json(['status' => 'ignored', 'reason' => 'token_mismatch']);
        }

        if ($draft->step !== 'AWAITING_CONFIRMATION') {
            return response()->json(['status' => 'invalid_state', 'step' => $draft->step]);
        }

        if ($draft->invoice_number) {
            $this->_sendWhatsAppText($phone, "ℹ️ Pesanan ini sudah dibuat. Invoice: *{$draft->invoice_number}*\nLink pembayaran: {$draft->payment_url}");

            return response()->json(['status' => 'already_created', 'invoice' => $draft->invoice_number]);
        }

        DB::beginTransaction();

        try {
            $senderLocation = $this->_resolveAddress((string) $draft->sender_address);
            $receiverLocation = $this->_resolveAddress((string) $draft->receiver_address);

            if (!$senderLocation || !$receiverLocation) {
                throw new Exception('Alamat tidak dapat dipetakan ke kode area ekspedisi. Silakan perbaiki alamat.');
            }

            $sanitizedPhone = $this->_sanitizePhoneNumber((string) $draft->sender_phone);
            $customer = User::where('no_hp', $sanitizedPhone)->first();
            $customerId = $customer ? $customer->id_pengguna : null;

            do {
                $nomorInvoice = 'SCK-BOT-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

            $itemValue = (int) $draft->item_value;

            $order = Pesanan::create([
                'nomor_invoice'           => $nomorInvoice,
                'id_pengguna_pembeli'     => $customerId,
                'customer_id'             => $customerId,
                'status'                  => 'Menunggu Pembayaran',
                'status_pesanan'          => 'Menunggu Pembayaran',
                'tanggal_pesanan'         => now(),
                'ip_address'              => null,
                'user_agent'              => 'Bot/WhatsApp Draft',

                'sender_name'             => $draft->sender_name,
                'sender_phone'            => $sanitizedPhone,
                'sender_address'          => $draft->sender_address,
                'sender_district_id'      => $senderLocation['district_id'],
                'sender_subdistrict_id'   => $senderLocation['subdistrict_id'],
                'sender_postal_code'      => $senderLocation['postal_code'],

                'receiver_name'           => $draft->receiver_name,
                'receiver_phone'          => $this->_sanitizePhoneNumber((string) $draft->receiver_phone),
                'receiver_address'        => $draft->receiver_address,
                'receiver_district_id'    => $receiverLocation['district_id'],
                'receiver_subdistrict_id' => $receiverLocation['subdistrict_id'],
                'receiver_postal_code'    => $receiverLocation['postal_code'],

                'item_description'        => $draft->item_description,
                'item_price'              => $itemValue,
                'weight'                  => $draft->weight,
                'service_type'            => $draft->courier_service,
                'expedition'              => 'regular-' . strtolower($draft->courier_code) . '-' . $draft->courier_service . '-' . $draft->shipping_cost . '-0-0-0',
                'payment_method'          => 'DOKU',
                'shipping_cost'           => $draft->shipping_cost,
                'price'                   => $draft->shipping_cost,
                'ansuransi'               => 'tidak',
                'insurance_cost'          => 0,
                'cod_fee'                 => 0,
                'item_type'               => 1,
            ]);

            $draft->invoice_number = $order->nomor_invoice;
            $draft->payment_url = null;
            $draft->step = 'AWAITING_PAYMENT_CHANNEL';
            $draft->save();

            DB::commit();

            // Kirim menu pilihan metode pembayaran (VA bank / QRIS) ke WhatsApp.
            $this->_sendPaymentChannelList(
                $phone,
                $order->nomor_invoice,
                (int) $draft->shipping_cost,
                $token
            );

            Log::info('Draft WhatsApp dikonversi menjadi pesanan (menunggu pilihan channel).', [
                'invoice' => $order->nomor_invoice,
                'phone'   => $phone,
                'courier' => $draft->courier_code,
            ]);

            return response()->json([
                'status'      => 'success',
                'invoice'     => $order->nomor_invoice,
                'total'       => $draft->shipping_cost,
                'next'        => 'pilih_metode_pembayaran',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Gagal membuat pesanan dari draft WhatsApp: ' . $e->getMessage());

            $this->_sendWhatsAppText($phone, "❌ Maaf, pesanan gagal dibuat: {$e->getMessage()}\n\nSilakan ketik *ubah* untuk memperbaiki data atau hubungi admin.");

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Pilihan channel pembayaran dari list WA: buat transaksi DOKU sesuai bank/QRIS.
     * ID tombol: sckpay|TOKEN|CHANNEL
     */
    private function _handlePaySelection(string $phone, string $payload)
    {
        $parts = explode('|', $payload);
        $token = $parts[0] ?? '';
        $channelKey = strtoupper((string) ($parts[1] ?? ''));

        $draft = BotOrderDraft::where('phone_number', $phone)->first();

        if (!$draft || !$draft->invoice_number) {
            $this->_sendWhatsAppText($phone, "ℹ️ Tidak ada tagihan aktif. Silakan mulai lagi dengan menanyakan ongkir.");

            return response()->json(['status' => 'ignored', 'reason' => 'no_invoice']);
        }

        if (!isset(self::WA_PAYMENT_CHANNELS[$channelKey])) {
            $this->_sendWhatsAppText($phone, "⚠️ Metode pembayaran tidak dikenali. Silakan pilih dari daftar yang dikirim.");

            return response()->json(['status' => 'invalid_channel', 'channel' => $channelKey], 422);
        }

        // Bila channel & tagihan sudah pernah dibuat, kirim ulang tautannya.
        if ($draft->payment_url) {
            $this->_sendWhatsAppText(
                $phone,
                "💳 *" . self::WA_PAYMENT_CHANNELS[$channelKey]['label'] . "*\n"
                . "🧾 Invoice: *{$draft->invoice_number}*\n\n"
                . "Lanjutkan pembayaran di tautan berikut:\n{$draft->payment_url}"
            );

            return response()->json([
                'status'      => 'already_created',
                'invoice'     => $draft->invoice_number,
                'payment_url' => $draft->payment_url,
            ]);
        }

        $order = Pesanan::where('nomor_invoice', $draft->invoice_number)->first();

        if (!$order) {
            $this->_sendWhatsAppText($phone, "❌ Pesanan tidak ditemukan. Silakan mulai ulang pemesanan.");

            return response()->json(['status' => 'error', 'message' => 'order_not_found'], 404);
        }

        $channel = self::WA_PAYMENT_CHANNELS[$channelKey];

        try {
            $doku = new DokuJokulService();
            $customer = User::where('no_hp', $this->_sanitizePhoneNumber((string) $draft->sender_phone))->first();

            $result = $doku->createSpecificCheckoutPayment(
                $order->nomor_invoice,
                (int) $draft->shipping_cost,
                [
                    'name'  => $draft->sender_name,
                    'email' => $customer->email ?? 'customer@tokosancaka.com',
                    'phone' => $this->_sanitizePhoneNumber((string) $draft->sender_phone),
                ],
                'DOKU_' . $channelKey
            );

            if (empty($result['success']) || empty($result['payment_url'])) {
                $msg = $result['message'] ?? 'Gagal membuat tagihan DOKU.';
                Log::error('DOKU channel payment gagal', ['invoice' => $order->nomor_invoice, 'channel' => $channelKey, 'message' => $msg]);
                $this->_sendWhatsAppText($phone, "❌ {$msg}\n\nSilakan pilih metode pembayaran lain.");

                return response()->json(['status' => 'error', 'message' => $msg], 502);
            }

            $paymentUrl = $result['payment_url'];

            $order->payment_url = $paymentUrl;
            $order->payment_method = 'DOKU';
            $order->save();

            $draft->payment_url = $paymentUrl;
            $draft->step = 'AWAITING_PAYMENT';
            $draft->save();

            $this->_sendWhatsAppText(
                $phone,
                "💳 *" . $channel['label'] . "*\n"
                . "🧾 Invoice: *{$order->nomor_invoice}*\n"
                . "💰 Total: *Rp " . number_format((int) $draft->shipping_cost, 0, ',', '.') . "*\n\n"
                . "Selesaikan pembayaran di tautan berikut:\n{$paymentUrl}\n\n"
                . "Pesanan otomatis diproses setelah pembayaran terkonfirmasi."
            );

            Log::info('Tagihan DOKU dibuat dari pilihan channel WhatsApp.', [
                'invoice' => $order->nomor_invoice,
                'channel' => $channelKey,
            ]);

            return response()->json([
                'status'      => 'success',
                'invoice'     => $order->nomor_invoice,
                'channel'     => $channelKey,
                'payment_url' => $paymentUrl,
            ]);
        } catch (Throwable $e) {
            Log::error('Exception pembuatan tagihan DOKU: ' . $e->getMessage());
            $this->_sendWhatsAppText($phone, "❌ Terjadi kesalahan saat membuat tagihan. Silakan coba lagi.");

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Kirim pesan interaktif bertipe button.
     */
    private function _sendWhatsAppButtons(string $phone, string $body, array $buttons): bool
    {
        $rows = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            $rows[] = [
                'id'    => $btn['id'],
                'title' => Str::limit($btn['title'], 20, ''),
            ];
        }

        return $this->_sendWhatsAppPayload([
            'phone_number' => $phone,
            'channel'      => 'whatsapp',
            'message_type' => 'interactive',
            'interactive'  => [
                'type'   => 'button',
                'header' => ['type' => 'text', 'text' => 'SANCAKA EXPRESS'],
                'body'   => ['text' => $body],
                'footer' => ['text' => 'Data dapat diubah sebelum dibayar'],
                'action' => ['buttons' => $rows],
            ],
        ]);
    }

    /**
     * Normalisasi nomor ke format 62xxxxxxxxxx tanpa simbol.
     */
    private function _normalizeWaPhone(string $value): string
    {
        $digits = preg_replace('/\D/', '', explode('@', $value)[0]);

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    private function _sendWhatsAppPayload(array $payload): bool
    {
        $apiKey = (string) config('services.apicoid.api_key');

        if ($apiKey === '') {
            Log::error('API.co.id WhatsApp API key belum dikonfigurasi.');
            return false;
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post('https://chat.api.co.id/api/v1/public/messages/send', $payload);

            // Window 24 jam tertutup (customer belum pernah chat) -> kirim template dulu.
            if ($this->_isWhatsAppWindowClosed($response)) {
                $phone = (string) ($payload['phone_number'] ?? '');
                if ($phone !== '' && $this->_sendWhatsAppOpeningTemplate($phone)) {
                    $response = Http::withToken($apiKey)
                        ->acceptJson()
                        ->asJson()
                        ->timeout(30)
                        ->post('https://chat.api.co.id/api/v1/public/messages/send', $payload);
                }
            }

            // Nomor pengirim belum terdaftar sebagai customer -> daftarkan lalu kirim ulang.
            if ($this->_isApiCoIdCustomerMissing($response)) {
                $phone = (string) ($payload['phone_number'] ?? '');
                if ($phone !== '' && $this->_ensureWhatsAppCustomer($phone)) {
                    $response = Http::withToken($apiKey)
                        ->acceptJson()
                        ->asJson()
                        ->timeout(30)
                        ->post('https://chat.api.co.id/api/v1/public/messages/send', $payload);
                }
            }

            if (!$response->successful() || $response->json('success') !== true) {
                Log::error('API.co.id WhatsApp send gagal.', [
                    'http_status' => $response->status(),
                    'response' => $response->json(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('API.co.id WhatsApp HTTP exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * True bila gateway menolak karena jendela 24 jam WhatsApp tertutup.
     */
    private function _isWhatsAppWindowClosed($response): bool
    {
        $code = (string) ($response->json('error.code') ?? '');
        $message = (string) ($response->json('error.message') ?? '');

        return $code === 'WindowClosed'
            || stripos($message, 'Only template messages allowed') !== false;
    }

    /**
     * Kirim template pembuka agar jendela percakapan terbuka.
     * Nama template dari APICOID_TEMPLATE_NAME; bila belum diset, tidak melakukan apa pun.
     */
    private function _sendWhatsAppOpeningTemplate(string $phone): bool
    {
        $apiKey = (string) config('services.apicoid.api_key');
        $template = (string) config('services.apicoid.template_name');

        if ($apiKey === '' || $template === '') {
            Log::warning('API.co.id: template pembuka belum dikonfigurasi (APICOID_TEMPLATE_NAME).', [
                'phone' => $phone,
            ]);

            return false;
        }

        $payload = [
            'phone_number' => $phone,
            'channel'      => 'whatsapp',
            'message_type' => 'template',
            'template'     => [
                'name'     => $template,
                // API.co.id minta object: {"code": "id"} — string ditolak VALIDATION_ERROR
                'language' => ['code' => (string) (config('services.apicoid.template_language') ?: 'id')],
            ],
        ];

        $phoneNumberId = (string) config('services.apicoid.phone_number_id');
        if ($phoneNumberId !== '') {
            $payload['whatsapp_phone_number_id'] = $phoneNumberId;
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post('https://chat.api.co.id/api/v1/public/messages/send', $payload);

            if (!$response->successful() || $response->json('success') !== true) {
                Log::error('API.co.id: template pembuka gagal.', [
                    'phone' => $phone,
                    'template' => $template,
                    'http_status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return false;
            }

            Log::info('API.co.id: template pembuka terkirim.', ['phone' => $phone, 'template' => $template]);

            return true;
        } catch (\Throwable $e) {
            Log::error('API.co.id template exception: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * True bila gateway menolak karena nomor belum terdaftar sebagai customer.
     */
    private function _isApiCoIdCustomerMissing($response): bool
    {
        if ((int) $response->status() !== 404) {
            return false;
        }

        $message = (string) ($response->json('error.message') ?? '');

        return stripos($message, 'Customer not found') !== false;
    }

    /**
     * Daftarkan nomor sebagai customer API.co.id (idempoten: cek dulu).
     * Nomor dinormalisasi ke format 62... tanpa simbol.
     */
    private function _ensureWhatsAppCustomer(string $phone): bool
    {
        $apiKey = (string) config('services.apicoid.api_key');
        if ($apiKey === '') {
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '62') || strlen($phone) < 10) {
            return false;
        }

        try {
            // Sudah ada? tidak perlu daftar lagi.
            $existing = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(20)
                ->get('https://chat.api.co.id/api/v1/public/customers', ['phone_number' => $phone]);

            if ($existing->successful()) {
                foreach ((array) $existing->json('data', []) as $customer) {
                    if (is_array($customer) && ($customer['phone_number'] ?? '') === $phone) {
                        return true;
                    }
                }
            }

            $created = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->post('https://chat.api.co.id/api/v1/public/customers', array_filter([
                    'phone_number' => $phone,
                    'channel'      => 'whatsapp',
                    // Wajib menurut API.co.id (ValidationError bila kosong).
                    'whatsapp_phone_number_id' => (string) config('services.apicoid.phone_number_id'),
                ], function ($v) {
                    return $v !== null && $v !== '';
                }));

            if ($created->successful()) {
                Log::info('API.co.id: customer WhatsApp didaftarkan otomatis.', ['phone' => $phone]);

                return true;
            }

            Log::warning('API.co.id: gagal mendaftarkan customer WhatsApp.', [
                'phone' => $phone,
                'http_status' => $created->status(),
                'response' => $created->json(),
            ]);
        } catch (\Throwable $e) {
            Log::error('API.co.id customer register exception: ' . $e->getMessage());
        }

        return false;
    }


    /**
     * Memetakan teks alamat dari AI ke District ID menggunakan API KiriminAja
     */
    private function _resolveAddress(string $addressText): ?array
    {
        try {
            $results = $this->kirimaja->searchAddress($addressText);

            // Ambil hasil pencarian pertama yang paling relevan.
            // Struktur respons KiriminAja: province_id, city_id, district_id,
            // subdistrict_id, full_address (tidak ada 'text' / 'zipcode').
            if (!empty($results['status']) && !empty($results['data']) && is_array($results['data'])) {
                foreach ($results['data'] as $candidate) {
                    if (!is_array($candidate)) {
                        continue;
                    }

                    $districtId    = $candidate['district_id'] ?? null;
                    $subdistrictId = $candidate['subdistrict_id'] ?? null;

                    // district_id & subdistrict_id wajib ada dan berupa ID positif.
                    if (!is_numeric($districtId) || !is_numeric($subdistrictId)
                        || (int) $districtId <= 0 || (int) $subdistrictId <= 0) {
                        continue;
                    }

                    return [
                        'province_id'    => isset($candidate['province_id']) && is_numeric($candidate['province_id'])
                                                ? (int) $candidate['province_id'] : null,
                        'city_id'        => isset($candidate['city_id']) && is_numeric($candidate['city_id'])
                                                ? (int) $candidate['city_id'] : null,
                        'district_id'    => (int) $districtId,
                        'subdistrict_id' => (int) $subdistrictId,
                        // Api tidak selalu mengirim zipcode; biarkan null (kolom nullable).
                        'postal_code'    => isset($candidate['zipcode']) && $candidate['zipcode'] !== ''
                                                ? (string) $candidate['zipcode'] : null,
                        'full_text'      => $candidate['full_address'] ?? null,
                    ];
                }
            }
            return null;
        } catch (Exception $e) {
            Log::error("Geocoding API KiriminAja Gagal: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Kirim daftar kurir interaktif ke WhatsApp via API.co.id.
     * Mengembalikan status ringkas untuk respons JSON bot.
     */
    private function _sendShippingListToWhatsApp(string $phoneInput, array $options, array $meta): array
    {
        $phone = preg_replace('/[^0-9]/', '', $phoneInput);
        if (Str::startsWith($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (Str::startsWith($phone, '8')) {
            $phone = '62' . $phone;
        }

        if (!Str::startsWith($phone, '62') || strlen($phone) < 10) {
            return [
                'whatsapp_sent' => false,
                'message'       => 'Nomor WhatsApp harus format Indonesia, contoh 6281234567890.',
            ];
        }

        if (empty($options)) {
            return [
                'whatsapp_sent' => false,
                'message'       => 'Tidak ada layanan ekspedisi untuk dikirim.',
            ];
        }

        if ((string) config('services.apicoid.api_key') === '') {
            return [
                'whatsapp_sent' => false,
                'message'       => 'API.co.id belum dikonfigurasi di server (APICOID_API_KEY).',
            ];
        }

        $token = Str::lower(Str::random(12));
        Cache::put('sck_wa_shipping_quote:' . $token, [
            'phone'   => $phone,
            'asal'    => $meta['asal'] ?? null,
            'tujuan'  => $meta['tujuan'] ?? null,
            'weight'  => $meta['weight'] ?? null,
            'options' => $options,
        ], now()->addMinutes(20));

        $sent = $this->_sendWhatsAppShippingList($phone, $token, 0, $options);

        if (!$sent) {
            Cache::forget('sck_wa_shipping_quote:' . $token);

            return [
                'whatsapp_sent' => false,
                'message'       => 'Gagal mengirim daftar kurir via API.co.id.',
            ];
        }

        return [
            'whatsapp_sent' => true,
            'phone'         => $phone,
            'jumlah_baris'  => min(count($options), 9),
            'quote_token'   => $token,
            'berlaku_menit' => 20,
        ];
    }

    /**
     * Ambil SEMUA opsi ekspedisi KiriminAja lalu normalisasi (read-only).
     */
    private function _getShippingOptions($senderDist, $senderSub, $recDist, $recSub, $weight, $itemValue): array
    {
        $options = $this->kirimaja->getExpressPricing(
            $senderDist, $senderSub, $recDist, $recSub,
            $weight, 1, 1, 1, $itemValue, null, 'regular', 0
        );

        if (!is_array($options) || ($options['status'] ?? false) !== true || empty($options['results'])) {
            return [];
        }

        $rows = [];
        foreach ((array) $options['results'] as $opt) {
            if (!is_array($opt)) {
                continue;
            }
            $cost = (int) ($opt['cost'] ?? $opt['final_price'] ?? 0);
            if ($cost <= 0) {
                continue;
            }
            $discount = (int) ($opt['discount_amount'] ?? 0);

            $rows[] = [
                'kurir'         => strtoupper((string) ($opt['service'] ?? 'unknown')),
                'layanan'       => (string) ($opt['service_name'] ?? $opt['service_type'] ?? ''),
                'kode'          => (string) ($opt['service_type'] ?? ''),
                'group'         => strtolower((string) ($opt['group'] ?? '')),
                'ongkir'        => max($cost - $discount, 0),
                'ongkir_normal' => $cost,
                'diskon'        => $discount,
                'etd'           => isset($opt['etd']) && $opt['etd'] !== '' ? (string) $opt['etd'] : null,
                'cod'           => (bool) ($opt['cod'] ?? false),
            ];
        }

        usort($rows, function ($a, $b) {
            return [$a['ongkir'], $a['kurir']] <=> [$b['ongkir'], $b['kurir']];
        });

        return $rows;
    }

    /**
     * Pilih opsi termurah dari daftar yang sudah dinormalisasi.
     */
    private function _pickCheapest(array $rows): ?array
    {
        if (empty($rows)) {
            return null;
        }

        return [
            'courier'      => $rows[0]['kurir'],
            'service_type' => $rows[0]['kode'] !== '' ? $rows[0]['kode'] : $rows[0]['layanan'],
            'cost'         => $rows[0]['ongkir'],
        ];
    }

    /**
     * Mencari ongkos kirim reguler dan mengambil yang paling murah
     */
    private function _getBestShippingCost($senderDist, $senderSub, $recDist, $recSub, $weight, $itemValue)
    {
        $options = $this->kirimaja->getExpressPricing(
            $senderDist, $senderSub, $recDist, $recSub,
            $weight, 1, 1, 1, $itemValue, null, 'regular', 0
        );

        if (isset($options['status']) && $options['status'] === true && !empty($options['results'])) {
            $bestCourier = null;
            $lowestPrice = PHP_INT_MAX;

            foreach ($options['results'] as $opt) {
                $cost = $opt['cost'] ?? $opt['final_price'] ?? 0;
                if ($cost > 0 && $cost < $lowestPrice) {
                    $lowestPrice = $cost;
                    $bestCourier = [
                        'courier'      => $opt['service'] ?? 'unknown',
                        'service_type' => $opt['service_type'] ?? 'reg',
                        'cost'         => $cost
                    ];
                }
            }
            return $bestCourier;
        }

        return null;
    }

    /**
     * Membuat Transaksi Tripay dan mengambil URL Checkout
     */
    private function _generateTripayUrl(Pesanan $order, int $amount, ?string $email): ?string
    {
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        $baseUrl = ($mode === 'production')
            ? 'https://tripay.co.id/api/transaction/create'
            : 'https://tripay.co.id/api-sandbox/transaction/create';

        $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', $mode);
        $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', $mode);
        $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', $mode);

        if (empty($apiKey)) return null;

        $expiredTime = time() + (24 * 60 * 60); // 24 Jam waktu bayar
        $customerEmail = !empty($email) ? $email : 'customer+' . Str::random(5) . '@tokosancaka.com';

        $payload = [
            'method'         => 'QRIS', // Default ke QRIS agar mudah via WA, atau kosongkan jika ingin Tripay menampilkan semua metode
            'merchant_ref'   => $order->nomor_invoice,
            'amount'         => $amount,
            'customer_name'  => $order->sender_name,
            'customer_email' => $customerEmail,
            'customer_phone' => $order->sender_phone,
            'order_items'    => [
                ['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim Bot', 'price' => $amount, 'quantity' => 1]
            ],
            'return_url'     => route('tracking.search', ['resi' => $order->nomor_invoice]),
            'expired_time'   => $expiredTime,
            'signature'      => hash_hmac('sha256', $merchantCode . $order->nomor_invoice . $amount, $privateKey),
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                            ->timeout(30)
                            ->post($baseUrl, $payload);

            $data = $response->json();
            if (isset($data['success']) && $data['success'] === true) {
                return $data['data']['checkout_url'] ?? null;
            }
            Log::error('Tripay Bot Creation Failed:', $data);
            return null;
        } catch (Exception $e) {
            Log::error('Tripay Bot HTTP Exception: ' . $e->getMessage());
            return null;
        }
    }

    private function _sanitizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (Str::startsWith($phone, '62')) {
            return '0' . substr($phone, 2);
        }
        if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) {
            return '0' . $phone;
        }
        return $phone;
    }
}
