<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterJasaController extends Controller
{
    public function index()
    {
        // Ambil Data Level 1
        $bidangs = DB::table('master_bidang')->orderBy('nama_bidang')->get();
        
        // Ambil Data Level 2 (Join ke Bidang)
        $subBidangs = DB::table('master_sub_bidang')
            ->join('master_bidang', 'master_sub_bidang.id_bidang', '=', 'master_bidang.id')
            ->select('master_sub_bidang.*', 'master_bidang.nama_bidang')
            ->orderBy('master_bidang.nama_bidang')
            ->orderBy('master_sub_bidang.nama_sub_bidang')
            ->get();

        // Ambil Data Level 3 (Join ke Sub Bidang)
        $layanans = DB::table('master_layanan')
            ->join('master_sub_bidang', 'master_layanan.id_sub_bidang', '=', 'master_sub_bidang.id')
            ->join('master_bidang', 'master_sub_bidang.id_bidang', '=', 'master_bidang.id')
            ->select('master_layanan.*', 'master_sub_bidang.nama_sub_bidang', 'master_bidang.nama_bidang')
            ->orderBy('master_bidang.nama_bidang')
            ->orderBy('master_layanan.nama_layanan')
            ->get();

        return view('admin.jasa.index', compact('bidangs', 'subBidangs', 'layanans'));
    }

    // ==============================================
    // 1. CRUD LEVEL 1 : BIDANG (Sancaka Home, dll)
    // ==============================================
    public function storeBidang(Request $request)
    {
        $request->validate(['nama_bidang' => 'required|string|max:100']);
        
        DB::table('master_bidang')->insert([
            'nama_bidang' => $request->nama_bidang,
            'status_aktif' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Divisi Bidang berhasil ditambahkan.');
    }

    public function updateBidang(Request $request, $id)
    {
        $request->validate(['nama_bidang' => 'required|string|max:100']);
        
        DB::table('master_bidang')->where('id', $id)->update([
            'nama_bidang' => $request->nama_bidang,
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Divisi Bidang berhasil diperbarui.');
    }

    public function destroyBidang($id)
    {
        DB::table('master_bidang')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Divisi Bidang berhasil dihapus.');
    }

    // ==============================================
    // 2. CRUD LEVEL 2 : SUB BIDANG (Tukang Bangunan)
    // ==============================================
    public function storeSubBidang(Request $request)
    {
        $request->validate([
            'id_bidang' => 'required|integer',
            'nama_sub_bidang' => 'required|string|max:100'
        ]);
        
        DB::table('master_sub_bidang')->insert([
            'id_bidang' => $request->id_bidang,
            'nama_sub_bidang' => $request->nama_sub_bidang,
            'status_aktif' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Sub Bidang berhasil ditambahkan.');
    }

    public function updateSubBidang(Request $request, $id)
    {
        $request->validate([
            'id_bidang' => 'required|integer',
            'nama_sub_bidang' => 'required|string|max:100'
        ]);

        DB::table('master_sub_bidang')->where('id', $id)->update([
            'id_bidang' => $request->id_bidang,
            'nama_sub_bidang' => $request->nama_sub_bidang,
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Sub Bidang berhasil diperbarui.');
    }

    public function destroySubBidang($id)
    {
        DB::table('master_sub_bidang')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Sub Bidang berhasil dihapus.');
    }

    // ==============================================
    // 3. CRUD LEVEL 3 : LAYANAN & TARIF (Pasang Keramik)
    // ==============================================
    public function storeLayanan(Request $request)
    {
        $request->validate([
            'id_sub_bidang' => 'required|integer',
            'nama_layanan' => 'required|string|max:150',
            'tarif_dasar' => 'required|numeric|min:0',
            'tipe_satuan' => 'required|string|max:50'
        ]);

        DB::table('master_layanan')->insert([
            'id_sub_bidang' => $request->id_sub_bidang,
            'nama_layanan' => $request->nama_layanan,
            'tarif_dasar' => $request->tarif_dasar,
            'tipe_satuan' => $request->tipe_satuan,
            'status_aktif' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Layanan & Tarif berhasil ditambahkan.');
    }

    public function updateLayanan(Request $request, $id)
    {
        $request->validate([
            'id_sub_bidang' => 'required|integer',
            'nama_layanan' => 'required|string|max:150',
            'tarif_dasar' => 'required|numeric|min:0',
            'tipe_satuan' => 'required|string|max:50'
        ]);

        DB::table('master_layanan')->where('id', $id)->update([
            'id_sub_bidang' => $request->id_sub_bidang,
            'nama_layanan' => $request->nama_layanan,
            'tarif_dasar' => $request->tarif_dasar,
            'tipe_satuan' => $request->tipe_satuan,
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Layanan & Tarif berhasil diperbarui.');
    }

    public function destroyLayanan($id)
    {
        DB::table('master_layanan')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Layanan & Tarif berhasil dihapus.');
    }

    // ==============================================
    // 4. BULK DESTROY (HAPUS MASSAL 3 TABEL)
    // ==============================================
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'type' => 'required|in:bidang,sub_bidang,layanan' // Validasi agar tidak bisa inject nama tabel lain
        ]);

        $table = 'master_' . $request->type;
        DB::table($table)->whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', count($request->ids) . ' data berhasil dihapus massal.');
    }
}