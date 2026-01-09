<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer\Pesanan; // Pastikan menunjuk ke model yang benar
use App\Models\Kontak;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerOrderController extends Controller
{
    /**
     * Menampilkan halaman form untuk membuat pesanan baru.
     */
    public function create()
    {
        return view('customer.pesanan.create');
    }

    /**
     * Menyimpan pesanan baru ke database, dengan validasi dan pengecekan saldo.
     */
    public function store(Request $request)
    {
        // Validasi data yang masuk dari form secara lengkap
        $validatedData = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'service_type' => 'required|string',
            'expedition' => 'required|string',
            'payment_method' => 'required|string',
            'item_description' => 'required|string',
            'weight' => 'required|numeric|min:0.1',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'kelengkapan' => 'nullable|array',
            'save_sender' => 'nullable|boolean',
            'save_receiver' => 'nullable|boolean',
        ]);

        $customerId = Auth::id();

        // --- LOGIKA PENGECEKAN SALDO ---
        if ($request->payment_method == 'Saldo') {
            $biayaPesanan = $this->calculateShippingCost($request); 

            $totalPemasukan = DB::table('top_ups')->where('customer_id', $customerId)->where('status', 'success')->sum('amount');
            $totalPengeluaran = DB::table('pesanan')->where('customer_id', $customerId)->where('payment_method', 'Saldo')->sum('total_biaya');
            $saldo = $totalPemasukan - $totalPengeluaran;

            if ($saldo < $biayaPesanan) {
                return redirect()->route('customer.pesanan.status')
                    ->with('status', 'failed')
                    ->with('reason', 'insufficient_balance')
                    ->with('message', 'Saldo Anda tidak mencukupi untuk melakukan pembayaran ini.');
            }
            $validatedData['total_biaya'] = $biayaPesanan;
        } else {
            $validatedData['total_biaya'] = $this->calculateShippingCost($request);
        }
        
        // --- LOGIKA SIMPAN KONTAK BARU ---
        if ($request->boolean('save_sender')) {
            Kontak::updateOrCreate(
                ['no_hp' => $request->sender_phone],
                [
                    'nama' => $request->sender_name,
                    'alamat' => $request->sender_address,
                    'tipe' => 'Pengirim'
                ]
            );
        }
        if ($request->boolean('save_receiver')) {
            Kontak::updateOrCreate(
                ['no_hp' => $request->receiver_phone],
                [
                    'nama' => $request->receiver_name,
                    'alamat' => $request->receiver_address,
                    'tipe' => 'Penerima'
                ]
            );
        }

        // --- PROSES PEMBUATAN PESANAN ---
        $pesananData = $validatedData;
        $pesananData['customer_id'] = $customerId;
        $pesananData['resi'] = 'SCK' . strtoupper(Str::random(8));
        $pesananData['status'] = 'Menunggu Pickup';
        
        $pesanan = Pesanan::create($pesananData);

        return redirect()->route('customer.pesanan.status')
                         ->with('status', 'success')
                         ->with('order', $pesanan);
    }
    
    /**
     * Menampilkan halaman status pesanan (berhasil/gagal).
     */
    public function status()
    {
        if (!session('status')) {
            return redirect()->route('customer.dashboard');
        }
        return view('customer.pesanan.status');
    }

    /**
     * Fungsi untuk kalkulasi ongkir berdasarkan berat asli dan berat volume.
     */
    private function calculateShippingCost(Request $request)
    {
        $costPerKg = 10000; // Contoh: Rp 10.000 per kg

        // 1. Ambil berat asli dari request (dalam kg)
        $actualWeight = $request->input('weight', 0);

        // 2. Ambil dimensi dari request (dalam cm)
        $length = $request->input('length', 0);
        $width = $request->input('width', 0);
        $height = $request->input('height', 0);

        // 3. Hitung berat volume. Divisor 6000 adalah standar umum.
        $volumeDivisor = 6000;
        $volumeWeight = ($length * $width * $height) / $volumeDivisor;

        // 4. Bandingkan berat asli dengan berat volume, ambil yang terbesar.
        $chargeableWeight = max($actualWeight, $volumeWeight);

        // 5. Bulatkan berat ke atas.
        $finalWeight = ceil($chargeableWeight);
        
        // Jika berat final kurang dari 1, tetap hitung sebagai 1 kg
        if ($finalWeight < 1) {
            $finalWeight = 1;
        }

        // 6. Hitung total biaya
        return $finalWeight * $costPerKg;
    }
}
