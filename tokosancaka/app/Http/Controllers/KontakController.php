<?php



namespace App\Http\Controllers;



use App\Models\Kontak;

use Illuminate\Http\Request;

use App\Exports\KontaksExport;

use App\Imports\KontaksImport;

use Maatwebsite\Excel\Facades\Excel;

use Barryvdh\DomPDF\Facade\Pdf;



class KontakController extends Controller

{

    /**

     * Menampilkan daftar kontak dengan fitur pencarian dan filter.

     */

    public function index(Request $request)

    {

        $query = Kontak::query();



        // Logika Pencarian

        if ($request->filled('search')) {

            $search = $request->input('search');

            $query->where(function($q) use ($search) {

                $q->where('nama', 'like', "%{$search}%")

                  ->orWhere('no_hp', 'like', "%{$search}%");

            });

        }



        // Logika Filter

        if ($request->filled('filter') && $request->input('filter') !== 'Semua') {

            $query->where('tipe', $request->input('filter'));

        }



        $kontaks = $query->latest()->paginate(10);



        return view('admin.kontak.index', compact('kontaks'));

    }



    /**

     * Menyimpan kontak baru dari modal.

     */

    public function store(Request $request)

    {

        $validatedData = $request->validate([

            'nama' => 'required|string|max:255',

            'no_hp' => 'required|string|max:20|unique:kontaks,no_hp',

            'alamat' => 'required|string',

            'tipe' => 'required|string',

        ]);



        Kontak::create($validatedData);



        return redirect()->route('admin.kontak.index')->with('success', 'Kontak baru berhasil disimpan.');

    }



    /**

     * Menampilkan data kontak untuk diedit (biasanya dalam format JSON untuk modal).

     */

    public function show(Kontak $kontak)

    {

        return response()->json($kontak);

    }

    

    /**

     * Fungsi untuk mencari kontak secara live (AJAX).

     */

    public function search(Request $request)

    {

        $query = $request->input('query');

        

        if(empty($query)) {

            return response()->json([]);

        }



        $kontaks = Kontak::where('nama', 'LIKE', "%{$query}%")

                         ->orWhere('no_hp', 'LIKE', "%{$query}%")

                         ->limit(10)

                         ->get(['id', 'nama', 'no_hp', 'alamat', 'province', 'regency', 'district', 'village', 'postal_code']);



        return response()->json($kontaks);

    }



    /**

     * ✅ FUNGSI BARU: Mendaftarkan kontak dari halaman scan publik.

     * Fungsi ini dipanggil oleh JavaScript dari halaman scan SPX.

     */

    public function registerFromScan(Request $request)

    {

        // 1. Validasi data yang masuk dari form pendaftaran

        $validatedData = $request->validate([

            'nama' => 'required|string|max:255',

            'no_hp' => 'required|string|max:20|unique:kontaks,no_hp',

            'alamat' => 'required|string',

        ]);



        // 2. Tetapkan tipe default karena form ini khusus untuk pelanggan/pengirim

        $validatedData['tipe'] = 'Pelanggan';



        try {

            // 3. Buat kontak baru di database

            $kontak = Kontak::create($validatedData);



            // 4. Kirim respons JSON yang sukses beserta data kontak yang baru dibuat

            return response()->json([

                'success' => true,

                'message' => 'Pendaftaran berhasil!',

                'data' => $kontak // JavaScript akan menggunakan data ini untuk melanjutkan proses

            ]);



        } catch (\Exception $e) {

            // 5. Jika ada error saat menyimpan, kirim respons JSON yang berisi pesan error

            return response()->json([

                'success' => false,

                'message' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.'

            ], 500); // Kode status 500 untuk Internal Server Error

        }

    }

    

    /**

     * Memperbarui data kontak.

     */

    public function update(Request $request, Kontak $kontak)

    {

        $validatedData = $request->validate([

            'nama' => 'required|string|max:255',

            'no_hp' => 'required|string|max:20|unique:kontaks,no_hp,' . $kontak->id,

            'alamat' => 'required|string',

            'tipe' => 'required|string',

        ]);



        $kontak->update($validatedData);



        return redirect()->route('admin.kontak.index')->with('success', 'Kontak berhasil diperbarui.');

    }



    /**

     * Menghapus kontak.

     */

    public function destroy(Kontak $kontak)

    {

        $kontak->delete();



        return redirect()->route('admin.kontak.index')->with('success', 'Kontak berhasil dihapus.');

    }

    

    // --- FUNGSI UNTUK EXPORT & IMPORT ---



    /**

     * Menangani export data ke Excel.

     */

    public function exportExcel() 

    {

        return Excel::download(new KontaksExport, 'data-kontak.xlsx');

    }



    /**

     * Menangani export data ke PDF.

     */

    public function exportPdf() 

    {

        $kontaks = Kontak::all();

        $pdf = PDF::loadView('admin.kontak.pdf', compact('kontaks'));

        return $pdf->download('data-kontak.pdf');

    }



    /**

     * Menangani import data dari Excel.

     */

    public function importExcel(Request $request) 

    {

        $request->validate([

            'file' => 'required|mimes:xlsx,xls'

        ]);

        

        try {

            Excel::import(new KontaksImport, $request->file('file'));

            return redirect()->route('admin.kontak.index')->with('success', 'Data kontak berhasil diimport.');

        } catch (\Exception $e) {

            return redirect()->route('admin.kontak.index')->with('error', 'Gagal mengimport data. Pastikan format file Excel sudah benar.');

        }

    }

}

