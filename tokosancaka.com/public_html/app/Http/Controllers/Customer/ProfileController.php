<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\KiriminAjaService; // 🔑 Ditambahkan untuk service API
use Carbon\Carbon; // Ditambahkan untuk timestamp

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman untuk melihat profil pengguna.
     */
    public function show(Request $request)
    {
        return view('customer.profile.show', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Menampilkan form untuk mengedit profil pengguna.
     */
    public function edit(Request $request) {
        return view('customer.profile.edit', [ 'user' => $request->user(), ]);
    }

    /**
     * Memperbarui informasi profil pengguna yang sudah login (Regular Update).
     */
    public function update(Request $request)
    {
        $user = $request->user();

        try {
            // --- VALIDASI DENGAN EXCEPTION UNTUK 'unique:Pengguna' ---
            $validated = $request->validate([
                'nama_lengkap'          => ['required', 'string', 'max:255'],
                // 🔑 PERBAIKAN: Menambahkan Rule::unique untuk No. WA yang diperbarui
                'no_wa'                 => ['required', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')],
                'store_name'            => ['nullable', 'string', 'max:255'],
                'store_logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
                'bank_name'             => ['nullable', 'string', 'max:255'],
                'bank_account_name'     => ['nullable', 'string', 'max:255'],
                'bank_account_number'   => ['nullable', 'string', 'max:255'],
                'province'              => ['required', 'string', 'max:255'],
                'regency'               => ['required', 'string', 'max:255'],
                'district'              => ['required', 'string', 'max:255'],
                'village'               => ['required', 'string', 'max:255'],
                'postal_code'           => ['nullable', 'string', 'max:10'],
                'address_detail'        => ['required', 'string'],
                'latitude'              => ['required', 'numeric', 'between:-90,90'],
                'longitude'             => ['required', 'numeric', 'between:-180,180'],
            ], [
                'latitude.required'     => 'Latitude wajib diisi. Gunakan tombol "Cari Koordinat".',
                'longitude.required'    => 'Longitude wajib diisi. Gunakan tombol "Cari Koordinat".',
                'no_wa.unique'          => 'Nomor WhatsApp sudah digunakan oleh pengguna lain.',
            ]);

            if ($request->hasFile('store_logo')) {
                // Hapus logo lama jika ada
                if ($user->store_logo_path) {
                    Storage::disk('public')->delete($user->store_logo_path);
                }
                $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
                $user->store_logo_path = $path;
            }

            $user->fill($validated);
            $user->save();

            return redirect()
                ->route('customer.profile.show')
                ->with('success', 'Profil Anda berhasil diperbarui.');

        } catch (\Throwable $e) {
            Log::error('Update profile gagal: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui profil. Silakan coba lagi.');
        }
    }

    /**
     * Menampilkan Form Input OTP
     */
    public function showOtpForm()
    {
        $user = auth()->user();

        // Jika status sudah aktif, jangan izinkan akses halaman OTP lagi
        if ($user->status === 'Aktif') {
            return redirect()->route('customer.dashboard');
        }

        return view('customer.profile.otp');
    }

    /**
     * Memproses Verifikasi Inputan OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6'
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size'     => 'Kode OTP harus 6 karakter.'
        ]);

        $user = auth()->user();

        // Cek apakah input OTP sama dengan yang ada di database
        if (strtoupper($user->setup_token) === strtoupper($request->otp)) {
            // Jika valid, arahkan ke halaman isi form profil lengkap
            return redirect()->route('customer.profile.setup')->with('success', 'OTP Valid! Silakan lengkapi data profil Anda.');
        }

        return redirect()->back()->with('error', 'Kode OTP yang Anda masukkan salah.');
    }

    /**
     * Menampilkan form setup profil (Tanpa Token di URL).
     * Route: customer/profile/setup
     */
    public function setup(Request $request)
    {
        $user = auth()->user();

        // Jika sudah aktif, lempar ke dashboard
        if ($user->status === 'Aktif') {
            return redirect()->route('customer.dashboard');
        }

        return view('customer.profile.setup', [
            'user' => $user
        ]);
    }

    /**
     * Memperbarui informasi profil dari form setup.
     * Route: customer/profile/update-setup
     */
    public function updateSetup(Request $request)
    {
        $user = auth()->user();

        // --- VALIDASI SAMA DENGAN UPDATE BIASA ---
        $validated = $request->validate([
            'nama_lengkap'          => ['required', 'string', 'max:255'],
            'no_wa'                 => ['required', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')],
            'store_name'            => ['nullable', 'string', 'max:255'],
            'store_logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'bank_name'             => ['nullable', 'string', 'max:255'],
            'bank_account_name'     => ['nullable', 'string', 'max:255'],
            'bank_account_number'   => ['nullable', 'string', 'max:255'],
            'province'              => ['required', 'string', 'max:255'],
            'regency'               => ['required', 'string', 'max:255'],
            'district'              => ['required', 'string', 'max:255'],
            'village'               => ['required', 'string', 'max:255'],
            'postal_code'           => ['nullable', 'string', 'max:10'],
            'address_detail'        => ['required', 'string'],
            'latitude'              => ['required', 'numeric', 'between:-90,90'],
            'longitude'             => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitude.required'     => 'Latitude wajib diisi. Gunakan tombol "Cari Koordinat".',
            'longitude.required'    => 'Longitude wajib diisi. Gunakan tombol "Cari Koordinat".',
            'no_wa.unique'          => 'Nomor WhatsApp sudah digunakan oleh pengguna lain.',
        ]);

        if ($request->hasFile('store_logo')) {
            if ($user->store_logo_path) {
                Storage::disk('public')->delete($user->store_logo_path);
            }
            $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
            $user->store_logo_path = $path;
        }

        // 🔑 Proses Akhir Setup
        $user->fill($validated);
        $user->profile_setup_at = Carbon::now(); // Tandai waktu setup selesai
        $user->status = 'Aktif'; // Ubah status menjadi Aktif
        $user->setup_token = null; // Hapus token agar tidak bisa digunakan lagi

        $user->save();

        return redirect()->route('customer.profile.show')
                        ->with('success', 'Aktivasi dan Profil Anda berhasil diselesaikan!');
    }

    public function searchKiriminAjaAddress(Request $request, KiriminAjaService $kiriminAja)
    {
        $query = $request->get('q');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            $apiResponse = $kiriminAja->searchAddress($query);

            if (isset($apiResponse['data']) && !empty($apiResponse['data'])) {

                // 🔑 KUNCI PERBAIKAN: Memetakan data dari API
                $processedData = collect($apiResponse['data'])->map(function ($item) {

                    // Memecah string full_address (asumsi format: Kelurahan, Kecamatan, Kota, Provinsi, Kode Pos)
                    $addressParts = array_map('trim', explode(',', $item['full_address'] ?? ''));

                    return [
                        'province' => $addressParts[3] ?? 'N/A',
                        'regency' => $addressParts[2] ?? 'N/A',
                        'district' => $addressParts[1] ?? 'N/A',
                        'village' => $addressParts[0] ?? 'N/A',
                        'postal_code' => $addressParts[4] ?? 'N/A',
                        'full_address_display' => $item['full_address'] ?? 'Alamat Tidak Terstruktur',
                    ];
                });

                return response()->json($processedData);
            }

            return response()->json([]);

        } catch (\Exception $e) {
            Log::error("KiriminAja API Error in ProfileController: " . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Menampilkan form permohonan penghapusan akun (Bisa diakses Public)
     */
    public function showDeleteAccountForm()
    {
        return view('customer.profile.request_delete');
    }

    /**
     * Memproses permohonan penghapusan akun dari form public
     */
    public function submitDeleteAccountRequest(Request $request)
    {
        // Validasi input dari user
        $validated = $request->validate([
            'email'        => ['required', 'email', 'max:255'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'alasan'       => ['nullable', 'string', 'max:1000'],
        ], [
            'email.required'        => 'Email akun wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
        ]);

        try {
            Log::info('PERMOHONAN PENGHAPUSAN AKUN DITERIMA:', [
                'email'        => $validated['email'],
                'nama_lengkap' => $validated['nama_lengkap'],
                'alasan'       => $validated['alasan'] ?? 'Tidak ada alasan yang diberikan',
                'ip_address'   => $request->ip(),
                'waktu'        => Carbon::now()->toDateTimeString(),
            ]);

            return redirect()->back()->with('success', 'Permohonan penghapusan akun berhasil dikirim. Tim Sancaka Express akan segera menghubungi Anda melalui Email/WhatsApp untuk proses verifikasi.');

        } catch (\Throwable $e) {
            Log::error('Gagal memproses permohonan hapus akun: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');
        }
    }
}
