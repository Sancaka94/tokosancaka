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

    /**
     * Endpoint API GET: /api/mobile/driver/nearby
     * Mencari driver terdekat dalam radius 5 KM
     */
    public function getNearbyDrivers(Request $request)
    {
        try {
            $lat = $request->query('lat');
            $lng = $request->query('lng');
            $radius = 5; // Radius maksimal pencarian dalam Kilometer (KM)

            // Validasi jika lat/lng kosong
            if (!$lat || !$lng) {
                return response()->json([
                    'success' => false,
                    'message' => 'Titik kordinat asal tidak ditemukan.'
                ]);
            }

            // QUERY MENGGUNAKAN RUMUS HAVERSINE UNTUK MENGHITUNG JARAK REAL
            // Mencari driver di tabel registrasi_driver_sancaka yang statusnya 'approved'
            $drivers = DB::table('registrasi_driver_sancaka')
                ->selectRaw("id, id_pengguna, nama_lengkap, latitude, longitude, status,
                    ( 6371 * acos( cos( radians(?) ) *
                      cos( radians( latitude ) ) *
                      cos( radians( longitude ) - radians(?) ) +
                      sin( radians(?) ) *
                      sin( radians( latitude ) ) )
                    ) AS distance", [$lat, $lng, $lat])
                ->where('status', 'approved') // Wajib Approved
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance', '<=', $radius) // Batasi hanya yang jaraknya <= 5 KM
                ->orderBy('distance', 'asc') // Urutkan dari yang paling dekat
                ->limit(10) // Tampilkan maksimal 10 driver
                ->get();

            // Format datanya agar persis seperti yang diminta React Native
            $formattedDrivers = $drivers->map(function ($driver) {
                return [
                    'id'       => $driver->id,
                    'name'     => $driver->nama_lengkap,
                    'vehicle'  => 'Ojek Sancaka', // Bisa diubah jika ada field merk motor
                    'distance' => round($driver->distance, 1) . ' KM', // Dibulatkan jadi 1 angka di belakang koma
                    'lat'      => (float) $driver->latitude,
                    'lng'      => (float) $driver->longitude,
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
     * Endpoint POST: /api/mobile/order/notify-driver
     * Mengirim notifikasi berdering ke HP Driver
     */
    public function notify_driver(Request $request)
    {
        $driverId = $request->input('driver_id');

        // Cari expo_token dari driver tersebut
        $driver = DB::table('registrasi_driver_sancaka')
            ->join('Pengguna', 'registrasi_driver_sancaka.id_pengguna', '=', 'Pengguna.id_pengguna')
            ->where('registrasi_driver_sancaka.id', $driverId)
            ->select('Pengguna.expo_token', 'registrasi_driver_sancaka.nama_lengkap')
            ->first();

        if (!$driver || empty($driver->expo_token)) {
            return response()->json([
                'status' => false,
                'message' => 'Driver tidak bisa dihubungi (Token tidak ditemukan).'
            ], 404);
        }

        try {
            // INILAH TEMPAT ANDA MENARUH PAYLOAD TERSEBUT (Dikonversi ke PHP Array)
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', [
                'to'        => $driver->expo_token,
                'title'     => '🚨 ORDERAN BARU MASUK!',
                'body'      => 'Segera ambil orderan dari pelanggan Anda.',
                'sound'     => 'default', // <--- INI YANG BIKIN HP DRIVER BUNYI
                'channelId' => 'pesanan-masuk', // <--- HARUS COCOK DENGAN SETTING DI HP DRIVER
                'data'      => [
                    'action'    => 'new_order',
                    'tarif'     => $request->input('tarif'),
                    'origin'    => $request->input('origin_address'),
                    'dest'      => $request->input('dest_address'),
                ]
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Memanggil driver ' . $driver->nama_lengkap
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Gagal memanggil.'], 500);
        }
    }

}
