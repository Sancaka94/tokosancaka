<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Email; // Pastikan Anda memiliki model Email

class EmailController extends Controller
{
    /**
     * Menampilkan halaman Blade Kotak Masuk
     */
    public function index()
    {
        // Sesuaikan dengan nama file blade Anda, misalnya resources/views/admin/email/index.blade.php
        return view('admin.email.index'); 
    }

    /**
     * Mengambil daftar email berdasarkan folder dan pencarian (Metode GET)
     */
    public function fetch(Request $request)
    {
        $folder = $request->query('folder', 'inbox');
        $search = $request->query('search', '');

        // Query dasar: Hanya ambil email milik user yang sedang login
        $query = Email::where('user_id', Auth::id());

        // Filter berdasarkan folder atau bintang
        if ($folder === 'starred') {
            $query->where('is_starred', true);
        } else {
            $query->where('folder', $folder);
        }

        // Filter pencarian jika ada input dari frontend
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('from_name', 'like', "%{$search}%")
                  ->orWhere('from_address', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        // Ambil data terbaru di atas
        $emails = $query->orderBy('created_at', 'desc')->get();

        // Hitung jumlah pesan belum dibaca khusus di kotak masuk (Inbox)
        $unreadCount = Email::where('user_id', Auth::id())
                            ->where('folder', 'inbox')
                            ->whereNull('read_at')
                            ->count();

        return response()->json([
            'emails' => $emails,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mengambil detail satu email saat diklik (Metode GET)
     */
    public function show($id)
    {
        $email = Email::where('user_id', Auth::id())->findOrFail($id);

        // Jika email belum pernah dibaca, catat waktu bacanya (Read Receipt)
        if (is_null($email->read_at)) {
            $email->update(['read_at' => now()]);
        }

        return response()->json($email);
    }

    /**
     * Mengirim email baru dari modal compose (Metode POST)
     */
    public function send(Request $request)
    {
        // Validasi data dari JavaScript
        $validated = $request->validate([
            'to'      => 'required|email',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        try {
            // 1. Simpan ke database sebagai "Terkirim" (Sent)
            $email = Email::create([
                'user_id'      => Auth::id(),
                'folder'       => 'sent', // Otomatis masuk folder terkirim
                'from_name'    => Auth::user()->name ?? 'Admin Sancaka',
                'from_address' => Auth::user()->email ?? 'admin@sancaka.my.id',
                'to_address'   => $validated['to'],
                'subject'      => $validated['subject'],
                'body'         => nl2br(htmlspecialchars($validated['body'])), // Ubah enter jadi <br>
                'is_starred'   => false,
                'read_at'      => now(), // Email terkirim dianggap sudah dibaca
            ]);

            // 2. LOGIKA SMTP DISINI (Opsional)
            // Jika Anda ingin email benar-benar terkirim ke Gmail/Yahoo, gunakan Facade Mail Laravel:
            // \Illuminate\Support\Facades\Mail::to($validated['to'])->send(new \App\Mail\YourMailClass($email));

            // Kembalikan respons sukses ke SweetAlert
            return response()->json([
                'success' => true,
                'message' => 'Email berhasil dikirim ke ' . $validated['to']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengupdate data parsial seperti fitur Bintang (Metode PATCH)
     */
    public function update(Request $request, $id)
    {
        $email = Email::where('user_id', Auth::id())->findOrFail($id);

        // Jika request membawa data is_starred, update field tersebut
        if ($request->has('is_starred')) {
            $email->is_starred = $request->is_starred;
        }

        // Jika dipindah ke tempat sampah (trash)
        if ($request->has('folder')) {
            $email->folder = $request->folder;
        }

        $email->save();

        return response()->json(['success' => true]);
    }
}