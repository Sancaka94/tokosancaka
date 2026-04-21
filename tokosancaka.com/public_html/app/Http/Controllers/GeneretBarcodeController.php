<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GeneretBarcodeController extends Controller
{
    public function create()
    {
        // Ambil data riwayat diurutkan dari yang terbaru
        $riwayat = DB::table('riwayat_barcodes')->orderBy('id', 'desc')->paginate(10);
        return view('barcode.create', compact('riwayat'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ], [
            'url.required' => 'URL wajib diisi.',
            'url.url' => 'Format URL tidak valid.'
        ]);

        // Simpan URL ke tabel riwayat
        DB::table('riwayat_barcodes')->insert([
            'url' => $request->url,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Generate untuk preview langsung
        $barcode = base64_encode(QrCode::format('png')->size(300)->margin(2)->generate($request->url));

        // Ambil kembali data riwayat untuk ditampilkan di tabel
        $riwayat = DB::table('riwayat_barcodes')->orderBy('id', 'desc')->paginate(10);

        return view('barcode.create', compact('barcode', 'riwayat'))->with('url', $request->url)->with('success', 'Barcode berhasil di-generate & disimpan!');
    }

    public function edit($id)
    {
        $data = DB::table('riwayat_barcodes')->where('id', $id)->first();
        if (!$data) return redirect()->route('barcode.create')->with('error', 'Data tidak ditemukan');

        return view('barcode.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['url' => 'required|url']);

        DB::table('riwayat_barcodes')->where('id', $id)->update([
            'url' => $request->url,
            'updated_at' => now()
        ]);

        return redirect()->route('barcode.create')->with('success', 'URL Barcode berhasil diperbarui!');
    }

    public function destroy($id)
    {
        DB::table('riwayat_barcodes')->where('id', $id)->delete();
        return redirect()->route('barcode.create')->with('success', 'Riwayat berhasil dihapus!');
    }

    public function download($id)
    {
        $data = DB::table('riwayat_barcodes')->where('id', $id)->first();
        if (!$data) return abort(404);

        // Generate gambar format PNG saat tombol download diklik
        $image = QrCode::format('png')->size(300)->margin(2)->generate($data->url);

        // Nama file menyesuaikan URL
        $fileName = 'barcode-' . Str::slug(substr($data->url, 0, 30)) . '-' . time() . '.png';

        // Paksa browser untuk mendownload gambar langsung
        return response($image)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
