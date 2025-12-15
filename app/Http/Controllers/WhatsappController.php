<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WhatsappController extends Controller
{
    // Pastikan token ada di .env dengan nama FONNTE_TOKEN
    protected $token;

    public function __construct()
    {
        $this->token = env('FONNTE_TOKEN', 'UvqpsKd6ksLjGsGe4ARn'); // Ganti default jika perlu
    }

    /**
     * 1. HALAMAN INBOX (Admin Dashboard)
     * Logika Grouping diperbaiki agar nama kontak valid.
     */
    public function index(Request $request)
    {
        // A. Ambil Daftar Kontak (Sidebar)
        $rawContacts = DB::table('whatsapp_logs')
            ->select('sender_number', DB::raw('MAX(created_at) as last_msg_time'))
            ->groupBy('sender_number')
            ->orderBy('last_msg_time', 'desc')
            ->get();

        // B. Mapping Nama Kontak (VERSI SIMPEL - TANPA CEK TABEL USER)
        $contacts = $rawContacts->map(function($contact) {
            
            // Langsung cari nama dari log WA terakhir
            $lastLog = DB::table('whatsapp_logs')
                ->where('sender_number', $contact->sender_number)
                ->where('type', 'incoming')
                ->orderBy('created_at', 'desc')
                ->first();
            
            // Fallback
            if (!$lastLog) {
                $lastLog = DB::table('whatsapp_logs')
                    ->where('sender_number', $contact->sender_number)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            $finalName = $lastLog->sender_name ?? 'Unknown';

            return (object) [
                'sender_number' => $contact->sender_number,
                'sender_name'   => $finalName,
                'last_msg_time' => $contact->last_msg_time
            ];
        });

        // C. Ambil Chat Aktif
        $activeChat = [];
        $activePhone = $request->query('phone'); 

        if ($activePhone) {
            $activeChat = WhatsappLog::where('sender_number', $activePhone)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('whatsapp.index', compact('contacts', 'activeChat', 'activePhone'));
    }

    /**
     * 2. KIRIM PESAN (Outgoing - Dari Admin Web)
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'target' => 'required',
            'message' => 'required',
        ]);

        try {
            // Menggunakan fungsi sendFonnte internal
            $response = $this->sendFonnte($request->target, [
                'message' => $request->message,
                'url' => null,
                'filename' => null,
            ]);

            if ($response['status']) {
                // Simpan ke Database
                WhatsappLog::create([
                    'sender_number' => $request->target,
                    'sender_name'   => 'Me (Admin)',
                    'message'       => $request->message,
                    'type'          => 'outgoing',
                    'status'        => 'sent'
                ]);

                return back()->with('success', 'Pesan terkirim!');
            } else {
                return back()->with('error', 'Gagal: ' . ($response['reason'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * 3. WEBHOOK (Logic Utama dari Snippet Anda)
     */
    public function webhook(Request $request)
    {
        // Log data mentah untuk debugging
        Log::info('Webhook Incoming:', $request->all());

        // Ambil Data sesuai dokumentasi Fonnte
        $device   = $request->device;
        $sender   = $request->sender;
        $message  = $request->message;
        $text     = $request->text; // Button text
        $member   = $request->member; // Group member
        $name     = $request->name;
        $location = $request->location;
        
        // Cek Group (Optional: Abaikan jika dari grup agar database tidak penuh)
        // Hapus blok if ini jika ingin bot membalas di grup juga
        if ($request->isgroup || $member) {
             return response()->json(['status' => true, 'message' => 'Group message ignored']);
        }

        // 1. SIMPAN PESAN MASUK (INCOMING) KE DATABASE
        // Penting agar muncul di Dashboard Admin
        try {
            WhatsappLog::create([
                'sender_number' => $sender,
                'sender_name'   => $name ?? 'Unknown',
                'message'       => $message ?? '(Media/Lainnya)',
                'media_url'     => $request->url ?? null,
                'type'          => 'incoming',
                'status'        => 'received'
            ]);
        } catch (\Exception $e) {
            Log::error('DB Save Error: ' . $e->getMessage());
        }

        // 2. LOGIKA AUTO REPLY (Sesuai Snippet Anda)
        $reply = null;
        $msgLower = strtolower($message); // Ubah ke huruf kecil biar fleksibel

        if ( $msgLower == "test" ) {
            $reply = [
                "message" => "working great! (Laravel Response)",
            ];
        } elseif ( $msgLower == "image" ) {
            $reply = [
                "message" => "image message",
                "url" => "https://filesamples.com/samples/image/jpg/sample_640%C3%97426.jpg",
            ];
        } elseif ( $msgLower == "audio" ) {
            $reply = [
                "message" => "audio message",
                "url" => "https://filesamples.com/samples/audio/mp3/sample3.mp3",
                "filename" => "music",
            ];
        } elseif ( $msgLower == "video" ) {
            $reply = [
                "message" => "video message",
                "url" => "https://filesamples.com/samples/video/mp4/sample_640x360.mp4",
            ];
        } elseif ( $msgLower == "file" ) {
            $reply = [
                "message" => "file message",
                "url" => "https://filesamples.com/samples/document/docx/sample3.docx",
                "filename" => "document",
            ];
        } else {
            // Default Reply jika keyword tidak dikenali
            $reply = [
                "message" => "Maaf, saya tidak mengerti. Silakan gunakan kata kunci berikut:\n\n- Test\n- Audio\n- Video\n- Image\n- File",
            ];
        }

        // 3. KIRIM BALASAN (AUTO REPLY)
        if ($reply) {
            $this->sendFonnte($sender, $reply);

            // Opsional: Simpan balasan bot ke database agar Admin tahu bot sudah menjawab
            WhatsappLog::create([
                'sender_number' => $sender,
                'sender_name'   => 'Bot AutoReply',
                'message'       => $reply['message'],
                'media_url'     => $reply['url'] ?? null,
                'type'          => 'outgoing',
                'status'        => 'sent'
            ]);
        }

        return response()->json(['status' => true]);
    }

    /**
     * FUNGSI BANTUAN: Kirim ke API Fonnte (Pengganti CURL Native)
     */
    private function sendFonnte($target, $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $data['message'],
                'url' => $data['url'] ?? null,
                'filename' => $data['filename'] ?? null,
            ]);

            return [
                'status' => $response->successful(),
                'reason' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'reason' => $e->getMessage()
            ];
        }
    }
}