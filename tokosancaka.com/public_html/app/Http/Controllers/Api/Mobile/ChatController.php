<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Models\Store;

class ChatController extends Controller
{
    /**
     * Mengambil riwayat pesan antara Pelanggan (Aplikasi) dengan Pemilik Toko
     */
    public function fetchMessages(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id'
        ]);

        $userId = Auth::user()->id_pengguna ?? Auth::id();

        // 1. Cari tahu siapa ID User dari pemilik toko ini
        $store = Store::find($request->store_id);
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $storeOwnerId = $store->user_id;

        // 2. Ambil pesan antara Pelanggan dan Pemilik Toko
        $rawMessages = Message::where(function($query) use ($userId, $storeOwnerId) {
                // Pesan dari Pelanggan ke Toko
                $query->where('from_id', $userId)->where('to_id', $storeOwnerId);
            })->orWhere(function($query) use ($userId, $storeOwnerId) {
                // Pesan balasan dari Toko ke Pelanggan
                $query->where('from_id', $storeOwnerId)->where('to_id', $userId);
            })
            ->orderBy('created_at', 'desc') // Wajib DESC untuk React Native Inverted FlatList
            ->limit(100)
            ->get();

        // 3. Format ulang (Map) agar sesuai dengan struktur React Native (sender: 'user' atau 'store')
        $formattedMessages = $rawMessages->map(function($msg) use ($userId) {
            return [
                'id' => $msg->id,
                // Jika from_id sama dengan ID Pelanggan, berarti 'user'. Jika tidak, berarti dari 'store'
                'sender' => ($msg->from_id == $userId) ? 'user' : 'store',
                'message' => $msg->message,
                'created_at' => $msg->created_at
            ];
        });

        // 4. (Opsional) Tandai pesan dari toko sudah dibaca
        // Message::where('from_id', $storeOwnerId)->where('to_id', $userId)->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $formattedMessages
            ]
        ]);
    }

    /**
     * Menyimpan pesan baru dari Pelanggan ke Toko
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'message'  => 'required|string|max:1000'
        ]);

        $userId = Auth::user()->id_pengguna ?? Auth::id();

        // Cari ID pemilik toko sebagai penerima pesan (to_id)
        $store = Store::find($request->store_id);

        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Toko tidak valid'], 404);
        }

        try {
            $message = Message::create([
                'from_id' => $userId,
                'to_id'   => $store->user_id,
                'message' => $request->message,
                // 'read_at' => null // Buka komentar ini jika tabelmu wajib mengisi read_at
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesan terkirim',
                'data' => [
                    'id' => $message->id,
                    'sender' => 'user',
                    'message' => $message->message,
                    'created_at' => $message->created_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage()
            ], 500);
        }
    }
}
