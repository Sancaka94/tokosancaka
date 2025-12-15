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
        $this->token = env('FONNTE_TOKEN', 'UvqpsKd6ksLjGsGe4ARn');
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

   public function webhook(Request $request)
{
    // 1. Log Incoming Data (Penting untuk Debugging)
    Log::info('WA Incoming:', $request->all());

    try {
        // 2. Cek apakah ini Pesan Grup?
        $isGroup = $request->isgroup || $request->isgroup === 'true'; // Fonnte kadang kirim boolean atau string

        // Jika Anda TIDAK ingin menyimpan chat grup (supaya database tidak penuh)
        if ($isGroup) {
            return response()->json(['status' => true, 'message' => 'Group message ignored']);
        }

        // 3. Ambil Data
        $sender  = $request->sender; // Nomor HP (atau Group ID)
        $message = $request->message;
        $name    = $request->name ?? 'Unknown';
        
        // Handle jika pesan kosong/non-text (misal: sticker)
        if ($message == "non-text message" || empty($message)) {
            $message = "(Sticker/Media/Non-Text)";
        }

        // 4. Simpan ke Database
        WhatsappLog::create([
            'sender_number' => $sender,
            'sender_name'   => $name,
            'message'       => $message,
            'media_url'     => $request->url ?? null,
            'type'          => 'incoming',
            'status'        => 'received'
        ]);

        // 5. Auto Reply (Hanya untuk Chat Pribadi)
        // Kita tidak mau bot membalas di Grup (bisa spam)
        if (!$isGroup) {
            $msgLower = strtolower($message);
            
            if ($msgLower == "test") {
                $this->replyAuto($sender, "Halo! Server Laravel siap menerima pesan.");
            }
            elseif ($msgLower == "info") {
                $this->replyAuto($sender, "Ini adalah layanan otomatis Sancaka Express.");
            }
        }

        return response()->json(['status' => true]);

    } catch (\Exception $e) {
        // Catat error asli ke Laravel Log agar ketahuan
        Log::error('Webhook Error: ' . $e->getMessage());
        return response()->json(['status' => false], 500);
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