<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DashboardSetting;

class DashboardSettingController extends Controller
{
    // Menampilkan halaman form UI Admin
    public function edit()
    {
        // Ambil data setting pertama, jika kosong buat object baru
        $setting = DashboardSetting::first() ?? new DashboardSetting();

        return view('admin.settings', compact('setting'));
        // Sesuaikan 'admin.settings' dengan letak folder view blade Anda, misalnya 'settings.blade.php'
    }

    // Menyimpan perubahan saat tombol "Simpan" diklik
    public function update(Request $request)
    {
        // Ambil data pertama dari tabel
        $setting = DashboardSetting::first();

        // Jika tabel benar-benar kosong (belum di-migrate), buat record baru
        if (!$setting) {
            $setting = new DashboardSetting();
        }

        // Update semua data yang dikirim dari form
        $setting->update([
            'parkir_dibagi_dua'      => $request->parkir_dibagi_dua,
            'nginap_dibagi_dua'      => $request->nginap_dibagi_dua,
            'toilet_masuk_profit'    => $request->toilet_masuk_profit,
            'gaji_hanya_dari_parkir' => $request->gaji_hanya_dari_parkir,

            'tampil_card_harian'     => $request->tampil_card_harian,
            'tampil_card_mingguan'   => $request->tampil_card_mingguan,
            'tampil_card_bulanan'    => $request->tampil_card_bulanan,
            'tampil_grafik_harian'   => $request->tampil_grafik_harian,
            'tampil_grafik_bulanan'  => $request->tampil_grafik_bulanan,
        ]);

        // Kembalikan ke halaman setting dengan pesan sukses
        return redirect()->back()->with('success', 'Konfigurasi dashboard berhasil diperbarui!');
    }
}
