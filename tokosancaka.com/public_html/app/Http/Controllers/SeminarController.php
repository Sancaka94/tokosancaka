<?php

namespace App\Http\Controllers;

use App\Models\SeminarParticipant;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf; // Pastikan library dompdf sudah diinstall

class SeminarController extends Controller
{
    // =========================================================================
    // BAGIAN PUBLIC (PESERTA)
    // =========================================================================

    /**
     * Menampilkan Formulir Pendaftaran
     */
    public function create()
    {
        return view('public.seminar.form');
    }

    /**
     * Menyimpan Data Pendaftaran & Redirect ke Tiket
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'nama'       => 'required|string|max:255',
            'email'      => 'required|email',
            'no_wa'      => 'required|numeric',
            'nib_status' => 'required|in:Sudah,Belum', // Validasi NIB
        ]);

        // 2. Generate Nomor Tiket Unik (Contoh: SEM-2026-X7Z9A)
        $ticketNumber = 'SEM-' . date('Y') . '-' . strtoupper(Str::random(5));

        // 3. Simpan ke Database
        $participant = SeminarParticipant::create([
            'ticket_number' => $ticketNumber,
            'nama'          => $request->nama,
            'email'         => $request->email,
            'instansi'      => $request->instansi,
            'no_wa'         => $request->no_wa,
            'nib_status'    => $request->nib_status, // Simpan status NIB
        ]);

        // 4. Redirect ke Halaman Tiket
        return redirect()->route('seminar.ticket', $participant->ticket_number);
    }

    /**
     * Menampilkan E-Tiket + QR Code
     */
    public function showTicket($ticket_number)
    {
        $participant = SeminarParticipant::where('ticket_number', $ticket_number)->firstOrFail();

        // Generate QR Code berisi Nomor Tiket
        $qrcode = QrCode::size(200)->generate($participant->ticket_number);

        return view('public.seminar.ticket', compact('participant', 'qrcode'));
    }

    // =========================================================================
    // BAGIAN ADMIN (PANITIA)
    // =========================================================================

    /**
     * Dashboard Data Peserta (Tabel + Statistik)
     */
    public function index()
    {
        // 1. Ambil Data Peserta (Pagination 20 per halaman)
        $participants = SeminarParticipant::latest()->paginate(20);

        // 2. Hitung Statistik Lengkap
        $stats = [
            'total'       => SeminarParticipant::count(),
            'hadir'       => SeminarParticipant::where('is_checked_in', true)->count(),
            'belum_hadir' => SeminarParticipant::where('is_checked_in', false)->count(),
            // Hitung jumlah instansi unik (tidak duplikat nama)
            'instansi'    => SeminarParticipant::whereNotNull('instansi')->distinct('instansi')->count('instansi'),
            // Hitung peserta yang SUDAH punya NIB
            'nib'         => SeminarParticipant::where('nib_status', 'Sudah')->count(),
        ];

        return view('admin.seminar.index', compact('participants', 'stats'));
    }

    /**
     * Halaman Scanner Kamera
     */
    public function scanPage()
    {
        return view('admin.seminar.scan');
    }

    /**
     * Proses Logic Absensi (Dipanggil via AJAX oleh Scanner)
     */
    public function processScan(Request $request)
    {
        $code = $request->code; // Code hasil scan (Nomor Tiket)

        $participant = SeminarParticipant::where('ticket_number', $code)->first();

        // 1. Cek Apakah Tiket Ada?
        if (!$participant) {
            return response()->json(['status' => 'error', 'message' => 'Tiket Tidak Ditemukan!']);
        }

        // 2. Cek Apakah Sudah Absen Sebelumnya?
        if ($participant->is_checked_in) {
            return response()->json([
                'status'  => 'warning',
                'message' => 'Peserta SUDAH Hadir sebelumnya.',
                'data'    => $participant,
                'time'    => $participant->check_in_at->format('H:i:s')
            ]);
        }

        // 3. Update Kehadiran (Absen Sukses)
        $participant->update([
            'is_checked_in' => true,
            'check_in_at'   => Carbon::now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Absensi Berhasil!',
            'data'    => $participant,
            'time'    => Carbon::now()->format('H:i:s')
        ]);
    }

    /**
     * Export Data ke PDF
     */
    public function exportPdf()
    {
        // Ambil semua data (tanpa pagination)
        $participants = SeminarParticipant::all();

        // Load View PDF
        $pdf = Pdf::loadView('admin.seminar.pdf', compact('participants'));

        // Download File
        return $pdf->download('Data-Peserta-Seminar-' . date('d-m-Y') . '.pdf');
    }

    /**
     * Export Data ke Excel (Format .xls Sederhana)
     */
    public function exportExcel()
    {
        $participants = SeminarParticipant::all();

        return response(view('admin.seminar.excel', compact('participants')))
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="Data-Peserta-Seminar-' . date('d-m-Y') . '.xls"');
    }
}
