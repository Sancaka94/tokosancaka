<?php

namespace App\Http\Controllers;

use App\Models\TelegramGroup;
use App\Models\TelegramMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            return redirect()->back()->with('success', 'Berhasil Login sebagai Admin!');
        }
        return redirect()->back()->with('error', 'Username atau password salah!');
    }

    public function adminLogout(Request $request) {
        $request->session()->forget('admin_logged_in');
        return redirect()->back();
    }

    public function storeGroup(Request $request) {
        $request->validate(['nama' => 'required|string', 'link' => 'required|string']);
        TelegramGroup::create(['nama' => $request->nama, 'link' => $request->link]);
        return redirect()->back()->with('success', 'Sumber grup berhasil ditambahkan!');
    }

    public function destroyGroup($id) {
        TelegramGroup::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Sumber grup berhasil dihapus!');
    }

    // ==========================================
    // BAGIAN WEBHOOK (MENERIMA PESAN DARI BOT)
    // ==========================================
    public function webhook(Request $request)
    {
        // Telegram mengirimkan data berformat JSON
        $update = $request->all();

        // Pastikan ini adalah pesan channel/grup
        $message = $update['message'] ?? $update['channel_post'] ?? null;

        if ($message) {
            $chatId = $message['chat']['id'];
            $chatTitle = $message['chat']['title'] ?? 'Unknown Chat';
            $messageId = $message['message_id'];
            $text = $message['text'] ?? $message['caption'] ?? null;

            $mediaType = null;
            $mediaFileId = null;

            // Deteksi jika ada media (Foto/Dokumen/Video)
            if (isset($message['photo'])) {
                $mediaType = 'photo';
                // Ambil ukuran foto terbesar (elemen terakhir dari array photo)
                $mediaFileId = end($message['photo'])['file_id'];
            } elseif (isset($message['video'])) {
                $mediaType = 'video';
                $mediaFileId = $message['video']['file_id'];
            } elseif (isset($message['document'])) {
                $mediaType = 'document';
                $mediaFileId = $message['document']['file_id'];
            }

            // Simpan ke Database (Update jika sudah ada)
            TelegramMessage::updateOrCreate(
                ['message_id' => $messageId, 'chat_id' => $chatId],
                [
                    'chat_title' => $chatTitle,
                    'text' => $text,
                    'media_type' => $mediaType,
                    'media_file_id' => $mediaFileId
                ]
            );

            Log::info("LOG LOG: Pesan baru dari {$chatTitle} disimpan ke database.");
        }

        // Telegram API selalu mengharapkan respon 200 OK agar tidak mengirim ulang
        return response('OK', 200);
    }

    // ==========================================
    // BAGIAN USER (PENCARIAN DARI DATABASE)
    // ==========================================
    public function index() {
        return view('telegram.index');
    }

    public function search(Request $request)
    {
        $keyword = $request->input('q');
        if (!$keyword) return view('telegram.index');

        $groups = TelegramGroup::all();
        $hasil_pencarian = [];

        // Mencari dari database kita sendiri (Sangat Cepat!)
        $dbMessages = TelegramMessage::where('text', 'LIKE', "%{$keyword}%")
                        ->orderBy('created_at', 'desc')
                        ->limit(30)
                        ->get();

        $botToken = env('TELEGRAM_BOT_TOKEN');

        foreach ($dbMessages as $msg) {
            $path_media = null;

            // Jika ada media, kita minta link download langsung dari Telegram (Tanpa mendownload ke server kita)
            if ($msg->media_file_id && $botToken) {
                // Request untuk mendapatkan File Path
                $response = Http::get("https://api.telegram.org/bot{$botToken}/getFile?file_id={$msg->media_file_id}");
                if ($response->successful() && isset($response->json()['result']['file_path'])) {
                    $filePath = $response->json()['result']['file_path'];
                    // Generate URL langsung ke server Telegram
                    $path_media = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";
                }
            }

            $hasil_pencarian[] = [
                'grup'       => $msg->chat_title,
                'link_grup'  => '#', // Link asli bisa dikonstruksi jika chat adalah channel publik (misal: t.me/nama_channel/msg_id)
                'teks'       => $msg->text ?? '[Hanya Media]',
                'tipe_media' => $msg->media_type,
                'path_media' => $path_media,
            ];
        }

        return view('telegram.index', compact('keyword', 'hasil_pencarian', 'groups'));
    }
}
