<?php

namespace App\Http\Controllers;

use App\Models\SeminarParticipant;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SeminarController extends Controller
{
    // --- PUBLIC: FORMULIR ---
    public function create()
    {
        return view('public.seminar.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'email' => 'required|email',
            'no_wa' => 'required',
        ]);

        // Generate Nomor Tiket Unik: SEM-TAHUN-ACAK (Contoh: SEM-2024-XY78Z)
        $ticketNumber = 'SEM-' . date('Y') . '-' . strtoupper(Str::random(5));

        $participant = SeminarParticipant::create([
            'ticket_number' => $ticketNumber,
            'nama' => $request->nama,
            'email' => $request->email,
            'instansi' => $request->instansi,
            'no_wa' => $request->no_wa,
        ]);

        // Redirect langsung ke halaman tiket
        return redirect()->route('seminar.ticket', $participant->ticket_number);
    }

    // --- PUBLIC: TAMPIL TIKET ---
    public function showTicket($ticket_number)
    {
        $participant = SeminarParticipant::where('ticket_number', $ticket_number)->firstOrFail();

        // Generate QR Code berisi Nomor Tiket
        $qrcode = QrCode::size(200)->generate($participant->ticket_number);

        return view('public.seminar.ticket', compact('participant', 'qrcode'));
    }

    // --- ADMIN: LIST PESERTA ---
    public function index()
    {
        $participants = SeminarParticipant::latest()->paginate(20);
        return view('admin.seminar.index', compact('participants'));
    }

    // --- ADMIN: HALAMAN SCANNER ---
    public function scanPage()
    {
        return view('admin.seminar.scan');
    }

    // --- ADMIN: PROSES ABSENSI (AJAX) ---
    public function processScan(Request $request)
    {
        $code = $request->code; // Code hasil scan (Nomor Tiket)

        $participant = SeminarParticipant::where('ticket_number', $code)->first();

        if (!$participant) {
            return response()->json(['status' => 'error', 'message' => 'Tiket Tidak Ditemukan!']);
        }

        if ($participant->is_checked_in) {
            return response()->json([
                'status' => 'warning',
                'message' => 'Peserta SUDAH Hadir sebelumnya.',
                'data' => $participant,
                'time' => $participant->check_in_at->format('H:i:s')
            ]);
        }

        // Update Kehadiran
        $participant->update([
            'is_checked_in' => true,
            'check_in_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi Berhasil!',
            'data' => $participant,
            'time' => Carbon::now()->format('H:i:s')
        ]);
    }
}
