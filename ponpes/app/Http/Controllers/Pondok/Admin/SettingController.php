<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    protected $connection;

    public function __construct()
    {
        $this->connection = DB::connection('pondok');
    }

    public function index()
    {
        // Ambil semua baris dari tabel settings
        $settingsRaw = $this->connection->table('settings')->get();
        
        // Ubah format data menjadi array tunggal agar mudah dipanggil
        $settings = $settingsRaw->pluck('value', 'key')->all();

        return view('pondok.admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        // 1. Ambil semua input kecuali token keamanan
        $inputs = $request->except(['_token', '_method']);

        // 2. Jika ada upload logo baru, proses simpan file
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('storage/uploads'), $fileName);
            // Simpan path-nya ke dalam array input
            $inputs['logo'] = 'storage/uploads/' . $fileName;
        }

        // 3. Simpan setiap input secara otomatis ke kolom 'key' yang sesuai
        foreach ($inputs as $key => $value) {
            // Jika data key belum ada, akan otomatis dibuat (Insert)
            // Jika sudah ada, data value-nya akan diperbarui (Update)
            $this->connection->table('settings')->updateOrInsert(
                ['key' => $key], 
                ['value' => $value]
            );
        }

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan berhasil diperbarui.');
    }
}