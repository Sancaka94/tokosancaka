<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Events\AdminNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\ScannedPackage;
use App\Models\SuratJalan;
use App\Models\Kontak;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ScansExport;
use Illuminate\Support\Facades\Mail;

class ScanSpxController extends Controller
{
    /**
     * Helper Method: Cek apakah user adalah Admin
     */
    private function isAdmin()
    {
        $user = Auth::user();
        return ($user && ($user->id_pengguna == 4 || strtolower($user->role) === 'admin'));
    }

    /**
     * Helper Method: Menerapkan Filter "JOIN" Otomatis
     */
    private function applyUserFilter($query)
    {
        if ($this->isAdmin()) {
            return $query; // Admin bebas melihat semua
        }

        $user = Auth::user();

        // PENCEGAH ERROR: Jika dibuka via browser eksternal (tanpa login app), tolak aksesnya
        if (!$user) {
            $query->where('id', -1);
            return $query;
        }

        $userId = $user->id_pengguna;

        // Ambil semua ID Kontak yang user_id-nya adalah milik user ini
        $kontakIds = Kontak::where('user_id', $userId)->pluck('id')->toArray();

        // Filter: Ambil yang user_id-nya cocok, ATAU kontak_id-nya ada di dalam daftar kontak miliknya
        $query->where(function($q) use ($userId, $kontakIds) {
            $q->where('user_id', $userId);

            if (!empty($kontakIds)) {
                $q->orWhereIn('kontak_id', $kontakIds);
            }
        });

        return $query;
    }

    /**
     * 1. Menampilkan halaman utama Riwayat Scan dengan paginasi.
     */
    public function index(Request $request)
    {
        $query = ScannedPackage::with(['kontak']);

        $this->applyUserFilter($query); // Terapkan Filter Join

        if ($request->has('search')) {
            $query->where('resi_number', 'like', '%' . $request->search . '%');
        }

        $now = Carbon::now();
        if ($request->has('filter_waktu')) {
            $filterWaktu = $request->query('filter_waktu');

            if ($filterWaktu === 'Hari Ini') {
                $query->whereDate('created_at', $now->toDateString());
            } elseif ($filterWaktu === 'Bulan Ini') {
                $query->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month);
            } elseif ($filterWaktu === 'Bulan Kemarin') {
                $lastMonth = $now->copy()->subMonth();
                $query->whereYear('created_at', $lastMonth->year)->whereMonth('created_at', $lastMonth->month);
            } elseif ($filterWaktu === 'Tahun Ini') {
                $query->whereYear('created_at', $now->year);
            }
        }

