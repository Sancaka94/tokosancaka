<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IzinSantriController extends Controller
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
        $izin = $this->connection->table('izin_santri')->paginate(15);
        return view('pondok.admin.izin_santri.index', compact('izin'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Mengambil daftar santri untuk dropdown
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.izin_santri.create', compact('santri'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'keperluan' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|string',
        ]);

        $this->connection->table('izin_santri')->insert($request->except('_token'));
        return redirect()->route('admin.izin-santri.index')->with('success', 'Data izin santri berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $izin = $this->connection->table('izin_santri')->find($id);
        return view('pondok.admin.izin_santri.show', compact('izin'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $izin = $this->connection->table('izin_santri')->find($id);
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.izin_santri.edit', compact('izin', 'santri'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'keperluan' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|string',
        ]);

        $this->connection->table('izin_santri')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.izin-santri.index')->with('success', 'Data izin santri berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('izin_santri')->where('id', $id)->delete();
        return redirect()->route('admin.izin-santri.index')->with('success', 'Data izin santri berhasil dihapus.');
    }
}

