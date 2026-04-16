<?php

namespace App\Http\Controllers;

use App\Models\TelegramGroup;
use App\Models\TelegramMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TelegramGroupController extends Controller
{
    // ==========================================
    // BAGIAN ADMIN (CRUD)
    // ==========================================
    public function adminView(Request $request) {
        $loggedIn = $request->session()->get('admin_logged_in', false);
        $groups = TelegramGroup::all();
        return view('telegram.admin', compact('loggedIn', 'groups'));
    }

    public function adminLogin(Request $request) {
        if ($request->username === env('ADMIN_USERNAME', 'Sancaka94') && $request->password === env('ADMIN_PASSWORD', 'Salafyyin***94')) {
            $request->session()->put('admin_logged_in', true);
            Log::info("LOG LOG: Admin berhasil login ke Panel Bot Webhook.");
            return redirect()->back()->with('success', 'Berhasil Login sebagai Admin!');
        }

        Log::warning("LOG LOG: Percobaan login Admin gagal (Kredensial salah).");
        return redirect()->back()->with('error', 'Username atau password salah!');
    }

    public function adminLogout(Request $request) {
        $request->session()->forget('admin_logged_in');
        Log::info("LOG LOG: Admin logout dari Panel.");
        return redirect()->back();
    }

    public function storeGroup(Request $request) {
        $request->validate(['nama' => 'required|string', 'link' => 'required|string']);
        TelegramGroup::create(['nama' => $request->nama, 'link' => $request->link]);

        Log::info("LOG LOG: Sumber grup baru ditambahkan ke database: {$request->nama}");
        return redirect()->back()->with('success', 'Sumber grup berhasil ditambahkan!');
    }

    public function destroyGroup($id) {
        $group = TelegramGroup::findOrFail($id);
        $nama = $group->nama;
        $group->delete();

        Log::info("LOG LOG: Sumber grup dihapus: {$nama}");
        return redirect()->back()->with('success', 'Sumber grup berhasil dihapus!');
    }

    // ==========================================
    // BAGIAN WEBHOOK (MENERIMA PESAN DARI BOT)
    // ==========================================
    public function webhook(Request $request)
    {
        Log::info("LOG LOG: 🟢 Menerima payload Webhook dari Telegram API.");

        $update = $request->all();
        $message = $update['message'] ?? $update['channel_post'] ?? null;

        if ($message) {
            $chatId = $message['chat']['id'] ?? 'Unknown';
            $chatTitle = $message['chat']['title'] ?? 'Unknown Chat';
            $messageId = $message['message_id'] ?? 0;
            $text = $message['text'] ?? $message['caption'] ?? null;

            $mediaType = null;
            $mediaFileId = null;
            $localMediaPath = null;

            Log::info("LOG LOG: Memproses pesan ID {$messageId} dari Chat/Grup: {$chatTitle}");

            if (isset($message['photo'])) {
                $mediaType = 'photo';
                $mediaFileId = end($message['photo'])['file_id'];
                Log::info("LOG LOG: Terdeteksi media (Foto) pada pesan ID {$messageId}.");
            } elseif (isset($message['video'])) {
                $mediaType = 'video';
                $mediaFileId = $message['video']['file_id'];
                Log::info("LOG LOG: Terdeteksi media (Video) pada pesan ID {$messageId}.");
            } elseif (isset($message['document'])) {
                $mediaType = 'document';
                $mediaFileId = $message['document']['file_id'];
                Log::info("LOG LOG: Terdeteksi media (Dokumen) pada pesan ID {$messageId}.");
            }

            if ($mediaFileId) {
                $botToken = env('TELEGRAM_BOT_TOKEN');

                try {
                    Log::info("LOG LOG: Meminta file_path ke Telegram untuk File ID: {$mediaFileId}");
                    $response = Http::get("https://api.telegram.org/bot{$botToken}/getFile?file_id={$mediaFileId}");

                    if ($response->successful() && isset($response->json()['result']['file_path'])) {
                        $filePath = $response->json()['result']['file_path'];
                        $telegramFileUrl = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";

                        $fileContents = Http::get($telegramFileUrl)->body();
                        $fileName = 'telegram_media/' . basename($filePath);

                        Storage::disk('public')->put($fileName, $fileContents);
                        $localMediaPath = $fileName;
                        Log::info("LOG LOG: ✅ Berhasil mendownload dan menyimpan media ke {$fileName}");
                    } else {
                        Log::error("LOG LOG: ❌ Gagal mendapatkan file_path dari Telegram.");
                    }
                } catch (\Exception $e) {
                    Log::error("LOG LOG: ❌ Terjadi error saat mendownload media: " . $e->getMessage());
                }
            }

            try {
                TelegramMessage::updateOrCreate(
                    ['message_id' => $messageId, 'chat_id' => $chatId],
                    [
                        'chat_title' => $chatTitle,
                        'text' => $text,
                        'media_type' => $mediaType,
                        'media_file_id' => $mediaFileId,
                        'local_media_path' => $localMediaPath
                    ]
                );

                Log::info("LOG LOG: ✅ Pesan dari {$chatTitle} sukses di-insert ke MySQL.");
            } catch (\Exception $e) {
                Log::error("LOG LOG: ❌ Gagal query insert/update ke MySQL. Error: " . $e->getMessage());
            }

        } else {
            Log::warning("LOG LOG: ⚠️ Payload webhook diterima tapi tidak ada object 'message' atau 'channel_post'.");
        }

        return response('OK', 200);
    }

    // ==========================================
    // BAGIAN USER (PENCARIAN DARI DATABASE MySQL)
    // ==========================================
    public function index() {
        return view('telegram.index');
    }

    public function search(Request $request)
    {
        $keyword = $request->input('q');
        if (!$keyword) return view('telegram.index');

        Log::info("LOG LOG: 🔎 Pengunjung mencoba mencari kata kunci: '{$keyword}'");

        $groups = TelegramGroup::all();
        $hasil_pencarian = [];

        $dbMessages = TelegramMessage::where('text', 'LIKE', "%{$keyword}%")
                        ->orderBy('created_at', 'desc')
                        ->limit(30)
                        ->get();

        Log::info("LOG LOG: Ditemukan " . $dbMessages->count() . " hasil di MySQL untuk '{$keyword}'.");

        foreach ($dbMessages as $msg) {
            $path_media = $msg->local_media_path ? asset('storage/' . $msg->local_media_path) : null;

            $hasil_pencarian[] = [
                'grup'       => $msg->chat_title,
                'link_grup'  => '#',
                'teks'       => $msg->text ?? '[Hanya Media]',
                'tipe_media' => $msg->media_type,
                'path_media' => $path_media,
            ];
        }

        Log::info("LOG LOG: Halaman pencarian berhasil di-render dan ditampilkan ke pengunjung.");

        return view('telegram.index', compact('keyword', 'hasil_pencarian', 'groups'));
    }
}
