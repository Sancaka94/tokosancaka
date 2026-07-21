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
        // Sinkronisasi otomatis sebelum render (Menghitung order yg status 'completed' atau 'selesai')
        $this->syncAllDriverPerformances();

        $drivers = DB::table('registrasi_driver_sancaka')
            ->leftJoin('driver_performa', 'registrasi_driver_sancaka.id_pengguna', '=', 'driver_performa.id_pengguna')
            ->leftJoin('driver_medali', 'driver_performa.id_medali', '=', 'driver_medali.id')
            ->where('registrasi_driver_sancaka.status', 'approved')
            ->select(
                'registrasi_driver_sancaka.id_pengguna',
                'registrasi_driver_sancaka.nama_lengkap',
                'driver_performa.bintang_manual',
                'driver_performa.total_order_selesai',
                'driver_performa.is_trusted_express',
                'driver_medali.nama_medali',
                'driver_medali.ikon'
            )->get();

        return view('admin.reward_driver.index', compact('drivers'));
    }

    public function update(Request $request, $id_pengguna)
    {
        DriverPerforma::updateOrCreate(
            ['id_pengguna' => $id_pengguna],
            [
                'bintang_manual' => $request->bintang,
                'is_trusted_express' => $request->is_trusted,
                'catatan_admin' => $request->catatan
            ]
        );

        return redirect()->back()->with('success', 'Performa & Izin Akses Driver berhasil diperbarui.');
    }

    // Engine Sinkronisasi Otomatis
    private function syncAllDriverPerformances()
    {
        $activeDrivers = DB::table('registrasi_driver_sancaka')->where('status', 'approved')->pluck('id_pengguna');
        $aturanMedali = DriverMedali::orderBy('minimal_order', 'desc')->get(); // Tarik dari yg paling tinggi (Platinum -> Newbie)

        foreach ($activeDrivers as $id_pengguna) {
            // 1. Hitung total orderan real dari database
            $totalOrder = DB::table('order_ojek_online')
                ->where('driver_id', $id_pengguna)
                ->whereIn('status', ['completed', 'selesai'])
                ->count();

            // 2. Tentukan Medali
            $id_medali_baru = 1; // Default Newbie
            foreach ($aturanMedali as $medali) {
                if ($totalOrder >= $medali->minimal_order) {
                    $id_medali_baru = $medali->id;
                    break;
                }
            }

            // 3. Simpan Rapor
            DriverPerforma::updateOrCreate(
                ['id_pengguna' => $id_pengguna],
                [
                    'total_order_selesai' => $totalOrder,
                    'id_medali' => $id_medali_baru
                ]
            );
        }
    }
}
