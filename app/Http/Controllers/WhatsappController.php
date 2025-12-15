<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappLog; // Model Database
use App\Services\FonnteService; // Service API Fonnte Anda
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    /**
     * 1. HALAMAN INBOX (UI Chat seperti WA Web)
     * Route: GET /whatsapp
     */
    public function index(Request $request)
    {
        // A. Ambil Daftar Kontak (Sidebar Kiri)
        // Kita grouping berdasarkan nomor pengirim agar tidak duplikat.
        // Diurutkan berdasarkan waktu pesan terakhir (MAX created_at).
        $contacts = DB::table('whatsapp_logs')
            ->select('sender_number', 'sender_name', DB::raw('MAX(created_at) as last_msg_time'))
            ->groupBy('sender_number', 'sender_name')
            ->orderBy('last_msg_time', 'desc')
            ->get();

        // B. Ambil Isi Chat (Area Kanan)
        // Hanya jika ada parameter ?phone=08xxx di URL
        $activeChat = [];
        $activePhone = $request->query('phone');

        if ($activePhone) {
            $activeChat = WhatsappLog::where('sender_number', $activePhone)
                ->orderBy('created_at', 'asc') // Chat lama di atas, baru di bawah
                ->get();
        }

        return view('whatsapp.index', compact('contacts', 'activeChat', 'activePhone'));
    }

    /**
     * 2. KIRIM PESAN (Outgoing - Dari Admin ke Customer)
     * Route: POST /whatsapp/send
     */
    public function sendMessage(Request $request)
    {
        // Validasi input
        $request->validate([
            'target' => 'required', // Nomor tujuan
            'message' => 'required', // Isi pesan
        ]);

        try {
            // --- MENGGUNAKAN SERVICE FONNTE ANDA ---
            $response = FonnteService::sendMessage($request->target, $request->message);

            // Cek apakah API Fonnte berhasil menerima request
            if ($response->successful()) {
                
                // Simpan LOG Pesan Keluar (Outgoing) ke Database
                // Agar muncul di history chat admin
                WhatsappLog::create([
                    'sender_number' => $request->target, // Nomor tujuan
                    'sender_name'   => 'Me (Admin)',     // Nama pengirim (kita sendiri)
                    'message'       => $request->message,
                    'type'          => 'outgoing',       // Tipe pesan keluar
                    'status'        => 'sent'
                ]);

                return back()->with('success', 'Pesan berhasil dikirim!');
            } else {
                // Jika API Fonnte menolak (misal token salah / server down)
                return back()->with('error', 'Gagal kirim via Fonnte: ' . $response->body());
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

   public function webhook(Request $request)
    {
        Log::info('Fonnte Webhook Data:', $request->all());

        $sender  = $request->sender;
        $message = $request->message;
        $name    = $request->name;
        $url     = $request->url;
        
        // 1. Cek apakah ini Pesan Grup?
        // Fonnte mengirim parameter 'isgroup' (true/false) atau mengecek akhiran '@g.us'
        $isGroup = $request->isgroup ?? false; 
        
        // JIKA INI GRUP, ABAIKAN SAJA (Return true biar Fonnte senang)
        if ($isGroup || str_ends_with($sender, '@g.us')) {
            return response()->json([
                'status' => true, 
                'detail' => 'Ignored: Group message'
            ]);
        }

        // 2. Cek apakah Sender kosong (Status Update)
        if (empty($sender)) {
            return response()->json([
                'status' => true, 
                'detail' => 'Ignored: Not a chat message'
            ]);
        }

        try {
            // 3. Simpan Pesan PRIBADI ke Database
            WhatsappLog::create([
                'sender_number' => $sender,
                'sender_name'   => $name ?? 'Unknown',
                'message'       => $message,
                'media_url'     => $url ?? null,
                'type'          => 'incoming',
                'status'        => 'received'
            ]);

            return response()->json(['status' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook Save Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }
}