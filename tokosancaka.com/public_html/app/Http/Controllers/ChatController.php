<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

        $contactIds = \App\Models\Message::where('from_id', $userId)->pluck('to_id')
            ->merge(\App\Models\Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->toArray();

        $users = \App\Models\User::whereIn('id_pengguna', $contactIds)->get()->map(function($u) use ($userId) {
            $uId = $u->id_pengguna;

            $u->last_message_data = \App\Models\Message::where(function($q) use ($userId, $uId) {
                $q->where('from_id', $userId)->where('to_id', $uId);
            })->orWhere(function($q) use ($userId, $uId) {
                $q->where('from_id', $uId)->where('to_id', $userId);
            })->orderBy('created_at', 'desc')->first();

            $u->unread_count = \App\Models\Message::where('from_id', $uId)
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

    // Menampilkan halaman chat untuk CUSTOMER
    public function customerIndex()
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        $contactIds = \App\Models\Message::where('from_id', $userId)->pluck('to_id')
            ->merge(\App\Models\Message::where('to_id', $userId)->pluck('from_id'))
            ->unique()->toArray();

        $adminId = 4;
        if (!in_array($adminId, $contactIds)) {
            $contactIds[] = $adminId;
        }

        $users = \App\Models\User::whereIn('id_pengguna', $contactIds)->get()->map(function($u) use ($userId) {
            $uId = $u->id_pengguna;

            $u->last_message_data = \App\Models\Message::where(function($q) use ($userId, $uId) {
                $q->where('from_id', $userId)->where('to_id', $uId);
            })->orWhere(function($q) use ($userId, $uId) {
                $q->where('from_id', $uId)->where('to_id', $userId);
            })->orderBy('created_at', 'desc')->first();

            $u->unread_count = \App\Models\Message::where('from_id', $uId)
                ->where('to_id', $userId)
                ->whereNull('read_at')
                ->count();

            $store = \App\Models\Store::where('user_id', $uId)->first();
            if ($store) {
                $u->nama_lengkap = $store->name;
                // PERBAIKAN: Jangan timpa logo jika logo toko kosong
                $u->store_logo_path = $store->logo ?? $u->store_logo_path ?? $u->profile_photo_path ?? null;
            } elseif (in_array(strtolower($u->role ?? ''), ['admin', 'superadmin']) || $uId == 4) {
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
        \Log::info('LOG LOG: [sendMessage] === REQUEST MASUK ===');
        \Log::info('LOG LOG: [sendMessage] Mengirim pesan ke contactId (Tujuan): ' . $contactId);

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

            // --- LOGIKA BOT AI (AKTIF UNTUK SEMUA ID) ---
            if (!empty($messageText)) {
                // Ambil role dan buat menjadi huruf kecil semua untuk keamanan pengecekan
                $roleLower = strtolower($role);

                // Daftar role yang diizinkan (dalam huruf kecil)
                $allowedRoles = ['pelanggan', 'seller', 'agent'];

                if (in_array($roleLower, $allowedRoles)) {
                    // Role VALID
                    \Log::info("LOG LOG: [sendMessage] Pengirim valid (Role: {$role}). Bot AI dipicu.");

                    // TRIGER BOT AI ANDA DISINI
                } else {
                    // Role TIDAK VALID
                    \Log::info("LOG LOG: [sendMessage] Pengirim BUKAN Pelanggan, Seller, atau Agent (Role: {$role}). Bot AI TIDAK dipicu.");
                }
            } else {
                \Log::info('LOG LOG: [sendMessage] Pesan hanya berupa gambar/kosong teks. Bot AI TIDAK dipicu.');
            }
            // ---------------------------------------------

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
    // FUNGSI UNTUK MENGIRIM PESAN KE N8N DAN MENYIMPAN BALASANNYA
    // =================================================================
    private function forwardToBot($userId, $messageText, $botId)
    {
        // Ganti dengan Production URL dari node Webhook n8n Anda
        $n8nWebhookUrl = 'https://ykzu69-n8n.bocindonesia.com/webhook/sancaka-chat';

        \Log::info('LOG LOG: === MULAI MEMANGGIL AI ===');
        \Log::info('LOG LOG: UserID: ' . $userId . ' | Tujuan Toko (BotID): ' . $botId . ' | Pesan: ' . $messageText);

        try {
            // Tembak API n8n
            $response = Http::timeout(30)->post($n8nWebhookUrl, [
                'user_id' => 'user_' . $userId, // Session ID unik untuk memory n8n
                'message' => $messageText
            ]);

            \Log::info('LOG LOG: Status Code dari n8n: ' . $response->status());

            if ($response->successful()) {
                // Catat bentuk mentah balasan dari n8n
                \Log::info('LOG LOG: Response Mentah n8n: ' . json_encode($response->json()));

                // Ambil field "balasan" dari JSON n8n
                $botReply = $response->json('balasan');

                if (!empty($botReply)) {
                    // Simpan balasan bot ke database seolah-olah dikirim oleh akun Toko/Admin
                    Message::create([
                        'from_id' => $botId,
                        'to_id'   => $userId,
                        'message' => $botReply,
                    ]);
                    \Log::info('LOG LOG: Balasan bot berhasil di-insert ke database!');
                } else {
                    \Log::error('LOG LOG: N8n membalas sukses, tapi field "balasan" kosong atau tidak ditemukan dalam format JSON.');
                }
            } else {
                \Log::error('LOG LOG: N8n menolak request. Response body: ' . $response->body());
            }
        } catch (\Exception $e) {
            // Jika n8n mati atau timeout
            \Log::error('LOG LOG: Gagal/Timeout menghubungi Bot n8n: ' . $e->getMessage());
        }

        \Log::info('LOG LOG: === SELESAI MEMANGGIL AI ===');
    }
}
