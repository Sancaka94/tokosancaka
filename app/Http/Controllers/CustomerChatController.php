<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Events\NewMessage;

class CustomerChatController extends Controller
{
    /**
     * Menampilkan halaman chat untuk pelanggan.
     */
    public function index()
    {
        // ID Admin diatur ke 4.
        $admin = User::find(4); 

        if (!$admin) {
            return view('customer.chat.index')->withErrors('Tidak dapat memulai percakapan, admin tidak ditemukan.');
        }
        
        return view('customer.chat.index', ['admin' => $admin]);
    }

    /**
     * Mengambil riwayat pesan antara pelanggan dan admin.
     */
    public function fetchMessages()
    {
        $customerId = Auth::id();
        
        $admin = User::find(4);
        if (!$admin) {
            return response()->json([]);
        }
        $adminId = $admin->getKey();

        $messages = Message::where(function($query) use ($adminId, $customerId) {
            $query->where('from_id', $adminId)->where('to_id', $customerId);
        })->orWhere(function($query) use ($adminId, $customerId) {
            $query->where('from_id', $customerId)->where('to_id', $adminId);
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    /**
     * Mengirim pesan dari pelanggan ke admin.
     */
    public function sendMessage(Request $request)
    {
        $request->validate(['message' => 'required|string']);

        $admin = User::find(4);
        if (!$admin) {
            return response()->json(['status' => 'Gagal: Admin tidak ditemukan.'], 404);
        }

        $message = Message::create([
            'from_id' => Auth::id(),
            'to_id' => $admin->getKey(),
            'message' => $request->message,
        ]);

        // Menyiarkan event ke pengguna lain.
        broadcast(new NewMessage($message))->toOthers();

        return response()->json(['status' => 'Pesan terkirim!', 'message' => $message]);
    }
}
