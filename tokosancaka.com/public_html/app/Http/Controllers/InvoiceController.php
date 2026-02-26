<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use PDF;

class InvoiceController extends Controller {

// Tampilkan tabel riwayat invoice
    public function index() {
        // Mengambil data terbaru dengan pagination
        $invoices = Invoice::orderBy('created_at', 'desc')->paginate(10);
        return view('invoice.index', compact('invoices'));
    }

    public function create() {
        // Generate nomor invoice otomatis: INV-YYYYMMDD-RAND
        $invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        return view('invoice.create', compact('invoiceNo'));
    }

    public function store(Request $request) {
        $invoice = Invoice::create([
            'invoice_no' => $request->invoice_no,
            'customer_name' => $request->customer_name,
            'company_name' => $request->company_name,
            'date' => $request->date,
            'subtotal' => $request->subtotal,
            'grand_total' => $request->subtotal,
        ]);

        if($request->hasFile('signature')) {
            $path = $request->file('signature')->store('signatures', 'public');
            $invoice->update(['signature_path' => $path]);
        }

        foreach ($request->items as $item) {
            $invoice->items()->create($item);
        }

        return redirect()->route('invoice.pdf', $invoice->id);
    }

    public function streamPDF($id) {
        $invoice = Invoice::with('items')->findOrFail($id);
        $pdf = PDF::loadView('invoice.pdf_template', compact('invoice'));
        return $pdf->stream($invoice->invoice_no . '.pdf');
    }
}
