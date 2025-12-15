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

    /**
     * 3. WEBHOOK (Incoming - Pesan Masuk dari Customer)
     * Route: POST /api/webhook/fonnte
     */
    public function webhook(Request $request)
    {
        // Log request masuk untuk debugging (Cek di storage/logs/laravel.log)
        Log::info('Fonnte Webhook Received:', $request->all());

        // Ambil data dari JSON yang dikirim Fonnte
        $sender  = $request->sender;   // Nomor HP Pengirim
        $message = $request->message;  // Isi Pesan
        $name    = $request->name;     // Nama Kontak Pengirim
        $url     = $request->url;      // URL Gambar/File (jika ada)

        // Validasi sederhana: Jika tidak ada pengirim, tolak request
        if (!$sender) {
            return response()->json(['status' => false, 'reason' => 'No Sender Data'], 400);
        }

        try {
            // Simpan Pesan Masuk (Incoming) ke Database
            WhatsappLog::create([
                'sender_number' => $sender,
                'sender_name'   => $name ?? 'Unknown', // Jika nama kosong, isi Unknown
                'message'       => $message,           // Isi pesan teks
                'media_url'     => $url ?? null,       // URL file/gambar (penting!)
                'type'          => 'incoming',         // Tipe pesan masuk
                'status'        => 'received'
            ]);

            // Wajib return JSON true agar Fonnte tahu data sukses diterima
            return response()->json(['status' => true]);

        } catch (\Exception $e) {
            // Jika database error (misal emoji, kolom kurang), catat di log
            Log::error('Webhook Database Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }
}