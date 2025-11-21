<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JabatanController extends Controller
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
        $jabatan = $this->connection->table('jabatan')->paginate(15);
        return view('pondok.admin.jabatan.index', compact('jabatan'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pondok.admin.jabatan.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:tokq3391_ponpes.jabatan,nama_jabatan',
            'deskripsi' => 'nullable|string',
        ]);

        $this->connection->table('jabatan')->insert($request->except('_token'));
        return redirect()->route('admin.jabatan.index')->with('success', 'Data jabatan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $jabatan = $this->connection->table('jabatan')->find($id);
        return view('pondok.admin.jabatan.show', compact('jabatan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $jabatan = $this->connection->table('jabatan')->find($id);
        return view('pondok.admin.jabatan.edit', compact('jabatan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:tokq3391_ponpes.jabatan,nama_jabatan,' . $id,
            'deskripsi' => 'nullable|string',
        ]);

        $this->connection->table('jabatan')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.jabatan.index')->with('success', 'Data jabatan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('jabatan')->where('id', $id)->delete();
        return redirect()->route('admin.jabatan.index')->with('success', 'Data jabatan berhasil dihapus.');
    }
}

