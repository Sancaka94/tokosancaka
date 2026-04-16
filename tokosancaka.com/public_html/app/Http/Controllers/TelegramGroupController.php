<?php

namespace App\Http\Controllers;

use App\Models\TelegramGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;

class TelegramGroupController extends Controller
{
    // Kredensial API (Diambil dari .env)
    private $api_id;
    private $api_hash;
    private $session_path;

    public function __construct()
    {
        $this->api_id = (int) env('TELEGRAM_API_ID', 34302401);
        $this->api_hash = env('TELEGRAM_API_HASH', 'c7eec7fb276ef7a4d1da69a8dab2a50d');
        // Path ke file session yang Anda buat di SSH tadi
        $this->session_path = storage_path('app/sancaka_telegram.session');
    }

    // ==========================================
    // BAGIAN ADMIN (CRUD)
    // ==========================================

    public function adminView(Request $request)
    {
        $loggedIn = $request->session()->get('admin_logged_in', false);
        $groups = TelegramGroup::all();
        return view('telegram.admin', compact('loggedIn', 'groups'));
    }

    public function adminLogin(Request $request)
    {
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
        $request->validate([
            'nama' => 'required|string|max:255',
            'link' => 'required|string|max:255',
        ]);

        TelegramGroup::create([
            'nama' => $request->nama,
            'link' => $request->link,
        ]);

        return redirect()->back()->with('success', 'Sumber grup berhasil ditambahkan!');
    }

    public function destroyGroup($id)
    {
        $group = TelegramGroup::findOrFail($id);
        $group->delete();
        return redirect()->back()->with('success', 'Sumber grup berhasil dihapus!');
    }

    // ==========================================
    // BAGIAN USER (PENCARIAN REAL-TIME)
    // ==========================================

    public function index()
    {
        return view('telegram.index');
    }

    public function search(Request $request)
    {
        $keyword = $request->input('q');
        if (!$keyword) return view('telegram.index');

        $groups = TelegramGroup::all();
        $hasil_pencarian = [];

        // Setup Folder Media di /public/media_downloads
        $mediaDir = public_path('media_downloads');
        if (!File::exists($mediaDir)) {
            File::makeDirectory($mediaDir, 0755, true);
        }

        try {
            // Inisialisasi MadelineProto dengan Session yang sudah ada
            $settings = new AppInfo();
            $settings->setApiId($this->api_id);
            $settings->setApiHash($this->api_hash);

            $mp = new API($this->session_path, $settings);

            // Pastikan session sudah start (Tanpa login ulang karena sudah ada file session)
            $mp->start();

            foreach ($groups as $grup) {
                try {
                    // Cari pesan di grup/channel ini (limit 3 per grup agar tidak lambat)
                    $search = $mp->messages->search([
                        'peer' => $grup->link,
                        'q'    => $keyword,
                        'limit' => 3
                    ]);

                    foreach ($search['messages'] as $msg) {
                        // Lewati jika pesan kosong (misal: service message)
                        if ($msg['_'] === 'messageEmpty') continue;

                        $tipe_media = null;
                        $path_media = null;

                        // Logika Deteksi & Download Media
                        if (isset($msg['media']) && $msg['media']['_'] !== 'messageMediaEmpty') {
                            if ($msg['media']['_'] === 'messageMediaPhoto') {
                                $tipe_media = 'photo';
                            } elseif ($msg['media']['_'] === 'messageMediaDocument') {
                                // Cek apakah dokumen ini Video
                                if (isset($msg['media']['document']['mime_type']) &&
                                    str_contains($msg['media']['document']['mime_type'], 'video')) {
                                    $tipe_media = 'video';
                                } else {
                                    $tipe_media = 'document';
                                }
                            }

                            // Download media ke folder publik
                            if ($tipe_media) {
                                // MadelineProto mengunduh file secara otomatis
                                $info = $mp->downloadToDir($msg['media'], $mediaDir);
                                if ($info) {
                                    $path_media = asset('media_downloads/' . basename($info));
                                }
                            }
                        }

                        $hasil_pencarian[] = [
                            'grup'       => $grup->nama,
                            'link_grup'  => $grup->link,
                            'teks'       => $msg['message'] ?? '[Hanya Media]',
                            'tipe_media' => $tipe_media,
                            'path_media' => $path_media,
                        ];
                    }
                } catch (\Exception $e) {
                    // Jika satu grup gagal (misal link salah), lanjut ke grup berikutnya
                    continue;
                }
            }

        } catch (\Exception $e) {
            return view('telegram.index', compact('keyword', 'hasil_pencarian', 'groups'))
                   ->withErrors(['msg' => 'Telegram Error: ' . $e->getMessage()]);
        }

        return view('telegram.index', compact('keyword', 'hasil_pencarian', 'groups'));
    }
}
