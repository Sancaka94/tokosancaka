<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webklex\IMAP\Facades\Client;

class EmailController extends Controller
{
    /**
     * Helper: Membuat koneksi IMAP dinamis sesuai akun webmail user yang sedang login via API Token
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
     * Helper: Membuat mailer SMTP dinamis sesuai akun webmail user yang sedang login via API Token
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
     * Cek Status Konfigurasi Webmail User (Untuk validasi tampilan awal di Expo)
     */
    public function status()
    {
        $user = Auth::user();
        $hasWebmail = $user && method_exists($user, 'hasWebmail') ? $user->hasWebmail() : !empty($user->webmail_email);

        return response()->json([
            'success'       => true,
            'has_webmail'   => $hasWebmail,
            'webmail_email' => $user->webmail_email ?? null,
        ]);
    }

    /**
     * Mengambil daftar email (IMAP untuk Inbox, DB untuk Sent/Trash, Gabungan untuk Starred)
     */
    public function fetch(Request $request)
    {
        $folder = $request->query('folder', 'inbox');
        $search = $request->query('search', '');
        $page   = (int) $request->query('page', 1);

        Log::info('LOG LOG: [MOBILE API] Memuat daftar email.', [
            'user_id' => Auth::id(),
            'folder'  => $folder,
            'page'    => $page
        ]);

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
                        'body'         => 'Ketuk untuk memuat isi pesan...',
                        'created_at'   => !empty($message->getDate()) ? $message->getDate()[0]->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                        'read_at'      => $message->hasFlag('SEEN') ? now()->toIso8601String() : null,
                        'is_starred'   => $message->hasFlag('FLAGGED'),
                        'source'       => 'imap',
                    ];
                }

                usort($emails, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });

                return response()->json([
                    'success'      => true,
                    'emails'       => $emails,
                    'unread_count' => $inboxFolder->query()->unseen()->count(),
                    'pagination'   => [
                        'current_page' => $paginator->currentPage(),
                        'last_page'    => $paginator->lastPage(),
                        'has_more'     => $paginator->hasMorePages()
                    ]
                ]);

            } catch (\Throwable $e) {
                Log::error('LOG LOG: [MOBILE API] IMAP Fetch Error', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke IMAP: ' . $e->getMessage()
                ], 500);
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
                        'body'         => strip_tags(Str::limit($dbEmail->body, 100)),
                        'created_at'   => \Carbon\Carbon::parse($dbEmail->created_at)->format('Y-m-d H:i:s'),
                        'read_at'      => $dbEmail->read_at,
                        'is_starred'   => true,
                        'source'       => 'local',
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
                            'body'         => 'Ketuk untuk memuat isi pesan...',
                            'created_at'   => !empty($message->getDate()) ? $message->getDate()[0]->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                            'read_at'      => $message->hasFlag('SEEN') ? now()->toIso8601String() : null,
                            'is_starred'   => true,
                            'source'       => 'imap',
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::error('LOG LOG: [MOBILE API] IMAP Starred Fetch Error', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
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
                    'success'       => true,
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
                Log::error('LOG LOG: [MOBILE API] Starred Folder Fatal Error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memuat pesan berbintang: ' . $e->getMessage()
                ], 500);
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
            'success'      => true,
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
     * Mengambil detail isi email (Baca Pesan untuk WebView di Expo)
     */
    public function show(Request $request, $id)
    {
        $source = $request->query('source'); // Opsional hint dari frontend ('local' atau 'imap')
        Log::info('LOG LOG: [MOBILE API] Melihat detail email.', ['user_id' => Auth::id(), 'email_id' => $id]);

        // Cek DB Lokal Dulu (kecuali jika explicitly diminta dari IMAP)
        if ($source !== 'imap') {
            $localEmail = Email::where('user_id', Auth::id())->find($id);

            if ($localEmail) {
                if (is_null($localEmail->read_at)) {
                    $localEmail->update(['read_at' => now()]);
                }
                return response()->json([
                    'success' => true,
                    'data'    => $localEmail
                ]);
            }
        }

        // Cek Server IMAP Milik User Login
        try {
            ini_set('memory_limit', '512M');
            $user = Auth::user();
            $client = $this->getUserImapClient();
            $inboxFolder = $client->getFolder('INBOX');
            $message = $inboxFolder->query()->getMessageByUid((int) $id);

            if (!$message) {
                return response()->json(['success' => false, 'message' => 'Email tidak ditemukan'], 404);
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
                                Log::warning("LOG LOG: Gagal membuat thumbnail PDF Imagick untuk {$cleanName}: " . $e->getMessage());
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
                        'mime'      => $mime,
                        'url'       => $fileUrl,
                        'thumbnail' => $thumbUrl
                    ];

                } catch (\Exception $e) {
                    Log::warning("LOG LOG: Gagal memproses lampiran email ID {$id}: " . $e->getMessage());
                }
            }

            if (empty(trim(strip_tags($body)))) {
                $body = "<i style='color: #94a3b8; font-family: sans-serif;'>(Email ini tidak berisi teks pesan, hanya melampirkan file)</i>" . $body;
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'           => $message->getUid(),
                    'from_name'    => $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail,
                    'from_address' => $message->getFrom()[0]->mail,
                    'to_address'   => $user->webmail_email,
                    'subject'      => mb_decode_mimeheader($message->getSubject()[0] ?? '(Tanpa Subjek)'),
                    'body'         => $body,
                    'is_starred'   => $message->hasFlag('FLAGGED'),
                    'attachments'  => $attachmentsArr,
                    'created_at'   => !empty($message->getDate()) ? $message->getDate()[0]->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                    'source'       => 'imap',
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('LOG LOG: [MOBILE API] IMAP Show Detail Error', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat isi pesan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengirim Email (via SMTP Server Dinamis per User, mendukung multipart/form-data dari Expo DocumentPicker/ImagePicker)
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

            Log::info('LOG LOG: [MOBILE API] Email sukses dikirim via SMTP.', ['from' => $senderEmail, 'to' => $to]);
            return response()->json([
                'success' => true,
                'message' => 'Email berhasil dikirim!',
                'data'    => $email
            ]);

        } catch (\Exception $e) {
            Log::error('LOG LOG: [MOBILE API] SMTP Error.', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fitur Update (Bintang & Pindah Folder) - Mendukung IMAP & Lokal
     */
    public function update(Request $request, $id)
    {
        $source = $request->input('source');

        if ($source !== 'imap') {
            $localEmail = Email::where('user_id', Auth::id())->find($id);

            if ($localEmail) {
                if ($request->has('is_starred')) {
                    $localEmail->is_starred = (bool) $request->is_starred;
                }
                if ($request->has('folder')) {
                    $localEmail->folder = $request->folder;
                }
                $localEmail->save();
                return response()->json(['success' => true, 'message' => 'Status email diperbarui.']);
            }
        }

        try {
            $client = $this->getUserImapClient();
            $message = $client->getFolder('INBOX')->query()->getMessageByUid((int) $id);
            if ($message && $request->has('is_starred')) {
                $request->is_starred ? $message->setFlag('FLAGGED') : $message->unsetFlag('FLAGGED');
                return response()->json(['success' => true, 'message' => 'Status bintang diperbarui.']);
            }
        } catch (\Exception $e) {
            Log::error('LOG LOG: [MOBILE API] IMAP Flag Error', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => false, 'message' => 'Email tidak ditemukan atau gagal diproses.'], 404);
    }

    /**
     * Mencari pengguna untuk auto-complete tujuan email di Expo
     */
    public function searchUsers(Request $request)
    {
        $term = $request->query('q');

        if (empty($term)) {
            return response()->json(['success' => true, 'data' => []]);
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

        return response()->json([
            'success' => true,
            'data'    => $users
        ]);
    }

    /**
     * Menghapus email (Mendukung hapus satuan maupun masal)
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
                        Log::warning("LOG LOG: [MOBILE API] Gagal menandai hapus UID $id: " . $e->getMessage());
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
            Log::error('LOG LOG: [MOBILE API] Hapus Email Error.', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }
}
