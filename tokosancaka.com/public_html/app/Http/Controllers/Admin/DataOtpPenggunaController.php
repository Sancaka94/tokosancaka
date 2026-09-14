<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataOtpPenggunaController extends Controller
{
    /**
     * Menampilkan halaman daftar Log OTP
     */
    public function index(Request $request)
    {
        $query = DB::table('dataotppengguna')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('kontak', 'like', "%{$search}%")
                  ->orWhere('otp_code', 'like', "%{$search}%")
                  ->orWhere('tipe_otp', 'like', "%{$search}%");
            });
        }

        $dataOtp = $query->paginate(20)->withQueryString();

        return view('admin.otp.index', compact('dataOtp'));
    }

    /**
     * Menghapus 1 data Log OTP
     */
    public function destroy($id)
    {
        DB::table('dataotppengguna')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Log OTP berhasil dihapus.');
    }

    /**
     * Membersihkan (Truncate) semua data Log OTP
     */
    public function clearAll()
    {
        DB::table('dataotppengguna')->truncate();
        return redirect()->back()->with('success', 'Semua riwayat OTP berhasil dibersihkan.');
    }
}
