<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DriverPerforma;
use App\Models\DriverMedali;

class RewardDriverOnlineController extends Controller
{
    public function index()
    {
        // 1. Sinkronisasi otomatis sebelum render (Menghitung order yg status 'completed' atau 'selesai')
        $this->syncAllDriverPerformances();

        // 2. Tarik data driver yang id_pengguna-nya tidak NULL
        $drivers = DB::table('registrasi_driver_sancaka')
            ->leftJoin('driver_performa', 'registrasi_driver_sancaka.id_pengguna', '=', 'driver_performa.id_pengguna')
            ->leftJoin('driver_medali', 'driver_performa.id_medali', '=', 'driver_medali.id')
            ->where('registrasi_driver_sancaka.status', 'approved')
            ->whereNotNull('registrasi_driver_sancaka.id_pengguna') // 🛡️ PENGAMAN: Abaikan driver dgn id_pengguna NULL
            ->select(
                'registrasi_driver_sancaka.id_pengguna',
                'registrasi_driver_sancaka.nama_lengkap',
                'driver_performa.bintang_manual',
                'driver_performa.total_order_selesai',
                'driver_performa.is_trusted_express',
                'driver_performa.catatan_admin',
                'driver_medali.nama_medali',
                'driver_medali.ikon'
            )->get();

        return view('admin.reward_driver.index', compact('drivers'));
    }

    public function update(Request $request, $id_pengguna)
    {
        // 🛡️ PENGAMAN: Mencegah error jika terjadi request tanpa id_pengguna
        if (empty($id_pengguna)) {
            return redirect()->back()->with('error', 'ID Pengguna tidak valid.');
        }

        $performa = DriverPerforma::where('id_pengguna', $id_pengguna)->first();

        // Buat baru jika belum ada di tabel performa
        if (!$performa) {
            $performa = new DriverPerforma();
            $performa->id_pengguna = $id_pengguna;
            $performa->total_order_selesai = 0; // Set default awal
            $performa->id_medali = 1; // Set newbie
            $performa->bintang_manual = 0; // Set default awal abu-abu (0)
        }

        // Update nilai dari request form halaman admin
        // Gunakan fallback '0' jika request bintang tidak dikirim (mencegah error)
        $performa->bintang_manual = $request->bintang ?? 0;
        $performa->is_trusted_express = $request->is_trusted;
        $performa->catatan_admin = $request->catatan;

        // Simpan
        $performa->save();

        return redirect()->back()->with('success', 'Performa & Izin Akses Driver berhasil diperbarui.');
    }

    // Engine Sinkronisasi Otomatis
    private function syncAllDriverPerformances()
    {
        // 1. Tarik HANYA driver yang punya id_pengguna (Cegah Crash akibat NULL di tabel utama)
        $activeDrivers = DB::table('registrasi_driver_sancaka')
            ->where('status', 'approved')
            ->whereNotNull('id_pengguna')
            ->pluck('id_pengguna');

        // Tarik master medali urut dari yang paling tinggi skornya ke rendah
        $aturanMedali = DriverMedali::orderBy('minimal_order', 'desc')->get();

        foreach ($activeDrivers as $id_pengguna) {

            // 🛡️ PENGAMAN EKSTRA: Jika id_pengguna kosong, string kosong, atau 0, lewati loop ini!
            if (empty($id_pengguna)) {
                continue;
            }

            // 2. Hitung total orderan real dari database (Order sukses)
            $totalOrder = DB::table('order_ojek_online')
                ->where('driver_id', $id_pengguna)
                ->whereIn('status', ['completed', 'selesai'])
                ->count();

            // 3. Tentukan Medali
            $id_medali_baru = 1; // Default ID untuk Newbie
            foreach ($aturanMedali as $medali) {
                if ($totalOrder >= $medali->minimal_order) {
                    $id_medali_baru = $medali->id;
                    break;
                }
            }

            // 4. Simpan Rapor Menggunakan Explicit Assignment (KEBAL ERROR MASS ASSIGNMENT)
            $performa = DriverPerforma::where('id_pengguna', $id_pengguna)->first();

            // Jika driver ini belum punya rapor sama sekali, kita buatkan baris baru
            if (!$performa) {
                $performa = new DriverPerforma();
                $performa->id_pengguna = $id_pengguna; // Diisi paksa ke objek, pasti masuk!

                // UBAH KE 0 AGAR ABU-ABU DI AWAL UNTUK DRIVER BARU
                $performa->bintang_manual = 0;

                $performa->is_trusted_express = 0; // Default tidak diizinkan bawa paket
                $performa->catatan_admin = null;
            }

            // Update statistik order dan medali otomatisnya
            $performa->total_order_selesai = $totalOrder;
            $performa->id_medali = $id_medali_baru;

            // Eksekusi simpan ke tabel performa
            $performa->save();
        }
    }
}
