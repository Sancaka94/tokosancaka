<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webklex\IMAP\Facades\Client; // Facade Webklex IMAP

class EmailController extends Controller
{
    /**
     * Helper: Membuat koneksi IMAP dinamis sesuai akun webmail user yang sedang login
     */
    private function getUserImapClient()
    {
        $user = Auth::user();

        if (!$user || !$user->hasWebmail()) {
            throw new \Exception('Akun Anda belum terhubung dengan alamat email @tokosancaka.com.');
        }

        $mailPassword = $user->getDecryptedWebmailPassword();
        if (empty($mailPassword)) {
            throw new \Exception('Kredensial password email tidak valid atau gagal didekripsi.');
        }

        $client = Client::make([
            'host'          => config('imap.accounts.default.host', 'server4.hostdata.id'),
            'port'          => (int) config('imap.accounts.default.port', 993),
            'encryption'    => config('imap.accounts.default.encryption', 'ssl'),
            'validate_cert' => config('imap.accounts.default.validate_cert', true),
            'username'      => $user->webmail_email,
            'password'      => $mailPassword,
            'protocol'      => 'imap',
        ]);

        $client->connect();

        return $client;
    }

    /**
     * Helper: Membuat mailer SMTP dinamis sesuai akun webmail user yang sedang login
     */
    private function getUserMailer()
    {
        $user = Auth::user();

        if (!$user || !$user->hasWebmail()) {
            throw new \Exception('Akun Anda belum memiliki konfigurasi email pengirim (@tokosancaka.com).');
        }

        $mailPassword = $user->getDecryptedWebmailPassword();
        if (empty($mailPassword)) {
            throw new \Exception('Kredensial SMTP email tidak valid.');
        }

        return Mail::build([
            'transport'  => 'smtp',
            'host'       => config('mail.mailers.smtp.host', 'server4.hostdata.id'),
            'port'       => (int) config('mail.mailers.smtp.port', 465),
            'encryption' => config('mail.mailers.smtp.scheme', 'ssl'),
            'username'   => $user->webmail_email,
            'password'   => $mailPassword,
        ]);
    }

    /**
     * Menampilkan halaman Blade Kotak Masuk
     */
    public function index()
    {
        Log::info('Akses halaman Kotak Masuk Email.', ['user_id' => Auth::id()]);
        return view('admin.email.index');
    }

    /**
     * Mengambil daftar email (IMAP untuk Inbox, DB untuk lainnya, Gabungan untuk Berbintang)
     */
    public function fetch(Request $request)
    {
        $folder = $request->query('folder', 'inbox');
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);

        Log::info('Memuat daftar email.', ['user_id' => Auth::id(), 'folder' => $folder, 'page' => $page]);

        // =========================================================
        // SKENARIO 1: KOTAK MASUK (MURNI DARI IMAP SERVER PER USER)
        // =========================================================
        if ($folder === 'inbox') {
            ini_set('memory_limit', '512M');
            try {
                $client = $this->getUserImapClient();
                $inboxFolder = $client->getFolder('INBOX');

                $query = $inboxFolder->query()->setFetchBody(false);

                if (!empty($search)) {
                    $query = $query->text($search);
                } else {
                    $query = $query->all();
                }

                $paginator = $query->setFetchOrder('desc')->paginate(15, $page, 'page');

                $emails = [];
                foreach ($paginator as $message) {
                    $emails[] = [
                        'id'           => $message->getUid(),
                        'from_name'    => $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail ?? 'Unknown',
                        'from_address' => $message->getFrom()[0]->mail ?? 'Unknown',
                        'subject'      => mb_decode_mimeheader($message->getSubject()[0] ?? '(Tanpa Subjek)'),
                        'body'         => 'Klik untuk memuat isi pesan...',
                        'created_at'   => !empty($message->getDate()) ? $message->getDate()[0]->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                        'read_at'      => $message->hasFlag('SEEN') ? now() : null,
                        'is_starred'   => $message->hasFlag('FLAGGED'),
                    ];
                }

                usort($emails, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });

                return response()->json([
                    'emails'       => $emails,
                    'unread_count' => $inboxFolder->query()->unseen()->count(),
                    'pagination'   => [
                        'current_page' => $paginator->currentPage(),
                        'last_page'    => $paginator->lastPage(),
                        'has_more'     => $paginator->hasMorePages()
                    ]
                ]);

            } catch (\Throwable $e) {
                Log::error('IMAP Fetch Error', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
                return response()->json(['error' => 'Gagal terhubung ke IMAP: ' . $e->getMessage()], 500);
            }
        }

