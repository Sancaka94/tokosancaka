<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Pesanan;
use App\Models\PesananAutokirim; // <-- PASTIKAN IMPORT INI
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->query('status');
        $searchQuery = $request->query('search');
        $tipeFilter = $request->query('tipe');
        $perPage = $request->query('per_page', 15); // Disamakan 15

        // =========================================================================
        // BAGIAN 1: QUERY DATA (Orders, Pesanan, Autokirim)
        // =========================================================================

        // --- 1. Query 'Orders' (Web/Otomatis) ---
        $orderQuery = Order::with(['user', 'store', 'items.product', 'items.variant'])
            ->when($statusFilter, function ($query, $statusTab) {
                $statusMap = [
                    'pending' => ['pending'],
                    'menunggu-pickup' => ['paid', 'processing'],
                    'diproses' => ['shipping'],
                    'terkirim' => ['delivered'],
                    'selesai' => ['completed'],
                    'batal' => ['cancelled', 'failed', 'rejected'],
                ];
                if (isset($statusMap[$statusTab])) return $query->whereIn('status', $statusMap[$statusTab]);
                return $query;
            })
            ->when($searchQuery, function ($query, $search) {
                return $query->where('invoice_number', 'like', "%{$search}%")->orWhere('shipping_reference', 'like', "%{$search}%");
            });

        // --- 2. Query 'Pesanan' (Manual) ---
        $pesananQuery = Pesanan::when($statusFilter, function ($query, $statusTab) {
                $statusMap = [
                    'menunggu-pickup' => ['Menunggu Pickup'],
                    'terkirim' => ['Sedang Dikirim'],
                    'selesai' => ['Selesai'],
                    'batal' => ['Batal'],
                ];
                if (isset($statusMap[$statusTab])) return $query->whereIn('status_pesanan', $statusMap[$statusTab]);
                return $query;
            })
            ->when($searchQuery, function ($query, $search) {
                return $query->where('nomor_invoice', 'like', "%{$search}%")->orWhere('resi', 'like', "%{$search}%");
            });

        // --- 3. Query 'Pesanan Autokirim' ---
        $autokirimQuery = PesananAutokirim::when($statusFilter, function ($query, $statusTab) {
                $statusMap = [
                    'pending' => ['waiting_payment', 'menunggu_pembayaran'],
                    'menunggu-pickup' => ['booking_created'],
                    'terkirim' => ['on_delivery', 'shipping'], // Sesuaikan dengan status API autokirim
                    'selesai' => ['completed', 'terkirim', 'selesai', 'sukses'],
                    'batal' => ['batal', 'gagal', 'cancelled'],
                ];
                if (isset($statusMap[$statusTab])) return $query->whereIn('status', $statusMap[$statusTab]);
                return $query;
            })
            ->when($searchQuery, function ($query, $search) {
                return $query->where('order_id', 'like', "%{$search}%")->orWhere('awb_number', 'like', "%{$search}%");
            });

        // --- Eksekusi Query & Terapkan Filter Tipe ---
        $orders = collect();
        if (!$tipeFilter || $tipeFilter === 'marketplace') {
            $orders = $orderQuery->get()->map(function ($item) {
                $item->is_pesanan = false;
                $item->is_autokirim = false;
                return $item;
            });
        }

        $pesanans = collect();
        if (!$tipeFilter || $tipeFilter === 'kiriminaja') {
            $pesanans = $pesananQuery->get()->map(function ($item) {
                $std = $this->standardizePesanan($item);
                $std->is_pesanan = true;
                $std->is_autokirim = false;
                return $std;
            });
        }

        $autokirims = collect();
        if (!$tipeFilter || $tipeFilter === 'autokirim') {
            $autokirims = $autokirimQuery->get()->map(function ($item) {
                $std = $this->standardizeAutokirim($item);
                $std->is_pesanan = false;
                $std->is_autokirim = true;
                return $std;
            });
        }

        // --- Gabungkan dan Urutkan ---
        $merged = collect($orders)->concat($pesanans)->concat($autokirims);
        $sorted = $merged->sortByDesc('created_at');

        // --- Pagination Manual ---
        $currentPage = Paginator::resolveCurrentPage('page');
        $currentPageItems = $sorted->slice(($currentPage - 1) * $perPage, $perPage);
        $paginatedItems = new LengthAwarePaginator(
            $currentPageItems, $sorted->count(), $perPage, $currentPage,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        // =========================================================================
        // BAGIAN 2: LOGIKA HITUNG PENDAPATAN (GABUNGAN 3 TABEL)
        // =========================================================================

        $pSelesai = Pesanan::where('status_pesanan', 'Selesai')->sum('price');
        $oSelesai = Order::where('status', 'completed')->sum('total_amount');
        $akSelesai = PesananAutokirim::whereIn('status', ['completed', 'terkirim', 'selesai', 'sukses'])->sum('grand_total');

        $pPickup  = Pesanan::whereIn('status_pesanan', ['Menunggu Pickup'])->sum('price');
        $oPickup  = Order::where('status', 'paid')->sum('total_amount');
        $akPickup = PesananAutokirim::where('status', 'booking_created')->sum('grand_total');

        $pDikirim = Pesanan::whereIn('status_pesanan', ['Diproses', 'Terkirim', 'Sedang Dikirim'])->sum('price');
        $oDikirim = Order::whereIn('status', ['processing', 'shipment', 'delivered'])->sum('total_amount');
        $akDikirim = PesananAutokirim::whereIn('status', ['on_delivery', 'shipping'])->sum('grand_total');

        $pGagal   = Pesanan::whereIn('status_pesanan', ['Batal', 'Gagal Bayar'])->sum('price');
        $oGagal   = Order::whereIn('status', ['cancelled', 'failed', 'rejected'])->sum('total_amount');
        $akGagal  = PesananAutokirim::whereIn('status', ['batal', 'gagal'])->sum('grand_total');

        return view('admin.orders.index', [
            'orders' => $paginatedItems,
            'incomeSelesai' => $pSelesai + $oSelesai + $akSelesai,
            'incomePickup' => $pPickup + $oPickup + $akPickup,
            'incomeDikirim' => $pDikirim + $oDikirim + $akDikirim,
            'incomeGagal' => $pGagal + $oGagal + $akGagal
        ]);
    }

    /**
     * FUNGSI BARU: Standarisasi Data Autokirim
     */
    private function standardizeAutokirim(PesananAutokirim $ak)
    {
        $order = new \stdClass();
        $order->id = $ak->id;
        $order->invoice_number = $ak->order_id;
        $order->created_at = $ak->created_at;
        $order->status = $ak->status;

        $order->shipping_method = $ak->kurir . ' - ' . $ak->layanan;
        $order->shipping_reference = $ak->awb_number;
        $order->payment_method = $ak->metode_pembayaran;

        $order->total_amount = $ak->grand_total;
        $order->subtotal = $ak->nilai_barang ?? 0;
        $order->shipping_cost = $ak->ongkir ?? 0;
        $order->cod_fee = 0; // Sesuaikan jika ada logika COD
        $order->insurance_cost = $ak->asuransi ? round($ak->nilai_barang * 0.002) : 0;

        // Mockup User (Pembeli)
        $user = new \stdClass();
        $user->nama_lengkap = $ak->penerima_nama;
        $user->address_detail = $ak->penerima_alamat;
        $order->user = $user;

        // Mockup Store (Pengirim)
        $store = new \stdClass();
        $store->name = $ak->pengirim_nama;
        $store->address_detail = $ak->pengirim_alamat;
        $order->store = $store;

        // Mockup Item
        $order->item_description = $ak->deskripsi_barang ?? $ak->kategori_barang;
        $order->weight = $ak->berat_gram;
        $order->length = $ak->panjang_cm;
        $order->width = $ak->lebar_cm;
        $order->height = $ak->tinggi_cm;

        $order->pengirim_hp = $ak->pengirim_hp;
        $order->penerima_hp = $ak->penerima_hp;

        return $order;
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
        // Tambahan data lengkap penerima
        $user->province = $pesanan->receiver_province;
        $user->postal_code = $pesanan->receiver_postal_code;

        // 2. Store (Pengirim/Sender)
        $store = new \stdClass();
        $store->name = $pesanan->sender_name ?? 'N/A';
        // PERBAIKAN UTAMA: Tambahkan phone, province, dll
        $store->phone = $pesanan->sender_phone;
        $store->address_detail = $pesanan->sender_address ?? 'N/A';
        $store->village = $pesanan->sender_village;
        $store->district = $pesanan->sender_district;
        $store->regency = $pesanan->sender_regency;
        $store->province = $pesanan->sender_province;
        $store->postal_code = $pesanan->sender_postal_code;

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

        // Agar blade tidak bingung, mapping sender_phone juga ke root object (opsional tapi aman)
        $order->sender_phone = $pesanan->sender_phone;
        $order->receiver_phone = $pesanan->receiver_phone;

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

            $mergedReportItems = $standardizedOrders->concat($standardizedPesanans);
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
