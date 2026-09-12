<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator; // <-- Tambahkan ini

class ProfileController extends Controller
{
    /**
     * Mengambil data profil user saat ini
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    /**
     * Memperbarui profil via Mobile API
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // 1. Menggunakan Validator::make agar bisa mengembalikan response JSON manual jika gagal
        $validator = Validator::make($request->all(), [
            'nama_lengkap'          => ['required', 'string', 'max:255'],
            'jenis_kelamin'         => ['required', 'string', 'in:Laki-laki,Perempuan'],
            'no_wa'                 => ['required', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')],
            'store_name'            => ['nullable', 'string', 'max:255'],
            'store_logo'            => ['nullable', 'image', 'max:4096'], // Diperbesar menjadi 4MB untuk berjaga-jaga dari kamera HP
            'bank_name'             => ['nullable', 'string', 'max:255'],
            'bank_account_name'     => ['nullable', 'string', 'max:255'],
            'bank_account_number'   => ['nullable', 'string', 'max:255'],
            'province'              => ['required', 'string', 'max:255'],
            'regency'               => ['required', 'string', 'max:255'],
            'district'              => ['required', 'string', 'max:255'],
            'village'               => ['required', 'string', 'max:255'], // Ini yang sebelumnya bikin error karena tidak dikirim
            'postal_code'           => ['nullable', 'string', 'max:10'],
            'address_detail'        => ['required', 'string'],
            // Menjadikan latitude dan longitude nullable (atau beri default) karena kadang GPS gagal
            'latitude'              => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'             => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid. Cek kembali form Anda.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validated = $validator->validated();

            // Proses Upload Gambar Logo Toko
            if ($request->hasFile('store_logo')) {
                if ($user->store_logo_path) {
                    Storage::disk('public')->delete($user->store_logo_path);
                }
                $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
                $user->store_logo_path = $path;

                // Hapus key store_logo dari $validated agar tidak ikut di $user->fill()
                unset($validated['store_logo']);
            }

            $user->fill($validated);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui!',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('API Profile Update Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendaftarkan Sub-Role / Karyawan (Admin ID 4, Seller & Agent)
     */
    public function registerKaryawan(Request $request)
    {
        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;
        $userRole = strtolower($user->role ?? '');

        // 1. Pengecekan Otorisasi: Pastikan Admin (ID 4), Agent, atau Seller yang lolos
        $isAllowed = in_array($userRole, ['admin', 'agent', 'seller']) || $userId == 4;

        if (!$isAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin, Agent, atau Seller yang dapat mendaftarkan karyawan.'
            ], 403);
        }

        // 🔥 PERBAIKAN 1: Tangkap string kosong dari frontend dan paksa jadi NULL SEBELUM di validasi
        $input = $request->all();
        if (empty($input['email'])) {
            $input['email'] = null;
        }
        if (empty($input['pin'])) {
            $input['pin'] = null;
        }
        // Masukkan kembali input yang sudah dibersihkan ke dalam request
        $request->merge($input);

        // 2. Validasi Input Karyawan
        $validator = Validator::make($request->all(), [
            'nama_lengkap'  => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
            
            // 🔥 PERBAIKAN 2: Tambahkan ->whereNull('deleted_at') agar data yang sudah dihapus tidak dianggap
            'no_wa'         => ['required', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->whereNull('deleted_at')],
            'email'         => ['nullable', 'email', 'max:255', Rule::unique('Pengguna', 'email')->whereNull('deleted_at')],
            
            'password'      => ['required', 'string', 'min:6'],
            'pin'           => ['nullable', 'string', 'max:6'], 
        ], [
            'no_wa.unique' => 'Nomor WhatsApp ini sudah terdaftar sebagai pengguna lain.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid. Silakan cek kembali.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 3. Buat Data Karyawan Baru
            $karyawan = new User(); 
            $karyawan->parent_id    = $userId; // Relasi ke Pemilik Toko
            $karyawan->nama_lengkap = $request->nama_lengkap;
            $karyawan->no_wa        = $request->no_wa;
            
            // Data opsional, karena sudah dipaksa jadi null di atas, langsung aman disimpan
            $karyawan->email         = $request->email;
            $karyawan->jenis_kelamin = $request->filled('jenis_kelamin') ? $request->jenis_kelamin : null;
            
            // Enkripsi Password & PIN
            $karyawan->password_hash = bcrypt($request->password);
            if ($request->filled('pin')) {
                $karyawan->pin = bcrypt($request->pin); 
            }

            // Set Role Khusus
            $karyawan->role = 'Karyawan'; 
            
            // Wariskan Data Toko dari Pemilik (Admin/Agent/Seller)
            $karyawan->store_name       = $user->store_name ?? ('Cabang ' . $user->nama_lengkap);
            $karyawan->store_logo_path  = $user->store_logo_path;
            $karyawan->province         = $user->province;
            $karyawan->regency          = $user->regency;
            $karyawan->district         = $user->district;
            $karyawan->village          = $user->village;
            $karyawan->address_detail   = $user->address_detail;

            // Status Default
            $karyawan->status      = 'Aktif';
            $karyawan->is_verified = 1; 
            
            $karyawan->save();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil didaftarkan!',
                'data'    => $karyawan
            ]);

        } catch (\Exception $e) {
            Log::error('API Register Karyawan Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan data.'
            ], 500);
        }
    }

    /**
     * Menampilkan daftar Karyawan milik Seller/Agent
     */
    public function listKaryawan(Request $request)
    {
        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        // Ambil semua pengguna yang parent_id nya adalah pemilik ini
        // 🔥 PASTIKAN TABEL PENGGUNA ANDA SUDAH ADA KOLOM 'can_access_pos' (TINYINT 1 DEFAULT 0)
        $karyawans = User::where('parent_id', $userId)->get();

        return response()->json([
            'success' => true,
            'data'    => $karyawans
        ]);
    }

    /**
     * Toggle/Ubah Hak Akses Menu Kasir untuk Karyawan
     */
    public function toggleAksesKasir(Request $request)
    {
        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        $request->validate([
            'karyawan_id' => 'required|integer',
            'akses_pos'   => 'required|in:0,1'
        ]);

        // Pastikan karyawan yang mau diubah benar-benar milik owner ini
        $karyawan = User::where('id_pengguna', $request->karyawan_id)
                        ->where('parent_id', $userId)
                        ->first();

        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan atau Anda tidak memiliki akses ke akun ini.'
            ], 404);
        }

        // Simpan Hak Akses
        $karyawan->can_access_pos = $request->akses_pos;
        $karyawan->save();

        return response()->json([
            'success' => true,
            'message' => 'Hak akses berhasil diperbarui.'
        ]);
    }

    /**
     * Memperbarui Hak Akses Menu Karyawan
     */
    public function updateAksesKaryawan(Request $request)
    {
        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        $request->validate([
            'karyawan_id' => 'required|integer',
            'akses_menu'  => 'required|array' // Pastikan ini array (misal: ['kasir' => true, 'laporan' => false])
        ]);

        // Pastikan karyawan tersebut benar-benar anak buah dari user yang sedang login
        $karyawan = User::where('id_pengguna', $request->karyawan_id)
                        ->where('parent_id', $userId)
                        ->first();

        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan atau Anda tidak memiliki akses ke akun ini.'
            ], 404);
        }

        // Simpan akses menu dalam bentuk JSON
        $karyawan->akses_menu = json_encode($request->akses_menu);
        $karyawan->save();

        return response()->json([
            'success' => true,
            'message' => 'Hak akses karyawan berhasil diperbarui.',
            'data'    => $request->akses_menu
        ]);
    }
}
