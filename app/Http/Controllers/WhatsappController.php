<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappLog; // Model Database
use App\Services\FonnteService; // Service API Fonnte Anda
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    public function index(Request $request)
    {
        // A. Ambil Daftar Kontak (Sidebar) - REVISI
        // Logika Baru: Grouping HANYA berdasarkan sender_number
        // Tujuannya agar nomor yang sama tidak muncul berkali-kali meski namanya berubah-ubah.
        
        $rawContacts = DB::table('whatsapp_logs')
            ->select('sender_number', DB::raw('MAX(created_at) as last_msg_time'))
            ->groupBy('sender_number')
            ->orderBy('last_msg_time', 'desc')
            ->get();

        // B. Proses Nama Kontak (Agar yang muncul nama paling update / benar)
        $contacts = $rawContacts->map(function($contact) {
            // 1. Cek apakah nomor ini terdaftar di Tabel User (Pelanggan)?
            // (Pastikan Model User dan kolom no_hp ada, jika tidak ada hapus bagian ini)
            $user = \App\Models\User::where('no_hp', $contact->sender_number)->first();
            
            if ($user) {
                $finalName = $user->name; // Prioritas 1: Nama dari data Pelanggan
            } else {
                // 2. Jika bukan User, ambil nama dari Log WA terakhir yang masuk
                $lastLog = DB::table('whatsapp_logs')
                    ->where('sender_number', $contact->sender_number)
                    ->where('type', 'incoming') // Utamakan nama dari pesan masuk
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                // Jika masih kosong, ambil dari log apa saja
                if (!$lastLog) {
                    $lastLog = DB::table('whatsapp_logs')
                        ->where('sender_number', $contact->sender_number)
                        ->orderBy('created_at', 'desc')
                        ->first();
                }

                $finalName = $lastLog->sender_name ?? 'Unknown';
            }

            // Kembalikan data yang sudah dirapikan
            return (object) [
                'sender_number' => $contact->sender_number,
                'sender_name'   => $finalName,
                'last_msg_time' => $contact->last_msg_time
            ];
        });

        // C. Ambil Chat Aktif (Area Kanan)
        $activeChat = [];
        $activePhone = $request->query('phone'); 

        if ($activePhone) {
            $activeChat = WhatsappLog::where('sender_number', $activePhone)
                ->orderBy('created_at', 'asc')
                ->get();
            
            // Opsional: Update status 'read' saat chat dibuka
            // WhatsappLog::where('sender_number', $activePhone)->update(['status' => 'read']);
        }

        return view('whatsapp.index', compact('contacts', 'activeChat', 'activePhone'));
    }

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