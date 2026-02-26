<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Storage;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use PDF; // Pastikan alias PDF sudah terdaftar di config/app.php atau gunakan Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Tampilkan halaman riwayat invoice (Read)
     */
    public function index()
    {
        // Menampilkan data terbaru dengan pagination 10 per halaman
        $invoices = Invoice::orderBy('created_at', 'desc')->paginate(10);
        return view('invoice.index', compact('invoices'));
    }

    /**
     * Tampilkan form pembuatan invoice (Create)
     */
    public function create()
    {
        // Generate nomor invoice otomatis, misal: INV-20260226-XXXX
        $invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        return view('invoice.create', compact('invoiceNo'));
    }

    /**
     * Proses simpan invoice baru ke database (Store)
     */
    public function store(Request $request)
    {
        // 1. Hitung ulang subtotal dari item untuk keamanan
        $subtotal = 0;
        foreach ($request->items as $item) {
            $subtotal += ($item['qty'] * $item['price']);
        }

        // 2. Kalkulasi Diskon
        $discount_type = $request->discount_type ?? 'nominal';
        $discount_value = $request->discount_value ?? 0;

        if ($discount_type == 'percent') {
            $discount_amount = $subtotal * ($discount_value / 100);
        } else {
            $discount_amount = $discount_value;
        }

        // 3. Kalkulasi Grand Total dan Sisa Tagihan (DP)
        $grand_total = max(0, $subtotal - $discount_amount); // max(0, ...) mencegah minus
        $dp = $request->dp ?? 0;
        $sisa_tagihan = max(0, $grand_total - $dp);

        // 4. Simpan Data Invoice
        $invoice = Invoice::create([
            'invoice_no' => $request->invoice_no,
            'customer_name' => $request->customer_name,
            'company_name' => $request->company_name,
            'alamat' => $request->alamat,
            'keterangan' => $request->keterangan,
            'date' => $request->date,
            'subtotal' => $subtotal,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'discount_amount' => $discount_amount,
            'grand_total' => $grand_total,
            'dp' => $dp,
            'sisa_tagihan' => $sisa_tagihan,
        ]);

        // 5. Upload Tanda Tangan jika ada
        if ($request->hasFile('signature')) {
            $path = $request->file('signature')->store('signatures', 'public');
            $invoice->update(['signature_path' => $path]);
        }

        // 6. Simpan Data Item (Produk/Jasa)
        foreach ($request->items as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'total' => ($item['qty'] * $item['price']) // Hitung ulang untuk pastikan data akurat
            ]);
        }

        return redirect()->route('invoice.index')->with('success', 'Invoice berhasil dibuat!');
    }

    /**
     * Tampilkan form edit invoice (Edit)
     */
    public function edit($id)
    {
        // Load invoice beserta relasi items-nya
        $invoice = Invoice::with('items')->findOrFail($id);
        return view('invoice.edit', compact('invoice'));
    }

    /**
     * Proses update data invoice (Update)
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        // 1. Hitung ulang subtotal
        $subtotal = 0;
        foreach ($request->items as $item) {
            $subtotal += ($item['qty'] * $item['price']);
        }

        // 2. Kalkulasi Diskon
        $discount_type = $request->discount_type ?? 'nominal';
        $discount_value = $request->discount_value ?? 0;

        if ($discount_type == 'percent') {
            $discount_amount = $subtotal * ($discount_value / 100);
        } else {
            $discount_amount = $discount_value;
        }

        // 3. Kalkulasi Grand Total dan Sisa Tagihan (DP)
        $grand_total = max(0, $subtotal - $discount_amount);
        $dp = $request->dp ?? 0;
        $sisa_tagihan = max(0, $grand_total - $dp);

        // 4. Update Data Utama
        $invoice->update([
            'customer_name' => $request->customer_name,
            'company_name' => $request->company_name,
            'alamat' => $request->alamat,
            'keterangan' => $request->keterangan,
            'date' => $request->date,
            'subtotal' => $subtotal,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'discount_amount' => $discount_amount,
            'grand_total' => $grand_total,
            'dp' => $dp,
            'sisa_tagihan' => $sisa_tagihan,
        ]);

        // 5. Update Tanda Tangan jika ada file baru yang diunggah
        if ($request->hasFile('signature')) {
            // Hapus file lama jika ada
            if ($invoice->signature_path && Storage::disk('public')->exists($invoice->signature_path)) {
                Storage::disk('public')->delete($invoice->signature_path);
            }
            // Simpan file baru
            $path = $request->file('signature')->store('signatures', 'public');
            $invoice->update(['signature_path' => $path]);
        }

        // 6. Update Items (Cara paling aman: Hapus item lama, masukkan item baru dari form)
        $invoice->items()->delete();
        foreach ($request->items as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'total' => ($item['qty'] * $item['price'])
            ]);
        }

        return redirect()->route('invoice.index')->with('success', 'Invoice berhasil diperbarui!');
    }

    /**
     * Hapus data invoice (Delete)
     */
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        // Hapus file tanda tangan dari storage jika ada
        if ($invoice->signature_path && Storage::disk('public')->exists($invoice->signature_path)) {
            Storage::disk('public')->delete($invoice->signature_path);
        }

        // Hapus data (item terkait akan otomatis terhapus jika di migration diset ON DELETE CASCADE)
        $invoice->delete();

        return redirect()->route('invoice.index')->with('success', 'Invoice berhasil dihapus!');
    }

    /**
     * Tampilkan dan cetak PDF (Stream)
     */
    public function streamPDF($id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);

        // Load view template PDF
        $pdf = PDF::loadView('invoice.pdf_template', compact('invoice'));

        // Tampilkan PDF di browser
        return $pdf->stream($invoice->invoice_no . '.pdf');

        // Catatan: Jika ingin langsung download, gunakan:
        // return $pdf->download($invoice->invoice_no . '.pdf');
    }

    /**
     * Halaman Publik: Cek Tracking Invoice
     */
    public function track(Request $request)
    {
        $invoice = null;
        $searched = false;

        if ($request->has('invoice_no')) {
            $searched = true;
            // Cari invoice berdasarkan nomor yang diinputkan
            $invoice = Invoice::with('items')->where('invoice_no', $request->invoice_no)->first();
        }

        return view('invoice.track', compact('invoice', 'searched'));
    }

    /**
     * Halaman Publik: Download PDF
     */
    public function publicDownloadPDF($invoice_no)
    {
        $invoice = Invoice::with('items')->where('invoice_no', $invoice_no)->firstOrFail();

        $pdf = PDF::loadView('invoice.pdf_template', compact('invoice'));

        // Langsung download file PDF-nya
        return $pdf->download($invoice->invoice_no . '.pdf');
    }

    /**
     * Memproses Update Status & Keterangan dari Modal di Halaman Index
     */
    public function updateStatus(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        // Set otomatis nilai progress (%) berdasarkan status yang dipilih dropdown
        $progress = 0;
        switch($request->status) {
            case 'Invoice Diterbitkan': $progress = 10; break;
            case 'Pembayaran Terverifikasi': $progress = 25; break;
            case 'Proses Pengerjaan': $progress = 50; break;
            case 'Finishing & Siap Kirim': $progress = 75; break;
            case 'Selesai & Lunas': $progress = 100; break;
            default: $progress = 10;
        }

        // Update database
        $invoice->update([
            'status' => $request->status,
            'progress_percent' => $progress,
            'tracking_note' => $request->tracking_note
        ]);

        return redirect()->route('invoice.index')->with('success', 'Status & Keterangan tracking berhasil diperbarui!');
    }

    /**
     * Proses Pembayaran via DOKU dari Halaman Publik (Auto-Detect Logic)
     */
    public function payWithDoku(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->sisa_tagihan <= 0) {
            return redirect()->back()->with('error', 'Tagihan ini sudah lunas.');
        }

        $amount = 0;
        $paymentType = '';
        $status = $invoice->status ?? 'Invoice Diterbitkan';

        // LOGIKA PENENTUAN NOMINAL TAGIHAN
        if ($status == 'Invoice Diterbitkan') {
            if ($invoice->dp > 0) {
                $amount = $invoice->dp;
                $paymentType = 'DP';
            } else {
                $amount = $invoice->grand_total;
                $paymentType = 'LUNAS_LANGSUNG';
            }
        } elseif ($status == 'Finishing & Siap Kirim') {
            // Jika sudah tahap akhir, bayar sisa kekurangannya
            $amount = $invoice->sisa_tagihan;
            $paymentType = 'PELUNASAN';
        } else {
            return redirect()->back()->with('error', 'Saat ini tidak ada tagihan yang harus dibayar, atau pembayaran Anda sedang diverifikasi.');
        }

        try {
            $dokuService = new \App\Services\DokuJokulService();

            // Reference Number: INV-NOMOR-JENISBAYAR-TIMESTAMP
            $referenceNo = $invoice->invoice_no . '-' . $paymentType . '-' . time();

            $customerData = [
                'name' => $invoice->customer_name,
                'email' => 'customer@sancakaexpress.com',
                'phone' => '085745808809'
            ];

            // Panggil API DOKU
            $paymentUrl = $dokuService->createPayment($referenceNo, $amount, $customerData);

            Log::info("DOKU Checkout Request", [
                'invoice' => $invoice->invoice_no,
                'jenis' => $paymentType,
                'nominal' => $amount
            ]);

            return redirect()->away($paymentUrl);

        } catch (\Exception $e) {
            Log::error("DOKU Checkout Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Maaf, terjadi kesalahan saat menghubungkan ke DOKU. Silakan coba lagi.');
        }
    }
}
