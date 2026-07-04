<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $minTahun = date('Y') - 8;

        // 1. Validasi Input (Dilengkapi Sesuai Database & Web Controller)
        $validator = Validator::make($request->all(), [
            'nama_lengkap'    => 'required|string|max:255',
            'tempat_lahir'    => 'required|string|max:100',
            'tanggal_lahir'   => 'required|date|before:-18 years',
            'jenis_kelamin'   => 'required|in:Laki-laki,Perempuan',
            'nomor_nik'       => 'required|string|max:20',
            'nomor_kk'        => 'required|string|max:20',
            'nomor_wa'        => 'required|string|max:20',
            'instansi_perusahaan' => 'nullable|string|max:255',
            'alamat_lengkap'  => 'required|string',
            'jenis_layanan'   => 'required|in:motor,mobil',
            'merk_kendaraan'  => 'required|string|max:100',
            'tahun_kendaraan' => 'required|integer|min:' . $minTahun . '|max:' . date('Y'),
            'plat_nomor'      => 'required|string|max:15',
            'latitude'        => 'required|numeric',
            'longitude'       => 'required|numeric',

            // File Pendukung (Wajib di awal pendaftaran)
            'file_ktp'           => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_sim'           => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_skck'          => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_stnk'          => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_motor'         => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_buku_rekening' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_wajah'         => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',

            // Opsional
            'file_kk'         => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_buku_nikah' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_bpkb'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
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

            $userLoggedIn = $request->user();

            if ($userLoggedIn) {
                $idPengguna = $userLoggedIn->id_pengguna;
                $namaLengkap = $userLoggedIn->nama_lengkap ?? $namaLengkap;
            } else {
                $existingUser = DB::table('Pengguna')->where('no_wa', $nomorWa)->first();
                if ($existingUser) {
                    $idPengguna = $existingUser->id_pengguna;
                    $namaLengkap = $existingUser->nama_lengkap ?? $namaLengkap;
                }
            }
            // =====================================================================

            // 2. Proses Upload File (Dilengkapi)
            $uploadPath = 'drivers';
            $filePaths = [
                'file_ktp' => null, 'file_sim' => null, 'file_skck' => null,
                'file_kk' => null, 'file_buku_nikah' => null, 'file_stnk' => null,
                'file_bpkb' => null, 'foto_motor' => null, 'file_buku_rekening' => null, 'foto_wajah' => null,
            ];

            foreach (array_keys($filePaths) as $fileKey) {
                if ($request->hasFile($fileKey)) {
                    $file = $request->file($fileKey);

                    // PROSES KEAMANAN FILE (Gunakan engine keamanan)
                    $pathAman = $this->amankanDanSimpanFile($file, $uploadPath);

                    if (!$pathAman) {
                        return response()->json([
                            'status'  => false,
                            'message' => "Pendaftaran Gagal: Berkas terindikasi berbahaya pada kolom: {$fileKey}."
                        ], 422);
                    }

                    $filePaths[$fileKey] = $pathAman;
                }
            }

            // 3. Simpan Ke Database Driver (Dilengkapi)
            $insertId = DB::table('registrasi_driver_sancaka')->insertGetId([
                'id_pengguna'     => $idPengguna,
                'nama_lengkap'    => $namaLengkap,
                'tempat_lahir'    => $request->input('tempat_lahir'),
                'tanggal_lahir'   => $request->input('tanggal_lahir'),
                'jenis_kelamin'   => $request->input('jenis_kelamin'),
                'nomor_nik'       => $request->input('nomor_nik'),
                'nomor_kk'        => $request->input('nomor_kk'),
                'nomor_wa'        => $nomorWa,
                'instansi_perusahaan' => $request->input('instansi_perusahaan'),
                'alamat_lengkap'  => $request->input('alamat_lengkap'),
                'jenis_layanan'   => $request->input('jenis_layanan'),
                'merk_kendaraan'  => $request->input('merk_kendaraan'),
                'tahun_kendaraan' => $request->input('tahun_kendaraan'),
                'plat_nomor'      => $request->input('plat_nomor'),
                'latitude'        => $request->input('latitude'),
                'longitude'       => $request->input('longitude'),

                'file_ktp'           => $filePaths['file_ktp'],
                'file_sim'           => $filePaths['file_sim'],
                'file_skck'          => $filePaths['file_skck'],
                'file_kk'            => $filePaths['file_kk'],
                'file_buku_nikah'    => $filePaths['file_buku_nikah'],
                'file_stnk'          => $filePaths['file_stnk'],
                'file_bpkb'          => $filePaths['file_bpkb'],
                'foto_motor'         => $filePaths['foto_motor'],
                'file_buku_rekening' => $filePaths['file_buku_rekening'],
                'foto_wajah'         => $filePaths['foto_wajah'],

                'status'          => 'pending',
                'is_active_map'   => 0,
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
                    'is_online'=> $driver->is_active_map == 1
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
            $idPengguna = $request->user()->id_pengguna;

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
        $minTahun = date('Y') - 8;

        $oldDriver = DB::table('registrasi_driver_sancaka')->where('id_pengguna', $idPengguna)->first();
        if (!$oldDriver) {
            return response()->json(['success' => false, 'message' => 'Data driver tidak ditemukan.'], 404);
        }

        // Validasi form (Dilengkapi)
        $validator = Validator::make($request->all(), [
            'nama_lengkap'    => 'required|string|max:255',
            'tempat_lahir'    => 'required|string|max:100',
            'tanggal_lahir'   => 'required|date|before:-18 years',
            'jenis_kelamin'   => 'required|in:Laki-laki,Perempuan',
            'nomor_nik'       => 'required|string|max:20',
            'nomor_kk'        => 'required|string|max:20',
            'nomor_wa'        => 'required|string|max:20',
            'instansi_perusahaan' => 'nullable|string|max:255',
            'alamat_lengkap'  => 'required|string',
            'jenis_layanan'   => 'required|in:motor,mobil',
            'merk_kendaraan'  => 'required|string|max:100',
            'tahun_kendaraan' => 'required|integer|min:' . $minTahun . '|max:' . date('Y'),
            'plat_nomor'      => 'required|string|max:15',
            'latitude'        => 'required|numeric',
            'longitude'       => 'required|numeric',

            // Semua file nullable saat update
            'file_ktp'           => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_sim'           => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_skck'          => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_stnk'          => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_motor'         => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_buku_rekening' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_wajah'         => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_kk'            => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_buku_nikah'    => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_bpkb'          => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $uploadPath = 'drivers';
            $filePaths = [];
            // Daftar field dokumen lengkap
            $fields = [
                'file_ktp', 'file_sim', 'file_skck', 'file_kk', 'file_buku_nikah',
                'file_stnk', 'file_bpkb', 'foto_motor', 'file_buku_rekening', 'foto_wajah'
            ];

           foreach ($fields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);

                    // PROSES KEAMANAN FILE
                    $pathAman = $this->amankanDanSimpanFile($file, $uploadPath);

                    if (!$pathAman) {
                        return response()->json([
                            'status'  => false,
                            'message' => "Gagal memperbarui: File {$field} terindikasi berbahaya!"
                        ], 422);
                    }

                    // Hapus file lama jika ada upload baru yang aman
                    if (!empty($oldDriver->$field) && Storage::disk('public')->exists($oldDriver->$field)) {
                        Storage::disk('public')->delete($oldDriver->$field);
                    }

                    $filePaths[$field] = $pathAman;
                } else {
                    // Gunakan file lama jika tidak ada upload baru
                    $filePaths[$field] = $oldDriver->$field;
                }
            }

            // Jalankan Query UPDATE (Dilengkapi)
            DB::table('registrasi_driver_sancaka')
                ->where('id_pengguna', $idPengguna)
                ->update([
                    'nama_lengkap'    => $request->input('nama_lengkap'),
                    'tempat_lahir'    => $request->input('tempat_lahir'),
                    'tanggal_lahir'   => $request->input('tanggal_lahir'),
                    'jenis_kelamin'   => $request->input('jenis_kelamin'),
                    'nomor_nik'       => $request->input('nomor_nik'),
                    'nomor_kk'        => $request->input('nomor_kk'),
                    'nomor_wa'        => $request->input('nomor_wa'),
                    'instansi_perusahaan' => $request->input('instansi_perusahaan'),
                    'alamat_lengkap'  => $request->input('alamat_lengkap'),
                    'jenis_layanan'   => $request->input('jenis_layanan'),
                    'merk_kendaraan'  => $request->input('merk_kendaraan'),
                    'tahun_kendaraan' => $request->input('tahun_kendaraan'),
                    'plat_nomor'      => $request->input('plat_nomor'),
                    'latitude'        => $request->input('latitude'),
                    'longitude'       => $request->input('longitude'),

                    'file_ktp'           => $filePaths['file_ktp'],
                    'file_sim'           => $filePaths['file_sim'],
                    'file_skck'          => $filePaths['file_skck'],
                    'file_kk'            => $filePaths['file_kk'],
                    'file_buku_nikah'    => $filePaths['file_buku_nikah'],
                    'file_stnk'          => $filePaths['file_stnk'],
                    'file_bpkb'          => $filePaths['file_bpkb'],
                    'foto_motor'         => $filePaths['foto_motor'],
                    'file_buku_rekening' => $filePaths['file_buku_rekening'],
                    'foto_wajah'         => $filePaths['foto_wajah'],

                    'updated_at'      => now(),
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

  public function notify_driver(Request $request)
    {
        Log::info("=== [API MAPBOX] REQUEST NOTIFY DRIVER (ORDER BARU) MASUK ===");
        Log::info("LOG LOG: Payload dari HP Pelanggan: ", $request->all());

        $driverId = $request->input('driver_id');
        $customerLat = $request->input('origin_lat');
        $customerLng = $request->input('origin_lng');

        $customer = $request->user();

        $driver = DB::table('registrasi_driver_sancaka')
            ->join('Pengguna', 'registrasi_driver_sancaka.id_pengguna', '=', 'Pengguna.id_pengguna')
            ->where('registrasi_driver_sancaka.id', $driverId)
            ->select('Pengguna.expo_token', 'registrasi_driver_sancaka.nama_lengkap', 'registrasi_driver_sancaka.latitude', 'registrasi_driver_sancaka.longitude', 'registrasi_driver_sancaka.id_pengguna as driver_user_id')
            ->first();

        if (!$driver || empty($driver->expo_token)) {
            Log::warning("LOG LOG: Driver Offline atau Expo Token Kosong", ['driver_id' => $driverId]);
            return response()->json(['status' => false, 'message' => 'Driver Offline / Token tidak ditemukan.'], 404);
        }

        $jarakKePemesanMeter = $this->getDistanceMeter(
            (float)$driver->latitude, (float)$driver->longitude,
            (float)$customerLat, (float)$customerLng
        );

        // 1. GENERATE ORDER ID
        $orderId = 'S-RIDE-' . strtoupper(uniqid());
        Log::info("LOG LOG: Order ID di-generate: " . $orderId);

        try {
            Log::info("LOG LOG: Mencoba Insert ke tabel order_ojek_online...");

            // 🔥 PERBAIKAN 1: SIMPAN CATATAN KE DATABASE 🔥
            DB::table('order_ojek_online')->insert([
                'order_id'          => $orderId,
                'customer_id'       => $customer->id_pengguna,
                'driver_id'         => $driver->driver_user_id,
                'origin_lat'        => $customerLat,
                'origin_lng'        => $customerLng,
                'origin_address'    => $request->input('origin_address', 'Lokasi Jemput'),
                'dest_lat'          => $request->input('dest_lat'),
                'dest_lng'          => $request->input('dest_lng'),
                'dest_address'      => $request->input('dest_address', 'Tujuan Antar'),
                'jarak_km'          => (float) $request->input('jarak_km', 0),
                'waktu_menit'       => (int) $request->input('waktu_menit', 0),
                'tarif'             => (float) $request->input('tarif', 0),
                'metode_pembayaran' => $request->input('metode_pembayaran', 'CASH'),
                'catatan'           => $request->input('catatan', null), // <-- TAMBAHAN SIMPAN CATATAN
                'status'            => 'pending',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            Log::info("LOG LOG: Sukses Insert ke Database!");

            // 2. KIRIM NOTIFIKASI BESERTA DATA UNTUK MODAL
            Log::info("LOG LOG: Mengirim Push Notification ke HP Driver...");

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', [
                'to'        => $driver->expo_token,
                'title'     => '🚨 ORDERAN BARU! Rp ' . number_format($request->input('tarif'), 0, ',', '.'),
                'body'      => 'Jarak Jemput: ' . $jarakKePemesanMeter . 'm | Tujuan: ' . $request->input('dest_address'),
                'sound'     => 'default',
                'channelId' => 'pesanan-masuk',
                'priority'  => 'high',
                'data'      => [
                    'action'           => 'new_order',
                    'order_id'         => $orderId,
                    'customer_id'      => $customer->id_pengguna,
                    'tarif'            => $request->input('tarif'),
                    'jarak_ke_pemesan' => $jarakKePemesanMeter,
                    'origin_address'   => $request->input('origin_address'),
                    'dest_address'     => $request->input('dest_address'),
                    'catatan'          => $request->input('catatan', '') // 🔥 PERBAIKAN 2: KIRIM CATATAN KE HP DRIVER 🔥
                ]
            ]);

            Log::info("LOG LOG: Balasan dari Expo: ", $response->json() ?? []);

            return response()->json(['status' => true, 'message' => 'Memanggil driver...', 'order_id' => $orderId]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: CRASH Insert DB / Notif! Pesan: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());

            return response()->json(['status' => false, 'message' => 'Gagal membuat pesanan di server.'], 500);
        }
    }

  public function accept_order(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $driverUser = $request->user();

            // 1. UPDATE STATUS ORDER DI DATABASE
            DB::table('order_ojek_online')
                ->where('order_id', $orderId)
                ->where('driver_id', $driverUser->id_pengguna)
                ->update(['status' => 'accepted', 'updated_at' => now()]);

            // 2. AMBIL DATA ORDER UNTUK CARI CUSTOMER
            $order = DB::table('order_ojek_online')->where('order_id', $orderId)->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order tidak valid.'], 404);
            }

            // Cari token HP milik customer
            $customerToken = DB::table('Pengguna')->where('id_pengguna', $order->customer_id)->value('expo_token');

            if ($customerToken) {
                Http::post('https://exp.host/--/api/v2/push/send', [
                    'to'    => $customerToken,
                    'title' => '✅ Driver Ditemukan!',
                    'body'  => $driverUser->nama_lengkap . ' siap menjemput Anda!',
                    'sound' => 'default',
                    'data'  => [
                        'action'   => 'order_accepted',
                        'order_id' => $orderId
                    ]
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Pesanan diterima.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Sistem Error.'], 500);
        }
    }

   /**
     * Endpoint GET: /api/mobile/order/detail/{order_id}
     * Menarik semua data order, customer, dan driver dari Database.
     */
    public function get_order_detail($order_id)
    {
        Log::info("=== [API MAPBOX] REQUEST GET ORDER DETAIL MASUK ===");
        Log::info("LOG LOG: Mencari data untuk Order ID: " . $order_id);

        try {
            $order = DB::table('order_ojek_online')
                ->join('Pengguna as customer', 'order_ojek_online.customer_id', '=', 'customer.id_pengguna')
                ->join('registrasi_driver_sancaka as driver', 'order_ojek_online.driver_id', '=', 'driver.id_pengguna')
                ->where('order_ojek_online.order_id', $order_id)
                ->select(
                    'order_ojek_online.*',
                    'customer.nama_lengkap as customer_name',
                    'customer.no_wa as customer_phone',
                    'driver.nama_lengkap as driver_name',
                    'driver.nomor_wa as driver_phone',
                    'driver.latitude as driver_lat',
                    'driver.longitude as driver_lng',
                    'driver.is_active_map as driver_is_online',
                    'driver.foto_motor'
                )
                ->first();

            if (!$order) {
                Log::warning("LOG LOG: Gagal! Order ID " . $order_id . " tidak ditemukan di database.");
                return response()->json(['success' => false, 'message' => 'Order tidak ditemukan'], 404);
            }

            Log::info("LOG LOG: SUKSES! Data order berhasil ditarik dan dikirim ke Frontend.");

            return response()->json(['success' => true, 'data' => $order]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: CRASH GET ORDER DETAIL! Pesan: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());

            return response()->json(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint POST: /api/mobile/order/update-status
     * Dipicu saat driver mengubah status (otw_jemput, otw_antar, completed)
     */
    public function update_status_order(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $newStatus = $request->input('status');
            $driverUser = $request->user();

            if (!$orderId || !$newStatus) {
                return response()->json(['success' => false, 'message' => 'Data tidak lengkap.'], 400);
            }

            // 1. UPDATE STATUS DI DATABASE
            DB::table('order_ojek_online')
                ->where('order_id', $orderId)
                ->where('driver_id', $driverUser->id_pengguna)
                ->update([
                    'status'     => $newStatus,
                    'updated_at' => now()
                ]);

            // 2. KIRIM NOTIFIKASI KE PELANGGAN BAHWA STATUS BERUBAH
            $order = DB::table('order_ojek_online')->where('order_id', $orderId)->first();

            if ($order) {
                $customerToken = DB::table('Pengguna')->where('id_pengguna', $order->customer_id)->value('expo_token');

                if ($customerToken) {
                    $notifTitle = 'Info Pesanan';
                    $notifBody = 'Status pesanan Anda diperbarui.';

                    // Kustomisasi pesan berdasarkan status
                    if ($newStatus === 'otw_jemput') {
                        $notifTitle = '🛵 Driver Menuju Lokasi';
                        $notifBody = $driverUser->nama_lengkap . ' sedang meluncur menjemput Anda.';
                    } else if ($newStatus === 'otw_antar') {
                        $notifTitle = '🏁 Menuju Tujuan';
                        $notifBody = 'Silakan pakai helm dan nikmati perjalanan Anda bersama Sancaka Express.';
                    } else if ($newStatus === 'completed') {
                        $notifTitle = '✅ Pesanan Selesai';
                        $notifBody = 'Terima kasih telah menggunakan layanan Sancaka Ride!';
                    }

                    Http::post('https://exp.host/--/api/v2/push/send', [
                        'to'    => $customerToken,
                        'title' => $notifTitle,
                        'body'  => $notifBody,
                        'sound' => 'default',
                        'data'  => [
                            'action'   => 'status_updated',
                            'order_id' => $orderId,
                            'status'   => $newStatus
                        ]
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Status perjalanan berhasil diperbarui.']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("[API DRIVER UPDATE STATUS] Crash: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sistem Error saat update status.'], 500);
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

    // =========================================================================
    // TAMBAHKAN FUNGSI HISTORY DI SINI
    // =========================================================================

    /**
     * Endpoint GET: /api/mobile/order/history
     * Menarik riwayat pesanan dengan filter hak akses (Admin vs Driver/Customer)
     */
    public function get_history(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $user->id_pengguna;

            // 1. Inisialisasi query utama (urutkan dari yang paling baru)
            $query = DB::table('order_ojek_online')
                ->orderBy('created_at', 'desc');

            // 2. Filter Hak Akses
            // Jika ID pengguna BUKAN 4 (Bukan Admin), maka batasi datanya
            if ($userId != 4) {
                $query->where(function ($q) use ($userId) {
                    $q->where('customer_id', $userId)
                      ->orWhere('driver_id', $userId);
                });
            }

            // 3. Eksekusi query
            $orders = $query->get();

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("[API HISTORY ORDER] Crash: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat riwayat sistem.'
            ], 500);
        }
    }

    // =========================================================================
    // MESIN KEAMANAN FILE BERINTEGRASI (INTERVENTION IMAGE + VIRUSTOTAL API)
    // =========================================================================
    private function amankanDanSimpanFile($file, $folder)
    {
        // ... (kode kamu selanjutnya tetap aman di bawah sini) ...

 // =========================================================================
    // MESIN KEAMANAN FILE BERINTEGRASI (INTERVENTION IMAGE + VIRUSTOTAL API)
    // =========================================================================
    private function amankanDanSimpanFile($file, $folder)
    {
        $ekstensi = strtolower($file->getClientOriginalExtension());
        $namaAcak = Str::uuid();

        // 1. JIKA GAMBAR -> Cuci dengan Intervention Image
        if (in_array($ekstensi, ['jpg', 'jpeg', 'png'])) {
            try {
                $namaFileBaru = $folder . '/' . $namaAcak . '.jpg';

                $img = Image::decode($file->getRealPath())->scaleDown(width: 1200);
                $encoded = $img->encodeUsingFileExtension('jpg', quality: 85);

                Storage::put('public/' . $namaFileBaru, (string) $encoded);
                return $namaFileBaru;
            } catch (\Exception $e) {
                Log::error('API Intervention Image Error: ' . $e->getMessage());
                return false;
            }
        }

        // 2. JIKA PDF -> Scan lewat VirusTotal API
        if ($ekstensi === 'pdf') {
            $isSafe = $this->scanPdfVirusTotal($file);

            if ($isSafe) {
                $namaFileBaru = $namaAcak . '.pdf';
                return $file->storeAs($folder, $namaFileBaru, 'public');
            } else {
                return false;
            }
        }

        return false;
    }

    private function scanPdfVirusTotal($file)
    {
        $apiKey = env('VIRUSTOTAL_API_KEY');
        if (empty($apiKey)) {
            Log::warning('VirusTotal API Key belum diatur di API Controller. File PDF lolos secara default.');
            return true;
        }

        $fileHash = hash_file('sha256', $file->getRealPath());

        try {
            // TAHAP 1: Cek Hash ke VirusTotal
            $cekHash = Http::withHeaders(['x-apikey' => $apiKey])
                ->get("https://www.virustotal.com/api/v3/files/{$fileHash}");

            if ($cekHash->successful()) {
                $stats = $cekHash->json('data.attributes.last_analysis_stats');
                if ($stats['malicious'] > 0 || $stats['suspicious'] > 0) {
                    Log::warning("VIRUSTOTAL API ALERT: File PDF terindikasi bahaya! Hash: {$fileHash}");
                }
                return ($stats['malicious'] == 0 && $stats['suspicious'] == 0);
            }

            // TAHAP 2: Upload File Baru
            $upload = Http::withHeaders(['x-apikey' => $apiKey])
                ->attach('file', file_get_contents($file->getRealPath()), 'berkas.pdf')
                ->post('https://www.virustotal.com/api/v3/files');

            if (!$upload->successful()) return false;
            $analysisId = $upload->json('data.id');

            // TAHAP 3: Polling Hasil
            for ($i = 0; $i < 4; $i++) {
                sleep(5);
                $analisis = Http::withHeaders(['x-apikey' => $apiKey])
                    ->get("https://www.virustotal.com/api/v3/analyses/{$analysisId}");

                if ($analisis->successful() && $analisis->json('data.attributes.status') === 'completed') {
                    $stats = $analisis->json('data.attributes.stats');
                    return ($stats['malicious'] == 0 && $stats['suspicious'] == 0);
                }
            }

            Log::warning('VirusTotal Timeout pada API pendaftaran mobile.');
            return false;

        } catch (\Exception $e) {
            Log::error('VirusTotal API Exception: ' . $e->getMessage());
            return false;
        }
    }

}
