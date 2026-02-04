<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SantriController extends Controller
{
    protected $connection;

    public function __construct()
    {
        $this->connection = DB::connection('pondok');
    }

    public function index()
    {
        $santri = $this->connection->table('santri')
            ->leftJoin('kelas', 'santri.kelas_id', '=', 'kelas.id')
            ->leftJoin('kamar', 'santri.kamar_id', '=', 'kamar.id')
            ->leftJoin('unit_pendidikan', 'santri.unit_id', '=', 'unit_pendidikan.id')
            ->select('santri.*', 'kelas.nama_kelas', 'kamar.nama_kamar', 'unit_pendidikan.nama_unit as nama_unit')
            ->paginate(15);

        return view('pondok.admin.santri.index', compact('santri'));
    }

    public function create()
    {
        $kelas = $this->connection->table('kelas')->get();
        $kamar = $this->connection->table('kamar')->get();
        $units = $this->connection->table('unit_pendidikan')->get();
        return view('pondok.admin.santri.create', compact('kelas', 'kamar', 'units'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:pondok.santri',
            'jenis_kelamin' => 'required|string',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'nama_ayah' => 'required|string|max:255',
            'nama_ibu' => 'required|string|max:255',
            'telepon_wali' => 'nullable|string|max:20',
            'kelas_id' => 'required|integer',
            'kamar_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'status' => 'required|string|in:Aktif,Lulus,Dikeluarkan,Skors,Tidak Aktif,Pindah',
        ]);

        $dataToInsert = $validatedData;
        $dataToInsert['tenant_id'] = 1; // Nilai tenant_id sementara

        try {
            $this->connection->table('santri')->insert($dataToInsert);
            return redirect()->route('admin.santri.index')->with('success', 'Data santri berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan data santri: ' . $e->getMessage());
        }
    }
    
    public function show(string $id)
    {
        $santri = $this->connection->table('santri')
            ->leftJoin('kelas', 'santri.kelas_id', '=', 'kelas.id')
            ->leftJoin('kamar', 'santri.kamar_id', '=', 'kamar.id')
            ->leftJoin('unit_pendidikan', 'santri.unit_id', '=', 'unit_pendidikan.id')
            ->select('santri.*', 'kelas.nama_kelas', 'kamar.nama_kamar', 'unit_pendidikan.nama_unit as nama_unit')
            ->where('santri.id', $id)->first();
    
        if (!$santri) abort(404);

        return view('pondok.admin.santri.show', compact('santri'));
    }

    public function edit(string $id)
    {
        $santri = $this->connection->table('santri')->find($id);
        if (!$santri) abort(404);
        
        $kelas = $this->connection->table('kelas')->get();
        $kamar = $this->connection->table('kamar')->get();
        $units = $this->connection->table('unit_pendidikan')->get();
        return view('pondok.admin.santri.edit', compact('santri', 'kelas', 'kamar', 'units'));
    }

    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:pondok.santri,nis,' . $id,
            'jenis_kelamin' => 'required|string',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'nama_ayah' => 'required|string|max:255',
            'nama_ibu' => 'required|string|max:255',
            'telepon_wali' => 'nullable|string|max:20',
            'kelas_id' => 'required|integer',
            'kamar_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'status' => 'required|string|in:Aktif,Lulus,Dikeluarkan,Skors,Tidak Aktif,Pindah',
        ]);

        try {
            $this->connection->table('santri')->where('id', $id)->update($validatedData);
            return redirect()->route('admin.santri.index')->with('success', 'Data santri berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui data santri: ' . $e->getMessage());
        }
    }

    /**
     * Update the status of a specific santri.
     */
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|string|in:Aktif,Lulus,Dikeluarkan,Skors,Tidak Aktif,Pindah',
        ]);

        try {
            $this->connection->table('santri')->where('id', $id)->update([
                'status' => $request->status,
            ]);
            return redirect()->route('admin.santri.index')->with('success', 'Status santri berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui status santri: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->connection->table('santri')->where('id', $id)->delete();
            return redirect()->route('admin.santri.index')->with('success', 'Data santri berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data santri: ' . $e->getMessage());
        }
    }
}

