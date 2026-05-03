<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webklex\IMAP\Facades\Client; // Facade Webklex IMAP
use App\Models\Email;
use Illuminate\Support\Facades\DB;

class EmailController extends Controller
{
    /**
     * Menampilkan halaman Blade Kotak Masuk
     */
    public function index()
    {
        Log::info('Akses halaman Kotak Masuk Email.', ['user_id' => Auth::id()]);
        return view('admin.email.index'); 
    }

    /**
     * Mengambil daftar email (IMAP untuk Inbox, DB untuk lainnya)
     */
    public function fetch(Request $request)
    {
        $folder = $request->query('folder', 'inbox');
        $search = $request->query('search', '');

        Log::info('Memuat daftar email.', ['user_id' => Auth::id(), 'folder' => $folder]);

        // === JIKA FOLDER INBOX (AMBIL DARI SERVER IMAP ASLI) ===
        if ($folder === 'inbox') {
            try {
                $client = Client::account('default');
                $client->connect();
                $inboxFolder = $client->getFolder('INBOX');

                // Tarik 15 pesan terbaru (dengan atau tanpa pencarian)
                if (!empty($search)) {
                    $messages = $inboxFolder->query()->text($search)->limit(15)->get();
                } else {
                    $messages = $inboxFolder->messages()->all()->limit(15)->get();
                }

                $emails = [];
                foreach($messages as $message){
                    $emails[] = [
                        'id' => $message->getUid(),
                        'from_name' => $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail,
                        'from_address' => $message->getFrom()[0]->mail,
                        'subject' => mb_decode_mimeheader($message->getSubject()[0] ?? '(Tanpa Subjek)'),
                        'body' => 'Pesan belum dimuat sepenuhnya...',
                        'created_at' => $message->getDate()[0]->format('Y-m-d H:i:s'),
                        'read_at' => $message->hasFlag('SEEN') ? now() : null,
                        'is_starred' => $message->hasFlag('FLAGGED'),
                    ];
                }

                // Urutkan dari yang terbaru
                usort($emails, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });

                return response()->json([
                    'emails' => $emails,
                    'unread_count' => $inboxFolder->query()->unseen()->count()
                ]);

            } catch (\Exception $e) {
                Log::error('IMAP Fetch Error', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Gagal terhubung ke IMAP: ' . $e->getMessage()], 500);
            }
        }

        // === JIKA FOLDER TERKIRIM/LAINNYA (AMBIL DARI DB LOKAL) ===
        $query = Email::where('user_id', Auth::id());

        if ($folder === 'starred') {
            $query->where('is_starred', true);
        } else {
            $query->where('folder', $folder);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('from_name', 'like', "%{$search}%");
            });
        }

        $emails = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'emails' => $emails,
            'unread_count' => 0
        ]);
    }

    /**
     * Mengambil detail isi email (Baca Pesan)
     */
    public function show($id)
    {
        Log::info('Melihat detail email.', ['email_id' => $id]);

        // Cek DB Lokal Dulu
        $localEmail = Email::where('user_id', Auth::id())->find($id);
        
        if ($localEmail) {
            if (is_null($localEmail->read_at)) {
                $localEmail->update(['read_at' => now()]);
            }
            return response()->json($localEmail);
        }

        // Cek Server IMAP
        try {
            $client = Client::account('default');
            $client->connect();
            $inboxFolder = $client->getFolder('INBOX');
            $message = $inboxFolder->query()->getMessageByUid((int) $id);

            if (!$message) {
                return response()->json(['error' => 'Email tidak ditemukan'], 404);
            }

            if (!$message->hasFlag('SEEN')) {
                $message->setFlag('SEEN'); // Tandai terbaca di server asli
            }

            return response()->json([
                'id' => $message->getUid(),
                'from_name' => $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail,
                'from_address' => $message->getFrom()[0]->mail,
                'subject' => mb_decode_mimeheader($message->getSubject()[0] ?? '(Tanpa Subjek)'),
                'body' => $message->getHTMLBody() ?? $message->getTextBody(), 
                'created_at' => $message->getDate()[0]->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            Log::error('IMAP Show Detail Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memuat isi pesan: ' . $e->getMessage()], 500);
        }
    }

   /**
     * Mengirim Email (via SMTP Server)
     */
    public function send(Request $request)
    {
        // 1. Tambahkan validasi untuk attachments (opsional batas ukuran per file 10MB)
        $validated = $request->validate([
            'to'            => 'required|email',
            'subject'       => 'required|string|max:255',
            'body'          => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', 
        ]);

        try {
            // 2. HAPUS htmlspecialchars() & nl2br(). 
            // Biarkan $bodyHtml berisi tag HTML asli bawaan Quill.js
            $bodyHtml = $validated['body'];
            
            $subject = $validated['subject'];
            $to = $validated['to'];
            
            // Tangkap file lampiran dari frontend
            $attachments = $request->file('attachments'); 

            // 3. Eksekusi Kirim SMTP dengan Lampiran
            Mail::html($bodyHtml, function ($message) use ($to, $subject, $attachments) {
                $message->to($to)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
                
                // Jika ada file lampiran, loop dan attach ke email
                if (!empty($attachments)) {
                    foreach ($attachments as $file) {
                        $message->attach($file->getRealPath(), [
                            'as' => $file->getClientOriginalName(),
                            'mime' => $file->getClientMimeType(),
                        ]);
                    }
                }
            });

            // 4. Simpan Riwayat ke DB lokal
            $email = Email::create([
                'user_id'      => Auth::id(),
                'folder'       => 'sent', 
                'from_name'    => config('mail.from.name', 'Admin Sancaka'),
                'from_address' => config('mail.from.address', 'admin@tokosancaka.com'),
                'to_address'   => $to,
                'subject'      => $subject,
                'body'         => $bodyHtml,
                'is_starred'   => false,
                'read_at'      => now(), 
            ]);

            Log::info('Email sukses dikirim via SMTP dengan lampiran.', ['to' => $to]);
            return response()->json(['success' => true, 'message' => 'Email berhasil dikirim!']);

        } catch (\Exception $e) {
            Log::error('SMTP Error.', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Fitur Update (Bintang & Hapus) - Mendukung IMAP & Lokal
     */
    public function update(Request $request, $id)
    {
        $localEmail = Email::where('user_id', Auth::id())->find($id);

        if ($localEmail) {
            // Update DB Lokal
            if ($request->has('is_starred')) $localEmail->is_starred = $request->is_starred;
            if ($request->has('folder')) $localEmail->folder = $request->folder;
            $localEmail->save();
            return response()->json(['success' => true]);
        } else {
            // Update Server IMAP
            try {
                $client = Client::account('default');
                $client->connect();
                $message = $client->getFolder('INBOX')->query()->getMessageByUid((int) $id);
                if ($message && $request->has('is_starred')) {
                    $request->is_starred ? $message->setFlag('FLAGGED') : $message->unsetFlag('FLAGGED');
                    return response()->json(['success' => true]);
                }
            } catch (\Exception $e) {
                Log::error('IMAP Flag Error', ['error' => $e->getMessage()]);
            }
        }
        return response()->json(['error' => 'Gagal memproses'], 500);
    }

   /**
     * Mencari pengguna untuk auto-complete tujuan email
     */
    public function searchUsers(Request $request)
    {
        $term = $request->query('q');

        if (empty($term)) {
            return response()->json([]);
        }

        // Pakai DB::table dijamin langsung tembus ke database PMA kamu
        $users = DB::table('Pengguna')
                    ->where('nama_lengkap', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('no_wa', 'like', "%{$term}%")
                    ->select('id_pengguna', 'nama_lengkap', 'email', 'no_wa', 'role')
                    ->limit(10)
                    ->get();

        return response()->json($users);
    }
}