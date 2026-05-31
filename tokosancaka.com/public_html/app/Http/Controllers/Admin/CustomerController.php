<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TopUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Store;
use App\Services\FonnteService;

class CustomerController extends Controller
{
    /**
     * Menampilkan halaman daftar pelanggan dan permintaan pendaftaran.
     */
    public function index()
    {
        $requests = User::where('status', 'Tidak Aktif')->orderBy('created_at', 'desc')->get();
        $customers = User::whereIn('role', ['pelanggan', 'seller', 'Pelanggan', 'Seller'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.customers.index', compact('requests', 'customers'));
    }

    /**
     * Menyetujui permintaan pendaftaran dan membuat akun pengguna baru.
     */
    public function approve($id, FonnteService $fonnteService)
    {
        $request = User::where('id_pengguna', $id)->firstOrFail();

        if (User::where('email', $request->email)->where('id_pengguna', '!=', $id)->exists()) {
            $request->update(['status' => 'approved_duplicate']);
            return Redirect::route('admin.customers.index')->with('error', 'Email ' . $request->email . ' sudah terdaftar pada akun lain.');
        }

        // 1. UPDATE STATUS PENGGUNA
        $request->update(['status' => 'Aktif']);

        // Pastikan setup_token ada
        if (empty($request->setup_token)) {
            $request->setup_token = Str::uuid();
            $request->save();
        }

        $setupUrl = url("/customer/profile/setup/{$request->setup_token}");
        $phoneNumber = $request->no_wa;
        $waStatus = "";

        // 2. LOGIKA PENGIRIMAN WHATSAPP VIA FONNTE SERVICE
        try {
            // Bersihkan nomor telepon: Ganti '0' di depan menjadi '62'
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '62' . substr($phoneNumber, 1);
            }

            // Pesan WA yang akan dikirim
            $message = "Selamat datang, {$request->nama_lengkap}! Pendaftaran Anda di Toko Sancaka telah disetujui. " .
                       "Akun Anda sudah aktif Ya kak, Jika ada Kendala yang kakak alami jangan ragu WA ke ADMIN +6285745808809\n\n" .
                       "Silakan klik link berikut untuk melengkapi profil dan mengatur password Anda (Link berlaku 48 jam):\n" .
                       $setupUrl . "\n\n" .
                       "Terima kasih!";

            // Panggil Service Fonnte
            $response = $fonnteService->sendMessage($phoneNumber, $message);

            // Cek respon
            if ($response && isset($response['status']) && $response['status'] === 'success') {
                Log::info('WA Approved dikirim via Fonte: ' . $phoneNumber);
                $waStatus = " dan notifikasi WA berhasil dikirim via Fonnte.";
            } else {
                Log::error('Gagal kirim WA Approved via Fonnte. Respon: ' . json_encode($response));
                $waStatus = " namun GAGAL mengirim notifikasi WA via Fonnte. (Cek log server)";
            }

        } catch (\Exception $e) {
            Log::error('Exception saat kirim WA via Fonnte Service: ' . $e->getMessage());
            $waStatus = " namun terjadi kesalahan saat mencoba mengirim WA via Fonnte Service.";
        }

        // 3. REDIRECT DAN PESAN SUKSES
        return Redirect::route('admin.customers.index')
            ->with('success', 'Pendaftaran untuk ' . $request->nama_lengkap . ' berhasil disetujui' . $waStatus);
    }

    /**
     * Menampilkan detail satu pelanggan.
     */
    public function show(User $customer)
    {
        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Menampilkan form untuk mengedit data pelanggan.
     */
    public function edit(User $customer)
    {
        // PERBAIKAN: Karena frontend sudah menggunakan Alpine.js (KiriminAja) yang menyimpan
        // data lokasi murni sebagai STRING (bukan ID referensi tabel), maka kita TIDAK PERLU 
        // lagi melakukan query berat ke tabel reg_provinces dll.
        // Cukup kirim model usernya ke view.

        return view('admin.customers.edit', [
            'user' => $customer,
        ]);
    }

   /**
     * Memperbarui data pelanggan di database (TERMASUK EDIT & SET PIN).
     */
    public function update(Request $request, User $customer)
    {
        // 1. Validasi Dasar + PIN + Alamat (Format Baru String)
        $validated = $request->validate([
            'id_pengguna'    => ['nullable'], // Tergantung kebutuhan, biasanya ID tidak diubah
            'nama_lengkap'   => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', Rule::unique('Pengguna', 'email')->ignore($customer->id_pengguna, 'id_pengguna')],
            'no_wa'          => ['required', 'string', 'max:25'],
            'store_name'     => ['nullable', 'string', 'max:255'],
            'store_logo_path'=> ['nullable', 'string', 'max:255'],
            'role'           => ['required', 'string', Rule::in(['Admin', 'Pelanggan', 'Seller'])],
            'status'         => ['required', 'string'],
            'is_verified'    => ['nullable', 'boolean'],

            // ---> PERBAIKAN: Hapus province_id dll, ganti dengan format String <---
            'province'       => ['nullable', 'string', 'max:255'],
            'regency'        => ['nullable', 'string', 'max:255'],
            'district'       => ['nullable', 'string', 'max:255'],
            'village'        => ['nullable', 'string', 'max:255'],
            'postal_code'    => ['nullable', 'string', 'max:20'],
            'address_detail' => ['nullable', 'string', 'max:500'],
            'latitude'       => ['nullable', 'string', 'max:50'],
            'longitude'      => ['nullable', 'string', 'max:50'],

            // Keuangan & Bank
            'saldo'               => ['nullable', 'numeric'],
            'dana_user_balance'   => ['nullable', 'numeric'],
            'balance_iak'         => ['nullable', 'numeric'],
            'bank_name'           => ['nullable', 'string', 'max:255'],
            'bank_account_name'   => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],

            // API Tokens & Meta
            'expo_token'        => ['nullable', 'string'],
            'dana_access_token' => ['nullable', 'string'],
            'dana_auth_code'    => ['nullable', 'string'],
            'dana_user_name'    => ['nullable', 'string'],
            'setup_token'       => ['nullable', 'string'],
            'reset_token'       => ['nullable', 'string'],
            'token_expiry'      => ['nullable', 'date'],
            'created_at'        => ['nullable', 'date'],
            'deleted_at'        => ['nullable', 'date'],
            'last_seen_at'      => ['nullable', 'date'],
            'last_seen'         => ['nullable', 'date'],
            'ip_address'        => ['nullable', 'string'],
            'user_agent'        => ['nullable', 'string'],

            'pin'            => ['nullable', 'digits:6'], 
        ]);

        // Buang password dan pin dari array validated agar tidak tersimpan sebagai teks biasa
        $updateData = collect($validated)->except(['password', 'pin'])->toArray();

        // 2. Logika Update Password (Opsional)
        if ($request->filled('password')) {
            // Asumsi tidak pakai confirmation di frontend baru
            $request->validate([
                'password' => ['required', 'string', 'min:8'],
            ]);
            $updateData['password_hash'] = Hash::make($request->password);
        }

        // 3. Logika Update PIN Transaksi (Opsional)
        if ($request->filled('pin')) {
            $updateData['pin'] = Hash::make($request->pin);
        }

        // 4. Simpan Perubahan
        $customer->update($updateData);

        return redirect()->route('admin.customers.index')
                         ->with('success', 'Data pelanggan ' . $customer->nama_lengkap . ' berhasil diperbarui.');
    }

    /**
     * Mengekspor data pelanggan ke file CSV.
     */
    public function exportCSV()
    {
        $fileName = "daftar-pelanggan-" . date('Y-m-d') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Nama Lengkap', 'Email', 'No. WA', 'Nama Toko', 'Role', 'Status Verifikasi', 'Tanggal Daftar']);

            $customers = User::where('role', 'pelanggan')->get();

            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->id_pengguna,
                    $customer->nama_lengkap,
                    $customer->email,
                    $customer->no_wa,
                    $customer->store_name,
                    $customer->role,
                    $customer->is_verified ? 'Terverifikasi' : 'Belum Verifikasi', // Disesuaikan dari email_verified_at ke is_verified
                    $customer->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Menyiapkan data untuk diekspor ke PDF.
     */
    public function exportPDF()
    {
        $customers = User::where('role', 'pelanggan')->get();
        return view('admin.customers.print', compact('customers'));
    }

   /**
     * Mengirim ulang link setup profil ke pengguna menggunakan FonnteService.
     */
    public function sendSetupLink(User $customer, FonnteService $fonnteService)
    {
        // 1. Pastikan token tersedia
        if (empty($customer->setup_token)) {
            $customer->setup_token = Str::uuid();
            $customer->save();
        }

        // 2. Buat URL setup
        $setupUrl = url("/customer/profile/setup/{$customer->setup_token}");

        // 3. Format nomor dan buat pesan
        $phoneNumber = $customer->no_wa;
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '62' . substr($phoneNumber, 1);
        }

        $message  = "Halo {$customer->nama_lengkap},\n\n";
        $message .= "Kami mengirim ulang link untuk melengkapi profil dan mengatur password akun Toko Sancaka Anda.\n\n";
        $message .= "Link Setup:\n{$setupUrl}\n\n";
        $message .= "Jika Anda sudah mengatur password, abaikan pesan ini. Terima kasih.";
        $waStatus = "";

        // 4. KIRIM WA VIA FONNTE SERVICE
        try {
            $response = $fonnteService->sendMessage($phoneNumber, $message);

            if ($response && isset($response['status']) && $response['status'] === 'success') {
                Log::info('Link Setup WA berhasil dikirim via Fonnte ke: ' . $phoneNumber);
                $waStatus = " dan notifikasi WA berhasil dikirim via Fonnte.";
            } else {
                Log::error('Gagal kirim Link Setup WA via Fonnte. Respon: ' . json_encode($response));
                $waStatus = " namun GAGAL mengirim notifikasi WA via Fonnte. (Cek log server)";
            }
        } catch (\Exception $e) {
            Log::error('Exception saat kirim Link Setup WA via Fonnte: ' . $e->getMessage());
            $waStatus = " namun terjadi kesalahan saat mencoba mengirim WA via Fonnte Service.";
        }

        // 5. Redirect ke halaman index
        return redirect()->route('admin.customers.index')
            ->with('success', 'Link setup berhasil dibuat dan dikirim ke ' . $customer->nama_lengkap . $waStatus);
    }

    /**
     * Menghapus data pelanggan.
     */
    public function destroy(User $customer)
    {
        $userName = $customer->nama_lengkap;
        $customer->forceDelete();
        return redirect()->route('admin.customers.index')->with('success', 'Data pelanggan ' . $userName . ' berhasil dihapus.');
    }

    /**
     * Menambahkan saldo ke customer.
     */
    public function addSaldo(Request $request, User $customer)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
        ]);

        try {
            DB::transaction(function () use ($request, $customer) {
                $customer->saldo = ($customer->saldo ?? 0) + $request->input('amount');
                $customer->save();

                TopUp::create([
                    'customer_id' => $customer->id_pengguna,
                    'transaction_id' => 'ADMIN-' . strtoupper(uniqid()),
                    'amount' => $request->input('amount'),
                    'status' => 'success',
                    'payment_method' => 'admin_manual',
                ]);
            });

            return redirect()->route('admin.customers.index')->with('success', 'Saldo berhasil ditambahkan untuk ' . $customer->nama_lengkap);

        } catch (\Exception $e) {
            Log::error('Gagal menambahkan saldo untuk customer ID ' . $customer->id_pengguna . ': ' . $e->getMessage());
            return redirect()->route('admin.customers.index')->with('error', 'Gagal menambahkan saldo. Terjadi kesalahan server.');
        }
    }

    // --- METHOD BARU UNTUK MENGUBAH PELANGGAN MENJADI PENJUAL ---

    /**
     * Menampilkan daftar semua pelanggan untuk diubah menjadi penjual.
     */
    public function indexForStores()
    {
        $customers = User::whereIn('role', ['Pelanggan', 'Seller'])
                          ->with('store')
                          ->latest('id_pengguna')
                          ->paginate(20);

        return view('admin.customer-to-seller.index', compact('customers'));
    }

    /**
     * Menampilkan form untuk membuat toko bagi pelanggan yang dipilih.
     */
    public function createStore(User $user)
    {
        if ($user->store) {
            return redirect()->route('admin.stores.edit', $user->store->id)
                             ->with('info', 'Pelanggan ini sudah memiliki toko. Anda bisa langsung mengeditnya di sini.');
        }

        return view('admin.customer-to-seller.create', compact('user'));
    }

    /**
     * Menyimpan toko baru dan mengubah role pelanggan.
     */
    public function storeStore(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:stores',
            'description' => 'required|string|min:10',
        ]);

        Store::create([
            'user_id' => $user->id_pengguna,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        $user->role = 'Seller';
        $user->save();

        return redirect()->route('admin.dashboard')
                             ->with('success', "Toko '{$request->name}' untuk pelanggan {$user->nama_lengkap} berhasil dibuat.");
    }
}