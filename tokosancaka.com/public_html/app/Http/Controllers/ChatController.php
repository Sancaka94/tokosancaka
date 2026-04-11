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
        return view('admin.chat');
    }

    // Menampilkan halaman chat untuk CUSTOMER
    public function customerIndex()
    {
        return view('customer.chat');
    }

    /**
     * API: Mendapatkan daftar percakapan (List Inbox)
     * Sama persis dengan logika mobile: mengambil chat terakhir dan jumlah unread
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

            $finalConversations[] = $conv;
        }

        // Sortir ulang agar yang ada chat terbaru naik ke atas
        usort($finalConversations, function($a, $b) {
            return strtotime($b['last_time']) - strtotime($a['last_time']);
        });

        // Kembalikan format array langsung (biasanya Javascript Web membutuhkan ini)
        return response()->json($finalConversations);
    }

    /**
     * API: Mendapatkan pesan dari sebuah percakapan
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
            ->orderBy('created_at', 'asc') // Web biasanya Ascending (Pesan lama di atas)
            ->limit(300)
            ->get();

        // 3. Format ulang agar kompatibel dengan Javascript Web yang sudah ada
        $formattedMessages = $messages->map(function($msg) use ($userId) {
            return [
                'id'              => $msg->id,
                'sender_id'       => $msg->from_id,
                'receiver_id'     => $msg->to_id,
                'is_me'           => ($msg->from_id == $userId), // Boolean untuk UI Web
                'content'         => $msg->message, // Jaga kompatibilitas jika JS lama pakai 'content'
                'message'         => $msg->message,
                'image_url'       => $msg->image_url,
                'created_at'      => $msg->created_at,
                'is_read'         => $msg->read_at ? true : false,
            ];
        });

        return response()->json($formattedMessages);
    }

    /**
     * API: Mengirim pesan baru
     */
    public function sendMessage(Request $request)
    {
        // Validasi bisa menerima 'content' (dari web lama) atau 'message', dan 'image'
        $request->validate([
            'contact_id' => 'required',
            'content'    => 'nullable|string|max:2000',
            'message'    => 'nullable|string|max:2000',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg|max:5120'
        ]);

        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;
        $contactId = $request->contact_id;

        // Ambil teks dari parameter mana pun yang dikirim Frontend
        $messageText = $request->message ?? $request->content ?? '';

        try {
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('uploads/chat', 'public');
                $imageUrl = asset('storage/' . $path);
            }

            if (empty($messageText) && empty($imageUrl)) {
                return response()->json(['success' => false, 'message' => 'Pesan tidak boleh kosong.'], 400);
            }

            $message = Message::create([
                'from_id'   => $userId,
                'to_id'     => $contactId,
                'message'   => $messageText,
                'image_url' => $imageUrl,
            ]);

            // TODO: Broadcast pesan menggunakan WebSockets (Laravel Echo)
            // event(new MessageSent($message, $contactId));

            return response()->json([
                'success' => true,
                'message' => 'Terkirim',
                'data' => [
                    'id'         => $message->id,
                    'sender_id'  => $message->from_id,
                    'is_me'      => true,
                    'content'    => $message->message,
                    'message'    => $message->message,
                    'image_url'  => $message->image_url,
                    'created_at' => $message->created_at,
                    'is_read'    => false
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }
}
