<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;
use App\Models\Kontak;
use App\Models\BroadcastHistory;
use App\Services\FonnteService;
use App\Services\GeminiService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BroadcastHistoryExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class BroadcastController extends Controller
{
    /**
     * 1. HALAMAN UTAMA (Form Kirim & Tabel Riwayat)
     */
    public function index(Request $request)
    {
        // A. Data untuk Tab Kirim Pesan
        // Ambil Pelanggan yang punya WA
        $pelanggans = Pelanggan::whereNotNull('nomor_wa')
            ->where('nomor_wa', '!=', '')
            ->latest()
            ->get(['id', 'nama_pelanggan', 'nomor_wa', 'keterangan']);

        // Ambil Kontak yang punya HP
        $kontaks = Kontak::whereNotNull('no_hp')
            ->where('no_hp', '!=', '')
            ->latest()
            ->get(['id', 'nama', 'no_hp', 'tipe']);

        // B. Data untuk Tab Riwayat (Dengan Filter)
        // Kita gunakan helper getFilteredData agar logic-nya sama dengan Export
        $histories = $this->getFilteredData($request)
            ->paginate(10)
            ->withQueryString();

        return view('broadcast.index', compact('pelanggans', 'kontaks', 'histories'));
    }

    /**
     * 2. PROSES KIRIM PESAN (CORE LOGIC)
     * Menangani Personal (Sapaan Nama) & Bulk Sending
     */
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required',
            'targets' => 'required|array|min:1'
        ]);
        
        $rawTargets = $request->targets; // Array string "08xx|Nama|Tipe"
        $baseMessage = $request->message;
        
        // Cek apakah pesan mengandung variabel {name} untuk personalisasi
        $isPersonalized = str_contains($baseMessage, '{name}');
        
        $cleanTargets = []; // Penampung untuk mode Bulk
        $historyData = [];  // Penampung untuk insert ke database
        $successCount = 0;

        foreach ($rawTargets as $item) {
            // 1. Parsing Value Checkbox
            // Format: "08123456789|Budi Santoso|Pelanggan"
            $parts = explode('|', $item);
            $numberRaw = $parts[0] ?? '';
            $name = $parts[1] ?? 'Kak';
            $type = $parts[2] ?? 'Umum';

            // 2. Bersihkan Nomor (08->62, dll)
            $formattedNumber = $this->formatNomorIndonesia($numberRaw);
            
            if ($formattedNumber) {
                
                // --- SKENARIO A: PESAN PERSONAL (Ada {name}) ---
                // Harus dikirim satu per satu karena isinya beda-beda tiap orang
                if ($isPersonalized) {
                    // Ganti {name} dengan Nama Asli
                    $personalMessage = str_replace('{name}', $name, $baseMessage);
                    
                    // Kirim Langsung via Fonnte Service
                    $response = FonnteService::sendMessage($formattedNumber, $personalMessage);

                    // Catat Status
                    $status = ($response && $response->successful()) ? 'Terkirim' : 'Gagal';
                    if($status == 'Terkirim') $successCount++;

                    // Siapkan Data Riwayat
                    $historyData[] = [
                        'target_name' => $name,
                        'target_number' => $numberRaw, // Simpan nomor asli biar mudah dibaca admin
                        'target_type' => $type,
                        'message' => $personalMessage,
                        'status' => $status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } 
                // --- SKENARIO B: PESAN UMUM (BULK) ---
                // Kumpulkan dulu, nanti kirim sekali tembak
                else {
                    // Gunakan nomor sebagai key untuk menghindari duplikat
                    $cleanTargets[$formattedNumber] = [
                        'name' => $name,
                        'raw' => $numberRaw,
                        'type' => $type
                    ]; 
                }
            }
        }

        // Eksekusi Pengiriman Bulk (Jika mode bukan personal)
        if (!$isPersonalized && !empty($cleanTargets)) {
            // Gabungkan nomor dengan koma: "6281,6282,6283"
            $targetString = implode(',', array_keys($cleanTargets));
            
            // Kirim Sekali Tembak
            $response = FonnteService::sendMessage($targetString, $baseMessage);
            $statusBulk = ($response && $response->successful()) ? 'Terkirim' : 'Gagal';
            
            if($statusBulk == 'Terkirim') $successCount = count($cleanTargets);

            // Catat Riwayat untuk setiap penerima
            foreach ($cleanTargets as $info) {
                $historyData[] = [
                    'target_name' => $info['name'],
                    'target_number' => $info['raw'],
                    'target_type' => $info['type'],
                    'message' => $baseMessage,
                    'status' => $statusBulk,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 3. Simpan Riwayat ke Database (Bulk Insert biar cepat)
        if (!empty($historyData)) {
            BroadcastHistory::insert($historyData);
        }

        return back()->with('success', "Proses selesai! $successCount pesan berhasil diproses.");
    }

    /**
     * 3. GENERATE TEXT VIA GEMINI AI (AJAX)
     * Membuat pesan otomatis dengan Header & Footer Sancaka
     */
    public function generateAi(Request $request, GeminiService $gemini)
    {
        $topic = $request->input('topic');
        
        if(!$topic) return response()->json(['error' => 'Topik harus diisi'], 400);

        // Prompt Khusus Sancaka Express
        // Kita instruksikan AI untuk memasang Header dan Footer wajib
        $systemPrompt = "
Bertindaklah sebagai Admin Customer Service **Sancaka Express** yang profesional, ramah, hangat, dan persuasif.
Gunakan gaya bahasa WhatsApp yang santai, sopan, tidak kaku, dan tidak terasa seperti robot.

Tugasmu adalah membuat pesan WhatsApp PENDEK, MENARIK, dan MEMBUAT PENASARAN
berdasarkan topik: '{$topic}'.

====================================
📌 SISTEM AUTO HOOK (A/B TESTING)
====================================
Pilih SECARA ACAK salah satu jenis HOOK di bawah ini setiap kali membuat pesan
(jangan sebutkan jenis hook-nya ke customer):

HOOK A – CURIOSITY (PENASARAN)
- Contoh gaya: rahasia, banyak yang belum tahu, jarang disadari

HOOK B – BENEFIT LANGSUNG
- Contoh gaya: hemat, murah, cepat, untung, praktis

HOOK C – URGENCY / PERINGATAN
- Contoh gaya: info penting, jangan sampai terlewat, khusus hari ini

Gunakan HOOK TERPILIH di BARIS AWAL PESAN.

====================================
🤖 AUTO ROTATE COPYWRITING (ANTI SPAM)
====================================
- Gunakan variasi kalimat
- Jangan mengulang pola kata yang sama
- Variasikan emoji, CTA, dan gaya kalimat
- Jangan gunakan kalimat template yang identik antar pesan

====================================
🧠 PSIKOLOGI FOMO / SCARCITY
====================================
Sisipkan MINIMAL 1 elemen berikut secara HALUS:
- \"Banyak yang sudah pakai\"
- \"Jangan sampai ketinggalan\"
- \"Sayang kalau dilewatkan\"
- \"Kesempatan terbatas\"
- \"Biasanya penuh cepat\"

====================================
IKUTI STRUKTUR WAJIB INI (JANGAN DIUBAH):
====================================

1. [HEADER SAPAAN]
   - Gunakan {name}
   - Gunakan HOOK hasil pilihan otomatis
   - Contoh:
     'Halo Kak {name} 👋'
     'Assalamualaikum Kak {name} ✨' 

ENTER

2. [ISI PESAN]
   - Maksimal 2 paragraf
   - Jelaskan topik '{$topic}' secara singkat & menarik
   - Gunakan emoji secukupnya (maks 4)
   - Tebalkan (**bold**) kata penting berikut:
     • **Sancaka Express**
     • **tokosancaka.com**
     • kata benefit utama (hemat, cepat, aman, murah)
   - Gunakan CTA HALUS yang memancing klik

   ENTER

3. [FOOTER WAJIB]
   Tulis PERSIS seperti ini (jangan diubah):

   Terimakasih Kakak {name} telah menggunakan aplikasi kiriman *Sancaka Express* untuk keperluan kiriman Paket kakak.
   Oh iya kak {name} sekedar informasi bahwa kami ada juga marketplace loh.
   Jangan lupa kunjungi *tokosancaka.com/etalase*
   Kakak Bisa jualan atau order dengan klik link diatas.
   Jika ada Kritik dan Saran Bisa Balas Pesan ini atau Hubungi Admin Kami 08819435180.

   TTD Manajemen *Sancaka Express*

   ENTER

4. [KALIMAT PENUTUP WAJIB]
   Tulis PERSIS seperti ini (HURUF KAPITAL SEMUA):

   *JANGAN LUPA CEK ONGKIR DAN KIRIM PAKET GUNAKAN TOKOSANCAKA.COM*

====================================
ATURAN PENTING:
====================================
- Jangan menyebut kata 'broadcast'
- Jangan menulis kata kata arahan saya lagi ke dalam text wahatspp
- Jangan menyebut nama AI atau Gemini
- Jangan menyebut kata 'Sancaka' tanpa embel-embel 'Express'
- Jangan menyebut kata 'Sancaka Express' lebih dari 2 kali
- Jangan menyebutkan marketplace selain 'tokosancaka.com'
- Jangan menyebut kata 'iklan'
- Jangan gunakan huruf kapital semua kecuali penutup
- Bold hanya untuk kata penting
- Pesan harus nyaman dibaca di WhatsApp
";


        $result = $gemini->generateText($systemPrompt);

        return response()->json(['text' => $result]);
    }

    /**
     * 4. FITUR EXPORT EXCEL
     */
    public function exportExcel(Request $request) 
    {
        $data = $this->getFilteredData($request)->get();
        return Excel::download(new BroadcastHistoryExport($data), 'laporan-broadcast-'.date('d-m-Y').'.xlsx');
    }

    /**
     * 5. FITUR EXPORT PDF
     */
    public function exportPdf(Request $request) 
    {
        $histories = $this->getFilteredData($request)->get();
        $pdf = Pdf::loadView('broadcast.pdf', compact('histories'));
        return $pdf->download('laporan-broadcast-'.date('d-m-Y').'.pdf');
    }

    /**
     * 6. HAPUS RIWAYAT
     */
    public function destroy($id) 
    {
        BroadcastHistory::findOrFail($id)->delete();
        return back()->with('success', 'Riwayat berhasil dihapus.');
    }

    /**
     * PRIVATE HELPER: Query Filter (Pencarian & Tanggal)
     * Digunakan oleh Index, Export Excel, dan PDF agar hasilnya konsisten
     */
    private function getFilteredData($request) {
        $query = BroadcastHistory::latest();

        // Filter Pencarian (Nama, Nomor, atau Isi Pesan)
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('target_name', 'like', "%{$request->search}%")
                  ->orWhere('target_number', 'like', "%{$request->search}%")
                  ->orWhere('message', 'like', "%{$request->search}%");
            });
        }

        // Filter Tipe (Pelanggan / Kontak)
        if ($request->filled('filter_type')) {
            $query->where('target_type', $request->filter_type);
        }

        // Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00', 
                $request->end_date . ' 23:59:59'
            ]);
        }

        return $query;
    }

    /**
     * PRIVATE HELPER: Format Nomor HP Indonesia (Cerdas)
     */
    private function formatNomorIndonesia($no) {
        // Hapus karakter selain angka
        $no = preg_replace('/[^0-9]/', '', trim($no));

        // Cek prefix
        if (substr($no, 0, 2) === '08') return '62' . substr($no, 1);
        if (substr($no, 0, 1) === '8') return '62' . $no;
        if (substr($no, 0, 2) === '62') return $no;
        
        // Nomor telepon rumah (021, 031, dll) -> 6221, 6231
        if (substr($no, 0, 1) === '0') return '62' . substr($no, 1);

        // Jika tidak dikenali tapi panjangnya wajar, return apa adanya (siapa tahu format internasional lain)
        return (strlen($no) > 6) ? $no : null;
    }
}