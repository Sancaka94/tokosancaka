<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DashboardWidget;
use App\Models\User;

class DashboardWidgetController extends Controller
{
    // Menampilkan halaman Builder dan daftar Kartu
    public function index()
    {
        $widgets = DashboardWidget::orderBy('order_index', 'asc')->get();
        // Ambil data pegawai untuk ditampilkan di dropdown khusus widget gaji
        $operators = User::where('role', 'operator')->get();

        return view('admin.widgets', compact('widgets', 'operators'));
    }

    // Menyimpan Kartu Baru
    public function store(Request $request)
    {
        $data = $request->except('_token');

        // PROTEKSI: Jika kolom persentase/urutan di form kosong, paksa menjadi angka 0
        $data['pct_parkir']   = $request->filled('pct_parkir') ? $request->pct_parkir : 0;
        $data['pct_nginap']   = $request->filled('pct_nginap') ? $request->pct_nginap : 0;
        $data['pct_toilet']   = $request->filled('pct_toilet') ? $request->pct_toilet : 0;
        $data['pct_kas_lain'] = $request->filled('pct_kas_lain') ? $request->pct_kas_lain : 0;
        $data['order_index']  = $request->filled('order_index') ? $request->order_index : 0;

        DashboardWidget::create($data);
        return redirect()->back()->with('success', '🎉 Widget Dashboard berhasil ditambahkan!');
    }

    // Mengupdate Kartu (Edit Rumus)
    public function update(Request $request, $id)
    {
        $widget = DashboardWidget::findOrFail($id);

        $data = $request->except(['_token', '_method']);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        // PROTEKSI: Jika kolom persentase/urutan di form kosong, paksa menjadi angka 0
        $data['pct_parkir']   = $request->filled('pct_parkir') ? $request->pct_parkir : 0;
        $data['pct_nginap']   = $request->filled('pct_nginap') ? $request->pct_nginap : 0;
        $data['pct_toilet']   = $request->filled('pct_toilet') ? $request->pct_toilet : 0;
        $data['pct_kas_lain'] = $request->filled('pct_kas_lain') ? $request->pct_kas_lain : 0;
        $data['order_index']  = $request->filled('order_index') ? $request->order_index : 0;

        $widget->update($data);
        return redirect()->back()->with('success', '✅ Rumus Widget berhasil diperbarui!');
    }

    // Menghapus Kartu
    public function destroy($id)
    {
        DashboardWidget::findOrFail($id)->delete();
        return redirect()->back()->with('success', '🗑️ Widget berhasil dihapus!');
    }
}
