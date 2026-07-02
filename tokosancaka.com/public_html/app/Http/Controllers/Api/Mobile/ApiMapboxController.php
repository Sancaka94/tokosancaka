<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Api;

class ApiMapboxController extends Controller
{
    /**
     * Endpoint API POST: /api/mobile/mapbox/cek-tarif
     */
    public function cek_tarif(Request $request)
    {
        // [DEBUG] Catat semua request yang masuk ke Laravel
        Log::info("=== [API MAPBOX] REQUEST CEK TARIF MASUK ===");
        Log::info("Payload:", $request->all());

        $latAsal = $request->input('sender_lat');
        $lngAsal = $request->input('sender_lng');
        $latTujuan = $request->input('receiver_lat');
        $lngTujuan = $request->input('receiver_lng');
        $layanan = $request->input('layanan');
        $beratGram = (float) $request->input('weight', 1000);

        if (!$latAsal || !$lngAsal || !$latTujuan || !$lngTujuan) {
            Log::warning("[API MAPBOX] Koordinat tidak lengkap.");
            return response()->json(['status' => false, 'message' => 'Koordinat tidak lengkap.']);
        }

        $mapboxToken = Api::getValue('MAPBOX_SECRET_TOKEN', 'global', env('MAPBOX_TOKEN'));

        if (empty($mapboxToken)) {
            Log::error("[API MAPBOX] Mapbox Token kosong di database!");
        }

        $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$lngAsal},{$latAsal};{$lngTujuan},{$latTujuan}";
        Log::info("[API MAPBOX] Menembak URL: " . $url);

