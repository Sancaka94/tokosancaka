<?php

namespace App\Http\Controllers;

use App\Models\DanaResponseCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DanaResponseCodeController extends Controller
{
    public function index(Request $request)
{
    // 1. AMBIL LIST KATEGORI UNIK (Tambahkan baris ini)
    // Ini akan mencari semua jenis kategori yang ada di database secara otomatis
    $categories = DanaResponseCode::select('category')->distinct()->pluck('category');

    // 2. Query Data Utama (Logic Search & Filter tetap sama)
    $query = DanaResponseCode::query();

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('response_code', 'like', "%{$search}%")
              ->orWhere('message_title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('solution', 'like', "%{$search}%");
        });
    }

    if ($request->filled('category') && $request->category !== 'ALL') {
        $query->where('category', $request->category);
    }

    if ($request->filled('status') && $request->status !== 'ALL') {
        $query->where('is_success', $request->status);
    }

    $codes = $query->orderBy('response_code', 'asc')
                   ->paginate(10)
                   ->withQueryString();

    // 3. KIRIM VARIABEL $categories KE VIEW
    return view('dana.response_codes.index', compact('codes', 'categories'));
}

    /**
     * Menyimpan data baru (Create).
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'response_code' => 'required|string|unique:dana_response_codes,response_code|max:50',
            'category'      => 'required|string|in:INQUIRY,TOPUP,GENERAL',
            'message_title' => 'required|string|max:255',
            'description'   => 'nullable|string',
            'solution'      => 'nullable|string',
            'is_success'    => 'required|boolean', // Menerima 0 atau 1
        ]);

        // 2. Simpan ke Database
        DanaResponseCode::create($validated);

        // 3. Redirect kembali dengan pesan sukses
        return redirect()->route('dana_response_codes.index')
                         ->with('success', 'Kode respon berhasil ditambahkan.');
    }

    /**
     * Mengupdate data yang ada (Update).
     */
    public function update(Request $request, $id)
    {
        $code = DanaResponseCode::findOrFail($id);

        // 1. Validasi Input
        $validated = $request->validate([
            // Unique validation tapi kecualikan ID ini sendiri (agar tidak error jika tidak ganti kode)
            'response_code' => [
                'required', 
                'string', 
                'max:50',
                Rule::unique('dana_response_codes', 'response_code')->ignore($code->id)
            ],
            'category'      => 'required|string|in:INQUIRY,TOPUP,GENERAL',
            'message_title' => 'required|string|max:255',
            'description'   => 'nullable|string',
            'solution'      => 'nullable|string',
            'is_success'    => 'required|boolean',
        ]);

        // 2. Update Data
        $code->update($validated);

        // 3. Redirect kembali
        return redirect()->route('dana_response_codes.index')
                         ->with('success', 'Data kode respon berhasil diperbarui.');
    }

    /**
     * Menghapus data (Delete).
     */
    public function destroy($id)
    {
        $code = DanaResponseCode::findOrFail($id);
        $code->delete();

        return redirect()->route('dana_response_codes.index')
                         ->with('success', 'Kode respon berhasil dihapus.');
    }
    
}