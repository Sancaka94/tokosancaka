<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FinancialReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = User::query();

        // ==========================================
        // 1. FILTER DATA BERDASARKAN ROLE (AUTH)
        // ==========================================
        if ($user->role === 'superadmin') {
            // Superadmin melihat semua data (Admin & Operator)
            $query->whereIn('role', ['admin', 'operator']);
        } elseif ($user->role === 'admin') {
            // Admin hanya melihat pegawai di bawah tenant/cabangnya
            $query->where('tenant_id', $user->tenant_id)
                  ->whereIn('role', ['admin', 'operator']);
        } else {
            // Operator HANYA bisa melihat data dirinya sendiri
            $query->where('id', $user->id);
        }

        $employees = $query->latest()->paginate(10);

        // ==========================================
        // 2. HITUNG REKAPAN PENDAPATAN PEGAWAI
        // ==========================================
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        foreach ($employees as $emp) {
            // Hitung total gaji yang sudah DIBAYARKAN di bulan ini
            $emp->pendapatan_bulan_ini = FinancialReport::where('kategori', 'Gaji Pegawai')
                ->where('keterangan', 'like', '%' . $emp->name . '%')
                ->whereMonth('tanggal', $currentMonth)
                ->whereYear('tanggal', $currentYear)
                ->sum('nominal');

            // Hitung total keseluruhan gaji dari awal sampai sekarang
            $emp->total_pendapatan = FinancialReport::where('kategori', 'Gaji Pegawai')
                ->where('keterangan', 'like', '%' . $emp->name . '%')
                ->sum('nominal');
        }

        return view('employees.index', compact('employees'));
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
