<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Kontak; // <-- Pastikan model Kontak di-import
use Illuminate\Support\Str;

class CustomerOrderController extends Controller
{
    /**
     * Menampilkan halaman form untuk membuat pesanan baru oleh pelanggan.
     */
    public function create()
    {
        return view('pesanan_customer.create');
    }

    /**
     * Menyimpan pesanan baru dari pelanggan ke database.
     */
    public function store(Request $request)
    {
        // Validasi data yang masuk dari form
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
            'weight' => 'required|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'kelengkapan' => 'nullable|array',
            'save_sender' => 'nullable',
            'save_receiver' => 'nullable',
        ]);

        // --- LOGIKA SIMPAN KONTAK BARU ---
        if ($request->has('save_sender')) {
            Kontak::updateOrCreate(
                ['no_hp' => $request->sender_phone], // Cari berdasarkan no_hp
                [
                    'nama' => $request->sender_name,
                    'alamat' => $request->sender_address,
                    'tipe' => 'Pengirim'
                ]
            );
        }
        if ($request->has('save_receiver')) {
            Kontak::updateOrCreate(
                ['no_hp' => $request->receiver_phone],
                [
                    'nama' => $request->receiver_name,
                    'alamat' => $request->receiver_address,
                    'tipe' => 'Penerima'
                ]
            );
        }

        // Buang data 'save' sebelum menyimpan ke pesanan
        $pesananData = $request->except(['save_sender', 'save_receiver']);

        // Tambahkan data otomatis
        $pesananData['resi'] = 'SCK' . strtoupper(Str::random(8));
        $pesananData['status'] = 'Menunggu Pickup';
        $address_parts = explode(',', $validatedData['receiver_address']);
        $pesananData['tujuan'] = trim(end($address_parts));

        // Simpan ke database
        $pesanan = Pesanan::create($pesananData);

        // Arahkan ke halaman sukses dengan membawa seluruh data pesanan
        return redirect()->route('pesanan.customer.success')->with('order', $pesanan);
    }

    /**
     * Menampilkan halaman sukses setelah membuat pesanan.
     */
    public function success()
    {
        if (!session('order')) {
            return redirect()->route('home');
        }
        return view('pesanan_customer.success', ['order' => session('order')]);
    }
}
