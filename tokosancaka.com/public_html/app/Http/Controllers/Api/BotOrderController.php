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
            $customer = User::whereIn('no_wa', $this->_waPhoneVariants($sanitizedPhone))->first();
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


    public function createCustomerOrder(Request $request)
    {
        $validated = $request->validate([
            'phone'              => 'required|string|max:20',
            'sender_name'        => 'required|string|max:100',
            'sender_phone'       => 'required|string|max:20',
            'sender_address'     => 'required|string|min:10|max:255',
            'receiver_name'      => 'required|string|max:100',
            'receiver_phone'     => 'required|string|max:20',
            'receiver_address'   => 'required|string|min:10|max:255',
            'weight'             => 'required|numeric|min:1|max:300000',
            'item_value'         => 'nullable|numeric|min:0',
            'item_description'   => 'nullable|string|max:255',
            'kurir'              => 'nullable|string|max:50',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        } elseif (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $senderPhone = preg_replace('/[^0-9]/', '', $validated['sender_phone']);
        if (str_starts_with($senderPhone, '8')) {
            $senderPhone = '62' . $senderPhone;
        } elseif (str_starts_with($senderPhone, '0')) {
            $senderPhone = '62' . substr($senderPhone, 1);
        }

        DB::beginTransaction();
        try {
            $senderLocation = $this->_resolveAddress($validated['sender_address']);
            $receiverLocation = $this->_resolveAddress($validated['receiver_address']);

            if (!$senderLocation || !$receiverLocation) {
                throw new Exception("Gagal memetakan alamat.");
            }

            $weight = (int) ceil((float) $validated['weight']);
            $itemValue = (int) ($validated['item_value'] ?? 10000);

            $shippingCost = $this->_getBestShippingCost(
                $senderLocation['district_id'],
                $senderLocation['subdistrict_id'],
                $receiverLocation['district_id'],
                $receiverLocation['subdistrict_id'],
                $weight,
                $itemValue
            );

            if (!$shippingCost) {
                throw new Exception("Tidak ada layanan ekspedisi untuk rute ini.");
            }

            $customer = User::whereIn('no_wa', $this->_waPhoneVariants($phone))->first();
            $customerId = $customer ? $customer->id_pengguna : null;

            $nomorInvoice = 'SCK-CUST-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists()) {
                $nomorInvoice = 'SCK-CUST-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }

            $order = Pesanan::create([
                'nomor_invoice'           => $nomorInvoice,
                'id_pengguna_pembeli'     => $customerId,
                'customer_id'             => $customerId,
                'status'                  => 'Menunggu Pembayaran',
                'status_pesanan'          => 'Menunggu Pembayaran',
                'tanggal_pesanan'         => now(),
                'ip_address'              => $request->ip(),
                'user_agent'              => $request->userAgent(),
                'sender_name'             => $validated['sender_name'],
                'sender_phone'            => $senderPhone,
                'sender_address'          => $validated['sender_address'],
                'sender_district_id'      => $senderLocation['district_id'],
                'sender_subdistrict_id'   => $senderLocation['subdistrict_id'],
                'sender_postal_code'      => $senderLocation['postal_code'],
                'receiver_name'           => $validated['receiver_name'],
                'receiver_phone'          => $this->_sanitizePhoneNumber($validated['receiver_phone']),
                'receiver_address'        => $validated['receiver_address'],
                'receiver_district_id'    => $receiverLocation['district_id'],
                'receiver_subdistrict_id' => $receiverLocation['subdistrict_id'],
                'receiver_postal_code'    => $receiverLocation['postal_code'],
                'item_description'        => $validated['item_description'] ?? 'Barang',
                'item_value'              => $itemValue,
                'weight'                  => $weight,
                'service_type'            => $shippingCost['service_type'],
                'courier'                 => $shippingCost['courier'],
                'payment_method'          => 'DOKU',
                'shipping_cost'           => $shippingCost['cost'],
                'price'                   => $shippingCost['cost'],
                'ansuransi'               => 'tidak',
                'insurance_cost'          => 0,
                'cod_fee'                 => 0,
                'item_type'               => 1,
            ]);

            $dokuService = new DokuJokulService();
            $paymentUrl = $dokuService->createPayment(
                $order->nomor_invoice,
                $order->price,
                [
                    'name'  => $validated['sender_name'],
                    'phone' => $senderPhone,
                ],
                [],
                [],
                null
            );

            if (!$paymentUrl) {
                throw new Exception("Gagal membuat tautan pembayaran Doku.");
            }

            $order->payment_url = $paymentUrl;
            $order->save();

            DB::commit();

            $this->_sendPaymentWhatsApp($phone, $order, $paymentUrl);

            Log::info("Customer order sukses dibuat. Invoice: {$nomorInvoice}", [
                'phone' => $phone,
                'invoice' => $nomorInvoice,
                'price' => $order->price,
            ]);

            return response()->json([
                'status'       => 'success',
                'invoice'      => $order->nomor_invoice,
                'customer'     => [
                    'phone'  => $phone,
                    'name'   => $validated['sender_name'],
                ],
                'sender'       => [
                    'name'  => $validated['sender_name'],
                    'phone' => $senderPhone,
                    'address' => $validated['sender_address'],
                ],
                'receiver'     => [
                    'name'  => $validated['receiver_name'],
                    'phone' => $validated['receiver_phone'],
                    'address' => $validated['receiver_address'],
                ],
                'weight'       => $weight,
                'item_value'   => $itemValue,
                'shipping_cost' => $shippingCost['cost'],
                'total_cost'   => $shippingCost['cost'],
                'payment'      => [
                    'method'  => 'DOKU',
                    'url'     => $paymentUrl,
                    'message' => 'Silakan transfer melalui Virtual Account atau QRIS. Batas waktu 60 menit.',
                ],
                'message'      => 'Pesanan berhasil dibuat. Silakan lakukan pembayaran melalui VA atau QRIS.',
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CustomerOrder Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kirim detail pembayaran (VA + QRIS) ke WhatsApp customer.
     */
    private function _sendPaymentWhatsApp(string $phone, Pesanan $order, string $paymentUrl): bool
    {
        $message = "Pembayaran SCK
";
        $message .= "Invoice: {$order->nomor_invoice}
";
        $message .= "Nama: {$order->sender_name}
";
        $message .= "Total Bayar: Rp " . number_format($order->price, 0, ',', '.') . "
";
        $message .= "Metode: DOKU (Virtual Account / QRIS)
";
        $message .= "Batas Waktu: 60 menit

";
        $message .= "Silakan transfer melalui VA atau QRIS berikut:
";
        $message .= "Link: {$paymentUrl}

";
        $message .= "Balas *{$order->nomor_invoice}* jika sudah lunas.";

        $response = FonnteService::sendMessage($phone, $message);
        return $response->successful();
    }

    /**</span>
Endpoint READ-ONLY: cek ongkir KiriminAja.
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
     * Endpoint bot: status draft checkout untuk satu nomor WhatsApp.
     * Dipakai Hermes sebagai guard: pelanggan di tengah alur wajib diloloskan.
     */
    public function botDraftStatus(Request $request)
    {
        if (!$this->_botAuthorized($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        $validated = $request->validate([
            'phone_number' => 'required|string|min:8|max:20',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone_number']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Nomor bisa tersimpan dalam beberapa format; cek semuanya.
        $variants = array_values(array_unique(array_filter([
            $phone,
            '0' . substr($phone, 2) ?: null,
            str_starts_with($phone, '62') ? '+' . $phone : null,
        ])));

        $draft = BotOrderDraft::whereIn('phone_number', $variants)
            ->orderByDesc('updated_at')
            ->first();

        if (!$draft) {
            return response()->json([
                'status' => 'success',
                'active' => false,
                'step'   => null,
            ]);
        }

        $step = strtoupper((string) $draft->step);
        $active = !in_array($step, ['COMPLETED', 'DONE', 'PAID', 'CANCEL', 'CANCELLED'], true);

        // Draft kedaluwarsa: tidak diubah > 2 jam -> anggap tidak aktif.
        if ($active && $draft->updated_at && $draft->updated_at->lt(now()->subHours(2))) {
            $active = false;
        }

        return response()->json([
            'status' => 'success',
            'active' => $active,
            'step'   => $draft->step,
        ]);
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
        if (count($parts) !== 3 || !in_array($parts[0], ['sckq', 'scknext', 'sckprev'], true)) {
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

        if (in_array($parts[0], ['scknext', 'sckprev'], true)) {
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
        $draft = $this->_startDraftFromQuote($quote['phone'], $token, $selected, $quote);

        Log::info('[WA-KURIR] Kurir dipilih, draft dibuat', [
            'draft_id' => $draft->id ?? null,
            'phone'    => $quote['phone'],
            'kurir'    => $selected['kurir'],
            'layanan'  => $selected['layanan'] ?? $selected['kode'],
            'ongkir'   => $selected['ongkir'],
            'step'     => $draft->step ?? null,
        ]);

        $message = "✅ *Kurir dipilih: " . $selected['kurir'] . " - " . ($selected['layanan'] ?: $selected['kode']) . "*\n"
            . "💰 Ongkir: *Rp " . number_format((int) $selected['ongkir'], 0, ',', '.') . "* (" . (int) $quote['weight'] . " gram)\n\n"
            . "Sekarang lengkapi data pengiriman secara bertahap (10 langkah).\n\n"
            . "👤 *Langkah 1/10: Nama Pengirim*\n"
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
     * Mengirim maksimal 8 layanan + tombol navigasi (prev/next) jika diperlukan.
     * Batas total row WhatsApp List message adalah 10 baris.
     */
    private function _sendWhatsAppShippingList(string $phone, string $token, int $page, array $options): bool
    {
        $perPage = 8;
        $totalOptions = count($options);
        $totalPages = (int) ceil($totalOptions / $perPage);
        $page = max(0, min($page, max(0, $totalPages - 1)));
        $offset = $page * $perPage;
        $pageOptions = array_slice($options, $offset, $perPage);

        if (empty($pageOptions)) {
            return false;
        }

        $rows = [];

        // Navigasi prev jika bukan halaman pertama (page > 0)
        if ($page > 0) {
            $rows[] = [
                'id'          => 'sckprev|' . $token . '|' . ($page - 1),
                'title'       => '⬅️ Halaman Sebelumnya',
                'description' => 'Kembali ke halaman ' . $page . ' dari ' . $totalPages,
            ];
        }

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
                'id'          => 'sckq|' . $token . '|' . $index,
                'title'       => $title !== '' ? $title : 'Layanan ' . ($index + 1),
                'description' => Str::limit($description, 72, ''),
            ];
        }

        // Navigasi next jika masih ada halaman berikutnya
        if (($offset + $perPage) < $totalOptions) {
            $rows[] = [
                'id'          => 'scknext|' . $token . '|' . ($page + 1),
                'title'       => '➡️ Halaman Berikutnya',
                'description' => 'Lihat halaman ' . ($page + 2) . ' dari ' . $totalPages,
            ];
        }

        $pageIndicator = $totalPages > 1 ? ' (Hal ' . ($page + 1) . '/' . $totalPages . ')' : '';

        $interactive = [
            'type' => 'list',
            'header' => [
                'type' => 'text',
                'text' => 'SANCAKA EXPRESS',
            ],
            'body' => [
                'text' => "📦 *Pilihan layanan pengiriman*{$pageIndicator}\n"
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
                        'title' => Str::limit('Pilihan Layanan' . $pageIndicator, 24, ''),
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
        Log::info('[WA-WEBHOOK] Inbound diterima', [
            'ip'      => $request->ip(),
            'method'  => $request->method(),
            'headers' => array_keys($request->headers->all()),
            'payload' => $request->all(),
        ]);

        $webhookSecret = (string) config('services.apicoid.webhook_secret');
        $providedSecret = (string) $request->header('X-WhatsApp-Webhook-Secret', '');

        if ($webhookSecret !== '' &&
            ($providedSecret === '' || !hash_equals($webhookSecret, $providedSecret))) {
            Log::warning('Webhook inbound WhatsApp ditolak: secret tidak valid.');

            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        // Format API.co.id: {event_type, data:{customer_phone, message_type, content, raw:{...}}}
        $eventType = (string) (data_get($payload, 'event_type') ?? '');

        // WA_EVENT_FILTER_V1 — HANYA pesan MASUK customer yang boleh diproses.
        // message.sent / message.read / message.delivered adalah notifikasi
        // tentang pesan KELUAR kita sendiri. Memprosesnya membuat bot membalas
        // pesannya sendiri (balasan dobel, isi sama) dan merusak state checkout.
        $inboundEvents = ['message.received', 'message.inbound', 'message', 'incoming_message'];

        if ($eventType !== '' && !in_array(strtolower($eventType), $inboundEvents, true)) {
            Log::info('[WA-WEBHOOK] Dilewati: bukan pesan masuk', ['event_type' => $eventType]);

            return response()->json(['status' => 'ignored', 'reason' => 'not_inbound', 'event_type' => $eventType]);
        }

        // Abaikan pesan yang arahnya keluar (dikirim oleh nomor toko sendiri).
        $direction = strtolower((string) (data_get($payload, 'data.direction') ?? ''));
        if ($direction === 'outbound' || $direction === 'out') {
            Log::info('[WA-WEBHOOK] Dilewati: pesan keluar', ['event_type' => $eventType]);

            return response()->json(['status' => 'ignored', 'reason' => 'outbound']);
        }

        // Idempoten: webhook bisa dikirim ulang dengan message_id sama.
        $messageId = (string) (data_get($payload, 'data.message_id')
            ?? data_get($payload, 'data.id')
            ?? data_get($payload, 'message_id')
            ?? '');
        if ($messageId !== '') {
            $dedupeKey = 'wa_seen:' . md5($messageId);
            if (Cache::has($dedupeKey)) {
                Log::info('[WA-WEBHOOK] Dilewati: duplikat', ['message_id' => $messageId]);

                return response()->json(['status' => 'ignored', 'reason' => 'duplicate']);
            }
            Cache::put($dedupeKey, 1, now()->addHours(6));
        }

        $phone = data_get($payload, 'data.customer_phone')
            ?? data_get($payload, 'data.from')
            ?? data_get($payload, 'data.phone_number')
            ?? data_get($payload, 'data.raw.from')
            ?? data_get($payload, 'from')
            ?? data_get($payload, 'phone_number');

        if (!$phone) {
            Log::info('[WA-WEBHOOK] Dilewati: tanpa nomor pengirim', ['event_type' => $eventType]);

            return response()->json(['status' => 'ignored', 'reason' => 'no_phone']);
        }

        $phone = $this->_normalizeWaPhone((string) $phone);

        // Abaikan pesan keluar (event message.sent) agar tidak loop.
        if (($messageDirection = data_get($payload, 'data.direction')) === 'outbound') {
            return response()->json(['status' => 'ignored', 'reason' => 'outbound']);
        }

        $interactiveId = data_get($payload, 'data.interactive_response.id')
            ?? data_get($payload, 'data.interactive.button_reply.id')
            ?? data_get($payload, 'data.interactive.list_reply.id')
            ?? data_get($payload, 'data.message.interactive.button_reply.id')
            ?? data_get($payload, 'data.message.interactive.list_reply.id')
            ?? data_get($payload, 'data.raw.interactive.list_reply.id')
            ?? data_get($payload, 'data.raw.interactive.button_reply.id')
            ?? data_get($payload, 'data.raw.button.id')
            ?? data_get($payload, 'interactive_response.id');

        // 1. Interaktif: pilihan kurir / tombol konfirmasi / navigasi pagination / saklar API owner
        // Diproses terlebih dahulu sebelum deduplikasi teks (agar klik tombol 'Berikutnya' berulang tidak terblokir).
        if (is_string($interactiveId) && $interactiveId !== '') {
            if (str_starts_with($interactiveId, 'sck_mode|')) {
                $action = substr($interactiveId, 9);
                $ok = \App\Http\Controllers\Admin\ApiSettingsController::handleSaklarWhatsAppAction($phone, $action);
                return response()->json(['status' => $ok ? 'success' : 'forbidden', 'action' => $action]);
            }

            if (str_starts_with($interactiveId, 'sckq|') || str_starts_with($interactiveId, 'scknext|') || str_starts_with($interactiveId, 'sckprev|')) {
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
            data_get($payload, 'data.content')
            ?? data_get($payload, 'data.raw.text.body')
            ?? data_get($payload, 'data.message.text.body')
            ?? data_get($payload, 'data.text.body')
            ?? data_get($payload, 'data.text')
            ?? data_get($payload, 'text')
            ?? ''
        ));

        if ($text === '') {
            Log::info('[WA-WEBHOOK] Dilewati: tanpa teks', [
                'event_type' => $eventType,
                'type'       => data_get($payload, 'data.message_type'),
            ]);

            return response()->json(['status' => 'ignored', 'reason' => 'no_text']);
        }

        // 0. Perintah khusus Owner: /saklar
        if (trim(strtolower($text)) === '/saklar') {
            $isOwner = ($phone === \App\Http\Controllers\Admin\ApiSettingsController::OWNER_PHONE);
            if ($isOwner) {
                Log::info('[SAKLAR-WA] Perintah /saklar diterima dari nomor owner', ['phone' => $phone]);
                $sent = \App\Http\Controllers\Admin\ApiSettingsController::sendSaklarWhatsAppPrompt($phone);

                return response()->json([
                    'status'  => 'success',
                    'handled' => 'saklar_prompt',
                    'sent'    => $sent,
                ]);
            }
        }

        // CROSSPATH_DEDUPE_V1: khusus pesan teks biasa, cegah pemrosesan ganda dalam 90 detik.
        $crossKey = 'wa_seen_body:' . md5($phone . '|' . mb_strtolower($text));
        if (Cache::has($crossKey)) {
            Log::info('[WA-WEBHOOK] Dilewati: isi pesan sama dalam 90 detik', [
                'phone' => $phone,
            ]);

            return response()->json(['status' => 'ignored', 'reason' => 'duplicate_content']);
        }
        Cache::put($crossKey, 1, now()->addSeconds(90));

        // SECURITY_GATE_V1: tolak upaya mengakses sistem/server/kredensial.
        // Lapisan ini wajib ada di Laravel karena webhook bisa dipanggil
        // langsung tanpa melewati adapter Hermes.
        if ($this->_looksLikeAttack($text)) {
            Log::warning('[WA-SECURITY] Pesan terindikasi upaya akses sistem', [
                'phone' => $phone,
                'text'  => Str::limit($text, 120, ''),
            ]);

            $this->_sendWhatsAppText($phone,
                "Maaf kak, pesan itu tidak bisa saya proses 🙏\n\n"
                . "Saya hanya melayani:\n"
                . "• Cek ongkir\n"
                . "• Lacak resi\n"
                . "• Kirim paket\n"
                . "• Bayar tagihan\n\n"
                . "Silakan tulis kebutuhan pengirimannya ya."
            );

            return response()->json(['status' => 'success', 'handled' => 'blocked']);
        }

        // Sapaan / pilihan menu / permintaan layanan -> serahkan percakapan ke Hermes.
        // Bila ada draft aktif di tengah pengisian (AWAITING_*), state machine Laravel tetap melayani.
        $draft = BotOrderDraft::where('phone_number', $phone)->first();
        $isDraftActive = $draft && !in_array(strtoupper((string) $draft->step), ['COMPLETED', 'CANCELLED'], true);

        if (!$isDraftActive) {
            // DETEKSI NIAT: Biarkan Laravel mencegat teks untuk melihat apakah ini
            // permintaan cek ongkir, lacak resi, atau menu.
            $intent = $this->_handleMenuIntent($phone, $text);

            if ($intent === 'ongkir' || $intent === 'menu' || $intent === 'tracking') {
                // Laravel sudah menangani dan mengirim balasan (Tombol/Teks).
                // Hentikan proses, berikan respons agar Hermes Agent DIBAWAH tidak ikut menjawab.
                return response()->json([
                    'status' => 'success',
                    'handled' => 'by_laravel_state_machine',
                    'intent' => $intent
                ]);
            }

            // Jika bukan ongkir/menu/tracking (misal: "halo bot", "kamu bisa apa?"),
            // baru delegasikan ke AI Hermes untuk obrolan luwes.
            Log::info('[WA-WEBHOOK] Pesan dilewati untuk ditangani Hermes Agent', [
                'phone' => $phone,
                'text'  => Str::limit($text, 60, ''),
            ]);
            return response()->json(['status' => 'success', 'handled' => 'delegated_to_hermes']);
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
     * Parse permintaan cek ongkir dari teks bebas.
     * "cek ongkir ngawi ke surabaya 1kg" -> [asal, tujuan, berat_gram]
     * Return null bila asal/tujuan tidak terdeteksi.
     */
    private function _parseOngkirRequest(string $text): ?array
    {
        $body = strtolower(trim($text));

        // Berat: "1kg", "1 kg", "2,5 kg", "500 gram", "500g"
        $weight = null;
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|kilogram)\b/', $body, $m)) {
            $weight = (int) round(((float) str_replace(',', '.', $m[1])) * 1000);
        } elseif (preg_match('/(\d+)\s*(gram|gr|g)\b/', $body, $m)) {
            $weight = (int) $m[1];
        }

        // Buang bagian berat + kata perintah agar tersisa "asal ke tujuan"
        $clean = preg_replace('/\d+(?:[.,]\d+)?\s*(kg|kilogram|gram|gr|g)\b/', ' ', $body);
        $clean = preg_replace('/\b(cek|ongkir|ongkos|tarif|harga|biaya|kirim|berapa|berat|dari|kak|ya|dong|tolong|saya|mau|paket|adalah|itu)\b/', ' ', (string) $clean);
        $clean = trim(preg_replace('/\s+/', ' ', (string) $clean));

        // Pisahkan dengan " ke " / " - " / ">"
        $parts = preg_split('/\s+(?:ke|-|>|–|—|sampai)\s+/i', $clean);
        // PARTIAL_ONGKIR_V1: boleh hanya asal ("dari Ngawi") atau hanya
        // tujuan ("ke Surabaya 1 kg"); sisanya dilengkapi dari konteks nomor.
        if (!$parts) {
            return null;
        }

        $origin = trim($parts[0] ?? '');
        $destination = trim($parts[1] ?? '');

        // Hanya berlaku bila memang hanya SATU wilayah yang terbaca:
        //   "dari Ngawi"      -> asal saja
        //   "ke Surabaya 1kg" -> tujuan saja
        // Kasus dua wilayah ("ngawi ke surabaya") tidak disentuh.
        $hasDari = (bool) preg_match('/\bdari\b/', $body);
        $hasKe = (bool) preg_match('/\bke\b/', $body);
        $onlyOne = ($destination === '' && $origin !== '');

        if ($onlyOne) {
            if ($hasDari && !$hasKe) {
                $destination = '';
            } elseif ($hasKe && !$hasDari) {
                $destination = $origin;
                $origin = '';
            }
        }

        $origin = trim(preg_replace('/\b(dari|ke)\b/', ' ', $origin) ?? '');
        $destination = trim(preg_replace('/\b(dari|ke)\b/', ' ', $destination) ?? '');

        // PARSER_SANITY_V1: buang sisi yang jelas bukan nama wilayah
        // ("bisa ga", "ga jadi", "halo"), supaya bot tidak menanyakan rute
        // dari potongan kalimat.
        if ($origin !== '' && !$this->_looksLikeRegion($origin)) {
            $origin = '';
        }
        if ($destination !== '' && !$this->_looksLikeRegion($destination)) {
            $destination = '';
        }

        // PARTIAL_ONGKIR_V1: "cek ongkir ke surabaya 1kg" -> asal kosong.
        // Kirim saja yang ada; asal diisi pemanggil dari konteks nomor.
        if ($origin === '' && $destination === '') {
            return null;
        }

        return [
            'origin'      => $origin,
            'destination' => $destination,
            'weight'      => $weight ?: 1000,
        ];
    }

    /**
     * Jalankan cek ongkir dari teks pelanggan lalu kirim daftar tombol kurir.
     * Return true bila berhasil memproses permintaan.
     */
    private function _handleOngkirText(string $phone, string $text): bool
    {
        $parsed = $this->_parseOngkirRequest($text);
        if (!$parsed) {
            return false;
        }

        // PARTIAL_ONGKIR_V1: lengkapi dari konteks nomor bila sebagian kosong.
        $ctxKey = 'sck_ongkir_ctx:' . md5((string) $phone);
        $ctx = Cache::get($ctxKey) ?: [];

        $origin = trim((string) ($parsed['origin'] ?? ''));
        $destination = trim((string) ($parsed['destination'] ?? ''));

        if ($origin === '') {
            $origin = (string) ($ctx['origin'] ?? '');
        }
        if ($destination === '') {
            $destination = (string) ($ctx['destination'] ?? '');
        }

        // Asal tetap tidak diketahui -> tanya dengan ramah, bukan instruksi kaku.
        if ($origin === '') {
            if ($destination !== '') {
                Cache::put($ctxKey, ['destination' => $destination], now()->addHours(6));
                $this->_sendWhatsAppText($phone,
                    "Siap kak, tujuannya *{$destination}* 🙏\n\n"
                    . "Kota asal pengirimannya dari mana ya?\n"
                    . "Contoh: _dari Ngawi_"
                );

                return true;
            }

            return false;
        }

        if ($destination === '') {
            Cache::put($ctxKey, ['origin' => $origin], now()->addHours(6));
            $this->_sendWhatsAppText($phone,
                "Baik kak, asal dari *{$origin}* 🙏\n\n"
                . "Tujuannya ke kota mana? Sebutkan juga beratnya ya.\n"
                . "Contoh: _ke Surabaya 1 kg_"
            );

            return true;
        }

        $parsed['origin'] = $origin;
        $parsed['destination'] = $destination;

        try {
            $sender = $this->_resolveAddress($origin);
            $receiver = $this->_resolveAddress($destination);

            if (!$sender || !$receiver) {
                $this->_sendWhatsAppText(
                    $phone,
                    "⚠️ Wilayah tidak dikenali sistem ekspedisi.\n\n"
                    . "Coba tulis *Kelurahan, Kecamatan, Kota*, contoh:\n"
                    . "_cek ongkir Ketanggi Ngawi ke Wiyung Surabaya 1 kg_"
                );

                return true;
            }

            $options = $this->_getShippingOptions(
                $sender['district_id'],
                $sender['subdistrict_id'],
                $receiver['district_id'],
                $receiver['subdistrict_id'],
                (int) $parsed['weight'],
                10000
            );

            if (empty($options)) {
                $this->_sendWhatsAppText(
                    $phone,
                    "⚠️ Tidak ada layanan ekspedisi untuk rute *{$sender['full_text']}* → *{$receiver['full_text']}*."
                );

                return true;
            }

            // Ingat rute terakhir supaya pesan lanjutan cukup "ke X 2 kg".
            Cache::put($ctxKey, [
                'origin'      => $origin,
                'destination' => $destination,
                'weight'      => (int) $parsed['weight'],
            ], now()->addHours(6));

            $wa = $this->_sendShippingListToWhatsApp($phone, $options, [
                'asal'   => $sender['full_text'] ?? $parsed['origin'],
                'tujuan' => $receiver['full_text'] ?? $parsed['destination'],
                'weight' => (int) $parsed['weight'],
            ]);

            if (empty($wa['whatsapp_sent'])) {
                // Fallback: kirim ringkasan teks 3 termurah.
                $lines = ["🚚 *Ongkir " . ($sender['full_text'] ?? $parsed['origin']) . " → "
                    . ($receiver['full_text'] ?? $parsed['destination']) . "*\n"];

                foreach (array_slice($options, 0, 5) as $opt) {
                    $lines[] = "• *" . $opt['kurir'] . "* " . $opt['layanan']
                        . " — Rp " . number_format((int) $opt['ongkir'], 0, ',', '.')
                        . ($opt['etd'] ? " (" . $opt['etd'] . " hari)" : "");
                }

                $this->_sendWhatsAppText($phone, implode("\n", $lines));
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[WA-ONGKIR] Gagal proses cek ongkir teks: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Deteksi sapaan / pilihan menu / permintaan layanan, lalu kirim menu.
     * Return true bila sudah ditangani (jangan lanjut ke state machine).
     */
    private function _handleMenuIntent(string $phone, string $text): string
    {
        $body = strtolower(trim($text));
        if ($body === '') {
            return false;
        }

        // Bila ada draft aktif, state machine yang berhak menangani.
        $draft = BotOrderDraft::where('phone_number', $phone)->first();
        if ($draft && !in_array(strtoupper((string) $draft->step), ['COMPLETED', 'CANCELLED'], true)) {
            return false;
        }

        // Bersihkan penomoran & tanda baca di depan.
        $clean = preg_replace('/^(no\.?\s*)?0?[1-9]\)?[.:\-]?\s*/', '', $body);
        $clean = trim(preg_replace('/[^a-z0-9& ]+/', ' ', (string) $clean));

        $greetings = ['halo', 'hai', 'hi', 'hallo', 'assalamualaikum', 'selamat',
                      'pagi', 'siang', 'sore', 'malam', 'kak', 'min', 'admin',
                      'permisi', 'tes', 'test', 'p', 'menu', 'bantuan', 'mulai'];

        $isNumber = (bool) preg_match('/^(no\.?\s*)?0?[1-9]\)?[.:]?$/', $body);

        $isGreeting = $clean === ''
            || (bool) preg_match('/^[0-9]+$/', $body)
            || collect(explode(' ', $clean))->every(fn ($w) => in_array($w, $greetings, true));

        // Permintaan layanan langsung (cek ongkir / resi / kirim / bayar).
        $serviceHints = ['ongkir', 'ongkos', 'tarif', 'resi', 'lacak', 'tracking',
                         'kirim paket', 'kirim barang', 'mau kirim', 'tagihan',
                         'bayar', 'pulsa', 'top up', 'topup', 'invoice'];

        $isService = false;
        foreach ($serviceHints as $hint) {
            if (str_contains($body, $hint)) {
                $isService = true;
                break;
            }
        }

        // ONGKIR_CONTEXT_CONTINUE_V1: lanjutan percakapan ongkir.
        // "dari ngawi", "ke surabaya 2 kg" -> proses, bukan smalltalk.
        // ROUTE_INTENT_V1: "dari ngawi ke surabaya 1kg" tanpa kata "ongkir"
        // tetap jelas maksudnya permintaan tarif.
        if (!$isService) {
            $hasRoute = (bool) preg_match('/\b(dari|ke)\b/', $body)
                && (bool) preg_match('/\d+\s*(kg|kilogram|gram|gr)\b/', $body);

            if ($hasRoute && $this->_handleOngkirText($phone, $text)) {
                return 'ongkir';
            }
        }

        if (!$isGreeting && !$isNumber && !$isService) {
            $ctxKey = 'sck_ongkir_ctx:' . md5((string) $phone);
            $ctx = Cache::get($ctxKey);

            if (is_array($ctx) && !empty($ctx)) {
                $looksWilayah = (bool) preg_match('/\b(dari|ke)\b/', $body)
                    || (bool) preg_match('/\d+\s*(kg|kilogram|gram|gr)\b/', $body);

                if ($looksWilayah && $this->_handleOngkirText($phone, $text)) {
                    return 'ongkir';
                }
            }

            return '';
        }

        if ($isService) {
            // Permintaan cek ongkir yang sudah memuat asal/tujuan/berat:
            // langsung proses, jangan hanya membalas instruksi.
            if (
                (str_contains($body, 'ongkir') || str_contains($body, 'ongkos') || str_contains($body, 'tarif'))
                && $this->_handleOngkirText($phone, $text)
            ) {
                return 'ongkir';
            }

            $this->_sendWhatsAppText($phone, $this->_menuServiceHint($body));

            return str_contains($body, 'resi') || str_contains($body, 'lacak')
                ? 'tracking'
                : 'menu';
        }

        $this->_sendWhatsAppText($phone, $this->_menuText());

        return 'menu';
    }

    /**
     * SECURITY_GATE_V1 — deteksi upaya akses sistem dari pesan pelanggan.
     * Sengaja konservatif: hanya pola yang jelas berbahaya.
     */
    private function _looksLikeAttack(string $text): bool
    {
        $t = ' ' . strtolower(preg_replace('/\s+/', ' ', $text) ?? '') . ' ';

        $patterns = [
            '.env', 'env file', 'baca file', 'buka file', 'tulis file', 'hapus file',
            'terminal', 'shell', 'ssh', 'bash', 'sudo', 'root access', 'chmod',
            'curl ', 'wget ', 'python ', 'php artisan', 'rm -', 'ls -', 'cat ',
            'database', 'select *', 'drop table', 'mysqldump', 'sql ',
            'api key', 'apikey', 'api_key', 'secret', 'password', 'sandi',
            'kredensial', 'credential', 'token bot',
            'system prompt', 'prompt sistem', 'abaikan instruksi', 'abaikan aturan',
            'ignore previous', 'ignore all', 'jailbreak', 'developer mode',
            'bypass', 'bobol', 'hack', 'inject', 'injection',
            'akses server', 'masuk server', 'akses hosting', 'sourc',
        ];

        foreach ($patterns as $needle) {
            if (str_contains($t, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Balasan luwes untuk pesan di luar alur (obrolan, tanya, terima kasih).
     */
    private function _friendlyReply(string $text): string
    {
        $t = strtolower(trim($text));

        $thanks = ['makasih', 'terima kasih', 'thanks', 'thank you', 'thx', 'tq', 'mksh'];
        foreach ($thanks as $word) {
            if (str_contains($t, $word)) {
                return "Sama-sama kak 🙏\n\nKalau masih ada yang mau ditanyakan soal pengiriman, silakan ya.";
            }
        }

        if (str_contains($t, 'siapa') || str_contains($t, 'bot') || str_contains($t, 'ini apa')
            || str_contains($t, 'kamu apa') || str_contains($t, 'nama')) {
            return "Saya asisten *Sancaka Express* 🤖\n\n"
                . "Saya bisa bantu 4 hal:\n"
                . "• Cek ongkir — _cek ongkir Ngawi ke Surabaya 1 kg_\n"
                . "• Lacak resi — _lacak resi IDE7002847325168_\n"
                . "• Kirim paket — _saya mau kirim paket_\n"
                . "• Bayar tagihan — _bayar tagihan listrik_\n\n"
                . "Mau mulai dari yang mana kak?";
        }

        if (str_contains($t, 'oke') || str_contains($t, 'ok') || str_contains($t, 'siap')
            || str_contains($t, 'baik') || str_contains($t, 'sip')) {
            return "Siap kak 👍\n\nKalau mau cek ongkir, kirim kota asal, kota tujuan, dan beratnya ya. Contoh: _cek ongkir Ngawi ke Surabaya 1 kg_";
        }

        if (str_contains($t, '?') || str_contains($t, 'gimana') || str_contains($t, 'bagaimana')
            || str_contains($t, 'berapa') || str_contains($t, 'bisa')) {
            return "Boleh saya bantu jawab kak 🙂\n\n"
                . "Supaya tepat, tulis kebutuhannya seperti ini:\n"
                . "• _cek ongkir Ngawi ke Surabaya 1 kg_\n"
                . "• _lacak resi IDE7002847325168_\n"
                . "• _saya mau kirim paket_\n"
                . "• _bayar tagihan listrik_\n\n"
                . "Kalau butuh hal lain, tulis saja — nanti saya arahkan.";
        }

        return "Baik kak, saya catat 🙂\n\n"
            . "Untuk hal-hal ini saya bisa bantu langsung:\n"
            . "• *Cek ongkir* — _cek ongkir Ngawi ke Surabaya 1 kg_\n"
            . "• *Lacak resi* — _lacak resi IDE7002847325168_\n"
            . "• *Kirim paket* — _saya mau kirim paket_\n"
            . "• *Bayar tagihan* — _bayar tagihan listrik_\n\n"
            . "Ketik salah satu contoh di atas ya kak.";
    }

    /**
     * Teks menu layanan utama.
     */
    private function _menuText(): string
    {
        return "Halo kak! 👋 Saya asisten *Sancaka Express*.\n\n"
            . "Silakan pilih layanan dengan mengetik angkanya:\n\n"
            . "*1* — Cek ongkir pengiriman\n"
            . "*2* — Lacak resi / tracking paket\n"
            . "*3* — Kirim paket (pesan pengiriman)\n"
            . "*4* — Bayar tagihan (listrik, pulsa, dll)\n\n"
            . "Contoh: ketik *1* lalu kirim asal dan tujuan paket Anda.";
    }

    /**
     * Instruksi lanjutan sesuai layanan yang disebut.
     */
    private function _menuServiceHint(string $body): string
    {
        if (str_contains($body, 'resi') || str_contains($body, 'lacak') || str_contains($body, 'tracking')) {
            return "📦 *Lacak Resi*\n\nKetik nomor resi Anda, contoh:\n_lacak resi IDE7002847325168_";
        }

        if (str_contains($body, 'ongkir') || str_contains($body, 'ongkos') || str_contains($body, 'tarif')) {
            return "🚚 *Cek Ongkir*\n\nKetik asal, tujuan, dan berat paket. Contoh:\n"
                . "_cek ongkir Ngawi ke Surabaya 1 kg_";
        }

        if (str_contains($body, 'kirim paket') || str_contains($body, 'kirim barang') || str_contains($body, 'mau kirim')) {
            return "📮 *Kirim Paket*\n\nKetik asal, tujuan, dan berat paket untuk melihat pilihan kurir. Contoh:\n"
                . "_kirim paket dari Ngawi ke Bandung 2 kg_";
        }

        return "💳 *Bayar Tagihan*\n\nSebutkan jenis tagihan Anda, contoh:\n_bayar tagihan listrik_";
    }

    /**
     * Handler teks bertahap: satu langkah per pesan.
     */
    private function _handleStepInput(string $phone, string $text)
    {
        $draft = BotOrderDraft::where('phone_number', $phone)->first();

        if (!$draft) {
            // FRIENDLY_FALLBACK_V1: jangan diam. Customer yang mengobrol atau
            // menulis hal di luar alur tetap mendapat jawaban yang mengarahkan.
            $this->_sendWhatsAppText($phone, $this->_friendlyReply($text));

            return response()->json(['status' => 'success', 'handled' => 'smalltalk']);
        }

        // CANCEL_INTENT_V1: pesan batal JANGAN disimpan sebagai data langkah.
        // Dulu "ga jadi kirim" tersimpan menjadi nama pengirim.
        if ($this->_isCancelIntent($text)) {
            $invoice = $draft->invoice_number ?? null;
            $draft->delete();

            Cache::forget('sck_ongkir_ctx:' . md5((string) $phone));

            Log::info('[WA-CANCEL] Draft dibatalkan pelanggan', [
                'phone'   => $phone,
                'invoice' => $invoice,
                'text'    => Str::limit($text, 60, ''),
            ]);

            $this->_sendWhatsAppText(
                $phone,
                "Baik kak, pesanan dibatalkan 👍\n\n"
                . "Tidak ada data yang tersimpan.\n"
                . "Kalau nanti mau kirim paket lagi, tinggal bilang saja ya."
            );

            return response()->json(['status' => 'success', 'handled' => 'cancelled']);
        }

        $text = Str::limit($text, 250, '');

        switch ($draft->step) {
            case 'AWAITING_SENDER_NAME':
                $draft->sender_name = $text;
                $draft->step = 'AWAITING_SENDER_PHONE';
                $this->_sendWhatsAppText($phone, "✅ Nama pengirim: *{$text}*\n\n📱 *Langkah 2/10: No. HP Pengirim*\nKetik nomor HP pengirim (contoh: 0857xxxxxxx)\natau ketik *sama* untuk memakai nomor WhatsApp ini.");
                break;

            case 'AWAITING_SENDER_PHONE':
                $value = strtolower($text) === 'sama' ? $phone : $this->_normalizeWaPhone($text);
                if (strlen(preg_replace('/\D/', '', $value)) < 9) {
                    $this->_sendWhatsAppText($phone, "⚠️ Nomor HP tidak valid. Ketik ulang nomor HP pengirim (contoh: 0857xxxxxxx).");

                    return response()->json(['status' => 'invalid_input', 'step' => $draft->step]);
                }
                $draft->sender_phone = $value;
                $draft->step = 'AWAITING_SENDER_AREA';
                $this->_sendWhatsAppText($phone, "✅ No. HP pengirim tersimpan.\n\n🏙️ *Langkah 3/10: Kelurahan, Kecamatan, Kota Pengirim*\nKetik *Kelurahan, Kecamatan, Kota* (contoh: Ketanggi, Ngawi, Ngawi).\n\n⚠️ Jangan tulis nama jalan di sini — alamat jalan ditanyakan pada langkah berikutnya.");
                break;

            case 'AWAITING_SENDER_AREA':
                $area = $this->_resolveAddress($text);
                if (!$area) {
                    $this->_sendWhatsAppText($phone, "⚠️ Wilayah *{$text}* tidak dikenali sistem ekspedisi.\n\nKetik ulang dengan format *Kelurahan, Kecamatan, Kota* (contoh: Ketanggi, Ngawi, Ngawi).");

                    return response()->json(['status' => 'invalid_area', 'step' => $draft->step]);
                }
                $draft->origin_text = $area['full_text'] ?: $text;
                $draft->sender_address = $area['full_text'] ?: $text;
                $draft->step = 'AWAITING_SENDER_ADDRESS';
                $this->_sendWhatsAppText($phone, "✅ Wilayah pengirim: *{$draft->origin_text}*\n\n📍 *Langkah 4/10: Detail Alamat Pengirim*\nKetik nama jalan, nomor rumah, RT/RW (contoh: Jl Dr Wahidin No 18 A RT 22 RW 05).");
                break;

            case 'AWAITING_SENDER_ADDRESS':
                if (strlen($text) < 5) {
                    $this->_sendWhatsAppText($phone, "⚠️ Alamat terlalu pendek. Ketik nama jalan dan nomor rumah (contoh: Jl Dr Wahidin No 18 A RT 22 RW 05).");

                    return response()->json(['status' => 'invalid_input', 'step' => $draft->step]);
                }
                $draft->sender_address = trim(($draft->origin_text ? $draft->origin_text . ', ' : '') . $text);
                $draft->step = 'AWAITING_RECEIVER_NAME';
                $this->_sendWhatsAppText($phone, "✅ Alamat pengirim tersimpan.\n\n👤 *Langkah 5/10: Nama Penerima*\nKetik nama lengkap penerima paket.");
                break;

            case 'AWAITING_RECEIVER_NAME':
                $draft->receiver_name = $text;
                $draft->step = 'AWAITING_RECEIVER_PHONE';
                $this->_sendWhatsAppText($phone, "✅ Nama penerima: *{$text}*\n\n📱 *Langkah 6/10: No. HP Penerima*\nKetik nomor HP penerima.");
                break;

            case 'AWAITING_RECEIVER_PHONE':
                $value = $this->_normalizeWaPhone($text);
                if (strlen(preg_replace('/\D/', '', $value)) < 9) {
                    $this->_sendWhatsAppText($phone, "⚠️ Nomor HP tidak valid. Ketik ulang nomor HP penerima.");

                    return response()->json(['status' => 'invalid_input', 'step' => $draft->step]);
                }
                $draft->receiver_phone = $value;
                $draft->step = 'AWAITING_RECEIVER_AREA';
                $this->_sendWhatsAppText($phone, "✅ No. HP penerima tersimpan.\n\n🏙️ *Langkah 7/10: Kelurahan, Kecamatan, Kota Penerima*\nKetik *Kelurahan, Kecamatan, Kota* (contoh: Wiyung, Wiyung, Surabaya).\n\n⚠️ Jangan tulis nama jalan di sini — alamat jalan ditanyakan pada langkah berikutnya.");
                break;

            case 'AWAITING_RECEIVER_AREA':
                $area = $this->_resolveAddress($text);
                if (!$area) {
                    $this->_sendWhatsAppText($phone, "⚠️ Wilayah *{$text}* tidak dikenali sistem ekspedisi.\n\nKetik ulang dengan format *Kelurahan, Kecamatan, Kota* (contoh: Wiyung, Wiyung, Surabaya).");

                    return response()->json(['status' => 'invalid_area', 'step' => $draft->step]);
                }
                $draft->destination_text = $area['full_text'] ?: $text;
                $draft->receiver_address = $area['full_text'] ?: $text;
                $draft->step = 'AWAITING_RECEIVER_ADDRESS';
                $this->_sendWhatsAppText($phone, "✅ Wilayah penerima: *{$draft->destination_text}*\n\n📍 *Langkah 8/10: Detail Alamat Penerima*\nKetik nama jalan/perumahan, nomor rumah, RT/RW (contoh: Perum Graha Kebraon Regency 2 No A3).");
                break;

            case 'AWAITING_RECEIVER_ADDRESS':
                if (strlen($text) < 5) {
                    $this->_sendWhatsAppText($phone, "⚠️ Alamat terlalu pendek. Ketik nama jalan/perumahan dan nomor rumah.");

                    return response()->json(['status' => 'invalid_input', 'step' => $draft->step]);
                }
                $draft->receiver_address = trim(($draft->destination_text ? $draft->destination_text . ', ' : '') . $text);
                $draft->step = 'AWAITING_ITEM_DESC';
                $this->_sendWhatsAppText($phone, "✅ Alamat penerima tersimpan.\n\n📦 *Langkah 9/10: Isi Paket*\nKetik deskripsi isi paket (contoh: baju kaos 2 pcs).");
                break;

            case 'AWAITING_ITEM_DESC':
                $draft->item_description = $text;
                $draft->step = 'AWAITING_ITEM_VALUE';
                $this->_sendWhatsAppText($phone, "✅ Isi paket: *{$text}*\n\n💰 *Langkah 10/10: Nilai Barang*\nKetik nilai barang dalam rupiah (contoh: 150000). Nilai ini untuk asuransi pengiriman.");
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

        $this->_sendWhatsAppText($phone, "✏️ Data direset. Mari isi ulang.\n\n👤 *Langkah 1/10: Nama Pengirim*\nKetik nama lengkap pengirim:");

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
            // Resolve memakai wilayah (origin/destination_text) yang sudah
            // tervalidasi di langkah AREA; alamat jalan hanya pelengkap.
            $senderLocation = $this->_resolveAddress((string) ($draft->origin_text ?: $draft->sender_address));
            $receiverLocation = $this->_resolveAddress((string) ($draft->destination_text ?: $draft->receiver_address));

            if (!$senderLocation || !$receiverLocation) {
                throw new Exception('Alamat tidak dapat dipetakan ke kode area ekspedisi. Silakan perbaiki alamat.');
            }

            $sanitizedPhone = $this->_sanitizePhoneNumber((string) $draft->sender_phone);
            $customer = User::whereIn('no_wa', $this->_waPhoneVariants($sanitizedPhone))->first();
            $customerId = $customer ? $customer->id_pengguna : null;

            do {
                $nomorInvoice = 'SCK-BOT-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

            $itemValue = (int) $draft->item_value;

            // PRICE_GUARD_V1: harga quote bisa basi/berbeda dari KiriminAja.
            // Samakan dengan harga real-time sebelum pesanan dibuat, supaya
            // pickup tidak ditolak dan customer tidak membayar lebih.
            $quotedCost = (int) $draft->shipping_cost;
            $finalCost = $quotedCost;
            $realtime = $this->_resolveRealtimeCost(
                $senderLocation, $receiverLocation, (int) $draft->weight,
                $itemValue, (string) $draft->courier_code, (string) $draft->courier_service
            );

            if ($realtime !== null && $realtime > 0 && $realtime !== $quotedCost) {
                Log::warning('[PRICE-GUARD] Selisih ongkir draft ' . $draft->id
                    . ': quote=' . $quotedCost . ' realtime=' . $realtime
                    . ' kurir=' . $draft->courier_code);

                $finalCost = $realtime;
                $draft->shipping_cost = $realtime;
                $draft->save();

                $this->_sendWhatsAppText(
                    $phone,
                    "ℹ️ *Tarif diperbarui*\n\n"
                    . "Ongkir menyesuaikan tarif ekspedisi terkini:\n"
                    . "• Sebelumnya: Rp " . number_format($quotedCost, 0, ',', '.') . "\n"
                    . "• Tarif berlaku: *Rp " . number_format($realtime, 0, ',', '.') . "*\n\n"
                    . "Mohon konfirmasi ulang pada ringkasan berikut."
                );
            }

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
                'shipping_cost'           => $finalCost,
                'price'                   => $finalCost,
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
            $customer = User::whereIn('no_wa', $this->_waPhoneVariants($this->_sanitizePhoneNumber((string) $draft->sender_phone)))->first();

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
    public function sendRawButtons(string $phone, string $body, array $buttons): bool
    {
        return $this->_sendWhatsAppButtons($phone, $body, $buttons);
    }

    /**
     * Kirim pesan teks.
     */
    public function sendRawText(string $phone, string $message): bool
    {
        return $this->_sendWhatsAppText($phone, $message);
    }

    private function _sendWhatsAppButtons(string $phone, string $body, array $buttons): bool
    {
        $rows = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            // API.co.id mewajibkan wrapper {type:"reply", reply:{id,title}}.
            $rows[] = [
                'type'  => 'reply',
                'reply' => [
                    'id'    => $btn['id'],
                    'title' => Str::limit($btn['title'], 20, ''),
                ],
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
    /**
     * ADDRESS_SCORE_V1 — resolusi alamat dengan skor kecocokan.
     *
     * Mengambil kandidat pertama ber-ID valid itu berbahaya: 'surabaya'
     * bisa jatuh ke "Surabaya Baru, Bandar Surabaya, Lampung Tengah".
     * Di sini setiap kandidat dinilai dulu, lalu yang tertinggi dipakai.
     */
    /**
     * ADDRESS_SCORE_V3 — kota besar -> provinsi.
     *
     * KiriminAja butuh provinsi untuk membedakan kota bernama sama.
     * Tanpa ini 'surabaya' bisa berarti desa di Lampung.
     */
    /**
     * CANCEL_INTENT_V1 — deteksi niat membatalkan proses.
     * Hanya dipakai saat ada draft aktif, agar tidak salah menangkap nama.
     */
    private function _isCancelIntent(string $text): bool
    {
        $t = ' ' . strtolower(preg_replace('/[^a-z0-9 ]+/', ' ', $text) ?? '') . ' ';
        $t = preg_replace('/\s+/', ' ', $t) ?? $t;

        $phrases = [
            'ga jadi', 'gak jadi', 'nggak jadi', 'engga jadi', 'enggak jadi',
            'tidak jadi', 'tak jadi', 'tdk jadi', 'batal', 'batalkan', 'cancel',
            'urung', 'urungkan', 'stop', 'berhenti', 'jangan lanjut', 'jangan teruskan',
            'gak usah', 'ga usah', 'nggak usah', 'tidak usah', 'batalin',
            'saya batal', 'mau batal', 'kembali', 'keluar',
        ];

        foreach ($phrases as $p) {
            if (str_contains($t, $p)) {
                return true;
            }
        }

        return false;
    }

    /**
     * PARSER_SANITY_V1 — kata yang TIDAK MUNGKIN menjadi nama wilayah.
     * Tanpa daftar ini, potongan kalimat seperti "bisa ga" atau "ga jadi"
     * dianggap nama kota dan bot menanyakan rute dari kata sampah.
     */
    private const NON_REGION_WORDS = [
        // sapaan & partikel
        'kak', 'kakak', 'min', 'admin', 'halo', 'hai', 'hallo', 'permisi', 'mas', 'mbak',
        'bapak', 'ibu', 'pak', 'bu', 'assalamualaikum', 'salam',
        // kata kerja / permintaan
        'cek', 'cekongkir', 'ongkir', 'ongkos', 'tarif', 'harga', 'biaya', 'kirim',
        'kirimin', 'kirimkan', 'mau', 'ingin', 'pengen', 'butuh', 'perlu', 'minta',
        'tolong', 'bantu', 'tanya', 'nanya', 'pesan', 'beli', 'bayar', 'lacak',
        'tracking', 'resi', 'lihat', 'tampilkan', 'kasih', 'beri',
        // tanya & sambung
        'apa', 'apakah', 'berapa', 'bagaimana', 'gimana', 'kapan', 'dimana', 'mana',
        'bisa', 'bisakah', 'boleh', 'bolehkah', 'ga', 'gak', 'nggak', 'tidak', 'tak',
        'engga', 'enggak', 'tdk', 'ya', 'yah', 'dong', 'deh', 'sih', 'nih', 'kan',
        'lah', 'kok', 'nya', 'yang', 'untuk', 'atau', 'dan', 'dengan', 'itu', 'ini',
        'ada', 'adakah', 'sudah', 'belum', 'masih', 'lagi', 'juga', 'sekalian',
        // batal & negasi
        'jadi', 'batal', 'batalkan', 'cancel', 'urung', 'gagal', 'salah', 'keliru',
        'stop', 'berhenti', 'tunggu', 'nanti', 'dulu',
        // kata ganti
        'saya', 'aku', 'kami', 'kita', 'gue', 'gua', 'kamu', 'anda', 'dia',
        // terima kasih & penutup
        'makasih', 'terima', 'kasih', 'thanks', 'thank', 'thx', 'tq', 'mksh',
        'sip', 'oke', 'ok', 'baik', 'siap', 'mantap', 'bagus',
        'selamat', 'pagi', 'siang', 'sore', 'malam',
        // satuan (bukan wilayah)
        'kg', 'kilogram', 'gram', 'gr', 'g', 'ons', 'ton', 'kilo',
        // lain-lain
        'kak', 'kalo', 'kalau', 'biar', 'supaya', 'sama', 'sampai', 'tujuan',
        'asal', 'dari', 'ke', 'paket', 'barang', 'isi', 'alamat', 'nama',
    ];

    /** Apakah token plausibel sebagai nama wilayah? */
    private function _looksLikeRegion(string $value): bool
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return false;
        }

        $words = preg_split('/\s+/', $value) ?: [];
        $meaningful = 0;

        foreach ($words as $w) {
            $w = trim($w);
            if ($w === '') {
                continue;
            }
            // Angka murni bukan nama wilayah.
            if (ctype_digit($w)) {
                return false;
            }
            if (in_array($w, self::NON_REGION_WORDS, true)) {
                // Satu kata terlarang sudah cukup menandakan bukan wilayah.
                return false;
            }
            if (mb_strlen($w) >= 3) {
                $meaningful++;
            }
        }

        return $meaningful >= 1;
    }

    private const CITY_PROVINCE = [
        'surabaya'  => 'jawa timur',
        'malang'    => 'jawa timur',
        'kediri'    => 'jawa timur',
        'madiun'    => 'jawa timur',
        'mojokerto' => 'jawa timur',
        'probolinggo' => 'jawa timur',
        'pasuruan'  => 'jawa timur',
        'blitar'    => 'jawa timur',
        'banyuwangi' => 'jawa timur',
        'jember'    => 'jawa timur',
        'ngawi'     => 'jawa timur',
        'sidoarjo'  => 'jawa timur',
        'gresik'    => 'jawa timur',
        'lamongan'  => 'jawa timur',
        'tuban'     => 'jawa timur',
        'jakarta'   => 'dki jakarta',
        'bandung'   => 'jawa barat',
        'bogor'     => 'jawa barat',
        'bekasi'    => 'jawa barat',
        'depok'     => 'jawa barat',
        'tangerang' => 'banten',
        'serang'    => 'banten',
        'semarang'  => 'jawa tengah',
        'solo'      => 'jawa tengah',
        'surakarta' => 'jawa tengah',
        'magelang'  => 'jawa tengah',
        'pekalongan' => 'jawa tengah',
        'tegal'     => 'jawa tengah',
        'yogyakarta' => 'di yogyakarta',
        'jogja'     => 'di yogyakarta',
        'sleman'    => 'di yogyakarta',
        'denpasar'  => 'bali',
        'medan'     => 'sumatera utara',
        'palembang' => 'sumatera selatan',
        'padang'    => 'sumatera barat',
        'pekanbaru' => 'riau',
        'bandar lampung' => 'lampung',
        'lampung'   => 'lampung',
        'makassar'  => 'sulawesi selatan',
        'manado'    => 'sulawesi utara',
        'banjarmasin' => 'kalimantan selatan',
        'balikpapan' => 'kalimantan timur',
        'samarinda' => 'kalimantan timur',
        'pontianak' => 'kalimantan barat',
        'jayapura'  => 'papua',
    ];

    /**
     * Cari wilayah dengan strategi bertingkat: apa adanya -> +provinsi.
     * Mengembalikan array kandidat terbaik atau null.
     */
    private function _searchAddressSmart(string $addressText, array $tokens): ?array
    {
        $queries = [$addressText];

        // Tambahkan variasi "kota, provinsi" bila kata kuncinya kota besar.
        $lastToken = $tokens ? end($tokens) : '';
        $province = self::CITY_PROVINCE[$lastToken] ?? null;
        if ($province !== null && !str_contains(strtolower($addressText), $province)) {
            $queries[] = trim($lastToken . ', ' . $province);
            $queries[] = trim($addressText . ', ' . $province);
        }

        $bestOverall = null;
        $bestScore = 0;

        foreach ($queries as $query) {
            try {
                $results = $this->kirimaja->searchAddress($query);
            } catch (\Throwable $e) {
                continue;
            }

            if (empty($results['status']) || empty($results['data']) || !is_array($results['data'])) {
                continue;
            }

            foreach ($results['data'] as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                $districtId    = $candidate['district_id'] ?? null;
                $subdistrictId = $candidate['subdistrict_id'] ?? null;

                if (!is_numeric($districtId) || !is_numeric($subdistrictId)
                    || (int) $districtId <= 0 || (int) $subdistrictId <= 0) {
                    continue;
                }

                $score = $this->_scoreAddressCandidate($candidate, $tokens);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestOverall = [
                        'province_id'    => isset($candidate['province_id']) && is_numeric($candidate['province_id'])
                                                ? (int) $candidate['province_id'] : null,
                        'city_id'        => isset($candidate['city_id']) && is_numeric($candidate['city_id'])
                                                ? (int) $candidate['city_id'] : null,
                        'district_id'    => (int) $districtId,
                        'subdistrict_id' => (int) $subdistrictId,
                        'postal_code'    => isset($candidate['zipcode']) && $candidate['zipcode'] !== ''
                                                ? (string) $candidate['zipcode'] : null,
                        'full_text'      => $candidate['full_address'] ?? null,
                        'score'          => $score,
                        'query'          => $query,
                    ];
                }
            }

            // Skor tinggi pada query pertama -> tidak perlu lanjut.
            if ($bestScore >= 10) {
                break;
            }
        }

        return $bestOverall;
    }

    private function _resolveAddress(string $addressText): ?array
    {
        try {
            $tokens = $this->_addressTokens($addressText);
            if (empty($tokens)) {
                return null;
            }

            $best = $this->_searchAddressSmart($addressText, $tokens);
            $bestScore = $best['score'] ?? 0;

            // Tidak ada kandidat yang benar-benar cocok -> jangan menebak.
            if ($best === null || $bestScore < 3) {
                Log::info('[ADDRESS] Tidak ada wilayah yang cocok', [
                    'query' => $addressText,
                    'best_score' => $bestScore,
                ]);

                return null;
            }

            return $best;
        } catch (Exception $e) {
            Log::error("Geocoding API KiriminAja Gagal: " . $e->getMessage());

            return null;
        }
    }

    /** Pecah teks alamat menjadi kata kunci bermakna. */
    private function _addressTokens(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9 ]+/', ' ', $text) ?? '';
        $stop = ['jl', 'jalan', 'no', 'nomor', 'rt', 'rw', 'kec', 'kel', 'kota',
                 'kab', 'kabupaten', 'provinsi', 'desa', 'kelurahan', 'kecamatan',
                 'perum', 'perumahan', 'blok', 'gang', 'gg'];

        $tokens = [];
        foreach (preg_split('/\s+/', trim($text)) ?: [] as $word) {
            if ($word === '' || in_array($word, $stop, true) || mb_strlen($word) < 3) {
                continue;
            }
            $tokens[] = $word;
        }

        return array_values(array_unique($tokens));
    }

    /** Skor kecocokan satu kandidat terhadap kata kunci. */
    private function _scoreAddressCandidate(array $candidate, array $tokens): int
    {
        $full = strtolower((string) ($candidate['full_address'] ?? ''));

        // Nama kota/kabupaten sering tidak dikirim terpisah; ambil dari full_address.
        $parts = array_map('trim', explode(',', $full));
        $cityPart = '';
        $lastPart = '';
        if (count($parts) >= 3) {
            $cityPart = $parts[count($parts) - 3];
        }
        if (count($parts) >= 2) {
            $lastPart = $parts[0];
        }

        $score = 0;

        foreach ($tokens as $token) {
            $inFull = str_contains($full, $token);
            $inCity = $cityPart !== '' && str_contains($cityPart, $token);
            $inLast = $lastPart !== '' && str_contains($lastPart, $token);

            if ($inCity || $inLast) {
                $score += 5;
            } elseif ($inFull) {
                $score += 3;
            } else {
                // Kata ini tidak ada di kandidat sama sekali -> penalti.
                $score -= 2;
            }
        }

        // Kata terakhir biasanya kota tujuan: beri bobot ekstra.
        // ADDRESS_SCORE_V2: kata terakhir adalah kunci kota tujuan.
        $lastToken = end($tokens);
        if ($lastToken !== false) {
            if ($cityPart !== '' && str_contains($cityPart, $lastToken)) {
                $score += 10;   // kota benar-benar cocok
            } elseif (!str_contains($full, $lastToken)) {
                $score -= 8;    // kata kunci tidak ada sama sekali
            } else {
                $score -= 6;    // hanya nyempil di nama kelurahan/desa
            }
        }

        return $score;
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
     * PRICE_GUARD_V1: ambil harga real-time untuk kurir+layanan terpilih.
     * Return null bila tidak ditemukan (jangan memblokir pembuatan pesanan).
     */
    private function _resolveRealtimeCost(array $sender, array $receiver, int $weight, int $itemValue, string $courierCode, string $serviceName): ?int
    {
        try {
            $options = $this->_getShippingOptions(
                $sender['district_id'],
                $sender['subdistrict_id'],
                $receiver['district_id'],
                $receiver['subdistrict_id'],
                max($weight, 1),
                max($itemValue, 1000)
            );
        } catch (\Throwable $e) {
            Log::warning('[PRICE-GUARD] Gagal ambil harga real-time: ' . $e->getMessage());

            return null;
        }

        $wantCourier = strtoupper(trim($courierCode));
        $wantService = trim($serviceName);

        foreach ($options as $opt) {
            if (strtoupper((string) $opt['kurir']) !== $wantCourier) {
                continue;
            }
            $name = (string) $opt['layanan'];
            if ($wantService !== '' && $name !== $wantService) {
                continue;
            }

            return (int) $opt['ongkir'];
        }

        return null;
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

    /**
     * Varian nomor WA untuk pencarian user (tabel Pengguna memakai kolom no_wa).
     * Menampung 08..., 628..., dan +62... agar data lama tetap terdeteksi.
     */
    private function _waPhoneVariants(string $phone): array
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') {
            return [$phone];
        }

        $variants = [$digits];

        if (str_starts_with($digits, '62')) {
            $local = '0' . substr($digits, 2);
            $variants[] = $local;
            $variants[] = '+' . $digits;
        } elseif (str_starts_with($digits, '0')) {
            $variants[] = '62' . substr($digits, 1);
            $variants[] = '+62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $variants[] = '0' . $digits;
            $variants[] = '62' . $digits;
            $variants[] = '+62' . $digits;
        }

        return array_values(array_unique($variants));
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
