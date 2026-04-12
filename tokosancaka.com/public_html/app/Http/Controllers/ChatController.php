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
    // =================================================================
    // 1. HALAMAN CHAT UNTUK ADMIN
    // =================================================================
    public function adminIndex()
    {
        $user = Auth::user();
        $userId = $user->getKey(); // Jauh lebih aman dari $user->id_pengguna
        $keyName = (new User)->getKeyName(); // Dinamis mengambil primary key (id atau id_pengguna)

        $contactIds = Message::where('from_id', $userId)->pluck('to_id')
            ->merge(Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->values()->toArray();

        $users = User::whereIn($keyName, $contactIds)
            ->where($keyName, '!=', $userId) // Jangan tampilkan diri sendiri
            ->get()
            ->map(function($u) use ($userId) {
                $uId = $u->getKey();

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
                return $u->last_message_data ? $u->last_message_data->created_at : '2000-01-01 00:00:00';
            });

        return view('admin.chat', compact('users'));
    }

    // =================================================================
    // 2. HALAMAN CHAT UNTUK CUSTOMER
    // =================================================================
    public function customerIndex()
    {
        $user = Auth::user();
        $userId = $user->getKey();
        $keyName = (new User)->getKeyName();

        // 1. Cari riwayat kontak
        $contactIds = Message::where('from_id', $userId)->pluck('to_id')
            ->merge(Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->values()->toArray();

        // 2. WAJIB: Masukkan ID Admin Pusat (Admin = 4) agar CS selalu muncul
        $adminId = 4;
        if (!in_array($adminId, $contactIds)) {
            $contactIds[] = $adminId;
        }

        // 3. Tarik data dari database dengan aman
        $users = User::whereIn($keyName, $contactIds)
            ->where($keyName, '!=', $userId) // Jangan tampilkan diri sendiri
            ->get()
            ->map(function($u) use ($userId) {
                $uId = $u->getKey();

                $u->last_message_data = Message::where(function($q) use ($userId, $uId) {
                    $q->where('from_id', $userId)->where('to_id', $uId);
                })->orWhere(function($q) use ($userId, $uId) {
                    $q->where('from_id', $uId)->where('to_id', $userId);
                })->orderBy('created_at', 'desc')->first();

                $u->unread_count = Message::where('from_id', $uId)
                    ->where('to_id', $userId)
                    ->whereNull('read_at')
                    ->count();

                // Ganti nama dengan nama Toko jika punya toko
                if (class_exists(Store::class)) {
                    $store = Store::where('user_id', $uId)->first();
                    if ($store) {
                        $u->nama_lengkap = $store->name;
                        $u->store_logo_path = $store->logo ?? null;
                    }
                }

                // Pastikan ID 4 selalu bernama Admin
                if (in_array(strtolower($u->role ?? ''), ['admin', 'superadmin']) || $uId == 4) {
                    $u->nama_lengkap = "Admin Sancaka";
                }

                return $u;
            })->sortByDesc(function($u) {
                return $u->last_message_data ? $u->last_message_data->created_at : '2000-01-01 00:00:00';
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
        $isTargetOnline = $targetUser && $targetUser->last_seen && Carbon::parse($targetUser->last_seen)->diffInMinutes(now()) < 5;

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
        $request->validate([
            'message' => 'nullable|string|max:2000',
            'image'   => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        $user = Auth::user();
        $userId = $user->getKey();
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
                'message' => 'Terkirim'
            ]);

        } catch (\Exception $e) {
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
}
