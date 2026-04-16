<?php

namespace App\Http\Controllers;

use App\Models\TelegramGroup;
use App\Models\TelegramMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Wajib di-import untuk LOG LOG
use Illuminate\Support\Facades\Http;

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

        // Telegram mengirimkan data berformat JSON
        $update = $request->all();

        // Pastikan ini adalah pesan dari channel atau grup
        $message = $update['message'] ?? $update['channel_post'] ?? null;

        if ($message) {
            $chatId = $message['chat']['id'] ?? 'Unknown';
            $chatTitle = $message['chat']['title'] ?? 'Unknown Chat';
            $messageId = $message['message_id'] ?? 0;
            $text = $message['text'] ?? $message['caption'] ?? null;

            $mediaType = null;
            $mediaFileId = null;

            Log::info("LOG LOG: Memproses pesan ID {$messageId} dari Chat/Grup: {$chatTitle}");

            // Deteksi jika ada media (Foto/Dokumen/Video)
            if (isset($message['photo'])) {
                $mediaType = 'photo';
                // Ambil ukuran foto terbesar (elemen terakhir dari array photo)
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

            try {
                // Simpan ke Database (Gunakan updateOrCreate agar jika bot restart tidak ada data ganda)
                TelegramMessage::updateOrCreate(
                    ['message_id' => $messageId, 'chat_id' => $chatId],
                    [
                        'chat_title' => $chatTitle,
                        'text' => $text,
                        'media_type' => $mediaType,
                        'media_file_id' => $mediaFileId
                    ]
                );

                Log::info("LOG LOG: ✅ Pesan dari {$chatTitle} sukses di-insert ke MySQL.");
            } catch (\Exception $e) {
                Log::error("LOG LOG: ❌ Gagal query insert/update ke MySQL. Error: " . $e->getMessage());
            }

        } else {
            Log::warning("LOG LOG: ⚠️ Payload webhook diterima tapi tidak ada object 'message' atau 'channel_post'.");
        }

        // Telegram API selalu mengharapkan respon 200 OK agar tidak mengirim ulang pesan yang sama
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

        // Mencari dari database kita sendiri (Sangat Cepat!)
        $dbMessages = TelegramMessage::where('text', 'LIKE', "%{$keyword}%")
                        ->orderBy('created_at', 'desc')
                        ->limit(30)
                        ->get();

        Log::info("LOG LOG: Ditemukan " . $dbMessages->count() . " hasil di MySQL untuk '{$keyword}'.");

        $botToken = env('TELEGRAM_BOT_TOKEN');

        if (!$botToken) {
            Log::warning("LOG LOG: ⚠️ TELEGRAM_BOT_TOKEN belum disetting di file .env!");
        }

        foreach ($dbMessages as $msg) {
            $path_media = null;

            // Jika ada media, kita minta link download langsung dari Telegram (Tanpa mendownload file fisik ke server)
            if ($msg->media_file_id && $botToken) {
                try {
                    Log::info("LOG LOG: Request URL gambar ke Telegram untuk File ID: {$msg->media_file_id}");

                    // Request untuk mendapatkan File Path ke server API Telegram
                    $response = Http::get("https://api.telegram.org/bot{$botToken}/getFile?file_id={$msg->media_file_id}");

                    if ($response->successful() && isset($response->json()['result']['file_path'])) {
                        $filePath = $response->json()['result']['file_path'];
                        // Generate URL langsung ke server Telegram CDN
                        $path_media = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";
                        Log::info("LOG LOG: ✅ Berhasil generate URL Media CDN untuk pesan ID {$msg->message_id}");
                    } else {
                        Log::error("LOG LOG: ❌ Gagal ambil file path. Response Telegram: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("LOG LOG: ❌ Terjadi error saat hit API HTTP getFile: " . $e->getMessage());
                }
            }

            $hasil_pencarian[] = [
                'grup'       => $msg->chat_title,
                'link_grup'  => '#', // Link asli
                'teks'       => $msg->text ?? '[Hanya Media]',
                'tipe_media' => $msg->media_type,
                'path_media' => $path_media,
            ];
        }

        Log::info("LOG LOG: Halaman pencarian berhasil di-render dan ditampilkan ke pengunjung.");

        return view('telegram.index', compact('keyword', 'hasil_pencarian', 'groups'));
    }
}
