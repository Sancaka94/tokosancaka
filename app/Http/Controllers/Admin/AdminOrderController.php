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
                      ->orWhere('shipping_reference', 'like', "%{$search}%")
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
            // Catatan: Tidak bisa menambahkan ->with() karena Model 'Pesanan'
            // mungkin tidak memiliki relasi 'user', 'store', dll.
            // Data 'Pesanan' diambil apa adanya (langsung dari kolom).
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


    /**
     * Mengubah objek 'Pesanan' agar strukturnya mirip dengan 'Order'
     * sehingga bisa dipakai di view Blade yang sama.
     *
     * @param  Pesanan $pesanan Model Pesanan dari database
     * @return object  Objek standar yang meniru struktur 'Order'
     */
    private function standardizePesanan(Pesanan $pesanan)
    {
        // 1. User (Pembeli/Receiver)
        $user = new \stdClass();
        $user->nama_lengkap = $pesanan->receiver_name ?? $pesanan->nama_pembeli ?? 'N/A';
        $user->no_wa = $pesanan->receiver_phone ?? $pesanan->telepon_pembeli ?? 'N/A';
        $user->email = null; // Tidak ada di 'Pesanan'
        $user->address_detail = $pesanan->receiver_address ?? $pesanan->alamat_pengiriman ?? 'N/A';
        $user->village = $pesanan->receiver_village;
        $user->district = $pesanan->receiver_district;
        $user->regency = $pesanan->receiver_regency;

        // 2. Store (Pengirim/Sender)
        $store = new \stdClass();
        $store->name = $pesanan->sender_name ?? 'N/A';
        $store->address_detail = $pesanan->sender_address ?? 'N/A';
        $store->village = $pesanan->sender_village;
        $store->district = $pesanan->sender_district;
        $store->regency = $pesanan->sender_regency;

        // 3. Items (Mockup 1 item)
        $item = new \stdClass();
        $item->product = new \stdClass();
        $item->product->name = $pesanan->item_description ?? 'Paket';
        $item->product->weight = $pesanan->weight;
        $item->product->length = $pesanan->length;
        $item->product->width = $pesanan->width;
        $item->product->height = $pesanan->height;

        $item->variant = null; // Tidak ada varian di 'Pesanan'
        $item->quantity = 1; // Asumsi 1
        $item->price_per_item = $pesanan->total_harga_barang ?? $pesanan->price; // total_harga_barang atau price
        $item->total_price = $item->price_per_item;

        $items = new Collection([$item]); // Jadikan koleksi

        // 4. Main Order Object
        $order = new \stdClass();
        $order->is_pesanan = true; // Flag
        $order->id = $pesanan->id_pesanan;
        $order->invoice_number = $pesanan->nomor_invoice;
        $order->created_at = $pesanan->created_at ?? $pesanan->tanggal_pesanan;
        $order->shipped_at = $pesanan->shipped_at;
        $order->finished_at = $pesanan->finished_at;

        $order->shipping_method = $pesanan->jasa_ekspedisi_aktual ?? $pesanan->expedition;
        $order->shipping_address = $pesanan->receiver_address ?? $pesanan->alamat_pengiriman;
        $order->shipping_reference = $pesanan->resi_aktual ?? $pesanan->resi;
        $order->payment_method = $pesanan->payment_method;

        // ===== PERBAIKAN LOGIKA BIAYA =====
        $order->total_amount = $pesanan->price ?? 0;

        // Gunakan ShippingHelper (jika ada) untuk parse biaya
        // Asumsi helper ada di App\Helpers\ShippingHelper
        $shippingInfo = \App\Helpers\ShippingHelper::parseShippingMethod($order->shipping_method);
        $order->shipping_cost = $shippingInfo['cost']; // Ambil biaya ongkir akurat dari parser

        // Tentukan Subtotal. Prioritaskan total_harga_barang jika ada.
        if ($pesanan->total_harga_barang !== null) {
            $order->subtotal = $pesanan->total_harga_barang;
        } else {
            // Jika tidak ada, hitung (Total - Ongkir)
            // Ini akan disesuaikan lagi jika ada biaya COD
            $order->subtotal = $order->total_amount - $order->shipping_cost;
        }
        
        $order->cod_fee = 0; // Default 0
        if (strtoupper($pesanan->payment_method) == 'CODBARANG' || strtoupper($pesanan->payment_method) == 'COD') {
             // Biaya COD = Total - Subtotal - Ongkir
             // (Gunakan $pesanan->total_harga_barang untuk subtotal jika ada, jika tidak, $order->subtotal)
             $subtotalForCalc = $pesanan->total_harga_barang ?? $order->subtotal;
             $calculated_cod_fee = $order->total_amount - $subtotalForCalc - $order->shipping_cost;
             
             $order->cod_fee = max(0, $calculated_cod_fee); // Pastikan tidak negatif
        }

        // Koreksi terakhir untuk subtotal jika 'total_harga_barang' tidak ada
        if ($pesanan->total_harga_barang === null) {
            $order->subtotal = $order->total_amount - $order->shipping_cost - $order->cod_fee;
            $order->subtotal = max(0, $order->subtotal); // Pastikan tidak negatif
        }
        // ===== AKHIR PERBAIKAN BIAYA =====

        // Relasi
        $order->user = $user;
        $order->store = $store;
        $order->items = $items;

        // Status
        $order->status = $pesanan->status_pesanan; // Tampilkan status asli

        return $order;
    }


    /**
     * Menampilkan halaman detail satu pesanan.
     * DIPERBARUI: Mencoba mencari di 'orders', lalu 'Pesanan'.
     *
     * @param  string $invoice Nomor invoice dari URL (misal: /admin/orders/SCK-ABCDEF)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
     public function show(string $invoice)
     {
        try {
            // Coba cari di 'orders' dulu
            $order = Order::where('invoice_number', $invoice)
                ->with(['user', 'store', 'items.product', 'items.variant'])
                ->first();

            if ($order) {
                $order->is_pesanan = false; // Tambahkan flag
                // Ditemukan di 'orders', tampilkan view 'orders.show'
                // PENTING: Anda perlu membuat file view ini: resources/views/admin/orders/show.blade.php
                return view('admin.orders.show', compact('order'));
            }

            // Jika tidak ada di 'orders', coba cari di 'Pesanan'
            $pesanan = Pesanan::where('nomor_invoice', $invoice)->firstOrFail();
            
            // Ditemukan di 'Pesanan', standarisasi datanya
            $order = $this->standardizePesanan($pesanan);
            
            // Tampilkan view 'orders.show' dengan data 'Pesanan' yang sudah distandarisasi
            return view('admin.orders.show', compact('order'));


        } catch (ModelNotFoundException $e) {
            // Tangani jika tidak ditemukan di *kedua* tabel
            Log::warning("Detail not found for invoice: " . $invoice . " (Checked 'orders' and 'Pesanan')");
            return redirect()->route('admin.orders.index')->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
        } catch (Exception $e) {
            // Tangani error lainnya
            Log::error('Error showing detail for ' . $invoice . ': ' . $e->getMessage());
            return redirect()->route('admin.orders.index')->with('error', 'Terjadi kesalahan saat menampilkan detail pesanan.');
        }
     }


     /**
     * Membatalkan pesanan.
     * DIPERBARUI: Mencoba mencari di 'orders', lalu 'Pesanan'.
     *
     * @param  string $invoice Nomor invoice dari URL
     * @return \Illuminate\Http\RedirectResponse
     */
     public function cancel(string $invoice)
     {
        try {
            // Coba cari di 'orders' dulu
            $order = Order::where('invoice_number', $invoice)->first();

            if ($order) {
                // --- LOGIKA UNTUK 'Order' ---
                $cancellableStatuses = ['pending', 'paid', 'processing']; // Sesuaikan dengan alur bisnis Anda
                if (in_array($order->status, $cancellableStatuses)) {
                    $order->status = 'cancelled';
                    $order->save();
                    // TODO: Logika PENTING untuk Mengembalikan Stok
                    return redirect()->back()->with('success', 'Pesanan (Order) #' . $invoice . ' berhasil dibatalkan.');
                } else {
                    Log::warning("Attempt to cancel order {$invoice} with non-cancellable status: {$order->status}");
                    return redirect()->back()->with('error', 'Pesanan (Order) tidak dapat dibatalkan (Status: ' . ucfirst($order->status) . ').');
                }
            }

            // Jika tidak ada di 'orders', coba cari di 'Pesanan'
            $pesanan = Pesanan::where('nomor_invoice', $invoice)->firstOrFail();

            // --- LOGIKA UNTUK 'Pesanan' ---
            $cancellableStatusesPesanan = ['Menunggu Pickup']; // Sesuaikan status 'Pesanan'
            if (in_array($pesanan->status_pesanan, $cancellableStatusesPesanan)) {
                $pesanan->status_pesanan = 'Batal'; // Sesuaikan status 'Batal'
                $pesanan->save();
                // TODO: Logika PENTING untuk Mengembalikan Stok (jika perlu)
                return redirect()->back()->with('success', 'Pesanan (Pesanan) #' . $invoice . ' berhasil dibatalkan.');
            } else {
                 Log::warning("Attempt to cancel 'Pesanan' {$invoice} with non-cancellable status: {$pesanan->status_pesanan}");
                 return redirect()->back()->with('error', 'Pesanan (Pesanan) tidak dapat dibatalkan (Status: ' . $pesanan->status_pesanan . ').');
            }

        } catch (ModelNotFoundException $e) {
            // Tangani jika tidak ditemukan di *kedua* tabel
            Log::warning("Attempt to cancel non-existent item: " . $invoice . " (Checked 'orders' and 'Pesanan')");
            return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
        } catch (Exception $e) {
            // Tangani error lainnya
            Log::error('Gagal membatalkan item ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat membatalkan pesanan.');
        }
     }

     /**
     * Ekspor Faktur PDF untuk satu pesanan.
     * DIPERBARUI: Bisa untuk 'orders' dan 'Pesanan'.
     *
     * @param  string $invoice Nomor invoice dari URL
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
     */
     public function exportInvoice(string $invoice)
     {
        try {
            // Coba cari di 'orders' dulu
            $order = Order::where('invoice_number', $invoice)
                            ->with([
                                'user:id_pengguna,nama_lengkap,no_wa,email,address_detail,village,district,regency',
                                'store', 'items', 'items.product:id,name',
                                'items.variant:id,product_variant_id,combination_string'
                            ])
                            ->first();
            
            if ($order) {
                $order->is_pesanan = false; // Tambah flag
            } else {
                // Jika tidak ada, cari di 'Pesanan'
                $pesanan = Pesanan::where('nomor_invoice', $invoice)->firstOrFail();
                // Standarisasi data 'Pesanan'
                $order = $this->standardizePesanan($pesanan);
            }

            // Lanjutkan dengan $order (baik dari 'Order' asli atau 'Pesanan' standar)
            $data = [
                'order' => $order,
                'title' => 'Faktur ' . $order->invoice_number
            ];

            // PENTING: resources/views/admin/orders/invoice_pdf.blade.php
            // Pastikan view ini bisa menangani $order->is_pesanan
            $pdf = Pdf::loadView('admin.orders.invoice_pdf', $data);
            $filename = 'Faktur-' . $order->invoice_number . '.pdf';
            return $pdf->download($filename);

        } catch (ModelNotFoundException $e) {
            Log::warning("Attempt to export invoice for non-existent item: " . $invoice . " (Checked 'orders' and 'Pesanan')");
            return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
        } catch (\ErrorException $e) {
            if (str_contains($e->getMessage(), 'View [admin.orders.invoice_pdf] not found')) {
                 Log::error('PDF View Missing: resources/views/admin/orders/invoice_pdf.blade.php');
                return redirect()->back()->with('error', 'Gagal membuat PDF: Template faktur (invoice_pdf.blade.php) belum dibuat.');
            }
            Log::error('Error rendering PDF view for invoice ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat tampilan PDF faktur.');
        } catch (Exception $e) {
            Log::error('Gagal membuat PDF faktur ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF faktur: ' . $e->getMessage());
        }
     }

     /**
     * Cetak Label Thermal PDF untuk satu pesanan.
     * DIPERBARUI: Bisa untuk 'orders' dan 'Pesanan'.
     *
     * @param  string $invoice Nomor invoice dari URL
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
     */
     public function printThermal(string $invoice)
     {
        try {
            // Coba cari di 'orders' dulu
            $order = Order::where('invoice_number', $invoice)
                            ->with(['user', 'store', 'items.product:id,name,weight,length,width,height'])
                            ->first();
            
            if ($order) {
                 $order->is_pesanan = false; // Tambah flag
            } else {
                // Jika tidak ada, cari di 'Pesanan'
                $pesanan = Pesanan::where('nomor_invoice', $invoice)->firstOrFail();
                // Standarisasi data 'Pesanan'
                $order = $this->standardizePesanan($pesanan);
            }

            // Lanjutkan dengan $order (baik dari 'Order' asli atau 'Pesanan' standar)
            $shippingInfo = \App\Helpers\ShippingHelper::parseShippingMethod($order->shipping_method);

            $data = [
                'order' => $order,
                'shippingInfo' => $shippingInfo,
                'title' => 'Label ' . $order->invoice_number
            ];

            // PENTING: resources/views/admin/orders/thermal_pdf.blade.php
            // Pastikan view ini bisa menangani $order->is_pesanan
            $widthInMm = 80;
            $heightInMm = 100;
            $customPaper = [0, 0, ($widthInMm * 2.83465), ($heightInMm * 2.83465)];

            $pdf = Pdf::loadView('admin.orders.thermal_pdf', $data)
                        ->setOption(['dpi' => 150, 'defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
                        ->setPaper($customPaper, 'portrait');

            $filename = 'Label-' . $order->invoice_number . '.pdf';
            return $pdf->stream($filename);

        } catch (ModelNotFoundException $e) {
            Log::warning("Attempt to print thermal for non-existent item: " . $invoice . " (Checked 'orders' and 'Pesanan')");
            return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
         } catch (\ErrorException $e) {
            if (str_contains($e->getMessage(), 'View [admin.orders.thermal_pdf] not found')) {
                 Log::error('PDF View Missing: resources/views/admin/orders/thermal_pdf.blade.php');
                return redirect()->back()->with('error', 'Gagal membuat PDF: Template label (thermal_pdf.blade.php) belum dibuat.');
            }
            Log::error('Error rendering PDF view for thermal ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat tampilan PDF label.');
        } catch (Exception $e) {
            Log::error('Gagal membuat PDF thermal ' . $invoice . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF thermal: ' . $e->getMessage());
        }
     }


     /**
     * Ekspor Laporan Penjualan PDF (dengan filter tanggal).
     * DIPERBARUI: Menggabungkan data 'orders' dan 'Pesanan' untuk laporan.
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
                'end_date.after_or_equal' => 'Tanggal Selesai harus setelah atau sama dengan Tanggal Mulai.'
            ]);

            $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

            // --- Ambil Data 'Orders' ---
            $statusesToIncludeOrders = ['paid', 'processing', 'shipped', 'delivered', 'completed'];
            $orders = Order::with(['user:id_pengguna,nama_lengkap'])
                ->whereIn('status', $statusesToIncludeOrders)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();
            
            // --- Ambil Data 'Pesanan' ---
            $statusesToIncludePesanan = ['Menunggu Pickup', 'Sedang Dikirim', 'Selesai'];
             $pesanans = Pesanan::query() // Ganti 'user' dengan relasi yang sesuai di 'Pesanan' jika ada
                ->whereIn('status_pesanan', $statusesToIncludePesanan)
                ->where(function($query) use ($startDate, $endDate) {
                    // Filter tanggal 'Pesanan', sesuaikan nama kolom jika beda
                    $query->whereBetween('tanggal_pesanan', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                          ->orWhereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                })
                ->get();

            // --- Gabungkan Data Untuk Laporan ---
            // Standarisasi data 'Pesanan' agar mirip 'Order' untuk view laporan
            $standardizedPesanans = $pesanans->map(function ($item) {
                // Gunakan helper standarisasi, tapi hanya ambil data yg perlu u/ laporan
                $stdOrder = $this->standardizePesanan($item);
                
                return (object) [ // Ubah jadi objek standar agar mirip Eloquent
                    'invoice_number' => $stdOrder->invoice_number,
                    'created_at' => $stdOrder->created_at,
                    'user' => (object) ['nama_lengkap' => $stdOrder->user->nama_lengkap],
                    'total_amount' => $stdOrder->total_amount, // 'total_amount' dari standarisasi
                    'status' => $stdOrder->status,
                    'is_pesanan' => true // Tambahkan flag
                ];
            });

            // Standarisasi data 'Order'
             $standardizedOrders = $orders->map(function ($item) {
                $item->is_pesanan = false; // Tambahkan flag
                return $item;
            });

            $mergedReportItems = $standardizedOrders->merge($standardizedPesanans);
            $sortedReportItems = $mergedReportItems->sortBy('created_at'); // Urutkan asc

            // Hitung total ringkasan untuk laporan
            $totalRevenue = $sortedReportItems->sum('total_amount'); // Jumlahkan total semua
            $totalOrders = $sortedReportItems->count(); // Hitung jumlah semua

            $data = [
                'orders' => $sortedReportItems, // Kirim data yang sudah digabung & diurutkan
                'startDate' => Carbon::parse($startDate),
                'endDate' => Carbon::parse($endDate),
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $totalOrders,
                'title' => 'Laporan Penjualan ' . Carbon::parse($startDate)->translatedFormat('d M Y') . ' - ' . Carbon::parse($endDate)->translatedFormat('d M Y')
            ];

            // PENTING: resources/views/admin/orders/report_pdf.blade.php
            // Pastikan view report_pdf.blade.php bisa menangani data yg distandarisasi
            $pdf = Pdf::loadView('admin.orders.report_pdf', $data)
                        ->setPaper('a4', 'landscape');

            $filename = 'Laporan-Penjualan-' . $startDate . '-sd-' . $endDate . '.pdf';
            return $pdf->download($filename);

        } catch (\Illuminate\Validation\ValidationException $e) {
             Log::warning('Validation error on export report: ' . json_encode($e->errors()));
             return redirect()->route('admin.orders.index')->withErrors($e->errors())->withInput();
        } catch (\ErrorException $e) {
            if (str_contains($e->getMessage(), 'View [admin.orders.report_pdf] not found')) {
                 Log::error('PDF View Missing: resources/views/admin/orders/report_pdf.blade.php');
                return redirect()->route('admin.orders.index')->with('error', 'Gagal membuat Laporan PDF: Template laporan (report_pdf.blade.php) belum dibuat.');
            }
            Log::error('Error rendering PDF view for report: ' . $e->getMessage());
            return redirect()->route('admin.orders.index')->with('error', 'Terjadi kesalahan saat membuat tampilan PDF laporan.');
        } catch (Exception $e) {
            Log::error('Gagal membuat Laporan PDF: ' . $e->getMessage());
            return redirect()->route('admin.orders.index')->with('error', 'Gagal membuat Laporan PDF: ' . $e->getMessage());
        }
     }
}

