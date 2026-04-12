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

        $contactId = $request->contact_id;
        if (!$contactId) {
            $store = Store::find($request->store_id);
            $contactId = $store ? $store->user_id : $request->store_id;
        }

        \App\Models\Message::where('from_id', $contactId)
            ->where('to_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => \Carbon\Carbon::now()]);

        $query = \App\Models\Message::where(function($q) use ($userId, $contactId) {
                $q->where('from_id', $userId)->where('to_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('from_id', $contactId)->where('to_id', $userId);
            });

        $rawMessages = $query->orderBy('created_at', 'desc')->limit(150)->get();

        $formattedMessages = $rawMessages->map(function($msg) use ($userId) {

            // ✅ PERBAIKAN URL GAMBAR: Tangani URL lama (http) maupun data path baru
            $finalImageUrl = null;
            if ($msg->image_url) {
                $finalImageUrl = str_starts_with($msg->image_url, 'http')
                    ? $msg->image_url
                    : 'https://tokosancaka.com/storage/' . $msg->image_url;
            }

            return [
                'id'         => $msg->id,
                'sender'     => ($msg->from_id == $userId) ? 'user' : 'store',
                'message'    => $msg->message,
                'image_url'  => $finalImageUrl, // <-- Terapkan URL yang sudah aman
                'created_at' => $msg->created_at,
                'is_read'    => $msg->read_at ? true : false,
            ];
        });

        // ... (Kode Status Online di bawahnya biarkan sama persis seperti milik Anda)
        $contactUser = \App\Models\User::find($contactId);
        $isOnline = false;
        $lastSeen = null;

        if ($contactUser && $contactUser->last_seen) {
            $lastSeenTime = \Carbon\Carbon::parse($contactUser->last_seen);
            if ($lastSeenTime->diffInMinutes(\Carbon\Carbon::now()) < 3) {
                $isOnline = true;
            } else {
                if ($lastSeenTime->isToday()) {
                    $lastSeen = "Hari ini " . $lastSeenTime->format('H:i');
                } elseif ($lastSeenTime->isYesterday()) {
                    $lastSeen = "Kemarin " . $lastSeenTime->format('H:i');
                } else {
                    $lastSeen = $lastSeenTime->format('d M H:i');
                }
            }
        } else {
            $isOnline = false;
            $lastSeen = "Beberapa waktu lalu";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $formattedMessages,
                'store_is_online' => $isOnline,
                'store_last_seen' => $lastSeen,
                'store_is_typing' => false
            ]
        ]);
    }

    /**
     * 2. MENGIRIM PESAN BARU
     */
    public function sendMessage(Request $request)
    {
        // ✅ PERBAIKAN VALIDASI: Gunakan file|mimes agar ramah dengan React Native
        $request->validate([
            'store_id'   => 'required',
            'message'    => 'nullable|string|max:1000',
            'image'      => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120',
            'product_id' => 'nullable|integer'
        ]);

        $userId = Auth::user()->id_pengguna ?? Auth::id();

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

                // ✅ PERBAIKAN PATH: Simpan direktori path-nya saja, jangan dibungkus asset()
                $imageUrl = $path;
            }

            if (empty($request->message) && empty($imageUrl)) {
                return response()->json(['success' => false, 'message' => 'Pesan tidak boleh kosong.'], 400);
            }

            $message = \App\Models\Message::create([
                'from_id'    => $userId,
                'to_id'      => $contactId,
                'message'    => $request->message ?? '',
                'image_url'  => $imageUrl,
                'product_id' => $request->product_id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id'         => $message->id,
                    'sender'     => 'user',
                    'message'    => $message->message,
                    // ✅ PANTULKAN URL LENGKAP agar langsung muncul seketika di layar HP setelah terkirim
                    'image_url'  => $message->image_url ? 'https://tokosancaka.com/storage/' . $message->image_url : null,
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

        // ==================================================
        // 1. UPDATE STATUS KITA SENDIRI SAAT BUKA DAFTAR CHAT
        // Supaya saat kita buka halaman ini, orang lain melihat kita Hijau (Online)
        // ==================================================
        \DB::table('Pengguna')->where('id_pengguna', $userId)->update([
            // Kita update dua-duanya jaga-jaga kalau ada sisa kolom lama
            'last_seen' => \Carbon\Carbon::now(),
            'last_seen_at' => \Carbon\Carbon::now(),
        ]);

        $messagesQuery = Message::orderBy('created_at', 'desc');

        if (!$isAdmin) {
            $messagesQuery->where(function($q) use ($userId) {
                $q->where('from_id', $userId)->orWhere('to_id', $userId);
            });
        }

        $allMessages = $messagesQuery->limit(2000)->get();
        $conversationsMap = [];

        foreach ($allMessages as $msg) {
            $contactId = ($msg->from_id == $userId) ? $msg->to_id : $msg->from_id;

            if (!isset($conversationsMap[$contactId])) {
                $conversationsMap[$contactId] = [
                    'contact_id'   => $contactId,
                    'last_message' => $msg->message ?: ($msg->image_url ? '📷 Mengirim Gambar' : ''),
                    'last_time'    => $msg->created_at,
                    'unread_count' => 0
                ];
            }

            if ($msg->from_id == $contactId && $msg->to_id == $userId && $msg->read_at == null) {
                $conversationsMap[$contactId]['unread_count'] += 1;
            }
        }

        $finalConversations = [];
        foreach ($conversationsMap as $conv) {
            $contactUser = \DB::table('Pengguna')->where('id_pengguna', $conv['contact_id'])->first();
            if (!$contactUser) continue;

            $store = Store::where('user_id', $conv['contact_id'])->first();
            $conv['name'] = $store ? $store->name : ($contactUser->nama_lengkap ?? 'Pengguna');
            $conv['logo'] = $contactUser->store_logo_path;
            $conv['store_id'] = $store ? $store->id : $conv['contact_id'];

            // ==========================================
            // 2. LOGIKA BACA STATUS ONLINE ANTI-GAGAL
            // ==========================================
            $isOnline = false;

            // Baca dari last_seen, kalau kosong cari last_seen_at
            $waktu_terakhir = $contactUser->last_seen ?? $contactUser->last_seen_at ?? null;

            if ($waktu_terakhir) {
                $lastSeenTime = \Carbon\Carbon::parse($waktu_terakhir);

                // Gunakan abs() agar tidak error jika jam server lebih lambat dari DB
                // Jika selisih kurang dari 3 menit = Online (Hijau)
                if (abs($lastSeenTime->diffInMinutes(\Carbon\Carbon::now())) < 3) {
                    $isOnline = true;
                }
            }

            // Masukkan true/false ke respon JSON
            $conv['is_online'] = $isOnline;
            $finalConversations[] = $conv;
        }

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

    /**
     * 5. MENGHAPUS PESAN SPESIFIK YANG DIPILIH
     */
    public function deleteSelectedMessages(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array'
        ]);

        $userId = Auth::user()->id_pengguna ?? Auth::id();

        // Pastikan hanya bisa menghapus pesan di mana User adalah pengirim atau penerimanya
        \App\Models\Message::whereIn('id', $request->message_ids)
            ->where(function($q) use ($userId) {
                $q->where('from_id', $userId)->orWhere('to_id', $userId);
            })
            ->delete();

        return response()->json(['success' => true, 'message' => 'Pesan berhasil dihapus']);
    }
}
