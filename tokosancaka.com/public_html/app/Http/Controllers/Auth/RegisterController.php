<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistrationRequest; // ✅ DARI KODE ANDA

// 👇 DITAMBAHKAN UNTUK NOTIFIKASI ADMIN
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;


class RegisterController extends Controller
{
    /**
     * Menampilkan form registrasi.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

   public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:Pengguna,email|unique:registration_requests,email',
            'no_wa' => ['required', 'string', 'min:10', 'regex:/^(08|62|\+62)[0-9]{7,13}$/'],
            'store_nama' => 'required|string|max:255',
        ]);

        RegistrationRequest::create($validatedData);

        try {
            // 1. Notifikasi Internal (Web/Database)
            $admins = User::where('role', 'admin')->get();
            if ($admins->count() > 0) {
                $dataNotifAdmin = [
                    'tipe'        => 'Registrasi',
                    'judul'       => 'Registrasi Pengguna Baru',
                    'pesan_utama' => 'Pengguna baru telah mendaftar: ' . $validatedData['name'],
                    'url'         => route('admin.registrations.index'),
                    'icon'        => 'fas fa-user-plus',
                ];
                Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
            }

            // ==========================================================
            // 2. PUSH NOTIFIKASI KE EXPO ADMIN (TAMBAHAN BARU)
            // ==========================================================
            $adminApp = DB::table('Pengguna')->where('id_pengguna', 4)->select('fcm_token', 'fcm_token_debug')->first();

            if ($adminApp && (!empty($adminApp->fcm_token) || !empty($adminApp->fcm_token_debug))) {
                $accessToken = $this->getGoogleAccessToken();
                $projectId = 'sancaka-express'; // Sesuaikan jika ID Project Firebase Anda berbeda

                $tokensToTry = [];
                if (!empty($adminApp->fcm_token)) $tokensToTry[] = $adminApp->fcm_token;
                if (!empty($adminApp->fcm_token_debug)) $tokensToTry[] = $adminApp->fcm_token_debug;

                if ($accessToken && count($tokensToTry) > 0) {
                    foreach ($tokensToTry as $tokenStr) {
                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type'  => 'application/json',
                        ])->timeout(10)->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                            'message' => [
                                'token' => $tokenStr,
                                'android' => ['priority' => 'HIGH'],
                                'notification' => [
                                    'title' => '👤 Pendaftar Baru!',
                                    'body'  => "{$validatedData['name']} ({$validatedData['store_nama']}) baru saja mendaftar. Cek sekarang!"
                                ],
                                'data' => [
                                    'action' => 'new_registration'
                                ]
                            ]
                        ]);

                        if ($response->successful()) {
                            Log::info("LOG LOG: Push Notif Registrasi sukses terkirim ke Expo Admin.");
                            break; // Stop jika token sudah berhasil
                        }
                    }
                }
            }
            // ==========================================================

        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi registrasi baru: ' . $e->getMessage());
        }

        return redirect()->route('register')
            ->with('success', 'Permintaan pendaftaran Anda telah berhasil dikirim. Mohon tunggu persetujuan dari Admin.');
    }

    private function getGoogleAccessToken()
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $jsonKeyPath = storage_path('app/firebase-auth.json');

            if (!file_exists($jsonKeyPath)) return null;

            $keyData = json_decode(file_get_contents($jsonKeyPath), true);
            if (!$keyData || !isset($keyData['private_key'])) return null;

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

                $response = \Illuminate\Support\Facades\Http::timeout(5)->asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ]);

                if ($response->successful() && $response->json('access_token')) {
                    return $response->json('access_token');
                }
            } catch (\Throwable $th) {
                \Illuminate\Support\Facades\Log::warning("FCM Token: " . $th->getMessage());
            }

            return null;
        });
    }
}
