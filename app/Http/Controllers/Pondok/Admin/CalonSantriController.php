<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalonSantriController extends Controller
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
        $calonSantri = $this->connection->table('calon_santri')->paginate(15);
        return view('pondok.admin.calon_santri.index', compact('calonSantri'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pondok.admin.calon_santri.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'nullable|email',
            'nomor_wa' => 'required|string',
            'status_pendaftaran' => 'required|string',
        ]);

        $this->connection->table('calon_santri')->insert($request->except('_token'));
        return redirect()->route('admin.calon-santri.index')->with('success', 'Data calon santri berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $calonSantri = $this->connection->table('calon_santri')->find($id);
        return view('pondok.admin.calon_santri.show', compact('calonSantri'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $calonSantri = $this->connection->table('calon_santri')->find($id);
        return view('pondok.admin.calon_santri.edit', compact('calonSantri'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'nullable|email',
            'nomor_wa' => 'required|string',
            'status_pendaftaran' => 'required|string',
        ]);

        $this->connection->table('calon_santri')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.calon-santri.index')->with('success', 'Data calon santri berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('calon_santri')->where('id', $id)->delete();
        return redirect()->route('admin.calon-santri.index')->with('success', 'Data calon santri berhasil dihapus.');
    }
}

