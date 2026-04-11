<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Models\Store;
use App\Models\User;

class ChatController extends Controller
{
    /**
     * 1. MENGAMBIL RIWAYAT CHAT & UPDATE CENTANG 2 (read_at)
     */
    public function fetchMessages(Request $request)
    {
        $request->validate([
            'store_id' => 'required'
        ]);

        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;
        $isAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'superadmin']);

        // Tentukan ID Lawan Chat (bisa berupa ID Toko atau langsung ID User)
        $contactId = $request->store_id;
        $store = Store::find($request->store_id);
        if ($store) {
            $contactId = $store->user_id;
        }

        // ========================================================
        // FITUR CENTANG 2 MERAH:
        // Saat user membuka chat, otomatis ubah status pesan yang MASUK
        // (dari lawan chat ke user ini) menjadi SUDAH DIBACA.
        // ========================================================
        Message::where('from_id', $contactId)
            ->where('to_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // AMBIL PESAN (Hanya yang melibatkan user ini dan kontak tersebut)
        $query = Message::where(function($q) use ($userId, $contactId) {
                $q->where('from_id', $userId)->where('to_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('from_id', $contactId)->where('to_id', $userId);
            });

        // Kunci Data: Ambil maksimal 150 chat terbaru
        $rawMessages = $query->orderBy('created_at', 'desc')->limit(150)->get();

        // Format data agar dipahami oleh React Native
        $formattedMessages = $rawMessages->map(function($msg) use ($userId) {
            return [
                'id'         => $msg->id,
                'sender'     => ($msg->from_id == $userId) ? 'user' : 'store',
                'message'    => $msg->message,
                'image_url'  => $msg->image_url,
                'created_at' => $msg->created_at,
                // Jika read_at ada isinya = true (Centang 2), jika null = false (Centang 1)
                'is_read'    => $msg->read_at ? true : false,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $formattedMessages
            ]
        ]);
    }

    /**
     * 2. MENGIRIM PESAN BARU
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'store_id' => 'required',
            'message'  => 'nullable|string|max:1000',
            'image'    => 'nullable|image|max:5120'
        ]);

        $userId = Auth::user()->id_pengguna ?? Auth::id();

        $contactId = $request->store_id;
        $store = Store::find($request->store_id);
        if ($store) {
            $contactId = $store->user_id;
        }

        try {
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('uploads/chat', 'public');
                $imageUrl = asset('storage/' . $path);
            }

            if (empty($request->message) && empty($imageUrl)) {
                return response()->json(['success' => false, 'message' => 'Pesan tidak boleh kosong.'], 400);
            }

            $message = Message::create([
                'from_id'   => $userId,
                'to_id'     => $contactId,
                'message'   => $request->message ?? '',
                'image_url' => $imageUrl,
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
                    'is_read'    => false // Baru dikirim, pasti false
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 3. MENGAMBIL DAFTAR RIWAYAT CHAT (LIST INBOX)
     */
    public function getConversations(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;
        $isAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'superadmin']);

        $messagesQuery = Message::orderBy('created_at', 'desc');

        // PRIVASI: Jika bukan admin, hanya ambil pesan miliknya sendiri
        if (!$isAdmin) {
            $messagesQuery->where(function($q) use ($userId) {
                $q->where('from_id', $userId)->orWhere('to_id', $userId);
            });
        }

        // Ambil data untuk diproses
        $allMessages = $messagesQuery->limit(2000)->get();

        $conversationsMap = [];

        foreach ($allMessages as $msg) {
            // Tentukan siapa lawan bicaranya
            $contactId = ($msg->from_id == $userId) ? $msg->to_id : $msg->from_id;

            if (!isset($conversationsMap[$contactId])) {
                $conversationsMap[$contactId] = [
                    'contact_id'   => $contactId,
                    'last_message' => $msg->message ?: ($msg->image_url ? '📷 Mengirim Gambar' : ''),
                    'last_time'    => $msg->created_at,
                    'unread_count' => 0
                ];
            }

            // Hitung Unread Count (Pesan dari lawan chat yang belum kita baca)
            if ($msg->from_id == $contactId && $msg->to_id == $userId && $msg->read_at == null) {
                $conversationsMap[$contactId]['unread_count'] += 1;
            }
        }

        $finalConversations = [];
        foreach ($conversationsMap as $conv) {
            $contactUser = User::find($conv['contact_id']);
            if (!$contactUser) continue;

            $store = Store::where('user_id', $conv['contact_id'])->first();

            $conv['name'] = $store ? $store->name : ($contactUser->nama_lengkap ?? 'Pengguna');
            $conv['logo'] = $contactUser->store_logo_path;

            // Kita kirimkan ID ini agar ketika diklik di React Native, langsung terhubung
            $conv['store_id'] = $store ? $store->id : $conv['contact_id'];

            $finalConversations[] = $conv;
        }

        // Sortir ulang agar yang ada chat terbaru naik ke atas
        usort($finalConversations, function($a, $b) {
            return strtotime($b['last_time']) - strtotime($a['last_time']);
        });

        return response()->json([
            'success' => true,
            'data' => $finalConversations
        ]);
    }

    /**
     * 4. MENGHITUNG TOTAL PESAN BELUM DIBACA UNTUK DASHBOARD BADGE
     */
    public function getUnreadCount(Request $request)
    {
        $userId = Auth::user()->id_pengguna ?? Auth::id();

        $count = Message::where('to_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }
}
