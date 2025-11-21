<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiKasBankController extends Controller
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
        $transaksi = $this->connection->table('transaksi_kas_bank')
            ->leftJoin('akun_akuntansi', 'transaksi_kas_bank.akun_id', '=', 'akun_akuntansi.id')
            ->select('transaksi_kas_bank.*', 'akun_akuntansi.nama_akun')
            ->paginate(15);

        return view('pondok.admin.transaksi_kas_bank.index', compact('transaksi'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $akun = $this->connection->table('akun_akuntansi')->get();
        return view('pondok.admin.transaksi_kas_bank.create', compact('akun'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'akun_id' => 'required|integer',
            'tanggal_transaksi' => 'required|date',
            'deskripsi' => 'required|string',
            'jenis_transaksi' => 'required|string', // 'Pemasukan' atau 'Pengeluaran'
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('transaksi_kas_bank')->insert($request->except('_token'));
        return redirect()->route('admin.transaksi-kas-bank.index')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $transaksi = $this->connection->table('transaksi_kas_bank')->find($id);
        return view('pondok.admin.transaksi_kas_bank.show', compact('transaksi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $transaksi = $this->connection->table('transaksi_kas_bank')->find($id);
        $akun = $this->connection->table('akun_akuntansi')->get();
        return view('pondok.admin.transaksi_kas_bank.edit', compact('transaksi', 'akun'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'akun_id' => 'required|integer',
            'tanggal_transaksi' => 'required|date',
            'deskripsi' => 'required|string',
            'jenis_transaksi' => 'required|string',
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('transaksi_kas_bank')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.transaksi-kas-bank.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('transaksi_kas_bank')->where('id', $id)->delete();
        return redirect()->route('admin.transaksi-kas-bank.index')->with('success', 'Transaksi berhasil dihapus.');
    }
}

