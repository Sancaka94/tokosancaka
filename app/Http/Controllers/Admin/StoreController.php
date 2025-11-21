<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User; // <-- Diperlukan untuk Payout
use App\Models\Transaction; // <-- Diperlukan untuk Payout
use App\Services\DokuJokulService; // <-- Diperlukan untuk Payout
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Diperlukan untuk Create/Store
use Illuminate\Support\Facades\DB; // Diperlukan untuk Payout
use Illuminate\Support\Facades\Log; // Diperlukan untuk Payout
use Illuminate\Support\Str; // Diperlukan untuk Create/Store

class StoreController extends Controller
{
    /**
     * =================================================================
     * FUNGSI UTAMA: Menampilkan halaman PENCARIAN DANA
     * =================================================================
     * Ini adalah method index() yang dibutuhkan oleh
     * file admin/stores/index.blade.php Anda.
     */
    public function index()
    {
        // Ambil semua toko customer, beserta relasi user-nya
        $customerStores = Store::with('user')
            ->whereHas('user', function ($query) {
                // Hanya ambil toko milik customer (bukan admin)
                $query->where('role', '!=', 'admin'); 
            })
            ->latest()
            ->paginate(20); // Gunakan paginasi

        // Mengirim data ke view pencairan dana
        return view('admin.store.index', compact('customerStores'));
    }

    /**
     * =================================================================
     * FUNGSI PAYOUT: Memproses pencairan dana (dipanggil dari modal)
     * =================================================================
     * Ini adalah method yang dibutuhkan oleh rute admin.stores.payout
     */
    public function payout(Request $request, Store $store)
    {
        // Validasi input dari modal
        $request->validate([
            'payout_amount' => 'required|numeric|min:0',
            'payout_description' => 'nullable|string|max:255',
        ]);

        // 1. Ambil semua data yang diperlukan
        $user = $store->user;
        if (!$user) {
            return back()->with('error', 'User untuk toko ini tidak ditemukan.');
        }
        if (empty($store->doku_sac_id)) {
            return back()->with('error', 'Toko ini tidak memiliki Doku SAC ID. Pencairan tidak bisa diproses.');
        }

        // 2. Hitung saldo
        $saldo_internal = $user->saldo ?? 0;
        $saldo_doku = $store->doku_balance_available ?? 0;
        $total_saldo = $saldo_internal + $saldo_doku;

        $payout_amount = (float) $request->input('payout_amount');
        $description = $request->input('payout_description') ?? 'Pencairan dana marketplace';

        // Jika payout_amount = 0, artinya cairkan semua
        if ($payout_amount == 0) {
            $payout_amount = $total_saldo;
        }

        // 3. Validasi jumlah
        if ($payout_amount <= 0) {
            return back()->with('error', 'Jumlah pencairan harus lebih dari 0.');
        }
        if ($payout_amount > $total_saldo) {
            return back()->with('error', 'Jumlah pencairan (Rp ' . number_format($payout_amount) . ') melebihi total saldo siap cair (Rp ' . number_format($total_saldo) . ').');
        }

        // 4. Mulai proses pencairan
        DB::beginTransaction();
        try {
            $dokuService = new DokuJokulService();
            
            // Generate ID unik untuk pencairan ini
            $payout_ref_id = 'PAYOUT-' . $store->id . '-' . time();

            // Panggil API Doku Payout (Transfer Antar Akun)
            $dokuResponse = $dokuService->transferToSubAccount(
                $payout_ref_id,
                $store->doku_sac_id,
                $payout_amount,
                $description
            );

            // Jika Doku GAGAL
            if (empty($dokuResponse['status']) || $dokuResponse['status'] !== 'SUCCESS') {
                $errorMessage = $dokuResponse['message'] ?? 'Gagal melakukan transfer Doku.';
                throw new \Exception('Doku Payout Gagal: ' . $errorMessage);
            }

            // Jika Doku BERHASIL
            // 5. Kurangi saldo di sistem kita
            // Logika: Kurangi Saldo Doku dulu, sisanya baru Saldo Internal

            $amountToReduce = $payout_amount;

            // Kurangi Saldo Doku (yang ada di tabel stores)
            if ($saldo_doku > 0) {
                $reduceFromDoku = min($saldo_doku, $amountToReduce);
                $store->doku_balance_available -= $reduceFromDoku;
                $amountToReduce -= $reduceFromDoku;
            }

            // Jika masih ada sisa, kurangi Saldo Internal (yang ada di tabel users)
            if ($amountToReduce > 0 && $saldo_internal > 0) {
                $reduceFromInternal = min($saldo_internal, $amountToReduce);
                $user->saldo -= $reduceFromInternal;
            }

            $user->save();
            $store->save();

            // 6. Catat transaksi Payout ini (PENTING)
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'payout', // Tipe baru: 'payout'
                'amount' => -$payout_amount, // Bernilai negatif karena uang keluar
                'status' => 'success',
                'description' => "Pencairan dana ke Doku SAC ID: " . $store->doku_sac_id . ". Catatan: " . $description,
                'reference_id' => $payout_ref_id,
                'payment_url' => $dokuResponse['transaction_id'] ?? null, // Simpan ID transaksi Doku
            ]);

