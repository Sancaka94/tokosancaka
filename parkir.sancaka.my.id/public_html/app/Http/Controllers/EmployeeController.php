<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::whereIn('role', ['admin', 'operator'])->latest()->paginate(10);
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
