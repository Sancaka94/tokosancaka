<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DirectAdminMailService
{
    /**
     * Membuat akun mailbox baru di DirectAdmin via CMD_API_POP
     */
    public function createMailbox(string $username, string $password): array
    {
        $host   = rtrim((string) env('DA_HOST', 'https://server4.hostdata.id:2222'), '/');
        $daUser = (string) env('DA_USERNAME');
        $daKey  = (string) env('DA_LOGIN_KEY');
        $domain = (string) env('DA_MAIL_DOMAIN', 'tokosancaka.com');
        $quota  = (int) env('DA_MAIL_QUOTA', 500); // Kuota default 500 MB per user

        $cleanUser = strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '', $username));

        if (empty($daUser) || empty($daKey)) {
            Log::error('LOG LOG: [DIRECTADMIN MAIL] DA_USERNAME atau DA_LOGIN_KEY belum diatur di .env');
            return [
                'success' => false,
                'message' => 'Konfigurasi server mail belum lengkap. Hubungi administrator.',
            ];
        }

        try {
            $response = Http::withBasicAuth($daUser, $daKey)
                ->asForm()
                ->timeout(15)
                ->post("{$host}/CMD_API_POP", [
                    'action'  => 'create',
                    'domain'  => $domain,
                    'user'    => $cleanUser,
                    'passwd'  => $password,
                    'passwd2' => $password,
                    'quota'   => $quota,
                ]);

            parse_str($response->body(), $result);

            if (($result['error'] ?? '1') === '0') {
                Log::info("LOG LOG: [DIRECTADMIN MAIL] Sukses membuat mailbox {$cleanUser}@{$domain}");
                return [
                    'success' => true,
                    'email'   => "{$cleanUser}@{$domain}",
                ];
            }

            Log::error('LOG LOG: [DIRECTADMIN MAIL GAGAL]', [
                'username' => $cleanUser,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'message' => $result['details'] ?? $result['text'] ?? 'Gagal membuat alamat email di server.',
            ];
        } catch (\Throwable $e) {
            Log::error('LOG LOG: [DIRECTADMIN MAIL EXCEPTION] ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke mail server. Silakan coba lagi.',
            ];
        }
    }
}
