<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;        // Model Pengguna
use App\Models\Message;      // Model untuk pesan chat
use Illuminate\Support\Facades\Auth; // Untuk mendapatkan user yang login
use Illuminate\Support\Facades\Log; // Untuk logging
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Pengguna;
use Carbon\Carbon;

class ChatController extends Controller
{
    public function index()
    {
        $adminId = Auth::id();

        // 1. Ambil semua user KECUALI admin
        $users = User::where((new User)->getKeyName(), '!=', $adminId)->get();

        // 2. Loop untuk mencari pesan terakhir setiap user
        $users = $users->map(function ($user) use ($adminId) {
            $lastMessage = Message::where(function($q) use ($adminId, $user) {
                    $q->where('from_id', $adminId)->where('to_id', $user->getKey());
                })
                ->orWhere(function($q) use ($adminId, $user) {
                    $q->where('from_id', $user->getKey())->where('to_id', $adminId);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            $user->last_message_data = $lastMessage;

            // Hitung jumlah pesan belum dibaca (Unread)
            $user->unread_count = Message::where('from_id', $user->getKey())
                                         ->where('to_id', $adminId)
                                         ->whereNull('read_at')
                                         ->count();

            return $user;
        })
        // 3. Urutkan berdasarkan chat terbaru (yang baru chat ada di paling atas)
        ->sortByDesc(function($user) {
            return $user->last_message_data ? $user->last_message_data->created_at : '2000-01-01 00:00:00';
        });

        return view('admin.chat.index', compact('users'));
    }

    /**
     * Memulai chat dengan user tertentu dari link.
     */
    public function startChat(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:Pengguna,id_pengguna'],
        ]);

        $recipientId = $validated['user_id'];
        $adminId = Auth::id();

        if ($recipientId == $adminId) {
            return redirect()->route('admin.chat.index')->with('warning', 'Anda tidak dapat memulai chat dengan diri sendiri.');
        }

        try {
            $recipient = User::findOrFail($recipientId);
            return redirect()->route('admin.chat.index', ['chat_with' => $recipientId])
                             ->with('info', 'Membuka chat dengan ' . $recipient->nama_lengkap);

        } catch (ModelNotFoundException $e) {
            Log::warning("Attempt to start chat with non-existent user ID: " . $recipientId);
            return redirect()->back()->with('error', 'Pengguna yang ingin Anda chat tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Error starting chat: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mencoba memulai chat.');
        }
    }

    public function start(Request $request)
    {
        $id_pengguna = $request->query('id_pengguna');
        return redirect()->route('admin.chat.index', ['chat_with' => $id_pengguna]);
    }

    /**
     * Mengambil riwayat pesan antara admin dan user tertentu (AJAX).
     * ✅ DISAMAKAN DENGAN HP: Menambahkan fitur auto-read (Centang Biru)
     */
    public function fetchMessages(User $user)
    {
        $adminId = Auth::id();
        $userId = $user->getKey();

        // 1. FITUR CENTANG BACA: Tandai pesan dari user ke admin menjadi sudah dibaca
        Message::where('from_id', $userId)
            ->where('to_id', $adminId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        // 2. Query pesan antara admin dan user
        $messages = Message::where(function($query) use ($adminId, $userId) {
                // Pesan dari admin ke user
                $query->where('from_id', $adminId)->where('to_id', $userId);
            })->orWhere(function($query) use ($adminId, $userId) {
                // Pesan dari user ke admin
                $query->where('from_id', $userId)->where('to_id', $adminId);
            })
            ->orderBy('created_at', 'asc') // Web biasanya Ascending (Dari atas ke bawah)
            ->get();

        // 3. Format ulang agar JS di frontend web lebih mudah memprosesnya
        $formattedMessages = $messages->map(function($msg) use ($adminId) {
            return [
                'id'         => $msg->id,
                'from_id'    => $msg->from_id,
                'to_id'      => $msg->to_id,
                'message'    => $msg->message,
                'image_url'  => $msg->image_url, // Support Gambar
                'created_at' => $msg->created_at,
                'is_me'      => ($msg->from_id == $adminId), // True jika ini pesan admin
                'is_read'    => $msg->read_at ? true : false,
            ];
        });

        return response()->json($formattedMessages);
    }

    /**
     * Mengirim pesan dari admin ke user (AJAX).
     * ✅ DISAMAKAN DENGAN HP: Support Upload Gambar & Pesan Boleh Kosong
     */
    public function sendMessage(Request $request, $userId)
    {
        // -----------------------------------------------------------------
        // JARING PENGAMAN: Cegat jika Javascript frontend salah mengirimkan
        // perintah "delete-all" ke rute sendMessage ini.
        // -----------------------------------------------------------------
        if ($userId === 'delete-all' || $request->contact_id === 'delete-all' || $request->user_id === 'delete-all') {
            Log::info('LOG LOG: Mengalihkan request nyasar ke fungsi penghapusan chat.');
            return $this->deleteAllMessages($request);
        }

        // Lanjutkan proses normal jika ID valid
        $user = User::findOrFail($userId);

        // Validasi disamakan: Teks boleh kosong jika ada gambar
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg|max:5120' // Maksimal 5MB
        ]);

        $adminId = Auth::id();

        try {
            $imageUrl = null;

            // Proses Upload Gambar Jika Ada
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('uploads/chat', 'public');
                $imageUrl = asset('storage/' . $path);
            }

            // Keamanan: Jika dua-duanya kosong, tolak
            if (empty($request->message) && empty($imageUrl)) {
                return response()->json([
                    'status' => 'Gagal',
                    'message' => 'Pesan teks atau gambar harus diisi.'
                ], 400);
            }

            // Buat record pesan baru
            $message = Message::create([
                'from_id'   => $adminId,
                'to_id'     => $userId,
                'message'   => $request->message ?? '',
                'image_url' => $imageUrl,
            ]);

            // TODO: Broadcast event pesan baru untuk real-time WebSockets
            // event(new \App\Events\NewChatMessage($message));

            // Format kembalian sesuai dengan JS frontend
            return response()->json([
                'status'  => 'Pesan terkirim!',
                'message' => [
                    'id'         => $message->id,
                    'from_id'    => $message->from_id,
                    'to_id'      => $message->to_id,
                    'message'    => $message->message,
                    'image_url'  => $message->image_url,
                    'created_at' => $message->created_at,
                    'is_me'      => true,
                    'is_read'    => false,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending message from admin ".$adminId." to user ".$userId.": " . $e->getMessage());
            return response()->json([
                'status' => 'Gagal mengirim pesan.',
                'error'  => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus semua riwayat pesan antara admin dan user tertentu.
     */
    public function deleteAllMessages(Request $request)
    {
        $adminId = Auth::id();
        $contactId = $request->user_id ?? $request->contact_id;

        if (!$contactId || $contactId === 'delete-all') {
            return response()->json(['success' => false, 'message' => 'ID Pengguna tujuan tidak ditemukan.'], 400);
        }

        try {
            Message::where(function($q) use ($adminId, $contactId) {
                $q->where('from_id', $adminId)->where('to_id', $contactId);
            })->orWhere(function($q) use ($adminId, $contactId) {
                $q->where('from_id', $contactId)->where('to_id', $adminId);
            })->delete();

            Log::info("LOG LOG: Admin [{$adminId}] telah menghapus riwayat chat dengan User [{$contactId}].");

            return response()->json([
                'success' => true,
                'status' => 'Sukses',
                'message' => 'Riwayat chat berhasil dibersihkan.'
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting messages: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 'Gagal',
                'message' => 'Gagal menghapus pesan.'
            ], 500);
        }
    }
}
