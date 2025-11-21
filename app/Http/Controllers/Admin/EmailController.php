<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Webklex\IMAP\Facades\Client;

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
            $messages = $folder->messages()->all()->paginate(20, $request->get('page', 1));

            return view('admin.email.inbox', compact('messages'));

        } catch (Exception $e) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Gagal terhubung ke server email: ' . $e->getMessage())
                ->withErrors(['connection' => 'Pastikan konfigurasi IMAP Anda sudah benar.']);
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
                return redirect()->route('admin.email.index')->with('error', 'Email tidak ditemukan.');
            }
            
            return view('admin.email.imap-show', compact('message'));

        } catch (Exception $e) {
            return redirect()->route('admin.email.index')->with('error', 'Gagal mengambil email: ' . $e->getMessage());
        }
    }
    
    /**
     * Menampilkan form untuk menulis email baru.
     */
    public function create()
    {
        return view('admin.email.create');
    }

    /**
     * -- DIPERBAIKI: Nama metode diubah dari 'store' menjadi 'send' --
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

        $to = $request->input('to');
        $subject = $request->input('subject');
        $body = $request->input('body');

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)
                        ->subject($subject);
            });

            return redirect()->route('admin.email.index')->with('success', 'Email berhasil dikirim!');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }

    /**
     * -- DIPERBAIKI: Nama metode diubah dari 'destroy' menjadi 'delete' --
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
            } else {
                 return response()->json([
                    'success' => false,
                    'message' => 'Email tidak ditemukan untuk dihapus.'
                ], 404);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
