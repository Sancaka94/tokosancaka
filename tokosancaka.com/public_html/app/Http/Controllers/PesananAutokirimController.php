<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PesananAutokirim;
use App\Models\AutoKirim; // Menggunakan data wilayah lokal AutoKirim
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PesananAutokirimController extends Controller
{
    // ==========================================
    // AREA CUSTOMER (Buat Pesanan)
    // ==========================================
    public function createCustomer()
    {
        return view('customer.pesanan_autokirim.create');
    }

    public function store(Request $request)
    {
        // Menyimpan data pesanan ke database
        $orderId = 'AK-' . strtoupper(Str::random(8));

        PesananAutokirim::create([
            'user_id'          => auth()->id() ?? null,
            'order_id'         => $orderId,
            'pengirim_nama'    => $request->pengirim_nama,
            'pengirim_hp'      => $request->pengirim_hp,
            'pengirim_alamat'  => $request->pengirim_alamat,
            'pengirim_kodepos' => $request->pengirim_kodepos,

            'penerima_nama'    => $request->penerima_nama,
            'penerima_hp'      => $request->penerima_hp,
            'penerima_alamat'  => $request->penerima_alamat,
            'penerima_kodepos' => $request->penerima_kodepos,

            'deskripsi_barang' => $request->deskripsi_barang,
            'kategori_barang'  => $request->kategori_barang,
            'berat_gram'       => $request->berat_gram,
            'panjang_cm'       => $request->panjang_cm ?? 0,
            'lebar_cm'         => $request->lebar_cm ?? 0,
            'tinggi_cm'        => $request->tinggi_cm ?? 0,
            'asuransi'         => $request->has('asuransi') ? 1 : 0,
            'nilai_barang'     => $request->nilai_barang ?? 0,

            'kurir'            => $request->kurir_terpilih,
            'layanan'          => $request->layanan_terpilih,
            'ongkir'           => $request->ongkir_terpilih ?? 0,
            'status'           => 'pending'
        ]);

        return redirect()->route('customer.pesanan-autokirim.create')->with('success', 'Pesanan berhasil dibuat! Menunggu pembayaran.');
    }

    // ==========================================
    // AREA ADMIN (Manajemen Data)
    // ==========================================
    public function indexAdmin(Request $request)
    {
        $pesanan = PesananAutokirim::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.pesanan_autokirim.index', compact('pesanan'));
    }

    // ==========================================
    // API / AJAX HELPER (Dipanggil via Alpine.js)
    // ==========================================

    // Mencari kodepos & wilayah berdasarkan tabel lokal AutoKirim
    public function searchAddressAjax(Request $request)
    {
        $keyword = $request->query('q');
        if (!$keyword || strlen($keyword) < 3) {
            return response()->json([]);
        }

        $data = AutoKirim::where('district_name', 'like', "%{$keyword}%")
            ->orWhere('regency_name', 'like', "%{$keyword}%")
            ->orWhere('zip', 'like', "%{$keyword}%")
            ->select('district_name', 'regency_name', 'province_name', 'zip')
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    // Simulasi Cek Ongkir (Ganti dengan API Autokirim asli Anda)
    public function cekOngkirAjax(Request $request)
    {
        // Anda bisa menembak API Autokirim sebenarnya di sini.
        // Untuk contoh ini, saya buatkan format balasan standar.

        $berat = max(1, ceil($request->berat_gram / 1000)); // Konversi ke KG
        $baseTarif = 10000;

        // Dummy Response API
        $ongkirList = [
            ['kurir' => 'Sancaka Express', 'layanan' => 'Sameday', 'harga' => $baseTarif * $berat, 'estimasi' => '1 Hari'],
            ['kurir' => 'JNE', 'layanan' => 'REG', 'harga' => ($baseTarif + 5000) * $berat, 'estimasi' => '2-3 Hari'],
            ['kurir' => 'J&T', 'layanan' => 'EZ', 'harga' => ($baseTarif + 4000) * $berat, 'estimasi' => '2-4 Hari'],
        ];

        return response()->json([
            'success' => true,
            'data' => $ongkirList
        ]);
    }
}
