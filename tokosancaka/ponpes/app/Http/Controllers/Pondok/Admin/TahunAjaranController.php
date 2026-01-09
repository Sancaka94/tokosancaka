<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TahunAjaranController extends Controller
{
    protected $connection;

    public function __construct()
    {
        $this->connection = DB::connection('pondok');
    }

    public function index()
    {
        $tahunAjaran = $this->connection->table('tahun_ajaran')->paginate(10);
        return view('pondok.admin.tahun_ajaran.index', compact('tahunAjaran'));
    }

    public function create()
    {
        return view('pondok.admin.tahun_ajaran.create');
    }

    public function store(Request $request)
    {
        // TODO: Tambahkan validasi
        $this->connection->table('tahun_ajaran')->insert($request->except('_token'));
        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Tahun Ajaran berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $tahunAjaran = $this->connection->table('tahun_ajaran')->find($id);
        return view('pondok.admin.tahun_ajaran.show', compact('tahunAjaran'));
    }

    public function edit(string $id)
    {
        $tahunAjaran = $this->connection->table('tahun_ajaran')->find($id);
        return view('pondok.admin.tahun_ajaran.edit', compact('tahunAjaran'));
    }

    public function update(Request $request, string $id)
    {
        // TODO: Tambahkan validasi
        $this->connection->table('tahun_ajaran')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Tahun Ajaran berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $this->connection->table('tahun_ajaran')->where('id', $id)->delete();
        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Tahun Ajaran berhasil dihapus.');
    }
}

