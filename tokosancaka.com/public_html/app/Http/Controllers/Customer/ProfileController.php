<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Api; // <-- TAMBAHAN IMPORT
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http; // <-- TAMBAHAN IMPORT
use App\Services\KiriminAjaService;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman untuk melihat profil pengguna.
     */
    public function show(Request $request)
    {
        return view('customer.profile.show', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Menampilkan form untuk mengedit profil pengguna.
     */
    public function edit(Request $request) {
        return view('customer.profile.edit', [ 'user' => $request->user(), ]);
    }

    /**
     * Memperbarui informasi profil pengguna yang sudah login (Regular Update).
     */
    public function update(Request $request)
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'nama_lengkap'          => ['required', 'string', 'max:255'],
                'no_wa'                 => ['required', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')],
                'store_name'            => ['nullable', 'string', 'max:255'],
                'store_logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
                'bank_name'             => ['nullable', 'string', 'max:255'],
                'bank_account_name'     => ['nullable', 'string', 'max:255'],
                'bank_account_number'   => ['nullable', 'string', 'max:255'],
                'province'              => ['required', 'string', 'max:255'],
                'regency'               => ['required', 'string', 'max:255'],
                'district'              => ['required', 'string', 'max:255'],
                'district_id'           => ['required', 'integer'], // <-- WAJIB UNTUK AUTOKIRIM
                'village'               => ['required', 'string', 'max:255'],
                'postal_code'           => ['nullable', 'string', 'max:10'],
                'address_detail'        => ['required', 'string'],
                'latitude'              => ['required', 'numeric', 'between:-90,90'],
                'longitude'             => ['required', 'numeric', 'between:-180,180'],
            ], [
                'latitude.required'     => 'Latitude wajib diisi. Gunakan tombol "Cari Koordinat".',
                'longitude.required'    => 'Longitude wajib diisi. Gunakan tombol "Cari Koordinat".',
                'no_wa.unique'          => 'Nomor WhatsApp sudah digunakan oleh pengguna lain.',
            ]);

            if ($request->hasFile('store_logo')) {
                if ($user->store_logo_path) {
                    Storage::disk('public')->delete($user->store_logo_path);
                }
                $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
                $user->store_logo_path = $path;
            }

            $user->fill($validated);

            // =======================================================
            // 🔄 SINKRONISASI OTOMATIS KE API AUTOKIRIM
            // =======================================================
            $apiData = [
                'nama'        => $validated['nama_lengkap'],
                'no_hp'       => $validated['no_wa'],
                'alamat'      => $validated['address_detail'],
                'email'       => $user->email ?? '',
                'district_id' => $validated['district_id']
            ];

            // Jika User sudah punya pickup_point_code, lakukan UPDATE
            if (!empty($user->pickup_point_code)) {
                $this->updatePickupPointApi($user->pickup_point_code, $apiData);
            } else {
                // Jika belum punya, lakukan INSERT API
                    $apiResult = $this->insertPickupPointApi($apiData);
                    if (isset($apiResult['rc']) && $apiResult['rc'] === '00') {
                        $user->pickup_point_code = $apiResult['data']['pickup_point_code'];
                        $msgSuffix = " Pickup Point berhasil dibuat: " . $apiResult['data']['pickup_point_code'];
                    }
                    elseif (isset($apiResult['rc']) && $apiResult['rc'] === '01') {
                        $apiData['no_hp'] = $apiData['no_hp'] . rand(100, 999);
                        $retryResult = $this->insertPickupPointApi($apiData);

                        if (isset($retryResult['rc']) && $retryResult['rc'] === '00') {
                            $user->pickup_point_code = $retryResult['data']['pickup_point_code'];
                            $msgSuffix = " Pickup Point berhasil dibuat (Bypass): " . $retryResult['data']['pickup_point_code'];
                        } else {
                            $msgSuffix = " (Gagal sinkron Autokirim)";
                        }
                    } else {
                        $msgSuffix = " (Gagal sinkron Autokirim)";
                    }
            }
            // =======================================================

            $user->save();

            return redirect()
                ->route('customer.profile.show')
                ->with('success', 'Profil Anda berhasil diperbarui dan disinkronkan.');

        } catch (\Throwable $e) {
            Log::error('Update profile gagal: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui profil. Silakan coba lagi.');
        }
    }

    /**
     * Menampilkan Form Input OTP (Public - Belum Login)
     */
    public function showOtpForm()
    {
        if (!session()->has('otp_no_wa')) {
            return redirect()->route('login')->with('error', 'Sesi tidak valid. Silakan login atau daftar terlebih dahulu.');
        }

        return view('customer.profile.otp');
    }

   /**
     * Memproses Verifikasi Inputan OTP (Lalu login otomatis)
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string'
        ], [
            'otp.required' => 'Kode OTP wajib diisi.'
        ]);

        $noWa = session('otp_no_wa');
        $user = User::where('no_wa', $noWa)->orderBy('id_pengguna', 'desc')->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Data pendaftar tidak ditemukan.');
        }

        $inputOtp = preg_replace('/\s+/', '', $request->otp);

        if (strtoupper($user->setup_token) !== strtoupper($inputOtp)) {
            dd([
                'Penyebab Error' => 'Kode di Database BERBEDA dengan yang diketik!',
                '1. OTP_DARI_DATABASE' => $user->setup_token,
                '2. OTP_YANG_ANDA_KETIK' => $inputOtp,
                '3. ID_PENGGUNA_YANG_DICEK' => $user->id_pengguna,
                '4. NO_WA_YANG_DICEK' => $user->no_wa
            ]);
        }

        if (strtoupper($user->setup_token) === strtoupper($inputOtp)) {
            Auth::login($user);
            session()->forget('otp_no_wa');
            return redirect()->route('customer.profile.setup')->with('success', 'Verifikasi berhasil! Silakan lengkapi data profil Anda.');
        }

        return redirect()->back()->with('error', 'Kode OTP yang Anda masukkan salah.');
    }

    /**
     * Menampilkan form setup profil (Khusus User yang baru login dari OTP).
     */
    public function setup(Request $request)
    {
        $user = auth()->user();

        if ($user->status === 'Aktif') {
            return redirect()->route('customer.dashboard');
        }

        return view('customer.profile.setup', [
            'user' => $user
        ]);
    }

    /**
     * Memperbarui informasi profil dari form setup.
     */
    public function updateSetup(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'nama_lengkap'          => ['required', 'string', 'max:255'],
            'no_wa'                 => ['required', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')],
            'store_name'            => ['nullable', 'string', 'max:255'],
            'store_logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'bank_name'             => ['nullable', 'string', 'max:255'],
            'bank_account_name'     => ['nullable', 'string', 'max:255'],
            'bank_account_number'   => ['nullable', 'string', 'max:255'],
            'province'              => ['required', 'string', 'max:255'],
            'regency'               => ['required', 'string', 'max:255'],
            'district'              => ['required', 'string', 'max:255'],
            'district_id'           => ['required', 'integer'], // <-- WAJIB UNTUK AUTOKIRIM
            'village'               => ['required', 'string', 'max:255'],
            'postal_code'           => ['nullable', 'string', 'max:10'],
            'address_detail'        => ['required', 'string'],
            'latitude'              => ['required', 'numeric', 'between:-90,90'],
            'longitude'             => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitude.required'     => 'Latitude wajib diisi. Gunakan tombol "Cari Koordinat".',
            'longitude.required'    => 'Longitude wajib diisi. Gunakan tombol "Cari Koordinat".',
            'no_wa.unique'          => 'Nomor WhatsApp sudah digunakan oleh pengguna lain.',
        ]);

        if ($request->hasFile('store_logo')) {
            if ($user->store_logo_path) {
                Storage::disk('public')->delete($user->store_logo_path);
            }
            $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
            $user->store_logo_path = $path;
        }

        $user->fill($validated);
        $user->profile_setup_at = Carbon::now();
        $user->status = 'Aktif';
        $user->setup_token = null;

        // =======================================================
        // 🔄 SINKRONISASI OTOMATIS KE API AUTOKIRIM SAAT SETUP
        // =======================================================
        $apiData = [
            'nama'        => $validated['nama_lengkap'],
            'no_hp'       => $validated['no_wa'],
            'alamat'      => $validated['address_detail'],
            'email'       => $user->email ?? '',
            'district_id' => $validated['district_id']
        ];

        if (!empty($user->pickup_point_code)) {
            $this->updatePickupPointApi($user->pickup_point_code, $apiData);
        } else {
            $apiResult = $this->insertPickupPointApi($apiData);
            if (isset($apiResult['rc']) && $apiResult['rc'] === '00') {
                $user->pickup_point_code = $apiResult['data']['pickup_point_code'];
            }
        }
        // =======================================================

        $user->save();

        return redirect()->route('customer.dashboard')
                         ->with('success', 'Aktivasi dan Profil Anda berhasil diselesaikan! Selamat datang di aplikasi Sancaka Express.');
    }

    /**
     * API KiriminAja Address Search
     */
    /* public function searchKiriminAjaAddress(Request $request, KiriminAjaService $kiriminAja)
    {
        $query = $request->get('q');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            $apiResponse = $kiriminAja->searchAddress($query);

            if (isset($apiResponse['data']) && !empty($apiResponse['data'])) {
                $processedData = collect($apiResponse['data'])->map(function ($item) {
                    $addressParts = array_map('trim', explode(',', $item['full_address'] ?? ''));

                    return [
                        'province' => $addressParts[3] ?? 'N/A',
                        'regency' => $addressParts[2] ?? 'N/A',
                        'district' => $addressParts[1] ?? 'N/A',
                        'village' => $addressParts[0] ?? 'N/A',
                        'postal_code' => $addressParts[4] ?? 'N/A',
                        'full_address_display' => $item['full_address'] ?? 'Alamat Tidak Terstruktur',
                    ];
                });

                return response()->json($processedData);
            }

            return response()->json([]);

        } catch (\Exception $e) {
            Log::error("KiriminAja API Error in ProfileController: " . $e->getMessage());
            return response()->json([], 500);
        }
    } */

    /**
     * Menampilkan form permohonan penghapusan akun (Public)
     */
    public function showDeleteAccountForm()
    {
        return view('customer.profile.request_delete');
    }

    /**
     * Memproses permohonan penghapusan akun
     */
    public function submitDeleteAccountRequest(Request $request)
    {
        $validated = $request->validate([
            'email'        => ['required', 'email', 'max:255'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'alasan'       => ['nullable', 'string', 'max:1000'],
        ], [
            'email.required'        => 'Email akun wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
        ]);

        try {
            Log::info('PERMOHONAN PENGHAPUSAN AKUN DITERIMA:', [
                'email'        => $validated['email'],
                'nama_lengkap' => $validated['nama_lengkap'],
                'alasan'       => $validated['alasan'] ?? 'Tidak ada alasan yang diberikan',
                'ip_address'   => $request->ip(),
                'waktu'        => Carbon::now()->toDateTimeString(),
            ]);

            return redirect()->back()->with('success', 'Permohonan penghapusan akun berhasil dikirim. Tim Sancaka Express akan segera menghubungi Anda melalui Email/WhatsApp untuk proses verifikasi.');

        } catch (\Throwable $e) {
            Log::error('Gagal memproses permohonan hapus akun: '.$e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');
        }
    }

    // =======================================================
    // HELPER API AUTOKIRIM (CONFIG, INSERT, UPDATE, FIND, DELETE)
    // =======================================================

    private function getAutokirimConfig()
    {
        $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
        $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, ($mode === 'production' ? 'https://api.autokirim.com' : 'https://api-dev.autokirim.com'));
        $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

        return (object) [
            'mode'     => strtoupper($mode),
            'base_url' => rtrim($baseUrl, '/'),
            'token'    => $token
        ];
    }

    private function insertPickupPointApi($data)
    {
        $config = $this->getAutokirimConfig();

        try {
            $payload = [
                "name"              => (string) $data['nama'],
                "phone"             => (string) $data['no_hp'],
                "address"           => (string) $data['alamat'],
                "email"             => (string) ($data['email'] ?? ''),
                "longitude"         => "",
                "latitude"          => "",
                "district_id"       => (int) $data['district_id'],
                "is_member_deposit" => false
            ];

            Log::info("LOG LOG: [USER - API INSERT] REQUEST:", $payload);

            $response = Http::timeout(15)
                ->withToken($config->token)
                ->post("{$config->base_url}/api/pickup-point/insert", $payload);

            $result = $response->json();
            Log::info("LOG LOG: [USER - API INSERT] RESPONSE:", $result ?? []);

            return $result;
        } catch (\Exception $e) {
            Log::error("LOG LOG: [USER - API INSERT] ERROR: " . $e->getMessage());
            return ['rc' => '500', 'rd' => 'Error: ' . $e->getMessage()];
        }
    }

    private function updatePickupPointApi($pickupCode, $data)
    {
        $config = $this->getAutokirimConfig();

        try {
            $payload = [
                "name"              => (string) $data['nama'],
                "phone"             => (string) $data['no_hp'],
                "district_id"       => (int) $data['district_id'],
                "address"           => (string) $data['alamat'],
                "longitude"         => "",
                "latitude"          => "",
                "pickup_point_code" => (string) $pickupCode
            ];

            Log::info("LOG LOG: [USER - API UPDATE] REQUEST:", $payload);

            $response = Http::timeout(15)
                ->withToken($config->token)
                ->post("{$config->base_url}/api/pickup-point/update", $payload);

            $result = $response->json();
            Log::info("LOG LOG: [USER - API UPDATE] RESPONSE:", $result ?? []);

            return ($response->successful() && isset($result['rc']) && $result['rc'] === '00');
        } catch (\Exception $e) {
            Log::error("LOG LOG: [USER - API UPDATE] ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function findPickupPointApi($pickupCode)
    {
        $config = $this->getAutokirimConfig();

        try {
            $payload = [
                "pickup_point_code" => (string) $pickupCode
            ];

            $response = Http::timeout(10)
                ->withToken($config->token)
                ->post("{$config->base_url}/api/pickup-point/find", $payload);

            $result = $response->json();
            return ($response->successful() && isset($result['rc']) && $result['rc'] === '00');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deletePickupPointApi($pickupCode)
    {
        $config = $this->getAutokirimConfig();

        try {
            $payload = [
                "pickup_point_code" => (string) $pickupCode
            ];

            $response = Http::timeout(10)
                ->withToken($config->token)
                ->post("{$config->base_url}/api/pickup-point/delete", $payload);

            $result = $response->json();
            return ($response->successful() && isset($result['rc']) && $result['rc'] === '00');
        } catch (\Exception $e) {
            return false;
        }
    }

    // =======================================================

    /**
     * Mengambil data saldo terbaru untuk auto-refresh via AJAX
     */
    public function cekSaldoAjax()
    {
        $user = auth()->user();

        if($user) {
            return response()->json([
                'success' => true,
                'saldo_format' => 'Rp ' . number_format($user->saldo ?? 0, 0, ',', '.')
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    /**
     * API KiriminAja Address Search + JOIN dengan DB Autokirim
     */
    public function searchKiriminAjaAddress(Request $request, KiriminAjaService $kiriminAja)
    {
        $query = $request->get('q');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            // 1. Tembak API KiriminAja
            $apiResponse = $kiriminAja->searchAddress($query);

            if (isset($apiResponse['data']) && !empty($apiResponse['data'])) {

                // 2. Format dan Join dengan Database Lokal
                $processedData = collect($apiResponse['data'])->map(function ($item) {
                    $addressParts = array_map('trim', explode(',', $item['full_address'] ?? ''));

                    $village  = $addressParts[0] ?? 'N/A';
                    $district = $addressParts[1] ?? 'N/A'; // Nama Kecamatan
                    $regency  = $addressParts[2] ?? 'N/A'; // Nama Kabupaten/Kota
                    $province = $addressParts[3] ?? 'N/A';
                    $zip      = $addressParts[4] ?? 'N/A';

                    // 3. Cari district_id di tabel auto_kirims berdasarkan Kecamatan & Kabupaten
                    // Menghilangkan kata 'KABUPATEN'/'KOTA' jika ada selisih penulisan
                    $cleanRegency = str_replace(['KABUPATEN ', 'KOTA '], '', strtoupper($regency));

                    $autoKirimDb = \Illuminate\Support\Facades\DB::table('auto_kirims')
                        ->select('district_id')
                        ->where('district_name', 'LIKE', '%' . $district . '%')
                        ->where('regency_name', 'LIKE', '%' . $cleanRegency . '%')
                        ->first();

                    return [
                        'province'    => $province,
                        'regency'     => $regency,
                        'district'    => $district,
                        'village'     => $village,
                        'postal_code' => $zip,
                        'district_id' => $autoKirimDb ? $autoKirimDb->district_id : null, // HASIL JOIN DISINI
                        'full_address_display' => $item['full_address'] ?? 'Alamat Tidak Terstruktur',
                    ];
                });

                return response()->json($processedData);
            }

            return response()->json([]);

        } catch (\Exception $e) {
            Log::error("LOG LOG KiriminAja x Autokirim API Error: " . $e->getMessage());
            return response()->json([], 500);
        }
    }
}
