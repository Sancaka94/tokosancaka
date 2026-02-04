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

class PenggunaController extends Controller
{
    // Tambahkan FonnteService melalui Dependency Injection
    protected $fonnteService;

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    /**
     * Menampilkan halaman daftar pengguna, permintaan pendaftaran, dengan fitur filter & search.
     */
    public function index(Request $request)
    {
        // Mendapatkan query pencarian
        $search = $request->get('search');
        $role_filter = $request->get('role_filter');
        $status_filter = $request->get('status_filter');

        // --- 1. Query Permintaan Pendaftaran (Status 'Tidak Aktif') ---
        $requests = User::where('status', 'Tidak Aktif')
                        ->orderBy('created_at', 'desc')
                        ->get();

        // --- 2. Query Daftar Pengguna (Status 'Aktif') ---
        $query = User::whereIn('role', ['Pelanggan', 'Seller', 'Admin'])
                      ->where('status', 'Aktif')
                      ->latest('created_at');

        // Menerapkan filter pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('store_name', 'like', '%' . $search . '%');
            });
        }
        
        // Menerapkan filter Role
        if ($role_filter && $role_filter !== 'Semua') {
            $query->where('role', $role_filter);
        }

        // Menerapkan filter Status (jika ada kebutuhan filter status lain selain 'Aktif')
        if ($status_filter && $status_filter !== 'Semua') {
             $query->where('status', $status_filter);
        }

        $customers = $query->paginate(15)->appends($request->query());
        
        // Jika permintaan adalah untuk filter/pencarian melalui AJAX
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.customers.partials.table_rows', ['users' => $customers])->render(),
                'pagination' => $customers->links()->render()
            ]);
        }

        // Memuat view dari lokasi yang diminta: admin.customers.data.index
        return view('admin.customers.data.index', compact('requests', 'customers', 'search', 'role_filter', 'status_filter'));
    }
    
    /**
     * Menyetujui permintaan pendaftaran dan membuat akun pengguna baru.
     */
    public function approve($id)
    {
        $request = User::where('id_pengguna', $id)->firstOrFail(); 

        if (User::where('email', $request->email)->where('status', 'Aktif')->exists()) {
            $request->update(['status' => 'approved_duplicate']);
            return Redirect::route('admin.pengguna.index')->with('error', 'Email ' . $request->email . ' sudah terdaftar pada pengguna aktif.');
        }

        if (empty($request->setup_token)) {
            $request->setup_token = Str::uuid();
            $request->save(); 
        }

        $request->update(['status' => 'Aktif']);

        $setupUrl = URL::temporarySignedRoute(
            'profile.setup', 
            now()->addHours(48),
            ['user' => $request->id_pengguna, 'token' => $request->setup_token] 
        ); 

        // --- INTEGRASI FONNTE WA ---
        $phoneNumber = $request->no_wa;
        
        $message  = "Selamat! Pendaftaran Anda di Toko Sancaka telah disetujui.\n\n";
        $message .= "Akun Anda sudah Aktif. Silakan klik link berikut untuk melengkapi profil dan mengatur password (Link berlaku 48 jam):\n";
        $message .= $setupUrl;

        $sent = $this->fonnteService->sendMessage($phoneNumber, $message);
        
        $success_message = 'Pendaftaran untuk ' . $request->nama_lengkap . ' berhasil disetujui!';
        if ($sent) {
             $success_message .= ' Link setup berhasil dikirim via WhatsApp. Jika Kakak ada kendala dapat hubungi Admin Sancaka +6285745808809';
        } else {
             Log::warning('Gagal mengirim WA via Fonnte untuk ID ' . $request->id_pengguna);
             $success_message .= ' Gagal mengirim WA (Cek log Fonnte). Link setup tetap aktif.';
        }

        return Redirect::route('admin.pengguna.index')
            ->with('success', $success_message);
    }

    /**
     * Menampilkan form untuk mengedit data pengguna.
     */
    public function edit(User $customer)
    {
        $provinces = DB::table('reg_provinces')->get();

        // Cari ID Wilayah berdasarkan NAMA TEKS yang tersimpan di tabel Pengguna
        $userProvinceId = DB::table('reg_provinces')->where('name', $customer->province)->value('id');
        
        $userRegencyId = null;
        if ($userProvinceId) {
            $userRegencyId = DB::table('reg_regencies')
                               ->where('name', $customer->regency)
                               ->where('province_id', $userProvinceId)
                               ->value('id');
        }

        $userDistrictId = null;
        if ($userRegencyId) {
            $userDistrictId = DB::table('reg_districts')
                                ->where('name', $customer->district)
                                ->where('regency_id', $userRegencyId)
                                ->value('id');
        }

        $userVillageId = null;
        if ($userDistrictId) {
            $userVillageId = DB::table('reg_villages')
                               ->where('name', $customer->village)
                               ->where('district_id', $userDistrictId)
                               ->value('id');
        }

        return view('admin.customers.edit', [
            'user' => $customer,
            'provinces' => $provinces,
            'userProvinceId' => $userProvinceId,
            'userRegencyId' => $userRegencyId,
            'userDistrictId' => $userDistrictId,
            'userVillageId' => $userVillageId,
        ]);
    }

    /**
     * Memperbarui data pengguna di database.
     */
    public function update(Request $request, User $customer)
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('Pengguna', 'email')->ignore($customer->id_pengguna, 'id_pengguna')],
            'no_wa' => ['required', 'string', 'max:15'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(['Admin', 'Pelanggan', 'Seller'])],
            
            // Mengambil ID untuk validasi
            'province_id' => ['required', 'exists:reg_provinces,id'],
            'regency_id' => ['required', 'exists:reg_regencies,id'],
            'district_id' => ['required', 'exists:reg_districts,id'],
            'village_id' => ['required', 'exists:reg_villages,id'],
            
            'address_detail' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'confirmed', Password::min(8)],
            ]);
            $validated['password_hash'] = Hash::make($request->password); // Menyimpan sebagai password_hash
            unset($validated['password']); // Hapus password field agar tidak error di fill
        }

        // --- Perbaikan: Mengambil NAMA Wilayah dari ID ---
        $provinceName = DB::table('reg_provinces')->where('id', $validated['province_id'])->value('name');
        $regencyName = DB::table('reg_regencies')->where('id', $validated['regency_id'])->value('name');
        $districtName = DB::table('reg_districts')->where('id', $validated['district_id'])->value('name');
        $villageName = DB::table('reg_villages')->where('id', $validated['village_id'])->value('name');
        
        // Data yang siap di-update
        $dataToUpdate = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'no_wa' => $validated['no_wa'],
            'store_name' => $validated['store_name'],
            'role' => $validated['role'],
            'address_detail' => $validated['address_detail'],
            'bank_name' => $request->bank_name,
            'bank_account_name' => $request->bank_account_name,
            'bank_account_number' => $request->bank_account_number,
            'province' => $provinceName,
            'regency' => $regencyName,
            'district' => $districtName,
            'village' => $villageName,
        ];

        if (isset($validated['password_hash'])) {
            $dataToUpdate['password_hash'] = $validated['password_hash'];
        }
        
        $customer->update($dataToUpdate);

        return redirect()->route('admin.pengguna.index')->with('success', 'Data pengguna ' . $customer->nama_lengkap . ' berhasil diperbarui.');
    }

    /**
     * Mengekspor data pengguna ke file CSV (Excel).
     */
    public function exportExcel()
    {
        $fileName = "daftar-pengguna-" . date('Y-m-d') . ".csv";
        $headers = [
            "Content-type"          => "text/csv",
            "Content-Disposition"   => "attachment; filename=$fileName",
            "Pragma"                => "no-cache",
            "Cache-Control"         => "must-revalidate, post-check=0, pre-check=0",
            "Expires"               => "0"
        ];

        $callback = function() {
            $handle = fopen('php://output', 'w');
            // Headers kolom
            fputcsv($handle, [
                'ID', 'Nama Lengkap', 'Email', 'No. WA', 'Nama Toko', 'Role', 'Status', 'Saldo', 'Tgl Daftar', 'Terakhir Dilihat'
            ]);
            
            // Ambil semua data pengguna aktif
            $users = User::where('status', 'Aktif')->get();

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->id_pengguna,
                    $user->nama_lengkap,
                    $user->email,
                    $user->no_wa,
                    $user->store_name,
                    $user->role,
                    $user->status,
                    $user->saldo,
                    $user->created_at ? $user->created_at->format('Y-m-d H:i') : '-',
                    $user->last_seen_at ? $user->last_seen_at->format('Y-m-d H:i') : '-',
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
        $users = User::where('status', 'Aktif')->get();
        // Ini adalah placeholder:
        return redirect()->route('admin.pengguna.index')->with('info', 'Fungsi Export PDF disiapkan. Perlu library PDF.');
    }
    
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

        return redirect()->route('admin.pengguna.index')
            ->with('success', 'Link setup berhasil dibuat untuk ' . $customer->nama_lengkap . '!')
            ->with('whatsapp_url', $whatsappUrl);
    }

    public function destroy(User $customer)
    {
        $userName = $customer->nama_lengkap;
        $customer->forceDelete();
        return redirect()->route('admin.pengguna.index')->with('success', 'Data pengguna ' . $userName . ' berhasil dihapus.');
    }

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

            return redirect()->route('admin.pengguna.index')->with('success', 'Saldo berhasil ditambahkan untuk ' . $customer->nama_lengkap);

        } catch (\Exception $e) {
            Log::error('Gagal menambahkan saldo untuk customer ID ' . $customer->id_pengguna . ': ' . $e->getMessage());
            return redirect()->route('admin.pengguna.index')->with('error', 'Gagal menambahkan saldo. Terjadi kesalahan server.');
        }
    }

    public function indexForStores()
    {
        $customers = User::whereIn('role', ['Pelanggan', 'Seller'])
                          ->with('store')
                          ->latest('id_pengguna')
                          ->paginate(20);

        return view('admin.customer-to-seller.index', compact('customers'));
    }

    public function createStore(User $user)
    {
        if ($user->store) {
            return redirect()->route('admin.stores.edit', $user->store->id)
                             ->with('info', 'Pelanggan ini sudah memiliki toko. Anda bisa langsung mengeditnya di sini.');
        }

        return view('admin.customer-to-seller.create', compact('user'));
    }

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