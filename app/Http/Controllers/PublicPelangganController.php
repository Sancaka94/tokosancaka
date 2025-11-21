<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;

class PublicPelangganController extends Controller
{
    /**
     * Menampilkan daftar pelanggan untuk publik.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Pelanggan::query();

        // Logika Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id_pelanggan', 'like', '%' . $search . '%')
                  ->orWhere('nama_pelanggan', 'like', '%' . $search . '%')
                  ->orWhere('nomor_wa', 'like', '%' . $search . '%');
            });
        }

        $pelanggans = $query->latest()->paginate(15); // Menampilkan 15 data per halaman

        return view('pelanggan.public_index', compact('pelanggans'));
    }
}
