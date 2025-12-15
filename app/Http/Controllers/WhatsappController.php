<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappLog; // Pastikan Model ini sudah dibuat
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    // Token sebaiknya ditaruh di .env dengan nama FONNTE_TOKEN
    protected $token;

    public function __construct()
    {
        $this->token = env('FONNTE_TOKEN', 'ISI_TOKEN_FONNTE_ANDA_DISINI');
    }

    /**
     * 1. HALAMAN INBOX (Tampilan Chat)
     */
    public function index(Request $request)
    {
        // A. Ambil Daftar Kontak (Sidebar)
        // Grouping berdasarkan nomor pengirim untuk mendapatkan list unik
        // Menggunakan logika MAX(created_at) agar kontak yang baru chat naik ke atas
        $contacts = DB::table('whatsapp_logs')
            ->select('sender_number', 'sender_name', DB::raw('MAX(created_at) as last_msg_time'))
            ->groupBy('sender_number', 'sender_name')
            ->orderBy('last_msg_time', 'desc')
            ->get();

        // B. Ambil Chat Aktif (Area Kanan)
        $activeChat = [];
        $activePhone = $request->query('phone'); // ?phone=0812xxx

        if ($activePhone) {
            $activeChat = WhatsappLog::where('sender_number', $activePhone)
                ->orderBy('created_at', 'asc') // Chat lama di atas, baru di bawah
                ->get();
        }

        return view('whatsapp.index', compact('contacts', 'activeChat', 'activePhone'));
    }

    /**
     * 2. KIRIM PESAN (Outgoing - Dari Admin ke User)
     */
    public function sendMessage(Request $request)
    {
        // Validasi input
        $request->validate([
            'target' => 'required',
            'message' => 'required',
        ]);

        try {
            // Hit API Fonnte
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $request->target,
                'message' => $request->message,
                'countryCode' => '62', // Default Indonesia
            ]);

            // Jika sukses terkirim ke server Fonnte
            if ($response->successful()) {
                $result = $response->json();
                
                // Simpan ke Database sebagai 'outgoing'
                WhatsappLog::create([
                    'sender_number' => $request->target,
                    'sender_name'   => 'Me (Admin)',
                    'message'       => $request->message,
                    'type'          => 'outgoing',
                    'status'        => 'sent'
                ]);

                return back()->with('success', 'Pesan berhasil dikirim!');
            } else {
                return back()->with('error', 'Gagal mengirim ke Fonnte: ' . $response->body());
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error System: ' . $e->getMessage());
        }
    }

    /**
     * 3. WEBHOOK (Incoming - Pesan Masuk dari Fonnte)
     * Pastikan URL ini dipasang di Dashboard Fonnte (POST)
     */
    public function webhook(Request $request)
    {
        // Log untuk debugging (cek di storage/logs/laravel.log)
        Log::info('Webhook Incoming:', $request->all());

        // Ambil Data dari Fonnte
        $sender  = $request->sender;
        $message = $request->message;
        $name    = $request->name;     // Nama pengirim
        $url     = $request->url;      // URL media (jika ada gambar/file)
        $filename= $request->filename; // Nama file (jika ada)

        if (!$sender) {
            return response()->json(['status' => false, 'reason' => 'Invalid Data'], 400);
        }

        try {
            // A. Simpan Pesan Masuk ke Database
            WhatsappLog::create([
                'sender_number' => $sender,
                'sender_name'   => $name ?? 'Unknown',
                'message'       => $message,     // Bisa null jika cuma kirim file
                'media_url'     => $url ?? null, // Simpan URL jika ada
                'type'          => 'incoming',
                'status'        => 'received'
            ]);

            // B. Auto Reply Logic (Sesuai kode native PHP Anda)
            // Cek isi pesan, ubah ke huruf kecil semua
            $msg = strtolower($message);

            if ($msg == "test") {
                $this->replyAuto($sender, "Working great! (Laravel Bot)");
            } 
            elseif ($msg == "image") {
                $this->replyAuto($sender, "Here is your image", "https://filesamples.com/samples/image/jpg/sample_640%C3%97426.jpg", "sample.jpg");
            } 
            elseif ($msg == "audio") {
                $this->replyAuto($sender, "Here is your audio", "https://filesamples.com/samples/audio/mp3/sample3.mp3", "music.mp3");
            } 
            elseif ($msg == "video") {
                $this->replyAuto($sender, "Here is your video", "https://filesamples.com/samples/video/mp4/sample_640x360.mp4");
            } 
            elseif ($msg == "file") {
                $this->replyAuto($sender, "Here is your document", "https://filesamples.com/samples/document/docx/sample3.docx", "document.docx");
            }
            // Jika Anda ingin pesan default (else)
            /*
            else {
                $this->replyAuto($sender, "Halo, saya tidak mengerti. Ketik: Test, Image, Audio, Video, atau File.");
            }
            */

            return response()->json(['status' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook Failed: ' . $e->getMessage());
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper Function: Untuk kirim balasan otomatis (Auto Reply)
     * Mendukung Teks dan Media (URL)
     */
    private function replyAuto($target, $message, $url = null, $filename = null)
    {
        $data = [
            'target' => $target,
            'message' => $message,
            'countryCode' => '62',
        ];

        // Jika ada URL (file/gambar), tambahkan ke payload
        if ($url) {
            $data['url'] = $url;
        }
        if ($filename) {
            $data['filename'] = $filename;
        }

        // Kirim request ke Fonnte
        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])->post('https://api.fonnte.com/send', $data);
        
        // Opsional: Simpan log balasan bot ke database agar terlihat di Inbox Admin
        if($response->successful()){
             WhatsappLog::create([
                'sender_number' => $target,
                'sender_name'   => 'Bot AutoReply',
                'message'       => $message,
                'media_url'     => $url,
                'type'          => 'outgoing',
                'status'        => 'sent'
            ]);
        }
    }
}