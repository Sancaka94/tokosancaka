<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagihanSantriController extends Controller
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
        $tagihan = $this->connection->table('tagihan_santri')
            ->leftJoin('santri', 'tagihan_santri.santri_id', '=', 'santri.id')
            ->leftJoin('pos_pembayaran', 'tagihan_santri.pos_id', '=', 'pos_pembayaran.id')
            ->select('tagihan_santri.*', 'santri.nama_lengkap', 'pos_pembayaran.nama_pos')
            ->paginate(15);

        return view('pondok.admin.tagihan_santri.index', compact('tagihan'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $santri = $this->connection->table('santri')->get();
        $posPembayaran = $this->connection->table('pos_pembayaran')->get();
        return view('pondok.admin.tagihan_santri.create', compact('santri', 'posPembayaran'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'pos_id' => 'required|integer',
            'jumlah_tagihan' => 'required|numeric|min:0',
            'tanggal_tagihan' => 'required|date',
            'tanggal_jatuh_tempo' => 'nullable|date',
            'deskripsi' => 'nullable|string',
            'status' => 'required|string', // Misal: 'Belum Lunas', 'Lunas', 'Ditangguhkan'
        ]);

        $this->connection->table('tagihan_santri')->insert($request->except('_token'));
        return redirect()->route('admin.tagihan-santri.index')->with('success', 'Tagihan santri berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tagihan = $this->connection->table('tagihan_santri')->find($id);
        return view('pondok.admin.tagihan_santri.show', compact('tagihan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $tagihan = $this->connection->table('tagihan_santri')->find($id);
        $santri = $this->connection->table('santri')->get();
        $posPembayaran = $this->connection->table('pos_pembayaran')->get();
        return view('pondok.admin.tagihan_santri.edit', compact('tagihan', 'santri', 'posPembayaran'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'pos_id' => 'required|integer',
            'jumlah_tagihan' => 'required|numeric|min:0',
            'tanggal_tagihan' => 'required|date',
            'tanggal_jatuh_tempo' => 'nullable|date',
            'deskripsi' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $this->connection->table('tagihan_santri')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.tagihan-santri.index')->with('success', 'Tagihan santri berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('tagihan_santri')->where('id', $id)->delete();
        return redirect()->route('admin.tagihan-santri.index')->with('success', 'Tagihan santri berhasil dihapus.');
    }
}

