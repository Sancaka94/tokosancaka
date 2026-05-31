<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PenggunaExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;

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
            'chartLabels',
            'chartSeries'
        ));
    }

    /**
     * Menampilkan detail satu pengguna (Lihat Detail).
     */
    public function show($id_pengguna)
    {
        $user = Pengguna::findOrFail($id_pengguna);

        return view('admin.customers.data.pengguna.show', compact('user'));
    }

    /**
     * Menampilkan form untuk mengedit pengguna (Edit Data).
     */
    public function edit($id_pengguna)
    {
        // [PERBAIKAN] Diubah menjadi $user karena frontend memanggil $user->nama_lengkap dst.
        $user = Pengguna::findOrFail($id_pengguna);

        return view('admin.customers.data.pengguna.edit', compact('user'));
    }

    /**
     * Menyimpan perubahan pada data pengguna (Edit Data).
     */
    public function update(Request $request, $id_pengguna)
    {
        $user = Pengguna::findOrFail($id_pengguna);

        // --- Aturan Validasi Lengkap Sesuai Frontend ---
        $request->validate([
            // Info Dasar
            'id_pengguna' => [
                'required', 
                Rule::unique('Pengguna', 'id_pengguna')->ignore($user->id_pengguna, 'id_pengguna')
            ],
            'nama_lengkap' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('Pengguna', 'email')->ignore($user->id_pengguna, 'id_pengguna'),
            ],
            'no_wa' => 'required|string|max:25',
            'store_name' => 'nullable|string|max:255',
            'store_logo_path' => 'nullable|string|max:255',

            // Status & Keamanan
            'role' => 'required|in:Admin,Seller,Pelanggan',
            'status' => 'required|in:Aktif,Beku,Nonaktif,Banned', // Ditambah Banned sesuai select HTML
            'is_verified' => 'required|boolean',
            'password' => 'nullable|string|min:8', // Hilangkan confirmed jika tidak ada input konfirmasi di form
            'pin' => 'nullable|digits:6',

            // Keuangan & Bank
            'saldo' => 'nullable|numeric',
            'dana_user_balance' => 'nullable|numeric',
            'balance_iak' => 'nullable|numeric',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',

            // Alamat & Lokasi
            'province' => 'nullable|string|max:255',
            'regency' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'address_detail' => 'nullable|string',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',

            // Tokens & Integrasi
            'expo_token' => 'nullable|string',
            'dana_access_token' => 'nullable|string',
            'dana_auth_code' => 'nullable|string',
            'dana_user_name' => 'nullable|string|max:255',
            'setup_token' => 'nullable|string',
            'reset_token' => 'nullable|string',

            // Timestamps & Meta Log
            'token_expiry' => 'nullable|date',
            'created_at' => 'nullable|date',
            'deleted_at' => 'nullable|date',
            'last_seen_at' => 'nullable|date',
            'last_seen' => 'nullable|date',
            'ip_address' => 'nullable|string|max:45',
            'user_agent' => 'nullable|string',
        ]);

        // Kumpulkan semua data kecuali Token CSRF, Method, Password, dan PIN
        $updateData = $request->except(['_token', '_method', 'password', 'pin']);

        // --- Logika Update Password Menjadi HASH ---
        if ($request->filled('password')) {
            $updateData['password_hash'] = Hash::make($request->password);
        }

        // --- Logika Update PIN Menjadi HASH ---
        if ($request->filled('pin')) {
            $updateData['pin'] = Hash::make($request->pin);
        }

        // --- Proses Update Data ---
        $user->update($updateData);

        return redirect()->route('admin.customers.data.pengguna.index')
                         ->with('success', 'Seluruh data pengguna berhasil diperbarui secara permanen.');
    }

    /**
     * Menghapus akun pengguna (Hapus Akun).
     */
    public function destroy($id_pengguna)
    {
        Pengguna::where('id_pengguna', $id_pengguna)->delete();

        return redirect()->route('admin.customers.data.pengguna.index')
                         ->with('success', 'Akun pengguna beserta seluruh datanya berhasil dihapus.');
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
            return Excel::download(new PenggunaExport($data), $filename . '.xlsx');

        } elseif ($type === 'pdf') {
            // Asumsi Anda memiliki Blade view untuk mencetak PDF: 'pdf.pengguna'
            $pdf = Pdf::loadView('pdf.pengguna', compact('data'));
            return $pdf->download($filename . '.pdf');
        }

        return redirect()->route('admin.customers.data.pengguna.index')->with('error', 'Format ekspor tidak valid.');
    }
}