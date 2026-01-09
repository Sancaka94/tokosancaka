<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; // Model Anda yang menunjuk ke tabel 'Pengguna'
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

class UserController extends Controller
{
    /**
     * Menampilkan daftar semua pengguna (halaman terpisah).
     * URL: GET /admin/users
     */
    public function index()
    {
        // Ambil semua pengguna dengan pagination
        $users = User::paginate(20);
        
        // Anda perlu membuat view 'admin.users.index'
        return view('admin.users.index', compact('users'));
    }

    /**
     * Menampilkan form untuk membuat pengguna baru.
     * URL: GET /admin/users/create
     */
    public function create()
    {
        // Data untuk dropdown
        $roles = ['Admin', 'Seller', 'Pelanggan'];
        $statuses = ['Aktif', 'Tidak Aktif', 'Dibekukan'];

        // Anda perlu membuat view 'admin.users.create'
        return view('admin.users.create', compact('roles', 'statuses'));
    }

    /**
     * Menyimpan pengguna baru ke database.
     * URL: POST /admin/users
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('Pengguna', 'email')],
            'no_wa' => ['nullable', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')],
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|max:50',
            'status' => 'required|string|max:50',
            'store_name' => 'nullable|string|max:255',
            'store_logo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_verified' => 'nullable',
        ]);

        try {
            $createData = [
                'nama_lengkap' => $validatedData['nama_lengkap'],
                'email' => $validatedData['email'],
                'no_wa' => $validatedData['no_wa'],
                'password' => $validatedData['password'], // Mutator di Model User akan hash ini
                'role' => $validatedData['role'],
                'status' => $validatedData['status'],
                'store_name' => $validatedData['store_name'],
                'is_verified' => $request->has('is_verified'),
            ];

            if ($request->hasFile('store_logo_path')) {
                // Simpan ke disk 'public_uploads' yang kita buat
                $createData['store_logo_path'] = $request->file('store_logo_path')->store('profile-photos', 'public_uploads');
            }

            User::create($createData);

            return redirect()->route('admin.settings.index')->with('success', 'Pengguna baru berhasil ditambahkan.');

        } catch (Exception $e) {
            Log::error('Gagal simpan user baru: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menyimpan pengguna: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan detail satu pengguna. (Untuk Tombol LIHAT)
     * URL: GET /admin/users/{user}
     */
    public function show(User $user)
    {
        // 'User $user' otomatis mengambil data berkat Route Model Binding
        // Ini menggunakan view 'admin.users.show' yang saya berikan sebelumnya
        return view('admin.users.show', compact('user'));
    }

    /**
     * Menampilkan form edit untuk satu pengguna. (Untuk Tombol EDIT)
     * URL: GET /admin/users/{user}/edit
     */
    public function edit(User $user)
    {
        $roles = ['Admin', 'Seller', 'Pelanggan'];
        $statuses = ['Aktif', 'Tidak Aktif', 'Dibekukan'];

        // Ini menggunakan view 'admin.users.edit' yang saya berikan sebelumnya
        return view('admin.users.edit', compact('user', 'roles', 'statuses'));
    }

    /**
     * Memperbarui pengguna di database. (Untuk form EDIT)
     * URL: PUT/PATCH /admin/users/{user}
     */
    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('Pengguna', 'email')->ignore($user->id_pengguna, 'id_pengguna')],
            'no_wa' => ['nullable', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')],
            'password' => 'nullable|string|min:8|confirmed', // Password opsional
            'role' => 'nullable|string|max:50',
            'status' => 'required|string|max:50',
            'store_name' => 'nullable|string|max:255',
            'store_logo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_verified' => 'nullable',
        ]);

        try {
            $updateData = [
                'nama_lengkap' => $validatedData['nama_lengkap'],
                'email' => $validatedData['email'],
                'no_wa' => $validatedData['no_wa'],
                'role' => $validatedData['role'],
                'status' => $validatedData['status'],
                'store_name' => $validatedData['store_name'],
                'is_verified' => $request->has('is_verified'),
            ];

            // 1. Hanya update password JIKA diisi
            if (!empty($validatedData['password'])) {
                $updateData['password'] = $validatedData['password']; // Mutator akan hash
            }

            // 2. Handle upload foto profil baru
            if ($request->hasFile('store_logo_path')) {
                // Hapus foto lama
                if ($user->store_logo_path && Storage::disk('public_uploads')->exists($user->store_logo_path)) {
                    Storage::disk('public_uploads')->delete($user->store_logo_path);
                }
                // Simpan foto baru
                $updateData['store_logo_path'] = $request->file('store_logo_path')->store('profile-photos', 'public_uploads');
            }

            $user->update($updateData);

            // Kembali ke halaman pengaturan
            return redirect()->route('admin.settings.index')->with('success', 'Data pengguna ' . $user->nama_lengkap . ' berhasil diperbarui.');

        } catch (Exception $e) {
            Log::error('Gagal update user: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal memperbarui pengguna: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus pengguna dari database. (Untuk Tombol HAPUS)
     * URL: DELETE /admin/users/{user}
     */
    public function destroy(User $user)
    {
        try {
            $nama = $user->nama_lengkap;
            $photoPath = $user->store_logo_path;

            // 1. Hapus pengguna dari database
            $user->delete();

            // 2. Hapus fotonya dari disk
            if ($photoPath && Storage::disk('public_uploads')->exists($photoPath)) {
                Storage::disk('public_uploads')->delete($photoPath);
            }
            
            // Kembali ke halaman pengaturan
            return back()->with('success', "Pengguna '$nama' berhasil dihapus.");

        } catch (Exception $e) {
            Log::error('Gagal hapus user: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus pengguna: ' . $e->getMessage());
        }
    }
    
    /**
     * Mengganti status 'Aktif'/'Dibekukan' untuk seorang pengguna.
     */
    public function toggleFreeze(User $user)
    {
        try {
            // Logika Toggle
            // Ganti 'Dibekukan' dengan nama status yang Anda gunakan (misal: 'Beku' atau 'Tidak Aktif')
            $newStatus = ($user->status == 'Aktif') ? 'Dibekukan' : 'Aktif'; 
            
            $user->status = $newStatus;
            $user->save();

            // Kirim balasan sukses ke JavaScript
            return response()->json([
                'success' => true,
                'new_status' => $newStatus,
                'message' => 'Status pengguna berhasil diperbarui.'
            ]);

        } catch (\Exception $e) {
            // Kirim balasan error ke JavaScript
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }
}