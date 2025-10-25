<?php

namespace App\Http\Controllers\Admin;

// Import class-class (dependency) yang dibutuhkan
use App\Http\Controllers\Controller; // Controller dasar Laravel
use Illuminate\Http\Request; // Mengelola request HTTP
use App\Models\Order; // Model untuk berinteraksi dengan tabel 'orders'
use Yajra\DataTables\Facades\DataTables; // Library untuk membuat tabel server-side
use Illuminate\Support\Facades\Log; // Untuk mencatat error ke log
use Exception; // Untuk menangani error
use Barryvdh\DomPDF\Facade\Pdf; // Library untuk generate PDF
use Carbon\Carbon; // Library untuk memanipulasi tanggal dan waktu

class AdminOrderController extends Controller
{
    /**
     * Menampilkan halaman utama Data Pesanan.
     * Method ini hanya memuat view Blade.
     */
    public function index()
    {
        // Mengembalikan view 'admin.orders.index'
        // Anda bisa mengirim data tambahan ke view jika perlu
        return view('admin.orders.index');
    }

    /**
     * Menyediakan data pesanan untuk Yajra DataTables via AJAX.
     * Ini adalah inti dari tampilan tabel yang dinamis.
     */
    public function getData(Request $request)
    {
        // Hanya proses jika request adalah AJAX (dari DataTables)
        if ($request->ajax()) {
            try {
                // Ambil nilai filter status dari tab yang aktif
                $statusFilter = $request->input('status');
                
                // Ambil nilai filter dari kotak pencarian custom
                $searchQuery = $request->input('search_query');

                // 1. Mulai query ke model Order
                // 'with' (Eager Loading) sangat penting untuk performa.
                // Ini mengambil data relasi (user, store, items) dalam satu query,
                // menghindari N+1 query problem.
                $data = Order::with([
                    'user', // Relasi ke tabel user (pembeli)
                    'store', // Relasi ke tabel store (penjual/pengirim)
                    'items.product', // Relasi ke item, lalu ke produk dari item tsb
                    'items.variant' // Relasi ke item, lalu ke varian dari item tsb
                 ])
                    ->when($statusFilter, function ($query, $status) {
                         // 2. Terapkan Filter Status jika ada (dari tab)
                         // 'when' hanya menjalankan query jika $statusFilter tidak kosong
                         $statusMap = [
                            'menunggu-pickup' => ['paid'], // Tab 'Menunggu Pickup' = status 'paid' di DB
                            'diproses' => ['processing'],
                            'terkirim' => ['shipped', 'delivered'],
                            'batal' => ['cancelled', 'failed'],
                         ];
                         if (isset($statusMap[$status])) {
                             // Jika status ada di map, filter pakai 'whereIn'
                             return $query->whereIn('status', $statusMap[$status]);
                         }
                         elseif ($status === 'pending') {
                            // Tangani status 'pending' (Menunggu Bayar) secara khusus
                             return $query->where('status', 'pending');
                         }
                         // Jika $status = "" (tab 'Semua') atau tidak dikenal, jangan filter
                         return $query;
                    })
                    ->when($searchQuery, function ($query, $search) {
                        // 3. Terapkan Filter Pencarian jika ada
                        // 'when' hanya berjalan jika $searchQuery tidak kosong
                         return $query->where(function($q) use ($search) {
                            // Grup 'where' agar 'orWhere' tidak bentrok dengan filter status
                            $q->where('invoice_number', 'like', "%{$search}%") // Cari di invoice
                              ->orWhere('tracking_number', 'like', "%{$search}%") // Cari di nomor resi
                              ->orWhereHas('user', function($userQuery) use ($search) {
                                  // Cari di relasi 'user' (penerima)
                                  $userQuery->where('nama_lengkap', 'like', "%{$search}%")
                                            ->orWhere('no_wa', 'like', "%{$search}%");
                              })
                              ->orWhereHas('store', function($storeQuery) use ($search) {
                                   // Cari di relasi 'store' (pengirim)
                                  $storeQuery->where('name', 'like', "%{$search}%");
                              });
                         });
                    })
                    ->select('orders.*') // Pastikan kita mengambil semua kolom dari tabel 'orders'
                    ->orderBy('created_at', 'desc'); // Urutkan data terbaru di atas

                // 4. Proses data menggunakan DataTables
                return DataTables::of($data)
                    ->addIndexColumn() // Tambah kolom 'No' (DT_RowIndex)
                    
                    // Kolom kustom 'transaksi'
                    ->addColumn('transaksi', function ($row) {
                        $paymentMethod = strtoupper($row->payment_method); // Misal: COD, QRIS
                         $date = $row->created_at->format('d M Y, H:i'); // Format tanggal
                        // Kembalikan string HTML
                        return <<<HTML
                        <div><strong>{$paymentMethod}</strong></div>
                        <div>{$row->invoice_number}</div>
                        <small>{$date}</small>
                        HTML;
                    })
                    
                    // Kolom kustom 'alamat'
                    ->addColumn('alamat', function ($row) {
                        // Ambil data pengirim (store) dari relasi
                        $senderName = $row->store->name ?? 'Toko Tidak Ditemukan';
                        $senderAddress = $row->store->address_detail ?? 'N/A';
                         $senderCity = ($row->store->village ?? '') .', '. ($row->store->district ?? '') .', '. ($row->store->regency ?? '');

                        // Ambil data penerima (user) dari relasi
                         $receiverName = $row->user->nama_lengkap ?? 'User Tidak Ditemukan';
                         $receiverPhone = $row->user->no_wa ?? 'N/A';
                         $receiverAddress = $row->shipping_address ?? 'N/A'; // Ambil alamat saat checkout
                         $receiverCity = ($row->user->village ?? '') .', '. ($row->user->district ?? '') .', '. ($row->user->regency ?? '');

                        // Kembalikan string HTML
                        return <<<HTML
                        <div class="mb-1">
                            <small>Dari:</small> <strong>{$senderName}</strong>
                            <div><small>{$senderAddress}, {$senderCity}</small></div>
                        </div>
                        <div>
                            <small>Kepada:</small> <strong>{$receiverName}</strong> ({$receiverPhone})
                            <div><small>{$receiverAddress}, {$receiverCity}</small></div>
                        </div>
                        HTML;
                    })
                    
                     // Kolom kustom 'ekspedisi'
                     ->addColumn('ekspedisi', function ($row) {
                        if (empty($row->shipping_method)) {
                            return 'N/A'; // Jika tidak ada metode pengiriman
                        }
                        try {
                            // Pecah string 'shipping_method' (cth: "express-sicepat-REG-10000-0")
                            [$type, $courier, $service, $shipCost, $asrCost] = explode('-', $row->shipping_method);
                            $formattedCost = 'Rp' . number_format((int)$shipCost, 0, ',', '.');
                            $serviceName = strtoupper($courier . ' ' . $service);
                            $typeLabel = $type == 'instant' ? 'INSTANT' : 'REGULER/EXPRESS';
                            // Kembalikan string HTML
                            return <<<HTML
                            <div><strong>{$serviceName}</strong></div>
                            <div><small>{$typeLabel}</small></div>
                            <div>{$formattedCost}</div>
                            HTML;
                        } catch (\Exception $e) {
                             // Tangani jika format string salah
                             Log::warning("Error parsing shipping_method '{$row->shipping_method}' for order {$row->id}: " . $e->getMessage());
                            return '<span class="text-danger">Error Parsing</span>';
                        }
                    })
                    
                    // Kolom kustom 'isi_paket'
                    ->addColumn('isi_paket', function ($row) {
                        $firstItem = $row->items->first(); // Ambil item pertama
                        if (!$firstItem) return 'N/A';
                        
                        // Cek apakah relasi product sudah di-load (harusnya sudah)
                        if (!$firstItem->relationLoaded('product')) $firstItem->load('product');
                        
                        $productName = $firstItem->product->name ?? 'Produk Dihapus';
                        $variantName = '';
                         // Cek jika item ini punya varian
                         if ($firstItem->variant) {
                            if (!$firstItem->relationLoaded('variant')) $firstItem->load('variant');
                            // Buat nama varian (cth: "Merah, XL")
                            $comboString = $firstItem->variant->combination_string ? str_replace(';', ', ', $firstItem->variant->combination_string) : $firstItem->variant->sku_code;
                            $variantName = ' (' . $comboString . ')';
                         }
                         $itemName = $productName . $variantName;
                         $quantity = $firstItem->quantity;
                         $totalItems = $row->items->count();
                         // Tambah info jika ada item lain
                         $otherItems = $totalItems > 1 ? ' + ' . ($totalItems - 1) . ' item lain' : '';

                        // Hitung berat total (ingat: berat ada di 'product')
                        $totalWeight = $row->items->sum(function($item) {
                            if (!$item->relationLoaded('product')) $item->load('product');
                            $weight = $item->product->weight ?? 0;
                            return $weight * $item->quantity;
                        });
                        // Ambil dimensi dari produk utama
                        $length = $firstItem->product->length ?? '-';
                        $width = $firstItem->product->width ?? '-';
                        $height = $firstItem->product->height ?? '-';

                        // Kembalikan string HTML
                         return <<<HTML
                         <div><strong>{$itemName}</strong> x {$quantity}{$otherItems}</div>
                         <small>Berat: {$totalWeight} gr</small> <br>
                         <small>Dimensi: {$length}x{$width}x{$height} cm</small>
                         HTML;
                    })
                    
                    // Kolom kustom 'status_badge'
                    ->addColumn('status_badge', function ($row) {
                        $status = $row->status;
                        $badgeClass = 'bg-secondary'; // Warna default
                        $statusText = ucfirst($status);

                        // Tentukan warna badge berdasarkan status
                        switch ($status) {
                            case 'pending': $badgeClass = 'bg-warning text-dark'; $statusText = 'Menunggu Pembayaran'; break;
                            case 'paid': $badgeClass = 'bg-info text-dark'; $statusText = 'Menunggu Pickup'; break;
                            case 'processing': $badgeClass = 'bg-primary'; $statusText = 'Diproses'; break;
                            case 'shipped': $badgeClass = 'bg-success'; $statusText = 'Terkirim'; break;
                            case 'delivered': $badgeClass = 'bg-success'; $statusText = 'Terkirim'; break;
                            case 'cancelled': $badgeClass = 'bg-danger'; $statusText = 'Dibatalkan'; break;
                            case 'failed': $badgeClass = 'bg-danger'; $statusText = 'Gagal'; break;
                        }
                        // Kembalikan string HTML badge
                        return '<span class="badge ' . e($badgeClass) . '">' . e($statusText) . '</span>';
                    })
                    
                    // Kolom kustom 'action' (Tombol Aksi)
                    ->addColumn('action', function($row){
                        // Ambil invoice number sebagai ID
                        $invoice = $row->invoice_number;
                        
                        // Buat URL untuk setiap aksi
                        $detailUrl = route('admin.orders.show', $invoice); // Detail pesanan
                        $invoicePdfUrl = route('admin.orders.invoice.pdf', $invoice); // Faktur PDF
                        $thermalPrintUrl = route('admin.orders.print.thermal', $invoice); // Cetak thermal
                        $cancelUrl = route('admin.orders.cancel', $invoice); // Batalkan pesanan
                        
                        // Ambil Resi (Asumsi disimpan di 'tracking_number' atau 'resi')
                        $resi = $row->tracking_number ?? $row->resi ?? null;
                        
                        // Buat link Lacak Resi (sesuai contoh Anda)
                        $trackLink = $resi ? 'https://tokosancaka.com/tracking/search?resi=' . e($resi) : '#';
                        // Nonaktifkan tombol jika resi belum ada
                        $trackDisabled = $resi ? '' : 'disabled style="pointer-events: none; opacity: 0.6;"';
                        
                        // Buat link Chat
                        // Asumsi: 'store' punya relasi 'user' (pemilik toko), atau 'store' punya 'user_id'
                        // $storeUserId = $row->store->user_id ?? null;
                        $customerUserId = $row->user_id; // ID pembeli

                        // Link untuk Admin chat dengan PENERIMA (Customer)
                        $chatWithReceiverUrl = route('admin.chat.start', ['user_id' => $customerUserId]); 
                        
                        // Kumpulan tombol
                        $actions = '<div class="d-flex justify-content-center gap-1 flex-wrap">';
                        
                        // Tombol Lacak (Truk)
                        $actions .= '<a href="'.e($trackLink).'" target="_blank" class="btn btn-sm btn-outline-primary" title="Lacak Paket" '.$trackDisabled.'><i class="fas fa-truck"></i></a>';
                        
                        // Tombol Detail (Mata)
                        $actions .= '<a href="'.e($detailUrl).'" class="btn btn-sm btn-outline-info" title="Detail Pesanan"><i class="fas fa-eye"></i></a>';
                        
                        // Tombol Cetak Thermal (Print)
                        $actions .= '<a href="'.e($thermalPrintUrl).'" target="_blank" class="btn btn-sm btn-outline-secondary" title="Cetak Label Thermal"><i class="fas fa-print"></i></a>';
                        
                        // Tombol Faktur PDF (File PDF)
                        $actions .= '<a href="'.e($invoicePdfUrl).'" target="_blank" class="btn btn-sm btn-outline-danger" title="Unduh Faktur PDF"><i class="fas fa-file-pdf"></i></a>';

                        // Tombol Chat ke Penerima (Pesan)
                        $actions .= '<a href="'.e($chatWithReceiverUrl).'" target="_blank" class="btn btn-sm btn-outline-success" title="Chat Penerima"><i class="fas fa-comment"></i></a>';

                         // Tombol Batalkan (Sampah)
                         if (in_array($row->status, ['pending', 'paid'])) {
                            // Tampilkan form 'DELETE' jika status memungkinkan
                            $actions .= '<form action="'.e($cancelUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin membatalkan pesanan ini?\')">'.csrf_field().method_field('PATCH').'<button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan"><i class="fas fa-trash"></i></button></form>';
                         } else {
                            // Tampilkan tombol nonaktif jika tidak bisa dibatalkan
                             $actions .= '<button class="btn btn-sm btn-outline-danger" title="Tidak dapat dibatalkan" disabled><i class="fas fa-trash"></i></button>';
                         }

                        $actions .= '</div>';
                        return $actions; // Kembalikan string HTML
                    })
                    // Tentukan kolom mana yang berisi HTML mentah
                    ->rawColumns(['transaksi', 'alamat', 'ekspedisi', 'isi_paket', 'status_badge', 'action'])
                    // Buat dan kembalikan response JSON
                    ->make(true);

            } catch (Exception $e) {
                // Tangani jika terjadi error saat proses
                Log::error('DataTables Error for Orders: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                return response()->json(['error' => 'Could not process data.', 'message' => $e->getMessage()], 500);
            }
        }
        // Jika request bukan AJAX, kembalikan ke view (fallback)
        return view('admin.orders.index');
    }

    /**
      * Menampilkan halaman detail satu pesanan.
      * Menerima $invoice (string) sebagai parameter dari route.
      */
     public function show(string $invoice)
     {
         // Cari order berdasarkan invoice_number, bukan ID
         $order = Order::where('invoice_number', $invoice)
                        ->with(['user', 'store', 'items.product', 'items.variant']) // Load semua relasi
                        ->firstOrFail(); // Error 404 jika tidak ketemu
         
         // Tampilkan view 'admin.orders.show' dengan data order
         return view('admin.orders.show', compact('order'));
     }


     /**
      * Membatalkan pesanan.
      * Menerima $invoice (string) sebagai parameter.
      */
     public function cancel(string $invoice)
     {
         try {
             // Cari order berdasarkan invoice_number
             $order = Order::where('invoice_number', $invoice)->firstOrFail();
             
             // Hanya batalkan jika statusnya masih 'pending' atau 'paid'
             if (in_array($order->status, ['pending', 'paid'])) {
                 // Ubah status order
                 $order->status = 'cancelled';
                 $order->save();

                 // Logika PENTING: Kembalikan Stok (jika perlu)
                 // Jika Anda mengurangi stok saat order DIBUAT (bukan saat dibayar),
                 // Anda harus mengembalikannya di sini.
                 
                 /* // Contoh logika pengembalian stok (Uncomment jika perlu)
                 foreach ($order->items as $item) {
                    if ($item->product_variant_id && $item->variant) {
                        // Jika item adalah varian
                        $item->variant->increment('stock', $item->quantity);
                    } elseif ($item->product) {
                        // Jika item adalah produk non-varian
                        $item->product->increment('stock', $item->quantity);
                    }
                 }
                 */

                 // Kembali ke halaman sebelumnya dengan pesan sukses
                 return redirect()->back()->with('success', 'Pesanan berhasil dibatalkan.');
             } else {
                 // Jika status sudah 'processing', 'shipped', dll.
                 return redirect()->back()->with('error', 'Pesanan tidak dapat dibatalkan (status: ' . $order->status . ').');
             }
         } catch (Exception $e) {
             // Tangani jika terjadi error
             Log::error('Gagal membatalkan order ' . $invoice . ': ' . $e->getMessage());
             return redirect()->back()->with('error', 'Terjadi kesalahan saat membatalkan pesanan.');
         }
     }

     /**
      * Ekspor Faktur PDF untuk satu pesanan.
      * Menerima $invoice (string) sebagai parameter.
      */
     public function exportInvoice(string $invoice)
     {
         try {
             // Cari order dan relasinya
             $order = Order::where('invoice_number', $invoice)
                            ->with(['user', 'store', 'items.product', 'items.variant'])
                            ->firstOrFail();
             
             // Siapkan data untuk dikirim ke view Blade
             $data = [
                'order' => $order,
                'title' => 'Faktur ' . $order->invoice_number
             ];
             
             // PENTING: Buat file view Blade di:
             // resources/views/admin/orders/invoice_pdf.blade.php
             // View ini akan berisi HTML untuk faktur Anda.
             $pdf = Pdf::loadView('admin.orders.invoice_pdf', $data);
             
             // Buat nama file
             $filename = 'Faktur-' . $order->invoice_number . '.pdf';
             // Unduh file PDF
             return $pdf->download($filename);

         } catch (Exception $e) {
             Log::error('Gagal membuat PDF faktur ' . $invoice . ': ' . $e->getMessage());
             return redirect()->back()->with('error', 'Gagal membuat PDF faktur: ' . $e->getMessage());
         }
     }

     /**
      * Cetak Label Thermal PDF untuk satu pesanan.
      * Menerima $invoice (string) sebagai parameter.
      */
     public function printThermal(string $invoice)
     {
         try {
             // Cari order dan relasinya
             $order = Order::where('invoice_number', $invoice)
                            ->with(['user', 'store', 'items.product', 'items.variant'])
                            ->firstOrFail();
             
             // Siapkan data untuk view Blade
             $data = [
                'order' => $order,
                'title' => 'Label ' . $order->invoice_number
             ];
             
             // PENTING: Buat file view Blade di:
             // resources/views/admin/orders/thermal_pdf.blade.php
             // View ini harus di-desain khusus untuk ukuran kertas thermal (cth: A6 atau 80mm)
             
             // Atur ukuran kertas custom untuk thermal (contoh: 80mm x 100mm)
             // Ukuran dalam points (1mm = 2.83465 points)
             $widthInMm = 80;
             $heightInMm = 100; // Sesuaikan tinggi label Anda
             $customPaper = [0, 0, ($widthInMm * 2.83465), ($heightInMm * 2.83465)];

             $pdf = Pdf::loadView('admin.orders.thermal_pdf', $data)
                        ->setPaper($customPaper, 'portrait'); // Set kertas custom
             
             // Buat nama file
             $filename = 'Label-' . $order->invoice_number . '.pdf';
             // Tampilkan PDF di browser (stream) agar bisa langsung di-print
             return $pdf->stream($filename);

         } catch (Exception $e) {
             Log::error('Gagal membuat PDF thermal ' . $invoice . ': ' . $e->getMessage());
             return redirect()->back()->with('error', 'Gagal membuat PDF thermal: ' . $e->getMessage());
         }
     }


     /**
      * Ekspor Laporan Penjualan PDF (dengan filter tanggal).
      */
     public function exportReport(Request $request)
     {
         try {
             // Validasi input tanggal
             $request->validate([
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
             ]);

             // Ambil tanggal, atau set default ke bulan ini
             $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
             $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
             
             // Tentukan status pesanan yang dianggap "selesai" atau "masuk"
             $statusesToInclude = ['paid', 'processing', 'shipped', 'delivered'];
             
             // Ambil data pesanan sesuai filter
             $orders = Order::with(['user', 'store', 'items'])
                ->whereIn('status', $statusesToInclude)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->orderBy('created_at', 'asc') // Urutkan dari terlama
                ->get();
                
             // Hitung total ringkasan
             $totalRevenue = $orders->sum('total_amount');
             $totalOrders = $orders->count();

             // Siapkan data untuk view Blade
             $data = [
                 'orders' => $orders,
                 'startDate' => $startDate,
                 'endDate' => $endDate,
                 'totalRevenue' => $totalRevenue,
                 'totalOrders' => $totalOrders,
                 'title' => 'Laporan Penjualan'
             ];
             
             // PENTING: Buat file view Blade di:
             // resources/views/admin/orders/report_pdf.blade.php
             // View ini akan berisi tabel laporan Anda.
             $pdf = Pdf::loadView('admin.orders.report_pdf', $data)->setPaper('a4', 'landscape'); // Atur ke landscape
             
             // Buat nama file
             $filename = 'Laporan-Penjualan-' . $startDate . '-sd-' . $endDate . '.pdf';
             // Unduh laporan
             return $pdf->download($filename);

         } catch (Exception $e) {
             Log::error('Gagal membuat Laporan PDF: ' . $e->getMessage());
             return redirect()->back()->with('error', 'Gagal membuat Laporan PDF: ' . $e->getMessage());
         }
     }
}

