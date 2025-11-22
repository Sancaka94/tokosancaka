<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Untuk agregasi statistik
use Illuminate\Validation\Rule; // Untuk validasi
// Asumsikan Anda menggunakan Laravel Excel (Maatwebsite) dan DomPDF
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PenggunaExport; // Anda perlu membuat file export ini
use Barryvdh\DomPDF\Facade\Pdf; // Untuk PDF
use Illuminate\Support\Facades\Hash; // WAJIB ada

class DataPenggunaController extends Controller
{
    /**
     * Menampilkan daftar data pengguna, termasuk fitur pencarian dan statistik.
     */
    public function index(Request $request)
    {
        // --- 1. Logika Pencarian dan Filter ---
        $query = Pengguna::orderBy('created_at', 'desc');

        $search = $request->input('search');
        $city = $request->input('city');

        // Pencarian berdasarkan ID, Nama, atau No. HP
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id_pengguna', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%");
            });
        }
        
        // Filter berdasarkan Kota/Kabupaten (regency)
        if ($city) {
            $query->where('regency', 'like', "%{$city}%");
        }
        
        // Ambil data dengan paginasi
        $pengguna = $query->paginate(10)->withQueryString();

        // --- 2. Ambil Data Statistik Lokasi (Untuk Grafik) ---
        $locationStats = Pengguna::select('province', DB::raw('count(*) as total'))
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->groupBy('province')
            ->orderBy('total', 'desc')
            ->limit(5) // Ambil 5 provinsi teratas
            ->get();

        // Siapkan data untuk dikirim ke Blade (untuk digunakan di JavaScript/Chart)
        $chartLabels = $locationStats->pluck('province');
        $chartSeries = $locationStats->pluck('total');


        // --- 3. Kirim ke View ---
        return view('admin.customers.data.pengguna.index', compact(
            'pengguna',
            'search',
            'city',
            'chartLabels', // Data Labels untuk Chart
            'chartSeries'  // Data Series untuk Chart
        ));
    }

    /**
     * Menampilkan detail satu pengguna (Lihat Detail).
     */
    public function show($id_pengguna)
    {
        $data = Pengguna::findOrFail($id_pengguna);

        return view('admin.customers.data.pengguna.show', compact('data'));
    }

    /**
     * Menampilkan form untuk mengedit pengguna (Edit Data).
     */
    public function edit($id_pengguna)
    {
        $data = Pengguna::findOrFail($id_pengguna);

        return view('admin.customers.data.pengguna.edit', compact('data'));
    }

   /**
 * Menyimpan perubahan pada data pengguna (Edit Data).
 */
public function update(Request $request, $id_pengguna)
{
    $data = Pengguna::findOrFail($id_pengguna);

    // --- Aturan Validasi ---
    $request->validate([
        'nama_lengkap' => 'required|string|max:255',
        'email' => [
            'required',
            'email',
            // Pastikan email unik, kecuali untuk pengguna yang sedang diedit
            Rule::unique('pengguna')->ignore($data->id_pengguna, 'id_pengguna'),
        ],
        'no_wa' => 'nullable|string|max:15',
        'role' => 'required|in:Admin,Seller,Pelanggan',
        'status' => 'required|in:Aktif,Beku,Nonaktif',
        
        // VITAL: Aturan Validasi untuk Password (Hanya divalidasi jika diisi)
        'password' => 'nullable|string|min:8|confirmed',

        // Validasi untuk kolom data toko/bank yang mungkin null
        'store_name' => 'nullable|string|max:255',
        'address_detail' => 'nullable|string',
        'bank_name' => 'nullable|string|max:255',
        'bank_account_number' => 'nullable|string|max:255',
        'bank_account_name' => 'nullable|string|max:255',
    ]);

    // Ambil semua data request, KECUALI 'password' dan 'password_confirmation'
    $updateData = $request->except(['password', 'password_confirmation', '_token', '_method']);

    // --- Logika Update Password Menjadi HASH ---
    if ($request->filled('password')) {
        // Jika field 'password' diisi (tidak kosong), maka hash password baru.
        $updateData['password_hash'] = Hash::make($request->password);
    }
    // CATATAN: Jika 'password' kosong, 'password_hash' di database tidak akan berubah.

    // --- Proses Update Data ---
    $data->update($updateData);

    return redirect()->route('admin.customers.pengguna.index')
                     ->with('success', 'Data pengguna berhasil diperbarui.');
}

    /**
     * Menghapus akun pengguna (Hapus Akun).
     */
    public function destroy($id_pengguna)
    {
        Pengguna::where('id_pengguna', $id_pengguna)->delete();

        return redirect()->route('admin.customers.pengguna.index')
                         ->with('success', 'Akun pengguna berhasil dihapus.');
    }

    /**
     * Mengekspor data pengguna ke Excel atau PDF.
     */
    public function export(Request $request, $type)
    {
        $query = Pengguna::orderBy('created_at', 'desc');

        // Terapkan kembali logika filter dan pencarian yang sama
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id_pengguna', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%");
            });
        }
        if ($city = $request->input('city')) {
            $query->where('regency', 'like', "%{$city}%");
        }

        $data = $query->get();
        $filename = 'data_pengguna_' . now()->format('Ymd_His');

        if ($type === 'excel') {
            // Asumsi Anda memiliki class PenggunaExport yang sudah disiapkan
            return Excel::download(new PenggunaExport($data), $filename . '.xlsx');

        } elseif ($type === 'pdf') {
            // Asumsi Anda memiliki Blade view untuk mencetak PDF: 'pdf.pengguna'
            $pdf = Pdf::loadView('pdf.pengguna', compact('data'));
            return $pdf->download($filename . '.pdf');
            
        }

        return redirect()->route('admin.customers.pengguna.index')->with('error', 'Format ekspor tidak valid.');
    }
}