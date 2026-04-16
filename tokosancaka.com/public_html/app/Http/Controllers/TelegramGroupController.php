<?php

namespace App\Http\Controllers;

use App\Models\TelegramGroup;
use Illuminate\Http\Request;

class TelegramGroupController extends Controller
{
    // ==========================================
    // BAGIAN ADMIN (CRUD & LOGIN SESI)
    // ==========================================

    public function adminView(Request $request)
    {
        // Mengecek status login dari session
        $loggedIn = $request->session()->get('admin_logged_in', false);

        // Mengambil semua data grup menggunakan Eloquent Model
        $groups = TelegramGroup::all();

        return view('telegram.admin', compact('loggedIn', 'groups'));
    }

    public function adminLogin(Request $request)
    {
        // Mengambil kredensial dari .env, dengan fallback default
        $adminUser = env('ADMIN_USERNAME', 'Sancaka94');
        $adminPass = env('ADMIN_PASSWORD', 'Salafyyin***94');

        if ($request->username === $adminUser && $request->password === $adminPass) {
            $request->session()->put('admin_logged_in', true);
            return redirect()->back()->with('success', 'Berhasil Login sebagai Admin!');
        }

        return redirect()->back()->with('error', 'Username atau password salah!');
    }

    public function adminLogout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        return redirect()->back();
    }

    public function storeGroup(Request $request)
    {
        // Validasi input form
        $request->validate([
            'nama' => 'required|string|max:255',
            'link' => 'required|string|max:255',
        ]);

        // Menyimpan data ke database menggunakan Eloquent
        TelegramGroup::create([
            'nama' => $request->nama,
            'link' => $request->link,
        ]);

        return redirect()->back()->with('success', 'Sumber grup berhasil ditambahkan!');
    }

    public function destroyGroup($id)
    {
        // Mencari data berdasarkan ID, jika tidak ada akan otomatis abort 404
        $group = TelegramGroup::findOrFail($id);

        // Menghapus data
        $group->delete();

        return redirect()->back()->with('success', 'Sumber grup berhasil dihapus!');
    }

    // ==========================================
    // BAGIAN USER (PENCARIAN)
    // ==========================================

    public function index()
    {
        return view('telegram.index');
    }

    public function search(Request $request)
    {
        $keyword = $request->input('q');

        if (!$keyword) {
            return view('telegram.index');
        }

        // Mengambil daftar grup dari Model
        $groups = TelegramGroup::all();
        $hasil_pencarian = [];

        /* * CATATAN: Karena aplikasi ini sekarang di PHP, fitur crawling real-time
         * Telethon Python tidak bisa langsung jalan di sini.
         * Di bawah ini adalah simulasi (Mockup) agar tampilan Tailwind berjalan.
         * Anda perlu menggantinya dengan integrasi Telegram API (Bot API) via Guzzle HTTP.
         */

        foreach ($groups as $grup) {
            $hasil_pencarian[] = [
                'grup' => $grup->nama,
                'link_grup' => $grup->link,
                'teks' => "Contoh hasil pencarian untuk kata kunci '{$keyword}' di dalam grup {$grup->nama}.",
                'tipe_media' => 'photo', // photo, video, document, null
                'path_media' => 'https://placehold.co/600x400?text=Gambar+Brosur+Salafy',
            ];
        }

        return view('telegram.index', compact('keyword', 'hasil_pencarian', 'groups'));
    }
}
