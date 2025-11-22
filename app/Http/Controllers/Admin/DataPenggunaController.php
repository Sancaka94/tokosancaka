<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Pengguna; 
use Illuminate\Http\Request;

class DataPenggunaController extends Controller
{
    /**
     * Menampilkan daftar data pengguna/pelanggan dengan paginasi.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $pengguna = Pengguna::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.customers.data.pengguna.index', compact('pengguna'));
    }
}