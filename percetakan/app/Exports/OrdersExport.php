<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $request;

    /**
     * Terima Request dari Controller untuk membaca Filter
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Query Data dengan Filter yang sama persis dengan Index
     */
    public function query()
    {
        $query = Order::query();

        // 1. Filter Pencarian
        if ($this->request->filled('q')) {
            $search = $this->request->q;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // 2. Filter Status Pembayaran
        if ($this->request->filled('status')) {
            $query->where('payment_status', $this->request->status);
        }

        // 3. Filter Tanggal (Flatpickr Logic)
        if ($this->request->filled('date_range')) {
            $dates = explode(' to ', $this->request->date_range);

            if (count($dates) == 2) {
                $query->whereBetween('created_at', [
                    $dates[0] . ' 00:00:00',
                    $dates[1] . ' 23:59:59'
                ]);
            } elseif (count($dates) == 1) {
                $query->whereDate('created_at', $dates[0]);
            }
        }

        // Urutkan dari yang terbaru
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Mapping data per baris
     */
    public function map($order): array
    {
        return [
            $order->created_at->translatedFormat('d F Y H:i'), // Tanggal
            $order->order_number,                              // No Invoice
            $order->customer_name,                             // Nama Pelanggan
            $order->customer_phone,                            // No HP/WA
            $order->destination_address ?? 'Ambil di Toko',    // Alamat
            strtoupper($order->payment_method),                // Metode Bayar
            strtoupper($order->payment_status),                // Status Bayar
            strtoupper($order->status),                        // Status Order
            $order->courier_service ?? '-',                    // Kurir
            $order->shipping_ref ?? '-',                       // Resi
            $order->final_price,                               // Total Harga
        ];
    }

    /**
     * Judul Header Kolom Excel
     */
    public function headings(): array
    {
        return [
            'TANGGAL ORDER',
            'NO. INVOICE',
            'NAMA PELANGGAN',
            'WHATSAPP',
            'ALAMAT TUJUAN',
            'METODE BAYAR',
            'STATUS BAYAR',
            'STATUS ORDER',
            'EKSPEDISI',
            'NO. RESI',
            'TOTAL BAYAR (RP)',
        ];
    }

    /**
     * Styling Sederhana (Bold Header)
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Baris 1 (Header) di-bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}
