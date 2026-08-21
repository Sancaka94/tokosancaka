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
        $page = (int) $request->query('page', 1); // Tangkap nomor halaman dari URL

        Log::info('Memuat daftar email.', ['user_id' => Auth::id(), 'folder' => $folder, 'page' => $page]);

        // === JIKA FOLDER INBOX (AMBIL DARI SERVER IMAP ASLI) ===
        if ($folder === 'inbox') {
            try {
                $client = Client::account('default');
                $client->connect();
                $inboxFolder = $client->getFolder('INBOX');

                // Siapkan Query
                $query = $inboxFolder->query();
                
                // Tambahkan pengecekan if-else ini
                if (!empty($search)) {
                    $query = $query->text($search);
                } else {
                    $query = $query->all(); // <--- INI KUNCI PERBAIKANNYA
                }

                // Gunakan setFetchOrder('desc') agar dibaca dari terbaru, lalu gunakan paginate(15)
                $paginator = $query->setFetchOrder('desc')->paginate(15, $page, 'page');

                $emails = [];
                foreach($paginator as $message){
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

                // Tetap diurutkan agar tampilan di layar presisi
                usort($emails, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });

                return response()->json([
                    'emails' => $emails,
                    'unread_count' => $inboxFolder->query()->unseen()->count(),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'has_more' => $paginator->hasMorePages()
                    ]
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

        // Ubah get() menjadi paginate(15) untuk lokal DB
        $paginator = $query->orderBy('created_at', 'desc')->paginate(15, ['*'], 'page', $page);

        return response()->json([
            'emails' => $paginator->items(),
            'unread_count' => 0,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages()
            ]
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

   /**
     * Menghapus email secara masal (Mendukung IMAP & Lokal secara terpisah)
     */
    public function destroy(Request $request)
    {
        // Tangkap data ids dan folder dari frontend
        $request->validate([
            'ids' => 'required|array',
            'folder' => 'required|string', 
        ]);

        $deletedCount = 0;
        $folder = $request->folder;

        try {
            // === JALUR 1: HAPUS DI SERVER IMAP (KHUSUS INBOX) ===
            if ($folder === 'inbox') {
                $client = Client::account('default');
                $client->connect();
                $inboxFolder = $client->getFolder('INBOX');

                foreach ($request->ids as $id) {
                    try {
                        $message = $inboxFolder->query()->getMessageByUid((int) $id);
                        if ($message) {
                            // Coba tandai sebagai deleted dengan format yang didukung banyak server
                            $message->setFlag(['\Deleted', 'Deleted']); 
                            $deletedCount++;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Gagal menandai hapus UID $id: " . $e->getMessage());
                    }
                }
                
                // Coba bersihkan server. Jika metode ini tidak ada di versi Webklex Anda, abaikan saja
                try {
                    $client->expunge(); 
                } catch (\Exception $e) {
                    try {
                        $inboxFolder->expunge();
                    } catch (\Exception $e2) {
                        Log::warning("Fungsi Expunge tidak didukung oleh versi Webklex atau Server ini.");
                    }
                }

            } 
            // === JALUR 2: HAPUS DI DATABASE LOKAL (TERKIRIM, BERBINTANG, DLL) ===
            else {
                $deletedCount = Email::where('user_id', Auth::id())
                                     ->whereIn('id', $request->ids)
                                     ->delete();
            }

            return response()->json([
                'success' => true,
                'message' => $deletedCount . ' pesan berhasil dihapus.'
            ]);

        } catch (\Exception $e) {
            // Ini akan memastikan errornya terlihat di pop-up SweetAlert, BUKAN cuma Error 500 blank
            Log::error('Hapus Email Masal Error.', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json([
                'success' => false,
                'error' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memproses pengiriman pesan dari Form Kontak Web (Frontend) via AJAX
     * Ditambahkan tanpa mengubah method lain.
     */
    public function submitContactForm(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        try {
            $adminEmail = config('mail.from.address', 'admin@tokosancaka.com'); 
            $subject = 'Pesan Baru dari Form Kontak: ' . $validated['name'];
            
            $bodyHtml = "
                <h3>Pesan Baru dari Pengunjung Website!</h3>
                <p><strong>Nama:</strong> {$validated['name']}</p>
                <p><strong>Email:</strong> {$validated['email']}</p>
                <p><strong>Isi Pesan:</strong><br>" . nl2br(htmlspecialchars($validated['message'])) . "</p>
            ";

            Mail::html($bodyHtml, function ($message) use ($adminEmail, $subject, $validated) {
                $message->to($adminEmail)
                        ->subject($subject)
                        ->replyTo($validated['email'], $validated['name']);
            });

            Log::info('Pesan kontak frontend sukses dikirim.', ['pengirim' => $validated['email']]);

            // [UPDATE] Ubah return menjadi JSON untuk ditangkap oleh AJAX
            return response()->json([
                'success' => true,
                'message' => 'Terima kasih! Pesan Anda berhasil dikirim dan akan segera kami proses.'
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal mengirim pesan dari form kontak.', ['error' => $e->getMessage()]);
            
            // [UPDATE] Ubah return error menjadi JSON
            return response()->json([
                'success' => false,
                'message' => 'Maaf, terjadi kesalahan saat mengirim pesan: ' . $e->getMessage()
            ], 500);
        }
    }
}