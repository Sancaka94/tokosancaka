<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;
use App\Models\Kontak;
use App\Models\BroadcastHistory; // Model Baru
use App\Services\FonnteService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BroadcastHistoryExport;
use Barryvdh\DomPDF\Facade\Pdf;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        // 1. DATA UNTUK FORM KIRIM (Tab 1 & 2)
        $pelanggans = Pelanggan::whereNotNull('nomor_wa')->latest()->get(['id', 'nama_pelanggan', 'nomor_wa', 'keterangan']);
        $kontaks = Kontak::whereNotNull('no_hp')->latest()->get(['id', 'nama', 'no_hp', 'tipe']);

        // 2. DATA RIWAYAT (Tab 3 - Dengan Filter & Search)
        $query = BroadcastHistory::latest();

        // Filter Pencarian (Nama/Nomor)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('target_name', 'like', "%$search%")
                  ->orWhere('target_number', 'like', "%$search%");
            });
        }

        // Filter Tipe (Pelanggan/Kontak)
        if ($request->filled('filter_type')) {
            $query->where('target_type', $request->filter_type);
        }

        // Pagination (10 data per halaman)
        $histories = $query->paginate(10)->withQueryString();

        return view('broadcast.index', compact('pelanggans', 'kontaks', 'histories'));
    }

    public function send(Request $request)
    {
        $request->validate(['message' => 'required', 'targets' => 'required|array']);
        
        $rawTargets = $request->targets; // Format: "08123|Nama|Tipe" (Kita ubah value checkbox di view nanti)
        $message = $request->message;
        $cleanTargets = [];
        $historyData = []; // Array untuk simpan ke DB

        foreach ($rawTargets as $item) {
            // Pecah value checkbox: "08123|Pak Budi|Pelanggan"
            $parts = explode('|', $item);
            $number = $parts[0] ?? '';
            $name = $parts[1] ?? 'Tanpa Nama';
            $type = $parts[2] ?? 'Umum';

            $formatted = $this->formatNomorIndonesia($number);
            
            if ($formatted) {
                $cleanTargets[] = $formatted;
                
                // Siapkan data untuk riwayat
                $historyData[] = [
                    'target_name' => $name,
                    'target_number' => $number, // Simpan nomor asli biar enak dilihat
                    'target_type' => $type,
                    'message' => $message,
                    'status' => 'Terkirim',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $cleanTargets = array_unique($cleanTargets);
        
        if (empty($cleanTargets)) return back()->with('error', 'Tidak ada nomor valid.');

        $targetString = implode(',', $cleanTargets);

        try {
            // Kirim via Fonnte Service
            $response = FonnteService::sendMessage($targetString, $message);

            if ($response->successful()) {
                // SIMPAN RIWAYAT KE DATABASE (Bulk Insert biar cepat)
                BroadcastHistory::insert($historyData);
                
                return back()->with('success', 'Broadcast berhasil dikirim dan riwayat disimpan.');
            }
            
            return back()->with('error', 'Gagal kirim via Fonnte.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // Export Excel
    public function exportExcel(Request $request) {
        $data = $this->getFilteredData($request);
        return Excel::download(new BroadcastHistoryExport($data), 'riwayat-broadcast.xlsx');
    }

    // Export PDF
    public function exportPdf(Request $request) {
        $histories = $this->getFilteredData($request);
        $pdf = Pdf::loadView('broadcast.pdf', compact('histories'));
        return $pdf->download('riwayat-broadcast.pdf');
    }

    // Hapus Riwayat
    public function destroy($id) {
        BroadcastHistory::findOrFail($id)->delete();
        return back()->with('success', 'Data riwayat dihapus.');
    }

    // Helper Private untuk Filter Data Export
    private function getFilteredData($request) {
        $query = BroadcastHistory::latest();
        if ($request->filled('search')) {
            $query->where('target_name', 'like', "%{$request->search}%");
        }
        if ($request->filled('filter_type')) {
            $query->where('target_type', $request->filter_type);
        }
        return $query->get();
    }

    private function formatNomorIndonesia($no) {
        $no = preg_replace('/[^0-9]/', '', trim($no));
        if (substr($no, 0, 2) === '08') return '62' . substr($no, 1);
        if (substr($no, 0, 1) === '8') return '62' . $no;
        if (substr($no, 0, 2) === '62') return $no;
        return $no;
    }
}