        $scans = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $scans
        ]);
    }

    /**
     * 2. Init data awal untuk halaman scanner SPX Mobile.
     */
    public function initMobile()
    {
        $customer = Auth::user();
        $todays_scans = $this->getTodaysScans();

        return response()->json([
            'success' => true,
            'data' => [
                'customer_name' => $customer->nama_lengkap,
                'customer_phone' => $customer->no_wa ?? $customer->no_hp ?? '',
                'saldo' => $customer->saldo,
                'saldo_format' => number_format($customer->saldo, 0, ',', '.'),
                'todays_count' => $todays_scans->count(),
                'recent_scans' => $todays_scans,
                'is_admin' => $this->isAdmin()
            ]
        ]);
    }

    /**
     * 3. Menyimpan data resi yang baru di-scan.
     */
    public function storeSpxScan(Request $request)
    {
        $request->validate([
            'resi_number' => 'required|string|unique:scanned_packages,resi_number|max:255'
        ]);

        $customer = Auth::user();
        $biayaScan = 1000;

        if ($customer->saldo < $biayaScan) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo tidak mencukupi! Sisa saldo Anda: Rp ' . number_format($customer->saldo, 0, ',', '.'),
                'type'    => 'error'
            ], 400);
        }

        $resi = $request->input('resi_number');
        $package = null;

        try {
            DB::transaction(function () use ($customer, $resi, $biayaScan, &$package) {
                $customer->decrement('saldo', $biayaScan);

                $package = ScannedPackage::create([
                    'user_id' => $customer->id_pengguna,
                    'kontak_id' => null, // HARUS NULL AGAR MENGGUNAKAN NAMA PROFIL USER
                    'resi_number' => $resi,
                    'status' => 'Proses Pickup',
                ]);
            });

            $message = $customer->nama_lengkap . ' telah scan resi baru: ' . $resi;
            $url = route('admin.spx_scans.index', ['search' => $resi]);
            event(new AdminNotificationEvent('Paket SPX Baru Di-scan!', $message, $url));

            $todays_scans = $this->getTodaysScans();

            return response()->json([
                'success' => true,
                'message' => 'Resi berhasil didaftarkan! Saldo terpotong Rp ' . number_format($biayaScan, 0, ',', '.'),
                'data' => [
                    'current_saldo' => number_format($customer->fresh()->saldo, 0, ',', '.'),
                    'package' => $package,
                    'todays_count' => $todays_scans->count(),
                    'recent_scans' => $todays_scans
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses saldo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * 4. Membuat surat jalan.
     */
    public function createSuratJalan(Request $request)
    {
        $validated = $request->validate(['resi_list' => 'required|array|min:1']);

        $customer = Auth::user();
        $resiList = $validated['resi_list'];
        $kodeUnik = 'SJ-' . strtoupper(Str::random(8));

        $suratJalan = SuratJalan::create([
            'user_id' => $customer->id_pengguna,
            'kontak_id' => null, // HARUS NULL AGAR MENGGUNAKAN NAMA PROFIL USER
            'kode_surat_jalan' => $kodeUnik,
            'jumlah_paket' => count($resiList),
        ]);

        $queryUpdate = ScannedPackage::whereIn('resi_number', $resiList);
        $this->applyUserFilter($queryUpdate); // Pastikan hanya resi miliknya
        $queryUpdate->update(['surat_jalan_id' => $suratJalan->id]);

        $message = $customer->nama_lengkap . ' telah membuat Surat Jalan baru.';
        $url = route('admin.spx_scans.index', ['search' => $kodeUnik]);
        event(new AdminNotificationEvent('Surat Jalan Baru Dibuat!', $message, $url));

        return response()->json([
            'success' => true,
            'message' => 'Surat Jalan berhasil dibuat!',
            'data' => [
                'pdf_url' => url('/api/mobile/suratjalan/download/' . $kodeUnik),
                'customer_name' => $customer->nama_lengkap,
                'package_count' => $suratJalan->jumlah_paket,
                'surat_jalan_code' => $suratJalan->kode_surat_jalan,
            ]
        ]);
    }

 /**
     * 5. Mengunduh Surat Jalan dalam format PDF.
     */
    public function downloadSuratJalan($kode_surat_jalan)
    {
        $suratJalan = SuratJalan::where('kode_surat_jalan', $kode_surat_jalan)->firstOrFail();
        $scans = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();

        // 1. KUMPULKAN DATA PENGIRIM
        $nama = 'Sancaka Express';
        $no_hp = '085745808809';
        $alamat = 'Toko Sancaka Express';

        if ($suratJalan->kontak_id) {
            $kontak = Kontak::find($suratJalan->kontak_id);
            if ($kontak) {
                $nama = $kontak->nama;
                $no_hp = $kontak->no_hp;
                $alamat = $kontak->alamat;
            }
        } elseif ($suratJalan->user_id) {
            $user = User::where('id_pengguna', $suratJalan->user_id)->first();
            if ($user) {
                $nama = $user->nama_lengkap;
                $no_hp = $user->no_wa ?? $user->no_hp;
                $alamat = $user->address_detail ?? $user->alamat;
            }
        }

        // 2. BUNGKUS KE DALAM OBJECT MULTI-NAMA
        $customerObj = (object) [
            'nama' => $nama,
            'nama_lengkap' => $nama,
            'no_hp' => $no_hp,
            'no_wa' => $no_hp,
            'alamat' => $alamat,
            'address_detail' => $alamat
        ];

        // 3. INJEKSI PAKSA KE DALAM OBJECT SURAT JALAN
        // Apapun cara file Blade memanggil datanya, pasti akan terjawab!
        $suratJalan->kontak = $customerObj; // Jaga-jaga jika Blade pakai $suratJalan->kontak->nama
        $suratJalan->user = $customerObj;   // Jaga-jaga jika Blade pakai $suratJalan->user->nama_lengkap
        $suratJalan->pengirim_nama = $nama; // Jaga-jaga jika pakai $suratJalan->pengirim_nama

        // 4. GENERATE PDF
        $pdf = Pdf::loadView('customer.scan.surat-jalan-pdf', [
            'suratJalan' => $suratJalan,
            'scans' => $scans,
            'customer' => $customerObj // Jaga-jaga jika Blade murni memanggil $customer->nama
        ]);

        // --- Notifikasi Telegram ke Anda ---
        $this->_sendTelegramNotificationLengkap($suratJalan, $scans, $customerObj);

        // --- Notifikasi Email ke Anda & Customer ---
        $this->_sendEmailSuratJalanLengkap($suratJalan, $scans, $customerObj);

        // --- SUSUN TEKS LENGKAP UNTUK PUSH NOTIFICATION ---
        $namaNotif = $customerObj->nama ?? 'Pengguna';

        // Ambil maksimal 3 resi agar pop-up di HP tidak terlalu panjang/terpotong
        $resiText = "";
        $hitung = 0;
        foreach ($scans as $pkg) {
            if ($hitung < 100) {
                $resiText .= "▪️ " . $pkg->resi_number . "\n";
            }
            $hitung++;
        }
        if (count($scans) > 100) {
            $sisa = count($scans) - 100;
            $resiText .= "(...dan {$sisa} paket lainnya)\n";
        }

        $bodyNotif = "Pengirim: {$namaNotif}\n";
        $bodyNotif .= "No. SJ: {$suratJalan->kode_surat_jalan}\n";
        $bodyNotif .= "Total: {$suratJalan->jumlah_paket} Paket\n";
        $bodyNotif .= "Daftar Resi:\n" . rtrim($resiText);

        // --- KIRIM PUSH NOTIFICATION ---
        $this->_sendExpoPushNotification(
            "Surat Jalan SPX Sancaka Express 🚚",
            $bodyNotif,
            $suratJalan
        );

        return $pdf->download('surat-jalan-' . $kode_surat_jalan . '.pdf');
    }

    /**
     * 6. Mengambil data riwayat scan untuk filter periodik
     */
    public function getHistory(Request $request)
    {
        $query = ScannedPackage::with(['kontak']);
        $this->applyUserFilter($query);

        $now = Carbon::now();
        if ($request->has('filter_waktu')) {
            $filterWaktu = $request->query('filter_waktu');
            if ($filterWaktu === 'Hari Ini') $query->whereDate('created_at', $now->toDateString());
            elseif ($filterWaktu === 'Bulan Ini') $query->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month);
            elseif ($filterWaktu === 'Bulan Kemarin') {
                $lastMonth = $now->copy()->subMonth();
                $query->whereYear('created_at', $lastMonth->year)->whereMonth('created_at', $lastMonth->month);
            } elseif ($filterWaktu === 'Tahun Ini') $query->whereYear('created_at', $now->year);
        } elseif ($request->has('period')) {
            switch ($request->input('period')) {
                case 'today': $query->whereDate('created_at', Carbon::today()); break;
                case '7days': $query->where('created_at', '>=', Carbon::now()->subDays(7)); break;
                case '14days': $query->where('created_at', '>=', Carbon::now()->subDays(14)); break;
                case '30days': $query->where('created_at', '>=', Carbon::now()->subDays(30)); break;
                case 'lastmonth': $query->whereMonth('created_at', Carbon::now()->subMonth()->month); break;
            }
        }

        $scans = $query->latest()->paginate(50);
        return response()->json(['success' => true, 'data' => $scans]);
    }

    /**
     * 7. Memperbarui data status scan
     */
    public function update(Request $request, $resi_number)
    {
        $validated = $request->validate(['status' => 'required|string|max:255']);

        $query = ScannedPackage::where('resi_number', $resi_number);
        $this->applyUserFilter($query);

        $scan = $query->firstOrFail();
        $scan->update($validated);

        return response()->json(['success' => true, 'message' => 'Status resi berhasil diperbarui.', 'data' => $scan]);
    }

    /**
     * 8. Menghapus data scan dari database.
     */
    public function destroy($resi_number)
    {
        $query = ScannedPackage::where('resi_number', $resi_number);
        $this->applyUserFilter($query);

        $scan = $query->firstOrFail();
        $scan->delete();

        return response()->json(['success' => true, 'message' => 'Data scan berhasil dihapus.']);
    }

    /**
     * 9. Mengekspor data riwayat scan ke PDF.
     */
    public function exportPdf()
    {
        $query = ScannedPackage::query();
        $this->applyUserFilter($query);

        $scans = $query->latest()->get();
        $pdf = Pdf::loadView('customer.scan.pdf', compact('scans'));
        return $pdf->download('riwayat-scan.pdf');
    }

    /**
     * 10. Mengekspor data riwayat scan ke Excel.
     */
    public function exportExcel()
    {
        $userId = $this->isAdmin() ? null : Auth::user()->id_pengguna;
        return Excel::download(new ScansExport($userId), 'riwayat-scan.xlsx');
    }

    /**
     * Helper method untuk mengambil data scan hari ini
     */
    private function getTodaysScans()
    {
        $query = ScannedPackage::whereDate('created_at', today())
                               ->whereNull('surat_jalan_id');

        $this->applyUserFilter($query);

        return $query->latest()->get();
    }

   /**
     * 11. Mengambil daftar Riwayat Surat Jalan beserta resi di dalamnya.
     */
    public function historySuratJalan()
    {
        $query = SuratJalan::query();
        $this->applyUserFilter($query);

        $suratJalans = $query->latest()->get();

        $history = $suratJalans->map(function ($sj) {
            $sj->scanned_packages = ScannedPackage::where('surat_jalan_id', $sj->id)->get();

            // Prioritas Data Pengirim
            if (!empty($sj->kontak_id)) {
                $kontak = Kontak::find($sj->kontak_id);
                $sj->kontak = [
                    'nama'   => $kontak->nama ?? '-',
                    'no_hp'  => $kontak->no_hp ?? '-',
                    'alamat' => $kontak->alamat ?? '-'
                ];
            } else {
                $user = User::where('id_pengguna', $sj->user_id)->first();
                $sj->kontak = [
                    'nama'   => $user->nama_lengkap ?? '-',
                    'no_hp'  => $user->no_wa ?? $user->no_hp ?? '-',
                    'alamat' => $user->address_detail ?? $user->alamat ?? '-'
                ];
            }

            return $sj;
        });

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Mengirim Notifikasi LENGKAP Surat Jalan ke Telegram (SPX Mobile)
     */
    private function _sendTelegramNotificationLengkap($suratJalan, $scans, $customerObj)
    {
        $botToken = config('services.telegram.token');
        $chatId = '1885140247'; // ID Telegram Anda (TokoSancaka.Com)

        if (empty($botToken)) return;

        $namaPengirim = $customerObj->nama ?? '-';
        $noWa = $customerObj->no_wa ?? '-';
        $alamat = $customerObj->alamat ?? '-';

        $waktu = \Carbon\Carbon::parse($suratJalan->created_at)->timezone('Asia/Jakarta')->format('d-m-Y H:i:s');

        // Link Google Maps (jika latitude & longitude tersedia di database)
        $googleMapsUrl = "-";
        if (!empty($suratJalan->latitude) && !empty($suratJalan->longitude)) {
            $googleMapsUrl = "<a href='https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}'>Buka di Google Maps 🌍</a>";
        }

        // Ambil daftar resi
        $resiList = "";
        foreach ($scans as $pkg) {
            $resiList .= "▪️ <code>{$pkg->resi_number}</code>\n";
        }

        // Format Pesan Teks Telegram (Lengkap seperti Email)
        $pesan = "🚨 <b>SURAT JALAN (SPX MOBILE) DIUNDUH</b> 🚨\n\n";
        $pesan .= "Telah diproses surat jalan oleh <b>{$namaPengirim}</b>.\n\n";

        $pesan .= "<b>DETAIL INFORMASI:</b>\n";
        $pesan .= "⏱ Waktu Input: {$waktu}\n";
        $pesan .= "🆔 No. SJ: <b>{$suratJalan->kode_surat_jalan}</b>\n";
        $pesan .= "📦 Jumlah Paket: {$suratJalan->jumlah_paket}\n";
        $pesan .= "📱 No. WA: {$noWa}\n";
        $pesan .= "🏠 Alamat: {$alamat}\n\n";

        $pesan .= "<b>DAFTAR RESI:</b>\n";
        $pesan .= $resiList . "\n";

        $pesan .= "📍 <b>Lokasi Pickup:</b>\n";
        $pesan .= $googleMapsUrl . "\n";

        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            \Illuminate\Support\Facades\Http::timeout(5)->post($url, [
                'chat_id' => $chatId,
                'text' => $pesan,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ]);
        } catch (\Exception $e) {
            // Sengaja dibiarkan kosong agar jika Telegram error, web/aplikasi tidak ikut error
        }
    }

   /**
     * Mengirim Notifikasi LENGKAP Surat Jalan ke Email (SPX Mobile)
     */
    private function _sendEmailSuratJalanLengkap($suratJalan, $scans, $customerObj)
    {
        $namaPengirim = $customerObj->nama ?? '-';
        $noWa = $customerObj->no_wa ?? '-';
        $alamat = $customerObj->alamat ?? '-';
        $waktu = \Carbon\Carbon::parse($suratJalan->created_at)->timezone('Asia/Jakarta')->translatedFormat('l, d F Y - H:i');

        $googleMapsUrl = "-";
        if (!empty($suratJalan->latitude) && !empty($suratJalan->longitude)) {
            $googleMapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";
        }

        $resiList = "";
        foreach ($scans as $pkg) {
            $resiList .= "{$pkg->resi_number}<br>";
        }

        $htmlBody = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #d9534f;'>⚠ Surat Jalan (SPX Mobile) Diunduh ⚠</h2>
                <p>Telah diproses surat jalan oleh <strong>{$namaPengirim}</strong>.</p>
                <table style='width: 100%; max-width: 500px; border-collapse: collapse; margin-bottom: 15px;'>
                    <tr><td style='padding: 5px 0;'><strong>Waktu Input:</strong></td><td>{$waktu}</td></tr>
                    <tr><td style='padding: 5px 0;'><strong>No. Surat Jalan:</strong></td><td>{$suratJalan->kode_surat_jalan}</td></tr>
                    <tr><td style='padding: 5px 0;'><strong>Jumlah Paket:</strong></td><td>{$suratJalan->jumlah_paket}</td></tr>
                    <tr><td style='padding: 5px 0;'><strong>No. WA Pengirim:</strong></td><td>{$noWa}</td></tr>
                    <tr><td style='padding: 5px 0;'><strong>Alamat Pengirim:</strong></td><td>{$alamat}</td></tr>
                </table>
                <div style='background: #f9f9f9; padding: 10px; border-left: 4px solid #0275d8; margin-bottom: 15px;'>
                    <strong>Daftar Resi:</strong><br>{$resiList}
                </div>
                <p><strong>Lokasi Pickup:</strong><br><a href='{$googleMapsUrl}' style='color: #0275d8; text-decoration: none;'>Buka di Google Maps &rarr;</a></p>
            </div>
        ";

        try {
            Mail::html($htmlBody, function ($message) use ($suratJalan) {
                // Kirim ke Admin
                $message->to('salafy1995@gmail.com')
                        ->subject("Surat Jalan (SPX Mobile) - {$suratJalan->kode_surat_jalan}");

                // PANCING EMAIL DARI DATABASE
                if ($suratJalan->user_id) {
                    $userDB = \App\Models\User::where('id_pengguna', $suratJalan->user_id)->first();
                    if ($userDB && !empty($userDB->email)) {
                        $message->cc($userDB->email); // CC ke email Pelanggan/Agen
                    }
                }
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal mengirim Email Surat Jalan: " . $e->getMessage());
        }
    }

   /**
     * Mengirim Push Notification Pop-up ke HP User via Expo Token
     */
    private function _sendExpoPushNotification($title, $body, $suratJalan)
    {
        // 1. Ambil data user dari database berdasarkan user_id di Surat Jalan
        $userDB = \App\Models\User::where('id_pengguna', $suratJalan->user_id)->first();

        // 2. Jika user tidak ditemukan atau token kosong, hentikan tanpa error
        if (!$userDB || empty($userDB->expo_token)) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Http::post('https://exp.host/--/api/v2/push/send', [
                'to'    => $userDB->expo_token,
                'title' => $title,
                'body'  => $body,
                'sound' => 'default',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Expo Push Error: " . $e->getMessage());
        }
    }

}
