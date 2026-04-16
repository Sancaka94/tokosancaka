<?php

namespace App\Http\Controllers;

use App\Models\TelegramGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramGroupController extends Controller
{
    // ==========================================
    // KREDENSIAL TELEGRAM API (MTProto)
    // ==========================================
    private $api_id = '34302401';
    private $api_hash = 'c7eec7fb276ef7a4d1da69a8dab2a50d';

    // ==========================================
    // BAGIAN ADMIN (CRUD & LOGIN SESI)
    // ==========================================

    public function adminView(Request $request)
    {
        Log::info("LOG LOG: Mengakses halaman Admin View.");

        // Mengecek status login dari session
        $loggedIn = $request->session()->get('admin_logged_in', false);

        // Mengambil semua data grup menggunakan Eloquent Model
        $groups = TelegramGroup::all();

        Log::info("LOG LOG: Berhasil memuat " . $groups->count() . " grup untuk halaman Admin.");
        return view('telegram.admin', compact('loggedIn', 'groups'));
    }

    public function adminLogin(Request $request)
    {
        Log::info("LOG LOG: Percobaan login Admin dilakukan.");

        // Mengambil kredensial dari .env, dengan fallback default
        $adminUser = env('ADMIN_USERNAME', 'Sancaka94');
        $adminPass = env('ADMIN_PASSWORD', 'Salafyyin***94');

        if ($request->username === $adminUser && $request->password === $adminPass) {
            $request->session()->put('admin_logged_in', true);
            Log::info("LOG LOG: Admin berhasil login ke sistem Panel Telegram.");
            return redirect()->back()->with('success', 'Berhasil Login sebagai Admin!');
        }

        Log::warning("LOG LOG: Admin gagal login. Kredensial tidak cocok.");
        return redirect()->back()->with('error', 'Username atau password salah!');
    }

    public function adminLogout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        Log::info("LOG LOG: Admin berhasil logout dari sistem.");
        return redirect()->back();
    }

    public function storeGroup(Request $request)
    {
        Log::info("LOG LOG: Memulai proses penambahan grup Telegram baru.");

        // Validasi input form
        $request->validate([
            'nama' => 'required|string|max:255',
            'link' => 'required|string|max:255',
        ]);

        // Menyimpan data ke database menggunakan Eloquent
        $group = TelegramGroup::create([
            'nama' => $request->nama,
            'link' => $request->link,
        ]);

        Log::info("LOG LOG: Grup baru berhasil ditambahkan ke database: " . $group->nama);
        return redirect()->back()->with('success', 'Sumber grup berhasil ditambahkan!');
    }

    public function destroyGroup($id)
    {
        Log::info("LOG LOG: Memulai proses penghapusan grup dengan ID: " . $id);

        // Mencari data berdasarkan ID, jika tidak ada akan otomatis abort 404
        $group = TelegramGroup::findOrFail($id);
        $namaGrup = $group->nama;

        // Menghapus data
        $group->delete();

        Log::info("LOG LOG: Grup berhasil dihapus dari database: " . $namaGrup);
        return redirect()->back()->with('success', 'Sumber grup berhasil dihapus!');
    }

    // ==========================================
    // BAGIAN USER (PENCARIAN)
    // ==========================================

    public function index()
    {
        Log::info("LOG LOG: Pengunjung mengakses halaman utama pencarian (Index).");
        return view('telegram.index');
    }

    public function search(Request $request)
    {
        $keyword = $request->input('q');

        if (!$keyword) {
            Log::info("LOG LOG: Pencarian diakses tanpa keyword, mengembalikan ke tampilan awal.");
            return view('telegram.index');
        }

        Log::info("LOG LOG: Pengunjung melakukan pencarian dengan keyword: '" . $keyword . "'");

        // Mengambil daftar grup dari Model
        $groups = TelegramGroup::all();
        $hasil_pencarian = [];

        Log::info("LOG LOG: Menyiapkan integrasi Telegram dengan API ID: " . $this->api_id . " dan API Hash: " . $this->api_hash);

        /* * CATATAN: Karena aplikasi ini sekarang di PHP, fitur crawling real-time
         * Telethon Python tidak bisa langsung jalan di sini.
         * Di bawah ini adalah simulasi (Mockup) agar tampilan Tailwind berjalan.
         * Anda perlu menggantinya dengan integrasi Telegram API (Bot API) via Guzzle HTTP
         * atau menggunakan library MadelineProto dengan API ID dan API Hash di atas.
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

        Log::info("LOG LOG: Menampilkan " . count($hasil_pencarian) . " hasil pencarian (mockup) ke pengunjung.");

        return view('telegram.index', compact('keyword', 'hasil_pencarian', 'groups'));
    }
}
