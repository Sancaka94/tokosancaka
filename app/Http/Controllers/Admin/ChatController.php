<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Menampilkan halaman utama chat.
     * ✅ DIPERBAIKI: Mengirim nama primary key ke view.
     */
    public function index()
    {
        $userModel = new User();
        $primaryKey = $userModel->getKeyName(); // Mendapatkan nama primary key (misal: 'id' atau 'id_pengguna')

        // Ambil semua user KECUALI admin yang sedang login
        $users = User::where($primaryKey, '!=', Auth::id())->get();
        
        // Kirim nama primary key ke view
        return view('admin.chat.index', compact('users', 'primaryKey'));
    }

    /**
     * Mengambil riwayat pesan antara admin dan user tertentu.
     */
    public function fetchMessages(User $user)
    {
        $adminId = Auth::id();
        $userId = $user->getKey(); // Menggunakan getKey() untuk konsistensi

        $messages = Message::where(function($query) use ($adminId, $userId) {
            $query->where('from_id', $adminId)->where('to_id', $userId);
        })->orWhere(function($query) use ($adminId, $userId) {
            $query->where('from_id', $userId)->where('to_id', $adminId);
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    /**
     * Mengirim pesan dari admin ke user.
     */
    public function sendMessage(Request $request, User $user)
    {
        $request->validate(['message' => 'required|string']);

        $message = Message::create([
            'from_id' => Auth::id(),
            'to_id' => $user->getKey(), // Menggunakan getKey() untuk konsistensi
            'message' => $request->message,
        ]);

        return response()->json(['status' => 'Pesan terkirim!', 'message' => $message]);
    }
}
