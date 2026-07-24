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
        try {
            $lat = (float) $request->query('lat');
            $lng = (float) $request->query('lng');
            $radius = 5; // Radius driver biasa (5 KM)

            if (!$lat || !$lng) return response()->json(['success' => false, 'message' => 'Kordinat tidak ditemukan.']);

            $user = $request->user();
            $passengerGender = $user->jenis_kelamin;
            if (empty($passengerGender)) {
                return response()->json(['success' => false, 'message' => 'Lengkapi Jenis Kelamin di profil Anda.'], 400);
            }

           // ==========================================================
            // 1. TARIK DARI REDIS GEOSPATIAL (DRIVER BIASA MAKSIMAL 5 KM)
            // ==========================================================
            $nearbyRaw = Redis::georadius('active_drivers', $lng, $lat, $radius, 'km', ['WITHDIST', 'ASC']);
            $formattedDrivers = [];

            if (!empty($nearbyRaw)) {
                // 🛠️ PERBAIKAN N+1 REDIS: Gunakan Pipeline agar tarikan massal jadi 1 Query
                $pipeline = Redis::pipeline();
                $driverDistances = []; // Menyimpan jarak untuk dicocokkan nanti

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

                    if ($dId) {
                        // Daftarkan perintah ke Pipeline (belum dieksekusi)
                        $pipeline->hgetall("driver_meta:{$dId}");
                        // Simpan jarak ke memori array PHP dengan ID sebagai Key
                        $driverDistances[$dId] = $dist;
                    }
                }

                // Eksekusi SEMUA perintah hgetall sekaligus (Tembak Redis HANYA 1 KALI)
                $metaResults = $pipeline->execute();

                // Proses hasil dari Pipeline
                foreach ($metaResults as $meta) {
                    // Filter Syariah
                    if (!empty($meta) && isset($meta['id_pengguna']) && isset($meta['gender']) && $meta['gender'] === $passengerGender) {
                        $dId = $meta['id_pengguna'];
                        $dist = $driverDistances[$dId] ?? 0;

                        $formattedDrivers[] = [
                            'id' => (int) ($meta['id'] ?? $dId),
                            'id_pengguna' => (int) $dId,
                            'name' => $meta['name'] ?? 'Driver Sancaka',
                            'vehicle' => $meta['vehicle'] ?? 'Ojek Sancaka',
                            'distance' => round($dist, 1) . ' KM',
                            'distance_raw' => $dist,
                            'lat' => (float) ($meta['lat'] ?? 0),
                            'lng' => (float) ($meta['lng'] ?? 0),
                            'is_online' => true
                        ];
                    }
                }
            }

            // ==========================================================
            // 2. RADAR SUPER ADMIN (BISA MENDETEKSI USER HINGGA 80 KM)
            // ==========================================================
            $adminRadarRadius = 80; // Admin punya radius 80 KM!

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

            // Evaluasi Syariah Admin
            $isAdminSyariahPass = true;
            if ($admin && !empty($admin->jenis_kelamin)) {
                if ($admin->jenis_kelamin !== $passengerGender) {
                    $isAdminSyariahPass = false;
                }
            }

            // Masukkan admin jika masih dalam 100 KM dan Online
            if ($admin && $admin->distance <= $adminRadarRadius && $isAdminSyariahPass) {
                $isAdminOnline = false;
                if (!empty($admin->last_seen)) {
                    $lastSeenTime = \Carbon\Carbon::parse($admin->last_seen);
                    if ($lastSeenTime->diffInMinutes(now()) <= 3) {
                        $isAdminOnline = true;
                    }
                }

                if ($isAdminOnline) {
                    $adminSudahAda = array_filter($formattedDrivers, function($d) {
                        return $d['id_pengguna'] == 4;
                    });

                    if (empty($adminSudahAda)) {
                        $formattedDrivers[] = [
                            'id'           => 4,
                            'id_pengguna'  => 4,
                            'name'         => 'Pusat Radar Sancaka (Admin)',
                            'vehicle'      => 'Sancaka Express',
                            'distance'     => round($admin->distance, 1) . ' KM',
                            'distance_raw' => (float) $admin->distance, // Ini bisa 30 KM!
                            'lat'          => (float) $admin->latitude,
                            'lng'          => (float) $admin->longitude,
                            'is_online'    => true
                        ];
                    }
                }
            }

            // ==========================================================
            // 3. URUTKAN SEMUANYA DARI YANG PALING DEKAT
            // ==========================================================
            usort($formattedDrivers, function($a, $b) {
                return $a['distance_raw'] <=> $b['distance_raw'];
            });

            return response()->json(['success' => true, 'data' => $formattedDrivers]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal sistem: ' . $e->getMessage()], 500);
        }
    }


    public function notify_driver(Request $request)
    {
        Log::info("=== [API MAPBOX] REQUEST NOTIFY DRIVER / ADMIN (ORDER BARU) MASUK ===");
        Log::info("LOG LOG: Payload dari HP Pelanggan: ", $request->all());

        $customerLat = $request->input('origin_lat');
        $customerLng = $request->input('origin_lng');
        $layanan     = $request->input('layanan', 'ojek_online');
        $driverId    = $request->input('driver_id'); // 🔥 TANGKAP ID DRIVER DARI HP

        $customer = $request->user();

        // Cek data apa yang sebenarnya dibaca oleh Laravel
        $debugData = DB::table('order_ojek_online')->get();
        Log::info("DEBUG ISI TABEL: ", $debugData->toArray());
        Log::info("DEBUG ID USER: " . ($customer->id_pengguna ?? $customer->id));

        // ==========================================================
        // 🛡️ [PERISAI 1]: ANTI SPAM ORDER FIKTIF BERUNTUN
        // ==========================================================
        $cekOrderAktif = DB::table('order_ojek_online')
            ->where('customer_id', $customer->id_pengguna ?? $customer->id)
            ->whereIn('status', ['pending', 'accepted', 'otw_jemput', 'otw_antar'])
            ->exists();

        if ($cekOrderAktif) {
            Log::warning("LOG LOG: ⛔ SPAM DETECTED! User ID {$customer->id_pengguna} mencoba membuat order fiktif berlapis.");
            return response()->json([
                'status' => false,
                'message' => 'Anda masih memiliki pesanan yang sedang berlangsung. Selesaikan atau batalkan terlebih dahulu!'
            ], 403);
        }

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
        // VALIDASI SALDO PENUMPANG (Kode asli Anda lanjut di bawah sini)
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
        if ($driverId == 4) {
            $driver = DB::table('Pengguna')
                ->where('id_pengguna', 4)
                ->select('fcm_token', 'fcm_token_debug', 'nama_lengkap', 'latitude', 'longitude', 'id_pengguna as driver_user_id')
                ->first();

            // Set default koordinat admin (misal: pusat Ngawi) agar jarak tidak error
            if ($driver && !$driver->latitude) {
                $driver->latitude = -7.4025;
                $driver->longitude = 111.4558;
            }
        } else {
            $driver = DB::table('registrasi_driver_sancaka')
                ->join('Pengguna', 'registrasi_driver_sancaka.id_pengguna', '=', 'Pengguna.id_pengguna')
                ->where('registrasi_driver_sancaka.id', $driverId)
                ->select(
                    'Pengguna.fcm_token',
                    'Pengguna.fcm_token_debug',
                    'registrasi_driver_sancaka.nama_lengkap',
                    'registrasi_driver_sancaka.latitude',
                    'registrasi_driver_sancaka.longitude',
                    'registrasi_driver_sancaka.id_pengguna as driver_user_id'
                )
                ->first();
        }

        // CEK TOKEN FCM
        if (!$driver || (empty($driver->fcm_token) && empty($driver->fcm_token_debug))) {
            Log::warning("LOG LOG: Target Offline atau FCM Kosong untuk Driver ID: {$driverId}.");
            return response()->json(['status' => false, 'message' => 'Driver/Admin belum mengaktifkan notifikasi.'], 404);
        }

        $jarakKePemesanMeter = $this->getDistanceMeter(
            (float)$driver->latitude, (float)$driver->longitude,
            (float)$customerLat, (float)$customerLng
        );

        // GENERATE ORDER ID
        $orderId = $orderPrefix . strtoupper(uniqid());
        Log::info("LOG LOG: Order ID di-generate: " . $orderId);

        try {
            DB::table('order_ojek_online')->insert([
                'order_id'          => $orderId,
                'customer_id'       => $customer->id_pengguna,
                'driver_id'         => $driver->driver_user_id, // Masuk sesuai ID target yang dilempar dari HP
                'origin_lat'        => $customerLat,
                'origin_lng'        => $customerLng,
                'origin_address'    => $request->input('origin_address', 'Lokasi Jemput'),
                'dest_lat'          => $request->input('dest_lat'),
                'dest_lng'          => $request->input('dest_lng'),
                'dest_address'      => $request->input('dest_address', 'Tujuan Antar'),
                'jarak_km'          => (float) $request->input('jarak_km', 0),
                'waktu_menit'       => (int) $request->input('waktu_menit', 0),
                'tarif'             => (float) $tarif,
                'metode_pembayaran' => $metodePembayaran,
                'catatan'           => $request->input('catatan', null),
                'status'            => 'pending',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            Log::info("LOG LOG: Sukses Insert ke Database MySQL!");

            // ==========================================================
            // 🔥 TAMBAHAN REDIS: Simpan Order Sementara (Auto Expire 30 Menit)
            // ==========================================================
            try {
                $orderDataRedis = [
                    'order_id'    => $orderId,
                    'customer_id' => $customer->id_pengguna,
                    'driver_id'   => $driver->driver_user_id,
                    'status'      => 'pending',
                    'layanan'     => $layanan,
                    'tarif'       => $tarif
                ];
                // 1800 detik = 30 menit. Jika 30 menit tidak diapa-apakan, auto hapus dari memori!
                Redis::setex("order_active:{$orderId}", 1800, json_encode($orderDataRedis));
                Log::info("LOG LOG: Sukses simpan order {$orderId} ke Redis (Expire 30 Menit)");
            } catch (\Exception $e) {
                Log::warning("LOG LOG: Gagal simpan ke Redis: " . $e->getMessage());
            }

            // FIREBASE RTDB PUSH
            try {
                $firebaseDbUrl = "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app/incoming_orders/{$driver->driver_user_id}/{$orderId}.json";
                $fbResponse = Http::put($firebaseDbUrl, [
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

            $tokensToTry = [];
            if (!empty($driver->fcm_token)) $tokensToTry[] = ['mode' => 'PRODUCTION', 'token' => $driver->fcm_token];
            if (!empty($driver->fcm_token_debug)) $tokensToTry[] = ['mode' => 'DEBUG', 'token' => $driver->fcm_token_debug];

            if ($accessToken && count($tokensToTry) > 0) {
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
                        Log::info("LOG LOG: SUKSES! Notif terkirim ke Target menggunakan Token {$mode}.");
                        break;
                    }
                }
            }

            return response()->json(['status' => true, 'message' => 'Pesanan berhasil dikirim ke Admin/Kurir.', 'order_id' => $orderId]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: CRASH Insert DB / Notif! Pesan: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal membuat pesanan di server.'], 500);
        }
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

            // HAPUS DARI FIREBASE RTDB AGAR HILANG DARI DASHBOARD
            try {
                $firebaseDbUrl = "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app/incoming_orders/{$driverUser->id_pengguna}/{$orderId}.json";
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
            $transactionResult = DB::transaction(function () use ($orderId, $newStatus, $driverUser) {

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

                // 2. UPDATE STATUS DI DATABASE
                $queryUpdate = DB::table('order_ojek_online')->where('order_id', $orderId);

                // Kunci Pengaman: Jika BUKAN Admin (Bukan ID 4 dan Bukan role Admin), wajib cocokkan driver_id
                if ($driverUser->id_pengguna != 4 && ($driverUser->role ?? '') !== 'Admin') {
                    $queryUpdate->where('driver_id', $driverUser->id_pengguna);
                }

                $affected = $queryUpdate->update([
                    'status'     => $newStatus,
                    'updated_at' => now()
                ]);

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
            // LOG PERTAMA: Bukti bahwa endpoint berhasil tertembak
            \Illuminate\Support\Facades\Log::info("LOG LOG: 🎯 [ENDPOINT TERTEMBAK] Request FCM masuk! Header Auth: " . $request->header('Authorization'));
            \Illuminate\Support\Facades\Log::info("LOG LOG: Payload Body: ", $request->all());

            // Pengecekan Autentikasi Manual (karena kita di luar middleware)
            $user = auth('sanctum')->user();

            if (!$user) {
                \Illuminate\Support\Facades\Log::warning("LOG LOG: ⛔ [FCM DITOLAK] Token Sanctum tidak valid atau kosong.");
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized / User tidak ditemukan.'
                ], 401);
            }

            $userId = $user->id_pengguna ?? $user->id;
            $isDebug = filter_var($request->input('is_debug', false), FILTER_VALIDATE_BOOLEAN);
            $kolomTarget = $isDebug ? 'fcm_token_debug' : 'fcm_token';

            \Illuminate\Support\Facades\Log::info("LOG LOG: 🔥 [FCM MASUK] User ID: {$userId} | Mode: " . ($isDebug ? 'DEBUG' : 'PRODUCTION') . " | Token: " . substr($request->fcm_token, 0, 15) . "...");

            \Illuminate\Support\Facades\DB::table('Pengguna')
                ->where('id_pengguna', $userId)
                ->update([
                    $kolomTarget => $request->fcm_token
                ]);

            return response()->json([
                'success' => true,
                'message' => "FCM Token berhasil disimpan ke kolom {$kolomTarget}"
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LOG LOG: 💥 [FCM CRASH] Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan FCM token.'
            ], 500);
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

}
