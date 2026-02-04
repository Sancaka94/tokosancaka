<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TabunganSantriController extends Controller
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
        $transaksi = $this->connection->table('tabungan_santri')
            ->leftJoin('santri', 'tabungan_santri.santri_id', '=', 'santri.id')
            ->select('tabungan_santri.*', 'santri.nama_lengkap')
            ->paginate(15);

        return view('pondok.admin.tabungan_santri.index', compact('transaksi'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.tabungan_santri.create', compact('santri'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tanggal_transaksi' => 'required|date',
            'jenis_transaksi' => 'required|string', // 'Setor' atau 'Tarik'
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('tabungan_santri')->insert($request->except('_token'));
        return redirect()->route('admin.tabungan-santri.index')->with('success', 'Transaksi tabungan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $transaksi = $this->connection->table('tabungan_santri')->find($id);
        return view('pondok.admin.tabungan_santri.show', compact('transaksi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $transaksi = $this->connection->table('tabungan_santri')->find($id);
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.tabungan_santri.edit', compact('transaksi', 'santri'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tanggal_transaksi' => 'required|date',
            'jenis_transaksi' => 'required|string',
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('tabungan_santri')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.tabungan-santri.index')->with('success', 'Transaksi tabungan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('tabungan_santri')->where('id', $id)->delete();
        return redirect()->route('admin.tabungan-santri.index')->with('success', 'Transaksi tabungan berhasil dihapus.');
    }
}

