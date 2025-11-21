<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\Package;
use App\Models\Scan;
use App\Models\User;
use App\Notifications\DeliveryOrderScanned;
use App\Events\AdminNotificationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CourierController extends Controller
{
    /**
     * Menampilkan daftar kurir dengan fungsionalitas pencarian.
     */
    public function index(Request $request)
    {
        $query = Courier::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                  ->orWhere('courier_id', 'like', '%' . $search . '%')
                  ->orWhere('phone_number', 'like', '%' . $search . '%');
            });
        }

        $couriers = $query->latest()->paginate(10);

        return view('couriers.index', compact('couriers'));
    }
    
    /**
     * Menampilkan halaman untuk menambah kurir baru.
     */
    public function create()
    {
        return view('couriers.create');
    }

    /**
     * Menyimpan data kurir baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'courier_id' => 'required|string|unique:couriers,courier_id',
            'full_name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone_number' => 'required|string|max:20',
        ]);

        Courier::create($request->all());

        return redirect()->route('admin.couriers.index')->with('success', 'Kurir baru berhasil ditambahkan.');
    }


    /**
     * Menampilkan detail data kurir beserta riwayat scan.
     */
 public function show($id)
{
    $courier = Courier::findOrFail($id);

    // Ganti 'courier_id' menjadi 'user_id' (atau 'kontak_id' jika lebih sesuai)
    $scanHistory = [
        'today' => Scan::where('user_id', $id)->whereDate('created_at', Carbon::today())->count(),
        'last_7_days' => Scan::where('user_id', $id)->where('created_at', '>=', Carbon::now()->subDays(7))->count(),
        'this_month' => Scan::where('user_id', $id)->whereMonth('created_at', Carbon::now()->month)->count(),
    ];

    return view('couriers.show', compact('courier', 'scanHistory'));
}

    /**
     * Menampilkan halaman untuk scan barcode surat jalan.
     */
    public function showScanPage($id)
    {
        $courier = Courier::findOrFail($id);
        return view('couriers.scan', compact('courier'));
    }

    /**
     * API endpoint untuk mengubah status resi/surat jalan setelah scan.
     */
    public function updatePackageStatus(Request $request)
    {
        $request->validate([
            'shipping_code' => 'required|string|exists:packages,shipping_code',
            'courier_id' => 'required|exists:couriers,id',
        ]);

        $package = Package::where('shipping_code', $request->shipping_code)->first();

        if ($package && $package->status == 'pickup') {
            $courier = Courier::find($request->courier_id);

            DB::transaction(function () use ($package, $courier, $request) {
                // 1. Ubah status paket
                $package->status = 'dibawa kurir';
                $package->courier_id = $courier->id;
                $package->save();

                // 2. Update data kurir
                $courier->last_scan_time = now();
                $courier->shipping_code = $request->shipping_code;
                $courier->status = 'Dalam Perjalanan';
                $courier->save();

                // 3. Catat riwayat scan
                Scan::create([
                    'courier_id' => $courier->id,
                    'package_id' => $package->id,
                ]);

                // 4. Kirim notifikasi ke semua admin
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new DeliveryOrderScanned($package, $courier));
                }

                // 5. Kirim event untuk notifikasi real-time
                $message = "Kurir {$courier->full_name} telah mengambil paket {$package->shipping_code}.";
                broadcast(new AdminNotificationEvent($message));
            });

            return response()->json(['success' => true, 'message' => 'Status paket berhasil diperbarui.']);
        }

        return response()->json(['success' => false, 'message' => 'Surat jalan tidak ditemukan atau status tidak valid.'], 404);
    }

    /**
     * API endpoint untuk menerima dan menyimpan data GPS dari HP kurir.
     */
    public function updateLocation(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $courier = Courier::find($id);
        if ($courier) {
            $courier->latitude = $request->latitude;
            $courier->longitude = $request->longitude;
            $courier->save();

            return response()->json(['success' => true, 'message' => 'Lokasi berhasil diperbarui.']);
        }

        return response()->json(['success' => false, 'message' => 'Kurir tidak ditemukan.'], 404);
    }

    /**
     * Menampilkan posisi GPS terakhir kurir di peta.
     */
    public function trackLocation($id)
    {
        $courier = Courier::findOrFail($id);
        return view('couriers.track', compact('courier'));
    }

    /**
     * Menampilkan halaman edit data kurir.
     */
    public function edit($id)
    {
        $courier = Courier::findOrFail($id);
        return view('couriers.edit', compact('courier'));
    }

    /**
     * Memproses pembaruan data kurir.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone_number' => 'required|string|max:20',
            'status' => 'required|string',
        ]);

        $courier = Courier::findOrFail($id);
        $courier->update($request->all());

        return redirect()->route('couriers.index')->with('success', 'Data kurir berhasil diperbarui.');
    }

    /**
     * Menghapus data kurir.
     */
    public function destroy($id)
    {
        $courier = Courier::findOrFail($id);
        $courier->delete();

        return redirect()->route('couriers.index')->with('success', 'Data kurir berhasil dihapus.');
    }

    /**
     * Menampilkan halaman cetak surat jalan untuk divalidasi admin.
     */
    public function printDeliveryOrder($id)
    {
        $courier = Courier::findOrFail($id);
        
        // Ambil semua paket yang di-scan oleh kurir ini pada hari ini
        $packages = Package::where('courier_id', $id)->whereDate('updated_at', Carbon::today())->get();

        return view('couriers.print', compact('courier', 'packages'));
    }
    
    /**
     * API endpoint for searching couriers by name.
     */
    public function search(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
        ]);

        $couriers = Courier::where('full_name', 'like', '%' . $request->name . '%')
            ->select('id', 'full_name', 'courier_id') // Hanya kirim data yang perlu
            ->take(5) // Batasi hasil agar tidak terlalu banyak
            ->get();

        return response()->json($couriers);
    }
}
