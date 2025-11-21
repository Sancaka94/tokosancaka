<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TahfidzProgressController extends Controller
{
    protected $connection;

    public function __construct()
    {
        $this->connection = DB::connection('pondok');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $progress = $this->connection->table('tahfidz_progress')
            ->leftJoin('santri', 'tahfidz_progress.santri_id', '=', 'santri.id')
            ->select('tahfidz_progress.*', 'santri.nama_lengkap')
            ->paginate(15);

        return view('pondok.admin.tahfidz_progress.index', compact('progress'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.tahfidz_progress.create', compact('santri'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tanggal_setor' => 'required|date',
            'surat' => 'required|string|max:255',
            'ayat_mulai' => 'required|integer|min:1',
            'ayat_selesai' => 'required|integer|min:1',
            'juz' => 'nullable|integer',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('tahfidz_progress')->insert($request->except('_token'));
        return redirect()->route('admin.tahfidz-progress.index')->with('success', 'Data progress tahfidz berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $progress = $this->connection->table('tahfidz_progress')->find($id);
        return view('pondok.admin.tahfidz_progress.show', compact('progress'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $progress = $this->connection->table('tahfidz_progress')->find($id);
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.tahfidz_progress.edit', compact('progress', 'santri'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tanggal_setor' => 'required|date',
            'surat' => 'required|string|max:255',
            'ayat_mulai' => 'required|integer|min:1',
            'ayat_selesai' => 'required|integer|min:1',
            'juz' => 'nullable|integer',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('tahfidz_progress')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.tahfidz-progress.index')->with('success', 'Data progress tahfidz berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('tahfidz_progress')->where('id', $id)->delete();
        return redirect()->route('admin.tahfidz-progress.index')->with('success', 'Data progress tahfidz berhasil dihapus.');
    }
}

