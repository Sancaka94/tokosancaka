<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosPembayaranController extends Controller
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
        $posPembayaran = $this->connection->table('pos_pembayaran')->paginate(15);
        return view('pondok.admin.pos_pembayaran.index', compact('posPembayaran'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pondok.admin.pos_pembayaran.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_pos' => 'required|string|max:255',
            'kode_pos' => 'required|string|max:50|unique:pondok.pos_pembayaran',
            'deskripsi' => 'nullable|string',
        ]);

        $this->connection->table('pos_pembayaran')->insert($request->except('_token'));
        return redirect()->route('admin.pos-pembayaran.index')->with('success', 'POS Pembayaran berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pos = $this->connection->table('pos_pembayaran')->find($id);
        return view('pondok.admin.pos_pembayaran.show', compact('pos'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pos = $this->connection->table('pos_pembayaran')->find($id);
        return view('pondok.admin.pos_pembayaran.edit', compact('pos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_pos' => 'required|string|max:255',
            'kode_pos' => 'required|string|max:50|unique:pondok.pos_pembayaran,kode_pos,' . $id,
            'deskripsi' => 'nullable|string',
        ]);

        $this->connection->table('pos_pembayaran')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.pos-pembayaran.index')->with('success', 'POS Pembayaran berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('pos_pembayaran')->where('id', $id)->delete();
        return redirect()->route('admin.pos-pembayaran.index')->with('success', 'POS Pembayaran berhasil dihapus.');
    }
}

