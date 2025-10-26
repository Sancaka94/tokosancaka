<?php

// Tentukan namespace untuk controller ini (lokasi file dalam struktur folder)
namespace App\Http\Controllers\Admin;

// Import class-class (dependency) yang dibutuhkan dari Laravel dan library lain
use App\Http\Controllers\Controller; // Controller dasar Laravel
use Illuminate\Http\Request; // Class untuk mengelola data request HTTP (query string, form input)
use App\Models\Order; // Model Eloquent untuk berinteraksi dengan tabel 'orders'
use App\Models\Pesanan; // <-- TAMBAHKAN INI: Model Eloquent untuk tabel 'Pesanan'
use App\Models\User; // Model User (digunakan untuk relasi dan pencarian)
use Illuminate\Support\Facades\Log; // Fasilitas logging Laravel untuk mencatat error atau informasi
use Exception; // Class Exception dasar PHP untuk menangani error umum
use Barryvdh\DomPDF\Facade\Pdf; // Facade untuk library DomPDF (generate PDF dari HTML)
use Carbon\Carbon; // Library untuk mempermudah manipulasi tanggal dan waktu
use Illuminate\Database\Eloquent\ModelNotFoundException; // Exception khusus saat query findOrFail() gagal
use Illuminate\Pagination\Paginator; // <-- TAMBAHAN: Untuk pagination manual
use Illuminate\Pagination\LengthAwarePaginator; // <-- TAMBAHAN: Untuk pagination manual
use Illuminate\Support\Collection; // <-- TAMBAHAN: Untuk pagination manual

