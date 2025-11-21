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

    /**
     * Show the form for editing the specified resource.
     * Menggunakan index() sebagai halaman utama untuk edit pengaturan.
     */
    public function index()
    {
        // Ambil semua pengaturan dan ubah menjadi format key => value
        $settingsRaw = $this->connection->table('settings')->get();
        $settings = $settingsRaw->pluck('value', 'key')->all();

        return view('pondok.admin.settings.edit', compact('settings'));
    }

    /**
     * Update the specified resource in storage.
     * Menggunakan store() untuk logika update karena hanya ada satu aksi.
     */
    public function store(Request $request)
    {
        // Validasi bisa disesuaikan dengan key pengaturan yang ada
        $request->validate([
            'nama_pondok' => 'nullable|string|max:255',
            'alamat_pondok' => 'nullable|string',
            'email_pondok' => 'nullable|email',
            'telepon_pondok' => 'nullable|string',
            // Tambahkan validasi untuk key lainnya...
        ]);

        $settings = $request->except('_token');

        foreach ($settings as $key => $value) {
            $this->connection->table('settings')
                ->where('key', $key)
                ->update(['value' => $value]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan berhasil diperbarui.');
    }
}

