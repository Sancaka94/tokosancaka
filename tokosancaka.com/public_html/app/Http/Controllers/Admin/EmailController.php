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
     * Mengambil daftar email (IMAP untuk Inbox, DB untuk lainnya, Gabungan untuk Berbintang)
     */
    public function fetch(Request $request)
    {
        $folder = $request->query('folder', 'inbox');
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);

        Log::info('Memuat daftar email.', ['user_id' => Auth::id(), 'folder' => $folder, 'page' => $page]);

        // =========================================================
        // SKENARIO 1: KOTAK MASUK (MURNI DARI IMAP SERVER)
        // =========================================================
        if ($folder === 'inbox') {
            try {
                $client = Client::account('default');
                $client->connect();
                $inboxFolder = $client->getFolder('INBOX');

                $query = $inboxFolder->query();

                if (!empty($search)) {
                    $query = $query->text($search);
                } else {
                    $query = $query->all();
                }

                $paginator = $query->setFetchOrder('desc')->paginate(15, $page, 'page');

                $emails = [];
                foreach($paginator as $message){
                    $emails[] = [
                        'id' => $message->getUid(),
                        'from_name' => $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail ?? 'Unknown',
                        'from_address' => $message->getFrom()[0]->mail ?? 'Unknown',
                        'subject' => mb_decode_mimeheader($message->getSubject()[0] ?? '(Tanpa Subjek)'),
                        'body' => 'Pesan belum dimuat sepenuhnya...',
                        // PERBAIKAN: Pengecekan aman header tanggal agar tidak terjadi error format() pada null
                        'created_at' => !empty($message->getDate()) ? $message->getDate()[0]->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                        'read_at' => $message->hasFlag('SEEN') ? now() : null,
                        'is_starred' => $message->hasFlag('FLAGGED'),
                    ];
                }

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

            } catch (\Throwable $e) { // Menggunakan Throwable untuk menangkap semua level error
                Log::error('IMAP Fetch Error', ['error' => $e->getMessage()]);
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
                    $localQuery->where(function($q) use ($search) {
                        $q->where('subject', 'like', "%{$search}%")
                          ->orWhere('from_name', 'like', "%{$search}%");
                    });
                }
                $localData = $localQuery->orderBy('created_at', 'desc')->get();

                foreach($localData as $dbEmail) {
                    $emails[] = [
                        'id' => $dbEmail->id,
                        'from_name' => $dbEmail->from_name,
                        'from_address' => $dbEmail->from_address,
                        'subject' => $dbEmail->subject,
                        'body' => $dbEmail->body,
                        // PERBAIKAN: Gunakan Carbon::parse untuk menghindari error format() on string
                        'created_at' => \Carbon\Carbon::parse($dbEmail->created_at)->format('Y-m-d H:i:s'),
                        'read_at' => $dbEmail->read_at,
                        'is_starred' => true,
                    ];
                }

                // B. Ambil dari IMAP Server (Pesan Inbox yang dibintangi)
                try {
                    $client = Client::account('default');
                    $client->connect();
                    $inboxFolder = $client->getFolder('INBOX');

                    // PERBAIKAN: Menggunakan format where('FLAGGED') untuk sintaks Webklex IMAP yang valid
                    $imapQuery = $inboxFolder->query()->where('FLAGGED');

                    if (!empty($search)) {
                        $imapQuery = $imapQuery->text($search);
                    }

                    $imapData = $imapQuery->setFetchOrder('desc')->limit(50)->get();

                    foreach($imapData as $message){
                        $emails[] = [
                            'id' => $message->getUid(),
                            'from_name' => $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail ?? 'Unknown',
                            'from_address' => $message->getFrom()[0]->mail ?? 'Unknown',
                            'subject' => mb_decode_mimeheader($message->getSubject()[0] ?? '(Tanpa Subjek)'),
                            'body' => 'Pesan belum dimuat sepenuhnya...',
                            // PERBAIKAN: Pengecekan aman header tanggal IMAP
                            'created_at' => !empty($message->getDate()) ? $message->getDate()[0]->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                            'read_at' => $message->hasFlag('SEEN') ? now() : null,
                            'is_starred' => true,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::error('IMAP Starred Fetch Error', ['error' => $e->getMessage()]);
                }

                // C. Urutkan gabungan berdasarkan waktu terbaru
                usort($emails, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });

                // D. Manual Pagination untuk hasil gabungan
                $total = count($emails);
                $perPage = 15;
                $lastPage = ceil($total / $perPage);
                $offset = ($page - 1) * $perPage;
                $pagedEmails = array_slice($emails, $offset, $perPage);

                return response()->json([
                    'emails' => array_values($pagedEmails), // array_values memastikan format respons JSON aman
                    'unread_count' => 0,
                    'starred_count' => $total,
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => $lastPage > 0 ? $lastPage : 1,
                        'has_more' => $page < $lastPage
                    ]
                ]);
            } catch (\Throwable $e) {
                // PERBAIKAN: Menangkap error utama agar tidak memicu 500 Internal Error di browser console
                Log::error('Starred Folder Fatal Error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
                return response()->json(['error' => 'Gagal memuat pesan berbintang: ' . $e->getMessage()], 500);
            }
        }

        // =========================================================
        // SKENARIO 3: KOTAK TERKIRIM & LAINNYA (MURNI DB LOKAL)
        // =========================================================
        $query = Email::where('user_id', Auth::id())->where('folder', $folder);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('from_name', 'like', "%{$search}%");
            });
        }

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
                $message->setFlag('SEEN');
            }

            $body = $message->getHTMLBody() ?? $message->getTextBody() ?? '';
            $attachmentsArr = [];

            // 2. Proses lampiran menggunakan method standar Webklex
            $attachments = $message->getAttachments();

            foreach ($attachments as $attachment) {
                try {
                    $name = $attachment->getName() ?? 'Lampiran_Tanpa_Nama';
                    $mime = $attachment->getMimeType() ?? 'application/octet-stream';

                    $contentId = $attachment->getContentId();
                    $cleanCid = $contentId ? str_replace(['<', '>'], '', $contentId) : null;

                    // A. Jika Inline Image (E-Signature) -> Ubah ke Base64 (Karena ukurannya kecil)
                    if ($cleanCid && (str_contains($mime, 'image') || str_contains($body, 'cid:' . $cleanCid))) {
                        $base64 = base64_encode($attachment->getContent());
                        $body = str_replace('cid:' . $cleanCid, "data:{$mime};base64,{$base64}", $body);
                    }
                    // B. Jika File Dokumen Fisik (PDF, Excel, dll) -> Simpan ke Storage & Buat Thumbnail PDF
                    else {
                        // 1. BERSihkan Nama File dari Spasi dan Tanda Kurung
                        $extension = pathinfo($name, PATHINFO_EXTENSION);
                        $filenameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
                        // Mengubah "File (1) (2).pdf" menjadi "file_1_2.pdf"
                        $cleanName = \Illuminate\Support\Str::slug($filenameWithoutExt, '_') . '.' . strtolower($extension);

                        // 2. Buat folder dengan permission 0755 secara eksplisit
                        $folderPath = 'public/email_attachments/' . $id;
                        if (!\Illuminate\Support\Facades\Storage::exists($folderPath)) {
                            \Illuminate\Support\Facades\Storage::makeDirectory($folderPath, 0755, true);
                        }

                        // Deklarasikan Path & Nama File
                        $path = $folderPath . '/' . $cleanName;
                        $thumbName = md5($cleanName) . '_thumb.jpg';
                        $thumbRelPath = $folderPath . '/' . $thumbName;

                        // ========================================================
                        // 🔥 LOGIKA PENCEGAH DOBEL: CEK APAKAH FILE SUDAH ADA 🔥
                        // ========================================================
                        if (!\Illuminate\Support\Facades\Storage::exists($path)) {

                            // Jika belum ada, BARU KITA DOWNLOAD isinya dari server IMAP
                            $fileContent = $attachment->getContent();
                            \Illuminate\Support\Facades\Storage::put($path, $fileContent);

                            // --- LOGIKA PEMBUATAN THUMBNAIL PDF (NATIVE IMAGICK) ---
                            if (strtolower($extension) === 'pdf') {
                                try {
                                    $pdfAbsPath = storage_path('app/' . $path);
                                    $thumbAbsPath = storage_path('app/' . $thumbRelPath);

                                    // Gunakan Native Imagick
                                    $imagick = new \Imagick();

                                    // Render dengan resolusi super tajam (300 DPI) SEBELUM membaca file
                                    $imagick->setResolution(300, 300);
                                    $imagick->readImage($pdfAbsPath . '[0]');

                                    // Beri background putih padat & gabungkan layar (Flatten)
                                    $imagick->setImageBackgroundColor('white');
                                    $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                                    $imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);

                                    // Resize ke ukuran ideal web
                                    $imagick->thumbnailImage(600, 0);

                                    $imagick->setImageFormat('jpg');
                                    $imagick->setImageCompressionQuality(85);

                                    // Simpan menggunakan Laravel Storage
                                    $imageBlob = $imagick->getImageBlob();
                                    \Illuminate\Support\Facades\Storage::put($thumbRelPath, $imageBlob);

                                    $imagick->clear();
                                    $imagick->destroy();

                                } catch (\Throwable $e) {
                                    Log::warning("Gagal membuat thumbnail PDF Imagick untuk {$cleanName}: " . $e->getMessage());
                                }
                            }
                        }

                        // Set URL untuk dikembalikan ke Response JSON
                        $fileUrl = asset('storage/email_attachments/' . $id . '/' . $cleanName);
                        $thumbUrl = null;

                        // Jika ekstensinya PDF dan file thumbnailnya beneran ada, set URL thumbnailnya
                        if (strtolower($extension) === 'pdf' && \Illuminate\Support\Facades\Storage::exists($thumbRelPath)) {
                            $thumbUrl = asset('storage/email_attachments/' . $id . '/' . $thumbName);
                        }

                        $attachmentsArr[] = [
                            'name'      => $cleanName, // Tampilkan nama yang sudah bersih
                            'url'       => $fileUrl,
                            'thumbnail' => $thumbUrl
                        ];
                    }
                } catch (\Exception $e) {
                    // LOG LOG tetap dilestarikan: Jika 1 file error, email tetap terbuka!
                    Log::warning("Gagal memproses lampiran email ID {$id}: " . $e->getMessage());
                }
            }

            return response()->json([
                'id' => $message->getUid(),
                'from_name' => $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail,
                'from_address' => $message->getFrom()[0]->mail,
                'subject' => mb_decode_mimeheader($message->getSubject()[0] ?? '(Tanpa Subjek)'),
                'body' => $body,
                'attachments' => $attachmentsArr,
                // Pencegahan error parsing format tanggal pada versi Webklex tertentu
                'created_at' => !empty($message->getDate()) ? $message->getDate()[0]->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            ]);

        } catch (\Throwable $e) { // PERBAIKAN: Ubah \Exception menjadi \Throwable
            Log::error('IMAP Show Detail Error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
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
                            // PERBAIKAN: Menggunakan fungsi delete() bawaan Webklex.
                            // Secara otomatis akan memberikan flag \Deleted dengan aman.
                            $message->delete();
                            $deletedCount++;
                        }
                    } catch (\Throwable $e) { // Tangkap error internal per email
                        Log::warning("Gagal menandai hapus UID $id: " . $e->getMessage());
                    }
                }

                // BLOK EXPUNGE() TELAH DIHAPUS DARI SINI AGAR TIDAK CRASH LOG LAGI
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
            // Ini akan memastikan errornya terlihat di pop-up SweetAlert, BUKAN cuma Error 500 blank
            Log::error('Hapus Email Masal Error.', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json([
                'success' => false,
                'error' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }

    public function submitContactForm(Request $request)
    {
        // 1. KEAMANAN: IDEMPOTENCY / RATE LIMITING
        $throttleKey = 'contact-form:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            Log::warning('Spam form kontak diblokir.', ['ip' => $request->ip()]);
            return response()->json(['success' => false, 'message' => "Tunggu {$seconds} detik sebelum mengirim pesan lagi."], 429);
        }

        // 2. KEAMANAN: VALIDASI KETAT (Termasuk menolak payload Cloudflare kosong)
        $validated = $request->validate([
            'name'                  => 'required|string|max:100|regex:/^[a-zA-Z\s]+$/',
            'email'                 => 'required|email:rfc,dns|max:100',
            'message'               => 'required|string|max:2000',
            'latitude'              => 'nullable|string',
            'longitude'             => 'nullable|string',
            'cf-turnstile-response' => 'required|string', // Tolak jika bot bypass script JS
        ]);

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        try {
            $adminEmail = config('mail.from.address', 'admin@tokosancaka.com');
            $subject = 'Pesan Baru dari Form Kontak: ' . $validated['name'];

            // 3. SANITASI XSS
            $safeName = htmlspecialchars($validated['name'], ENT_QUOTES, 'UTF-8');
            $safeEmail = htmlspecialchars($validated['email'], ENT_QUOTES, 'UTF-8');
            $safeMessage = nl2br(htmlspecialchars($validated['message'], ENT_QUOTES, 'UTF-8'));
            $ipAddress = $request->ip();
            $date = now()->format('d M Y, H:i');

            // Format Link Maps
            $mapsLink = "Lokasi Tidak Diketahui";
            if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
                $mapsLink = "<a href='https://www.google.com/maps?q={$validated['latitude']},{$validated['longitude']}' target='_blank' style='color: #2563eb; text-decoration: none; font-weight: 600;'>Lihat di Google Maps</a>";
            }

            // 4. TEMPLATE EMAIL PREMIUM DENGAN GPS
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

            Log::info('Pesan kontak frontend sukses dikirim.', ['pengirim' => $validated['email'], 'ip' => $request->ip()]);

            return response()->json(['success' => true, 'message' => 'Terima kasih! Pesan Anda berhasil dikirim.']);

        } catch (\Exception $e) {
            // LOG LOG tetap dilestarikan!
            Log::error('Gagal mengirim pesan dari form kontak.', ['error' => $e->getMessage(), 'ip' => $request->ip()]);
            return response()->json(['success' => false, 'message' => 'Kendala server. Coba beberapa saat lagi.'], 500);
        }
    }
}
