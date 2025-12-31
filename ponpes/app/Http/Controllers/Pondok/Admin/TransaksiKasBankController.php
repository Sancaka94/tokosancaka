<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiKasBankController extends Controller
{
    // HAPUS connection 'pondok' agar menggunakan default .env
    
    public function index()
    {
        // Pastikan tabel 'transaksi_kas_bank' dan 'akun_akuntansi' sudah ada
        $transaksi = DB::table('transaksi_kas_bank')
            ->leftJoin('akun_akuntansi', 'transaksi_kas_bank.akun_id', '=', 'akun_akuntansi.id')
            ->select('transaksi_kas_bank.*', 'akun_akuntansi.nama_akun')
            ->orderBy('transaksi_kas_bank.tanggal_transaksi', 'desc') // Urutkan dari yang terbaru
            ->paginate(15);

        return view('pondok.admin.transaksi_kas_bank.index', compact('transaksi'));
    }

    public function create()
    {
        $akun = DB::table('akun_akuntansi')->get();
        return view('pondok.admin.transaksi_kas_bank.create', compact('akun'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'akun_id' => 'required|integer',
            'tanggal_transaksi' => 'required|date',
            'deskripsi' => 'required|string',
            'jenis_transaksi' => 'required|in:Masuk,Keluar', // Validasi diperketat
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        DB::table('transaksi_kas_bank')->insert([
            'akun_id' => $request->akun_id,
            'tanggal_transaksi' => $request->tanggal_transaksi,
            'deskripsi' => $request->deskripsi,
            'jenis_transaksi' => $request->jenis_transaksi,
            'jumlah' => $request->jumlah,
            'keterangan' => $request->keterangan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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



