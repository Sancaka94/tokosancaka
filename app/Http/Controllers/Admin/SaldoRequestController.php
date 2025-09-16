<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TopUp;
use App\Models\User; // ✅ PERBAIKAN: Menggunakan model User sesuai standar proyek
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaldoRequestController extends Controller
{
    /**
     * Menampilkan daftar semua permintaan top-up yang masih pending.
     */
    public function index()
    {
        // Mengambil relasi 'customer' yang terhubung via 'customer_id'
        $requests = TopUp::where('status', 'pending')
                         ->with('customer')
                         ->latest()
                         ->paginate(15);

        return view('admin.saldo.index', compact('requests'));
    }

    /**
     * Menyetujui permintaan top-up.
     */
    public function approve(TopUp $topUp)
    {
        DB::transaction(function () use ($topUp) {
            $topUp->status = 'success';
            $topUp->save();

            // Menggunakan relasi 'customer' untuk mendapatkan objek User
            $customer = $topUp->customer;
            if ($customer) {
                $customer->saldo += $topUp->amount;
                $customer->save();
            }
        });

        return redirect()->route('admin.saldo.requests.index')->with('success', 'Permintaan saldo berhasil disetujui.');
    }

    /**
     * Menolak permintaan top-up.
     */
    public function reject(TopUp $topUp)
    {
        $topUp->status = 'failed';
        $topUp->save();

        return redirect()->route('admin.saldo.requests.index')->with('success', 'Permintaan saldo telah ditolak.');
    }
}
