<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DashboardWidget;

class DashboardWidgetController extends Controller
{
    // Menampilkan halaman Builder dan daftar Kartu
    public function index()
    {
        // Ambil semua widget, urutkan berdasarkan order_index
        $widgets = DashboardWidget::orderBy('order_index', 'asc')->get();
        return view('admin.widgets', compact('widgets'));
    }

    // Menyimpan Kartu Baru
    public function store(Request $request)
    {
        DashboardWidget::create($request->except('_token'));
        return redirect()->back()->with('success', '🎉 Kartu Dashboard berhasil ditambahkan!');
    }

    // Mengupdate Kartu (Edit Rumus)
    public function update(Request $request, $id)
    {
        $widget = DashboardWidget::findOrFail($id);

        // Handle checkbox is_active (jika dimatikan, HTML tidak mengirim nilai, jadi kita set manual jika tidak ada)
        $data = $request->except(['_token', '_method']);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $widget->update($data);
        return redirect()->back()->with('success', '✅ Rumus Kartu berhasil diperbarui!');
    }

    // Menghapus Kartu
    public function destroy($id)
    {
        DashboardWidget::findOrFail($id)->delete();
        return redirect()->back()->with('success', '🗑️ Kartu berhasil dihapus!');
    }
}
