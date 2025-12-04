<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction; 
use Illuminate\Support\Facades\DB;

class AdminPpobController extends Controller
{
    /**
     * Menampilkan data transaksi dengan filter & search.
     */
    public function index(Request $request)
    {
        // 1. Eager Loading relasi 'user' untuk performa (mencegah N+1 query)
        $query = PpobTransaction::with('user')->latest(); 

        // 2. SEARCH (Cari berdasarkan Order ID, No HP, SN, atau Nama User)
        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('customer_no', 'like', "%{$search}%")
                  ->orWhere('sn', 'like', "%{$search}%")
                  // Cari ke tabel users (relasi)
                  ->orWhereHas('user', function($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%") // Ganti 'name' dg 'nama_lengkap' jika perlu
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 3. FILTER STATUS
        if ($request->has('status') && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 4. FILTER TANGGAL
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00', 
                $request->end_date . ' 23:59:59'
            ]);
        }

        $transactions = $query->paginate(20);

        return view('admin.ppob.data.index', compact('transactions'));
    }

    public function exportExcel() {
        return back()->with('info', 'Fitur Export Excel akan segera hadir.');
    }

    public function exportPdf() {
        return back()->with('info', 'Fitur Export PDF akan segera hadir.');
    }
}