// Deklarasi class controller, mewarisi (extends) Controller dasar Laravel
class AdminOrderController extends Controller
{
    /**
     * Menampilkan halaman utama Data Pesanan (Gabungan 'Orders' dan 'Pesanan').
     * Method ini mengambil data dari kedua tabel, menggabungkan, memfilter,
     * melakukan pencarian, dan paginasi secara manual.
     *
     * @param  \Illuminate\Http\Request  $request Object request yang berisi query string (cth: ?status=pending&search=INV)
     * @return \Illuminate\View\View      Objek view Blade 'admin.orders.index' beserta data gabungan
     */
    public function index(Request $request)
    {
        // Ambil nilai filter 'status' dari URL, contoh: /admin/orders?status=pending
        $statusFilter = $request->query('status');
        // Ambil nilai 'search' dari URL, contoh: /admin/orders?search=INV-123
        $searchQuery = $request->query('search');

        // Tentukan jumlah item per halaman untuk pagination
        // Ambil dari query string 'per_page', jika tidak ada, gunakan 15
        $perPage = $request->query('per_page', 15);

        // --- 1. Query 'Orders' ---
        $orderQuery = Order::query()
            ->with([
                'user:id_pengguna,nama_lengkap,no_wa,village,district,regency',
                'store:id,name,address_detail,village,district,regency',
                'items:id,order_id,product_id,product_variant_id,quantity',
                'items.product:id,name,weight,length,width,height',
                'items.variant:id,product_variant_id,combination_string,sku_code'
            ])
            ->when($statusFilter, function ($query, $statusTab) {
                // Mapping status filter (dari URL) ke status di tabel 'orders'
                $statusMap = [
                    'pending' => ['pending'],
                    'menunggu-pickup' => ['paid', 'processing'],
                    'diproses' => ['shipping'],
                    'terkirim' => ['delivered'],
                    'selesai' => ['completed'],
                    'batal' => ['cancelled', 'failed', 'rejected'],
                ];
                if (isset($statusMap[$statusTab])) {
                    return $query->whereIn('status', $statusMap[$statusTab]);
                }
                return $query;
            })
            ->when($searchQuery, function ($query, $search) {
                // Pencarian untuk tabel 'orders'
                return $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhere('tracking_number', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('nama_lengkap', 'like', "%{$search}%")
                                    ->orWhere('no_wa', 'like', "%{$search}%");
                      })
                      ->orWhereHas('store', function($storeQuery) use ($search) {
                          $storeQuery->where('name', 'like', "%{$search}%");
                      });
                });
            });

        // --- 2. Query 'Pesanan' ---
        $pesananQuery = Pesanan::query()
            // ->with(['...']) // Tambahkan relasi 'Pesanan' jika ada (cth: toko, pembeli)
            ->when($statusFilter, function ($query, $statusTab) {
                // Mapping status filter (dari URL) ke status di tabel 'Pesanan'
                $statusMap = [
                    // 'pending' => [], // Tidak ada status 'pending' di Pesanan
                    'menunggu-pickup' => ['Menunggu Pickup'],
                    // 'diproses' => [], // Tidak ada status 'diproses' di Pesanan
                    'terkirim' => ['Sedang Dikirim'],
                    'selesai' => ['Selesai'], // Asumsi nama statusnya 'Selesai'
                    'batal' => ['Batal'], // Asumsi nama statusnya 'Batal'
                ];
                if (isset($statusMap[$statusTab])) {
                    return $query->whereIn('status_pesanan', $statusMap[$statusTab]);
                }
                return $query;
            })
            ->when($searchQuery, function ($query, $search) {
                // Pencarian untuk tabel 'Pesanan'
                return $query->where(function($q) use ($search) {
                    $q->where('nomor_invoice', 'like', "%{$search}%")
                      ->orWhere('resi', 'like', "%{$search}%")
                      ->orWhere('resi_aktual', 'like', "%{$search}%")
                      ->orWhere('nama_pembeli', 'like', "%{$search}%")
                      ->orWhere('telepon_pembeli', 'like', "%{$search}%")
                      ->orWhere('receiver_name', 'like', "%{$search}%")
                      ->orWhere('receiver_phone', 'like', "%{$search}%")
                      ->orWhere('sender_name', 'like', "%{$search}%");
                });
            });

        // --- 3. Eksekusi Query dan Standarisasi Tanggal ---
        $orders = $orderQuery->get();
        $pesanans = $pesananQuery->get();

        // Standarisasi 'created_at' untuk sorting
        // Blade file Anda sudah menangani ini untuk tampilan, tapi kita butuh untuk sorting
        $standardizedPesanans = $pesanans->map(function ($pesanan) {
            // Gunakan 'created_at' jika ada, jika tidak, gunakan 'tanggal_pesanan'
            $pesanan->created_at = $pesanan->created_at ?? $pesanan->tanggal_pesanan;
            return $pesanan;
        });

        // --- 4. Gabungkan dan Urutkan ---
        $merged = $orders->merge($standardizedPesanans);
        $sorted = $merged->sortByDesc('created_at'); // Urutkan berdasarkan tanggal (terbaru dulu)

        // --- 5. Pagination Manual ---
        $currentPage = Paginator::resolveCurrentPage('page'); // Ambil halaman saat ini
        // "Iris" koleksi yang sudah diurutkan sesuai halaman
        $currentPageItems = $sorted->slice(($currentPage - 1) * $perPage, $perPage);

        // Buat objek Paginator baru
        $paginatedItems = new LengthAwarePaginator(
            $currentPageItems, // Item untuk halaman ini
            $sorted->count(),  // Total semua item (untuk menghitung total halaman)
            $perPage,          // Item per halaman
            $currentPage,      // Halaman saat ini
            [
                'path' => Paginator::resolveCurrentPath(), // URL dasar
                'query' => $request->query() // <-- PENTING: Meneruskan filter (status, search) ke link pagination
            ]
        );

        // 6. Kirim data hasil pagination ($paginatedItems) ke view
        // Kita tetap mengirimkannya sebagai variabel 'orders'
        return view('admin.orders.index', ['orders' => $paginatedItems]);
    }

    /*
    // Method getData() untuk Yajra DataTables (TIDAK DIGUNAKAN LAGI oleh view index di atas)
    // Bisa dihapus jika tidak dipakai di tempat lain, atau disimpan sebagai referensi.
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            try {
                // ... Logika query DataTables seperti sebelumnya ...
            } catch (Exception $e) {
                // ... Error handling ...
            }
        }
        abort(403, 'Request harus via AJAX.');
    }
    */

    /**
     * Menampilkan halaman detail satu pesanan.
     * PENTING: Saat ini HANYA berfungsi untuk data dari tabel 'orders'.
     *
     * @param  string $invoice Nomor invoice dari URL (misal: /admin/orders/SCK-ABCDEF)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
     public function show(string $invoice)
     {
        try {
            // Cari pesanan berdasarkan 'invoice_number' di tabel 'orders'
            $order = Order::where('invoice_number', $invoice)
                // Eager load relasi yang dibutuhkan di halaman detail
                ->with(['user', 'store', 'items.product', 'items.variant'])
                // Jika tidak ditemukan, lempar exception ModelNotFoundException (akan jadi error 404)
                ->firstOrFail();

            // Tampilkan view 'admin.orders.show' dan kirim data $order
            // PENTING: Anda perlu membuat file view ini: resources/views/admin/orders/show.blade.php
            return view('admin.orders.show', compact('order'));

        } catch (ModelNotFoundException $e) {
            // Tangani jika order tidak ditemukan
            Log::warning("Order detail not found for invoice: " . $invoice . " (Checked 'orders' table only)");
            // Redirect kembali ke halaman daftar dengan pesan error
            return redirect()->route('admin.orders.index')->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan di data "Order".');
        } catch (Exception $e) {
            // Tangani error lainnya
            Log::error('Error showing order detail for ' . $invoice . ': ' . $e->getMessage());
            return redirect()->route('admin.orders.index')->with('error', 'Terjadi kesalahan saat menampilkan detail pesanan.');
        }
     }


     /**
     * Membatalkan pesanan.
     * PENTING: Saat ini HANYA berfungsi untuk data dari tabel 'orders'.
     *
     * @param  string $invoice Nomor invoice dari URL
     * @return \Illuminate\Http\RedirectResponse
     */
     public function cancel(string $invoice)
     {
        try {
            // Cari pesanan berdasarkan 'invoice_number' di tabel 'orders'
            $order = Order::where('invoice_number', $invoice)->firstOrFail();

            // Tentukan status mana saja yang boleh dibatalkan
            $cancellableStatuses = ['pending', 'paid', 'processing']; // Sesuaikan dengan alur bisnis Anda

            // Cek apakah status saat ini ada di dalam array $cancellableStatuses
            if (in_array($order->status, $cancellableStatuses)) {
                // Ubah status order menjadi 'cancelled'
                $order->status = 'cancelled';
                // Anda bisa tambahkan timestamp pembatalan jika ada kolomnya
                // $order->cancelled_at = now();
                $order->save(); // Simpan perubahan ke database

                // TODO: Logika PENTING untuk Mengembalikan Stok Produk/Varian
                // (Lihat controller Anda sebelumnya untuk contoh)
                /*
                Log::info("Attempting to restock items for cancelled order: " . $order->invoice_number);
                foreach ($order->items as $item) {
                    // ... (logika restock) ...
                }
                */

                // Kirim notifikasi pembatalan (opsional)
                // event(new OrderCancelledEvent($order)); // Anda perlu membuat event ini

                // Redirect kembali ke halaman sebelumnya (daftar pesanan) dengan pesan sukses
                return redirect()->back()->with('success', 'Pesanan #' . $invoice . ' berhasil dibatalkan.');
            } else {
                // Jika status tidak memungkinkan untuk dibatalkan
                Log::warning("Attempt to cancel order {$invoice} with non-cancellable status: {$order->status}");
                return redirect()->back()->with('error', 'Pesanan tidak dapat dibatalkan (Status saat ini: ' . ucfirst($order->status) . ').');
            }
        } catch (ModelNotFoundException $e) {
            // Tangani jika order tidak ditemukan
            Log::warning("Attempt to cancel non-existent order: " . $invoice . " (Checked 'orders' table only)");
            return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan di data "Order".');
        } catch (Exception $e) {
            // Tangani error lainnya
            Log::error('Gagal membatalkan order ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat membatalkan pesanan.');
        }
     }

     /**
     * Ekspor Faktur PDF untuk satu pesanan.
     * PENTING: Saat ini HANYA berfungsi untuk data dari tabel 'orders'.
     *
     * @param  string $invoice Nomor invoice dari URL
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
     */
     public function exportInvoice(string $invoice)
     {
        try {
            // Cari order beserta relasi yang dibutuhkan untuk faktur
            $order = Order::where('invoice_number', $invoice)
                            ->with([
                                'user:id_pengguna,nama_lengkap,no_wa,email,address_detail,village,district,regency', // Ambil data user lengkap
                                'store', // Ambil data toko lengkap
                                'items', // Ambil semua item
                                'items.product:id,name', // Hanya nama produk dari item
                                'items.variant:id,product_variant_id,combination_string' // Hanya info varian dari item
                            ])
                            ->firstOrFail(); // Error 404 jika tidak ketemu

            // Siapkan data untuk dikirim ke view Blade PDF
            $data = [
                'order' => $order,
                'title' => 'Faktur ' . $order->invoice_number // Judul dokumen PDF
            ];

            // PENTING: Buat file view Blade di:
            // resources/views/admin/orders/invoice_pdf.blade.php
            $pdf = Pdf::loadView('admin.orders.invoice_pdf', $data);
            // ->setPaper('a4', 'portrait');

            // Buat nama file PDF yang akan diunduh
            $filename = 'Faktur-' . $order->invoice_number . '.pdf';
            // Unduh file PDF (browser akan menampilkan dialog save/open)
            return $pdf->download($filename);

        } catch (ModelNotFoundException $e) {
            Log::warning("Attempt to export invoice for non-existent order: " . $invoice . " (Checked 'orders' table only)");
            return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan di data "Order".');
        } catch (\ErrorException $e) {
             // Tangani error jika view PDF belum dibuat
            if (str_contains($e->getMessage(), 'View [admin.orders.invoice_pdf] not found')) {
                 Log::error('PDF View Missing: resources/views/admin/orders/invoice_pdf.blade.php');
                return redirect()->back()->with('error', 'Gagal membuat PDF: Template faktur (invoice_pdf.blade.php) belum dibuat.');
            }
            // Tangani error view lainnya
            Log::error('Error rendering PDF view for invoice ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat tampilan PDF faktur.');
        } catch (Exception $e) {
            // Tangani error DomPDF atau error lainnya
            Log::error('Gagal membuat PDF faktur ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF faktur: ' . $e->getMessage());
        }
     }

     /**
     * Cetak Label Thermal PDF untuk satu pesanan.
     * PENTING: Saat ini HANYA berfungsi untuk data dari tabel 'orders'.
     *
     * @param  string $invoice Nomor invoice dari URL
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
     */
     public function printThermal(string $invoice)
     {
        try {
            // Cari order beserta relasi yang dibutuhkan untuk label
            $order = Order::where('invoice_number', $invoice)
                            ->with(['user', 'store', 'items.product:id,name,weight,length,width,height']) // Ambil data produk yg relevan
                            ->firstOrFail(); // Error 404 jika tidak ketemu

            // Ambil detail pengiriman dari shipping_method
            $shippingInfo = \App\Helpers\ShippingHelper::parseShippingMethod($order->shipping_method); // Gunakan helper jika ada

            // Siapkan data untuk view Blade PDF
            $data = [
                'order' => $order,
                'shippingInfo' => $shippingInfo, // Kirim info pengiriman
                'title' => 'Label ' . $order->invoice_number // Judul dokumen
            ];

            // PENTING: Buat file view Blade di:
            // resources/views/admin/orders/thermal_pdf.blade.php
            
            // Atur ukuran kertas custom untuk thermal (contoh: 80mm x 100mm)
            $widthInMm = 80;
            $heightInMm = 100; // Sesuaikan tinggi label Anda
            $customPaper = [0, 0, ($widthInMm * 2.83465), ($heightInMm * 2.83465)];

            // Load view dan set ukuran kertas custom
            $pdf = Pdf::loadView('admin.orders.thermal_pdf', $data)
                        // Set margin ke 0 jika perlu
                        ->setOption(['dpi' => 150, 'defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
                        ->setPaper($customPaper, 'portrait');

            // Buat nama file
            $filename = 'Label-' . $order->invoice_number . '.pdf';
            // Tampilkan PDF di browser (stream) agar bisa langsung di-print dari browser
            return $pdf->stream($filename);

        } catch (ModelNotFoundException $e) {
            Log::warning("Attempt to print thermal for non-existent order: " . $invoice . " (Checked 'orders' table only)");
            return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan di data "Order".');
         } catch (\ErrorException $e) {
             // Tangani error jika view PDF belum dibuat
            if (str_contains($e->getMessage(), 'View [admin.orders.thermal_pdf] not found')) {
                 Log::error('PDF View Missing: resources/views/admin/orders/thermal_pdf.blade.php');
                return redirect()->back()->with('error', 'Gagal membuat PDF: Template label (thermal_pdf.blade.php) belum dibuat.');
            }
            Log::error('Error rendering PDF view for thermal ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat tampilan PDF label.');
        } catch (Exception $e) {
            // Tangani error DomPDF atau error lainnya
            Log::error('Gagal membuat PDF thermal ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF thermal: ' . $e->getMessage());
        }
     }


     /**
     * Ekspor Laporan Penjualan PDF (dengan filter tanggal).
     * PENTING: Saat ini HANYA berfungsi untuk data dari tabel 'orders'.
     *
     * @param  \Illuminate\Http\Request  $request Object request berisi 'start_date' dan 'end_date'
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
     */
     public function exportReport(Request $request)
     {
        try {
            // Validasi input tanggal dari request (form di modal)
            $validated = $request->validate([
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            ], [
                // Pesan error custom (opsional)
                'end_date.after_or_equal' => 'Tanggal Selesai harus setelah atau sama dengan Tanggal Mulai.'
            ]);

            // Ambil tanggal dari input, atau set default ke bulan ini jika tidak diisi
            $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

            // Tentukan status pesanan yang dianggap "masuk" dalam laporan
            $statusesToInclude = ['paid', 'processing', 'shipped', 'delivered', 'completed'];

            // Ambil data pesanan sesuai filter tanggal dan status DARI 'orders'
            $orders = Order::with(['user:id_pengguna,nama_lengkap', 'store:id,name', 'items']) // Eager load relasi secukupnya
                ->whereIn('status', $statusesToInclude) // Filter berdasarkan status yang valid
                // Filter berdasarkan rentang tanggal 'created_at' (inklusif)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->orderBy('created_at', 'asc') // Urutkan dari pesanan terlama dalam rentang
                ->get(); // Ambil semua data yang cocok (koleksi)
            
            // TODO: Jika laporan juga harus mencakup 'Pesanan', Anda perlu query 'Pesanan' di sini
            // dan menggabungkannya sebelum menghitung total.

            // Hitung total ringkasan untuk laporan
            $totalRevenue = $orders->sum('total_amount'); // Jumlahkan total semua pesanan
            $totalOrders = $orders->count(); // Hitung jumlah pesanan

            // Siapkan data untuk dikirim ke view Blade PDF
            $data = [
                'orders' => $orders,           // Koleksi data pesanan ('orders' saja)
                'startDate' => Carbon::parse($startDate),      // Objek Carbon untuk format tanggal
                'endDate' => Carbon::parse($endDate),          // Objek Carbon untuk format tanggal
                'totalRevenue' => $totalRevenue, // Total pendapatan
                'totalOrders' => $totalOrders,   // Jumlah total pesanan
                'title' => 'Laporan Penjualan ' . Carbon::parse($startDate)->translatedFormat('d M Y') . ' - ' . Carbon::parse($endDate)->translatedFormat('d M Y') // Judul dinamis
            ];

            // PENTING: Buat file view Blade di:
            // resources/views/admin/orders/report_pdf.blade.php
            $pdf = Pdf::loadView('admin.orders.report_pdf', $data)
                        ->setPaper('a4', 'landscape'); // Atur kertas A4 landscape agar tabel muat

            // Buat nama file PDF yang dinamis
            $filename = 'Laporan-Penjualan-' . $startDate . '-sd-' . $endDate . '.pdf';
            // Unduh laporan PDF
            return $pdf->download($filename);

        } catch (\Illuminate\Validation\ValidationException $e) {
             // Tangani error validasi tanggal
             Log::warning('Validation error on export report: ' . json_encode($e->errors()));
             // Redirect kembali ke halaman index dengan pesan error validasi dan input lama
             return redirect()->route('admin.orders.index')->withErrors($e->errors())->withInput();
        } catch (\ErrorException $e) {
            // Tangani error jika view PDF belum dibuat
            if (str_contains($e->getMessage(), 'View [admin.orders.report_pdf] not found')) {
                 Log::error('PDF View Missing: resources/views/admin/orders/report_pdf.blade.php');
                return redirect()->route('admin.orders.index')->with('error', 'Gagal membuat Laporan PDF: Template laporan (report_pdf.blade.php) belum dibuat.');
            }
            Log::error('Error rendering PDF view for report: ' . $e->getMessage());
            return redirect()->route('admin.orders.index')->with('error', 'Terjadi kesalahan saat membuat tampilan PDF laporan.');
        } catch (Exception $e) {
            // Tangani error DomPDF atau error lainnya
            Log::error('Gagal membuat Laporan PDF: ' . $e->getMessage());
            return redirect()->route('admin.orders.index')->with('error', 'Gagal membuat Laporan PDF: ' . $e->getMessage());
        }
     }
}
