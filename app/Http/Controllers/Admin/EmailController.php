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
     * Menampilkan daftar email dari folder Inbox (IMAP).
     */
    public function index(Request $request)
    {
        try {
            $client = Client::account('default');
            $client->connect();
            $folder = $client->getFolder('INBOX');
            
            // Mengambil pesan dengan penanganan error jika folder tidak ditemukan
            $messages = $folder->messages()->all()->paginate(20, $request->get('page', 1));

            return view('admin.email.inbox', compact('messages'));

        } catch (Exception $e) {
            // Mencatat log teknis di storage/logs/laravel.log
            Log::error("IMAP Connection Error: " . $e->getMessage());

            // Menampilkan pesan error detail di halaman inbox, bukan redirect ke dashboard
            return view('admin.email.inbox', [
                'messages' => collect([]), // Kirim koleksi kosong agar view tidak crash
                'error_detail' => $e->getMessage()
            ])->with('error', 'Gagal terhubung ke server email. Lihat detail di bawah.');
        }
    }

    /**
     * Menampilkan detail satu email (IMAP).
     */
    public function show($messageId)
    {
        try {
            $client = Client::account('default');
            $client->connect();
            $folder = $client->getFolder('INBOX');
            $message = $folder->query()->where('uid', $messageId)->get()->first();
            
            if ($message) {
                $message->setFlag('Seen');
            } else {
                throw new Exception("Email dengan UID $messageId tidak ditemukan di server.");
            }
            
            return view('admin.email.imap-show', compact('message'));

        } catch (Exception $e) {
            Log::error("IMAP Show Error: " . $e->getMessage());
            
            // Kembali ke index dengan membawa pesan error teknis
            return redirect()->route('admin.email.index')
                ->with('error', 'Gagal mengambil email: ' . $e->getMessage());
        }
    }

    /**
     * Mengirim email menggunakan SMTP.
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $to = $request->input('to');
            $subject = $request->input('subject');
            $body = $request->input('body');

            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)
                        ->subject($subject);
            });

            return redirect()->route('admin.email.index')->with('success', 'Email berhasil dikirim!');

        } catch (Exception $e) {
            Log::error("SMTP Send Error: " . $e->getMessage());
            
            // Menampilkan error detail di form agar admin bisa troubleshoot (misal: SMTP auth failed)
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus email dari server (IMAP).
     */
    public function delete($messageId)
    {
        try {
            $client = Client::account('default');
            $client->connect();
            $folder = $client->getFolder('INBOX');
            $message = $folder->query()->where('uid', $messageId)->get()->first();

            if ($message) {
                $message->delete(true); 
                return response()->json([
                    'success' => true,
                    'message' => 'Email berhasil dihapus secara permanen.'
                ]);
            }
            
            throw new Exception("UID $messageId tidak ditemukan.");

        } catch (Exception $e) {
            Log::error("IMAP Delete Error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan form tulis email (Jika masih dibutuhkan secara konvensional)
     */
    public function create()
    {
        return view('admin.email.create');
    }

    /**
     * Mengambil detail satu email (JSON).
     */
    public function show(Request $request, $id)
    {
        try {
            $client = Client::account('default');
            $client->connect();
            $folder = $client->getFolder('INBOX');
            $message = $folder->query()->where('uid', $id)->get()->first();
            
            if (!$message) throw new Exception("Email tidak ditemukan.");

            $message->setFlag('Seen');

            return response()->json([
                'subject' => $message->getSubject(),
                'from_name' => $message->getFrom()[0]->personal,
                'from_address' => $message->getFrom()[0]->mail,
                'created_at' => $message->getDate(),
                'body' => $message->getHTMLBody() ?: $message->getTextBody(),
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}