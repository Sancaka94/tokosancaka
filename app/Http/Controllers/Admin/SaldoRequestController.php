<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction; // Pastikan ini di-import
use App\Models\User;        // ✅ PERBAIKAN: Spasi ' ' biasa, bukan spasi non-breaking ' '
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\SaldoUpdated; // <-- Event untuk update saldo real-time (jika ada)
use App\Services\FonnteService; // 🔑 TAMBAHKAN INI
use Carbon\Carbon; // Digunakan untuk Carbon::parse
use App\Notifications\NotifikasiUmum;

class SaldoRequestController extends Controller
{
    /**
     * Menampilkan daftar permintaan top up yang 'pending'.
     * Ini adalah halaman yang Anda screenshot.
     */
    public function index(Request $request)
    {
        // Ambil SEMUA permintaan top up yang statusnya 'pending'
        // PENTING: ::with('user') akan mengambil data Pengguna (nama_lengkap, dll)
        //          untuk menghindari N+1 query problem.
        $query = Transaction::with('user')
                    ->where('type', 'topup')
                    ->where('status', 'pending')
                    // ✅ PERBAIKAN:
                    // Kita kembalikan logikanya ke 'description'
                    // agar menampilkan SEMUA transfer manual yang pending,
                    // baik yang sudah upload bukti ataupun yang belum.
                    ->where('description', 'like', '%Transfer Manual%')
                    ->whereNotNull('payment_proof_path'); // Pastikan sudah ada bukti transfer
        
        // (Opsional) Tambahkan filter jika perlu
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('reference_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('nama_lengkap', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Urutkan dari yang paling baru
        $requests = $query->latest()->paginate(20);

        // Kirim data 'requests' (jamak) ke view
        return view('admin.saldo.index', compact('requests'));
    }

    /**
     * Menyetujui permintaan top-up.
     */
    public function approve(Transaction $transaction, FonnteService $fonnteService)
    {
        if ($transaction->status !== 'pending') {
            return redirect()->route('admin.saldo.requests.index')->with('error', 'Transaksi ini sudah diproses sebelumnya.');
        }

        try {
            DB::transaction(function () use ($transaction, $fonnteService) {
                
                // 1. Ubah status transaksi & simpan
                $transaction->status = 'success';
                $transaction->save();

                // 2. Ambil data user
                $user = $transaction->user ?? User::find($transaction->user_id);

                if ($user) {
                    // 3. Tambahkan saldo ke user
                    $user->saldo += $transaction->amount;
                    $user->save();
                    
                    // 🔑 4. SIAPKAN DATA LENGKAP UNTUK NOTIFIKASI
                    $amount = number_format($transaction->amount, 0, ',', '.');
                    $saldoBaru = number_format($user->saldo, 0, ',', '.');
                    $namaPelanggan = $user->nama_lengkap;
                    $emailPelanggan = $user->email;
                    $idTransaksi = $transaction->reference_id;
                    
                    // Format tanggal dan jam
                    $transactionTime = Carbon::parse($transaction->created_at)
                                        ->locale('id')
                                        ->translatedFormat('l, d M Y | H:i T'); 

                    // 5. KIRIM PESAN WHATSAPP (APPROVE)
                    $noWa = preg_replace('/^0/', '62', $user->no_wa);
                    // Nomor Admin (Ganti jika Anda punya cara yang lebih baik untuk mengambilnya)
                    $adminWa = '085745808809'; 

                    $message = <<<TEXT
*✅ TOP UP BERHASIL! (ID: {$idTransaksi})*

Halo Kak {$namaPelanggan},
Permintaan top up saldo Anda telah *DISETUJUI* oleh Admin +628819435180

*— Detail Transaksi —*
- *ID Transaksi:* {$idTransaksi}
- *Waktu:* {$transactionTime}
- *Email:* {$emailPelanggan}
- *Nominal Top Up:* Rp {$amount}

*— Ringkasan Saldo —*
- *Total Saldo Baru:* *Rp {$saldoBaru}*

Terima kasih atas transaksinya!

Hormat kami,
*Admin Sancaka Express*
TEXT;
                    
                    // 🔑 PANGGILAN FONNTE SERVICE (Menggunakan object yang di-inject)
                    // Kirim ke Customer
                    $fonnteService->sendMessage($noWa, $message);
                    // Kirim ke Admin (sebagai log/konfirmasi)
                    $fonnteService->sendMessage($adminWa, "[LOG] Top Up Customer {$namaPelanggan} disetujui. ID: {$idTransaksi}. Jumlah: Rp {$amount}. Saldo Customer: Rp {$saldoBaru}.");
                    
                    // 6. 🔑 KIRIM NOTIFIKASI UMUM KE PELANGGAN
                    $dataNotifCustomer = [
                        'tipe'        => 'Keuangan',
                        'judul'       => '✅ Saldo Berhasil Ditambahkan',
                        'pesan_utama' => "Top Up Rp {$amount} Anda telah disetujui. Saldo baru Anda: Rp {$saldoBaru}.",
                        'url'         => route('customer.topup.index'), 
                        'icon'        => 'fas fa-wallet',
                    ];
                    // Menggunakan trait Notifiable pada model User
                    $user->notify(new NotifikasiUmum($dataNotifCustomer));
                    
                    // (Opsional) Trigger event jika menggunakan Laravel Echo/Websockets
                    event(new SaldoUpdated($user->id, $user->saldo));

                } else {
                    throw new \Exception("User dengan ID {$transaction->user_id} tidak ditemukan.");
                }
            });

        } catch (\Exception $e) {
            DB::rollback();
            Log::critical('Gagal menyetujui top up manual', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.saldo.requests.index')->with('error', 'Gagal memproses persetujuan. Silakan coba lagi. Error: ' . $e->getMessage());
        }

        return redirect()->route('admin.saldo.requests.index')->with('success', 'Permintaan saldo berhasil disetujui dan notifikasi dikirim.');
    }

   /**
     * Menolak (Reject) permintaan top up.
     */
    public function reject(Request $request, Transaction $transaction, FonnteService $fonnteService) // 🔑 Inject Service
    {
        $request->validate(['reason' => 'nullable|string|max:255']);
        $alasan = $request->input('reason') ?? 'Bukti transfer tidak valid atau tidak ditemukan, Untuk Lebih Jelasnya Silahkan Hubungi ADMIN 08819435180'; // Alasan lebih spesifik

        if ($transaction->status !== 'pending' || $transaction->type !== 'topup') {
            return redirect()->route('admin.saldo.requests.index')->with('error', 'Permintaan ini sudah diproses.');
        }
        
        // Cukup ubah status transaksi menjadi 'failed'
        $transaction->status = 'failed';
        $transaction->description = 'Top up ditolak Admin. Alasan: ' . $alasan;
        $transaction->save();

        // 🔑 KIRIM PESAN WHATSAPP (REJECT)
$user = $transaction->user ?? User::find($transaction->user_id);

if ($user) {
    // 💡 PERBAIKAN: Definisikan $adminWa di sini
    $adminWa = '085745808809'; // PENTING: Gunakan nomor yang sama dengan fungsi approve()

    $noWa = preg_replace('/^0/', '62', $user->no_wa);
    $amount = number_format($transaction->amount, 0, ',', '.');

    $message = <<<TEXT

*❌ TOP UP DITOLAK!*

Halo Kak {$user->nama_lengkap},
Permintaan top up saldo Anda sebesar *Rp {$amount}* telah *DITOLAK* oleh Admin.

Alasan Penolakan: *{$alasan}*

Mohon periksa kembali bukti transfer atau status transaksi Anda.
Silakan ajukan top up kembali jika terjadi kesalahan.

Hormat kami,
*Admin Sancaka Express*
TEXT;
            // Kirim ke Customer
            $fonnteService->sendMessage($noWa, $message);
            // Kirim log ke Admin
            $fonnteService->sendMessage($adminWa, "[LOG] Top Up Customer {$user->nama_lengkap} ditolak. ID: {$transaction->reference_id}. Jumlah: Rp {$amount}. Alasan: {$alasan}");

            // 7. 🔑 KIRIM NOTIFIKASI UMUM KE PELANGGAN
            $dataNotifCustomer = [
                'tipe'        => 'Keuangan',
                'judul'       => '❌ Top Up Ditolak',
                'pesan_utama' => "Top Up Rp {$amount} Anda ditolak. Alasan: {$alasan}.",
                'url'         => route('customer.topup.index'), 
                'icon'        => 'fas fa-times-circle',
            ];
            $user->notify(new NotifikasiUmum($dataNotifCustomer));
        }

        return redirect()->route('admin.saldo.requests.index')->with('success', 'Permintaan top up telah ditolak dan notifikasi dikirim.');
    }
    
    /**
     * Menampilkan seluruh riwayat top-up dari semua user.
     */
    public function showHistory()
    {
        $transactions = Transaction::where('type', 'topup')
                                    ->with('user') // Memuat relasi user
                                    ->latest() // Mengurutkan dari yang terbaru
                                    ->paginate(20); // Menggunakan paginasi

        // Ganti 'admin.saldo.riwayat' sesuai nama file Blade Anda
        return view('admin.saldo.riwayat', compact('transactions'));
    }
}