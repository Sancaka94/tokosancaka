<?php

namespace App\Http\Controllers;

use App\Models\TelegramGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;

class TelegramGroupController extends Controller
{
    // ==========================================
    // KREDENSIAL TELEGRAM API (MTProto)
    // ==========================================
    // MadelineProto mewajibkan api_id berupa Integer
    private $api_id = 34302401;
    private $api_hash = 'c7eec7fb276ef7a4d1da69a8dab2a50d';

    // ==========================================
    // BAGIAN ADMIN (CRUD & LOGIN SESI)
    // ==========================================

    public function adminView(Request $request)
    {
        Log::info("LOG LOG: Mengakses halaman Admin View.");

        $loggedIn = $request->session()->get('admin_logged_in', false);
        $groups = TelegramGroup::all();

        Log::info("LOG LOG: Berhasil memuat " . $groups->count() . " grup untuk halaman Admin.");
        return view('telegram.admin', compact('loggedIn', 'groups'));
    }

    public function adminLogin(Request $request)
    {
        Log::info("LOG LOG: Percobaan login Admin dilakukan.");

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

        $request->validate([
            'nama' => 'required|string|max:255',
            'link' => 'required|string|max:255',
        ]);

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

        $group = TelegramGroup::findOrFail($id);
        $namaGrup = $group->nama;

        $group->delete();

        Log::info("LOG LOG: Grup berhasil dihapus dari database: " . $namaGrup);
        return redirect()->back()->with('success', 'Sumber grup berhasil dihapus!');
    }

    // ==========================================
    // BAGIAN USER (PENCARIAN REAL-TIME MTPROTO)
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

        $groups = TelegramGroup::all();
        $hasil_pencarian = [];

        Log::info("LOG LOG: Menyiapkan integrasi Telegram MadelineProto dengan API ID: " . $this->api_id . " dan API Hash: " . $this->api_hash);

        // Path ke file sesi MadelineProto yang dibuat via Tinker
        $sessionPath = storage_path('app/madeline.madeline');

        try {
            // Setup Pengaturan App
            $settings = new Settings();
            $appInfo = (new AppInfo())
                ->setApiId($this->api_id)
                ->setApiHash($this->api_hash);
            $settings->setAppInfo($appInfo);

            // Inisialisasi API
            $MadelineProto = new API($sessionPath, $settings);

            // Pastikan tidak meminta input login di web request
            $MadelineProto->start();

            foreach ($groups as $grup) {
                try {
                    Log::info("LOG LOG: Memulai pencarian asli di grup: {$grup->link}");

                    // Resolusi link grup menjadi Peer Object Telegram
                    $peer = $MadelineProto->getInfo($grup->link);

                    // Eksekusi pencarian global langsung di server Telegram
                    $searchResult = $MadelineProto->messages->search([
                        'peer' => $peer,
                        'q' => $keyword,
                        'filter' => ['_' => 'inputMessagesFilterEmpty'], // Filter semua jenis pesan
                        'min_date' => 0,
                        'max_date' => 0,
                        'offset_id' => 0,
                        'add_offset' => 0,
                        'limit' => 15, // Batasi 15 hasil per grup agar loading web tidak timeout
                        'max_id' => 0,
                        'min_id' => 0,
                        'hash' => 0,
                    ]);

                    if (isset($searchResult['messages'])) {
                        foreach ($searchResult['messages'] as $message) {

                            $teks = $message['message'] ?? '[Hanya Media]';
                            $tipe_media = null;

                            // Mendeteksi jenis media (jika ada)
                            if (isset($message['media']['_'])) {
                                if ($message['media']['_'] === 'messageMediaPhoto') {
                                    $tipe_media = 'photo';
                                } elseif ($message['media']['_'] === 'messageMediaDocument') {
                                    // Video di Telegram tergolong dalam document
                                    if (isset($message['media']['document']['attributes'])) {
                                        foreach ($message['media']['document']['attributes'] as $attr) {
                                            if ($attr['_'] === 'documentAttributeVideo') {
                                                $tipe_media = 'video';
                                                break;
                                            }
                                        }
                                    }
                                    if (!$tipe_media) {
                                        $tipe_media = 'document';
                                    }
                                }
                            }

                            // Kita ambil datanya
                            $hasil_pencarian[] = [
                                'grup' => $grup->nama,
                                'link_grup' => $grup->link,
                                'teks' => $teks,
                                'tipe_media' => $tipe_media,
                                // Catatan: Mendownload media MTProto secara synchronous di HTTP request
                                // akan membuat server crash/timeout. Kita tandai URL-nya ke '#'.
                                // Solusi advance: gunakan Job/Queue Laravel untuk mendownload media.
                                'path_media' => '#',
                            ];
                        }
                    }

                    Log::info("LOG LOG: Pencarian selesai di grup: {$grup->nama}");

                } catch (\Exception $e) {
                    Log::error("LOG LOG: Gagal melakukan pencarian di grup {$grup->nama}. Error: " . $e->getMessage());
                }
            }

            Log::info("LOG LOG: Menampilkan " . count($hasil_pencarian) . " hasil pencarian (REAL dari Telegram) ke pengunjung.");

        } catch (\Exception $e) {
            Log::error("LOG LOG: MadelineProto Fatal Error: " . $e->getMessage());
        }

        return view('telegram.index', compact('keyword', 'hasil_pencarian', 'groups'));
    }
}
