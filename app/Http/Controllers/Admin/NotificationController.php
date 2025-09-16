<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\TopUp;
use App\Models\ScannedPackage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
// ✅ ADDED: Import the standard Laravel Notification model
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * A helper function to safely execute and cache database queries.
     * It prevents errors by catching exceptions and logging them.
     */
    private function getCount($cacheKey, $queryClosure)
    {
        try {
            // Cache the result for 1 minute to improve performance
            return Cache::remember($cacheKey, 60, function () use ($queryClosure) {
                return $queryClosure();
            });
        } catch (\Exception $e) {
            // Log the specific error for debugging
            Log::error("Notification count failed for key [{$cacheKey}]: " . $e->getMessage());
            
            // Return 0 and a 500 error status to the frontend
            return response()->json(['count' => 0], 500);
        }
    }

    /**
     * Get the count of all unread notifications for the main bell icon.
     * Corresponds to: /admin/notifications/count
     */
    public function count()
    {
        try {
            // Counts all notifications that have not been read yet
            $count = DatabaseNotification::whereNull('read_at')->count();
            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            Log::error("General notification count failed: " . $e->getMessage());
            return response()->json(['count' => 0], 500);
        }
    }

    /**
     * Get the count of new user registrations.
     * Corresponds to: /admin/notifications/registrations-count
     */
    public function registrationsCount()
    {
        $count = $this->getCount('notifications_registrations_count', function () {
            // Counts users with a 'pending' status.
            return User::where('status', 'Tidak Aktif')->count();
        });

        return response()->json(['count' => $count]);
    }

    /**
     * Get the count of new orders (pesanan).
     * Corresponds to: /admin/notifications/pesanan-count
     */
    public function pesananCount()
    {
        $count = $this->getCount('notifications_pesanan_count', function () {
            // Counts orders with status 'Menunggu Pickup'
            return Pesanan::where('status_pesanan', 'Menunggu Pickup')->count();
        });

        return response()->json(['count' => $count]);
    }

    /**
     * Get the count of pending balance requests (saldo).
     * Corresponds to: /admin/notifications/saldo-requests-count
     */
    public function saldoRequestsCount()
    {
        $count = $this->getCount('notifications_saldo_requests_count', function () {
            // Counts TopUp requests with 'pending' status
            return TopUp::where('status', 'pending')->count();
        });

        return response()->json(['count' => $count]);
    }

    /**
     * Get the count of today's scanned packages (SPX).
     * Corresponds to: /admin/notifications/spx-scans-count
     */
    public function spxScansCount() //
    {
        $count = $this->getCount('notifications_spx_scans_count', function () {
            // Counts SPX packages scanned today
            return ScannedPackage::whereDate('created_at', today())->count();
        });

        return response()->json(['count' => $count]);
    }

    /**
     * Get the count of today's scanned packages.
     * Corresponds to: /admin/notifications/riwayat-scan-count
     */
    public function riwayatScanCount()
    {
        $count = $this->getCount('notifications_riwayat_scan_count', function () {
            // Counts packages scanned today
            return ScannedPackage::whereDate('created_at', today())->count();
        });

        return response()->json(['count' => $count]);
    }
}
