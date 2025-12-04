<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction; // Pastikan Model ini benar

class AdminPpobController extends Controller
{
    /**
     * Menampilkan seluruh data transaksi PPOB dengan filter lengkap.
     */
    public function index(Request $request)
    {
        // 1. Mulai Query dengan Eager Loading User
        // 'with' digunakan agar query tidak berat saat meloop user name di blade
        $query = PpobTransaction::with('user')->latest(); 

        // 2. Logika Pencarian (Search)
        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            
            $query->where(function($q) use ($search) {
                // Cari di tabel transaksi
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('customer_no', 'like', "%{$search}%")
                  ->orWhere('sn', 'like', "%{$search}%")
                  // Cari juga di tabel user (Relasi)
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 3. Filter Status
        if ($request->has('status') && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 4. Filter Rentang Tanggal
        if ($request->has('start_date') && $request->filled('start_date') && 
            $request->has('end_date') && $request->filled('end_date')) {
            
            $startDate = $request->start_date . ' 00:00:00';
            $endDate   = $request->end_date . ' 23:59:59';
            
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // 5. Pagination (20 data per halaman)
        $transactions = $query->paginate(20);

        // 6. Return View
        // Sesuai request folder: resources/views/admin/ppob/data/index.blade.php
        return view('admin.ppob.data.index', compact('transactions'));
    }

    // Placeholder untuk Export Excel (Sesuai tombol di view)
    public function exportExcel()
    {
        // Logika export excel (bisa pakai Laravel Excel)
        return back()->with('success', 'Fitur Export Excel belum diaktifkan.');
    }

    // Placeholder untuk Export PDF (Sesuai tombol di view)
    public function exportPdf()
    {
        // Logika export pdf (bisa pakai DomPDF)
        return back()->with('success', 'Fitur Export PDF belum diaktifkan.');
    }
}