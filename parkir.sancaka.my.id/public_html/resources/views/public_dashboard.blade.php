<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Info Parkir</title>

    <link rel="icon" type="image/jpeg" href="https://tokosancaka.com/storage/uploads/logo.jpeg">
    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/logo.jpeg">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="text-gray-800">

    <nav class="bg-blue-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo" class="h-8 w-8 bg-white rounded-full p-1">
                    <span class="font-bold text-xl tracking-wide">Portal Info Parkir</span>
                </div>
                <div>
                    <a href="{{ route('login') }}" class="text-sm font-semibold bg-white text-blue-600 hover:bg-gray-100 px-4 py-2 rounded-full transition-colors shadow-sm">
                        Login &rarr;
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-6 text-center md:text-left">
            <h1 class="text-2xl font-bold text-gray-800">Ringkasan Operasional</h1>
            <p class="text-gray-500 text-sm mt-1">Live Update: {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y - H:i') }} WIB</p>
        </div>

        @php
            // =========================================================================
            // ENGINE PERHITUNGAN CERDAS OTOMATIS (VERSI FINAL & BERSIH)
            // =========================================================================
            use App\Models\Transaction;
            use App\Models\FinancialReport;
            use App\Models\User;
            use Illuminate\Support\Facades\DB;
            use Carbon\Carbon;

            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $h2 = Carbon::today()->subDays(2);
            $lastMonth = Carbon::now()->subMonth();

            // PEMISAHAN RUMUS MUTLAK
            $rumusParkirMurni = DB::raw('(CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END)');
            $rumusToilet = DB::raw('IFNULL(toilet_fee, 0)');

            // Fungsi Helper untuk Menghitung Persen NAIK/TURUN
            if (!function_exists('hitungPersen')) {
                function hitungPersen($sekarang, $kemarin) {
                    $selisih = $sekarang - $kemarin;
                    $persen = $kemarin > 0 ? ($selisih / $kemarin) * 100 : ($sekarang > 0 ? 100 : 0);
                    return ['selisih' => $selisih, 'persen' => $persen, 'is_naik' => $selisih >= 0];
                }
            }

            // --- 1. DATA KENDARAAN ---
            $motorHariIni = Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $today)->count();
            $motorKemarin = Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $yesterday)->count();

            $sepedaHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPD-%')->count();
            $sepedaKemarin = Transaction::whereDate('entry_time', $yesterday)->where('plate_number', 'LIKE', 'SPD-%')->count();

            $sepedaListrikHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPL-%')->count();
            $sepedaListrikKemarin = Transaction::whereDate('entry_time', $yesterday)->where('plate_number', 'LIKE', 'SPL-%')->count();

            $pegawaiRsudHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'RSUD-%')->count();
            $pegawaiRsudKemarin = Transaction::whereDate('entry_time', $yesterday)->where('plate_number', 'LIKE', 'RSUD-%')->count();

            // --- 2. DATA PENDAPATAN & OMZET ---

            // HARI INI
            $parkirMurniHariIni = Transaction::whereDate('entry_time', $today)->sum($rumusParkirMurni);
            $toiletHariIni      = Transaction::whereDate('entry_time', $today)->sum($rumusToilet);
            $kasMasukHariIni    = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluarHariIni   = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pengeluaran')->sum('nominal');

            $omzetHariIni      = $parkirMurniHariIni + $toiletHariIni + $kasMasukHariIni;
            $pendapatanHariIni = ($parkirMurniHariIni / 2) + $toiletHariIni + $kasMasukHariIni - $kasKeluarHariIni;

            // KEMARIN
            $parkirMurniKemarin = Transaction::whereDate('entry_time', $yesterday)->sum($rumusParkirMurni);
            $toiletKemarin      = Transaction::whereDate('entry_time', $yesterday)->sum($rumusToilet);
            $kasMasukKemarin    = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluarKemarin   = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pengeluaran')->sum('nominal');

            $omzetKemarin      = $parkirMurniKemarin + $toiletKemarin + $kasMasukKemarin;
            $pendapatanKemarin = ($parkirMurniKemarin / 2) + $toiletKemarin + $kasMasukKemarin - $kasKeluarKemarin;

            // H-2
            $parkirMurniH2 = Transaction::whereDate('entry_time', $h2)->sum($rumusParkirMurni);
            $toiletH2      = Transaction::whereDate('entry_time', $h2)->sum($rumusToilet);
            $kasMasukH2    = FinancialReport::whereDate('tanggal', $h2)->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluarH2   = FinancialReport::whereDate('tanggal', $h2)->where('jenis', 'pengeluaran')->sum('nominal');

            $pendapatanH2 = ($parkirMurniH2 / 2) + $toiletH2 + $kasMasukH2 - $kasKeluarH2;

            // BULAN INI
            $parkirMurniBulanIni = Transaction::whereMonth('entry_time', $today->month)->whereYear('entry_time', $today->year)->sum($rumusParkirMurni);
            $toiletBulanIni      = Transaction::whereMonth('entry_time', $today->month)->whereYear('entry_time', $today->year)->sum($rumusToilet);
            $kasMasukBulanIni    = FinancialReport::whereMonth('tanggal', $today->month)->whereYear('tanggal', $today->year)->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluarBulanIni   = FinancialReport::whereMonth('tanggal', $today->month)->whereYear('tanggal', $today->year)->where('jenis', 'pengeluaran')->sum('nominal');

            $pendapatanBulanIni = ($parkirMurniBulanIni / 2) + $toiletBulanIni + $kasMasukBulanIni - $kasKeluarBulanIni;

            // BULAN KEMARIN
            $parkirMurniBulanKemarin = Transaction::whereMonth('entry_time', $lastMonth->month)->whereYear('entry_time', $lastMonth->year)->sum($rumusParkirMurni);
            $toiletBulanKemarin      = Transaction::whereMonth('entry_time', $lastMonth->month)->whereYear('entry_time', $lastMonth->year)->sum($rumusToilet);
            $kasMasukBulanKemarin    = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluarBulanKemarin   = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pengeluaran')->sum('nominal');

            $pendapatanBulanKemarin = ($parkirMurniBulanKemarin / 2) + $toiletBulanKemarin + $kasMasukBulanKemarin - $kasKeluarBulanKemarin;

            // --- 3. DATA PARKIR MURNI (KHUSUS 4 CARD BAWAH) ---
            $parkirHariIni = $parkirMurniHariIni;
            $parkirKemarin = $parkirMurniKemarin;
            $parkirH2      = $parkirMurniH2;

            $parkir7Hari = Transaction::whereDate('entry_time', '>=', Carbon::today()->subDays(6))
                                      ->whereDate('entry_time', '<=', Carbon::today())
                                      ->sum($rumusParkirMurni);

            $parkir7HariLalu = Transaction::whereDate('entry_time', '>=', Carbon::today()->subDays(13))
                                          ->whereDate('entry_time', '<=', Carbon::today()->subDays(7))
                                          ->sum($rumusParkirMurni);

            $parkirBulanIni = $parkirMurniBulanIni;
            $parkirBulanLalu = $parkirMurniBulanKemarin;

            // --- 4. ENGINE GAJI PEGAWAI (UNTUK 4 CARD GAJI) ---
            $operators = User::where('role', 'operator')->get();
            $lapGajiHariIni = FinancialReport::whereDate('tanggal', $today)->where('kategori', 'Gaji Pegawai')->get();
            $lapGajiKemarin = FinancialReport::whereDate('tanggal', $yesterday)->where('kategori', 'Gaji Pegawai')->get();

            // AMBIL KAS MASUK TAPI KHUSUS PARKIR SAJA (Toilet/Umum Tidak Dihitung)
            $kasMasukKhususParkirHariIni = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
            $kasMasukKhususParkirKemarin = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');

            $pendKotorHariIni = $parkirMurniHariIni + $kasMasukKhususParkirHariIni;
            $pendKotorKemarin = $parkirMurniKemarin + $kasMasukKhususParkirKemarin;

            $operatorData = [];
            foreach($operators as $op) {
                $manualHariIni = $lapGajiHariIni->filter(fn($r) => stripos($r->keterangan, $op->name) !== false)->sum('nominal');
                $gajiHariIniOp = $manualHariIni > 0 ? $manualHariIni : ($op->salary_type == 'percentage' ? ($op->salary_amount / 100) * $pendKotorHariIni : $op->salary_amount);

                $manualKemarin = $lapGajiKemarin->filter(fn($r) => stripos($r->keterangan, $op->name) !== false)->sum('nominal');
                $gajiKemarinOp = $manualKemarin > 0 ? $manualKemarin : ($op->salary_type == 'percentage' ? ($op->salary_amount / 100) * $pendKotorKemarin : $op->salary_amount);

                $operatorData[] = (object)[
                    'name' => $op->name,
                    'gaji_hari_ini' => $gajiHariIniOp,
                    'gaji_kemarin' => $gajiKemarinOp,
                    'cmp' => hitungPersen($gajiHariIniOp, $gajiKemarinOp),
                    'type' => $op->salary_type,
                    'amount' => $op->salary_amount
                ];
            }

            // TOTAL BULAN INI UNTUK GAJI (Murni Parkir + Kas Parkir)
            $kasParkirBln = FinancialReport::whereMonth('tanggal', $today->month)
                                ->whereYear('tanggal', $today->year)
                                ->where('jenis', 'pemasukan')
                                ->where('kategori', 'Parkiran')
                                ->sum('nominal');
            $kotorBulanIni = $parkirMurniBulanIni + $kasParkirBln;

            // --- EKSEKUSI PERSENTASE NAIK/TURUN UNTUK SEMUA CARD ---
            $cmpMotor = hitungPersen($motorHariIni, $motorKemarin);
            $cmpSepeda = hitungPersen($sepedaHariIni, $sepedaKemarin);
            $cmpListrik = hitungPersen($sepedaListrikHariIni, $sepedaListrikKemarin);
            $cmpRsud = hitungPersen($pegawaiRsudHariIni, $pegawaiRsudKemarin);

            $cmpPendapatanKemarin = hitungPersen($pendapatanKemarin, $pendapatanH2);
            $cmpPendapatan = hitungPersen($pendapatanHariIni, $pendapatanKemarin);
            $cmpOmzet = hitungPersen($omzetHariIni, $omzetKemarin);
            $cmpBulan = hitungPersen($pendapatanBulanIni, $pendapatanBulanKemarin);

            $cmpParkirHari = hitungPersen($parkirHariIni, $parkirKemarin);
            $cmpParkirKemarin = hitungPersen($parkirKemarin, $parkirH2);
            $cmpParkir7 = hitungPersen($parkir7Hari, $parkir7HariLalu);
            $cmpParkirBulan = hitungPersen($parkirBulanIni, $parkirBulanLalu);
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div class="bg-gradient-to-br from-sky-400 to-sky-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-sky-100 text-xs font-bold uppercase tracking-wider mb-1">Motor (Hari Ini)</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $motorHariIni }} <span class="text-sm font-medium text-sky-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">🏍️</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpMotor['selisih'] == 0)
                        <span>Stagnan sama dgn kemarin</span>
                    @elseif($cmpMotor['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpMotor['persen']), 1, ',', '.') }}% (+{{ number_format(abs($cmpMotor['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpMotor['persen']), 1, ',', '.') }}% ({{ number_format($cmpMotor['selisih'], 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-teal-400 to-teal-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-teal-100 text-xs font-bold uppercase tracking-wider mb-1">Sepeda (Hari Ini)</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $sepedaHariIni }} <span class="text-sm font-medium text-teal-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">🚲</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpSepeda['selisih'] == 0)
                        <span>Stagnan sama dgn kemarin</span>
                    @elseif($cmpSepeda['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpSepeda['persen']), 1, ',', '.') }}% (+{{ number_format(abs($cmpSepeda['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpSepeda['persen']), 1, ',', '.') }}% ({{ number_format($cmpSepeda['selisih'], 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-purple-100 text-xs font-bold uppercase tracking-wider mb-1">Sepeda Listrik</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $sepedaListrikHariIni }} <span class="text-sm font-medium text-purple-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">⚡</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpListrik['selisih'] == 0)
                        <span>Stagnan sama dgn kemarin</span>
                    @elseif($cmpListrik['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpListrik['persen']), 1, ',', '.') }}% (+{{ number_format(abs($cmpListrik['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpListrik['persen']), 1, ',', '.') }}% ({{ number_format($cmpListrik['selisih'], 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-rose-400 to-rose-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-rose-100 text-xs font-bold uppercase tracking-wider mb-1">Pegawai RSUD</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $pegawaiRsudHariIni }} <span class="text-sm font-medium text-rose-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">🏥</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpRsud['selisih'] == 0)
                        <span>Stagnan sama dgn kemarin</span>
                    @elseif($cmpRsud['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpRsud['persen']), 1, ',', '.') }}% (+{{ number_format(abs($cmpRsud['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpRsud['persen']), 1, ',', '.') }}% ({{ number_format($cmpRsud['selisih'], 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-orange-100 text-xs font-bold uppercase tracking-wider mb-1">Profit Kemarin</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($pendapatanKemarin, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-90">⏳</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpPendapatanKemarin['selisih'] == 0)
                        <span>Stagnan sama dgn H-2</span>
                    @elseif($cmpPendapatanKemarin['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpPendapatanKemarin['persen']), 1, ',', '.') }}% dr H-2</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpPendapatanKemarin['persen']), 1, ',', '.') }}% dr H-2</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-green-100 text-xs font-bold uppercase tracking-wider mb-1">Profit Hari Ini</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-90">💵</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpPendapatan['selisih'] == 0)
                        <span>Stagnan sama dgn kemarin</span>
                    @elseif($cmpPendapatan['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpPendapatan['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($cmpPendapatan['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpPendapatan['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($cmpPendapatan['selisih']), 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-red-100 text-xs font-bold uppercase tracking-wider mb-1">Omzet Hari Ini</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-90">💰</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpOmzet['selisih'] == 0)
                        <span>Stagnan sama dgn kemarin</span>
                    @elseif($cmpOmzet['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpOmzet['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($cmpOmzet['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpOmzet['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($cmpOmzet['selisih']), 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-blue-100 text-xs font-bold uppercase tracking-wider mb-1">Profit Bulan Ini</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-90">📈</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpBulan['selisih'] == 0)
                        <span>Stagnan sama dgn bln lalu</span>
                    @elseif($cmpBulan['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpBulan['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($cmpBulan['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpBulan['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($cmpBulan['selisih']), 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

        </div>

        <div class="mb-6 mt-12 text-center md:text-left">
            <h2 class="text-2xl font-bold text-gray-800">Profit Kendaraan (Murni Parkir)</h2>
            <p class="text-gray-500 text-sm mt-1">Total uang masuk murni dari tiket parkir tanpa tambahan toilet dan kas.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-1">Parkir (Hari Ini)</h5>
                        <p class="text-2xl font-black">Rp {{ number_format($parkirHariIni, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-80">🎟️</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpParkirHari['selisih'] == 0)
                        <span>Stagnan sama dgn kemarin</span>
                    @elseif($cmpParkirHari['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpParkirHari['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($cmpParkirHari['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpParkirHari['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($cmpParkirHari['selisih']), 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-500 to-slate-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Parkir (Kemarin)</h5>
                        <p class="text-2xl font-black">Rp {{ number_format($parkirKemarin, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-80">📅</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpParkirKemarin['selisih'] == 0)
                        <span>Stagnan sama dgn H-2</span>
                    @elseif($cmpParkirKemarin['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpParkirKemarin['persen']), 1, ',', '.') }}% dr H-2</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpParkirKemarin['persen']), 1, ',', '.') }}% dr H-2</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-cyan-100 text-xs font-bold uppercase tracking-wider mb-1">Parkir (7 Hari)</h5>
                        <p class="text-2xl font-black">Rp {{ number_format($parkir7Hari, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-80">📊</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpParkir7['selisih'] == 0)
                        <span>Stagnan sama dgn mg lalu</span>
                    @elseif($cmpParkir7['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpParkir7['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($cmpParkir7['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpParkir7['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($cmpParkir7['selisih']), 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-fuchsia-200 text-xs font-bold uppercase tracking-wider mb-1">Parkir (Bulan Ini)</h5>
                        <p class="text-2xl font-black">Rp {{ number_format($parkirBulanIni, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-80">📅</div>
                </div>
                <div class="mt-3 flex items-center text-[11px] font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpParkirBulan['selisih'] == 0)
                        <span>Stagnan sama dgn bln lalu</span>
                    @elseif($cmpParkirBulan['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpParkirBulan['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($cmpParkirBulan['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpParkirBulan['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($cmpParkirBulan['selisih']), 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
                    <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (7 Hari Terakhir)</h3>
                </div>
                <div class="p-4">
                    <canvas id="chartHarianPublic" height="250"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
                    <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (6 Bulan Terakhir)</h3>
                </div>
                <div class="p-4">
                    <canvas id="chartBulananPublic" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="mb-6 mt-12 flex justify-between items-end">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Estimasi Gaji Pegawai (Hari Ini)</h2>
                <p class="text-gray-500 text-sm mt-1">Perhitungan otomatis gaji hanya dari Parkir Murni + Kas Parkir.</p>
            </div>
            <span class="hidden md:inline-block text-[10px] bg-purple-100 text-purple-800 px-3 py-1.5 rounded-full font-bold uppercase tracking-wider border border-purple-200">Dihitung Otomatis</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            @forelse($operatorData as $index => $op)
                @php
                    $colors = [
                        'text-blue-600 bg-blue-100',
                        'text-emerald-600 bg-emerald-100',
                        'text-amber-600 bg-amber-100',
                        'text-rose-600 bg-rose-100'
                    ];
                    $color = $colors[$index % 4];

                    // PENTING: Hitung Total Gaji 1 Bulan Ini berdasarkan dasar gaji (Parkir + Kas Parkir)
                    $totalBulanIni = $op->type == 'percentage' ? ($op->amount / 100) * $kotorBulanIni : $op->amount * date('j');
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col relative overflow-hidden transform transition duration-300 hover:scale-105 hover:shadow-lg">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="{{ $color }} p-3 rounded-full flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 font-bold uppercase mb-0.5 tracking-wider">Total 1 Bln: <span class="text-indigo-600 font-black">Rp {{ number_format($totalBulanIni, 0, ',', '.') }}</span></p>

                            <h4 class="font-bold text-gray-800 text-lg leading-none mb-1.5">{{ $op->name }}</h4>

                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $op->type == 'percentage' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-green-50 text-green-600 border border-green-100' }}">
                                {{ $op->type == 'percentage' ? 'Bagi Hasil ('.(float)$op->amount.'%)' : 'Flat' }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Estimasi Diterima (Hari Ini)</p>
                        <p class="text-2xl font-black text-gray-800">Rp {{ number_format($op->gaji_hari_ini, 0, ',', '.') }}</p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-[11px] font-bold">
                        @if($op->cmp['selisih'] == 0)
                            <span class="text-gray-500 flex items-center bg-gray-50 px-2 py-1 rounded w-full justify-center">
                                Pendapatan sama dgn kemarin
                            </span>
                        @elseif($op->cmp['is_naik'])
                            <span class="text-green-600 flex items-center bg-green-50 border border-green-100 px-2 py-1 rounded w-full justify-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                Naik {{ number_format(abs($op->cmp['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($op->cmp['selisih']), 0, ',', '.') }})
                            </span>
                        @else
                            <span class="text-red-500 flex items-center bg-red-50 border border-red-100 px-2 py-1 rounded w-full justify-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                Turun {{ number_format(abs($op->cmp['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($op->cmp['selisih']), 0, ',', '.') }})
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-4 bg-white p-6 rounded-xl shadow-sm text-center text-gray-500 font-medium border border-dashed border-gray-300">
                    Belum ada data pegawai yang terdaftar.
                </div>
            @endforelse
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Riwayat Gaji Pegawai Per Hari</h3>
                <span class="text-xs bg-amber-100 text-amber-800 px-2 py-1 rounded-full font-semibold">10 Hari Terakhir</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pendapatan Kotor</th>

                            @if(isset($operators) && count($operators) > 0)
                                @foreach($operators as $pegawai)
                                    @php
                                        $totalBulanIniTabel = $pegawai->salary_type == 'percentage' ? ($pegawai->salary_amount / 100) * $kotorBulanIni : $pegawai->salary_amount * date('j');
                                    @endphp
                                    <th class="px-6 py-4 text-right tracking-wider border-l border-gray-100">
                                        <span class="block text-[10px] text-gray-400 font-bold uppercase mb-1">
                                            Total 1 Bln: <span class="text-emerald-500 font-black">Rp {{ number_format($totalBulanIniTabel, 0, ',', '.') }}</span>
                                        </span>
                                        <span class="block text-xs font-black text-indigo-600 uppercase">{{ $pegawai->name }}</span>
                                    </th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                   <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($riwayat_gaji ?? [] as $rg)
                            @php
                                // Paksa format tanggal jadi Y-m-d agar pasti terbaca oleh database
                                $tglTabelGaji = \Carbon\Carbon::parse($rg->tanggal)->format('Y-m-d');

                                // Panggil langsung DB::raw di dalam loop agar tidak error scope
                                $rumusParkirLoop = \Illuminate\Support\Facades\DB::raw('(CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END)');

                                // Hitung Parkir Murni hari itu
                                $parkirMurniTabel = \App\Models\Transaction::whereDate('entry_time', $tglTabelGaji)->sum($rumusParkirLoop);

                                // Hitung Kas Parkir hari itu (khusus kategori Parkiran)
                                $kasParkirTabel = \App\Models\FinancialReport::whereDate('tanggal', $tglTabelGaji)
                                                    ->where('jenis', 'pemasukan')
                                                    ->where('kategori', 'Parkiran')
                                                    ->sum('nominal');

                                // KOTOR GAJI: Hanya Parkir + Kas Parkir (TOILET TIDAK DIHITUNG)
                                $kotorGajiTabel = $parkirMurniTabel + $kasParkirTabel;
                            @endphp
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">
                                    {{ \Carbon\Carbon::parse($tglTabelGaji)->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    <span class="bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded border border-emerald-100 inline-block w-fit">
                                        Rp {{ number_format($kotorGajiTabel, 0, ',', '.') }}
                                    </span>
                                    {{-- Indikator visual jika ada kas masuk parkiran --}}
                                    @if($kasParkirTabel > 0)
                                        <span class="block text-[10px] text-emerald-600 font-bold mt-1">+ Kas: Rp {{ number_format($kasParkirTabel, 0, ',', '.') }}</span>
                                    @endif
                                </td>

                                @if(isset($operators) && count($operators) > 0)
                                    @foreach($operators as $pegawai)
                                        @php
                                            // Cek apakah ada edit manual kas untuk gaji pegawai di tanggal tsb
                                            $manualEditTabel = \App\Models\FinancialReport::whereDate('tanggal', $tglTabelGaji)
                                                ->where('kategori', 'Gaji Pegawai')
                                                ->where('keterangan', 'LIKE', '%' . $pegawai->name . '%')
                                                ->sum('nominal');

                                            if ($manualEditTabel > 0) {
                                                $gajiFinalTabel = $manualEditTabel;
                                                $statusGajiTabel = 'Manual';
                                            } else {
                                                $gajiFinalTabel = $pegawai->salary_type == 'percentage'
                                                    ? ($pegawai->salary_amount / 100) * $kotorGajiTabel
                                                    : $pegawai->salary_amount;
                                                $statusGajiTabel = 'Otomatis';
                                            }
                                        @endphp
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-black text-gray-700 text-base border-l border-gray-100">
                                            Rp {{ number_format($gajiFinalTabel, 0, ',', '.') }}

                                            @if($statusGajiTabel == 'Manual')
                                                <span class="block text-[9px] text-orange-500 uppercase mt-0.5">Edit Manual</span>
                                            @endif
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="px-6 py-8 text-center text-gray-400 italic">Belum ada riwayat gaji terekam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Aktivitas Kendaraan Terbaru</h3>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full font-semibold">Data disamarkan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu Masuk</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($recent_transactions ?? [] as $trx)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-800 tracking-wider bg-gray-100 px-3 py-1 rounded border border-gray-200">
                                        {{ Str::mask($trx->plate_number, '*', 4, 3) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap capitalize text-gray-600 font-medium">
                                    {{ $trx->vehicle_type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                    {{ $trx->entry_time ? \Carbon\Carbon::parse($trx->entry_time)->translatedFormat('H:i') . ' WIB' : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(strtolower($trx->status) == 'masuk')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">
                                            <span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                            Sedang Parkir
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                            Selesai / Keluar
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">Belum ada aktivitas terekam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-white border-t border-gray-100">
                {{ $recent_transactions->links() }}
            </div>
        </div>

        <div class="mb-6 mt-12 text-center md:text-left">
            <h2 class="text-2xl font-bold text-gray-800">Transparansi Kas Manual</h2>
            <p class="text-gray-500 text-sm mt-1">Laporan pemasukan dan pengeluaran operasional.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <h5 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Total Pemasukan Kas</h5>
                    <p class="text-2xl font-black text-green-600 mt-2">Rp {{ number_format($totalPemasukanKas ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50">📥</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <h5 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Total Pengeluaran Kas</h5>
                    <p class="text-2xl font-black text-red-600 mt-2">Rp {{ number_format($totalPengeluaranKas ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50">📤</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <h5 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Saldo Kas Akhir</h5>
                    <p class="text-2xl font-black text-blue-600 mt-2">Rp {{ number_format($saldoKas ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50">💰</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Riwayat Pendapatan Kendaraan</h3>
                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full font-semibold">Murni Parkir & Toilet</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Total Kendaraan Keluar</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total Omzet (Rp)</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-indigo-600 uppercase tracking-wider">Total Profit Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($revenue_transactions ?? [] as $trx)
                            @php
                                // Paksa format tanggal Y-m-d
                                $tglRev = \Carbon\Carbon::parse($trx->tanggal)->format('Y-m-d');

                                $rumusParkirLoop = \Illuminate\Support\Facades\DB::raw('(CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END)');
                                $rumusToiletLoop = \Illuminate\Support\Facades\DB::raw('IFNULL(toilet_fee, 0)');

                                // Hitung data murni & kas secara live per tanggal
                                $parkirMurniRev = \App\Models\Transaction::whereDate('entry_time', $tglRev)->sum($rumusParkirLoop);
                                $toiletRev = \App\Models\Transaction::whereDate('entry_time', $tglRev)->sum($rumusToiletLoop);

                                // Mengambil semua kas masuk & keluar di tanggal tersebut
                                $kasMasukRev = \App\Models\FinancialReport::whereDate('tanggal', $tglRev)->where('jenis', 'pemasukan')->sum('nominal');
                                $kasKeluarRev = \App\Models\FinancialReport::whereDate('tanggal', $tglRev)->where('jenis', 'pengeluaran')->sum('nominal');

                                // LOGIKA PENDAPATAN: Parkir + Toilet + Semua Kas
                                $omzetTabelRev = $parkirMurniRev + $toiletRev + $kasMasukRev;
                                $profitTabelRev = ($parkirMurniRev / 2) + $toiletRev + $kasMasukRev - $kasKeluarRev;
                            @endphp
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">
                                    {{ \Carbon\Carbon::parse($tglRev)->translatedFormat('d F Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600 font-medium">
                                    <span class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded border border-blue-100">
                                        {{ $trx->total_kendaraan }} Unit
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-gray-600 text-base">
                                    Rp {{ number_format($omzetTabelRev, 0, ',', '.') }}
                                    {{-- Menampilkan info jika ada tambahan dari kas --}}
                                    @if($kasMasukRev > 0)
                                        <span class="block text-[10px] text-green-500 font-normal mt-0.5">+ Kas: Rp {{ number_format($kasMasukRev, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-indigo-600 text-base md:text-lg bg-indigo-50/30">
                                    Rp {{ number_format($profitTabelRev, 0, ',', '.') }}
                                    {{-- Menampilkan info jika nominal sudah dipotong/ditambah kas --}}
                                    @if($kasMasukRev > 0 || $kasKeluarRev > 0)
                                        <span class="block text-[10px] text-indigo-400 font-normal mt-0.5">(Sdh termasuk Kas)</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">Belum ada pendapatan parkir terekam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
            @if(isset($revenue_transactions) && $revenue_transactions->hasPages())
                <div class="px-6 py-4 bg-white border-t border-gray-100">
                    {{ $revenue_transactions->links() }}
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Aktivitas Kas Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori / Keterangan</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($recent_financials ?? [] as $kas)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-medium">
                                    {{ \Carbon\Carbon::parse($kas->tanggal)->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span class="font-semibold">{{ $kas->kategori }}</span>
                                    @if($kas->keterangan)
                                        <span class="text-gray-400 ml-1">- {{ $kas->keterangan }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-sm">
                                    @if($kas->jenis == 'pemasukan')
                                        <span class="text-green-600">+ Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-red-600">- Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">Belum ada catatan kas masuk atau keluar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-white border-t border-gray-100">
                {{ $recent_financials->links() }}
            </div>
        </div>

    </main>

    <footer class="mt-4 py-6 text-center text-gray-400 text-sm">
        &copy; {{ date('Y') }} Sistem Informasi Parkir. Hak Cipta Dilindungi.
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Chart.defaults.font.family = "'Inter', sans-serif";

            const rawChartData = @json($chartData);

            if(rawChartData.harian && document.getElementById('chartHarianPublic')) {
                const ctxH = document.getElementById('chartHarianPublic').getContext('2d');
                new Chart(ctxH, {
                    type: 'line',
                    data: {
                        labels: rawChartData.harian.labels,
                        datasets: [{
                            label: 'Pendapatan Bersih (Rp)',
                            data: rawChartData.harian.data,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { callback: (value) => 'Rp ' + value.toLocaleString('id-ID') } } }
                    }
                });
            }

            if(rawChartData.bulanan && document.getElementById('chartBulananPublic')) {
                const ctxB = document.getElementById('chartBulananPublic').getContext('2d');
                new Chart(ctxB, {
                    type: 'bar',
                    data: {
                        labels: rawChartData.bulanan.labels,
                        datasets: [{
                            label: 'Pendapatan Bersih (Rp)',
                            data: rawChartData.bulanan.data,
                            backgroundColor: '#3b82f6',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { callback: (value) => 'Rp ' + value.toLocaleString('id-ID') } } }
                    }
                });
            }
        });
    </script>
</body>
</html>
