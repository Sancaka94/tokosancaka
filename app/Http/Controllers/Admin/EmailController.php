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
            // Gunakan akun yang sesuai dengan cPanel (admin@tokosancaka.com)
            $client = Client::account('default'); 
            $client->connect();
            
            // Nama folder di cPanel biasanya case-sensitive (INBOX)
            $folderName = strtoupper($request->get('folder', 'INBOX'));
            $folder = $client->getFolder($folderName);
            
            if (!$folder) {
                return response()->json(['success' => false, 'message' => "Folder $folderName tidak ditemukan."], 404);
            }

            // Ambil pesan terbaru (descending) agar sinkron dengan tampilan cPanel
            $messages = $folder->messages()->all()->sort('created_at', 'desc')->paginate(15, $request->get('page', 1));
            
            $formattedEmails = [];
            foreach ($messages as $msg) {
                $formattedEmails[] = [
                    'id' => $msg->getUid(),
                    'from_name' => $msg->getFrom()[0]->personal ?: $msg->getFrom()[0]->mail,
                    'subject' => $msg->getSubject()->get() ?: '(no subject)',
                    'created_at' => $msg->getDate()->get()->format('Y-m-d H:i:s'),
                    'is_read' => $msg->hasFlag('Seen'),
                    'is_starred' => $msg->hasFlag('Flagged'),
                ];
            }

            return response()->json([
                'success' => true,
                'emails' => $formattedEmails,
                'unread_count' => $folder->query()->unseen()->get()->count(),
                'total' => $messages->total()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kesalahan IMAP: ' . $e->getMessage()
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