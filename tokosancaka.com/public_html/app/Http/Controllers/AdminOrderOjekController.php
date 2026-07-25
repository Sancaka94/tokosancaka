<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminOrderOjekController extends Controller
{
    /**
     * Menampilkan riwayat pesanan Sancaka Ride & Express untuk Admin
     */
    public function index()
    {
        // 1. DATA UNTUK CARD STATISTIK (MONITOR)
        $totalPesanan = DB::table('order_ojek_online')->count();
        $totalExpress = DB::table('order_ojek_online')->where('order_id', 'like', 'S-EXP%')->count();
        $totalRide    = DB::table('order_ojek_online')->where('order_id', 'like', 'S-RIDE%')->count();
        $totalSelesai = DB::table('order_ojek_online')->whereIn('status', ['completed', 'selesai'])->count();

        // 2. MENARIK DATA TABEL TRANSAKSI (Dengan Join Customer & Driver)
        $orders = DB::table('order_ojek_online')
            ->leftJoin('Pengguna as customer', 'order_ojek_online.customer_id', '=', 'customer.id_pengguna')
            ->leftJoin('registrasi_driver_sancaka as driver', 'order_ojek_online.driver_id', '=', 'driver.id_pengguna')
            ->leftJoin('Pengguna as admin_user', 'order_ojek_online.driver_id', '=', 'admin_user.id_pengguna')
            ->select(
                'order_ojek_online.*',
                'customer.nama_lengkap as customer_name',
                // Logika: Jika driver_id = 4 (admin pusat), ambil dari tabel Pengguna, jika kurir biasa ambil dari tabel registrasi_driver_sancaka
                DB::raw('COALESCE(driver.nama_lengkap, admin_user.nama_lengkap, "Driver Belum Ada / Dibatalkan") as driver_name')
            )
            ->orderBy('order_ojek_online.created_at', 'desc')
            ->paginate(15);

        // Melempar data ke file Blade Tailwind
        return view('admin.orders.ojek.index', compact(
            'orders',
            'totalPesanan',
            'totalExpress',
            'totalRide',
            'totalSelesai'
        ));
    }

    /**
     * Menghapus Satu Data Transaksi
     */
    public function destroy($id)
    {
        try {
            $order = DB::table('order_ojek_online')->where('id', $id)->first();

            if ($order) {
                // Hapus file gambar Penerima
                if ($order->bukti_foto_penerima && Storage::disk('public')->exists($order->bukti_foto_penerima)) {
                    Storage::disk('public')->delete($order->bukti_foto_penerima);
                }
                if ($order->bukti_ttd_penerima && Storage::disk('public')->exists($order->bukti_ttd_penerima)) {
                    Storage::disk('public')->delete($order->bukti_ttd_penerima);
                }

                // Hapus file gambar Pengirim (Sancaka Express)
                if ($order->bukti_foto_pengirim && Storage::disk('public')->exists($order->bukti_foto_pengirim)) {
                    Storage::disk('public')->delete($order->bukti_foto_pengirim);
                }
                if ($order->bukti_ttd_pengirim && Storage::disk('public')->exists($order->bukti_ttd_pengirim)) {
                    Storage::disk('public')->delete($order->bukti_ttd_pengirim);
                }

                // Hapus record dari database
                DB::table('order_ojek_online')->where('id', $id)->delete();
            }

            return redirect()->back()->with('success', 'Data transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus Banyak Data Secara Bersamaan (Bulk Destroy)
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $ids = $request->input('ids');

            if ($ids && is_array($ids)) {
                // Ambil data untuk menghapus file gambarnya terlebih dahulu
                $orders = DB::table('order_ojek_online')->whereIn('id', $ids)->get();

                foreach ($orders as $order) {
                    // Hapus Penerima
                    if ($order->bukti_foto_penerima && Storage::disk('public')->exists($order->bukti_foto_penerima)) {
                        Storage::disk('public')->delete($order->bukti_foto_penerima);
                    }
                    if ($order->bukti_ttd_penerima && Storage::disk('public')->exists($order->bukti_ttd_penerima)) {
                        Storage::disk('public')->delete($order->bukti_ttd_penerima);
                    }

                    // Hapus Pengirim
                    if ($order->bukti_foto_pengirim && Storage::disk('public')->exists($order->bukti_foto_pengirim)) {
                        Storage::disk('public')->delete($order->bukti_foto_pengirim);
                    }
                    if ($order->bukti_ttd_pengirim && Storage::disk('public')->exists($order->bukti_ttd_pengirim)) {
                        Storage::disk('public')->delete($order->bukti_ttd_pengirim);
                    }
                }

                // Hapus semua record terpilih dari database
                DB::table('order_ojek_online')->whereIn('id', $ids)->delete();

                return redirect()->back()->with('success', count($ids) . ' data transaksi berhasil dihapus.');
            }

            return redirect()->back()->with('error', 'Pilih minimal satu transaksi untuk dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data massal: ' . $e->getMessage());
        }
    }
}
