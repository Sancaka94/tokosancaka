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
        // 1. UBAH VALIDASI DI SINI (message jadi nullable)
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'message'  => 'nullable|string|max:1000', // <-- Ubah 'required' jadi 'nullable'
            'image'    => 'nullable|image|max:5120'   // <-- Opsional: Batasi ukuran gambar max 5MB
        ]);

        $userId = Auth::user()->id_pengguna ?? Auth::id();

        // Cari ID pemilik toko
        $store = Store::find($request->store_id);

        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Toko tidak valid'], 404);
        }

        try {
            // 2. PROSES UPLOAD GAMBAR KE SERVER
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                // Simpan ke storage/app/public/uploads/chat
                $path = $file->store('uploads/chat', 'public');
                $imageUrl = asset('storage/' . $path);
            }

            // 3. CEK PENGAMAN: Pastikan minimal ada Teks ATAU Gambar
            if (empty($request->message) && empty($imageUrl)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan teks atau gambar harus diisi.'
                ], 400);
            }

            // 4. SIMPAN KE DATABASE
            $message = Message::create([
                'from_id'   => $userId,
                'to_id'     => $store->user_id,
                'message'   => $request->message ?? '', // Jika null, jadikan string kosong
                'image_url' => $imageUrl, // Pastikan kolom ini sudah ada di tabel messages kamu
                // 'read_at' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesan terkirim',
                'data' => [
                    'id'         => $message->id,
                    'sender'     => 'user',
                    'message'    => $message->message,
                    'image_url'  => $message->image_url,
                    'created_at' => $message->created_at,
                    'is_read'    => false
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
