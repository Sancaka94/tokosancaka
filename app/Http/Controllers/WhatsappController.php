<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB; // Wajib import ini
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    protected $token;

    public function __construct()
    {
        // Masukkan token Fonnte Anda di sini atau di .env
        $this->token = env('FONNTE_TOKEN', 'UvqpsKd6ksLjGsGe4ARn'); 
    }

    public function index(Request $request)
{
    // 1. Ambil daftar nomor unik untuk Sidebar (Kontak)
    // Mengambil semua sender_number, dibuang duplikatnya
    $contacts = DB::table('whatsapp_logs')
        ->select('sender_number', 'sender_name', DB::raw('MAX(created_at) as last_msg_time'))
        ->groupBy('sender_number', 'sender_name')
        ->orderBy('last_msg_time', 'desc')
        ->get();

    // 2. Ambil pesan untuk nomor yang sedang dipilih (jika ada)
    $activeChat = [];
    $activePhone = $request->query('phone'); // Ambil dari URL ?phone=0812xxx

    if ($activePhone) {
        $activeChat = DB::table('whatsapp_logs')
            ->where('sender_number', $activePhone) // Ambil chat milik nomor ini
            ->orderBy('created_at', 'asc') // Urutkan dari lama ke baru (seperti WA)
            ->get();
            
        // Update status 'read' jika perlu (opsional)
    }

    return view('whatsapp.index', compact('contacts', 'activeChat', 'activePhone'));
}

    // 2. KIRIM PESAN (CREATE - OUTGOING)
    public function sendMessage(Request $request)
    {
        $request->validate([
            'target' => 'required',
            'message' => 'required',
        ]);

        try {
            // Request ke API Fonnte
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $request->target,
                'message' => $request->message,
                'countryCode' => '62',
            ]);

            $result = $response->json();

            if ($response->successful() && ($result['status'] ?? false)) {
                
                // INSERT MENGGUNAKAN QUERY BUILDER
                // Perlu manual input 'created_at' karena Query Builder tidak otomatis
                DB::table('whatsapp_logs')->insert([
                    'sender_number' => $request->target,
                    'sender_name'   => 'Admin (Me)',
                    'message'       => $request->message,
                    'type'          => 'outgoing',
                    'status'        => 'success',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                return back()->with('success', 'Pesan terkirim!');
            } else {
                return back()->with('error', 'Gagal kirim via Fonnte.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // 3. TERIMA PESAN (WEBHOOK - INCOMING)
    public function webhook(Request $request)
    {
        // Ambil data (sesuaikan dengan param yang dikirim Fonnte)
        $sender  = $request->input('sender');
        $message = $request->input('message');
        $name    = $request->input('name');

        if ($sender && $message) {
            // INSERT PESAN MASUK
            DB::table('whatsapp_logs')->insert([
                'sender_number' => $sender,
                'sender_name'   => $name ?? 'Unknown',
                'message'       => $message,
                'type'          => 'incoming',
                'status'        => 'received',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        return response()->json(['status' => true]);
    }
}