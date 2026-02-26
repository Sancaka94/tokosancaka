<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\FinancialReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // ==========================================
        // 1. DATA TABEL ATAS (REKAP PEGAWAI)
        // ==========================================
        $query = User::query();

        if ($user->role === 'superadmin') {
            $query->whereIn('role', ['admin', 'operator']);
        } elseif ($user->role === 'admin') {
            $query->where('tenant_id', $user->tenant_id)->whereIn('role', ['admin', 'operator']);
        } else {
            $query->where('id', $user->id);
        }

        $employees = $query->latest()->paginate(10, ['*'], 'emp_page');
        $allTargetEmployees = $query->get(); // Untuk tabel riwayat bawah

        $currentMonth = Carbon::now()->format('Y-m');

        // ==========================================
        // 2. AMBIL SEMUA DATA PENDAPATAN (PARKIR + KAS)
        // ==========================================
        $parkirRaw = Transaction::whereNotNull('exit_time')
            ->select('exit_time', 'fee', 'toilet_fee')->get()
            ->groupBy(function($item) { return Carbon::parse($item->exit_time)->format('Y-m-d'); })
            ->map(function($group) {
                return $group->sum(function($trx) { return $trx->fee + ($trx->toilet_fee ?? 0); });
            })->toArray();

        $kasRaw = FinancialReport::where('jenis', 'pemasukan')
            ->select('tanggal', 'nominal')->get()
            ->groupBy(function($item) { return Carbon::parse($item->tanggal)->format('Y-m-d'); })
            ->map(function($group) { return $group->sum('nominal'); })->toArray();

        $gajiManualSemua = FinancialReport::where('kategori', 'Gaji Pegawai')->get();
        $gajiDates = $gajiManualSemua->map(function($item) { return substr($item->tanggal, 0, 10); })->toArray();

        // Kumpulkan semua tanggal yang ada aktivitasnya
        $tanggalAktifSemua = array_unique(array_merge(array_keys($parkirRaw), array_keys($kasRaw), $gajiDates));
        rsort($tanggalAktifSemua); // Urutkan dari hari terbaru ke terlama

        // ==========================================
        // 3. HITUNG REKAP TABEL ATAS (BULAN INI & TOTAL)
        // ==========================================
        foreach ($employees as $emp) {
            $totalBulanIni = 0;
            $totalSemua = 0;

            foreach ($tanggalAktifSemua as $tgl) {
                $manual = $gajiManualSemua->filter(function($item) use ($emp, $tgl) {
                    return substr($item->tanggal, 0, 10) == $tgl && stripos($item->keterangan, $emp->name) !== false;
                })->sum('nominal');

                if ($manual > 0) {
                    $totalSemua += $manual;
                    if (str_starts_with($tgl, $currentMonth)) $totalBulanIni += $manual;
                } else {
                    $gross = ($parkirRaw[$tgl] ?? 0) + ($kasRaw[$tgl] ?? 0);
                    if ($gross > 0) {
                        $earned = ($emp->salary_type == 'percentage') ? ($emp->salary_amount / 100) * $gross : $emp->salary_amount;
                        $totalSemua += $earned;
                        if (str_starts_with($tgl, $currentMonth)) $totalBulanIni += $earned;
                    }
                }
            }
            $emp->pendapatan_bulan_ini = $totalBulanIni;
            $emp->total_pendapatan = $totalSemua;
        }

        // ==========================================
        // 4. DATA TABEL BAWAH (RIWAYAT HARIAN GAJI)
        // ==========================================
        $historyCollection = collect();
        $filterTgl = $request->tanggal;
        $filterSearch = $request->search ? strtolower($request->search) : null;

        foreach ($tanggalAktifSemua as $tgl) {
            if ($filterTgl && $tgl != $filterTgl) continue;

            $gross = ($parkirRaw[$tgl] ?? 0) + ($kasRaw[$tgl] ?? 0);

            foreach ($allTargetEmployees as $emp) {
                if ($filterSearch && stripos($emp->name, $filterSearch) === false) continue;

                $manual = $gajiManualSemua->filter(function($item) use ($emp, $tgl) {
                    return substr($item->tanggal, 0, 10) == $tgl && stripos($item->keterangan, $emp->name) !== false;
                })->first();

                if ($manual) {
                    $historyCollection->push((object)[
                        'tanggal' => $tgl,
                        'nama' => $emp->name,
                        'keterangan' => 'Sudah Dibayar Kas',
                        'nominal' => $manual->nominal,
                        'status' => 'manual'
                    ]);
                } else {
                    if ($gross > 0) {
                        $earned = ($emp->salary_type == 'percentage') ? ($emp->salary_amount / 100) * $gross : $emp->salary_amount;
                        $historyCollection->push((object)[
                            'tanggal' => $tgl,
                            'nama' => $emp->name,
                            'keterangan' => 'Estimasi Sistem',
                            'nominal' => $earned,
                            'status' => 'auto'
                        ]);
                    }
                }
            }
        }

        // Paginasi Manual untuk Tabel Bawah
        $perPage = 15;
        $currentPage = request()->input('hist_page', 1);
        $currentItems = $historyCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $salaryHistory = new LengthAwarePaginator($currentItems, count($historyCollection), $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
            'pageName' => 'hist_page'
        ]);

        return view('employees.index', compact('employees', 'salaryHistory'));
    }

    public function create() { return view('employees.create'); }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users,email',
            'password'      => 'required|string|min:8',
            'role'          => 'required|in:operator,admin',
            'salary_type'   => 'required|in:nominal,percentage',
            'salary_amount' => 'required|numeric|min:0',
        ]);

        User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'role'          => $request->role,
            'salary_type'   => $request->salary_type,
            'salary_amount' => $request->salary_amount,
            'tenant_id'     => auth()->user()->tenant_id,
        ]);

        return redirect()->route('employees.index')->with('success', 'Akun pegawai berhasil ditambahkan.');
    }

    public function edit(User $employee) { return view('employees.edit', compact('employee')); }

    public function update(Request $request, User $employee)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users,email,' . $employee->id,
            'role'          => 'required|in:operator,admin',
            'salary_type'   => 'required|in:nominal,percentage',
            'salary_amount' => 'required|numeric|min:0',
        ]);

        $data = $request->only('name', 'email', 'role', 'salary_type', 'salary_amount');
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);
        return redirect()->route('employees.index')->with('success', 'Data pegawai diperbarui.');
    }

    public function destroy(User $employee)
    {
        if (auth()->id() === $employee->id) return redirect()->route('employees.index')->with('error', 'Gagal.');
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Pegawai dihapus.');
    }
}
