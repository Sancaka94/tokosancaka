<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class PaketController extends Controller
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
        $paket = $this->connection->table('paket')->paginate(15);
        return view('pondok.admin.paket.index', compact('paket'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pondok.admin.paket.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_paket' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'periode_hari' => 'required|integer|min:1',
            'fitur' => 'nullable|string', // Fitur diinput sebagai teks, dipisahkan baris baru
        ]);

        $data = $request->except('_token');
        
        // Mengubah string fitur (dipisahkan baris baru) menjadi JSON Array
        if (!empty($data['fitur'])) {
            $fiturArray = array_filter(array_map('trim', explode("\n", $data['fitur'])));
            $data['fitur'] = json_encode($fiturArray);
        } else {
            $data['fitur'] = json_encode([]);
        }

        $this->connection->table('paket')->insert($data);
        return redirect()->route('admin.paket.index')->with('success', 'Paket berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $paket = $this->connection->table('paket')->find($id);
        return view('pondok.admin.paket.show', compact('paket'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $paket = $this->connection->table('paket')->find($id);

        // Mengubah JSON fitur kembali menjadi string agar mudah diedit di textarea
        if (!empty($paket->fitur)) {
            $fiturArray = json_decode($paket->fitur, true);
            $paket->fitur = is_array($fiturArray) ? implode("\n", $fiturArray) : '';
        }

        return view('pondok.admin.paket.edit', compact('paket'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_paket' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'periode_hari' => 'required|integer|min:1',
            'fitur' => 'nullable|string',
        ]);

        $data = $request->except(['_token', '_method']);

        // Mengubah string fitur menjadi JSON Array sebelum update
        if (!empty($data['fitur'])) {
            $fiturArray = array_filter(array_map('trim', explode("\n", $data['fitur'])));
            $data['fitur'] = json_encode($fiturArray);
        } else {
            $data['fitur'] = json_encode([]);
        }

        $this->connection->table('paket')->where('id', $id)->update($data);
        return redirect()->route('admin.paket.index')->with('success', 'Paket berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('paket')->where('id', $id)->delete();
        return redirect()->route('admin.paket.index')->with('success', 'Paket berhasil dihapus.');
    }
}

