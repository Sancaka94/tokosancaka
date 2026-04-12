<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChatController extends Controller
{
    // Menampilkan halaman chat untuk ADMIN
    public function adminIndex()
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        $contactIds = Message::where('from_id', $userId)->pluck('to_id')
            ->merge(Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->toArray();

        $users = User::whereIn('id_pengguna', $contactIds)->get()->map(function($u) use ($userId) {
            $uId = $u->id_pengguna;

            $u->last_message_data = Message::where(function($q) use ($userId, $uId) {
                $q->where('from_id', $userId)->where('to_id', $uId);
            })->orWhere(function($q) use ($userId, $uId) {
                $q->where('from_id', $uId)->where('to_id', $userId);
            })->orderBy('created_at', 'desc')->first();

            $u->unread_count = Message::where('from_id', $uId)
                ->where('to_id', $userId)
                ->whereNull('read_at')
                ->count();

            return $u;
        })->sortByDesc(function($u) {
            // 🟢 PERBAIKAN: Gunakan ternary operator agar tidak error jika null
            return $u->last_message_data ? $u->last_message_data->created_at : '2000-01-01 00:00:00';
        });

        return view('admin.chat', compact('users'));
    }

    // Menampilkan halaman chat untuk CUSTOMER
    public function customerIndex()
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        $contactIds = Message::where('from_id', $userId)->pluck('to_id')
            ->merge(Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->toArray();

        // WAJIB: Masukkan ID Admin Pusat (Admin = 4) agar CS selalu muncul
        $adminId = 4;
        if (!in_array($adminId, $contactIds)) {
            $contactIds[] = $adminId;
        }

        $users = User::whereIn('id_pengguna', $contactIds)->get()->map(function($u) use ($userId) {
            $uId = $u->id_pengguna;

            $u->last_message_data = Message::where(function($q) use ($userId, $uId) {
                $q->where('from_id', $userId)->where('to_id', $uId);
            })->orWhere(function($q) use ($userId, $uId) {
                $q->where('from_id', $uId)->where('to_id', $userId);
            })->orderBy('created_at', 'desc')->first();

            $u->unread_count = Message::where('from_id', $uId)
                ->where('to_id', $userId)
                ->whereNull('read_at')
                ->count();

            $store = Store::where('user_id', $uId)->first();
            if ($store) {
                $u->nama_lengkap = $store->name;
                $u->store_logo_path = $store->logo ?? null;
            } elseif (in_array(strtolower($u->role ?? ''), ['admin', 'superadmin']) || $uId == 4) {
                $u->nama_lengkap = "Admin Sancaka";
            }

            return $u;
        })->sortByDesc(function($u) {
            // 🟢 PERBAIKAN: Cegah error Attempt to read property on null
            return $u->last_message_data ? $u->last_message_data->created_at : '2000-01-01 00:00:00';
        });

        return view('customer.chat', compact('users'));
    }


    /**
     * API: Mendapatkan daftar percakapan (List Inbox untuk dipanggil JS AJAX)
     */
    public function getConversations()
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;
        $isAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'superadmin']);

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

            // Hindari user chat dengan dirinya sendiri
            if ($contactId == $userId) continue;

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

        // 🟢 PERBAIKAN KRUSIAL: Memaksa Admin Sancaka (ID 4) SELALU ADA di API Customer
        // Walaupun mereka belum pernah chat sekalipun.
        if (!$isAdmin && !isset($conversationsMap[4])) {
            $conversationsMap[4] = [
                'contact_id'   => 4,
                'last_message' => 'Klik untuk mulai percakapan',
                'last_time'    => now()->toDateTimeString(),
                'unread_count' => 0
            ];
        }

        $finalConversations = [];
        foreach ($conversationsMap as $conv) {
            // 🟢 PERBAIKAN: Gunakan where('id_pengguna') untuk mencegah User::find gagal
            $contactUser = User::where('id_pengguna', $conv['contact_id'])->first();
            if (!$contactUser) continue;

            $store = Store::where('user_id', $conv['contact_id'])->first();

            if ($store) {
                $conv['name'] = $store->name;
            } elseif (in_array(strtolower($contactUser->role ?? ''), ['admin', 'superadmin']) || $conv['contact_id'] == 4) {
                $conv['name'] = "Admin Sancaka";
            } else {
                $conv['name'] = $contactUser->nama_lengkap ?? $contactUser->name ?? 'Pengguna';
            }

            $conv['logo'] = $contactUser->store_logo_path ?? null;
            $conv['store_id'] = $store ? $store->id : $conv['contact_id'];
            $conv['is_online'] = $contactUser->last_seen && Carbon::parse($contactUser->last_seen)->diffInMinutes(now()) < 5;

            $finalConversations[] = $conv;
        }

        usort($finalConversations, function($a, $b) {
            return strtotime($b['last_time']) - strtotime($a['last_time']);
        });

        return response()->json($finalConversations);
    }

    /**
     * API: Mendapatkan pesan dari sebuah percakapan
     */
    public function getMessages($contactId)
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        Message::where('from_id', $contactId)
            ->where('to_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        $messages = Message::where(function($q) use ($userId, $contactId) {
                $q->where('from_id', $userId)->where('to_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('from_id', $contactId)->where('to_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->limit(500)
            ->get();

        $formattedMessages = $messages->map(function($msg) use ($userId) {
            return [
                'id'          => $msg->id,
                'from_id'     => $msg->from_id,
                'to_id'       => $msg->to_id,
                'is_me'       => ($msg->from_id == $userId),
                'message'     => $msg->message,
                'image_url'   => $msg->image_url,
                'product_id'  => $msg->product_id ?? null,
                'created_at'  => $msg->created_at,
                'is_read'     => $msg->read_at ? true : false,
                'read_at'     => $msg->read_at
            ];
        });

        $targetUser = User::where('id_pengguna', $contactId)->first();
        $isTargetOnline = $targetUser && $targetUser->last_seen && Carbon::parse($targetUser->last_seen)->diffInMinutes(now()) < 5;

        $isAdminOnline = false;
        if ($targetUser && in_array(strtolower($targetUser->role ?? ''), ['admin', 'superadmin'])) {
            $isAdminOnline = $isTargetOnline;
        }

        return response()->json([
            'success'       => true,
            'messages'      => $formattedMessages,
            'target_online' => $isTargetOnline,
            'admin_online'  => $isAdminOnline
        ]);
    }

    /**
     * API: Mengirim pesan baru
     */
    public function sendMessage(Request $request, $contactId)
    {
        $request->validate([
            'message' => 'nullable|string|max:2000',
            'image'   => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;
        $messageText = $request->message ?? $request->content ?? '';

        try {
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('uploads/chat', 'public');
                $imageUrl = $path;
            }

            if (empty($messageText) && empty($imageUrl)) {
                return response()->json(['success' => false, 'message' => 'Pesan tidak boleh kosong.'], 400);
            }

            $message = Message::create([
                'from_id'    => $userId,
                'to_id'      => $contactId,
                'message'    => $messageText,
                'image_url'  => $imageUrl,
                'product_id' => $request->product_id ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terkirim',
                'data' => [
                    'id'         => $message->id,
                    'from_id'    => $message->from_id,
                    'is_me'      => true,
                    'message'    => $message->message,
                    'image_url'  => $message->image_url,
                    'product_id' => $message->product_id,
                    'created_at' => $message->created_at,
                    'is_read'    => false
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Hapus seluruh riwayat pesan dengan satu user
     */
    public function deleteAllMessages(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        $contactId = $request->user_id ?? $request->contact_id;

        if (!$contactId) {
            return response()->json(['success' => false, 'message' => 'User ID tidak ditemukan.'], 400);
        }

        try {
            Message::where(function($q) use ($userId, $contactId) {
                $q->where('from_id', $userId)->where('to_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('from_id', $contactId)->where('to_id', $userId);
            })->delete();

            return response()->json(['success' => true, 'message' => 'Riwayat chat berhasil dibersihkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus pesan.'], 500);
        }
    }
}
