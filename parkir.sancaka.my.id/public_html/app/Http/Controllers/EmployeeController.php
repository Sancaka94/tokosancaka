<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FinancialReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // ==========================================
        // 1. DATA TABEL ATAS (REKAP PEGAWAI)
        // ==========================================
        $query = User::query();

        if ($user->role === 'superadmin') {
            $query->whereIn('role', ['admin', 'operator']);
        } elseif ($user->role === 'admin') {
            $query->where('tenant_id', $user->tenant_id)
                  ->whereIn('role', ['admin', 'operator']);
        } else {
            $query->where('id', $user->id);
        }

        $employees = $query->latest()->paginate(10, ['*'], 'emp_page');

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        foreach ($employees as $emp) {
            $emp->pendapatan_bulan_ini = FinancialReport::where('kategori', 'Gaji Pegawai')
                ->where('keterangan', 'like', '%' . $emp->name . '%')
                ->whereMonth('tanggal', $currentMonth)
                ->whereYear('tanggal', $currentYear)
                ->sum('nominal');

            $emp->total_pendapatan = FinancialReport::where('kategori', 'Gaji Pegawai')
                ->where('keterangan', 'like', '%' . $emp->name . '%')
                ->sum('nominal');
        }

        // ==========================================
        // 2. DATA TABEL BAWAH (RIWAYAT HARIAN GAJI)
        // ==========================================
        $historyQuery = FinancialReport::where('kategori', 'Gaji Pegawai');

        // Filter Hak Akses Riwayat
        if ($user->role === 'admin') {
            // Ambil daftar nama pegawai di bawah admin ini
            $tenantEmployeeNames = User::where('tenant_id', $user->tenant_id)->pluck('name')->toArray();
            if (!empty($tenantEmployeeNames)) {
                $historyQuery->where(function($q) use ($tenantEmployeeNames) {
                    foreach ($tenantEmployeeNames as $name) {
                        $q->orWhere('keterangan', 'like', '%' . $name . '%');
                    }
                });
            } else {
                $historyQuery->whereRaw('1 = 0'); // Kosongkan jika tidak ada pegawai
            }
        } elseif ($user->role === 'operator') {
            $historyQuery->where('keterangan', 'like', '%' . $user->name . '%');
        }

        // Filter Pencarian Tanggal
        if ($request->filled('tanggal')) {
            $historyQuery->whereDate('tanggal', $request->tanggal);
        }

        // Filter Pencarian Nama / Keterangan
        if ($request->filled('search')) {
            $search = $request->search;
            $historyQuery->where('keterangan', 'like', '%' . $search . '%');
        }

        $salaryHistory = $historyQuery->orderBy('tanggal', 'desc')->paginate(15, ['*'], 'hist_page')->withQueryString();

        return view('employees.index', compact('employees', 'salaryHistory'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users,email',
            'password'      => 'required|string|min:8',
            'role'          => 'required|in:operator,admin',
            'salary_type'   => 'required|in:nominal,percentage',
            'salary_amount' => 'required|numeric|min:0',
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain.'
        ]);

        User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'role'          => $request->role,
            'salary_type'   => $request->salary_type,
            'salary_amount' => $request->salary_amount,
            'tenant_id'     => auth()->user()->tenant_id,
        ]);

        return redirect()->route('employees.index')->with('success', 'Akun pegawai berhasil ditambahkan.');
    }

    public function edit(User $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, User $employee)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users,email,' . $employee->id,
            'role'          => 'required|in:operator,admin',
            'salary_type'   => 'required|in:nominal,percentage',
            'salary_amount' => 'required|numeric|min:0',
        ]);

        $data = [
            'name'          => $request->name,
            'email'         => $request->email,
            'role'          => $request->role,
            'salary_type'   => $request->salary_type,
            'salary_amount' => $request->salary_amount,
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Data pegawai diperbarui.');
    }

    public function destroy(User $employee)
    {
        if (auth()->id() === $employee->id) {
            return redirect()->route('employees.index')->with('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
        }

        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Pegawai dihapus.');
    }
}
