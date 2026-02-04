<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    // API: Mendapatkan daftar percakapan
    public function getConversations()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            // Admin mendapatkan daftar semua customer
            $conversations = User::where('role', 'customer')->orderBy('name')->get();
        } else {
            // Customer hanya mendapatkan admin
            $conversations = User::where('role', 'admin')->get();
        }
        
        return response()->json($conversations);
    }

    // API: Mendapatkan pesan dari sebuah percakapan
    public function getMessages($contactId)
    {
        $userId = Auth::id();

        $conversation = Conversation::whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereHas('participants', function ($query) use ($contactId) {
                $query->where('user_id', $contactId);
            })
            ->first();

        if (!$conversation) {
            return response()->json([]);
        }

        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    // API: Mengirim pesan baru
    public function sendMessage(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|exists:users,id',
            'content' => 'required|string',
        ]);

        $userId = Auth::id();
        $contactId = $request->contact_id;

        // Cari atau buat percakapan baru
        $conversation = Conversation::whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereHas('participants', function ($query) use ($contactId) {
                $query->where('user_id', $contactId);
            })
            ->first();
        
        if (!$conversation) {
            $conversation = Conversation::create();
            $conversation->participants()->attach([$userId, $contactId]);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'content' => $request->content,
        ]);

        // TODO: Broadcast pesan menggunakan WebSockets (Laravel Echo)
        // event(new MessageSent($message, $contactId));

        return response()->json($message);
    }
}