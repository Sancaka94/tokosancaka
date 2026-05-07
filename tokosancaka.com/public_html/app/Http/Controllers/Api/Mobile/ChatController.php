<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Message;
use App\Models\Store;
use App\Models\User;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::user()->id_pengguna ?? Auth::id();
        $query = DB::table('Pengguna');

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('nama_lengkap', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('no_wa', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('whatsapp', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('phone', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        if ($userId) {
            $query->where('id_pengguna', '!=', $userId);
        }

        $users = $query->limit(20)->get();

        $formattedUsers = $users->map(function($user) {
            return [
                'id'              => $user->id_pengguna ?? $user->id,
                'nama_lengkap'    => $user->nama_lengkap ?? $user->name ?? 'User',
                'no_wa'           => $user->no_wa ?? $user->whatsapp ?? $user->phone ?? '-',
                'store_logo_path' => $user->store_logo_path ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $formattedUsers
        ]);
    }

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

        Message::where('from_id', $contactId)
            ->where('to_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => \Carbon\Carbon::now()]);

        $query = Message::where(function($q) use ($userId, $contactId) {
                $q->where('from_id', $userId)->where('to_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('from_id', $contactId)->where('to_id', $userId);
            });

        $rawMessages = $query->orderBy('created_at', 'desc')->limit(150)->get();

        $formattedMessages = $rawMessages->map(function($msg) use ($userId) {
            $finalImageUrl = null;
            if ($msg->image_url) {
                $finalImageUrl = str_starts_with($msg->image_url, 'http')
                    ? $msg->image_url
                    : 'https://tokosancaka.com/storage/' . $msg->image_url;
            }

            $finalAudioUrl = null;
            if ($msg->audio_url) {
                $finalAudioUrl = str_starts_with($msg->audio_url, 'http')
                    ? $msg->audio_url
                    : 'https://tokosancaka.com/storage/' . $msg->audio_url;
            }

            return [
                'id'         => $msg->id,
                'sender'     => ($msg->from_id == $userId) ? 'user' : 'store',
                'message'    => $msg->message,
                'product_id' => $msg->product_id,
                'image_url'  => $finalImageUrl,
                'created_at' => $msg->created_at,
                'audio_url'  => $finalAudioUrl,
                'is_read'    => $msg->read_at ? true : false,
            ];
        });

        $contactUser = DB::table('Pengguna')->where('id_pengguna', $contactId)->first();
        $isOnline = false;
        $lastSeen = null;

        if ($contactUser) {
            $waktu_terakhir = $contactUser->last_seen ?? $contactUser->last_seen_at ?? null;
            if ($waktu_terakhir) {
                $lastSeenTime = \Carbon\Carbon::parse($waktu_terakhir);
                if (abs($lastSeenTime->diffInMinutes(\Carbon\Carbon::now())) < 3) {
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
                $lastSeen = "Beberapa waktu lalu";
            }
        } else {
            $lastSeen = "Offline";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'messages'        => $formattedMessages,
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
        $request->validate([
            // Bikin store_id tidak wajib kalau contact_id ada (dan sebaliknya)
            'store_id'   => 'required_without:contact_id|nullable',
            'contact_id' => 'required_without:store_id|nullable',
            'message'    => 'nullable|string|max:1000',
            'image'      => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120',
            'audio'      => 'nullable|file|mimes:m4a,mp3,wav,ogg,aac,mp4,3gp,webm|max:10240',
            'product_id' => 'nullable|integer'
        ]);

        $userId = Auth::user()->id_pengguna ?? Auth::id();

        $contactId = $request->contact_id;
        if (!$contactId && $request->store_id) {
            $store = Store::find($request->store_id);
            $contactId = $store ? $store->user_id : $request->store_id;
        }

        try {
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $imageUrl = $file->store('uploads/chat', 'public');
            }

            $audioUrl = null;
            if ($request->hasFile('audio')) {
                $file = $request->file('audio');
                $audioUrl = $file->store('uploads/chat_audio', 'public');
            }

            if (empty($request->message) && empty($imageUrl) && empty($audioUrl)) {
                return response()->json(['success' => false, 'message' => 'Pesan tidak boleh kosong.'], 400);
            }

            $message = Message::create([
                'from_id'    => $userId,
                'to_id'      => $contactId,
                'message'    => $request->message ?? '',
                'image_url'  => $imageUrl,
                'audio_url'  => $audioUrl,
                'product_id' => $request->product_id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id'         => $message->id,
                    'sender'     => 'user',
                    'message'    => $message->message,
                    'product_id' => $message->product_id,
                    'image_url'  => $message->image_url ? 'https://tokosancaka.com/storage/' . $message->image_url : null,
                    'created_at' => $message->created_at,
                    'audio_url'  => $message->audio_url ? 'https://tokosancaka.com/storage/' . $message->audio_url : null,
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

        DB::table('Pengguna')->where('id_pengguna', $userId)->update([
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
                $lastMsgText = $msg->message;
                if (empty($lastMsgText)) {
                    if ($msg->audio_url) {
                        $lastMsgText = '🎵 Pesan Suara';
                    } elseif ($msg->image_url) {
                        $lastMsgText = '📷 Mengirim Gambar';
                    }
                }

                $conversationsMap[$contactId] = [
                    'contact_id'   => $contactId,
                    'last_message' => $lastMsgText,
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
            $contactUser = DB::table('Pengguna')->where('id_pengguna', $conv['contact_id'])->first();
            if (!$contactUser) continue;

            $store = Store::where('user_id', $conv['contact_id'])->first();

            // 👇 PERBAIKAN: Ambil nama dari tabel Pengguna (store_name)
            $conv['name'] = $store ? $store->name : ($contactUser->store_name ?? $contactUser->nama_lengkap ?? 'Pengguna');

            // 👇 PERBAIKAN: KEMBALIKAN KE ASAL! Ambil logo mutlak dari tabel Pengguna
            $conv['logo'] = $contactUser->store_logo_path;

            $conv['store_id'] = $store ? $store->id : $conv['contact_id'];

            $isOnline = false;
            $waktu_terakhir = $contactUser->last_seen ?? $contactUser->last_seen_at ?? null;

            if ($waktu_terakhir) {
                $lastSeenTime = \Carbon\Carbon::parse($waktu_terakhir);
                if (abs($lastSeenTime->diffInMinutes(\Carbon\Carbon::now())) < 3) {
                    $isOnline = true;
                }
            }

            $conv['is_online'] = $isOnline;
            $finalConversations[] = $conv;
        }

            if ($waktu_terakhir) {
                $lastSeenTime = \Carbon\Carbon::parse($waktu_terakhir);
                if (abs($lastSeenTime->diffInMinutes(\Carbon\Carbon::now())) < 3) {
                    $isOnline = true;
                }
            }

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
     * 4. MENGHITUNG TOTAL PESAN BELUM DIBACA
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

        Message::where(function($q) use ($userId, $contactId) {
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

        Message::whereIn('id', $request->message_ids)
            ->where(function($q) use ($userId) {
                $q->where('from_id', $userId)->orWhere('to_id', $userId);
            })
            ->delete();

        return response()->json(['success' => true, 'message' => 'Pesan berhasil dihapus']);
    }

    public function savePushToken(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('LOG LOG: [Token] Memulai proses simpan token.', $request->all());

        $request->validate([
            'push_token' => 'required|string'
        ]);

        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        $update = DB::table('Pengguna')
            ->where('id_pengguna', $userId)
            ->update(['expo_token' => $request->push_token]);

        return response()->json([
            'success' => true,
            'message' => 'Token notifikasi berhasil disimpan'
        ]);
    }

    public function searchUsers(Request $request)
    {
        $userId = Auth::user()->id_pengguna ?? Auth::id();
        $searchTerm = $request->search;

        if (!$searchTerm || strlen($searchTerm) < 3) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // 👇 PERBAIKAN: Langsung cari di tabel Pengguna tanpa JOIN yang ribet
        $users = DB::table('Pengguna')
            ->where('id_pengguna', '!=', $userId)
            ->where(function($q) use ($searchTerm) {
                // Bisa dicari lewat Nama User, Nomor WA, atau Nama Toko
                $q->where('nama_lengkap', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('no_wa', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('store_name', 'LIKE', '%' . $searchTerm . '%');
            })
            ->limit(20)
            ->get();

        // 👇 PERBAIKAN: Map datanya mengambil langsung dari kolom yang benar
        $formattedUsers = $users->map(function($user) {
            return [
                'id'              => $user->id_pengguna,
                'nama_lengkap'    => $user->nama_lengkap ?? 'User Sancaka',
                'no_wa'           => $user->no_wa ?? '-',
                'store_id'        => $user->id_pengguna,
                'nama_toko'       => $user->store_name ?? $user->nama_lengkap, // Prioritaskan nama toko
                'logo_toko'       => $user->store_logo_path, // Langsung tembak path logonya
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $formattedUsers
        ]);
    }

}