        // =========================================================
        // SKENARIO 2: BERBINTANG (GABUNGAN DARI IMAP & LOKAL DB)
        // =========================================================
        if ($folder === 'starred') {
            try {
                $emails = [];

                // A. Ambil dari DB Lokal (Pesan Terkirim yang dibintangi)
                $localQuery = Email::where('user_id', Auth::id())->where('is_starred', true);
                if (!empty($search)) {
                    $localQuery->where(function ($q) use ($search) {
                        $q->where('subject', 'like', "%{$search}%")
                          ->orWhere('from_name', 'like', "%{$search}%")
                          ->orWhere('to_address', 'like', "%{$search}%");
                    });
                }
                $localData = $localQuery->orderBy('created_at', 'desc')->get();

                foreach ($localData as $dbEmail) {
                    $emails[] = [
                        'id'           => $dbEmail->id,
                        'from_name'    => $dbEmail->from_name,
                        'from_address' => $dbEmail->from_address,
                        'to_address'   => $dbEmail->to_address,
                        'subject'      => $dbEmail->subject,
                        'body'         => $dbEmail->body,
                        'created_at'   => \Carbon\Carbon::parse($dbEmail->created_at)->format('Y-m-d H:i:s'),
                        'read_at'      => $dbEmail->read_at,
                        'is_starred'   => true,
                    ];
                }

                // B. Ambil dari IMAP Server (Pesan Inbox yang dibintangi)
                try {
                    $client = $this->getUserImapClient();
                    $inboxFolder = $client->getFolder('INBOX');

                    $imapQuery = $inboxFolder->query()->where('FLAGGED');

                    if (!empty($search)) {
                        $imapQuery = $imapQuery->text($search);
                    }

                    $imapData = $imapQuery->setFetchOrder('desc')->limit(50)->get();

                    foreach ($imapData as $message) {
                        $emails[] = [
                            'id'           => $message->getUid(),
                            'from_name'    => $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail ?? 'Unknown',
                            'from_address' => $message->getFrom()[0]->mail ?? 'Unknown',
                            'subject'      => mb_decode_mimeheader($message->getSubject()[0] ?? '(Tanpa Subjek)'),
                            'body'         => 'Klik untuk memuat isi pesan...',
                            'created_at'   => !empty($message->getDate()) ? $message->getDate()[0]->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                            'read_at'      => $message->hasFlag('SEEN') ? now() : null,
                            'is_starred'   => true,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::error('IMAP Starred Fetch Error', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
                }

                // C. Urutkan gabungan berdasarkan waktu terbaru
                usort($emails, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });

                // D. Manual Pagination untuk hasil gabungan
                $total = count($emails);
                $perPage = 15;
                $lastPage = ceil($total / $perPage);
                $offset = ($page - 1) * $perPage;
                $pagedEmails = array_slice($emails, $offset, $perPage);

                return response()->json([
                    'emails'        => array_values($pagedEmails),
                    'unread_count'  => 0,
                    'starred_count' => $total,
                    'pagination'    => [
                        'current_page' => $page,
                        'last_page'    => $lastPage > 0 ? $lastPage : 1,
                        'has_more'     => $page < $lastPage
                    ]
                ]);
            } catch (\Throwable $e) {
                Log::error('Starred Folder Fatal Error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
                return response()->json(['error' => 'Gagal memuat pesan berbintang: ' . $e->getMessage()], 500);
            }
        }

        // =========================================================
        // SKENARIO 3: KOTAK TERKIRIM & LAINNYA (MURNI DB LOKAL)
        // =========================================================
        $query = Email::where('user_id', Auth::id())->where('folder', $folder);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('from_name', 'like', "%{$search}%")
                  ->orWhere('to_address', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate(15, ['*'], 'page', $page);

        return response()->json([
            'emails'       => $paginator->items(),
            'unread_count' => 0,
            'pagination'   => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'has_more'     => $paginator->hasMorePages()
            ]
        ]);
    }

    /**
     * Mengambil detail isi email (Baca Pesan)
     */
    public function show($id)
    {
        Log::info('Melihat detail email.', ['user_id' => Auth::id(), 'email_id' => $id]);

        // Cek DB Lokal Dulu
        $localEmail = Email::where('user_id', Auth::id())->find($id);

        if ($localEmail) {
            if (is_null($localEmail->read_at)) {
                $localEmail->update(['read_at' => now()]);
            }
            return response()->json($localEmail);
        }

        // Cek Server IMAP Milik User Login
        try {
            ini_set('memory_limit', '512M');
            $user = Auth::user();
            $client = $this->getUserImapClient();
            $inboxFolder = $client->getFolder('INBOX');
            $message = $inboxFolder->query()->getMessageByUid((int) $id);

            if (!$message) {
                return response()->json(['error' => 'Email tidak ditemukan'], 404);
            }

            if (!$message->hasFlag('SEEN')) {
                $message->setFlag('SEEN');
            }

            $body = $message->getHTMLBody() ?? $message->getTextBody() ?? '';
            $attachmentsArr = [];
            $userId = Auth::id();

            $attachments = $message->getAttachments();

            foreach ($attachments as $attachment) {
                try {
                    $name = $attachment->getName() ?? 'Lampiran_Tanpa_Nama';
                    $mime = $attachment->getMimeType() ?? 'application/octet-stream';
                    $contentId = $attachment->getContentId();
                    $cleanCid = $contentId ? str_replace(['<', '>'], '', $contentId) : null;

                    $disposition = strtolower($attachment->getDisposition() ?? 'attachment');

                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $filenameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
                    $cleanName = Str::slug($filenameWithoutExt, '_') . ($extension ? '.' . strtolower($extension) : '');

                    // Isolasi folder lampiran per user_id agar tidak bentrok antar akun
                    $folderPath = 'public/email_attachments/' . $userId . '/' . $id;
                    $absoluteFolderPath = storage_path('app/' . $folderPath);

                    if (!Storage::exists($folderPath)) {
                        Storage::makeDirectory($folderPath, 0755, true);
                        @chmod($absoluteFolderPath, 0755);
                    }

                    $path = $folderPath . '/' . $cleanName;
                    $thumbName = md5($cleanName) . '_thumb.jpg';
                    $thumbRelPath = $folderPath . '/' . $thumbName;
                    $absoluteFilePath = storage_path('app/' . $path);

                    if (!Storage::exists($path)) {
                        $fileContent = $attachment->getContent();
                        Storage::put($path, $fileContent);
                        @chmod($absoluteFilePath, 0644);

                        if (strtolower($extension) === 'pdf') {
                            try {
                                $pdfAbsPath = storage_path('app/' . $path);
                                $thumbAbsPath = storage_path('app/' . $thumbRelPath);

                                $imagick = new \Imagick();
                                $imagick->setResolution(300, 300);
                                $imagick->readImage($pdfAbsPath . '[0]');
                                $imagick->setImageBackgroundColor('white');
                                $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                                $imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                                $imagick->thumbnailImage(600, 0);
                                $imagick->setImageFormat('jpg');
                                $imagick->setImageCompressionQuality(85);
                                $imageBlob = $imagick->getImageBlob();
                                Storage::put($thumbRelPath, $imageBlob);
                                @chmod($thumbAbsPath, 0644);

                                $imagick->clear();
                                $imagick->destroy();
                            } catch (\Throwable $e) {
                                Log::warning("Gagal membuat thumbnail PDF Imagick untuk {$cleanName}: " . $e->getMessage());
                            }
                        }
                    }

                    $fileUrl = asset('storage/email_attachments/' . $userId . '/' . $id . '/' . $cleanName);
                    $thumbUrl = str_starts_with($mime, 'image/') ? $fileUrl : null;

                    if (strtolower($extension) === 'pdf' && Storage::exists($thumbRelPath)) {
                        $thumbUrl = asset('storage/email_attachments/' . $userId . '/' . $id . '/' . $thumbName);
                    }

                    if ($cleanCid && str_contains($body, 'cid:' . $cleanCid)) {
                        $body = str_replace('cid:' . $cleanCid, $fileUrl, $body);
                    } elseif (($disposition === 'inline' || $cleanCid) && str_starts_with($mime, 'image/')) {
                        $body .= "
                        <div style='margin-top: 20px; padding: 10px; background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; display: inline-block;'>
                            <img src='{$fileUrl}' style='max-width: 100%; height: auto; border-radius: 4px;' alt='{$cleanName}'>
                        </div><br>";
                    }

                    $attachmentsArr[] = [
                        'name'      => $cleanName,
                        'url'       => $fileUrl,
                        'thumbnail' => $thumbUrl
                    ];

                } catch (\Exception $e) {
                    Log::warning("Gagal memproses lampiran email ID {$id}: " . $e->getMessage());
                }
            }

            if (empty(trim(strip_tags($body)))) {
                $body = "<i style='color: #94a3b8; font-family: sans-serif;'>(Email ini tidak berisi teks pesan, hanya melampirkan file)</i>" . $body;
            }

            return response()->json([
                'id'           => $message->getUid(),
                'from_name'    => $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail,
                'from_address' => $message->getFrom()[0]->mail,
                'to_address'   => $user->webmail_email,
                'subject'      => mb_decode_mimeheader($message->getSubject()[0] ?? '(Tanpa Subjek)'),
                'body'         => $body,
                'is_starred'   => $message->hasFlag('FLAGGED'),
                'attachments'  => $attachmentsArr,
                'created_at'   => !empty($message->getDate()) ? $message->getDate()[0]->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            ]);

        } catch (\Throwable $e) {
            Log::error('IMAP Show Detail Error', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['error' => 'Gagal memuat isi pesan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengirim Email (via SMTP Server Dinamis per User)
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'to'            => 'required|email',
            'subject'       => 'required|string|max:255',
            'body'          => 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        try {
            $user = Auth::user();
            $senderEmail = $user->webmail_email;
            $senderName = $user->nama_lengkap ?? $senderEmail;

            $bodyHtml = $validated['body'];
            $subject = $validated['subject'];
            $to = $validated['to'];
            $attachments = $request->file('attachments');

            $mailer = $this->getUserMailer();

            $mailer->html($bodyHtml, function ($message) use ($to, $subject, $attachments, $senderEmail, $senderName) {
                $message->to($to)
                        ->subject($subject)
                        ->from($senderEmail, $senderName);

                if (!empty($attachments)) {
                    foreach ($attachments as $file) {
                        $message->attach($file->getRealPath(), [
                            'as'   => $file->getClientOriginalName(),
                            'mime' => $file->getClientMimeType(),
                        ]);
                    }
                }
            });

            $email = Email::create([
                'user_id'      => Auth::id(),
                'folder'       => 'sent',
                'from_name'    => $senderName,
                'from_address' => $senderEmail,
                'to_address'   => $to,
                'subject'      => $subject,
                'body'         => $bodyHtml,
                'is_starred'   => false,
                'read_at'      => now(),
            ]);

            Log::info('Email sukses dikirim via SMTP dengan lampiran.', ['from' => $senderEmail, 'to' => $to]);
            return response()->json(['success' => true, 'message' => 'Email berhasil dikirim!']);

        } catch (\Exception $e) {
            Log::error('SMTP Error.', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
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
            if ($request->has('is_starred')) {
                $localEmail->is_starred = $request->is_starred;
            }
            if ($request->has('folder')) {
                $localEmail->folder = $request->folder;
            }
            $localEmail->save();
            return response()->json(['success' => true]);
        } else {
            try {
                $client = $this->getUserImapClient();
                $message = $client->getFolder('INBOX')->query()->getMessageByUid((int) $id);
                if ($message && $request->has('is_starred')) {
                    $request->is_starred ? $message->setFlag('FLAGGED') : $message->unsetFlag('FLAGGED');
                    return response()->json(['success' => true]);
                }
            } catch (\Exception $e) {
                Log::error('IMAP Flag Error', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
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

        $users = DB::table('Pengguna')
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($term) {
                        $q->where('nama_lengkap', 'like', "%{$term}%")
                          ->orWhere('webmail_email', 'like', "%{$term}%")
                          ->orWhere('email', 'like', "%{$term}%")
                          ->orWhere('no_wa', 'like', "%{$term}%");
                    })
                    ->select(
                        'id_pengguna',
                        'nama_lengkap',
                        DB::raw('COALESCE(webmail_email, email) as email'),
                        'no_wa',
                        'role'
                    )
                    ->limit(10)
                    ->get();

        return response()->json($users);
    }

    /**
     * Menghapus email secara masal (Mendukung IMAP & Lokal secara terpisah)
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'folder' => 'required|string',
        ]);

        $deletedCount = 0;
        $folder = $request->folder;

        try {
            // === JALUR 1: HAPUS DI SERVER IMAP (KHUSUS INBOX) ===
            if ($folder === 'inbox') {
                ini_set('memory_limit', '512M');
                $client = $this->getUserImapClient();
                $inboxFolder = $client->getFolder('INBOX');

                foreach ($request->ids as $id) {
                    try {
                        $message = $inboxFolder->query()->getMessageByUid((int) $id);
                        if ($message) {
                            $message->delete();
                            $deletedCount++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Gagal menandai hapus UID $id: " . $e->getMessage());
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

        } catch (\Throwable $e) {
            Log::error('Hapus Email Masal Error.', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json([
                'success' => false,
                'error'   => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }

    public function submitContactForm(Request $request)
    {
        $throttleKey = 'contact-form:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning('Spam form kontak diblokir.', ['ip' => $request->ip()]);
            return response()->json(['success' => false, 'message' => "Tunggu {$seconds} detik sebelum mengirim pesan lagi."], 429);
        }

        $validated = $request->validate([
            'name'                  => 'required|string|max:100|regex:/^[a-zA-Z\s]+$/',
            'email'                 => 'required|email:rfc,dns|max:100',
            'message'               => 'required|string|max:2000',
            'latitude'              => 'nullable|string',
            'longitude'             => 'nullable|string',
            'cf-turnstile-response' => 'required|string',
        ]);

        RateLimiter::hit($throttleKey, 60);

        try {
            $adminEmail = config('mail.from.address', 'admin@tokosancaka.com');
            $subject = 'Pesan Baru dari Form Kontak: ' . $validated['name'];

            $safeName = htmlspecialchars($validated['name'], ENT_QUOTES, 'UTF-8');
            $safeEmail = htmlspecialchars($validated['email'], ENT_QUOTES, 'UTF-8');
            $safeMessage = nl2br(htmlspecialchars($validated['message'], ENT_QUOTES, 'UTF-8'));
            $ipAddress = $request->ip();
            $date = now()->format('d M Y, H:i');

            $mapsLink = "Lokasi Tidak Diketahui";
            if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
                $mapsLink = "<a href='https://www.google.com/maps?q={$validated['latitude']},{$validated['longitude']}' target='_blank' style='color: #2563eb; text-decoration: none; font-weight: 600;'>Lihat di Google Maps</a>";
            }

            $bodyHtml = "
            <div style='font-family: Helvetica, Arial, sans-serif; background-color: #f3f4f6; padding: 40px 20px; margin: 0;'>
                <div style='max-width: 600px; background-color: #ffffff; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>

                    <div style='background: linear-gradient(135deg, #dc2626, #991b1b); color: #ffffff; padding: 30px 20px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 1px;'>SANCAKA EXPRESS</h2>
                        <p style='margin: 8px 0 0 0; font-size: 14px; color: #fca5a5;'>Pesan Baru dari Pengunjung Website</p>
                    </div>

                    <div style='padding: 35px 30px;'>
                        <h3 style='color: #1f2937; font-size: 16px; font-weight: 700; margin: 0 0 15px 0; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;'>Informasi Pengirim</h3>
                        <table style='width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 15px;'>
                            <tr><td style='padding: 10px 0; color: #6b7280; width: 120px; font-weight: 600;'>Nama</td><td style='padding: 10px 0; color: #111827; font-weight: 600;'>: {$safeName}</td></tr>
                            <tr><td style='padding: 10px 0; color: #6b7280; font-weight: 600;'>Email</td><td style='padding: 10px 0;'>: <a href='mailto:{$safeEmail}' style='color: #2563eb; text-decoration: none; font-weight: 600;'>{$safeEmail}</a></td></tr>
                            <tr><td style='padding: 10px 0; color: #6b7280; font-weight: 600;'>Lokasi GPS</td><td style='padding: 10px 0;'>: {$mapsLink}</td></tr>
                        </table>

                        <h3 style='color: #1f2937; font-size: 16px; font-weight: 700; margin: 0 0 15px 0; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;'>Isi Pesan</h3>
                        <div style='background-color: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #dc2626; color: #374151; line-height: 1.8; font-size: 15px;'>{$safeMessage}</div>
                    </div>

                    <div style='background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6;'>
                        <p style='margin: 0;'>Pesan tervalidasi oleh sistem Anti-Spam Sancaka.</p>
                        <p style='margin: 6px 0 0 0; font-family: monospace;'>IP Pengirim: {$ipAddress} | Waktu: {$date} WIB</p>
                    </div>
                </div>
            </div>";

            Mail::html($bodyHtml, function ($message) use ($adminEmail, $subject, $validated) {
                $message->to($adminEmail)->subject($subject)->replyTo($validated['email'], $validated['name']);
            });

            // --- EKSEKUSI PUSH NOTIFIKASI KE EXPO HP ADMIN ---
            $this->notifyAdminEmailBaru($safeName, $subject);
            // -------------------------------------------------

            Log::info('Pesan kontak frontend sukses dikirim.', ['pengirim' => $validated['email'], 'ip' => $request->ip()]);

            return response()->json(['success' => true, 'message' => 'Terima kasih! Pesan Anda berhasil dikirim.']);

        } catch (\Exception $e) {
            // LOG LOG tetap dilestarikan!
            Log::error('Gagal mengirim pesan dari form kontak.', ['error' => $e->getMessage(), 'ip' => $request->ip()]);
            return response()->json(['success' => false, 'message' => 'Kendala server. Coba beberapa saat lagi.'], 500);
        }
    }

    /**
     * =========================================================================
     * PUSH NOTIFICATION EMAIL BARU KE ADMIN (FCM V1)
     * =========================================================================
     */
    private function notifyAdminEmailBaru($namaPengirim, $subjek)
    {
        try {
            $adminId = 4;
            $admin = DB::table('Pengguna')
                        ->where('id_pengguna', $adminId)
                        ->select('fcm_token', 'fcm_token_debug')
                        ->first();

            if (!$admin) return;

            $title = '📧 Pesan Masuk Baru!';
            $body = "Dari {$namaPengirim}: {$subjek}";

            $accessToken = $this->getGoogleAccessToken();
            $projectId = 'sancaka-express';

            $tokensToTry = [];
            if (!empty($admin->fcm_token)) $tokensToTry[] = $admin->fcm_token;
            if (!empty($admin->fcm_token_debug)) $tokensToTry[] = $admin->fcm_token_debug;

            if ($accessToken && count($tokensToTry) > 0) {
                foreach ($tokensToTry as $tokenStr) {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type'  => 'application/json',
                    ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $tokenStr,
                            'notification' => [
                                'title' => $title,
                                'body'  => $body
                            ],
                            'webpush' => [
                                'fcm_options' => [
                                    'link' => url('/admin/email')
                                ]
                            ],
                            'data' => [
                                'action' => 'new_email_inbox'
                            ]
                        ]
                    ]);

                    if ($response->successful()) {
                        Log::info("LOG LOG: [NOTIF EMAIL] Berhasil kirim Push Notif ke Admin ID 4 untuk pesan dari {$namaPengirim}");
                        break;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("LOG LOG: [NOTIF EMAIL ERROR] " . $e->getMessage());
        }
    }

    /**
     * Helper: Generate Access Token FCM V1
     */
    private function getGoogleAccessToken()
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $jsonKeyPath = storage_path('app/firebase-auth.json');

            if (!file_exists($jsonKeyPath)) {
                Log::error("FCM Token: File firebase-auth.json tidak ditemukan di storage/app/");
                return null;
            }

            $keyData = json_decode(file_get_contents($jsonKeyPath), true);
            if (!$keyData || !isset($keyData['private_key'])) return null;

            try {
                $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
                $now = time();
                $claim = json_encode([
                    'iss'   => $keyData['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud'   => 'https://oauth2.googleapis.com/token',
                    'exp'   => $now + 3600,
                    'iat'   => $now
                ]);

                $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
                $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));

                $signature = '';
                openssl_sign($base64UrlHeader . '.' . $base64UrlClaim, $signature, $keyData['private_key'], 'SHA256');
                $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

                $jwt = $base64UrlHeader . '.' . $base64UrlClaim . '.' . $base64UrlSignature;

                $response = Http::timeout(5)->asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt
                ]);

                if ($response->successful() && $response->json('access_token')) {
                    return $response->json('access_token');
                }
            } catch (\Throwable $th) {
                Log::warning("FCM Token Gagal: " . $th->getMessage());
            }

            return null;
        });
    }
}
