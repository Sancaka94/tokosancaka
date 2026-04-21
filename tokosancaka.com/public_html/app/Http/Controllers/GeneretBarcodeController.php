<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GeneretBarcodeController extends Controller
{
    // 1. Tampilkan Halaman Utama & Tabel Riwayat
    public function create()
    {
        $riwayat = DB::table('riwayat_barcodes')->orderBy('id', 'desc')->paginate(10);
        return view('barcode.create', compact('riwayat'));
    }

    // 2. Proses Simpan & Generate Baru
    public function generate(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ], [
            'url.required' => 'URL wajib diisi.',
            'url.url' => 'Format URL tidak valid.'
        ]);

        // Simpan ke Database
        DB::table('riwayat_barcodes')->insert([
            'url' => $request->url,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Ambil data riwayat lagi agar tabel tetap muncul setelah submit
        $riwayat = DB::table('riwayat_barcodes')->orderBy('id', 'desc')->paginate(10);

        // Generate untuk preview di atas
        $barcode = base64_encode(QrCode::format('png')->size(300)->margin(2)->generate($request->url));

        return view('barcode.create', compact('barcode', 'riwayat'))
                ->with('url', $request->url)
                ->with('success', 'Barcode berhasil dibuat dan disimpan ke riwayat!');
    }

    // 3. Halaman Edit
    public function edit($id)
    {
        $data = DB::table('riwayat_barcodes')->where('id', $id)->first();
        if (!$data) return redirect()->route('barcode.create')->with('error', 'Data tidak ditemukan');

        return view('barcode.edit', compact('data'));
    }

    // 4. Proses Update
    public function update(Request $request, $id)
    {
        $request->validate(['url' => 'required|url']);

        DB::table('riwayat_barcodes')->where('id', $id)->update([
            'url' => $request->url,
            'updated_at' => now()
        ]);

        return redirect()->route('barcode.create')->with('success', 'Riwayat berhasil diperbarui!');
    }

    // 5. Proses Hapus
    public function destroy($id)
    {
        DB::table('riwayat_barcodes')->where('id', $id)->delete();
        return redirect()->route('barcode.create')->with('success', 'Riwayat berhasil dihapus!');
    }

   public function download($id)
    {
        $data = DB::table('riwayat_barcodes')->where('id', $id)->first();
        if (!$data) abort(404);

        $fileName = 'barcode-' . time() . '.png';

        // Generate gambar dan paksa casting menjadi (string) murni
        $image = (string) QrCode::format('png')->size(300)->margin(2)->generate($data->url);

        return response()->streamDownload(function () use ($image) {
            // Bersihkan output buffer jika ada karakter spasi tak sengaja dari file PHP lain
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            // Cetak binary gambar
            echo $image;
        }, $fileName, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
