<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis; // TAMBAHAN REDIS
use App\Http\Controllers\Api\Mobile\RewardDriverOnlineMobileController;
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
        $layanan = $request->input('layanan', $request->input('vendor'));
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

  public function toggleMap(Request $request)
    {
        try {
            $user = $request->user();
            $idPengguna = $user->id_pengguna;
            $isActive = $request->input('is_active_map'); // 1 atau 0

            if ($isActive == 1) {
                $driver = DB::table('registrasi_driver_sancaka')->where('id_pengguna', $idPengguna)->first();
                if ($driver) {
                    Redis::hmset("driver_meta:{$idPengguna}", [
                        'id' => $driver->id,
                        'id_pengguna' => $driver->id_pengguna,
                        'name' => $driver->nama_lengkap,
                        'gender' => $driver->jenis_kelamin,
                        'vehicle' => 'Ojek Sancaka',
                        'is_online' => 1
                    ]);

                    // 🔥 TAMBAHAN REDIS: Expired otomatis 12 Jam (43200 detik)
                    Redis::expire("driver_meta:{$idPengguna}", 43200);
                }
            } else {
                Redis::zrem('active_drivers', $idPengguna);
                Redis::del("driver_meta:{$idPengguna}");

            $firebaseUrl = "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app/incoming_orders/{$idPengguna}.json";
            Http::delete($firebaseUrl);
        }

        // Simpan status online ke MySQL hanya untuk persistensi jangka panjang (tidak apa-apa karena jarang dilakukan)
        DB::table('registrasi_driver_sancaka')->where('id_pengguna', $idPengguna)->update(['is_active_map' => $isActive]);

        return response()->json(['success' => true, 'message' => 'Status aktif berhasil diubah.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

   public function updateLocation(Request $request)
    {
        try {
            $user = $request->user();
            $idPengguna = $user->id_pengguna;
            $lat = (float) $request->input('latitude');
            $lng = (float) $request->input('longitude');

            // ==========================================================
            // 🛡️ [PERISAI 3]: ANTI REDIS GEO-CRASH (Bypass Fake GPS ekstrim)
            // ==========================================================
            if ($lat < -85.0 || $lat > 85.0 || $lng < -180.0 || $lng > 180.0) {
                Log::warning("LOG LOG: ⛔ Koordinat tidak masuk akal (Fake GPS / Error Device) dari Driver ID {$idPengguna}. Lat: {$lat}, Lng: {$lng}");
                return response()->json(['success' => false, 'message' => 'Koordinat GPS Anda tidak valid.'], 400);
            }

            // 1. SIMPAN KE REDIS GEOSPATIAL (Untuk Radar Penumpang & Jarak)
        try {
            Redis::geoadd('active_drivers', $lng, $lat, $idPengguna);
            // Simpan juga koordinat terakhir ke Redis Hash untuk fallback cepat
            Redis::hset("driver_meta:{$idPengguna}", 'lat', $lat, 'lng', $lng, 'last_updated', time());
            Redis::expire("driver_meta:{$idPengguna}", 43200);
        } catch (\Exception $e) {
            Log::warning("Gagal update lokasi di Redis: " . $e->getMessage());
        }

        // 2. SIMPAN KE FIREBASE RTDB (Agar Penumpang Bisa Melacak via SDK tanpa Polling API)
        // [REFACTOR]: Hapus total update ke MySQL (registrasi_driver_sancaka & Pengguna)!
        try {
            $firebaseUrl = "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app/drivers_live_gps/{$idPengguna}.json";
            Http::put($firebaseUrl, [
                'lat' => $lat,
                'lng' => $lng,
                'updated_at' => time()
            ]);
        } catch (\Exception $e) {
            Log::warning("Gagal sync GPS ke Firebase RTDB: " . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'GPS Tersinkron ke Redis & Firebase.']);
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

    public function getNearbyDrivers(Request $request)
    {
        Log::info("=== [API RADAR] MENCARI DRIVER TERDEKAT MASUK ===");
        try {
            $lat = (float) $request->query('lat');
            $lng = (float) $request->query('lng');
            $layanan = $request->query('layanan', 'ojek_online');

            Log::info("LOG LOG: [Radar] Koordinat Penjemputan -> Lat: {$lat}, Lng: {$lng}");

            if (!$lat || !$lng) {
                Log::warning("LOG LOG: [Radar] Koordinat kosong!");
                return response()->json(['success' => false, 'message' => 'Kordinat tidak ditemukan.']);
            }

            $user = $request->user();
            $passengerGender = $user->jenis_kelamin;
            Log::info("LOG LOG: [Radar] User ID Pemesan: {$user->id_pengguna}, Gender Penumpang: {$passengerGender}, Layanan: {$layanan}");

            if (empty($passengerGender)) {
                Log::warning("LOG LOG: [Radar] Gender penumpang kosong. Pencarian dihentikan.");
                return response()->json(['success' => false, 'message' => 'Lengkapi Jenis Kelamin di profil Anda.'], 400);
            }

            $formattedDrivers = [];
            $maxJemput = 25; // Sesuai aturan: Minimal jemput 1 meter sampai 25 KM

            // ==========================================================
            // 🔥 [LANGKAH 1] TARIK DATA ADMIN (ID 4) DARI MYSQL
            // ==========================================================
            $adminDataToPush = null;

            $admin = DB::table('Pengguna')
                ->selectRaw("id_pengguna, nama_lengkap, jenis_kelamin, latitude, longitude, last_seen,
                    ( 6371 * acos( cos( radians(?) ) *
                      cos( radians( latitude ) ) *
                      cos( radians( longitude ) - radians(?) ) +
                      sin( radians(?) ) *
                      sin( radians( latitude ) ) )
                    ) AS distance", [$lat, $lng, $lat])
                ->where('id_pengguna', 4)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->first();

            if ($admin && $admin->distance <= $maxJemput) {
                // Cek Status Online (Maks 3 Menit)
                $isAdminOnline = false;
                if (!empty($admin->last_seen)) {
                    $diffMin = \Carbon\Carbon::parse($admin->last_seen)->diffInMinutes(now());
                    if ($diffMin <= 3) $isAdminOnline = true;
                }

                // Cek Aturan Syariah (Hanya berlaku untuk Ojek Online)
                $isAdminSyariahPass = true;
                if ($layanan === 'ojek_online') {
                    if ($admin->jenis_kelamin !== $passengerGender) {
                        $isAdminSyariahPass = false;
                        Log::warning("LOG LOG: [Radar Admin] ❌ Admin DITOLAK (Syariah Ojek Online / Beda Gender)");
                    }
                }

                if ($isAdminOnline && $isAdminSyariahPass) {
                    $adminDataToPush = [
                        'id'           => 4,
                        'id_pengguna'  => 4,
                        'name'         => 'Pusat Radar Sancaka (Admin)',
                        'vehicle'      => 'Sancaka Express',
                        'distance'     => round($admin->distance, 1) . ' KM',
                        'distance_raw' => (float) $admin->distance,
                        'lat'          => (float) $admin->latitude,
                        'lng'          => (float) $admin->longitude,
                        'is_online'    => true
                    ];
                    Log::info("LOG LOG: [Radar] ✅ Admin ONLINE & Lolos Syarat (Jarak Jemput: {$admin->distance} KM).");
                }
            }

            // ==========================================================
            // 🔥 [LANGKAH 2] TARIK DRIVER BIASA DARI REDIS (ANTI N+1)
            // ==========================================================
            Log::info("LOG LOG: [Radar] Mencari driver biasa di Redis. Radius jemput: {$maxJemput} KM.");
            $nearbyRaw = Redis::georadius('active_drivers', $lng, $lat, $maxJemput, 'km', ['WITHDIST', 'ASC']);

            if (!empty($nearbyRaw)) {
                $pipeline = Redis::pipeline();
                $driverDistances = [];

                foreach ($nearbyRaw as $item) {
                    $dId = null;
                    $dist = 0;

                    if (is_array($item)) {
                        $dId = $item[0] ?? null;
                        $dist = isset($item[1]) ? (float) $item[1] : 0;
                    } elseif (is_object($item)) {
                        $dId = $item->member ?? $item->name ?? null;
                        $dist = isset($item->distance) ? (float) $item->distance : 0;
                    } else {
                        $dId = $item;
                    }

                    if ($dId && $dId != 4) { // Hindari dobel ID 4 jika nyangkut di Redis
                        $pipeline->hgetall("driver_meta:{$dId}");
                        $driverDistances[$dId] = $dist;
                    }
                }

                $metaResults = $pipeline->execute();

                foreach ($metaResults as $meta) {
                    if (!empty($meta) && isset($meta['id_pengguna'])) {
                        $dId = $meta['id_pengguna'];
                        $driverGender = $meta['gender'] ?? 'KOSONG';
                        $dist = $driverDistances[$dId] ?? 0;

                        // Cek Aturan Syariah (Hanya berlaku untuk Ojek Online)
                        $isDriverSyariahPass = true;
                        if ($layanan === 'ojek_online') {
                            if ($driverGender !== $passengerGender) {
                                $isDriverSyariahPass = false;
                                Log::warning("LOG LOG: [Radar] ❌ Driver Biasa ID {$dId} DITOLAK (Syariah Ojek Online / Beda Gender)");
                            }
                        }

                        if ($isDriverSyariahPass) {
                            $formattedDrivers[] = [
                                'id'           => (int) ($meta['id'] ?? $dId),
                                'id_pengguna'  => (int) $dId,
                                'name'         => $meta['name'] ?? 'Driver Sancaka',
                                'vehicle'      => $meta['vehicle'] ?? 'Ojek Sancaka',
                                'distance'     => round($dist, 1) . ' KM',
                                'distance_raw' => $dist,
                                'lat'          => (float) ($meta['lat'] ?? 0),
                                'lng'          => (float) ($meta['lng'] ?? 0),
                                'is_online'    => true
                            ];
                            Log::info("LOG LOG: [Radar] ✅ Driver Biasa ID {$dId} Lolos Filter (Jarak Jemput: {$dist} KM).");
                        }
                    }
                }
            }

            // ==========================================================
            // 🔥 [LANGKAH 3] GABUNGKAN & PRIORITASKAN SORTING
            // ==========================================================
            if ($adminDataToPush) {
                $formattedDrivers[] = $adminDataToPush;
            }

            usort($formattedDrivers, function($a, $b) use ($layanan) {
                // RULE: Sancaka Express WAJIB dahulukan Admin ID 4
                if ($layanan === 'sancaka_express') {
                    if ($a['id_pengguna'] == 4) return -1;
                    if ($b['id_pengguna'] == 4) return 1;
                }

                // Kalau Ojek Online, murni saingan berdasar jarak terdekat
                return $a['distance_raw'] <=> $b['distance_raw'];
            });

            Log::info("LOG LOG: [Radar Final] TOTAL DRIVER YANG DIKIRIM KE APLIKASI: " . count($formattedDrivers));

            return response()->json(['success' => true, 'data' => $formattedDrivers]);
        } catch (\Exception $e) {
            Log::error("LOG LOG: [Radar CRASH] Pesan Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal sistem: ' . $e->getMessage()], 500);
        }
    }


  public function notify_driver(Request $request)
    {
        Log::info("=== [API MAPBOX] REQUEST NOTIFY DRIVER / ADMIN (ORDER BARU) MASUK ===");
        Log::info("LOG LOG: Payload dari HP Pelanggan: ", $request->all());

        $customerLat = $request->input('origin_lat');
        $customerLng = $request->input('origin_lng');
        $layanan     = $request->input('layanan', $request->input('vendor', 'ojek_online'));
        $driverId    = $request->input('driver_id');

        $customer = $request->user();

        // ==========================================================
        // 🛡️ [PERISAI TAMBAHAN]: MENCEGAH BUG & LIMITASI ANTAR
        // ==========================================================

        // LOG LOG: Jika layanan Ojek Online WAJIB ada Driver, jika Sancaka Express boleh kosong (masuk database antrean)
        if (empty($driverId) && $layanan !== 'sancaka_express') {
            Log::warning("LOG LOG: ⛔ Order Ditolak! Aplikasi tidak mengirimkan ID Driver.");
            return response()->json([
                'status' => false,
                'message' => 'Driver/Kurir belum tersedia di sekitar Anda. Silakan tunggu beberapa saat lagi.'
            ], 400);
        }

        $destAddress = strtolower($request->input('dest_address', ''));
        if (str_contains($destAddress, 'pilih tujuan')) {
            Log::warning("LOG LOG: ⛔ Order Ditolak! Tujuan belum dipilih oleh user.");
            return response()->json([
                'status' => false,
                'message' => 'Silakan pilih lokasi tujuan dengan benar di peta.'
            ], 400);
        }

        // LIMITASI MAKSIMAL ANTAR (DROP-OFF)
        $jarakKm = (float) $request->input('jarak_km', 0);

        if ($layanan === 'sancaka_express') {
            // Sancaka Express: Admin max antar 60 KM. Driver biasa tak terhingga (tidak dicek).
            if ($driverId == 4 && $jarakKm > 60) {
                Log::warning("LOG LOG: ⛔ Order Sancaka Express Ditolak! Jarak antar Admin {$jarakKm} KM melebihi batas 60 KM.");
                return response()->json([
                    'status' => false,
                    'message' => 'Jarak antar khusus Sancaka Express (Pusat) maksimal 60 KM. Coba cari lokasi yang lebih dekat.'
                ], 400);
            }
        }
        // ==========================================================

        // ==========================================================
        // 🛡️ [PERISAI 2]: ANTI MANIPULASI TARIF (HACKER BYPASS)
        // ==========================================================
        $metodePembayaran = strtoupper($request->input('metode_pembayaran', 'CASH'));
        $tarif = (float) $request->input('tarif', 0);

        // Ambil batas bawah tarif dari settingan atau set default 3000 (Sancaka Express termurah)
        $tarifMinimal = ($layanan === 'ojek_online') ? 5000 : 3000;

        if ($tarif < $tarifMinimal) {
            Log::error("LOG LOG: ☠️ HACKING DETECTED! Manipulasi harga! User ID {$customer->id_pengguna} mengirim tarif Rp {$tarif}");
            return response()->json([
                'status' => false,
                'message' => 'Tarif tidak valid atau terindikasi dimanipulasi oleh sistem pihak ketiga.'
            ], 400);
        }

        // ==========================================================
        // VALIDASI SALDO PENUMPANG
        // ==========================================================
        if ($metodePembayaran === 'SALDO') {
            $cekSaldoUser = DB::table('Pengguna')
                ->where('id_pengguna', $customer->id_pengguna ?? $customer->id)
                ->value('saldo');

            if ($cekSaldoUser < $tarif) {
                Log::warning("LOG LOG: Order ditolak! Saldo Penumpang kurang.");
                return response()->json([
                    'status' => false,
                    'message' => 'Saldo Sancaka Anda tidak mencukupi. Silakan Top Up atau ubah metode ke Tunai/CASH.'
                ], 400);
            }
        }

        // ==========================================================
        // TENTUKAN PREFIX BERDASARKAN LAYANAN
        // ==========================================================
        if ($layanan === 'sancaka_express') {
            Log::info("LOG LOG: Layanan Sancaka Express. Target: Driver ID {$driverId}");
            $orderPrefix = 'S-EXP-';
        } else {
            Log::info("LOG LOG: Layanan Ojek Online. Target: Driver ID {$driverId}");
            $orderPrefix = 'S-RIDE-';
        }

        // ==========================================================
        // CARI TARGET FCM (APAKAH ITU ADMIN ATAU DRIVER BIASA?)
        // ==========================================================
        $driver = null;
        $jarakKePemesanMeter = 0;

        // LOG LOG: Target spesifik hanya dicari jika layanannya BUKAN Sancaka Express
        if ($layanan !== 'sancaka_express') {
            if ($driverId == 4) {
                $driver = DB::table('Pengguna')
                    ->where('id_pengguna', 4)
                    ->select('fcm_token', 'fcm_token_debug', 'nama_lengkap', 'latitude', 'longitude', 'id_pengguna as driver_user_id')
                    ->first();

                if ($driver && !$driver->latitude) {
                    $driver->latitude = -7.4025;
                    $driver->longitude = 111.4558;
                }
            } else {
                $driver = DB::table('registrasi_driver_sancaka')
                    ->join('Pengguna', 'registrasi_driver_sancaka.id_pengguna', '=', 'Pengguna.id_pengguna')
                    ->where('registrasi_driver_sancaka.id', $driverId)
                    ->select('Pengguna.fcm_token', 'Pengguna.fcm_token_debug', 'registrasi_driver_sancaka.nama_lengkap', 'registrasi_driver_sancaka.latitude', 'registrasi_driver_sancaka.longitude', 'registrasi_driver_sancaka.id_pengguna as driver_user_id')
                    ->first();
            }

            if (!$driver || (empty($driver->fcm_token) && empty($driver->fcm_token_debug))) {
                Log::warning("LOG LOG: Target Offline atau FCM Kosong untuk Driver ID: {$driverId}.");
                return response()->json(['status' => false, 'message' => 'Driver/Admin belum mengaktifkan notifikasi.'], 404);
            }

            $jarakKePemesanMeter = $this->getDistanceMeter(
                (float)$driver->latitude, (float)$driver->longitude,
                (float)$customerLat, (float)$customerLng
            );
        }

        // GENERATE ORDER ID
        $orderId = $orderPrefix . strtoupper(uniqid());
        Log::info("LOG LOG: Order ID di-generate: " . $orderId);

        // ==========================================================
        // 🔥 TAMBAHAN LOGIKA PAYMENT GATEWAY (MENGAMBIL DARI TOPUP)
        // ==========================================================
        $paymentMethodClean = str_replace('#', '', $metodePembayaran);
        $isGateway = in_array($paymentMethodClean, ['DOKU', 'DOKU_JOKUL', 'DANA', 'DANA_BINDING', 'DANA_BALANCE']) || str_starts_with($paymentMethodClean, 'TRIPAY');

        // Jika Gateway, tahan status jadi unpaid agar driver tidak menjemput
        $initialStatus = $isGateway ? 'unpaid' : 'pending';
        $paymentUrl = null;

        // DB Transaction agar jika API Tripay/Doku error, database tidak nyangkut
        DB::beginTransaction();

        try {
            DB::table('order_ojek_online')->insert([
                'order_id'          => $orderId,
                'customer_id'       => $customer->id_pengguna,
                // LOG LOG: Jika Express biarkan NULL agar bisa diambil siapa saja
                'driver_id'         => ($layanan === 'sancaka_express') ? 0 : $driver->driver_user_id,
                'origin_lat'        => $customerLat,
                'origin_lng'        => $customerLng,
                'origin_address'    => $request->input('origin_address', 'Lokasi Jemput'),
                'dest_lat'          => $request->input('dest_lat'),
                'dest_lng'          => $request->input('dest_lng'),
                'dest_address'      => $request->input('dest_address', 'Tujuan Antar'),
                'jarak_km'          => (float) $request->input('jarak_km', 0),
                'waktu_menit'       => (int) $request->input('waktu_menit', 0),
                'tarif'             => (float) $tarif,
                'metode_pembayaran' => $paymentMethodClean, // Bersih tanpa hashtag
                'catatan'           => $request->input('catatan', null),
                'status'            => $initialStatus,      // Status dinamis (unpaid / pending)
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            Log::info("LOG LOG: Sukses Insert ke Database MySQL dengan status: {$initialStatus}");

            // ==========================================================
            // 🔥 EKSEKUSI API GATEWAY JIKA METODE PEMBAYARAN ONLINE
            // ==========================================================
            if ($isGateway) {
                // Instansiasi Service DANA
                $danaSignature = app(\App\Services\DanaSignatureService::class);
                $this->applyDynamicConfig();

                if ($paymentMethodClean === 'DANA_BINDING') {
                    if (empty($customer->dana_access_token)) {
                        throw new \Exception("Akun DANA belum terhubung.");
                    }
                    $danaRes = $this->_createDanaBindingOrderWidget($orderId, $tarif, $customer, $danaSignature);

                    if (!isset($danaRes['success']) || !$danaRes['success']) {
                        throw new \Exception($danaRes['message'] ?? 'Gagal memproses Auto Debit DANA.');
                    }

                    $paymentUrl = $danaRes['redirect_url'] ?? null;

                    // Jika sukses instan auto-debit (tanpa perlu buka URL), majukan status ke pending
                    if (!$paymentUrl && $danaRes['success']) {
                        $initialStatus = 'pending';
                        DB::table('order_ojek_online')->where('order_id', $orderId)->update(['status' => 'pending']);
                        Log::info("LOG LOG: Auto Debit DANA sukses seketika. Status order diubah ke pending.");
                    }

                } elseif (in_array($paymentMethodClean, ['DANA', 'DANA_BALANCE'])) {
                    $danaRes = $this->_createDanaGatewayOrder($orderId, $tarif, $customer, $danaSignature);
                    if (!isset($danaRes['success']) || !$danaRes['success']) {
                        throw new \Exception($danaRes['message'] ?? 'Gagal membuat tagihan DANA.');
                    }
                    $paymentUrl = $danaRes['redirect_url'];

                } elseif ($paymentMethodClean === 'DOKU' || $paymentMethodClean === 'DOKU_JOKUL') {
                    $dokuService = new \App\Services\DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($orderId, $tarif);
                    if (empty($paymentUrl)) {
                        throw new \Exception("Gagal mendapatkan link dari DOKU Jokul.");
                    }

               } else {
                    // JALUR TRIPAY (BCAVA, QRIS, DLL)
                    $orderItems = [
                        ['sku' => 'RIDE', 'name' => 'Layanan Sancaka', 'price' => $tarif, 'quantity' => 1]
                    ];
                    $tripayResponse = $this->_createTripayOrderInternal($orderId, $tarif, $paymentMethodClean, $orderItems, $customer);

                    if (empty($tripayResponse['success'])) {
                        throw new \Exception($tripayResponse['message'] ?? 'Gagal membuat tagihan Tripay.');
                    }
                    $paymentUrl = $tripayResponse['data']['checkout_url'];
                }
            }

            // ✅ TARUH KODENYA TEPAT DI SINI, SEBELUM DB::commit() ✅
            DB::table('order_ojek_online')->where('order_id', $orderId)->update(['payment_url' => $paymentUrl]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG LOG: CRASH Insert DB / Gateway! Pesan: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()
            ], 400); // 400 Bad Request agar muncul di alert HP
        }

        // ==========================================================
        // 🔥 EKSEKUSI NOTIF DRIVER HANYA JIKA STATUS PENDING
        // (Cash, Saldo Dompet, atau DANA Auto Debit yg sukses)
        // ==========================================================
        if ($initialStatus === 'pending') {

           // TAMBAHAN REDIS: Simpan Order Sementara
            try {
                $orderDataRedis = [
                    'order_id'    => $orderId,
                    'customer_id' => $customer->id_pengguna,
                    'driver_id'   => ($layanan === 'sancaka_express') ? null : $driver->driver_user_id,
                    'status'      => 'pending',
                    'layanan'     => $layanan,
                    'tarif'       => $tarif
                ];

                // LOG LOG: Jika Sancaka Express, biarkan di redis lama (12 Jam) karena user mau nunggu berjam-jam
                $expireTime = ($layanan === 'sancaka_express') ? 43200 : 1800;
                \Illuminate\Support\Facades\Redis::setex("order_active:{$orderId}", $expireTime, json_encode($orderDataRedis));
                Log::info("LOG LOG: Sukses simpan order {$orderId} ke Redis");
            } catch (\Exception $e) {
                Log::warning("LOG LOG: Gagal simpan ke Redis: " . $e->getMessage());
            }

            // FIREBASE RTDB PUSH
            try {
                // LOG LOG: Jika express, taruh di node "pool_express" agar semua driver bisa lihat
                if ($layanan === 'sancaka_express') {
                    $firebaseDbUrl = "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app/incoming_orders/pool_express/{$orderId}.json";
                } else {
                    $firebaseDbUrl = "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app/incoming_orders/{$driver->driver_user_id}/{$orderId}.json";
                }$fbResponse = Http::put($firebaseDbUrl, [
                    'order_id'       => $orderId,
                    'origin_lat'     => $customerLat,
                    'origin_lng'     => $customerLng,
                    'origin_address' => $request->input('origin_address', 'Lokasi Jemput'),
                    'dest_address'   => $request->input('dest_address', 'Tujuan Antar'),
                    'tarif'          => $tarif,
                    'timestamp'      => now()->timestamp
                ]);

                if ($fbResponse->successful()) {
                    Log::info("LOG LOG: Sukses Insert pesanan ke Firebase RTDB.");
                } else {
                    Log::error("LOG LOG: 💥 FIREBASE PUT GAGAL! Status: " . $fbResponse->status() . " | Pesan: " . $fbResponse->body());
                }
            } catch (\Exception $e) {
                Log::error("LOG LOG: 💥 CRASH JARINGAN SERVER KE FIREBASE: " . $e->getMessage());
            }

           // FIREBASE FCM (PUSH NOTIFICATION)
            $accessToken = $this->getGoogleAccessToken();
            $projectId = 'sancaka-express';

            $targets = []; // Array menampung object user (punya fcm_token & fcm_token_debug)

            if ($layanan === 'sancaka_express') {
                // BROADCAST: Ambil Admin & Driver yang Aktif + APPROVED
                $broadcastTargets = DB::table('Pengguna')
                    ->leftJoin('registrasi_driver_sancaka', 'Pengguna.id_pengguna', '=', 'registrasi_driver_sancaka.id_pengguna')
                    ->where('Pengguna.id_pengguna', 4) // Admin
                    ->orWhere(function($query) {
                        $query->where('registrasi_driver_sancaka.is_active_map', 1)
                              ->where('registrasi_driver_sancaka.status', 'approved'); // WAJIB APPROVED
                    })
                    ->select('Pengguna.fcm_token', 'Pengguna.fcm_token_debug')
                    ->get();

                foreach ($broadcastTargets as $tgt) {
                    $targets[] = $tgt;
                }
            } else {
                // OJEK ONLINE: Hanya 1 Driver Target
                if ($driver) $targets[] = $driver;

                $targetDriverId = $driver ? $driver->driver_user_id : null;
                if ($targetDriverId != 4) { // Hindari duplikat jika drivernya memang admin itu sendiri
                    $adminTarget = DB::table('Pengguna')
                        ->where('id_pengguna', 4)
                        ->select('fcm_token', 'fcm_token_debug')
                        ->first();

                    if ($adminTarget) {
                        $targets[] = $adminTarget;
                    }
                }
            }

            // EKSEKUSI PENGIRIMAN
            if ($accessToken && count($targets) > 0) {
                foreach ($targets as $userTarget) {
                    // Ambil token untuk 1 user ini saja
                    $userTokens = [];
                    if (!empty($userTarget->fcm_token)) $userTokens[] = ['mode' => 'PRODUCTION', 'token' => $userTarget->fcm_token];
                    if (!empty($userTarget->fcm_token_debug)) $userTokens[] = ['mode' => 'DEBUG', 'token' => $userTarget->fcm_token_debug];

                    // Looping token (coba production, jika sukses STOP untuk user ini, lanjut user berikutnya)
                    foreach ($userTokens as $tokenData) {
                        $mode = $tokenData['mode'];
                        $tokenStr = $tokenData['token'];

                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type'  => 'application/json',
                        ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                            'message' => [
                                'token' => $tokenStr,
                                'android' => ['priority' => 'HIGH'],
                                'data' => [
                                    'action'           => 'new_order',
                                    'layanan'          => (string) $layanan,
                                    'order_id'         => (string) $orderId,
                                    'customer_id'      => (string) ($customer->id_pengguna ?? $customer->id),
                                    'tarif'            => (string) $tarif,
                                    'jarak_ke_pemesan' => (string) $jarakKePemesanMeter,
                                    'origin_address'   => (string) $request->input('origin_address', ''),
                                    'dest_address'     => (string) $request->input('dest_address', ''),
                                    'catatan'          => (string) $request->input('catatan', ''),
                                    'berat'            => (string) $request->input('weight', '0'),
                                    'nama_barang'      => (string) $request->input('nama_barang', '-'),
                                    'panjang'          => (string) $request->input('panjang', '0'),
                                    'lebar'            => (string) $request->input('lebar', '0'),
                                    'tinggi'           => (string) $request->input('tinggi', '0'),
                                    'asuransi'         => (string) $request->input('asuransi', 'tidak'),
                                ]
                            ]
                        ]);

                        if ($response->successful()) {
                            Log::info("LOG LOG: SUKSES! Notif terkirim ke 1 User via Token {$mode}.");
                            break; // HANYA BREAK LOOP TOKEN INTERNAL, LOOP USER LUAR TETAP JALAN!
                        }
                    }
                }
            }

        } else {
            Log::info("LOG LOG: Status pesanan {$orderId} adalah UNPAID. Notifikasi FCM driver ditahan menunggu pelunasan dari Gateway.");
        }

        // ==========================================================
        // RESPONSE KEMBALI KE FRONTEND
        // ==========================================================
        if ($initialStatus === 'unpaid') {
            // Karena aplikasi React Native Expo sudah live dan akan langsung force redirect ke Map jika status=true,
            // Maka kita tetap kirim status=true, tapi informasikan kepada user untuk bayar lewat menu Riwayat.
            return response()->json([
                'status' => true,
                'message' => 'Pesanan diamankan! Silakan buka menu Riwayat untuk melanjutkan pembayaran agar Driver menuju ke lokasi Anda.',
                'order_id' => $orderId,
                'payment_url' => $paymentUrl
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Pesanan berhasil dikirim ke Admin/Kurir.',
            'order_id' => $orderId
        ]);
    }


    public function accept_order(Request $request)
    {
        Log::info("=== [API MAPBOX] REQUEST ACCEPT ORDER MASUK ===");
        Log::info("LOG LOG: Payload Accept Order: ", $request->all());

        try {
            $orderId = $request->input('order_id');
            $driverUser = $request->user();

            if (!$orderId || !$driverUser) {
                return response()->json(['success' => false, 'message' => 'Order ID atau data Driver tidak valid.'], 400);
            }

            // 1. CEK APAKAH ORDER MASIH TERSEDIA
            $order = DB::table('order_ojek_online')->where('order_id', $orderId)->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan di database.'], 404);
            }

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Maaf, pesanan ini baru saja diambil oleh driver lain atau telah dibatalkan.'
                ], 409);
            }

            // 2. AMBIL DETAIL KENDARAAN & DATA DRIVER
            $driverDetail = DB::table('registrasi_driver_sancaka')
                ->where('id_pengguna', $driverUser->id_pengguna)
                ->first();

            $namaDriver = $driverDetail->nama_lengkap ?? $driverUser->nama_lengkap ?? 'Driver Sancaka';
            $platNomor  = $driverDetail->plat_nomor ?? '-';
            $merkMotor  = $driverDetail->merk_kendaraan ?? '-';
            $noWaDriver = $driverDetail->nomor_wa ?? $driverUser->no_wa ?? '';

            // 3. UPDATE STATUS ORDER DI DATABASE
            $affected = DB::table('order_ojek_online')
                ->where('order_id', $orderId)
                ->where('status', 'pending') // 🔥 TAMBAHKAN BARIS INI (Kunci Pengaman)
                ->update([
                    'driver_id'  => $driverUser->id_pengguna,
                    'status'     => 'accepted',
                    'updated_at' => now()
                ]);

            if ($affected === 0) {
                Log::warning("LOG LOG: Order {$orderId} gagal diambil oleh {$driverUser->id_pengguna} (Mungkin sudah diambil driver lain).");
                return response()->json([
                    'success' => false,
                    'message' => 'Maaf, pesanan ini baru saja diambil oleh driver lain atau telah dibatalkan.'
                ], 409); // Kembalikan error 409 Conflict
            }

            Log::info("LOG LOG: Pesanan {$orderId} resmi diterima oleh Driver ID: {$driverUser->id_pengguna}");

            // HAPUS DARI FIREBASE RTDB AGAR HILANG DARI DASHBOARD DRIVER LAIN
            try {
                // LOG LOG: Jika orderan adalah Sancaka Express, hapus dari folder "pool_express"
                if (str_starts_with($orderId, 'S-EXP-')) {
                    $firebaseDbUrl = "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app/incoming_orders/pool_express/{$orderId}.json";
                } else {
                    $firebaseDbUrl = "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app/incoming_orders/{$driverUser->id_pengguna}/{$orderId}.json";
                }
                $fbResponse = Http::delete($firebaseDbUrl);

                if ($fbResponse->successful()) {
                    Log::info("LOG LOG: Pesanan berhasil dihapus dari Firebase RTDB Dashboard driver.");
                } else {
                    Log::error("LOG LOG: 💥 FIREBASE DELETE GAGAL! Status: " . $fbResponse->status() . " | Pesan: " . $fbResponse->body());
                }
            } catch (\Exception $e) {
                Log::error("LOG LOG: 💥 CRASH JARINGAN SERVER KE FIREBASE (DELETE): " . $e->getMessage());
            }

            // 4. KIRIM NOTIFIKASI KE PELANGGAN (HYBRID TOKEN SYSTEM)
            // Tarik dua jenis token milik pelanggan
            $customer = DB::table('Pengguna')->where('id_pengguna', $order->customer_id)->select('fcm_token', 'fcm_token_debug')->first();

            if ($customer && (!empty($customer->fcm_token) || !empty($customer->fcm_token_debug))) {
                Log::info("LOG LOG: Mempersiapkan Push Notif FCM v1 ke HP Pelanggan...");

                $accessToken = $this->getGoogleAccessToken();
                $projectId = 'sancaka-express';

                $tokensToTry = [];
                if (!empty($customer->fcm_token)) {
                    $tokensToTry[] = ['mode' => 'PRODUCTION', 'token' => $customer->fcm_token];
                }
                if (!empty($customer->fcm_token_debug)) {
                    $tokensToTry[] = ['mode' => 'DEBUG', 'token' => $customer->fcm_token_debug];
                }

                if ($accessToken && count($tokensToTry) > 0) {
                    $notifTerkirim = false;

                    foreach ($tokensToTry as $target) {
                        $mode = $target['mode'];
                        $tokenStr = $target['token'];

                        Log::info("LOG LOG: Mencoba menembak token pelanggan mode {$mode}...");

                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type'  => 'application/json',
                        ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                            'message' => [
                                'token' => $tokenStr,
                                'android' => [
                                    'priority' => 'HIGH'
                                ],
                                'notification' => [
                                    'title' => '✅ Driver Ditemukan!',
                                    'body'  => "{$namaDriver} ({$platNomor}) siap meluncur menjemput Anda!"
                                ],
                                'data' => [
                                    'action'       => 'order_accepted',
                                    'order_id'     => (string) $orderId,
                                    'driver_name'  => (string) $namaDriver,
                                    'plat_nomor'   => (string) $platNomor,
                                    'merk_motor'   => (string) $merkMotor,
                                    'driver_phone' => (string) $noWaDriver,
                                    'driver_lat'   => (string) ($driverDetail->latitude ?? 0),
                                    'driver_lng'   => (string) ($driverDetail->longitude ?? 0)
                                ]
                            ]
                        ]);

                        if ($response->successful()) {
                            Log::info("LOG LOG: SUKSES! Notif pelanggan terkirim menggunakan Token {$mode}. Balasan FCM v1: " . $response->body());
                            $notifTerkirim = true;
                            break; // BERHENTI LOOPING JIKA SUDAH SUKSES
                        } else {
                            Log::warning("LOG LOG: GAGAL kirim ke pelanggan menggunakan Token {$mode}. Mencoba cadangan jika ada. Error: " . $response->body());
                        }
                    }

                    if (!$notifTerkirim) {
                        Log::error("LOG LOG: FATAL! Semua token pelanggan (Production & Debug) gagal atau hangus.");
                    }
                } else {
                    Log::warning("LOG LOG: Gagal kirim notif pelanggan. Access Token FCM v1 gagal dibuat.");
                }
            } else {
                Log::warning("LOG LOG: Token FCM Pelanggan kosong di database (Baik Production maupun Debug). Notif tidak dikirim.");
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil diterima! Silakan menuju ke lokasi jemput.',
                'data' => [
                    'order_id' => $orderId
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: CRASH di accept_order! Pesan: " . $e->getMessage());
            Log::error("LOG LOG: Trace: " . $e->getTraceAsString());

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem saat menerima pesanan.'], 500);
        }
    }

 public function get_order_detail(Request $request, $order_id)
    {
        Log::info("=== [API MAPBOX] REQUEST GET ORDER DETAIL MASUK ===");
        Log::info("LOG LOG: Mencari data untuk Order ID: " . $order_id);

        try {
            $user = $request->user();
            $userId = $user ? $user->id_pengguna : null;
            $userRole = $user ? ($user->role ?? 'Pelanggan') : 'Pelanggan';

            $query = DB::table('order_ojek_online')
                ->join('Pengguna as customer', 'order_ojek_online.customer_id', '=', 'customer.id_pengguna')
                ->leftJoin('registrasi_driver_sancaka as driver', 'order_ojek_online.driver_id', '=', 'driver.id_pengguna')
                // 👇 TAMBAHAN: Tarik juga data dari tabel Pengguna jika yang ambil order adalah Admin
                ->leftJoin('Pengguna as admin_user', 'order_ojek_online.driver_id', '=', 'admin_user.id_pengguna')
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
                    'driver.foto_motor',
                    // 👇 Ambil koordinat langsung dari tabel Pengguna
                    'admin_user.latitude as admin_lat',
                    'admin_user.longitude as admin_lng'
                );

            // --- KUNCI KEAMANAN IDOR ---
            if ($userId != 4 && $userRole !== 'Admin') {
                $query->where(function($q) use ($userId) {
                    $q->where('order_ojek_online.customer_id', $userId)
                      ->orWhere('order_ojek_online.driver_id', $userId);
                });
            }

            $order = $query->first();

            if (!$order) {
                Log::warning("LOG LOG: Gagal! Order ID " . $order_id . " tidak ditemukan atau diakses ilegal.");
                return response()->json(['success' => false, 'message' => 'Order tidak ditemukan atau Anda tidak memiliki akses.'], 404);
            }

            // 🔥 PERBAIKAN LOGIKA ADMIN 🔥
            // Jika Admin (ID 4) yang ambil order, kita gunakan koordinat dari admin_lat/admin_lng
            if ($order->driver_id == 4) {
                $order->driver_name = "Pusat Radar Sancaka";
                $order->driver_phone = "08819435180";
                // Ambil lokasi dari tabel Pengguna, jika masih null baru pakai titik tengah Ngawi
                $order->driver_lat = $order->admin_lat ?? -7.4025;
                $order->driver_lng = $order->admin_lng ?? 111.4558;
                $order->driver_is_online = 1;
            }

            Log::info("LOG LOG: SUKSES! Data order berhasil ditarik dan dikirim ke Frontend.");

            return response()->json(['success' => true, 'data' => $order]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: CRASH GET ORDER DETAIL! Pesan: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

 public function update_status_order(Request $request)
    {
        Log::info("=== [API DRIVER UPDATE STATUS] REQUEST MASUK ===");
        Log::info("LOG LOG: Payload Request: ", $request->all());

        // =========================================================================
        // 🔥 1. FITUR IDEMPOTENCY (PENCEGAH DOUBLE REQUEST SAAT JARINGAN LAG) 🔥
        // =========================================================================
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey) {
            // Jika request yang sama sudah pernah diproses, langsung kembalikan respon sukses yang tersimpan
            if (\Illuminate\Support\Facades\Cache::has('idempotency_' . $idempotencyKey)) {
                Log::warning("LOG LOG: Idempotency Terdeteksi! Request duplikat dicegah untuk key: " . $idempotencyKey);
                return \Illuminate\Support\Facades\Cache::get('idempotency_' . $idempotencyKey);
            }
        }

        try {
            $orderId = $request->input('order_id');
            $newStatus = $request->input('status');
            $driverUser = $request->user();

            if (!$orderId || !$newStatus) {
                return response()->json(['success' => false, 'message' => 'Data tidak lengkap.'], 400);
            }

            // =========================================================================
            // 🔥 2. DATABASE TRANSACTION & LOCK (PENCEGAH RACE CONDITION SALDO) 🔥
            // =========================================================================
            // Kita pisahkan proses edit DB di dalam blok transaksi khusus agar aman
            // KODE YANG BENAR
            $transactionResult = DB::transaction(function () use ($orderId, $newStatus, $driverUser, $request) {

                // 1. CEK KONDISI ORDER + MENGUNCI BARIS (lockForUpdate)
                // Ini mencegah 2 request memodifikasi row pesanan ini dalam waktu bersamaan
                $order = DB::table('order_ojek_online')
                    ->where('order_id', $orderId)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    return ['status' => 404, 'response' => response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404)];
                }

                // PENGAMAN MUTLAK: Jika sudah completed, hentikan eksekusi!
                if ($order->status === 'completed' || $order->status === 'selesai') {
                    Log::warning("LOG LOG: Pencegahan Dobel Saldo! Order {$orderId} sudah selesai sebelumnya.");
                    return ['status' => 200, 'response' => response()->json([
                        'success' => true,
                        'message' => 'Pesanan ini sudah selesai sebelumnya.'
                    ])];
                }

               // 2. SIAPKAN DATA UPDATE
                $updateData = [
                    'status'     => $newStatus,
                    'updated_at' => now()
                ];

                // =========================================================================
                // 🛡️ PERISAI SANCAKA EXPRESS: WAJIB FOTO & TTD PENGIRIM SAAT AMBIL PAKET
                // =========================================================================
                if ($newStatus === 'otw_antar' && str_starts_with($order->order_id, 'S-EXP-')) {
                    $adaFotoPengirim = $request->hasFile('foto_pengirim') || !empty($order->bukti_foto_pengirim);
                    $adaTtdPengirim = $request->hasFile('ttd_pengirim') || $request->filled('ttd_pengirim') || !empty($order->bukti_ttd_pengirim);

                    if (!$adaFotoPengirim) {
                        return ['status' => 422, 'response' => response()->json(['success' => false, 'message' => 'Gagal! Layanan Sancaka Express WAJIB melampirkan Foto Pengirim sebelum mengantar paket.'], 422)];
                    }

                    if (!$adaTtdPengirim) {
                        return ['status' => 422, 'response' => response()->json(['success' => false, 'message' => 'Gagal! Layanan Sancaka Express WAJIB melampirkan Tanda Tangan Pengirim sebelum mengantar paket.'], 422)];
                    }

                    // Proses Simpan Foto Pengirim
                    if ($request->hasFile('foto_pengirim')) {
                        $foto = $request->file('foto_pengirim');
                        $namaFoto = 'foto_pengirim_' . $orderId . '_' . time() . '.' . $foto->getClientOriginalExtension();
                        $updateData['bukti_foto_pengirim'] = $foto->storeAs('bukti_pengiriman/foto', $namaFoto, 'public');
                    }

                    // Proses Simpan TTD Pengirim (Mendukung File & Base64)
                    if ($request->hasFile('ttd_pengirim')) {
                        $ttd = $request->file('ttd_pengirim');
                        $namaTtd = 'ttd_pengirim_' . $orderId . '_' . time() . '.' . $ttd->getClientOriginalExtension();
                        $updateData['bukti_ttd_pengirim'] = $ttd->storeAs('bukti_pengiriman/ttd', $namaTtd, 'public');
                    } elseif ($request->filled('ttd_pengirim')) {
                        $ttdBase64 = $request->input('ttd_pengirim');
                        if (preg_match('/^data:image\/(\w+);base64,/', $ttdBase64, $type)) {
                            $ttdData = substr($ttdBase64, strpos($ttdBase64, ',') + 1);
                            $ttdData = base64_decode($ttdData);
                            $namaTtd = 'ttd_pengirim_' . $orderId . '_' . time() . '.' . strtolower($type[1]);
                            \Illuminate\Support\Facades\Storage::disk('public')->put('bukti_pengiriman/ttd/' . $namaTtd, $ttdData);
                            $updateData['bukti_ttd_pengirim'] = 'bukti_pengiriman/ttd/' . $namaTtd;
                        }
                    }

                    if ($request->filled('foto_token_id_pengirim')) {
                        $updateData['foto_token_id_pengirim'] = $request->input('foto_token_id_pengirim');
                    }
                }
                // =========================================================================

                // =========================================================================
                // 🛡️ PERISAI SANCAKA EXPRESS: WAJIB FOTO & TTD SAAT SELESAI
                // =========================================================================
                // Jika statusnya mau diubah jadi 'completed' DAN ini adalah order Sancaka Express (Prefix S-EXP)
                if (($newStatus === 'completed' || $newStatus === 'selesai') && str_starts_with($order->order_id, 'S-EXP-')) {

                    $adaFoto = $request->hasFile('foto_penerima') || !empty($order->bukti_foto_penerima);
                    $adaTtd = $request->hasFile('ttd_penerima') || $request->filled('ttd_penerima') || !empty($order->bukti_ttd_penerima);

                    if (!$adaFoto) {
                        return ['status' => 422, 'response' => response()->json(['success' => false, 'message' => 'Gagal! Layanan Sancaka Express WAJIB melampirkan Foto Penerima sebelum diselesaikan.'], 422)];
                    }

                    if (!$adaTtd) {
                        return ['status' => 422, 'response' => response()->json(['success' => false, 'message' => 'Gagal! Layanan Sancaka Express WAJIB melampirkan Tanda Tangan Penerima sebelum diselesaikan.'], 422)];
                    }

                    // Proses Simpan Foto (Upload File)
                    if ($request->hasFile('foto_penerima')) {
                        $foto = $request->file('foto_penerima');
                        $namaFoto = 'foto_' . $orderId . '_' . time() . '.' . $foto->getClientOriginalExtension();
                        $updateData['bukti_foto_penerima'] = $foto->storeAs('bukti_pengiriman/foto', $namaFoto, 'public');
                    }

                    // Proses Simpan TTD (Bisa berupa Upload File ATAU String Base64 dari React Native Canvas)
                    if ($request->hasFile('ttd_penerima')) {
                        $ttd = $request->file('ttd_penerima');
                        $namaTtd = 'ttd_' . $orderId . '_' . time() . '.' . $ttd->getClientOriginalExtension();
                        $updateData['bukti_ttd_penerima'] = $ttd->storeAs('bukti_pengiriman/ttd', $namaTtd, 'public');
                    } elseif ($request->filled('ttd_penerima')) {
                        // Jika TTD dikirim dalam bentuk Base64 (Mendukung data:image/png;base64,...)
                        $ttdBase64 = $request->input('ttd_penerima');
                        if (preg_match('/^data:image\/(\w+);base64,/', $ttdBase64, $type)) {
                            $ttdData = substr($ttdBase64, strpos($ttdBase64, ',') + 1);
                            $ttdData = base64_decode($ttdData);
                            $namaTtd = 'ttd_' . $orderId . '_' . time() . '.' . strtolower($type[1]);
                            \Illuminate\Support\Facades\Storage::disk('public')->put('bukti_pengiriman/ttd/' . $namaTtd, $ttdData);
                            $updateData['bukti_ttd_penerima'] = 'bukti_pengiriman/ttd/' . $namaTtd;
                        }
                    }

                    if ($request->filled('foto_token_id')) {
                        $updateData['foto_token_id'] = $request->input('foto_token_id');
                    }

                }
                // =========================================================================

                $queryUpdate = DB::table('order_ojek_online')->where('order_id', $orderId);

                // Kunci Pengaman: Jika BUKAN Admin (Bukan ID 4 dan Bukan role Admin), wajib cocokkan driver_id
                if ($driverUser->id_pengguna != 4 && ($driverUser->role ?? '') !== 'Admin') {
                    $queryUpdate->where('driver_id', $driverUser->id_pengguna);
                }

                $affected = $queryUpdate->update($updateData);

                if ($affected === 0) {
                    Log::warning("LOG LOG: Gagal update status! Order ID {$orderId} tidak valid untuk Driver ID {$driverUser->id_pengguna}.");
                    return ['status' => 403, 'response' => response()->json(['success' => false, 'message' => 'Gagal mengubah status. Pesanan tidak ditemukan atau akses ditolak.'], 403)];
                }
                Log::info("LOG LOG: Database berhasil diupdate ke status: {$newStatus}");

               // =========================================================================
                // 🔥 LOGIKA SALDO MASUK KE AKUN DRIVER & POTONGAN KOMISI DINAMIS 🔥
                // =========================================================================
                if ($newStatus === 'completed' || $newStatus === 'selesai') {
                    $tarifTotal = (float) $order->tarif;
                    $driverId = $driverUser->id_pengguna;

                    // 1. AMBIL PENGATURAN KOMISI DARI DATABASE (Via ApiSettings)
                    if ($driverId == 4) {
                        // Admin (ID 4)
                        $feeType = \App\Models\Api::getValue('KOMISI_ADMIN_TYPE', 'global', 'percent');
                        $feeAmount = (float) \App\Models\Api::getValue('KOMISI_ADMIN_AMOUNT', 'global', 0);
                    } else {
                        // Driver Reguler
                        $feeType = \App\Models\Api::getValue('KOMISI_DRIVER_TYPE', 'global', 'percent');
                        $feeAmount = (float) \App\Models\Api::getValue('KOMISI_DRIVER_AMOUNT', 'global', 10);
                    }

                    // 2. HITUNG POTONGAN KOMISI APLIKASI
                    $potonganAplikasi = 0;
                    if ($feeType === 'percent') {
                        $potonganAplikasi = $tarifTotal * ($feeAmount / 100);
                    } else {
                        $potonganAplikasi = $feeAmount;
                    }

                    // 3. HITUNG PAJAK & BIAYA TAMBAHAN (Fitur Baru)
                    $pajakPercent = (float) \App\Models\Api::getValue('KOMISI_PAJAK_PERCENT', 'global', 0);
                    $biayaTambahanNominal = (float) \App\Models\Api::getValue('KOMISI_BIAYA_NOMINAL', 'global', 0);
                    $keteranganBiaya = \App\Models\Api::getValue('KOMISI_BIAYA_KETERANGAN', 'global', 'Biaya Layanan Sancaka');

                    $potonganPajak = $tarifTotal * ($pajakPercent / 100);

                    // Total Seluruh Potongan yang dikenakan ke Driver
                    $totalPotongan = $potonganAplikasi + $potonganPajak + $biayaTambahanNominal;

                    // Pengaman Mutlak: Pastikan total potongan tidak membuat saldo driver minus (maksimal = tarif total)
                    if ($totalPotongan > $tarifTotal) {
                        $totalPotongan = $tarifTotal;
                    }

                    // Tarif bersih yang didapat oleh driver
                    $tarifBersihDriver = $tarifTotal - $totalPotongan;

                    // 4. TAMBAHKAN SALDO BERSIH KE AKUN DRIVER
                    DB::table('Pengguna')
                        ->where('id_pengguna', $driverId)
                        ->increment('saldo', $tarifBersihDriver);

                    Log::info("LOG LOG: ORDER {$orderId} SELESAI.");
                    Log::info("LOG LOG: Tarif Total Rp {$tarifTotal} | Komisi App: Rp {$potonganAplikasi} | Pajak ({$pajakPercent}%): Rp {$potonganPajak} | Tambahan ({$keteranganBiaya}): Rp {$biayaTambahanNominal}");
                    Log::info("LOG LOG: Saldo Bersih Masuk ke Driver ID {$driverId}: Rp {$tarifBersihDriver}");

                    // 5. POTONG SALDO PENUMPANG (Jika bayar pakai Saldo)
                    if (strtoupper($order->metode_pembayaran) === 'SALDO') {
                        DB::table('Pengguna')
                            ->where('id_pengguna', $order->customer_id)
                            ->decrement('saldo', $tarifTotal);

                        Log::info("LOG LOG: SALDO PENUMPANG ID {$order->customer_id} BERHASIL DIPOTONG Rp {$tarifTotal}");
                    }

                    // 6. UANG POTONGAN (KOMISI+PAJAK+TAMBAHAN) DIMASUKKAN KE AKUN ADMIN
                    if ($driverId != 4 && $totalPotongan > 0) {
                        DB::table('Pengguna')
                            ->where('id_pengguna', 4)
                            ->increment('saldo', $totalPotongan);
                        Log::info("LOG LOG: TOTAL POTONGAN Rp {$totalPotongan} OTOMATIS DITAMBAHKAN KE SALDO ADMIN ID 4");
                    }

                    // ==========================================================
                    // 🔥 TAMBAHAN REDIS: Hapus Data Karena Sudah Sukses/Selesai
                    // ==========================================================
                    try {
                        Redis::del("order_active:{$orderId}");
                        Log::info("LOG LOG: Memori Redis dibersihkan. Order {$orderId} dihapus karena status COMPLETED.");
                    } catch (\Exception $e) {
                        Log::warning("LOG LOG: Gagal hapus dari Redis: " . $e->getMessage());
                    }

                } else {
                    // Jika status berubah ke 'otw_jemput' atau 'otw_antar', kita perbarui Redis
                    // dan perpanjang waktu hidupnya (misal jadi 2 jam / 7200 detik)
                    try {
                        if (Redis::exists("order_active:{$orderId}")) {
                            $redisData = json_decode(Redis::get("order_active:{$orderId}"), true);
                            $redisData['status'] = $newStatus;
                            Redis::setex("order_active:{$orderId}", 7200, json_encode($redisData));
                        }
                    } catch (\Exception $e) {
                        // Abaikan jika Redis fail, DB adalah sumber kebenaran utama
                    }
                }
                // =========================================================================

                return ['status' => 200, 'order' => $order];
            });

            // Jika transaksi digagalkan dari dalam (404/403/Pengaman Mutlak), return response-nya
            if (isset($transactionResult['response'])) {
                if ($idempotencyKey) {
                    \Illuminate\Support\Facades\Cache::put('idempotency_' . $idempotencyKey, $transactionResult['response'], now()->addMinutes(5));
                }
                return $transactionResult['response'];
            }

            // Transaksi sukses, ambil data order terbaru untuk notifikasi
            $order = $transactionResult['order'];

            // 3. KIRIM NOTIFIKASI KE PELANGGAN (HYBRID TOKEN SYSTEM)
            // Sengaja ditaruh di luar DB::transaction agar proses Firebase lambat tidak menahan database
            $customer = DB::table('Pengguna')
                ->where('id_pengguna', $order->customer_id)
                ->select('fcm_token', 'fcm_token_debug')
                ->first();

            if ($customer && (!empty($customer->fcm_token) || !empty($customer->fcm_token_debug))) {
                $notifTitle = 'Info Pesanan';
                $notifBody = 'Status pesanan Anda diperbarui.';

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

                $accessToken = $this->getGoogleAccessToken();
                $projectId = 'sancaka-express';

                $tokensToTry = [];
                if (!empty($customer->fcm_token)) {
                    $tokensToTry[] = ['mode' => 'PRODUCTION', 'token' => $customer->fcm_token];
                }
                if (!empty($customer->fcm_token_debug)) {
                    $tokensToTry[] = ['mode' => 'DEBUG', 'token' => $customer->fcm_token_debug];
                }

                if ($accessToken && count($tokensToTry) > 0) {
                    $notifTerkirim = false;
                    foreach ($tokensToTry as $target) {
                        $mode = $target['mode'];
                        $tokenStr = $target['token'];

                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type'  => 'application/json',
                        ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                            'message' => [
                                'token' => $tokenStr,
                                'android' => ['priority' => 'HIGH'],
                                'notification' => [
                                    'title' => $notifTitle,
                                    'body'  => $notifBody
                                ],
                                'data' => [
                                    'action'   => 'status_updated',
                                    'order_id' => (string) $orderId,
                                    'status'   => (string) $newStatus
                                ]
                            ]
                        ]);

                        if ($response->successful()) {
                            $notifTerkirim = true;
                            break;
                        }
                    }
                }
            }

            $finalResponse = response()->json(['success' => true, 'message' => 'Status perjalanan berhasil diperbarui.']);

            // Simpan hasil ke memori selama 5 menit jika idemptotencyKey digunakan
            if ($idempotencyKey) {
                \Illuminate\Support\Facades\Cache::put('idempotency_' . $idempotencyKey, $finalResponse, now()->addMinutes(5));
            }

            return $finalResponse;

        } catch (\Exception $e) {
            Log::error("[API DRIVER UPDATE STATUS] Crash: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sistem Error saat update status.'], 500);
        }
    }

 public function track_driver($driver_id)
{
    try {
        // [REFACTOR]: Ambil langsung dari Redis Geopos atau Redis Meta
        $meta = Redis::hgetall("driver_meta:{$driver_id}");

        if (empty($meta) || !isset($meta['lat'])) {
            // Fallback cari di Geopos jika di hash kosong
            $pos = Redis::geopos('active_drivers', $driver_id);
            if (!empty($pos) && !empty($pos[0])) {
                return response()->json([
                    'success' => true,
                    'latitude' => (float) $pos[0][1],
                    'longitude' => (float) $pos[0][0],
                    'is_online' => true
                ]);
            }
            return response()->json(['success' => false, 'message' => 'Driver sedang offline atau GPS tidak aktif.']);
        }

        return response()->json([
            'success' => true,
            'latitude' => (float) $meta['lat'],
            'longitude' => (float) $meta['lng'],
            'is_online' => ($meta['is_online'] ?? 0) == 1
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Gagal melacak: ' . $e->getMessage()], 500);
    }
}

/**
     * Endpoint GET: /api/mobile/order/history
     * Menarik riwayat pesanan dengan filter hak akses dan tipe (customer/driver)
     */
    public function get_history(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Tidak ada akses (Unauthorized).'], 401);
            }

            $userId = $user->id_pengguna;
            $userRole = $user->role ?? 'Pelanggan';

            // Tangkap parameter 'type' dari React Native (?type=customer atau ?type=driver)
            $type = $request->query('type');

            $query = DB::table('order_ojek_online')
                ->leftJoin('registrasi_driver_sancaka as driver', 'order_ojek_online.driver_id', '=', 'driver.id_pengguna')
                ->leftJoin('Pengguna as customer', 'order_ojek_online.customer_id', '=', 'customer.id_pengguna') // Join customer juga untuk admin
                ->select(
                    'order_ojek_online.*',
                    'driver.nama_lengkap as driver_name',
                    'driver.nomor_wa as driver_phone',
                    'customer.nama_lengkap as customer_name'
                )
                ->orderBy('order_ojek_online.created_at', 'desc');

            // --- FILTER KEAMANAN & HAK AKSES ADMIN ---
            if ($userId != 4 && $userRole !== 'Admin') {
                if ($type === 'customer') {
                    // Jika yang diminta adalah riwayat sebagai penumpang
                    $query->where('order_ojek_online.customer_id', $userId);
                } elseif ($type === 'driver') {
                    // Jika yang diminta adalah riwayat sebagai driver (narik)
                    $query->where('order_ojek_online.driver_id', $userId);
                } else {
                    // Default gabungan (jika parameter tidak dikirim)
                    $query->where(function ($q) use ($userId) {
                        $q->where('order_ojek_online.customer_id', $userId)
                          ->orWhere('order_ojek_online.driver_id', $userId);
                    });
                }
            }

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

   /**
     * Helper Private: Generate Access Token FCM V1 (Sistem Kebal / Auto-Fallback)
     */
    private function getGoogleAccessToken()
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $jsonKeyPath = storage_path('app/firebase-auth.json');

            // Cek keberadaan file kunci rahasia
            if (!file_exists($jsonKeyPath)) {
                \Illuminate\Support\Facades\Log::error("FCM Token: File firebase-auth.json tidak ditemukan di storage/app/");
                return null;
            }

            $keyData = json_decode(file_get_contents($jsonKeyPath), true);
            if (!$keyData || !isset($keyData['private_key'])) {
                \Illuminate\Support\Facades\Log::error("FCM Token: Format JSON firebase-auth.json tidak valid.");
                return null;
            }

            // ========================================================
            // PERCOBAAN 1: JALUR NINJA (Murni PHP OpenSSL) - Paling Cepat
            // ========================================================
            try {
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

                // Set timeout 5 detik agar tidak membebani server jika Google sedang lemot
                $response = \Illuminate\Support\Facades\Http::timeout(5)->asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ]);

                if ($response->successful() && $response->json('access_token')) {
                    // Sukses menggunakan Jalur Ninja
                    return $response->json('access_token');
                }
            } catch (\Throwable $th) {
                // Jika Jalur Ninja error, kita biarkan lolos untuk mencoba Jalur Kedua
                \Illuminate\Support\Facades\Log::warning("FCM Token: Jalur Ninja gagal (" . $th->getMessage() . "). Beralih mencoba Jalur Resmi...");
            }

            // ========================================================
            // PERCOBAAN 2: JALUR RESMI (Google Auth Library) - Fallback
            // ========================================================
            try {
                // PENGAMAN: Cek dulu apakah library Google di folder vendor benar-benar ada
                // Ini mencegah terjadinya error 'Class not found' yang bikin web down
                if (!class_exists('\Google\Auth\Credentials\ServiceAccountCredentials')) {
                    throw new \Exception("Library Google tidak ditemukan di folder vendor.");
                }

                $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
                    'https://www.googleapis.com/auth/firebase.messaging',
                    $keyData
                );

                $token = $credentials->fetchAuthToken()['access_token'];

                if ($token) {
                    // Sukses menggunakan Jalur Resmi
                    return $token;
                }
            } catch (\Throwable $th) {
                \Illuminate\Support\Facades\Log::error("FCM Token: Jalur Resmi Google juga gagal (" . $th->getMessage() . ")");
            }

            // Jika kedua jalur gagal, kembalikan null
            return null;
        });
    }

  public function saveFcmToken(Request $request)
{
    try {
        $user = auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $userId = $user->id_pengguna ?? $user->id;
        $isDebug = filter_var($request->input('is_debug', false), FILTER_VALIDATE_BOOLEAN);
        $kolomTarget = $isDebug ? 'fcm_token_debug' : 'fcm_token';

        // 1. CLEAR: Hapus token ini dari user lain agar tidak bocor (Leak Prevention)
        \Illuminate\Support\Facades\DB::table('Pengguna')
            ->where($kolomTarget, $request->fcm_token)
            ->update([$kolomTarget => null]);

        // 2. UPDATE: Simpan ke user yang sedang login
        \Illuminate\Support\Facades\DB::table('Pengguna')
            ->where('id_pengguna', $userId)
            ->update([$kolomTarget => $request->fcm_token]);

        return response()->json(['success' => true, 'message' => "FCM tersimpan aman."]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error'], 500);
    }
}

    public function getKomisiFee(Request $request)
    {
        \Illuminate\Support\Facades\Log::info("=== [DEBUG] API KOMISI FEE DIAKSES ===");

        try {
            $user = $request->user();

            if (!$user) {
                \Illuminate\Support\Facades\Log::error("[DEBUG] Token Ditolak / User tidak ditemukan!");
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi tidak valid / Token Kadaluarsa. Silakan Logout dan Login lagi.'
                ], 401);
            }

            $userId = $user->id_pengguna ?? $user->id;
            $isAdmin = ($userId == 4 || $user->role === 'Admin');

            \Illuminate\Support\Facades\Log::info("[DEBUG] User ID: {$userId} | Is Admin: " . ($isAdmin ? "YES" : "NO"));

            // 1. Tarik Aturan Komisi & Pajak Saat Ini
            $adminFeeType = \App\Models\Api::getValue('KOMISI_ADMIN_TYPE', 'global', 'percent');
            $adminFeeAmount = (float) \App\Models\Api::getValue('KOMISI_ADMIN_AMOUNT', 'global', 0);

            $driverFeeType = \App\Models\Api::getValue('KOMISI_DRIVER_TYPE', 'global', 'percent');
            $driverFeeAmount = (float) \App\Models\Api::getValue('KOMISI_DRIVER_AMOUNT', 'global', 10);

            $pajakPercent = (float) \App\Models\Api::getValue('KOMISI_PAJAK_PERCENT', 'global', 0);
            $biayaNominal = (float) \App\Models\Api::getValue('KOMISI_BIAYA_NOMINAL', 'global', 0);
            $biayaKet = \App\Models\Api::getValue('KOMISI_BIAYA_KETERANGAN', 'global', 'Biaya Layanan');

            \Illuminate\Support\Facades\Log::info("[DEBUG] Menarik data dari Database order_ojek_online...");

            // 2. Query Data Order (Hanya yang sudah selesai)
            $query = DB::table('order_ojek_online')
                ->leftJoin('registrasi_driver_sancaka as driver', 'order_ojek_online.driver_id', '=', 'driver.id_pengguna')
                ->whereIn('order_ojek_online.status', ['completed', 'selesai'])
                ->select(
                    'order_ojek_online.order_id',
                    'order_ojek_online.tarif',
                    'order_ojek_online.driver_id',
                    'order_ojek_online.created_at',
                    'driver.nama_lengkap as driver_name'
                )
                ->orderBy('order_ojek_online.created_at', 'desc');

            // Filter jika bukan Admin
            if (!$isAdmin) {
                $query->where('order_ojek_online.driver_id', $userId);
            }

            $orders = $query->get();
            \Illuminate\Support\Facades\Log::info("[DEBUG] Ditemukan " . $orders->count() . " Transaksi.");

            // 3. Kalkulasi dan Pemrosesan Format Data
            $formattedTransactions = [];
            $todayStr = now()->toDateString();
            $yesterdayStr = now()->subDay()->toDateString();
            $thisMonthStr = now()->format('Y-m');
            $lastMonthStr = now()->subMonth()->format('Y-m');

            $txToday = 0; $txYesterday = 0; $txThisMonth = 0; $txLastMonth = 0;
            $totalFeeCollected = 0; $totalTaxCollected = 0;

            foreach ($orders as $o) {
                $tarifTotal = (float) $o->tarif;
                $dateStr = date('Y-m-d', strtotime($o->created_at));
                $monthStr = date('Y-m', strtotime($o->created_at));

                // Hitung Statistik Waktu
                if ($dateStr === $todayStr) $txToday++;
                if ($dateStr === $yesterdayStr) $txYesterday++;
                if ($monthStr === $thisMonthStr) $txThisMonth++;
                if ($monthStr === $lastMonthStr) $txLastMonth++;

                // Logika Potongan Dinamis
                $potonganAplikasi = 0;
                if ($o->driver_id == 4) {
                    $potonganAplikasi = ($adminFeeType === 'percent') ? ($tarifTotal * ($adminFeeAmount / 100)) : $adminFeeAmount;
                } else {
                    $potonganAplikasi = ($driverFeeType === 'percent') ? ($tarifTotal * ($driverFeeAmount / 100)) : $driverFeeAmount;
                }

                $potonganPajak = $tarifTotal * ($pajakPercent / 100);
                $totalPotongan = $potonganAplikasi + $potonganPajak + $biayaNominal;
                if ($totalPotongan > $tarifTotal) $totalPotongan = $tarifTotal;

                $pendapatanBersih = $tarifTotal - $totalPotongan;

                $totalFeeCollected += $potonganAplikasi + $biayaNominal;
                $totalTaxCollected += $potonganPajak;

                $formattedTransactions[] = [
                    'order_id' => $o->order_id,
                    'date' => date('d M Y H:i', strtotime($o->created_at)),
                    'driver_id' => $o->driver_id,
                    'driver_name' => $o->driver_name ?? 'Admin / Pusat',
                    'tarif_total' => $tarifTotal,
                    'potongan_aplikasi' => $potonganAplikasi,
                    'potongan_pajak' => $potonganPajak,
                    'persen_pajak' => $pajakPercent,
                    'biaya_tambahan' => $biayaNominal,
                    'keterangan_tambahan' => $biayaKet,
                    'pendapatan_bersih' => $pendapatanBersih,
                ];
            }

            $totalDrivers = DB::table('registrasi_driver_sancaka')->where('status', 'approved')->count();

            \Illuminate\Support\Facades\Log::info("[DEBUG] SUKSES! Data siap dilempar ke HP.");

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'today' => $txToday,
                        'yesterday' => $txYesterday,
                        'this_month' => $txThisMonth,
                        'last_month' => $txLastMonth,
                    ],
                    'admin_stats' => $isAdmin ? [
                        'total_drivers' => $totalDrivers,
                        'total_transactions' => count($orders),
                        'total_fee_collected' => $totalFeeCollected,
                        'total_tax_collected' => $totalTaxCollected,
                    ] : null,
                    'transactions' => $formattedTransactions
                ]
            ]);

        } catch (\Exception $e) {
            // TANGKAP ERROR DAN KIRIM LANGSUNG KE LAYAR HP!
            $errorDetail = "File: " . basename($e->getFile()) . " | Baris: " . $e->getLine() . " | Pesan: " . $e->getMessage();
            \Illuminate\Support\Facades\Log::error("[DEBUG CRASH] " . $errorDetail);

            return response()->json([
                'success' => false,
                'message' => 'Backend Crash / Terjadi kegagalan query.',
                'debug_error' => $errorDetail // <--- DIKIRIM KE REACT NATIVE
            ], 500);
        }
    }

    /**
     * POST: /api/mobile/order/komisi-fee/bulk-delete
     * Menghapus riwayat transaksi (HANYA ADMIN ID 4)
     */
    public function bulkDeleteKomisiFee(Request $request)
    {
        try {
            $user = $request->user();

            // PERBAIKAN: Gunakan fallback null coalescing
            $userId = $user->id_pengguna ?? $user->id;

            if ($userId != 4 && $user->role !== 'Admin') {
                return response()->json(['success' => false, 'message' => 'Hanya Admin yang dapat menghapus riwayat komisi.'], 403);
            }

            $ids = $request->input('ids');
            if (empty($ids) || !is_array($ids)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada ID yang dipilih.'], 400);
            }

            DB::table('order_ojek_online')->whereIn('order_id', $ids)->delete();

            \Illuminate\Support\Facades\Log::info("LOG LOG: Admin ID 4 menghapus " . count($ids) . " riwayat pesanan (Komisi).");

            return response()->json(['success' => true, 'message' => 'Riwayat berhasil dihapus.']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG LOG: Crash bulkDeleteKomisiFee: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus riwayat.'], 500);
        }
    }

     /**
     * Endpoint khusus untuk menarik data cetak resi (Sifat semi-publik: Siapapun yang punya resi bisa akses)
     */
    public function get_order_resi_detail(Request $request, $order_id)
    {
        Log::info("=== [API MAPBOX] REQUEST GET RESI DETAIL MASUK ===");

        try {
            $query = DB::table('order_ojek_online')
                ->join('Pengguna as customer', 'order_ojek_online.customer_id', '=', 'customer.id_pengguna')
                ->leftJoin('registrasi_driver_sancaka as driver', 'order_ojek_online.driver_id', '=', 'driver.id_pengguna')
                ->leftJoin('Pengguna as admin_user', 'order_ojek_online.driver_id', '=', 'admin_user.id_pengguna')
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
                    'driver.foto_motor',
                    'admin_user.latitude as admin_lat',
                    'admin_user.longitude as admin_lng'
                );

            // KUNCI KEAMANAN IDOR DIHAPUS AGAR AGEN/PENERIMA BISA TRACKING & CETAK RESI

            $order = $query->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan. Pastikan nomor resi benar.'], 404);
            }

            // Jika yang ambil order adalah Admin (ID 4)
            if ($order->driver_id == 4) {
                $order->driver_name = "Pusat Radar Sancaka";
                $order->driver_phone = "08819435180";
                $order->driver_lat = $order->admin_lat ?? -7.4025;
                $order->driver_lng = $order->admin_lng ?? 111.4558;
                $order->driver_is_online = 1;
            }

            // =========================================================================
            // MAPPING DATA KHUSUS CETAK RESI
            // =========================================================================

            $order->resi = $order->order_id;
            $order->nomor_invoice = $order->order_id;
            $order->expedition = 'Sancaka-Express-Reguler';

            // Pengirim
            $order->sender_name = $order->customer_name ?? 'Pengirim';
            $order->sender_phone = $order->customer_phone ?? '-';
            $order->sender_address = $order->origin_address ?? '-';
            $order->sender_village = '-';
            $order->sender_district = '-';
            $order->sender_regency = '-';
            $order->sender_postal_code = '-';

            // Penerima
            $order->receiver_name = 'Penerima Paket';
            $order->receiver_phone = '-';
            $order->receiver_address = $order->dest_address ?? '-';
            $order->receiver_village = '-';
            $order->receiver_district = '-';
            $order->receiver_regency = 'NGAWI';
            $order->receiver_postal_code = '-';

            // Spesifikasi Paket
            $order->weight = 1000;
            $order->item_price = 0;
            $order->insurance = false;
            $order->length = 10;
            $order->width = 10;
            $order->height = 10;
            $order->item_description = $order->catatan ?? 'Paket Sancaka Express';

            // Tarif (Konversi pasti angka/Float)
            $order->price = (float) $order->tarif;
            $order->shipping_cost = (float) $order->tarif;

            $order->created_at = \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i:s');

            // Ekstrak berat gram dari catatan (Jika ada format (1000g))
            if ($order->catatan && preg_match('/\((\d+)g\)/i', $order->catatan, $matches)) {
                $order->weight = (int) $matches[1];
            }

            return response()->json(['success' => true, 'data' => $order]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Dinamisasi Config DANA
     */
    private function applyDynamicConfig()
    {
        $settings = \App\Models\Api::pluck('value', 'key')->toArray();
        $isProduction = ($settings['dana_production_mode'] ?? '0') == '1';

        if ($isProduction) {
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.saas.dana.id',
                'services.dana.merchant_id'   => $settings['dana_prod_merchant_id'] ?? env('DANA_PROD_MERCHANT_ID'),
                'services.dana.client_id'     => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.x_partner_id'  => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.private_key'   => $settings['dana_prod_private_key'] ?? env('DANA_PROD_PRIVATE_KEY'),
                'services.dana.client_secret' => $settings['dana_prod_client_secret'] ?? env('DANA_PROD_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        } else {
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.merchant_id'   => $settings['dana_sandbox_merchant_id'] ?? env('DANA_MERCHANT_ID'),
                'services.dana.client_id'     => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.x_partner_id'  => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.private_key'   => $settings['dana_sandbox_private_key'] ?? env('DANA_PRIVATE_KEY'),
                'services.dana.client_secret' => $settings['dana_sandbox_client_secret'] ?? env('DANA_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        }
    }

    /**
     * Helper: Create Tripay Order
     */
    private function _createTripayOrderInternal($orderId, $amount, $paymentMethod, array $orderItems, $user): array
    {
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        if ($mode === 'production') {
            $baseUrl      = 'https://tripay.co.id/api/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'production');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'production');
        } else {
            $baseUrl      = 'https://tripay.co.id/api-sandbox/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox');
        }

        $payload = [
            'method'         => $paymentMethod,
            'merchant_ref'   => $orderId,
            'amount'         => $amount,
            'customer_name'  => $user->nama_lengkap ?? 'User Sancaka',
            'customer_email' => $user->email ?? ('user'.$user->id_pengguna.'@tokosancaka.com'),
            'customer_phone' => $user->no_wa ?? '081111111111',
            'order_items'    => $orderItems,
            'return_url'     => url('/'),
            'expired_time'   => time() + (24 * 60 * 60),
            'signature'      => hash_hmac('sha256', $merchantCode . $orderId . $amount, $privateKey),
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->timeout(30)->post($baseUrl, $payload);
            $responseData = $response->json();

            if (!$response->successful() || !isset($responseData['success']) || $responseData['success'] !== true) {
                return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan Tripay.'];
            }
            return $responseData;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error API Tripay: ' . $e->getMessage()];
        }
    }

    /**
     * Helper: Create DANA Checkout
     */
    private function _createDanaGatewayOrder($orderId, $amount, $user, $danaSignature)
    {
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(29)->format('Y-m-d\TH:i:sP');
        $amountValue = number_format((float)$amount, 2, '.', '');

        $path = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

        $body = [
            "partnerReferenceNo" => (string) $orderId,
            "merchantId"         => config('services.dana.merchant_id'),
            "amount"             => ["value" => $amountValue, "currency" => "IDR"],
            "validUpTo"          => $validUpTo,
            "urlParams"          => [
                ["url" => route('dana.return', ['trx_id' => $orderId]), "type" => "PAY_RETURN", "isDeeplink" => "N"],
                ["url" => url('/dana/notify'), "type" => "NOTIFICATION", "isDeeplink" => "N"]
            ],
            "payOptionDetails"   => [
                ["payMethod" => "BALANCE", "payOption" => "BALANCE", "transAmount" => ["value" => $amountValue, "currency" => "IDR"]]
            ],
            "additionalInfo"     => [
                "order"   => ["orderTitle" => substr("Order " . $orderId, 0, 64), "scenario" => "API"],
                "mcc"     => "5732",
                "envInfo" => ["sourcePlatform" => "IPG", "terminalType" => "SYSTEM", "orderTerminalType" => "APP"]
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessToken = $danaSignature->getAccessToken();
            $signature   = $danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $baseUrl     = config('services.dana.base_url');

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post($baseUrl . $path);
            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {
                $redirectUrl = $result['appLinkUrl'] ?? $result['webRedirectUrl'] ?? null;
                if (!empty($redirectUrl)) {
                    return ['success' => true, 'redirect_url' => $redirectUrl];
                }
            }

            return ['success' => false, 'message' => "Gagal dari DANA: " . ($result['responseMessage'] ?? 'Unknown Error')];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Koneksi DANA Gagal.'];
        }
    }

    /**
     * Helper: Create DANA Binding (Auto Debit)
     */
    public function _createDanaBindingOrderWidget($orderId, $amount, $userAccount, $danaSignature)
    {
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $path = '/rest/redirection/v1.0/debit/payment-host-to-host';
        $amountValue = number_format((float)$amount, 2, '.', '');

        $body = [
            "partnerReferenceNo" => (string) $orderId,
            "merchantId"         => config('services.dana.merchant_id'),
            "validUpTo"          => $validUpTo,
            "amount"             => ["value" => $amountValue, "currency" => "IDR"],
            "urlParams"          => [
                ["type" => "NOTIFICATION", "url" => url('/dana/notify')],
                ["type" => "PAY_RETURN", "url" => route('dana.return', ['trx_id' => $orderId]), "isDeeplink" => "N"]
            ],
            "payOptionDetails" => [
                ["payMethod" => "BALANCE", "payOption" => "BALANCE", "transAmount" => ["value" => $amountValue, "currency" => "IDR"]]
            ],
            "additionalInfo" => [
                "order" => [
                    "orderTitle"        => substr("Order " . $orderId, 0, 64),
                    "merchantTransType" => "01",
                    "buyer"             => ["externalUserId" => (string) $userAccount->id_pengguna, "externalUserType" => "MERCHANT_USER", "nickname" => "Customer"]
                ],
                "mcc"     => "5732",
                "envInfo" => ["sourcePlatform" => "IPG", "terminalType" => "SYSTEM", "orderTerminalType" => "WEB"]
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessTokenB2B = $danaSignature->getAccessToken();
            $signature      = $danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $baseUrl        = config('services.dana.base_url');

            $headers = [
                'Content-Type'           => 'application/json',
                'Authorization'          => 'Bearer ' . $accessTokenB2B,
                'Authorization-Customer' => 'Bearer ' . $userAccount->dana_access_token,
                'X-TIMESTAMP'            => $timestamp,
                'X-SIGNATURE'            => $signature,
                'ORIGIN'                 => config('services.dana.origin'),
                'X-PARTNER-ID'           => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID'          => (string) time() . \Illuminate\Support\Str::random(6),
                'X-DEVICE-ID'            => 'SANCAKA-APP',
                'CHANNEL-ID'             => '95221'
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post($baseUrl . $path);
            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? null;
                if (!empty($redirectUrl)) {
                    // Coba request OTT token agar bisa langsung bayar 1 klik tanpa login DANA lagi (seperti di TopUpController)
                    // ... [Sengaja disingkat untuk fokus pada URL]
                    return ['success' => true, 'redirect_url' => $redirectUrl];
                }
            }
            return ['success' => false, 'message' => "Gagal dari DANA: " . ($result['responseMessage'] ?? 'Error')];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Koneksi DANA gagal.'];
        }
    }

}
