<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Exports\PesanansExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PesananController extends Controller
{
    // ... (metode index, create, store, dll. tidak berubah)
    public function index(Request $request)
    {
        
         // TAMBAHKAN QUERY INI DI ATAS:
    // Ini akan menandai semua notifikasi pesanan yang belum dilihat sebagai "telah dilihat"
    \App\Models\Pesanan::where('status', 'baru')
                         ->where('telah_dilihat', false)
                         ->update(['telah_dilihat' => true]);
                         
        $query = Pesanan::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $orders = $query->latest()->paginate(10); 
        return view('admin.pesanan.index', compact('orders'));
    }
    public function create()
    {
        return view('admin.pesanan.create');
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'service_type' => 'required|string',
            'expedition' => 'required|string',
            'payment_method' => 'required|string',
            'item_description' => 'required|string',
            'weight' => 'required|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'kelengkapan' => 'nullable|array',
            'save_sender' => 'nullable',
            'save_receiver' => 'nullable',
        ]);
        if ($request->has('save_sender')) {
            Kontak::updateOrCreate(
                ['no_hp' => $request->sender_phone],
                ['nama' => $request->sender_name, 'alamat' => $request->sender_address, 'tipe' => 'Pengirim']
            );
        }
        if ($request->has('save_receiver')) {
            Kontak::updateOrCreate(
                ['no_hp' => $request->receiver_phone],
                ['nama' => $request->receiver_name, 'alamat' => $request->receiver_address, 'tipe' => 'Penerima']
            );
        }
        $validatedData['resi'] = 'SCK' . strtoupper(Str::random(8));
        $validatedData['status'] = 'Menunggu Pickup';
        $address_parts = explode(',', $validatedData['receiver_address']);
        $validatedData['tujuan'] = trim(end($address_parts));
        Pesanan::create($validatedData);
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan baru dengan resi ' . $validatedData['resi'] . ' berhasil dibuat!');
    }
    public function show($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.show', compact('order'));
    }
    public function edit($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.edit', compact('order'));
    }
    public function update(Request $request, $resi)
    {
        $validatedData = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'item_description' => 'required|string',
            'weight' => 'required|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'payment_method' => 'required|string',
            'service_type' => 'required|string',
            'expedition' => 'required|string',
            'kelengkapan' => 'nullable|array',
        ]);
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        $validatedData['kelengkapan'] = $request->has('kelengkapan') ? $request->input('kelengkapan') : null;
        $order->update($validatedData);
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil diperbarui.');
    }
    public function destroy($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        $order->delete();
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil dihapus.');
    }
    public function showScanForm($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.scan-aktual', compact('pesanan'));
    }

    /**
     * Memperbarui resi aktual dan status pesanan.
     */
    public function updateResiAktual(Request $request, $resi)
    {
        $request->validate([
            'jasa_ekspedisi_aktual' => 'required|string',
            'resi_aktual' => 'required|string',
            'total_ongkir' => 'nullable|numeric|min:0',
        ]);

        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();

        // ======================= PERBAIKAN FINAL =======================
        // Menggunakan metode direct assignment yang lebih "memaksa"
        // ===============================================================
        $pesanan->jasa_ekspedisi_aktual = $request->input('jasa_ekspedisi_aktual');
        $pesanan->resi_aktual = $request->input('resi_aktual');
        $pesanan->total_ongkir = $request->input('total_ongkir');
        $pesanan->status = 'Diproses';

        // Menyimpan semua perubahan ke database
        $pesanan->save();

        return redirect()->route('admin.pesanan.index')->with('success', 'Resi aktual dan ongkir berhasil diperbarui!');
    }

    // ... (sisa metode tidak berubah)
    public function riwayatScan(Request $request)
    {
        $query = Pesanan::whereNotNull('resi_aktual');
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")->orWhere('resi_aktual', 'like', "%{$search}%");
            });
        }
        if ($request->filled('range')) {
            switch ($request->input('range')) {
                case 'harian': $query->whereDate('updated_at', Carbon::today()); break;
                case 'mingguan': $query->whereBetween('updated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]); break;
                case 'bulanan': $query->whereMonth('updated_at', Carbon::now()->month)->whereYear('updated_at', Carbon::now()->year); break;
            }
        }
        $perPage = $request->input('per_page', 10);
        $scannedOrders = $query->latest('updated_at')->paginate($perPage);
        $scannedOrders->appends($request->all());
        return view('admin.pesanan.riwayat-scan', compact('scannedOrders'));
    }
    public function updateStatus(Request $request, $resi)
    {
        $request->validate(['status' => 'required|string|in:Terkirim,Batal']);
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        $pesanan->update(['status' => $request->status]);
        return redirect()->back()->with('success', 'Status pesanan ' . $resi . ' berhasil diubah menjadi "' . $request->status . '".');
    }
    public function exportExcel() 
    {
        return Excel::download(new PesanansExport, 'semua-pesanan.xlsx');
    }
    public function exportPdf() 
    {
        $orders = Pesanan::all();
        $pdf = PDF::loadView('admin.pesanan.pdf', ['orders' => $orders]);
        return $pdf->download('semua-pesanan.pdf');
    }
    public function exportExcelRiwayat(Request $request) 
    {
        $query = Pesanan::whereNotNull('resi_aktual');
        $pesanansToExport = $query->get();
        return Excel::download(new PesanansExport($pesanansToExport), 'riwayat-scan.xlsx');
    }
    public function exportPdfRiwayat(Request $request) 
    {
        $query = Pesanan::whereNotNull('resi_aktual');
        $orders = $query->get();
        $pdf = PDF::loadView('admin.pesanan.pdf', ['orders' => $orders]);
        return $pdf->download('riwayat-scan.pdf');
    }
    public function cetakResi($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.cetak', compact('order'));
    }
    public function cetakResiThermal($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.cetak_thermal', ['pesanan' => $pesanan]);
    }
    public function showScanPaket()
    {
        $kontaks = Kontak::orderBy('nama', 'asc')->get();
        return view('admin.pesanan.scan-paket', compact('kontaks'));
    }
    public function processScanPaket(Request $request)
    {
        $request->validate([
            'kontak_id' => 'required|exists:kontaks,id',
            'resi' => 'required|string|max:255|unique:pesanan,resi',
        ], [
            'kontak_id.required' => 'GAGAL: Anda harus memilih pengirim terlebih dahulu.',
            'kontak_id.exists' => 'GAGAL: Pengirim yang dipilih tidak valid.',
            'resi.required' => 'GAGAL: Nomor resi tidak boleh kosong.',
            'resi.unique' => 'GAGAL: Resi ini sudah pernah di-scan sebelumnya.',
        ]);
        $kontakId = $request->input('kontak_id');
        $resi = $request->input('resi');
        $pengirim = Kontak::findOrFail($kontakId);
        try {
            Pesanan::create([
                'kontak_id' => $pengirim->id,
                'nama_pengirim' => $pengirim->nama,
                'resi' => $resi,
                'status' => 'manifest',
                'nama_penerima' => 'Belum Diisi',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['GAGAL: Terjadi kesalahan saat menyimpan ke database. ' . $e->getMessage()]);
        }
        return back()->with('success', "BERHASIL: Resi '{$resi}' untuk pengirim '{$pengirim->nama}' berhasil dicatat.")
                     ->with('selected_kontak_id', $kontakId);
    }
    
    public function count()
{
    // Ubah query Anda menjadi seperti ini:
    $count = \App\Models\Pesanan::where('status', 'baru')
                                  ->where('telah_dilihat', false) // Tambahkan baris ini
                                  ->count();
                                  
    return response()->json(['count' => $count]);
}
}
