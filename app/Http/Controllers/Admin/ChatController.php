<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;        // Model Pengguna
use App\Models\Message;      // Model untuk pesan chat
use Illuminate\Support\Facades\Auth; // Untuk mendapatkan user yang login
use Illuminate\Support\Facades\Log; // Untuk logging (opsional)
use Illuminate\Database\Eloquent\ModelNotFoundException; // Untuk menangani error findOrFail
use App\Models\Pengguna; // ⬅️ tambahkan baris ini

class ChatController extends Controller
{
    /**
     * Menampilkan halaman utama chat.
     * ✅ DIPERBAIKI: Mengirim nama primary key ke view tidak perlu jika view tidak menggunakannya.
     * Mengirim $users sudah cukup.
     */
    public function index()
    {
        // $userModel = new User();
        // $primaryKey = $userModel->getKeyName(); // Ini tidak perlu dikirim ke view biasanya

        // Ambil semua user KECUALI admin yang sedang login
        // Menggunakan getKeyName() untuk memastikan kolom primary key benar
        $users = User::where((new User)->getKeyName(), '!=', Auth::id())
                     // ->where('role', '!=', 'Admin') // Filter role jika perlu
                     ->orderBy('nama_lengkap', 'asc')
                     ->get();

        // Kirim hanya $users ke view
        // Pastikan view 'admin.chat.index' ada
        return view('admin.chat.index', compact('users'));
    }

    /**
     * ✅ BARU: Memulai chat dengan user tertentu dari link.
     * Method ini akan me-redirect ke halaman chat utama dengan parameter user yang dituju.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startChat(Request $request)
    {
        // 1. Validasi input 'user_id' dari query string URL
        $validated = $request->validate([
            // Pastikan 'user_id' ada, berupa angka, dan ada di tabel 'Pengguna' menggunakan primary key 'id_pengguna'
            'user_id' => ['required', 'integer', 'exists:Pengguna,id_pengguna'],
        ]);

        $recipientId = $validated['user_id'];
        $adminId = Auth::id(); // Dapatkan ID admin yang sedang login

        // 2. Cek agar admin tidak chat dengan diri sendiri
        if ($recipientId == $adminId) {
            return redirect()->route('admin.chat.index')->with('warning', 'Anda tidak dapat memulai chat dengan diri sendiri.');
        }

        // 3. Cari user yang akan di-chat untuk memastikan valid dan ambil nama
        try {
            // Gunakan findOrFail dengan primary key kustom
            $recipient = User::findOrFail($recipientId);

            // 4. (Opsional) Logika untuk menandai percakapan aktif atau membuat record conversation

            // 5. Redirect ke halaman chat utama (`index`) sambil membawa ID user tujuan
            //    Parameter 'chat_with' akan ditambahkan ke URL: /admin/chat?chat_with={recipientId}
            //    JavaScript di view admin.chat.index bisa membaca parameter ini.
            return redirect()->route('admin.chat.index', ['chat_with' => $recipientId])
                         ->with('info', 'Membuka chat dengan ' . $recipient->nama_lengkap);

        } catch (ModelNotFoundException $e) {
            // Tangani jika user_id tidak ditemukan
            Log::warning("Attempt to start chat with non-existent user ID: " . $recipientId);
            // Kembali ke halaman sebelumnya (misal orders) dengan error
            return redirect()->back()->with('error', 'Pengguna yang ingin Anda chat tidak ditemukan.');
        } catch (\Exception $e) {
            // Tangani error lainnya
            Log::error('Error starting chat: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mencoba memulai chat.');
        }
    }


    /**
     * Mengambil riwayat pesan antara admin dan user tertentu (AJAX).
     * Route Model Binding $user otomatis mencari berdasarkan primary key ('id_pengguna').
     */
    public function fetchMessages(User $user) // Type hinting User $user akan otomatis resolve model
    {
        $adminId = Auth::id();
        $userId = $user->getKey(); // Menggunakan getKey() yang sudah tahu primary key ('id_pengguna')

        // Query pesan antara admin dan user
        $messages = Message::where(function($query) use ($adminId, $userId) {
                // Pesan dari admin ke user
                $query->where('from_id', $adminId)->where('to_id', $userId);
            })->orWhere(function($query) use ($adminId, $userId) {
                // Pesan dari user ke admin
                $query->where('from_id', $userId)->where('to_id', $adminId);
            })
            ->orderBy('created_at', 'asc') // Urutkan pesan dari yang terlama
            // ->limit(50) // Batasi jumlah pesan yang diambil jika perlu
            ->get(); // Ambil hasilnya

        // Tandai pesan sebagai sudah dibaca (opsional)
        // Message::where('from_id', $userId)->where('to_id', $adminId)->whereNull('read_at')->update(['read_at' => now()]);

        // Kembalikan response JSON
        return response()->json($messages);
    }

    /**
     * Mengirim pesan dari admin ke user (AJAX).
     * Route Model Binding $user otomatis mencari berdasarkan primary key ('id_pengguna').
     */
    public function sendMessage(Request $request, User $user)
    {
        // Validasi input message
        $validated = $request->validate(['message' => 'required|string|max:1000']);

        try {
            // Buat record pesan baru
            $message = Message::create([
                'from_id' => Auth::id(),   // ID pengirim (admin)
                'to_id' => $user->getKey(), // ID penerima (user yang di-chat)
                'message' => $validated['message'], // Isi pesan
                // 'read_at' => null, // Awalnya belum dibaca
            ]);

            // TODO: Broadcast event pesan baru untuk real-time
            // Pastikan Anda sudah membuat event NewChatMessage
            // event(new \App\Events\NewChatMessage($message));

            // Kembalikan response sukses beserta data pesan yang baru dibuat
            return response()->json(['status' => 'Pesan terkirim!', 'message' => $message]);

        } catch (\Exception $e) {
            // Tangani jika gagal menyimpan pesan
            Log::error("Error sending message from admin ".Auth::id()." to user ".$user->getKey().": " . $e->getMessage());
            return response()->json(['status' => 'Gagal mengirim pesan.'], 500); // Kirim response error server
        }
    }
    
public function start(Request $request)
{
    $id_pengguna = $request->query('id_pengguna');

    // Redirect ke halaman chat utama dengan parameter 'chat_with'
    return redirect()->route('admin.chat.index', ['chat_with' => $id_pengguna]);
}



}

