<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemplateWhatsAppController extends Controller
{
    protected string $baseUrl = 'https://chat.api.co.id/api/v1/public';

    /**
     * Helper untuk header request ke API.co.id
     */
    protected function getHeaders(): array
    {
        $apiKey = config('services.apicoid.api_key') ?? env('APICOID_API_KEY');

        return [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'SancakaBot/1.0 (+https://tokosancaka.com)',
        ];
    }

    /**
     * Lihat daftar template yang terdaftar di API.co.id
     * GET /api/v1/whatsapp/templates
     */
    public function listTemplates()
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->get($this->baseUrl . '/templates?limit=100');

            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil daftar template WhatsApp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke API.co.id: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buat Template WhatsApp di API.co.id
     * POST /api/v1/whatsapp/templates/create
     *
     * Catatan Meta / WhatsApp API:
     * Tombol QUICK_REPLY maksimal 3 tombol per template.
     */
    public function createTemplate(Request $request)
    {
        $templateName = $request->input('template_name', 'sancaka_pembuka');
        $category     = strtoupper($request->input('category', 'UTILITY'));
        $language     = $request->input('language', 'id');

        // Isi default pesan pembuka Sancaka
        $defaultBody = "Halo kak! Selamat datang di Layanan Resmi Sancaka.\n\n"
                     . "Kami siap melayani kebutuhan Anda:\n"
                     . "1. Sancaka Express (Jasa Pengiriman Paket)\n"
                     . "2. Sancaka Digital Printing / Percetakan\n"
                     . "3. Sancaka Legal (Perizinan & Hukum)\n"
                     . "4. Sancaka Ojek & Kurir Lokal\n\n"
                     . "Silakan pilih tombol di bawah atau balas chat ini untuk mulai konsultasi/cek ongkir:";

        $bodyText = $request->input('body', $defaultBody);

        // Catatan: WhatsApp Meta membatasi tombol QUICK_REPLY maksimal 3 tombol.
        // Pilihan tombol default:
        $defaultButtons = [
            ['type' => 'QUICK_REPLY', 'text' => 'Cek Ongkir Paket'],
            ['type' => 'QUICK_REPLY', 'text' => 'Percetakan & Cetak'],
            ['type' => 'QUICK_REPLY', 'text' => 'Layanan Lainnya'],
        ];

        $buttons = $request->input('buttons', $defaultButtons);

        // Validasi tombol jika tipe QUICK_REPLY melebihi 3
        if (is_array($buttons) && count($buttons) > 3) {
            $quickReplyCount = 0;
            foreach ($buttons as $b) {
                if (($b['type'] ?? '') === 'QUICK_REPLY') {
                    $quickReplyCount++;
                }
            }
            if ($quickReplyCount > 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp Meta membatasi maksimal 3 tombol QUICK_REPLY pada template. Silakan kurangi tombol menjadi maksimal 3.'
                ], 422);
            }
        }

        $payload = [
            'template_name' => $templateName,
            'category'      => $category,
            'language'      => $language,
            'body'          => $bodyText,
        ];

        if (!empty($buttons)) {
            $payload['buttons'] = $buttons;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->post($this->baseUrl . '/templates', $payload);

            $resData = $response->json();

            if ($response->successful()) {
                Log::info('Berhasil mendaftarkan template WhatsApp ke API.co.id:', $resData);
            } else {
                Log::error('Gagal membuat template WhatsApp di API.co.id:', [
                    'status'   => $response->status(),
                    'response' => $resData,
                    'payload'  => $payload
                ]);
            }

            return response()->json($resData, $response->status());
        } catch (\Throwable $e) {
            Log::error('Exception saat membuat template WhatsApp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hit template WhatsApp sancaka_pembuka ke nomor pelanggan
     * POST /api/v1/whatsapp/templates/send-pembuka
     */
    public function sendTemplatePembuka(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        $templateName = config('services.apicoid.template_name', env('APICOID_TEMPLATE_NAME', 'sancaka_pembuka'));
        $templateLang = config('services.apicoid.template_language', env('APICOID_TEMPLATE_LANGUAGE', 'id'));

        $payload = [
            'phone_number' => $phone,
            'channel'      => 'whatsapp',
            'message_type' => 'template',
            'template'     => [
                'name'     => $templateName,
                'language' => [
                    'code' => $templateLang,
                ],
            ],
        ];

        $phoneNumberId = config('services.apicoid.phone_number_id', env('APICOID_PHONE_NUMBER_ID'));
        if (!empty($phoneNumberId)) {
            $payload['whatsapp_phone_number_id'] = $phoneNumberId;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->post($this->baseUrl . '/messages/send', $payload);

            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim template pembuka: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }
}
