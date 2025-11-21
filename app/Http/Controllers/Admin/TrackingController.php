<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\SpxScan;
use App\Models\ScanHistory; // Pastikan Anda memiliki model ini

class TrackingController extends Controller
{
    /**
     * Menampilkan halaman tracking utama.
     */
    public function showTrackingPage(Request $request)
    {
        if ($request->has('resi')) {
            return $this->trackPackage($request);
        }
        
        return view('public.tracking.index');
    }

    /**
     * Mencari dan menampilkan hasil pelacakan paket dari berbagai sumber.
     */
    public function trackPackage(Request $request)
    {
        $request->validate(['resi' => 'required|string|max:255']);
        $resi = $request->input('resi');
        $result = null;

        // Langkah 1: Cari di tabel pesanan
        $pesanan = Pesanan::with(['scanHistories'])
                          ->where('resi', $resi)
                          ->orWhere('resi_aktual', $resi)
                          ->first();
        
        if ($pesanan) {
            $result = [
                'is_pesanan' => true,
                'resi' => $pesanan->resi,
                // ======================= PERBAIKAN FINAL DI SINI =======================
                // Mengambil data dari kolom yang benar: sender_name dan receiver_name
                // sesuai dengan struktur tabel Anda.
                // =======================================================================
                'pengirim' => $pesanan->sender_name ?? 'N/A',
                'penerima' => $pesanan->receiver_name ?? 'N/A',
                'alamat_penerima' => $pesanan->receiver_address ?? 'N/A', 
                'status' => $pesanan->status,
                'tanggal_dibuat' => $pesanan->created_at,
                'histories' => $pesanan->scanHistories->sortByDesc('created_at'),
                'resi_aktual' => $pesanan->resi_aktual,
                'jasa_ekspedisi_aktual' => $pesanan->jasa_ekspedisi_aktual,
            ];
        }

        // Langkah 2: Jika tidak ditemukan, cari di tabel spx_scans
        if (!$result) {
            $spxScan = SpxScan::with('kontak')->where('resi', $resi)->first();
            if ($spxScan) {
                $result = [
                    'is_pesanan' => false,
                    'resi' => $spxScan->resi,
                    'pengirim' => $spxScan->kontak->nama ?? 'N/A',
                    'penerima' => 'Agen SPX Express (Sancaka Express)',
                    'alamat_penerima' => $spxScan->kontak->alamat ?? 'N/A',
                    'status' => $spxScan->status,
                    'tanggal_dibuat' => $spxScan->created_at,
                    'histories' => collect([
                        (object)[
                            'status' => $spxScan->status,
                            'lokasi' => 'SPX Ngawi',
                            'keterangan' => 'Paket telah di-scan oleh pengirim.',
                            'created_at' => $spxScan->created_at
                        ]
                    ]),
                ];
            }
        }

        if ($result) {
            return view('public.tracking.index', compact('result'));
        }

        return redirect()->route('tracking.index')->with('error', "Nomor resi '{$resi}' tidak ditemukan. Pastikan nomor yang Anda masukkan sudah benar.");
    }
}
