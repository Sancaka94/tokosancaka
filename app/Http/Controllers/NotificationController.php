<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\ScannedPackage;
use App\Models\TopUp; // Pastikan model ini ada dan diimpor
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Menghitung jumlah registrasi pelanggan baru yang belum terverifikasi.
     */
    public function registrationsCount()
    {
        try {
            $count = User::where('role', 'Pelanggan')->where('status', 'pending')->count();
        } catch (\Exception $e) {
            Log::error('Error di registrationsCount: ' . $e->getMessage());
            $count = 0;
        }
        return response()->json(['count' => $count]);
    }

    /**
     * Menghitung jumlah pesanan baru.
     */
    public function pesananCount()
    {
        try {
            $count = Pesanan::whereIn('status', ['pending', 'baru'])->count();
        } catch (\Exception $e) {
            Log::error('Error di pesananCount: ' . $e->getMessage());
            $count = 0;
        }
        return response()->json(['count' => $count]);
    }

    /**
     * Menghitung jumlah scan SPX hari ini.
     */
    public function spxScansCount()
    {
        try {
            $count = ScannedPackage::whereDate('created_at', today())->count();
        } catch (\Exception $e) {
            Log::error('Error di spxScansCount: ' . $e->getMessage());
            $count = 0;
        }
        return response()->json(['count' => $count]);
    }

    /**
     * Menghitung total riwayat scan.
     */
    public function riwayatScanCount()
    {
        try {
            $count = ScannedPackage::count();
        } catch (\Exception $e) {
            Log::error('Error di riwayatScanCount: ' . $e->getMessage());
            $count = 0;
        }
        return response()->json(['count' => $count]);
    }

    /**
     * Menghitung jumlah permintaan saldo yang menunggu persetujuan.
     */
    public function saldoRequestsCount()
    {
        try {
            // Asumsi: Model adalah 'TopUp' dan statusnya 'pending'.
            $count = TopUp::where('status', 'pending')->count();
        } catch (\Exception $e) {
            Log::error('Error di saldoRequestsCount: ' . $e->getMessage());
            $count = 0;
        }
        return response()->json(['count' => $count]);
    }
}
