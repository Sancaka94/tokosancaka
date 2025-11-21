<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalUmumController extends Controller
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
        $jurnal = $this->connection->table('jurnal_umum')->orderBy('tanggal', 'desc')->paginate(15);
        return view('pondok.admin.jurnal_umum.index', compact('jurnal'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $akun = $this->connection->table('akun_akuntansi')->get();
        return view('pondok.admin.jurnal_umum.create', compact('akun'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'deskripsi' => 'required|string',
            'akun_debit_id' => 'required|integer',
            'akun_kredit_id' => 'required|integer|different:akun_debit_id',
            'jumlah' => 'required|numeric|min:0',
        ]);

        $this->connection->table('jurnal_umum')->insert($request->except('_token'));
        return redirect()->route('admin.jurnal-umum.index')->with('success', 'Entri jurnal umum berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $jurnal = $this->connection->table('jurnal_umum')->find($id);
        return view('pondok.admin.jurnal_umum.show', compact('jurnal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $jurnal = $this->connection->table('jurnal_umum')->find($id);
        $akun = $this->connection->table('akun_akuntansi')->get();
        return view('pondok.admin.jurnal_umum.edit', compact('jurnal', 'akun'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'deskripsi' => 'required|string',
            'akun_debit_id' => 'required|integer',
            'akun_kredit_id' => 'required|integer|different:akun_debit_id',
            'jumlah' => 'required|numeric|min:0',
        ]);

        $this->connection->table('jurnal_umum')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.jurnal-umum.index')->with('success', 'Entri jurnal umum berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('jurnal_umum')->where('id', $id)->delete();
        return redirect()->route('admin.jurnal-umum.index')->with('success', 'Entri jurnal umum berhasil dihapus.');
    }
}

