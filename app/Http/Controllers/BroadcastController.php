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
use App\Jobs\SendBroadcastJob; // <--- JANGAN LUPA IMPORT INI DI ATAS

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

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required',
            'targets' => 'required|array|min:1'
        ]);
        
        $rawTargets = $request->targets; 
        $baseMessage = $request->message;
        $isPersonalized = str_contains($baseMessage, '{name}');
        
        $queueCount = 0;
        
        // 1. Kumpulkan semua target yang valid dulu
        $validTargets = [];

        foreach ($rawTargets as $item) {
            $parts = explode('|', $item);
            $numberRaw = $parts[0] ?? '';
            $name = $parts[1] ?? 'Kak';
            $type = $parts[2] ?? 'Umum';

            $formattedNumber = $this->formatNomorIndonesia($numberRaw);
            
            if ($formattedNumber) {
                $validTargets[] = [
                    'original_number' => $numberRaw, // Untuk display history
                    'formatted_number' => $formattedNumber, // Untuk kirim API
                    'name' => $name,
                    'type' => $type
                ];
            }
        }

        // 2. BAGI MENJADI KELOMPOK (CHUNK) ISI 5
        // Ini inti logikanya: [Batch 1 (5 org)], [Batch 2 (5 org)], dst...
        $chunks = array_chunk($validTargets, 5);

        foreach ($chunks as $batchIndex => $batchTargets) {
            
            // 3. HITUNG DELAY WAKTU
            // Batch 0 (5 menit pertama) = delay 0 menit (langsung kirim / atau set 1 menit)
            // Batch 1 (10 menit kedua)  = delay 5 menit
            // Batch 2 (15 menit ketiga) = delay 10 menit
            // Rumus: Index * 5 menit
            $delayMinutes = $batchIndex * 5; 
            
            // Tambahkan sedikit variasi detik agar tidak persis bersamaan (tapi tetap dalam rentang menit)
            // Agar WA tidak curiga kok kirimnya pas banget detik 00
            
            foreach ($batchTargets as $targetInfo) {
                
                // Siapkan Pesan
                $finalMessage = $baseMessage;
                if ($isPersonalized) {
                    $finalMessage = str_replace('{name}', $targetInfo['name'], $baseMessage);
                }

                // Simpan ke Database dulu sebagai "Dalam Antrian"
                $history = BroadcastHistory::create([
                    'target_name' => $targetInfo['name'],
                    'target_number' => $targetInfo['original_number'],
                    'target_type' => $targetInfo['type'],
                    'message' => $finalMessage,
                    'status' => 'Dalam Antrian (Menunggu giliran...)', // Status awal
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // MASUKKAN KE ANTRIAN DENGAN DELAY
                // Kita tambah delay acak 1-30 detik per item biar lebih natural
                $randomSeconds = rand(1, 30); 
                $totalDelay = now()->addMinutes($delayMinutes)->addSeconds($randomSeconds);

                SendBroadcastJob::dispatch(
                    $targetInfo['formatted_number'], 
                    $finalMessage, 
                    $history->id
                )->delay($totalDelay);

                $queueCount++;
            }
        }

        // Hitung estimasi selesai
        $totalBatch = count($chunks);
        $totalTime = ($totalBatch - 1) * 5; 

        return back()->with('success', "Berhasil! $queueCount pesan masuk antrian. Estimasi selesai dalam $totalTime menit (5 pesan per 5 menit).");
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
Bertindaklah sebagai Admin Customer Service *Sancaka Express* yang profesional, ramah, hangat, dan persuasif.
Gunakan gaya bahasa WhatsApp yang santai, sopan, tidak kaku, dan tidak terasa seperti robot. (Kata Ini jangan kamu ketik lagi di text)

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
     • *Sancaka Express*
     • *tokosancaka.com*
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

    public function destroyAll()
{
    try {
        // Hapus semua data di tabel broadcast_histories
        // Ganti 'BroadcastHistory' sesuai nama Model Anda
        \App\Models\BroadcastHistory::truncate(); 

        return redirect()->back()->with('success', 'Seluruh riwayat broadcast berhasil dihapus bersih.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Gagal menghapus riwayat: ' . $e->getMessage());
    }
}

}