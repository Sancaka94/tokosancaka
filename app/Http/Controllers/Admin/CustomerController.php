<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RegistrationRequest;
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
use App\Models\Store; // ✅ DITAMBAHKAN

class CustomerController extends Controller
{
    /**
     * Menampilkan halaman daftar pelanggan dan permintaan pendaftaran.
     */
    public function index()
    {
        $requests = User::where('status', 'Tidak Aktif')->orderBy('created_at', 'desc')->get();
    $customers = User::whereIn('role', ['pelanggan', 'seller'])
    ->orderBy('created_at', 'desc')
    ->paginate(15);

        return view('admin.customers.index', compact('requests', 'customers'));
    }

    /**
     * Menyetujui permintaan pendaftaran dan membuat akun pengguna baru.
     */
    public function approve($id)
    {
        $request = User::where('id_pengguna',$id)->first();

        if (User::where('email', $request->email)->exists()) {
            $request->update(['status' => 'approved_duplicate']);
            return Redirect::route('admin.customers.index')->with('error', 'Email ' . $request->email . ' sudah terdaftar.');
        }

        $request->update(['status' => 'Aktif']);

        $setupUrl = url("/customer/profile/setup/{$requestData->setup_token}");

        $message = "Selamat! Pendaftaran Anda di Toko Sancaka telah disetujui. Silakan klik link berikut untuk menyelesaikan profil Anda: " . $setupUrl;
        $whatsappUrl = "https://api.whatsapp.com/send?phone=" . $request->no_wa . "&text=" . urlencode($message);

        return Redirect::route('admin.customers.index')
            ->with('success', 'Pendaftaran untuk ' . $newUser->nama_lengkap . ' berhasil disetujui!')
            ->with('whatsapp_url', $whatsappUrl);
    }

    /**
     * Menampilkan form untuk mengedit data pelanggan.
     */
    public function edit(User $customer)
    {
        $provinces = DB::table('reg_provinces')->get();
        return view('admin.customers.edit', [
            'customer' => $customer, // PERBAIKAN: Menggunakan 'customer' secara konsisten
            'provinces' => $provinces
        ]);
    }

    /**
     * Memperbarui data pelanggan di database.
     */
    public function update(Request $request, User $customer)
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('Pengguna', 'email')->ignore($customer->id_pengguna, 'id_pengguna')],
            'no_wa' => ['required', 'string', 'max:15'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(['Admin', 'Pelanggan'])],
            'province_id' => ['required', 'exists:reg_provinces,id'],
            'regency_id' => ['required', 'exists:reg_regencies,id'],
            'district_id' => ['required', 'exists:reg_districts,id'],
            'village_id' => ['required', 'exists:reg_villages,id'],
            'address_detail' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'confirmed', Password::min(8)],
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $customer->update($validated);

        return redirect()->route('admin.customers.index')->with('success', 'Data pelanggan ' . $customer->nama_lengkap . ' berhasil diperbarui.');
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
                    $customer->email_verified_at ? 'Terverifikasi' : 'Belum Verifikasi',
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
     * Mengirim ulang link setup profil ke pengguna.
     */
    public function sendSetupLink(User $customer)
    {
        if (empty($customer->setup_token)) {
            $customer->setup_token = Str::uuid();
            $customer->save();
        }

        $setupUrl = URL::temporarySignedRoute(
            'profile.setup',
            now()->addHours(48),
            ['user' => $customer->id_pengguna]
        );

        $phoneNumber = $customer->no_wa;
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '62' . substr($phoneNumber, 1);
        }

        $message  = "Halo " . $customer->nama_lengkap . ",\n\n";
        $message .= "Berikut adalah link untuk melengkapi profil dan mengatur password Anda. Link ini berlaku 48 jam.\n\n";
        $message .= $setupUrl;

        $whatsappUrl = "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Link setup berhasil dibuat untuk ' . $customer->nama_lengkap . '!')
            ->with('whatsapp_url', $whatsappUrl);
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
    public function addSaldo(Request $request, User $customer) // PERBAIKAN: Menggunakan Route Model Binding
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
