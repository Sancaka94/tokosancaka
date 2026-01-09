<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coa;
use App\Models\JournalTransaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LaporanKeuanganController extends Controller
{
    /**
     * Mengambil ID tenant yang sedang aktif dengan lebih aman.
     */
    private function getTenantId()
    {
        return Auth::user()?->tenant_id ?? 1;
    }

    /**
     * Menampilkan dan memfilter laporan pemasukan.
     */
    public function pemasukan(Request $request)
    {
        $tenantId = $this->getTenantId();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // PERBAIKAN: Menggunakan LIKE untuk query yang lebih fleksibel
        $baseQuery = JournalTransaction::whereHas('coa', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)->where('tipe', 'LIKE', '%Pendapatan%');
        })
        ->whereHas('journal', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate . " 00:00:00", $endDate . " 23:59:59"]);
        });

        $totalPemasukan = (clone $baseQuery)->sum('credit');
        
        $transactions = (clone $baseQuery)->where('credit', '>', 0)->latest('id')->paginate(15);

        // PERBAIKAN: Menggunakan LIKE untuk mengambil semua jenis pendapatan
        $incomeCoas = Coa::where('tenant_id', $tenantId)
            ->where('tipe', 'LIKE', '%Pendapatan%')
            ->orderBy('kode')
            ->get();

        return view('admin.laporan.pemasukan', compact('transactions', 'totalPemasukan', 'incomeCoas', 'startDate', 'endDate'));
    }

    /**
     * Menyimpan data pemasukan baru ke dalam jurnal.
     */
    public function storePemasukan(Request $request)
    {
        $tenantId = $this->getTenantId();
        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'coa_id' => 'required|exists:coas,id',
            'jumlah' => 'required|numeric|min:1',
        ]);

        $coaPendapatan = Coa::find($request->coa_id);
        // PERBAIKAN: Validasi yang lebih fleksibel
        if ($coaPendapatan->tenant_id != $tenantId || !str_contains($coaPendapatan->getOriginal('tipe'), 'Pendapatan')) {
            abort(403, 'Akses Ditolak atau Kategori tidak valid.');
        }
        
        $coaKas = Coa::where('kode', '1100')->where('tenant_id', $tenantId)->firstOrFail();
        
        $journal = \Scottlaurent\Accounting\Models\Journal::create(['currency' => 'IDR']);
        $journal->credit($request->jumlah * 100, $coaPendapatan);
        $journal->debit($request->jumlah * 100, $coaKas);

        foreach($journal->transactions as $transaction) {
            $transaction->description = $request->deskripsi;
            $transaction->save();
        }

        return redirect()->route('admin.laporan.pemasukan')->with('success', 'Pemasukan berhasil dicatat.');
    }

    /**
     * Menampilkan dan memfilter laporan pengeluaran.
     */
    public function pengeluaran(Request $request)
    {
        $tenantId = $this->getTenantId();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // PERBAIKAN: Menggunakan LIKE untuk query yang lebih fleksibel
        $baseQuery = JournalTransaction::whereHas('coa', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)->where('tipe', 'LIKE', '%Beban%');
        })
        ->whereHas('journal', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate . " 00:00:00", $endDate . " 23:59:59"]);
        });
        
        $totalPengeluaran = (clone $baseQuery)->sum('debit');
        
        $transactions = (clone $baseQuery)->where('debit', '>', 0)->latest('id')->paginate(15);

        // PERBAIKAN: Menggunakan LIKE untuk mengambil semua jenis beban
        $expenseCoas = Coa::where('tenant_id', $tenantId)
            ->where('tipe', 'LIKE', '%Beban%')
            ->orderBy('kode')
            ->get();

        return view('admin.laporan.pengeluaran', compact('transactions', 'totalPengeluaran', 'expenseCoas', 'startDate', 'endDate'));
    }

    /**
     * Menyimpan data pengeluaran baru ke dalam jurnal.
     */
    public function storePengeluaran(Request $request)
    {
        $tenantId = $this->getTenantId();
        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'coa_id' => 'required|exists:coas,id',
            'jumlah' => 'required|numeric|min:1',
        ]);

        $coaBeban = Coa::find($request->coa_id);
        // PERBAIKAN: Validasi yang lebih fleksibel
        if ($coaBeban->tenant_id != $tenantId || !str_contains($coaBeban->getOriginal('tipe'), 'Beban')) {
            abort(403, 'Akses Ditolak atau Kategori tidak valid.');
        }

        $coaKas = Coa::where('kode', '1100')->where('tenant_id', $tenantId)->firstOrFail();
        
        $journal = \Scottlaurent\Accounting\Models\Journal::create(['currency' => 'IDR']);
        $journal->debit($request->jumlah * 100, $coaBeban);
        $journal->credit($request->jumlah * 100, $coaKas);

        foreach($journal->transactions as $transaction) {
            $transaction->description = $request->deskripsi;
            $transaction->save();
        }

        return redirect()->route('admin.laporan.pengeluaran')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    /**
     * Menampilkan Laporan Laba Rugi untuk periode yang dipilih.
     */
     public function labaRugi(Request $request)
    {
        $tenantId = $this->getTenantId();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // PERBAIKAN: Menggunakan LIKE untuk query yang lebih fleksibel
        $pendapatan = Coa::where('tenant_id', $tenantId)
            ->where('tipe', 'LIKE', '%Pendapatan%')
            ->with(['journalTransactions' => function($query) use ($startDate, $endDate) {
                $query->whereHas('journal', function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->whereBetween('created_at', [$startDate . " 00:00:00", $endDate . " 23:59:59"]);
                });
            }])
            ->get();

        // PERBAIKAN: Menggunakan LIKE untuk query yang lebih fleksibel
        $beban = Coa::where('tenant_id', $tenantId)
            ->where('tipe', 'LIKE', '%Beban%')
             ->with(['journalTransactions' => function($query) use ($startDate, $endDate) {
                $query->whereHas('journal', function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->whereBetween('created_at', [$startDate . " 00:00:00", $endDate . " 23:59:59"]);
                });
            }])
            ->get();

        $totalPendapatan = $pendapatan->sum(function($account){
            return $account->journalTransactions->sum('credit');
        });

        $totalBeban = $beban->sum(function($account){
            return $account->journalTransactions->sum('debit');
        });

        $labaRugi = $totalPendapatan - $totalBeban;

        return view('admin.laporan.laba-rugi', compact('pendapatan', 'beban', 'totalPendapatan', 'totalBeban', 'labaRugi', 'startDate', 'endDate'));
    }


    /**
     * Menampilkan Laporan Neraca Saldo.
     */
    public function neracaSaldo(Request $request)
    {
        $tenantId = $this->getTenantId();
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $accounts = Coa::where('tenant_id', $tenantId)
            ->with(['journalTransactions' => function ($query) use ($endDate) {
                $query->whereHas('journal', function($subQuery) use ($endDate) {
                    $subQuery->where('created_at', '<=', $endDate . " 23:59:59");
                });
            }])
            ->orderBy('kode')->get();

        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $debit = $account->journalTransactions->sum('debit');
            $credit = $account->journalTransactions->sum('credit');
            $balance = $debit - $credit;

            // PERBAIKAN: Logika isDebitNormal dibuat lebih fleksibel
            $isDebitNormal = str_contains($account->getOriginal('tipe'), 'Aset') || str_contains($account->getOriginal('tipe'), 'Beban');

            if ($isDebitNormal) {
                if ($balance >= 0) {
                    $trialBalance[] = ['account' => $account, 'debit' => $balance, 'credit' => 0];
                } else {
                    $trialBalance[] = ['account' => $account, 'debit' => 0, 'credit' => abs($balance)];
                }
            } else { // Kredit Normal (Kewajiban, Ekuitas, Pendapatan)
                if ($balance <= 0) {
                    $trialBalance[] = ['account' => $account, 'debit' => 0, 'credit' => abs($balance)];
                } else {
                    $trialBalance[] = ['account' => $account, 'debit' => $balance, 'credit' => 0];
                }
            }
        }

        foreach ($trialBalance as $item) {
            $totalDebit += $item['debit'];
            $totalCredit += $item['credit'];
        }

        return view('admin.laporan.neraca-saldo', compact('trialBalance', 'totalDebit', 'totalCredit', 'endDate'));
    }

    /**
     * Menampilkan Laporan Neraca.
     */
    public function neraca(Request $request)
    {
        $tenantId = $this->getTenantId();
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // PERBAIKAN: Menggunakan LIKE untuk query yang lebih fleksibel
        $pendapatan = JournalTransaction::whereHas('coa', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)->where('tipe', 'LIKE', '%Pendapatan%');
        })->whereHas('journal', function($query) use ($endDate){
            $query->where('created_at', '<=', $endDate . " 23:59:59");
        })->sum('credit');

        // PERBAIKAN: Menggunakan LIKE untuk query yang lebih fleksibel
        $beban = JournalTransaction::whereHas('coa', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)->where('tipe', 'LIKE', '%Beban%');
        })->whereHas('journal', function($query) use ($endDate){
            $query->where('created_at', '<=', $endDate . " 23:59:59");
        })->sum('debit');

        $labaRugiBerjalan = $pendapatan - $beban;

        $accounts = Coa::where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->where('tipe', 'LIKE', '%Aset%')
                      ->orWhere('tipe', 'LIKE', '%Kewajiban%')
                      ->orWhere('tipe', 'LIKE', '%Ekuitas%');
            })
            ->with(['journalTransactions' => function ($query) use ($endDate) {
                $query->whereHas('journal', function($subQuery) use ($endDate){
                    $subQuery->where('created_at', '<=', $endDate . " 23:59:59");
                });
            }])
            ->orderBy('kode')->get();

        $assets = [];
        $liabilities = [];
        $equities = [];

        foreach($accounts as $account){
            $balance = $account->journalTransactions->sum('debit') - $account->journalTransactions->sum('credit');
            $account->balance = $balance;

            if(str_contains($account->getOriginal('tipe'), 'Aset')) {
                $assets[] = $account;
            } elseif (str_contains($account->getOriginal('tipe'), 'Kewajiban')) {
                $liabilities[] = $account;
            } elseif (str_contains($account->getOriginal('tipe'), 'Ekuitas')) {
                $equities[] = $account;
            }
        }

        return view('admin.laporan.neraca', compact('assets', 'liabilities', 'equities', 'labaRugiBerjalan', 'endDate'));
    }
}

