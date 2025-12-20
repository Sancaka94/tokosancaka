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
                $folder = $client->getFolder($request->get('folder', 'INBOX'));
                
                // Mengambil pesan dengan pagination
                $messages = $folder->messages()->all()->paginate(20, $request->get('page', 1));
                
                return response()->json([
                    'emails' => $messages,
                    'unread_count' => $folder->messages()->unseen()->get()->count()
                ]);
            } catch (Exception $e) {
                Log::error("IMAP Index Error: " . $e->getMessage());
                return response()->json([
                    'error' => true,
                    'message' => 'Gagal memuat email: ' . $e->getMessage()
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