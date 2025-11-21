<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PenggunaController extends Controller
{
    protected $connection;

    public function __construct()
    {
        $this->connection = DB::connection('pondok');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pengguna = $this->connection->table('pengguna')->paginate(15);
        return view('pondok.admin.pengguna.index', compact('pengguna'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pondok.admin.pengguna.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:pondok.pengguna',
            'email' => 'required|string|email|max:255|unique:pondok.pengguna',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string',
        ]);

        $data = $request->except(['_token', 'password_confirmation']);
        $data['password'] = Hash::make($request->password);

        $this->connection->table('pengguna')->insert($data);
        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pengguna = $this->connection->table('pengguna')->find($id);
        return view('pondok.admin.pengguna.show', compact('pengguna'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pengguna = $this->connection->table('pengguna')->find($id);
        return view('pondok.admin.pengguna.edit', compact('pengguna'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:pondok.pengguna,username,' . $id,
            'email' => 'required|string|email|max:255|unique:pondok.pengguna,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string',
        ]);

        $data = $request->except(['_token', '_method', 'password', 'password_confirmation']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $this->connection->table('pengguna')->where('id', $id)->update($data);
        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('pengguna')->where('id', $id)->delete();
        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}

