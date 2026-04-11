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
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        // ✅ PERBAIKAN LOGIKA ID: Prioritaskan contact_id jika ada
        $contactId = $request->contact_id;
        if (!$contactId) {
            $store = Store::find($request->store_id);
            $contactId = $store ? $store->user_id : $request->store_id;
        }

        // ✅ EKSEKUSI UPDATE: Hapus Notifikasi / Ubah jadi Centang 2
        \App\Models\Message::where('from_id', $contactId)
            ->where('to_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => \Carbon\Carbon::now()]);

        // AMBIL PESAN
        $query = \App\Models\Message::where(function($q) use ($userId, $contactId) {
                $q->where('from_id', $userId)->where('to_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('from_id', $contactId)->where('to_id', $userId);
            });

        $rawMessages = $query->orderBy('created_at', 'desc')->limit(150)->get();

        $formattedMessages = $rawMessages->map(function($msg) use ($userId) {
            return [
                'id'         => $msg->id,
                'sender'     => ($msg->from_id == $userId) ? 'user' : 'store',
                'message'    => $msg->message,
                'image_url'  => $msg->image_url,
                'created_at' => $msg->created_at,
                'is_read'    => $msg->read_at ? true : false,
            ];
        });

        // ==================================================
        // KODE BARU: MENGAMBIL STATUS ONLINE LAWAN CHAT
        // ==================================================
        $contactUser = \App\Models\User::find($contactId);

        $isOnline = false;
        $lastSeen = null;

        if ($contactUser && $contactUser->last_seen) {
            // Cek apakah last_seen kurang dari 3 menit yang lalu (aktif)
            $lastSeenTime = \Carbon\Carbon::parse($contactUser->last_seen);
            if ($lastSeenTime->diffInMinutes(\Carbon\Carbon::now()) < 3) {
                $isOnline = true;
            } else {
                // Format tanggal terakhir dilihat (Contoh: "10:30" atau "Kemarin 15:00")
                if ($lastSeenTime->isToday()) {
                    $lastSeen = "Hari ini " . $lastSeenTime->format('H:i');
                } elseif ($lastSeenTime->isYesterday()) {
                    $lastSeen = "Kemarin " . $lastSeenTime->format('H:i');
                } else {
                    $lastSeen = $lastSeenTime->format('d M H:i');
                }
            }
        } else {
            // Jika kolom last_seen kosong atau null
            $isOnline = false;
            $lastSeen = "Beberapa waktu lalu";
        }

        // Kembalikan Response beserta status Online
        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $formattedMessages,
                'store_is_online' => $isOnline,
                'store_last_seen' => $lastSeen,
                'store_is_typing' => false // Bisa dikembangkan nanti dengan WebSockets jika mau
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

        // ✅ PERBAIKAN LOGIKA ID
        $contactId = $request->contact_id;
        if (!$contactId) {
            $store = Store::find($request->store_id);
            $contactId = $store ? $store->user_id : $request->store_id;
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

            $message = \App\Models\Message::create([
                'from_id'   => $userId,
                'to_id'     => $contactId,
                'message'   => $request->message ?? '',
                'image_url' => $imageUrl,
            ]);

            return response()->json([
                'success' => true,
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
            // Ambil data user menggunakan Query Builder langsung ke tabel Pengguna
            $contactUser = \DB::table('Pengguna')->where('id_pengguna', $conv['contact_id'])->first();

            if (!$contactUser) continue;

            $store = Store::where('user_id', $conv['contact_id'])->first();

            $conv['name'] = $store ? $store->name : ($contactUser->nama_lengkap ?? 'Pengguna');
            $conv['logo'] = $contactUser->store_logo_path;
            $conv['store_id'] = $store ? $store->id : $conv['contact_id'];

            // ==========================================
            // KODE PERBAIKAN: DEKLARASI $isOnline
            // ==========================================
            $isOnline = false; // Harus dideklarasikan dulu sebagai false

            if ($contactUser->last_seen) {
                $lastSeenTime = \Carbon\Carbon::parse($contactUser->last_seen);
                // Jika kurang dari 3 menit, dia online!
                if ($lastSeenTime->diffInMinutes(\Carbon\Carbon::now()) < 3) {
                    $isOnline = true;
                }
            }

            // Masukkan status online ke dalam response
            $conv['is_online'] = $isOnline;
            // ==========================================

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

    public function deleteChat(Request $request)
    {
        $userId = Auth::user()->id_pengguna ?? Auth::id();
        $contactId = $request->contact_id;
        if (!$contactId) {
            $store = Store::find($request->store_id);
            $contactId = $store ? $store->user_id : $request->store_id;
        }

        \App\Models\Message::where(function($q) use ($userId, $contactId) {
            $q->where('from_id', $userId)->where('to_id', $contactId);
        })->orWhere(function($q) use ($userId, $contactId) {
            $q->where('from_id', $contactId)->where('to_id', $userId);
        })->delete();

        return response()->json(['success' => true]);
    }
}
