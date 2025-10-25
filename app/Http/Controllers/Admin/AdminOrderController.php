<?php

namespace App\Http\Controllers\Admin;

// Import class-class (dependency) yang dibutuhkan
use App\Http\Controllers\Controller; // Controller dasar Laravel
use Illuminate\Http\Request; // Mengelola request HTTP
use App\Models\Order; // Model untuk berinteraksi dengan tabel 'orders'
use App\Models\User; // Model User (untuk link chat)
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
                // Ambil nilai filter status dari tab yang aktif di frontend
                $statusFilter = $request->input('status'); // Contoh: 'pending', 'processing', 'menunggu-pickup'

                // Ambil nilai filter dari kotak pencarian custom
                $searchQuery = $request->input('search_query');

                // 1. Mulai query ke model Order
                // 'with' (Eager Loading) sangat penting untuk performa.
                // Ini mengambil data relasi (user, store, items) dalam satu query,
                // menghindari N+1 query problem.
                $data = Order::with([
                        // Relasi ke tabel user (pembeli/customer)
                        // Pastikan ada function 'user()' di Model Order.php
                        'user',
                        // Relasi ke tabel store (penjual/pengirim)
                        // Pastikan ada function 'store()' di Model Order.php
                        'store',
                        // Relasi ke tabel order_items, DAN dari item ke product
                        // Pastikan ada function 'items()' di Model Order.php
                        // Pastikan ada function 'product()' di Model OrderItem.php
                        'items.product',
                        // Relasi ke tabel order_items, DAN dari item ke product_variants
                        // Pastikan ada function 'variant()' di Model OrderItem.php
                        'items.variant'
                    ])
                    ->when($statusFilter, function ($query, $statusTab) {
                        // 2. Terapkan Filter Status jika ada (dari tab)
                        // 'when' hanya menjalankan query jika $statusFilter tidak kosong/null
                        
                        // !! PENTING: Sesuaikan map ini dengan nilai status di frontend & backend !!
                        $statusMap = [
                            // 'key_dari_tab_frontend' => ['status_di_database'],
                            'pending' => ['pending'], // Menunggu Bayar
                            'menunggu-pickup' => ['paid', 'processing'], // Dibayar ATAU sedang diproses KiriminAja
                            'diproses' => ['shipping'], // Status jika sudah dikirim (misalnya)
                            'terkirim' => ['delivered', 'completed'], // Sudah sampai ATAU selesai
                            'batal' => ['cancelled', 'failed', 'rejected'], // Semua jenis pembatalan/kegagalan
                            // 'selesai' => ['completed'] // Jika ada tab 'Selesai' terpisah
                        ];

                        if (isset($statusMap[$statusTab])) {
                            // Jika status dari tab ada di map, filter pakai 'whereIn'
                            // 'whereIn' cocok untuk status yang bisa lebih dari satu (cth: 'terkirim')
                            return $query->whereIn('status', $statusMap[$statusTab]);
                        }
                        // Jika $statusTab = "" (tab 'Semua') atau tidak dikenal di map, jangan filter
                        return $query;
                    })
                    ->when($searchQuery, function ($query, $search) {
                        // 3. Terapkan Filter Pencarian jika ada
                        // 'when' hanya berjalan jika $searchQuery tidak kosong/null
                        return $query->where(function($q) use ($search) {
                            // Grup 'where' (closure) agar 'orWhere' tidak bentrok dengan filter status
                            // Cari di kolom invoice_number tabel orders
                            $q->where('invoice_number', 'like', "%{$search}%")
                              // Cari di kolom tracking_number tabel orders (jika sudah ditambahkan)
                              ->orWhere('tracking_number', 'like', "%{$search}%")
                              // Cari di kolom resi tabel orders (jika Anda pakai nama ini)
                              // ->orWhere('resi', 'like', "%{$search}%")
                              // Cari di relasi 'user' (penerima)
                              ->orWhereHas('user', function($userQuery) use ($search) {
                                  // Cari nama lengkap ATAU nomor WA di tabel users
                                  $userQuery->where('nama_lengkap', 'like', "%{$search}%")
                                            ->orWhere('no_wa', 'like', "%{$search}%");
                              })
                              // Cari di relasi 'store' (pengirim)
                              ->orWhereHas('store', function($storeQuery) use ($search) {
                                  // Cari nama toko di tabel stores
                                  $storeQuery->where('name', 'like', "%{$search}%");
                              });
                              // Tambahkan orWhereHas lain jika perlu (misal: cari nama produk di order_items)
                        });
                    })
                    // Pastikan kita mengambil semua kolom dari tabel 'orders'
                    // Ini penting jika Anda menggunakan DataTables versi lama
                    ->select('orders.*')
                    // Urutkan data terbaru di atas
                    ->orderBy('created_at', 'desc');

                // 4. Proses data menggunakan DataTables
                return DataTables::of($data)
                    // Tambah kolom 'No' (DT_RowIndex) secara otomatis
                    ->addIndexColumn()

                    // Kolom kustom 'transaksi'
                    ->addColumn('transaksi', function ($row) { // $row adalah objek Order
                        // Ambil metode pembayaran dan ubah ke huruf besar (cth: COD, QRIS)
                        $paymentMethod = strtoupper($row->payment_method ?? 'N/A');
                        // Format tanggal pembuatan order (dd M YYYY, HH:mm)
                        $date = Carbon::parse($row->created_at)->translatedFormat('d M Y, H:i'); // Gunakan translatedFormat jika perlu bahasa Indo
                        // Kembalikan string HTML untuk kolom ini
                        return <<<HTML
                        <div><strong>{$paymentMethod}</strong></div>
                        <div>{$row->invoice_number}</div>
                        <small>{$date}</small>
                        HTML;
                    })

                    // Kolom kustom 'alamat'
                    ->addColumn('alamat', function ($row) {
                        // Ambil data pengirim (store) dari relasi, gunakan '??' untuk fallback jika null
                        $senderName = $row->store->name ?? '<span class="text-danger">Toko N/A</span>';
                        $senderAddress = $row->store->address_detail ?? 'Alamat toko tidak lengkap';
                        // Gabungkan info wilayah toko (null-safe)
                        $senderCity = implode(', ', array_filter([
                            $row->store->village ?? null,
                            $row->store->district ?? null,
                            $row->store->regency ?? null
                        ]));
                        $senderCity = $senderCity ?: 'Wilayah toko tidak lengkap';


                        // Ambil data penerima (user) dari relasi, gunakan '??' untuk fallback jika null
                        $receiverName = $row->user->nama_lengkap ?? '<span class="text-danger">Pembeli N/A</span>';
                        $receiverPhone = $row->user->no_wa ?? 'No WA N/A';
                        // Ambil alamat pengiriman yang disimpan saat checkout
                        $receiverAddress = $row->shipping_address ?? 'Alamat pengiriman tidak ada';
                        // Gabungkan info wilayah pembeli (null-safe)
                         $receiverCity = implode(', ', array_filter([
                            $row->user->village ?? null,
                            $row->user->district ?? null,
                            $row->user->regency ?? null
                        ]));
                         $receiverCity = $receiverCity ?: 'Wilayah pembeli tidak lengkap';

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
                        // Jika kolom 'shipping_method' kosong
                        if (empty($row->shipping_method)) {
                            return '<span class="text-muted">N/A</span>';
                        }
                        try {
                            // Coba pecah string 'shipping_method' (cth: "express-sicepat-REG-10000-0")
                            // Format: type-courier-service-cost-insuranceCost
                            // !! Pastikan format string ini konsisten saat disimpan di CheckoutController !!
                            $parts = explode('-', $row->shipping_method);
                            if (count($parts) < 4) { // Minimal harus ada 4 bagian (type, courier, service, cost)
                                throw new Exception("Format shipping_method tidak lengkap.");
                            }
                            $type = $parts[0]; // express / instant
                            $courier = $parts[1]; // sicepat / jne / etc
                            $service = $parts[2]; // REG / OKE / etc
                            $shipCost = (int) $parts[3]; // Ambil biaya kirim
                            // $asrCost = isset($parts[4]) ? (int) $parts[4] : 0; // Ambil biaya asuransi (opsional)

                            // Format biaya kirim menjadi Rupiah
                            $formattedCost = 'Rp' . number_format($shipCost, 0, ',', '.');
                            // Gabungkan nama kurir dan layanan
                            $serviceName = strtoupper($courier) . ' - ' . strtoupper($service);
                            // Tentukan label tipe pengiriman
                            $typeLabel = $type == 'instant' ? 'INSTANT' : strtoupper($type); // Misal: EXPRESS

                            // Kembalikan string HTML
                            return <<<HTML
                            <div><strong>{$serviceName}</strong></div>
                            <div><small>{$typeLabel}</small></div>
                            <div>{$formattedCost}</div>
                            HTML;
                        } catch (Exception $e) {
                            // Tangani jika format string salah atau tidak lengkap
                            Log::warning("Error parsing shipping_method '{$row->shipping_method}' for order {$row->invoice_number}: " . $e->getMessage());
                            return '<span class="text-danger small">Parsing Error</span>';
                        }
                    })

                    // Kolom kustom 'isi_paket'
                    ->addColumn('isi_paket', function ($row) {
                        // Ambil item pertama dari relasi 'items'
                        $firstItem = $row->items->first();
                        // Jika tidak ada item sama sekali
                        if (!$firstItem) return '<span class="text-muted">N/A</span>';

                        // Ambil nama produk dari relasi 'product' di item pertama
                        // Gunakan fallback jika produk sudah dihapus
                        $productName = $firstItem->product->name ?? '<span class="text-danger">Produk Dihapus</span>';

                        $variantName = '';
                        // Cek apakah item ini punya varian (berdasarkan product_variant_id)
                        if ($firstItem->product_variant_id && $firstItem->variant) {
                            // Ambil detail varian dari relasi 'variant'
                            // Buat nama varian dari combination_string atau SKU
                            $comboString = $firstItem->variant->combination_string ? str_replace(';', ', ', $firstItem->variant->combination_string) : $firstItem->variant->sku_code;
                            $variantName = ' (' . ($comboString ?: 'Varian N/A') . ')';
                        }
                        // Gabungkan nama produk dan varian (jika ada)
                        $itemName = $productName . $variantName;
                        // Ambil kuantitas item pertama
                        $quantity = $firstItem->quantity;
                        // Hitung jumlah total item dalam pesanan
                        $totalItems = $row->items->count();
                        // Tambah teks jika ada item lain
                        $otherItems = $totalItems > 1 ? ' + ' . ($totalItems - 1) . ' item lain' : '';

                        // Hitung berat total dari SEMUA item dalam pesanan
                        $totalWeight = $row->items->sum(function($item) {
                            // Ambil berat dari relasi 'product' di setiap item
                            // Ingat: berat ada di produk utama, bukan varian
                            $weight = $item->product->weight ?? 0;
                            return $weight * $item->quantity; // Kalikan dengan kuantitas item
                        });

                        // Ambil dimensi dari produk utama item pertama (asumsi semua item mirip)
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
                        $status = $row->status; // Ambil status dari kolom 'status'
                        $badgeClass = 'bg-secondary'; // Warna default (abu-abu)
                        $statusText = ucfirst($status); // Teks default (huruf awal kapital)

                        // Tentukan warna dan teks badge berdasarkan nilai status
                        // !! Sesuaikan case ini dengan nilai status AKTUAL di database Anda !!
                        switch ($status) {
                            case 'pending':
                                $badgeClass = 'bg-warning text-dark'; // Kuning
                                $statusText = 'Menunggu Pembayaran';
                                break;
                            case 'paid': // Jika Anda menggunakan status 'paid'
                                $badgeClass = 'bg-info text-dark';    // Biru muda
                                $statusText = 'Menunggu Pickup';
                                break;
                            case 'processing': // Status setelah dibayar / COD dibuat
                                $badgeClass = 'bg-primary';           // Biru tua
                                $statusText = 'Diproses'; // Atau 'Menunggu Pickup' jika lebih cocok
                                break;
                            case 'shipping': // Jika Anda menggunakan status 'shipping'
                                $badgeClass = 'bg-success';          // Hijau
                                $statusText = 'Dikirim';
                                break;
                             case 'delivered': // Jika Anda menggunakan status 'delivered'
                                $badgeClass = 'bg-success';          // Hijau
                                $statusText = 'Terkirim'; // Atau 'Sampai Tujuan'
                                break;
                             case 'completed': // Jika Anda menggunakan status 'completed'
                                $badgeClass = 'bg-success';          // Hijau
                                $statusText = 'Selesai';
                                break;
                            case 'cancelled':
                            case 'failed':
                            case 'rejected': // Gabungkan semua status gagal/batal
                                $badgeClass = 'bg-danger';           // Merah
                                $statusText = 'Dibatalkan/Gagal';
                                break;
                            default:
                                $statusText = 'Status Tidak Dikenal';
                                break;
                        }
                        // Kembalikan string HTML badge Bootstrap
                        return '<span class="badge ' . e($badgeClass) . '">' . e($statusText) . '</span>';
                    })

                    // Kolom kustom 'action' (Tombol Aksi)
                    ->addColumn('action', function($row){
                        // Ambil invoice number sebagai ID unik
                        $invoice = $row->invoice_number;

                        // Buat URL untuk setiap aksi menggunakan helper 'route()'
                        $detailUrl = route('admin.orders.show', $invoice); // Detail pesanan
                        $invoicePdfUrl = route('admin.orders.invoice.pdf', $invoice); // Faktur PDF
                        $thermalPrintUrl = route('admin.orders.print.thermal', $invoice); // Cetak thermal
                        $cancelUrl = route('admin.orders.cancel', $invoice); // Batalkan pesanan (URL untuk form PATCH)

                        // Ambil Resi (Nomor Pelacakan)
                        // Coba ambil dari kolom 'tracking_number', jika tidak ada coba 'resi', jika tidak ada set null
                        // !! Pastikan Anda punya salah satu kolom ini di tabel 'orders' !!
                        $resi = $row->tracking_number ?? $row->resi ?? null;

                        // Buat link Lacak Resi eksternal
                        // Ganti URL ini jika halaman tracking Anda berbeda
                        $trackLink = $resi ? ('https://tokosancaka.com/tracking/search?resi=' . urlencode($resi)) : '#';
                        // Siapkan atribut 'disabled' jika resi tidak ada
                        $trackDisabled = $resi ? '' : 'disabled style="pointer-events: none; opacity: 0.6;"';

                        // Buat link Chat (Admin chat dengan Customer/Penerima)
                        $customerUserId = $row->user_id; // ID pembeli
                        // Pastikan ID pembeli ada sebelum membuat link
                        $chatWithReceiverUrl = $customerUserId ? route('admin.chat.start', ['user_id' => $customerUserId]) : '#';
                        // Nonaktifkan tombol chat jika ID pembeli tidak ada
                        $chatDisabled = $customerUserId ? '' : 'disabled style="pointer-events: none; opacity: 0.6;"';

                        // Kumpulan tombol HTML (menggunakan class Bootstrap)
                        // d-flex: layout flexbox
                        // justify-content-center: rata tengah horizontal
                        // gap-1: jarak antar tombol
                        // flex-wrap: agar tombol bisa turun baris jika layar sempit
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
                        $actions .= '<a href="'.e($chatWithReceiverUrl).'" target="_blank" class="btn btn-sm btn-outline-success" title="Chat Penerima" '.$chatDisabled.'><i class="fas fa-comment"></i></a>';

                        // Tombol Batalkan (Sampah)
                        // Hanya tampilkan form jika status memungkinkan untuk dibatalkan
                        if (in_array($row->status, ['pending', 'paid', 'processing'])) { // Bisa juga batalkan 'processing' jika belum di-pickup
                            // Form ini akan mengirim request PATCH ke $cancelUrl saat disubmit
                            $actions .= '<form action="'.e($cancelUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin membatalkan pesanan ini?\')">'
                                     . csrf_field() // Token CSRF Laravel
                                     . method_field('PATCH') // Method spoofing untuk PATCH
                                     . '<button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan Pesanan"><i class="fas fa-trash"></i></button>'
                                     . '</form>';
                        } else {
                            // Tampilkan tombol nonaktif jika tidak bisa dibatalkan
                            $actions .= '<button class="btn btn-sm btn-outline-danger" title="Tidak dapat dibatalkan" disabled><i class="fas fa-trash"></i></button>';
                        }

                        $actions .= '</div>'; // Tutup div container tombol
                        return $actions; // Kembalikan string HTML
                    })
                    // Beri tahu DataTables kolom mana saja yang berisi HTML mentah (tidak perlu di-escape)
                    ->rawColumns(['transaksi', 'alamat', 'ekspedisi', 'isi_paket', 'status_badge', 'action'])
                    // Buat dan kembalikan response JSON yang siap dibaca DataTables
                    ->make(true);

            } catch (Exception $e) {
                // Tangani jika terjadi error saat proses pengambilan data
                Log::error('DataTables Error for Orders: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                // Kembalikan response error JSON
                return response()->json(['error' => 'Tidak dapat memproses data.', 'message' => $e->getMessage()], 500);
            }
        }
        // Jika request bukan AJAX, kembalikan ke view biasa (fallback, seharusnya tidak terjadi jika JS aktif)
        // return view('admin.orders.index'); // Atau bisa juga abort(403)
         abort(403, 'Request harus via AJAX.'); // Lebih aman
    }

    /**
     * Menampilkan halaman detail satu pesanan.
     * Menerima $invoice (string) sebagai parameter dari route.
     */
     public function show(string $invoice)
     {
         try {
             // Cari order berdasarkan invoice_number, bukan ID
             $order = Order::where('invoice_number', $invoice)
                 // Load semua relasi yang mungkin dibutuhkan di halaman detail
                 ->with(['user', 'store', 'items.product', 'items.variant'])
                 // Gagal (404 Not Found) jika order tidak ditemukan
                 ->firstOrFail();

             // Tampilkan view 'admin.orders.show' dengan data order
             // PENTING: Anda perlu membuat file view ini: resources/views/admin/orders/show.blade.php
             return view('admin.orders.show', compact('order'));

         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             // Tangani jika order tidak ditemukan
             Log::warning("Order detail not found for invoice: " . $invoice);
             return redirect()->route('admin.orders.index')->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
         } catch (Exception $e) {
             // Tangani error lainnya
             Log::error('Error showing order detail for ' . $invoice . ': ' . $e->getMessage());
             return redirect()->route('admin.orders.index')->with('error', 'Terjadi kesalahan saat menampilkan detail pesanan.');
         }
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

             // Tentukan status mana saja yang boleh dibatalkan
             $cancellableStatuses = ['pending', 'paid', 'processing']; // Misal: 'processing' bisa dibatalkan sebelum pickup

             // Hanya batalkan jika statusnya termasuk dalam daftar $cancellableStatuses
             if (in_array($order->status, $cancellableStatuses)) {
                 // Ubah status order menjadi 'cancelled'
                 $order->status = 'cancelled';
                 // Tambahkan juga timestamp pembatalan jika ada kolomnya (misal: 'cancelled_at')
                 // $order->cancelled_at = now();
                 $order->save(); // Simpan perubahan

                 // TODO: Logika PENTING untuk Mengembalikan Stok Produk/Varian
                 // Jika Anda mengurangi stok saat order DIBUAT atau DIBAYAR,
                 // Anda harus mengembalikannya di sini agar stok akurat.
                 /* // Contoh logika pengembalian stok (Uncomment dan sesuaikan jika perlu)
                 Log::info("Attempting to restock items for cancelled order: " . $order->invoice_number);
                 foreach ($order->items as $item) {
                     try {
                         if ($item->product_variant_id && $item->variant) {
                             // Jika item adalah varian, tambahkan stok ke varian
                             $item->variant->increment('stock', $item->quantity);
                             Log::info("Restocked variant ID {$item->product_variant_id} by {$item->quantity}");
                         } elseif ($item->product) {
                             // Jika item adalah produk non-varian, tambahkan stok ke produk
                             $item->product->increment('stock', $item->quantity);
                             Log::info("Restocked product ID {$item->product_id} by {$item->quantity}");
                         } else {
                              Log::warning("Could not restock item ID {$item->id}: Product or Variant not found.");
                         }
                     } catch (Exception $stockError) {
                         Log::error("Error restocking item ID {$item->id} for order {$order->invoice_number}: " . $stockError->getMessage());
                         // Pertimbangkan: Lanjutkan proses atau hentikan?
                     }
                 }
                 */

                 // Kirim notifikasi (opsional)
                 // event(new OrderCancelledEvent($order));

                 // Kembali ke halaman sebelumnya dengan pesan sukses
                 return redirect()->back()->with('success', 'Pesanan #' . $invoice . ' berhasil dibatalkan.');
             } else {
                 // Jika status tidak memungkinkan untuk dibatalkan
                 Log::warning("Attempt to cancel order {$invoice} with non-cancellable status: {$order->status}");
                 return redirect()->back()->with('error', 'Pesanan tidak dapat dibatalkan (Status saat ini: ' . ucfirst($order->status) . ').');
             }
         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             // Tangani jika order tidak ditemukan
             Log::warning("Attempt to cancel non-existent order: " . $invoice);
             return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
         } catch (Exception $e) {
             // Tangani error lainnya
             Log::error('Gagal membatalkan order ' . $invoice . ': ' . $e->getMessage());
             return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat membatalkan pesanan.');
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
                             ->with(['user', 'store', 'items.product', 'items.variant']) // Eager load relasi
                             ->firstOrFail(); // Error 404 jika tidak ketemu

             // Siapkan data untuk dikirim ke view Blade PDF
             $data = [
                 'order' => $order,
                 'title' => 'Faktur ' . $order->invoice_number // Judul dokumen PDF
             ];

             // PENTING: Buat file view Blade di:
             // resources/views/admin/orders/invoice_pdf.blade.php
             // View ini akan berisi HTML untuk faktur Anda (gunakan tabel, dll).
             $pdf = Pdf::loadView('admin.orders.invoice_pdf', $data);
             // Anda bisa atur orientasi kertas jika perlu: ->setPaper('a4', 'portrait')

             // Buat nama file PDF yang akan diunduh
             $filename = 'Faktur-' . $order->invoice_number . '.pdf';
             // Unduh file PDF (browser akan menampilkan dialog save/open)
             return $pdf->download($filename);

         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             Log::warning("Attempt to export invoice for non-existent order: " . $invoice);
             return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
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
                             ->with(['user', 'store', 'items.product', 'items.variant']) // Eager load
                             ->firstOrFail(); // Error 404 jika tidak ketemu

             // Siapkan data untuk view Blade PDF
             $data = [
                 'order' => $order,
                 'title' => 'Label ' . $order->invoice_number // Judul dokumen
                 // Tambahkan data lain jika perlu (misal: barcode string)
             ];

             // PENTING: Buat file view Blade di:
             // resources/views/admin/orders/thermal_pdf.blade.php
             // View ini harus di-desain khusus untuk ukuran kertas thermal
             // Gunakan styling inline atau CSS sederhana, hindari JavaScript.

             // Atur ukuran kertas custom untuk thermal (contoh: 80mm x 100mm)
             // Ukuran dalam points (1mm ≈ 2.83 points)
             $widthInMm = 80;
             $heightInMm = 100; // Sesuaikan tinggi label Anda
             // Format: [x min, y min, x max, y max] dalam points
             $customPaper = [0, 0, ($widthInMm * 2.83465), ($heightInMm * 2.83465)];

             // Load view dan set ukuran kertas custom
             $pdf = Pdf::loadView('admin.orders.thermal_pdf', $data)
                       ->setPaper($customPaper, 'portrait'); // Set kertas custom, orientasi portrait

             // Buat nama file
             $filename = 'Label-' . $order->invoice_number . '.pdf';
             // Tampilkan PDF di browser (stream) agar bisa langsung di-print dari browser
             return $pdf->stream($filename);

         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             Log::warning("Attempt to print thermal for non-existent order: " . $invoice);
             return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
         } catch (Exception $e) {
             Log::error('Gagal membuat PDF thermal ' . $invoice . ': ' . $e->getMessage());
             // Beri pesan error yang lebih informatif jika mungkin (misal, view tidak ditemukan)
             if (str_contains($e->getMessage(), 'View [admin.orders.thermal_pdf] not found')) {
                 return redirect()->back()->with('error', 'Gagal membuat PDF: View thermal_pdf.blade.php belum dibuat.');
             }
             return redirect()->back()->with('error', 'Gagal membuat PDF thermal: ' . $e->getMessage());
         }
     }


     /**
      * Ekspor Laporan Penjualan PDF (dengan filter tanggal).
      */
     public function exportReport(Request $request)
     {
         try {
             // Validasi input tanggal dari request (form di modal)
             $validated = $request->validate([
                 // 'nullable' berarti tidak wajib diisi
                 'start_date' => 'nullable|date_format:Y-m-d',
                 // 'end_date' harus setelah atau sama dengan 'start_date' jika keduanya diisi
                 'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
             ]);

             // Ambil tanggal dari input, atau set default ke bulan ini jika tidak diisi
             $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
             $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

             // Tentukan status pesanan yang dianggap "masuk" dalam laporan
             // !! Sesuaikan array ini dengan logika bisnis Anda !!
             $statusesToInclude = ['paid', 'processing', 'shipped', 'delivered', 'completed'];

             // Ambil data pesanan sesuai filter tanggal dan status
             $orders = Order::with(['user', 'store', 'items']) // Eager load relasi
                 ->whereIn('status', $statusesToInclude) // Filter berdasarkan status yang valid
                 // Filter berdasarkan rentang tanggal 'created_at'
                 ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                 ->orderBy('created_at', 'asc') // Urutkan dari pesanan terlama dalam rentang
                 ->get(); // Ambil semua data yang cocok

             // Hitung total ringkasan untuk laporan
             $totalRevenue = $orders->sum('total_amount'); // Jumlahkan total semua pesanan
             $totalOrders = $orders->count(); // Hitung jumlah pesanan

             // Siapkan data untuk dikirim ke view Blade PDF
             $data = [
                 'orders' => $orders,           // Koleksi data pesanan
                 'startDate' => $startDate,     // Tanggal mulai filter
                 'endDate' => $endDate,         // Tanggal selesai filter
                 'totalRevenue' => $totalRevenue, // Total pendapatan
                 'totalOrders' => $totalOrders,   // Jumlah total pesanan
                 'title' => 'Laporan Penjualan ' . Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y') // Judul dinamis
             ];

             // PENTING: Buat file view Blade di:
             // resources/views/admin/orders/report_pdf.blade.php
             // View ini akan berisi tabel laporan Anda (loop $orders), dan ringkasan total.
             $pdf = Pdf::loadView('admin.orders.report_pdf', $data)
                       ->setPaper('a4', 'landscape'); // Atur kertas A4 landscape agar tabel muat

             // Buat nama file PDF yang dinamis
             $filename = 'Laporan-Penjualan-' . $startDate . '-sd-' . $endDate . '.pdf';
             // Unduh laporan PDF
             return $pdf->download($filename);

         } catch (\Illuminate\Validation\ValidationException $e) {
              // Tangani error validasi tanggal
              Log::warning('Validation error on export report: ' . $e->getMessage());
              return redirect()->back()->withErrors($e->errors())->withInput(); // Kembali dengan pesan error validasi
         } catch (Exception $e) {
             Log::error('Gagal membuat Laporan PDF: ' . $e->getMessage());
              // Beri pesan error yang lebih informatif jika mungkin (misal, view tidak ditemukan)
             if (str_contains($e->getMessage(), 'View [admin.orders.report_pdf] not found')) {
                 return redirect()->back()->with('error', 'Gagal membuat PDF: View report_pdf.blade.php belum dibuat.');
             }
             return redirect()->back()->with('error', 'Gagal membuat Laporan PDF: ' . $e->getMessage());
         }
     }
}

