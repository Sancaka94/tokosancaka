<?php

namespace App\Http\Controllers;

use App\Models\Proyek;
use Illuminate\Http\Request;

class ProyekController extends Controller
{
    // LOG LOG
    public function index()
    {
        $proyek = Proyek::latest()->get();
        return view('proyek.index', compact('proyek'));
    }

    public function create()
    {
        return view('proyek.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_proyek' => 'required|string|max:255',
            'lokasi_proyek' => 'required|string',
            'nomor_hp' => 'required|string|max:20',
        ]);

        Proyek::create($validated);
        return redirect()->route('proyek.index')->with('success', 'Data Proyek berhasil ditambahkan.');
    }

    public function show(Proyek $proyek)
    {
        // Menampilkan halaman detail RAB khusus untuk proyek ini
        $items = $proyek->rabItems()->orderBy('kategori')->get();
        return view('proyek.show', compact('proyek', 'items'));
    }

    public function edit(Proyek $proyek)
    {
        return view('proyek.edit', compact('proyek'));
    }

    public function update(Request $request, Proyek $proyek)
    {
        $validated = $request->validate([
            'nama_proyek' => 'required|string|max:255',
            'lokasi_proyek' => 'required|string',
            'nomor_hp' => 'required|string|max:20',
        ]);

        $proyek->update($validated);
        return redirect()->route('proyek.index')->with('success', 'Data Proyek berhasil diperbarui.');
    }

    public function destroy(Proyek $proyek)
    {
        $proyek->delete();
        return redirect()->route('proyek.index')->with('success', 'Data Proyek berhasil dihapus.');
    }

    // LOG LOG
    public function simpanCatatan(Request $request, Proyek $proyek)
    {
        $request->validate(['catatan' => 'nullable|string']);
        $proyek->update(['catatan' => $request->catatan]);
        return redirect()->route('proyek.show', $proyek->id)->with('success', 'Catatan proyek berhasil disimpan.');
    }

    // LOG LOG
    public function sharePublic(Proyek $proyek)
    {
        // Mengambil semua item RAB untuk proyek ini dan diurutkan berdasarkan kategori
        $items = $proyek->rabItems()->orderBy('kategori')->get();

        return view('proyek.share', compact('proyek', 'items'));
    }

    // LOG LOG
    public function updateSharePublic(Request $request, Proyek $proyek)
    {
        // 1. Validasi Input
        $request->validate([
            'catatan' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.uraian_pekerjaan' => 'required_with:items|string',
            'items.*.volume' => 'required_with:items|numeric|min:0',
            'items.*.satuan' => 'required_with:items|string',
            'items.*.harga_satuan' => 'required_with:items|numeric|min:0',

            'new_items' => 'nullable|array',
            'new_items.*.uraian_pekerjaan' => 'required_with:new_items|string',
            'new_items.*.volume' => 'required_with:new_items|numeric|min:0',
            'new_items.*.satuan' => 'required_with:new_items|string',
            'new_items.*.harga_satuan' => 'required_with:new_items|numeric|min:0',
        ]);

        // 2. Simpan Catatan Proyek
        $proyek->update([
            'catatan' => $request->catatan
        ]);

        // 3. Update Data Item Lama
        if ($request->has('items')) {
            foreach ($request->items as $id => $data) {
                $item = \App\Models\RabItem::find($id);

                // Pastikan item ditemukan dan benar-benar milik proyek ini
                if ($item && $item->proyek_id == $proyek->id) {
                    $item->update([
                        'uraian_pekerjaan' => $data['uraian_pekerjaan'],
                        'volume'           => $data['volume'],
                        'satuan'           => $data['satuan'],
                        'harga_satuan'     => $data['harga_satuan'],
                        'total'            => $data['volume'] * $data['harga_satuan'], // Kalkulasi ulang total
                    ]);
                }
            }
        }

        // 4. Insert Data Item Baru (Dari tombol Tambah Baris)
        if ($request->has('new_items')) {

            // Ambil kategori terakhir yang ada di form, jika tidak ada gunakan default
            $kategoriDefault = 'PEKERJAAN UMUM';
            if ($request->has('kategori') && is_array($request->kategori)) {
                $kategoriDefault = end($request->kategori);
            }

            foreach ($request->new_items as $new_data) {
                // Abaikan jika baris kosong tidak sengaja terkirim
                if (empty($new_data['uraian_pekerjaan'])) continue;

                \App\Models\RabItem::create([
                    'proyek_id'        => $proyek->id,
                    'kategori'         => $kategoriDefault,
                    'uraian_pekerjaan' => $new_data['uraian_pekerjaan'],
                    'volume'           => $new_data['volume'],
                    'satuan'           => $new_data['satuan'],
                    'harga_satuan'     => $new_data['harga_satuan'],
                    'total'            => $new_data['volume'] * $new_data['harga_satuan'], // Kalkulasi total
                ]);
            }
        }

        return redirect()->back()->with('success', 'Data RAB dan Catatan berhasil diperbarui!');
    }
}
