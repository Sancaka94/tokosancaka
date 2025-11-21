<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranSantriController extends Controller
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
        $pembayaran = $this->connection->table('pembayaran_santri')
            ->leftJoin('santri', 'pembayaran_santri.santri_id', '=', 'santri.id')
            ->leftJoin('tagihan_santri', 'pembayaran_santri.tagihan_id', '=', 'tagihan_santri.id')
            ->select(
                'pembayaran_santri.*',
                'santri.nama_lengkap',
                'tagihan_santri.deskripsi as deskripsi_tagihan'
            )
            ->paginate(15);

        return view('pondok.admin.pembayaran_santri.index', compact('pembayaran'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $santri = $this->connection->table('santri')->get();
        $tagihan = $this->connection->table('tagihan_santri')->where('status', '!=', 'Lunas')->get();
        return view('pondok.admin.pembayaran_santri.create', compact('santri', 'tagihan'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tagihan_id' => 'required|integer',
            'tanggal_bayar' => 'required|date',
            'jumlah_bayar' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('pembayaran_santri')->insert($request->except('_token'));
        
        // Optional: Anda bisa menambahkan logika untuk mengupdate status tagihan di sini
        // Misalnya, jika jumlah bayar >= jumlah tagihan, update status tagihan menjadi 'Lunas'.

        return redirect()->route('admin.pembayaran-santri.index')->with('success', 'Data pembayaran berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pembayaran = $this->connection->table('pembayaran_santri')->find($id);
        return view('pondok.admin.pembayaran_santri.show', compact('pembayaran'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pembayaran = $this->connection->table('pembayaran_santri')->find($id);
        $santri = $this->connection->table('santri')->get();
        $tagihan = $this->connection->table('tagihan_santri')->get();
        return view('pondok.admin.pembayaran_santri.edit', compact('pembayaran', 'santri', 'tagihan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tagihan_id' => 'required|integer',
            'tanggal_bayar' => 'required|date',
            'jumlah_bayar' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('pembayaran_santri')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.pembayaran-santri.index')->with('success', 'Data pembayaran berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('pembayaran_santri')->where('id', $id)->delete();
        return redirect()->route('admin.pembayaran-santri.index')->with('success', 'Data pembayaran berhasil dihapus.');
    }
}

