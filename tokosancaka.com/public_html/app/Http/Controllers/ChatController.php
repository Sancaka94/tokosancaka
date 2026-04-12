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
        $pk = (new User())->getKeyName(); // Otomatis mendeteksi apakah pakai 'id' atau 'id_pengguna'

        // 1. Ambil semua ID pengguna yang pernah interaksi dengan admin ini
        $contactIds = Message::where('from_id', $userId)->pluck('to_id')
            ->merge(Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->toArray();

        // 2. Tarik data User lengkap dengan relasi Pesan Terakhir
        $users = User::whereIn($pk, $contactIds)->get()->map(function($u) use ($userId) {
            $uId = $u->id_pengguna ?? $u->id;

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
            return $u->last_message_data->created_at ?? '2000-01-01';
        });

        return view('admin.chat', compact('users'));
    }

    // Menampilkan halaman chat untuk CUSTOMER
    public function customerIndex()
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;
        $pk = (new User())->getKeyName(); // Otomatis mendeteksi primary key

        // 1. Cari ID toko/admin yang pernah di-chat oleh customer ini
        $contactIds = Message::where('from_id', $userId)->pluck('to_id')
            ->merge(Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->toArray();

        // 2. DINAMIS: Cari Admin Pusat otomatis dari tabel users berdasarkan role
        $admin = User::whereIn('role', ['admin', 'superadmin'])->first();
        if ($admin) {
            $adminId = $admin->id_pengguna ?? $admin->id;
            // Pastikan Admin selalu ada di sidebar, meskipun belum pernah chat
            if (!in_array($adminId, $contactIds)) {
                $contactIds[] = $adminId;
            }
        }

        // 3. Tarik data User/Toko lengkap
        $users = User::whereIn($pk, $contactIds)->get()->map(function($u) use ($userId) {
            $uId = $u->id_pengguna ?? $u->id;

            // Ambil pesan terakhir
            $u->last_message_data = Message::where(function($q) use ($userId, $uId) {
                $q->where('from_id', $userId)->where('to_id', $uId);
            })->orWhere(function($q) use ($userId, $uId) {
                $q->where('from_id', $uId)->where('to_id', $userId);
            })->orderBy('created_at', 'desc')->first();

            // Hitung yang belum dibaca
            $u->unread_count = Message::where('from_id', $uId)
                ->where('to_id', $userId)
                ->whereNull('read_at')
                ->count();

            // Cek apakah dia Toko, jika ya ganti namanya
            $store = \App\Models\Store::where('user_id', $uId)->first();
            if ($store) {
                $u->nama_lengkap = $store->name;
                $u->store_logo_path = $store->logo ?? null;
            } elseif (in_array(strtolower($u->role ?? ''), ['admin', 'superadmin'])) {
                $u->nama_lengkap = "Admin Sancaka";
            }

            return $u;
        })->sortByDesc(function($u) {
            return $u->last_message_data->created_at ?? '2000-01-01';
        });

        return view('customer.chat', compact('users'));
    }

    /**
     * API: Mendapatkan daftar percakapan (List Inbox)
     */
    public function getConversations()
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

            // Prioritaskan nama toko, jika tidak ada pakai nama user
            $conv['name'] = $store ? $store->name : ($contactUser->nama_lengkap ?? $contactUser->name ?? 'Pengguna');
            $conv['logo'] = $contactUser->store_logo_path ?? null;
            $conv['store_id'] = $store ? $store->id : $conv['contact_id'];

            // Tambahkan informasi online
            $conv['is_online'] = $contactUser->last_seen && Carbon::parse($contactUser->last_seen)->diffInMinutes(now()) < 5;

            $finalConversations[] = $conv;
        }

        // Sortir ulang agar chat terbaru naik ke atas
        usort($finalConversations, function($a, $b) {
            return strtotime($b['last_time']) - strtotime($a['last_time']);
        });

        return response()->json($finalConversations);
    }

    /**
     * API: Mendapatkan pesan dari sebuah percakapan
     * Parameter $contactId diambil dari URL route
     */
    public function getMessages($contactId)
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        // 1. FITUR CENTANG BACA: Tandai pesan dari kontak ini sudah dibaca
        Message::where('from_id', $contactId)
            ->where('to_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        // 2. Ambil riwayat pesannya
        $messages = Message::where(function($q) use ($userId, $contactId) {
                $q->where('from_id', $userId)->where('to_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('from_id', $contactId)->where('to_id', $userId);
            })
            ->orderBy('created_at', 'asc') // Web Ascending (Pesan lama di atas)
            ->limit(500)
            ->get();

        // 3. Format ulang untuk Frontend
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

        // 4. Cek Status Online Lawan Bicara (Untuk fitur centang & indikator hijau)
        $targetUser = User::find($contactId);
        $isTargetOnline = $targetUser && $targetUser->last_seen && Carbon::parse($targetUser->last_seen)->diffInMinutes(now()) < 5;

        // Khusus untuk web Customer: Kita beri tahu apakah yang online ini Admin
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
     * Parameter $contactId diambil dari URL route
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
                // Simpan path relatif (Sama seperti logika Mobile App)
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

        // Bisa dari payload POST atau dari route parameter
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
