<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunKeuangan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoaController extends Controller
{
    /**
     * MENAMPILKAN DAFTAR AKUN
     */
    public function index(Request $request)
    {
        // Default filter: Ekspedisi jika tidak ada request
        $unit = $request->input('unit', 'Ekspedisi');

        $accounts = AkunKeuangan::where('unit_usaha', $unit)
            ->orderBy('kode_akun', 'asc')
            ->get();

        return view('admin.coa.index', compact('accounts', 'unit'));
    }

    public function create()
    {
        // Ambil daftar kategori unik untuk saran (datalist)
        $existingCategories = AkunKeuangan::select('kategori')->distinct()->pluck('kategori');
        return view('admin.coa.create', compact('existingCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'unit_usaha' => 'required|in:Ekspedisi,Percetakan',
            'kode_akun' => [
                'required', 'string', 'max:20',
                // Validasi Unik: Kode boleh sama asalkan BEDA unit usaha
                Rule::unique('akun_keuangan')->where(function ($query) use ($request) {
                    return $query->where('unit_usaha', $request->unit_usaha);
                }),
            ],
            'nama_akun' => 'required|string|max:100',
            'kategori' => 'required|string|max:50',
            'jenis_laporan' => 'required|in:Neraca,Laba Rugi',
            'tipe_arus' => 'required|in:Pemasukan,Pengeluaran,Netral',
        ], [
            'kode_akun.unique' => 'Kode akun ini sudah terdaftar di unit usaha tersebut.',
        ]);

        AkunKeuangan::create($request->all());

        return redirect()->route('admin.coa.index', ['unit' => $request->unit_usaha])
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $account = AkunKeuangan::findOrFail($id);
        $existingCategories = AkunKeuangan::select('kategori')->distinct()->pluck('kategori');
        return view('admin.coa.edit', compact('account', 'existingCategories'));
    }

    public function update(Request $request, $id)
    {
        $account = AkunKeuangan::findOrFail($id);

        $request->validate([
            'unit_usaha' => 'required|in:Ekspedisi,Percetakan',
            'kode_akun' => [
                'required', 'string', 'max:20',
                // Validasi Unik Ignore ID saat ini
                Rule::unique('akun_keuangan')
                    ->where(function ($query) use ($request) {
                        return $query->where('unit_usaha', $request->unit_usaha);
                    })->ignore($account->id),
            ],
            'nama_akun' => 'required|string|max:100',
            'kategori' => 'required|string|max:50',
            'jenis_laporan' => 'required|in:Neraca,Laba Rugi',
            'tipe_arus' => 'required|in:Pemasukan,Pengeluaran,Netral',
        ]);

        $account->update($request->all());

        return redirect()->route('admin.coa.index', ['unit' => $request->unit_usaha])
            ->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $account = AkunKeuangan::findOrFail($id);
        $unit = $account->unit_usaha;
        $account->delete();

        return redirect()->route('admin.coa.index', ['unit' => $unit])
            ->with('success', 'Akun berhasil dihapus.');
    }
}