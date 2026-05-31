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
use Illuminate\Support\Facades\Mail;

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
        $request->status = 'Aktif';

        if (empty($request->setup_token)) {
            $request->setup_token = Str::random(40); 
        }
        $request->save();

        $setupUrl = route('customer.profile.setup', ['token' => $request->setup_token]);
        
        $phoneNumber = $request->no_wa;
        $userEmail = $request->email;
        $waStatus = "";
        $emailStatus = "";

        // 2. LOGIKA PENGIRIMAN EMAIL VIA SMTP
        try {
            $subject = "Pendaftaran Disetujui - Aktivasi Akun Toko Sancaka";
            $bodyHtml = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2>Selamat datang, {$request->nama_lengkap}!</h2>
                    <p>Pendaftaran Anda di <strong>Toko Sancaka</strong> telah disetujui dan akun Anda sudah aktif.</p>
                    <p>Silakan klik tombol di bawah ini untuk melengkapi profil dan mengatur password akun Anda (Link berlaku 48 jam):</p>
                    <p style='margin: 20px 0;'>
                        <a href='{$setupUrl}' style='background-color: #0d6efd; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Aktivasi Akun Saya</a>
                    </p>
                    <p>Atau copy-paste link berikut ke browser Anda:</p>
                    <p><a href='{$setupUrl}'>{$setupUrl}</a></p>
                    <p>Jika ada kendala, jangan ragu membalas email ini atau hubungi Admin via WhatsApp.</p>
                    <br>
                    <p>Terima kasih,<br><strong>Tim Toko Sancaka</strong></p>
                </div>
            ";

            Mail::html($bodyHtml, function ($message) use ($userEmail, $subject) {
                $message->to($userEmail)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("Email aktivasi berhasil dikirim ke: " . $userEmail);
            $emailStatus = " & Email";
        } catch (\Exception $e) {
            Log::error("Gagal kirim email aktivasi ke {$userEmail}: " . $e->getMessage());
            $emailStatus = " (Email gagal)";
        }

        // 3. LOGIKA PENGIRIMAN WHATSAPP VIA FONNTE SERVICE
        try {
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '62' . substr($phoneNumber, 1);
            }

            $message = "Selamat datang, *{$request->nama_lengkap}*! Pendaftaran Anda di Toko Sancaka telah disetujui.\n\n" .
                       "Akun Anda sudah aktif. Jika ada kendala, jangan ragu WA ke ADMIN +6285745808809\n\n" .
                       "Silakan klik link berikut untuk melengkapi profil dan mengatur password Anda (Link berlaku 48 jam):\n" .
                       $setupUrl . "\n\n" .
                       "Terima kasih!";

            $response = $fonnteService->sendMessage($phoneNumber, $message);

            if ($response && isset($response['status']) && $response['status'] === 'success') {
                Log::info('WA Approved dikirim via Fonnte: ' . $phoneNumber);
                $waStatus = "notifikasi WA{$emailStatus} berhasil dikirim.";
            } else {
                Log::error('Gagal kirim WA Approved via Fonnte. Respon: ' . json_encode($response));
                $waStatus = "GAGAL kirim WA{$emailStatus}.";
            }

        } catch (\Exception $e) {
            Log::error('Exception saat kirim WA via Fonnte Service: ' . $e->getMessage());
            $waStatus = "terjadi kesalahan koneksi WA{$emailStatus}.";
        }

        // 4. REDIRECT DAN PESAN SUKSES
        return Redirect::route('admin.customers.index')
            ->with('success', "Pendaftaran {$request->nama_lengkap} disetujui, " . $waStatus);
    }

    /**
     * Mengirim ulang link setup profil ke pengguna menggunakan FonnteService & Email.
     */
    public function sendSetupLink(User $customer, FonnteService $fonnteService)
    {
        if (empty($customer->setup_token)) {
            $customer->setup_token = Str::random(40);
            $customer->save();
        }

        $setupUrl = route('customer.profile.setup', ['token' => $customer->setup_token]);
        $phoneNumber = $customer->no_wa;
        $userEmail = $customer->email;
        $waStatus = "";
        $emailStatus = "";

        // 1. LOGIKA KIRIM EMAIL
        try {
            $subject = "Kirim Ulang: Link Aktivasi Akun Toko Sancaka";
            $bodyHtml = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2>Halo {$customer->nama_lengkap},</h2>
                    <p>Sesuai permintaan, kami mengirimkan ulang link untuk melengkapi profil dan mengatur password akun Toko Sancaka Anda.</p>
                    <p style='margin: 20px 0;'>
                        <a href='{$setupUrl}' style='background-color: #198754; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Aktivasi / Atur Password</a>
                    </p>
                    <p>Atau copy-paste link berikut ke browser Anda:</p>
                    <p><a href='{$setupUrl}'>{$setupUrl}</a></p>
                    <p><em>Jika Anda sudah mengatur password, abaikan pesan ini.</em></p>
                </div>
            ";

            Mail::html($bodyHtml, function ($message) use ($userEmail, $subject) {
                $message->to($userEmail)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("Email kirim ulang aktivasi berhasil dikirim ke: " . $userEmail);
            $emailStatus = " & Email";
        } catch (\Exception $e) {
            Log::error("Gagal kirim ulang email aktivasi ke {$userEmail}: " . $e->getMessage());
            $emailStatus = " (Email gagal)";
        }

        // 2. LOGIKA KIRIM WHATSAPP
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '62' . substr($phoneNumber, 1);
        }

        $message  = "Halo *{$customer->nama_lengkap}*,\n\n";
        $message .= "Kami mengirim ulang link untuk melengkapi profil dan mengatur password akun Toko Sancaka Anda.\n\n";
        $message .= "Link Setup:\n{$setupUrl}\n\n";
        $message .= "Jika Anda sudah mengatur password, abaikan pesan ini. Terima kasih.";

        try {
            $response = $fonnteService->sendMessage($phoneNumber, $message);

            if ($response && isset($response['status']) && $response['status'] === 'success') {
                Log::info('Link Setup WA berhasil dikirim via Fonnte ke: ' . $phoneNumber);
                $waStatus = "notifikasi WA{$emailStatus} berhasil dikirim.";
            } else {
                Log::error('Gagal kirim Link Setup WA via Fonnte. Respon: ' . json_encode($response));
                $waStatus = "GAGAL kirim notifikasi WA{$emailStatus}.";
            }
        } catch (\Exception $e) {
            Log::error('Exception saat kirim Link Setup WA via Fonnte: ' . $e->getMessage());
            $waStatus = "terjadi kesalahan pada WA{$emailStatus}.";
        }

        return redirect()->route('admin.customers.index')
            ->with('success', "Link setup dibuat dan dikirim ke {$customer->nama_lengkap}. Status: {$waStatus}");
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