            DB::commit();
            return back()->with('success', 'Pencairan dana sebesar Rp ' . number_format($payout_amount) . ' ke toko ' . $store->name . ' berhasil diproses.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses pencairan dana (payout)', [
                'store_id' => $store->id,
                'sac_id' => $store->doku_sac_id,
                'amount' => $payout_amount,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    /**
     * =================================================================
     * FUNGSI TAMBAHAN: Untuk mengelola toko (dari kode Anda)
     * =================================================================
     */

    /**
     * FITUR 1: Menampilkan form untuk admin membuat toko baru.
     */
    public function create()
    {
        $adminUser = Auth::user();
        if ($adminUser->store) {
            return redirect()->route('admin.stores.index')
                ->with('info', 'Anda sudah memiliki toko. Gunakan form di bawah untuk mengelola toko customer.');
        }
        // Perbaikan: Menggunakan 'admin.stores.create' (plural)
        return view('admin.stores.create');
    }

    /**
     * FITUR 1: Menyimpan toko baru yang dibuat oleh admin.
     */
    public function store(Request $request)
    {
        $adminUser = Auth::user();
        $request->validate([
            'name' => 'required|string|max:255|unique:stores', 
            'description' => 'required|string|min:20'
        ]);

        Store::create([
            'user_id' => $adminUser->id_pengguna, // Asumsi 'id_pengguna' adalah foreign key yang benar
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'doku_status' => 'PENDING', // Menambahkan status default
        ]);

        return redirect()->route('admin.stores.index')->with('success', 'Toko untuk akun admin berhasil dibuat!');
    }

    /**
     * FITUR 3: Menampilkan form untuk mengedit toko milik customer.
     */
    public function edit(Store $store)
    {
        // $store akan otomatis ditemukan oleh Laravel (Route Model Binding)
        // Perbaikan: Menggunakan 'admin.stores.edit' (plural)
        return view('admin.stores.edit', compact('store'));
    }

    /**
     * FITUR 3: Mengupdate data toko milik customer.
     */
    public function update(Request $request, Store $store)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:stores,name,' . $store->id,
            'description' => 'required|string|min:20',
            // Tambahan validasi untuk data Doku
            'doku_sac_id' => 'nullable|string|max:100',
            'doku_status' => 'nullable|string|in:PENDING,SUCCESS,FAILED,COMPLETED',
        ]);

        $store->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'doku_sac_id' => $request->doku_sac_id,
            'doku_status' => $request->doku_status,
        ]);

        return redirect()->route('admin.stores.index')->with('success', 'Data toko berhasil diperbarui.');
    }
}