<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coa;
use App\Exports\CoaExport;
use App\Imports\CoaImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class CoaController extends Controller
{
    /**
     * Mengambil ID tenant yang sedang aktif.
     */
    private function getTenantId()
    {
        if (Auth::check() && Auth::user()->tenant_id) {
            return Auth::user()->tenant_id;
        }
        return 1; // Fallback
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenantId = $this->getTenantId();
        $coas = Coa::where('tenant_id', $tenantId)->orderBy('kode')->paginate(20);
        return view('admin.coa.index', compact('coas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.coa.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $tenantId = $this->getTenantId();

        $request->validate([
            'kode' => 'required|string|max:10|unique:coas,kode,NULL,id,tenant_id,' . $tenantId,
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:aset,kewajiban,ekuitas,pendapatan,beban',
        ]);

        Coa::create([
            'tenant_id' => $tenantId,
            'kode' => $request->kode,
            'nama' => $request->nama,
            'tipe' => $request->tipe,
        ]);

        return redirect()->route('admin.coa.index')->with('success', 'Kode Akun berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Coa $coa)
    {
        if ($coa->tenant_id != $this->getTenantId()) {
            abort(403, 'Akses Ditolak');
        }
        return view('admin.coa.edit', compact('coa'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Coa $coa)
    {
        $tenantId = $this->getTenantId();
        if ($coa->tenant_id != $tenantId) {
             abort(403, 'Akses Ditolak');
        }

        $request->validate([
            'kode' => 'required|string|max:10|unique:coas,kode,' . $coa->id . ',id,tenant_id,' . $tenantId,
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:aset,kewajiban,ekuitas,pendapatan,beban',
        ]);

        $coa->update($request->all());

        return redirect()->route('admin.coa.index')->with('success', 'Kode Akun berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coa $coa)
    {
        if ($coa->tenant_id != $this->getTenantId()) {
             abort(403, 'Akses Ditolak');
        }

        if ($coa->journalTransactions()->exists()) {
             return redirect()->route('admin.coa.index')->with('error', 'Akun tidak bisa dihapus karena sudah memiliki riwayat transaksi.');
        }
        
        $coa->delete();
        return redirect()->route('admin.coa.index')->with('success', 'Kode Akun berhasil dihapus.');
    }

    /**
     * Export data COA ke file Excel.
     */
    public function exportExcel()
    {
        return Excel::download(new CoaExport($this->getTenantId()), 'daftar-kode-akun.xlsx');
    }

    /**
     * Export data COA ke file PDF.
     */
    public function exportPdf()
    {
        $tenantId = $this->getTenantId();
        $data = [
            'coas' => Coa::where('tenant_id', $tenantId)->orderBy('kode')->get(),
            'date' => date('d/m/Y')
        ];
        
        $pdf = PDF::loadView('admin.coa.pdf', $data);
        return $pdf->download('daftar-kode-akun.pdf');
    }

    /**
     * Menampilkan form untuk import Excel.
     */
    public function showImportForm()
    {
        return view('admin.coa.import');
    }

    /**
     * Memproses file Excel yang diimpor.
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new CoaImport($this->getTenantId()), $request->file('file'));
            return redirect()->route('admin.coa.index')->with('success', 'Data Kode Akun berhasil diimpor!');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
             $failures = $e->failures();
             $errorMessages = [];
             // PERBAIKAN: Membuat pesan error yang lebih detail
             foreach ($failures as $failure) {
                 // Pesan akan berisi: "Baris [nomor baris]: [pesan error]"
                 $errorMessages[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors());
             }
             // Menggabungkan semua pesan error menjadi satu
             $fullErrorMessage = 'Gagal mengimpor. Terdapat error pada file Excel Anda: ' . implode('; ', $errorMessages);
             return back()->withErrors(['file' => $fullErrorMessage]);
        }
    }

    /**
     * Mengunduh file template Excel untuk impor.
     */
    public function downloadTemplate()
    {
        $export = new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function array(): array
            {
                return [];
            }
            public function headings(): array
            {
                return ['kode', 'nama', 'tipe'];
            }
        };

        return Excel::download($export, 'template_coa.xlsx');
    }
}

