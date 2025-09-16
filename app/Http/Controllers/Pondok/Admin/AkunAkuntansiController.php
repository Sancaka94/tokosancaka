<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AkunAkuntansiController extends Controller
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
        $akun = $this->connection->table('akun_akuntansi')->paginate(15);
        return view('pondok.admin.akun_akuntansi.index', compact('akun'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Mengambil akun lain untuk opsi 'parent account' jika ada
        $parentAkun = $this->connection->table('akun_akuntansi')->get();
        return view('pondok.admin.akun_akuntansi.create', compact('parentAkun'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kode_akun' => 'required|string|unique:tokq3391_ponpes.akun_akuntansi,kode_akun',
            'nama_akun' => 'required|string|max:255',
            'kategori' => 'required|string',
        ]);

        $this->connection->table('akun_akuntansi')->insert($request->except('_token'));
        return redirect()->route('admin.akun-akuntansi.index')->with('success', 'Akun akuntansi berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $akun = $this->connection->table('akun_akuntansi')->find($id);
        return view('pondok.admin.akun_akuntansi.show', compact('akun'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $akun = $this->connection->table('akun_akuntansi')->find($id);
        $parentAkun = $this->connection->table('akun_akuntansi')->where('id', '!=', $id)->get();
        return view('pondok.admin.akun_akuntansi.edit', compact('akun', 'parentAkun'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'kode_akun' => 'required|string|unique:tokq3391_ponpes.akun_akuntansi,kode_akun,' . $id,
            'nama_akun' => 'required|string|max:255',
            'kategori' => 'required|string',
        ]);

        $this->connection->table('akun_akuntansi')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.akun-akuntansi.index')->with('success', 'Akun akuntansi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('akun_akuntansi')->where('id', $id)->delete();
        return redirect()->route('admin.akun-akuntansi.index')->with('success', 'Akun akuntansi berhasil dihapus.');
    }
}

