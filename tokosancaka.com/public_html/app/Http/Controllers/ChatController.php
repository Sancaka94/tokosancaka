<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    // =================================================================
    // 1. Menampilkan halaman chat untuk ADMIN
    // =================================================================
    public function adminIndex()
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        // Ambil semua kontak yang pernah berinteraksi
        $contactIds = Message::where('from_id', $userId)->pluck('to_id')
            ->merge(Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->toArray();

        // Optimasi: Gunakan Eager Loading (with) jika ada relasi, atau ambil data di luar loop
        $users = User::whereIn('id_pengguna', $contactIds)->get()->map(function($u) use ($userId) {
            $uId = $u->id_pengguna;

            // Subquery untuk Last Message
            $u->last_message_data = Message::where(function($q) use ($userId, $uId) {
                $q->where('from_id', $userId)->where('to_id', $uId);
            })->orWhere(function($q) use ($userId, $uId) {
                $q->where('from_id', $uId)->where('to_id', $userId);
            })->orderBy('created_at', 'desc')->first();

            // Hitung Unread
            $u->unread_count = Message::where('from_id', $uId)
                ->where('to_id', $userId)
                ->whereNull('read_at')
                ->count();

            // PASTIKAN LOGO DIAMBIL DENGAN AMAN
            $u->store_logo_path = $u->store_logo_path ?? $u->profile_photo_path ?? null;

            return $u;
        })->sortByDesc(function($u) {
            return $u->last_message_data->created_at ?? '2000-01-01';
        });

        return view('admin.chat', compact('users'));
    }

    // =================================================================
    // 2. Menampilkan halaman chat untuk CUSTOMER
    // =================================================================
    public function customerIndex()
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        $contactIds = Message::where('from_id', $userId)->pluck('to_id')
            ->merge(Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->toArray();

        // PERBAIKAN: Ambil ID dinamis untuk Admin/Superadmin agar tidak hardcode di angka 4
        $adminIds = User::whereIn('role', ['admin', 'superadmin'])->pluck('id_pengguna')->toArray();
        $contactIds = array_unique(array_merge($contactIds, $adminIds));

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
                // PERBAIKAN: Jangan timpa logo jika logo toko kosong
                $u->store_logo_path = $store->logo ?? $u->store_logo_path ?? $u->profile_photo_path ?? null;
            } elseif (in_array(strtolower($u->role ?? ''), ['admin', 'superadmin'])) {
                $u->nama_lengkap = "Admin Sancaka";
                $u->store_logo_path = $u->store_logo_path ?? $u->profile_photo_path ?? null;
            } else {
                $u->store_logo_path = $u->store_logo_path ?? $u->profile_photo_path ?? null;
            }

            return $u;
        })->sortByDesc(function($u) {
            return $u->last_message_data->created_at ?? '2000-01-01';
        });

        return view('customer.chat', compact('users'));
    }

    // =================================================================
    // 3. API GET MESSAGES (Dipanggil oleh Javascript)
    // =================================================================
    public function getMessages($contactId)
    {
        $user = Auth::user();
        $userId = $user->getKey();

        // Tandai sudah dibaca
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

        // Cek Online
        $keyName = (new User)->getKeyName();
        $targetUser = User::where($keyName, $contactId)->first();

        // PERBAIKAN: Ubah last_seen menjadi last_seen_at di sini
        $isTargetOnline = $targetUser && $targetUser->last_seen_at && Carbon::parse($targetUser->last_seen_at)->diffInMinutes(now()) < 5;

        return response()->json([
            'success'       => true,
            'messages'      => $formattedMessages,
            'target_online' => $isTargetOnline
        ]);
    }

    // =================================================================
    // 4. API SEND MESSAGE (Kirim Pesan Baru)
    // =================================================================
    public function sendMessage(Request $request, $contactId)
    {
        \Log::info('LOG LOG: [sendMessage] === REQUEST MASUK ===');
        \Log::info('LOG LOG: [sendMessage] Mengirim pesan ke contactId (Tujuan): ' . $contactId);

        // --- TAMBAHAN PERBAIKAN ---
        // Cegat jika Javascript frontend salah alamat mengirim perintah "delete-all" ke rute Send
        if ($contactId === 'delete-all' || $request->contact_id === 'delete-all') {
            \Log::info('LOG LOG: [sendMessage] Mengalihkan request ke fungsi deleteAllMessages...');
            return $this->deleteAllMessages($request);
        }
        // --------------------------

        $request->validate([
            'message' => 'nullable|string|max:2000',
            'image'   => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        $user = Auth::user();
        $userId = $user->getKey();
        $messageText = $request->message ?? $request->content ?? '';

        \Log::info('LOG LOG: [sendMessage] Pengirim (userId): ' . $userId . ' | Role: ' . ($user->role ?? 'Kosong'));

        try {
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('uploads/chat', 'public');
                $imageUrl = $path;
                \Log::info('LOG LOG: [sendMessage] Ada file gambar yang diupload: ' . $path);
            }

            if (empty($messageText) && empty($imageUrl)) {
                \Log::warning('LOG LOG: [sendMessage] GAGAL - Pesan dan gambar kosong.');
                return response()->json(['success' => false, 'message' => 'Pesan tidak boleh kosong.'], 400);
            }

            $message = Message::create([
                'from_id'    => $userId,
                'to_id'      => $contactId,
                'message'    => $messageText,
                'image_url'  => $imageUrl,
                'product_id' => $request->product_id ?? null,
            ]);

            \Log::info('LOG LOG: [sendMessage] Pesan berhasil disimpan ke DB (Message ID: ' . $message->id . ')');

            \Log::info('LOG LOG: [sendMessage] === REQUEST SELESAI (SUKSES) ===');
            return response()->json([
                'success' => true,
                'message' => 'Terkirim'
            ]);

        } catch (\Exception $e) {
            \Log::error('LOG LOG: [sendMessage] ERROR SISTEM: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // =================================================================
    // 5. API DELETE MESSAGES
    // =================================================================
    public function deleteAllMessages(Request $request)
    {
        $user = Auth::user();
        $userId = $user->getKey();
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

    // =================================================================
    // 6. API CEK STATUS ONLINE MASSAL (Untuk Sidebar)
    // =================================================================
    public function checkOnlineStatuses(Request $request)
    {
        $userIds = $request->user_ids ?? [];

        if (empty($userIds)) {
            return response()->json(['online_users' => []]);
        }

        // Cari user yang ID-nya ada di array request dan masih aktif 5 menit terakhir
        $keyName = (new User)->getKeyName();
        $onlineUsers = User::whereIn($keyName, $userIds)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', \Carbon\Carbon::now()->subMinutes(5))
            ->pluck($keyName);

        return response()->json([
            'online_users' => $onlineUsers
        ]);
    }
}
