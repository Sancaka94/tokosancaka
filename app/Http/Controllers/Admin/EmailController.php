<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    /**
     * Menampilkan halaman utama email (View) atau data JSON untuk AJAX.
     */
    public function index(Request $request)
{
    if ($request->wantsJson() || $request->ajax()) {
        try {
            $client = Client::account('default');
            $client->connect();
            
            $folderName = $request->get('folder', 'INBOX');
            $folder = $client->getFolder($folderName);
            
            // Logika Pencarian jika ada query
            $query = $folder->messages();
            if ($request->has('search')) {
                $query = $query->whereText($request->search);
            }

            // Ambil pesan dengan pagination
            $messages = $query->all()->paginate(20, $request->get('page', 1));
            
            // Format data untuk mempermudah JavaScript
            $formattedEmails = collect($messages->items())->map(function($msg) {
                return [
                    'id' => $msg->getUid(),
                    'from_name' => $msg->getFrom()[0]->personal ?? 'Unknown',
                    'subject' => $msg->getSubject()->get(),
                    'created_at' => $msg->getDate()->get()->format('Y-m-d H:i:s'),
                    'is_starred' => $msg->hasFlag('Flagged'),
                    'read_at' => $msg->hasFlag('Seen') ? now() : null,
                ];
            });

            return response()->json([
                'success' => true,
                'emails' => $formattedEmails,
                'unread_count' => $folder->query()->unseen()->get()->count(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Koneksi IMAP Gagal: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ], 500);
        }
    }

    return view('admin.email.inbox');
}

    /**
     * Menampilkan form tulis email (opsional).
     */
    public function create()
    {
        return view('admin.email.create');
    }

    /**
     * Mengambil detail satu email (JSON).
     */
    public function show($id)
    {
        try {
            $client = Client::account('default');
            $client->connect();
            $folder = $client->getFolder('INBOX');
            
            // Mencari pesan berdasarkan UID
            $message = $folder->query()->where('uid', $id)->get()->first();
            
            if (!$message) {
                return response()->json(['message' => 'Email tidak ditemukan.'], 404);
            }

            // Tandai sudah dibaca
            $message->setFlag('Seen');

            return response()->json([
                'subject' => $message->getSubject()->get(),
                'from_name' => $message->getFrom()[0]->personal,
                'from_address' => $message->getFrom()[0]->mail,
                'created_at' => $message->getDate()->get()->format('Y-m-d H:i:s'),
                'body' => $message->getHTMLBody() ?: $message->getTextBody(),
            ]);
        } catch (Exception $e) {
            Log::error("IMAP Show Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal membuka detail: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengirim email melalui SMTP.
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            Mail::raw($request->body, function ($message) use ($request) {
                $message->to($request->to)
                        ->subject($request->subject);
            });

            return response()->json(['success' => true, 'message' => 'Email berhasil dikirim!']);
        } catch (Exception $e) {
            Log::error("SMTP Send Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal mengirim: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus email (IMAP).
     */
    public function delete($id)
    {
        try {
            $client = Client::account('default');
            $client->connect();
            $folder = $client->getFolder('INBOX');
            $message = $folder->query()->where('uid', $id)->get()->first();

            if ($message) {
                $message->delete(true); 
                return response()->json(['success' => true, 'message' => 'Email dihapus.']);
            }
            
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        } catch (Exception $e) {
            Log::error("IMAP Delete Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
    }
}