<?php

// Tentukan namespace untuk controller ini (lokasi file dalam struktur folder)
namespace App\Http\Controllers\Admin;

// Import class-class (dependency) yang dibutuhkan dari Laravel dan library lain
use App\Http\Controllers\Controller; // Controller dasar Laravel
use Illuminate\Http\Request; // Class untuk mengelola data request HTTP (query string, form input)
use App\Models\Order; // Model Eloquent untuk berinteraksi dengan tabel 'orders'
use App\Models\User; // Model User (digunakan untuk relasi dan pencarian)
// use Yajra\DataTables\Facades\DataTables; // Tidak digunakan lagi untuk view index, tapi mungkin berguna di tempat lain
use Illuminate\Support\Facades\Log; // Fasilitas logging Laravel untuk mencatat error atau informasi
use Exception; // Class Exception dasar PHP untuk menangani error umum
use Barryvdh\DomPDF\Facade\Pdf; // Facade untuk library DomPDF (generate PDF dari HTML)
use Carbon\Carbon; // Library untuk mempermudah manipulasi tanggal dan waktu
use Illuminate\Database\Eloquent\ModelNotFoundException; // Exception khusus saat query findOrFail() gagal

// Deklarasi class controller, mewarisi (extends) Controller dasar Laravel
class AdminOrderController extends Controller
{
    /**
     * Menampilkan halaman utama Data Pesanan.
     * Method ini mengambil data pesanan dengan filter, pencarian, dan pagination,
     * lalu mengirimkannya ke view Blade untuk ditampilkan sebagai tabel HTML biasa.
     *
     * @param  \Illuminate\Http\Request  $request Object request yang berisi query string (cth: ?status=pending&search=INV)
     * @return \Illuminate\View\View      Objek view Blade 'admin.orders.index' beserta data orders
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

        // 1. Mulai query ke model Order
        $query = Order::query() // Sama dengan Order:: , tapi lebih eksplisit
            // 2. Eager Loading Relasi: Ambil data terkait (user, store, items, dll.)
            //    dalam query awal untuk menghindari N+1 problem (lebih efisien).
            ->with([
                'user:id_pengguna,nama_lengkap,no_wa,village,district,regency', // Hanya ambil kolom yang dibutuhkan dari user
                'store:id,name,address_detail,village,district,regency', // Hanya ambil kolom yang dibutuhkan dari store
                'items:id,order_id,product_id,product_variant_id,quantity', // Hanya ambil kolom yang dibutuhkan dari items
                'items.product:id,name,weight,length,width,height', // Dari items, ambil produk terkait (hanya kolom yg perlu)
                'items.variant:id,product_variant_id,combination_string,sku_code' // Dari items, ambil varian terkait (hanya kolom yg perlu)
            ])
            // 3. Terapkan Filter Status jika parameter 'status' ada di URL
            ->when($statusFilter, function ($query, $statusTab) {
                // Mapping antara nilai 'status' dari URL/Tab ke nilai 'status' di database
                // Sesuaikan array ini jika nilai status di DB Anda berbeda!
                $statusMap = [
                    'pending' => ['pending'],                         // Tab Menunggu Bayar
                    'menunggu-pickup' => ['paid', 'processing'],   // Tab Menunggu Pickup (dibayar atau diproses KiriminAja)
                    'diproses' => ['shipping'],                    // Tab Diproses (jika Anda pakai status 'shipping')
                    'terkirim' => ['delivered'],                   // Tab Terkirim (jika Anda pakai status 'delivered')
                    'selesai' => ['completed'],                    // Tab Selesai (jika Anda pakai status 'completed')
                    'batal' => ['cancelled', 'failed', 'rejected'],// Tab Batal (gabungan semua status gagal/batal)
                ];
                // Jika nilai $statusTab ada di dalam $statusMap sebagai key
                if (isset($statusMap[$statusTab])) {
                    // Terapkan filter 'whereIn' (mencari status yang ada dalam array)
                    return $query->whereIn('status', $statusMap[$statusTab]);
                }
                // Jika status tidak dikenal atau kosong (tab 'Semua'), jangan filter
                return $query;
            })
            // 4. Terapkan Filter Pencarian jika parameter 'search' ada di URL
            ->when($searchQuery, function ($query, $search) {
                // Menggunakan closure 'where' agar kondisi OR tergabung dengan benar
                return $query->where(function($q) use ($search) {
                    // Cari di kolom 'invoice_number'
                    $q->where('invoice_number', 'like', "%{$search}%")
                      // Cari di kolom 'tracking_number' (resi)
                      ->orWhere('tracking_number', 'like', "%{$search}%")
                      // ->orWhere('resi', 'like', "%{$search}%") // Uncomment jika nama kolom Anda 'resi'
                      // Cari di relasi 'user' (pembeli/penerima)
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          // Cari berdasarkan 'nama_lengkap' ATAU 'no_wa'
                          $userQuery->where('nama_lengkap', 'like', "%{$search}%")
                                    ->orWhere('no_wa', 'like', "%{$search}%");
                      })
                      // Cari di relasi 'store' (penjual/pengirim)
                      ->orWhereHas('store', function($storeQuery) use ($search) {
                          // Cari berdasarkan nama toko
                          $storeQuery->where('name', 'like', "%{$search}%");
                      });
                      // Bisa tambahkan orWhereHas lagi untuk cari di nama produk, dll.
                });
            })
            // 5. Urutkan hasil berdasarkan tanggal pembuatan (created_at), terbaru di atas
            ->orderBy('created_at', 'desc');

        // 6. Eksekusi query dan ambil hasil dengan pagination
        //    paginate() otomatis menghitung total data dan halaman
        //    withQueryString() memastikan parameter filter (status, search) tetap ada di link pagination
        $orders = $query->paginate($perPage)->withQueryString();

        // 7. Kirim data hasil pagination ($orders) ke view 'admin.orders.index'
        return view('admin.orders.index', compact('orders'));
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
     * Dipanggil saat link 'Detail' (ikon mata) di tabel diklik.
     *
     * @param  string $invoice Nomor invoice dari URL (misal: /admin/orders/SCK-ABCDEF)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
     public function show(string $invoice)
     {
         try {
             // Cari pesanan berdasarkan 'invoice_number'
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
             Log::warning("Order detail not found for invoice: " . $invoice);
             // Redirect kembali ke halaman daftar dengan pesan error
             return redirect()->route('admin.orders.index')->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
         } catch (Exception $e) {
             // Tangani error lainnya
             Log::error('Error showing order detail for ' . $invoice . ': ' . $e->getMessage());
             return redirect()->route('admin.orders.index')->with('error', 'Terjadi kesalahan saat menampilkan detail pesanan.');
         }
     }


     /**
      * Membatalkan pesanan.
      * Dipanggil saat form 'Cancel' (ikon sampah) di tabel disubmit (method PATCH).
      *
      * @param  string $invoice Nomor invoice dari URL
      * @return \Illuminate\Http\RedirectResponse
      */
     public function cancel(string $invoice)
     {
         try {
             // Cari pesanan berdasarkan 'invoice_number'
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
                 // Jika Anda mengurangi stok saat order DIBUAT atau DIBAYAR,
                 // Anda *harus* mengembalikannya di sini agar stok akurat.
                 // Uncomment dan sesuaikan logika di bawah ini jika diperlukan.
                 /*
                 Log::info("Attempting to restock items for cancelled order: " . $order->invoice_number);
                 foreach ($order->items as $item) {
                     try {
                         if ($item->product_variant_id && $item->load('variant')->variant) { // Load relasi jika belum
                             $item->variant->increment('stock', $item->quantity);
                             Log::info("Restocked variant ID {$item->product_variant_id} by {$item->quantity}");
                         } elseif ($item->load('product')->product) { // Load relasi jika belum
                             $item->product->increment('stock', $item->quantity);
                             Log::info("Restocked product ID {$item->product_id} by {$item->quantity}");
                         } else {
                              Log::warning("Could not restock item ID {$item->id}: Product/Variant relation missing.");
                         }
                     } catch (Exception $stockError) {
                         Log::error("Error restocking item ID {$item->id} for order {$order->invoice_number}: " . $stockError->getMessage());
                     }
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
      * Dipanggil saat link 'Faktur PDF' (ikon file-pdf) diklik.
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
                                 'user:id,nama_lengkap,no_wa,email,address_detail,village,district,regency', // Ambil data user lengkap
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
                 // Anda bisa tambahkan data lain, misal data perusahaan dari settings
                 // 'company_name' => Setting::get('company_name'),
             ];

             // PENTING: Buat file view Blade di:
             // resources/views/admin/orders/invoice_pdf.blade.php
             // Desain tampilan faktur menggunakan HTML dan CSS di file ini.
             $pdf = Pdf::loadView('admin.orders.invoice_pdf', $data);
             // Atur orientasi kertas jika perlu (default portrait)
             // ->setPaper('a4', 'portrait');

             // Buat nama file PDF yang akan diunduh
             $filename = 'Faktur-' . $order->invoice_number . '.pdf';
             // Unduh file PDF (browser akan menampilkan dialog save/open)
             return $pdf->download($filename);

         } catch (ModelNotFoundException $e) {
             Log::warning("Attempt to export invoice for non-existent order: " . $invoice);
             return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
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
      * Dipanggil saat link 'Cetak Thermal' (ikon print) diklik.
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
                 // Anda mungkin perlu generate string barcode di sini dan mengirimkannya ke view
                 // 'barcode_string' => generateBarcodeString($order->tracking_number ?? $order->invoice_number),
             ];

             // PENTING: Buat file view Blade di:
             // resources/views/admin/orders/thermal_pdf.blade.php
             // Desain view ini HANYA dengan HTML dan CSS sederhana agar cocok untuk printer thermal.
             // Atur font, ukuran, margin seminimal mungkin. Gunakan tabel jika perlu.

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
             Log::warning("Attempt to print thermal for non-existent order: " . $invoice);
             return redirect()->back()->with('error', 'Pesanan dengan invoice ' . $invoice . ' tidak ditemukan.');
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
      * Dipanggil saat form di modal 'Export Laporan' disubmit.
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
             // Sesuaikan array ini dengan logika bisnis Anda!
             $statusesToInclude = ['paid', 'processing', 'shipped', 'delivered', 'completed'];

             // Ambil data pesanan sesuai filter tanggal dan status
             $orders = Order::with(['user:id,nama_lengkap', 'store:id,name', 'items']) // Eager load relasi secukupnya
                 ->whereIn('status', $statusesToInclude) // Filter berdasarkan status yang valid
                 // Filter berdasarkan rentang tanggal 'created_at' (inklusif)
                 ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                 ->orderBy('created_at', 'asc') // Urutkan dari pesanan terlama dalam rentang
                 ->get(); // Ambil semua data yang cocok (koleksi)

             // Hitung total ringkasan untuk laporan
             $totalRevenue = $orders->sum('total_amount'); // Jumlahkan total semua pesanan
             $totalOrders = $orders->count(); // Hitung jumlah pesanan

             // Siapkan data untuk dikirim ke view Blade PDF
             $data = [
                 'orders' => $orders,           // Koleksi data pesanan
                 'startDate' => Carbon::parse($startDate),     // Objek Carbon untuk format tanggal
                 'endDate' => Carbon::parse($endDate),         // Objek Carbon untuk format tanggal
                 'totalRevenue' => $totalRevenue, // Total pendapatan
                 'totalOrders' => $totalOrders,   // Jumlah total pesanan
                 'title' => 'Laporan Penjualan ' . Carbon::parse($startDate)->translatedFormat('d M Y') . ' - ' . Carbon::parse($endDate)->translatedFormat('d M Y') // Judul dinamis
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

     public function start(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return redirect()->back()->with('error', 'User tidak ditemukan.');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->back()->with('error', 'Data penerima tidak ditemukan.');
        }

        // Tampilkan halaman chat admin dengan user tertentu
        return view('admin.chat.start', compact('user'));
    }
}