        try {
            $response = Http::get($url, [
                'access_token' => $mapboxToken,
                'geometries'   => 'geojson',
                'overview'     => 'simplified'
            ]);

            if (!$response->successful() || empty($response['routes'][0])) {
                Log::error("[API MAPBOX] Mapbox API Gagal Merespons: ", $response->json() ?? []);
                return response()->json(['status' => false, 'message' => 'Gagal mendapatkan rute dari Mapbox']);
            }

            $route = $response['routes'][0];
            $distanceKm = $route['distance'] / 1000;
            $durationMin = ceil($route['duration'] / 60);

            Log::info("[API MAPBOX] Jarak: {$distanceKm} KM | Waktu: {$durationMin} Menit");

            if ($layanan == 'ojek_online') {
                $baseFare = (float) Api::getValue('SANCAKA_OJEK_BASE_FARE', 'global', 5000);
                $pricePerKm = (float) Api::getValue('SANCAKA_OJEK_PER_KM', 'global', 2500);
                $totalCost = $baseFare + ($distanceKm * $pricePerKm);
            } else {
                $baseFare = (float) Api::getValue('SANCAKA_EXPRESS_BASE_FARE', 'global', 3000);
                $pricePerKm = (float) Api::getValue('SANCAKA_EXPRESS_PER_KM', 'global', 1000);
                $pricePerKg = (float) Api::getValue('SANCAKA_EXPRESS_PER_KG', 'global', 1000);

                $weightKg = max(1, ceil($beratGram / 1000));
                $totalCost = $baseFare + ($distanceKm * $pricePerKm) + ($weightKg * $pricePerKg);
            }

            $finalCost = (int) (ceil($totalCost / 500) * 500);

            Log::info("[API MAPBOX] Tarif Final Dihitung: Rp " . $finalCost);

            return response()->json([
                'status' => true,
                'data' => [
                    'jarak_km' => round($distanceKm, 2),
                    'waktu_menit' => $durationMin,
                    'tarif_final' => $finalCost
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("[API MAPBOX] EXCEPTION CRASH: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return response()->json([
                'status' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint API POST: /api/mobile/driver/register
     * MENANGANI PENDAFTARAN DRIVER + UPLOAD FILE
     */
    public function register_driver(Request $request)
    {
        Log::info("=== [API DRIVER] REQUEST PENDAFTARAN MASUK ===");

        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'nama_lengkap'    => 'required|string|max:255',
            'nomor_nik'       => 'required|string|max:20',
            'nomor_kk'        => 'required|string|max:20',
            'nomor_wa'        => 'required|string|max:20',
            'alamat_lengkap'  => 'required|string',
            'latitude'        => 'required|numeric',
            'longitude'       => 'required|numeric',
            'file_ktp'        => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_kk'         => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_buku_nikah' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_stnk'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_bpkb'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_motor'      => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_wajah'      => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            Log::warning("[API DRIVER] Validasi gagal: ", $validator->errors()->toArray());
            return response()->json([
                'status'  => false,
                'message' => 'Data tidak lengkap atau format file salah.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // =====================================================================
            // FITUR CERDAS: DETEKSI AKUN PENGGUNA YANG SUDAH ADA
            // =====================================================================
            $nomorWa = $request->input('nomor_wa');
            $idPengguna = null;
            $namaLengkap = $request->input('nama_lengkap');

            // Cek apakah user sedang login dengan Sanctum/Passport token
            $userLoggedIn = $request->user();

            if ($userLoggedIn) {
                // Jika login, gunakan data dari token
                $idPengguna = $userLoggedIn->id_pengguna;
                $namaLengkap = $userLoggedIn->nama_lengkap ?? $namaLengkap;
            } else {
                // Jika mendaftar anonim, cari di tabel Pengguna berdasarkan Nomor WA
                $existingUser = DB::table('Pengguna')->where('no_wa', $nomorWa)->first();
                if ($existingUser) {
                    $idPengguna = $existingUser->id_pengguna;
                    // Ambil nama asli dari akun untuk validitas (menimpa input form)
                    $namaLengkap = $existingUser->nama_lengkap ?? $namaLengkap;
                }
            }
            // =====================================================================

            // 2. Proses Upload File
            $uploadPath = 'drivers';
            $filePaths = [
                'file_ktp' => null, 'file_kk' => null, 'file_buku_nikah' => null,
                'file_stnk' => null, 'file_bpkb' => null, 'foto_motor' => null, 'foto_wajah' => null,
            ];

            foreach (array_keys($filePaths) as $fileKey) {
                if ($request->hasFile($fileKey)) {
                    $file = $request->file($fileKey);
                    $filename = time() . '_' . $fileKey . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($uploadPath, $filename, 'public');
                    $filePaths[$fileKey] = $path;
                }
            }

            // 3. Simpan Ke Database Driver
            $insertId = DB::table('registrasi_driver_sancaka')->insertGetId([
                'id_pengguna'     => $idPengguna, // <-- Data Tersinkronisasi Otomatis
                'nama_lengkap'    => $namaLengkap, // <-- Diambil dari akun jika ada
                'nomor_nik'       => $request->input('nomor_nik'),
                'nomor_kk'        => $request->input('nomor_kk'),
                'nomor_wa'        => $nomorWa,
                'alamat_lengkap'  => $request->input('alamat_lengkap'),
                'latitude'        => $request->input('latitude'),
                'longitude'       => $request->input('longitude'),
                'file_ktp'        => $filePaths['file_ktp'],
                'file_kk'         => $filePaths['file_kk'],
                'file_buku_nikah' => $filePaths['file_buku_nikah'],
                'file_stnk'       => $filePaths['file_stnk'],
                'file_bpkb'       => $filePaths['file_bpkb'],
                'foto_motor'      => $filePaths['foto_motor'],
                'foto_wajah'      => $filePaths['foto_wajah'],
                'status'          => 'pending',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            Log::info("[API DRIVER] Pendaftaran Sukses! ID: {$insertId} | Linked Pengguna ID: " . ($idPengguna ?? 'NULL'));

            return response()->json([
                'status'  => true,
                'message' => 'Pendaftaran berhasil dikirim. Tim kami akan memvalidasi data Anda.',
                'data'    => [
                    'id'          => $insertId,
                    'id_pengguna' => $idPengguna
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("[API DRIVER] CRASH SERVER: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return response()->json([
                'status'  => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan data.'
            ], 500);
        }
    }

   public function getNearbyDrivers(Request $request)
    {
        try {
            $lat = $request->query('lat');
            $lng = $request->query('lng');
            $radius = 5;

            if (!$lat || !$lng) {
                return response()->json([
                    'success' => false,
                    'message' => 'Titik kordinat asal tidak ditemukan.'
                ]);
            }

            // 🔥 PERBAIKAN QUERY: Tambahkan is_active_map di Select dan Where 🔥
            $drivers = DB::table('registrasi_driver_sancaka')
                ->selectRaw("id, id_pengguna, nama_lengkap, latitude, longitude, status, is_active_map,
                    ( 6371 * acos( cos( radians(?) ) *
                      cos( radians( latitude ) ) *
                      cos( radians( longitude ) - radians(?) ) +
                      sin( radians(?) ) *
                      sin( radians( latitude ) ) )
                    ) AS distance", [$lat, $lng, $lat])
                ->where('status', 'approved')
                ->where('is_active_map', 1) // KUNCI: HANYA TARIK DRIVER YANG ONLINE
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance', '<=', $radius)
                ->orderBy('distance', 'asc')
                ->limit(10)
                ->get();

            $formattedDrivers = $drivers->map(function ($driver) {
                return [
                    'id'       => $driver->id,
                    'name'     => $driver->nama_lengkap,
                    'vehicle'  => 'Ojek Sancaka',
                    'distance' => round($driver->distance, 1) . ' KM',
                    'lat'      => (float) $driver->latitude,
                    'lng'      => (float) $driver->longitude,
                    'is_online'=> $driver->is_active_map == 1 // Kirim status ke frontend jika dibutuhkan
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $formattedDrivers
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("[API DRIVER NEARBY] Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mencari driver.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 1. CEK STATUS DRIVER BERDASARKAN ID PENGGUNA AUTH
     * GET /api/mobile/driver/my-status
     */
    public function myStatus(Request $request)
    {
        try {
            // Ambil id_pengguna langsung dari token login expo
            $idPengguna = $request->user()->id_pengguna;

            // Cari di database tabel driver
            $driver = DB::table('registrasi_driver_sancaka')
                        ->where('id_pengguna', $idPengguna)
                        ->first();

            if ($driver) {
                return response()->json([
                    'success' => true,
                    'data' => $driver
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Belum terdaftar sebagai driver.'
            ], 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 2. UPDATE DATA DRIVER (JIKA SUDAH TERDAFTAR)
     * POST /api/mobile/driver/update
     */
    public function updateDriver(Request $request)
    {
        $idPengguna = $request->user()->id_pengguna;

        // Ambil data driver lama untuk pengecekan file
        $oldDriver = DB::table('registrasi_driver_sancaka')->where('id_pengguna', $idPengguna)->first();
        if (!$oldDriver) {
            return response()->json(['success' => false, 'message' => 'Data driver tidak ditemukan.'], 404);
        }

        // Validasi form (berkas dibuat nullable karena sifatnya update/opsional)
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'nomor_nik'    => 'required|string|max:20',
            'nomor_kk'     => 'required|string|max:20',
            'nomor_wa'     => 'required|string|max:20',
            'alamat_lengkap' => 'required|string',
            'latitude'     => 'required|numeric',
            'longitude'    => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $uploadPath = 'drivers';
            $filePaths = [];
            $fields = ['file_ktp', 'file_kk', 'file_buku_nikah', 'file_stnk', 'file_bpkb', 'foto_motor', 'foto_wajah'];

            foreach ($fields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = time() . '_' . $field . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $filePaths[$field] = $file->storeAs($uploadPath, $filename, 'public');
                } else {
                    // Jika tidak upload file baru, tetap gunakan file yang lama di database
                    $filePaths[$field] = $oldDriver->$field;
                }
            }

            // Jalankan Query UPDATE berdasarkan id_pengguna token
            DB::table('registrasi_driver_sancaka')
                ->where('id_pengguna', $idPengguna)
                ->update([
                    'nama_lengkap'   => $request->input('nama_lengkap'),
                    'nomor_nik'      => $request->input('nomor_nik'),
                    'nomor_kk'       => $request->input('nomor_kk'),
                    'nomor_wa'       => $request->input('nomor_wa'),
                    'alamat_lengkap' => $request->input('alamat_lengkap'),
                    'latitude'       => $request->input('latitude'),
                    'longitude'      => $request->input('longitude'),
                    'file_ktp'       => $filePaths['file_ktp'],
                    'file_kk'        => $filePaths['file_kk'],
                    'file_buku_nikah'=> $filePaths['file_buku_nikah'],
                    'file_stnk'      => $filePaths['file_stnk'],
                    'file_bpkb'      => $filePaths['file_bpkb'],
                    'foto_motor'     => $filePaths['foto_motor'],
                    'foto_wajah'     => $filePaths['foto_wajah'],
                    'updated_at'     => now(),
                ]);

            return response()->json(['success' => true, 'message' => 'Data driver Anda berhasil diperbarui.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 3. SAKLAR TOGGLE ONLINE/OFFLINE DI MAP
     * POST /api/mobile/driver/toggle-map
     */
    public function toggleMap(Request $request)
    {
        try {
            $idPengguna = $request->user()->id_pengguna;
            $isActive = $request->input('is_active_map'); // 1 atau 0

            DB::table('registrasi_driver_sancaka')
                ->where('id_pengguna', $idPengguna)
                ->update([
                    'is_active_map' => $isActive,
                    'updated_at'    => now()
                ]);

            return response()->json(['success' => true, 'message' => 'Status aktif berhasil diubah.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 4. UPDATE KOORDINAT GPS REAL-TIME (DINAMIS TIAP 4 DETIK)
     * POST /api/mobile/driver/update-location
     */
    public function updateLocation(Request $request)
    {
        try {
            $idPengguna = $request->user()->id_pengguna;

            DB::table('registrasi_driver_sancaka')
                ->where('id_pengguna', $idPengguna)
                ->update([
                    'latitude'   => $request->input('latitude'),
                    'longitude'  => $request->input('longitude'),
                    'updated_at' => now()
                ]);

            return response()->json(['success' => true, 'message' => 'Koordinat GPS sinkron.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

   /**
     * Hitung Jarak Haversine antar dua titik koordinat (dalam Meter)
     */
    private function getDistanceMeter($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Radius bumi dalam meter
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c);
    }

   /**
     * Endpoint POST: /api/mobile/order/notify-driver
     * Mengirim notifikasi detail pesanan ke HP Driver
     */
    public function notify_driver(Request $request)
    {
        $driverId = $request->input('driver_id');
        $customerLat = $request->input('origin_lat');
        $customerLng = $request->input('origin_lng');

        // 1. Dapatkan Data User Pemesan (Customer) dari Tabel Pengguna
        $customer = $request->user(); // Karena API ini dilindungi Auth:Sanctum

        // 2. Dapatkan Data Driver yang dituju
        $driver = DB::table('registrasi_driver_sancaka')
            ->join('Pengguna', 'registrasi_driver_sancaka.id_pengguna', '=', 'Pengguna.id_pengguna')
            ->where('registrasi_driver_sancaka.id', $driverId)
            ->select('Pengguna.expo_token', 'registrasi_driver_sancaka.nama_lengkap', 'registrasi_driver_sancaka.latitude', 'registrasi_driver_sancaka.longitude')
            ->first();

        if (!$driver || empty($driver->expo_token)) {
            return response()->json(['status' => false, 'message' => 'Driver Offline / Token tidak ditemukan.'], 404);
        }

        // 3. Hitung Jarak
        $jarakKePemesanMeter = $this->getDistanceMeter(
            (float)$driver->latitude, (float)$driver->longitude,
            (float)$customerLat, (float)$customerLng
        );

       try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', [
                'to'        => $driver->expo_token,
                'title'     => '🚨 ORDERAN BARU! Rp ' . number_format($request->input('tarif'), 0, ',', '.'),
                'body'      => 'Dari: ' . $customer->nama_lengkap . ' (' . $jarakKePemesanMeter . 'm)',
                'sound'     => 'default',
                'channelId' => 'pesanan-masuk',
                'priority'  => 'high',
                'data'      => [
                    'action'             => 'new_order',
                    'order_id'           => $request->input('order_id', uniqid()),
                    'customer_id'        => $customer->id_pengguna,
                    'customer_name'      => $customer->nama_lengkap,
                    'customer_phone'     => $customer->no_wa,
                    'tarif'              => $request->input('tarif'),
                    'origin_address'     => $request->input('origin_address'),
                    'dest_address'       => $request->input('dest_address'),
                    'origin_lat'         => $request->input('origin_lat'),
                    'origin_lng'         => $request->input('origin_lng'),
                    'dest_lat'           => $request->input('dest_lat'),
                    'dest_lng'           => $request->input('dest_lng'),
                    'jarak_ke_pemesan'   => $jarakKePemesanMeter,
                    'waktu_tempuh_menit' => $request->input('waktu_menit', 10),
                ]
            ]);

            $expoResult = $response->json();
            Log::info("[API MAPBOX] Balasan dari Expo: ", $expoResult ?? []);

            // Expo mengembalikan struktur spesifik jika ada error pada data token
            if (!$response->successful() || (isset($expoResult['data'][0]['status']) && $expoResult['data'][0]['status'] === 'error')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal mengirim notifikasi ke perangkat driver. Token mungkin tidak valid.',
                    'error_detail' => $expoResult
                ], 500);
            }

            return response()->json(['status' => true, 'message' => 'Memanggil driver ' . $driver->nama_lengkap]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

   /**
     * Endpoint POST: /api/mobile/order/driver-accept
     * Dipicu ketika Driver klik "Terima Pesanan". Mengirim notif balik ke Customer.
     */
    public function accept_order(Request $request)
    {
        try {
            $customerId = $request->input('customer_id');
            $driverUser = $request->user(); // Data user driver yang sedang login

            // Ambil info detail kendaraan driver
            $driverDetail = DB::table('registrasi_driver_sancaka')
                ->where('id_pengguna', $driverUser->id_pengguna)
                ->first();

            if (!$driverDetail) {
                return response()->json(['success' => false, 'message' => 'Data driver tidak ditemukan.'], 404);
            }

            // Cari token HP milik customer
            $customerToken = DB::table('Pengguna')->where('id_pengguna', $customerId)->value('expo_token');

            if ($customerToken) {
                // Kirim notifikasi balik ke Customer
                Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://exp.host/--/api/v2/push/send', [
                    'to'    => $customerToken,
                    'title' => '✅ Driver Ditemukan!',
                    'body'  => $driverUser->nama_lengkap . ' siap menjemput Anda dengan Ojek Sancaka',
                    'sound' => 'default',
                    'data'  => [
                        'action'      => 'order_accepted',
                        'driver_id'   => $driverUser->id_pengguna,
                        'driver_name' => $driverUser->nama_lengkap,
                        'driver_lat'  => $driverDetail->latitude,
                        'driver_lng'  => $driverDetail->longitude,
                        'phone'       => $driverDetail->nomor_wa,
                    ]
                ]);
            }

            // Wajib kembalikan JSON sukses agar Frontend mau pindah halaman
            return response()->json([
                'success' => true,
                'message' => 'Pesanan diterima.'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("[API DRIVER ACCEPT] Crash: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menerima pesanan.'
            ], 500);
        }
    }

    /**
  * Endpoint GET: /api/mobile/order/track-driver/{driver_id}
  * Digunakan oleh pelanggan untuk menarik koordinat realtime driver
  */
 public function track_driver($driver_id)
 {
     $driver = DB::table('registrasi_driver_sancaka')->where('id_pengguna', $driver_id)->first();

     if (!$driver) {
         return response()->json(['success' => false, 'message' => 'Driver tidak ditemukan']);
     }

     return response()->json([
         'success' => true,
         'latitude' => (float) $driver->latitude,
         'longitude' => (float) $driver->longitude,
         'is_online' => $driver->is_active_map
     ]);
 }

}
