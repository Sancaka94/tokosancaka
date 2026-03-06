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
            // ENGINE PERHITUNGAN OTOMATIS (Ditulis di Blade agar tidak perlu ubah Controller)
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

            $rumusTarif = DB::raw('(CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END) + IFNULL(toilet_fee, 0)');

            // --- 1. DATA KENDARAAN (HARI INI VS KEMARIN) ---
            $motorHariIni = $data['motor_masuk'] ?? 0;
            $motorKemarin = Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $yesterday)->count();

            $sepedaHariIni = $sepedaBiasaHariIni ?? 0;
            $sepedaKemarin = Transaction::whereDate('entry_time', $yesterday)->where('plate_number', 'LIKE', 'SPD-%')->count();

            $sepedaListrikHariIni = $sepedaListrikHariIni ?? 0;
            $sepedaListrikKemarin = Transaction::whereDate('entry_time', $yesterday)->where('plate_number', 'LIKE', 'SPL-%')->count();

            $pegawaiRsudHariIni = $pegawaiRsudHariIni ?? 0;
            $pegawaiRsudKemarin = Transaction::whereDate('entry_time', $yesterday)->where('plate_number', 'LIKE', 'RSUD-%')->count();

            // --- 2. DATA PENDAPATAN & OMZET ---
            $pendapatanHariIni = ($data['total_pendapatan'] ?? 0) / 2;
            $pendapatanKemarin = ($data['pendapatan_kemarin'] ?? 0) / 2;

            $omzetHariIni = $data['total_pendapatan'] ?? 0;
            $omzetKemarin = $data['pendapatan_kemarin'] ?? 0;

            $pendapatanBulanIni = ($data['pendapatan_bulan_ini'] ?? 0) / 2;
            $pendapatanBulanKemarin = ($data['pendapatan_bulan_kemarin'] ?? 0) / 2;

            // --- 3. DATA GAJI PEGAWAI (BARU) ---
            $gajiHariIni = collect($employeeSalaries ?? [])->sum('earned');

            // Kalkulasi Gaji Kemarin secara cerdas
            $operators = User::where('role', 'operator')->get();
            $lapGajiKemarin = FinancialReport::whereDate('tanggal', $yesterday)->where('kategori', 'Gaji Pegawai')->get();
            $pendKotorKemarin = Transaction::whereDate('entry_time', $yesterday)->sum($rumusTarif) + FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')->sum('nominal');

            $gajiKemarin = $operators->map(function($op) use ($lapGajiKemarin, $pendKotorKemarin) {
                $manual = $lapGajiKemarin->filter(fn($r) => stripos($r->keterangan, $op->name) !== false)->sum('nominal');
                if ($manual > 0) return $manual;
                if ($op->salary_type == 'percentage') return ($op->salary_amount / 100) * $pendKotorKemarin;
                return $op->salary_amount;
            })->sum();

            // --- 4. DATA PARKIR MURNI ---
            $parkirHariIni = $data['parkir_hari_ini'] ?? 0;
            $parkirKemarin = $data['parkir_kemarin'] ?? 0;
            $parkirH2 = Transaction::whereDate('entry_time', $h2)->sum($rumusTarif);

            $parkir7Hari = $data['parkir_7_hari'] ?? 0;
            $parkir7HariLalu = Transaction::whereDate('entry_time', '>=', Carbon::today()->subDays(13))
                                          ->whereDate('entry_time', '<=', Carbon::today()->subDays(7))
                                          ->sum($rumusTarif);

            $parkirBulanIni = $data['parkir_bulan_ini'] ?? 0;
            $parkirBulanLalu = Transaction::whereMonth('entry_time', $lastMonth->month)->whereYear('entry_time', $lastMonth->year)->sum($rumusTarif);

            // --- HELPER UNTUK MENGHITUNG PERSENTASE (DRY CODE) ---
            if (!function_exists('hitungPersen')) {
                function hitungPersen($sekarang, $kemarin) {
                    $selisih = $sekarang - $kemarin;
                    $persen = $kemarin > 0 ? ($selisih / $kemarin) * 100 : ($sekarang > 0 ? 100 : 0);
                    return ['selisih' => $selisih, 'persen' => $persen, 'is_naik' => $selisih >= 0];
                }
            }

            $cmpMotor = hitungPersen($motorHariIni, $motorKemarin);
            $cmpSepeda = hitungPersen($sepedaHariIni, $sepedaKemarin);
            $cmpListrik = hitungPersen($sepedaListrikHariIni, $sepedaListrikKemarin);
            $cmpRsud = hitungPersen($pegawaiRsudHariIni, $pegawaiRsudKemarin);

            $cmpPendapatan = hitungPersen($pendapatanHariIni, $pendapatanKemarin);
            $cmpOmzet = hitungPersen($omzetHariIni, $omzetKemarin);
            $cmpBulan = hitungPersen($pendapatanBulanIni, $pendapatanBulanKemarin);
            $cmpGaji = hitungPersen($gajiHariIni, $gajiKemarin);

            $cmpParkirHari = hitungPersen($parkirHariIni, $parkirKemarin);
            $cmpParkirKemarin = hitungPersen($parkirKemarin, $parkirH2);
            $cmpParkir7 = hitungPersen($parkir7Hari, $parkir7HariLalu);
            $cmpParkirBulan = hitungPersen($parkirBulanIni, $parkirBulanLalu);
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div class="bg-gradient-to-br from-sky-400 to-sky-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-sky-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Motor (Hari Ini)</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $motorHariIni }} <span class="text-sm font-medium text-sky-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">🏍️</div>
                </div>
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpMotor['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpMotor['persen']), 1, ',', '.') }}% (+{{ number_format(abs($cmpMotor['selisih']), 0, ',', '.') }} Unit)</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpMotor['persen']), 1, ',', '.') }}% (-{{ number_format(abs($cmpMotor['selisih']), 0, ',', '.') }} Unit)</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-teal-400 to-teal-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-teal-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Sepeda (Hari Ini)</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $sepedaHariIni }} <span class="text-sm font-medium text-teal-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">🚲</div>
                </div>
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpSepeda['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpSepeda['persen']), 1, ',', '.') }}% (+{{ number_format(abs($cmpSepeda['selisih']), 0, ',', '.') }} Unit)</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpSepeda['persen']), 1, ',', '.') }}% (-{{ number_format(abs($cmpSepeda['selisih']), 0, ',', '.') }} Unit)</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-purple-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Sepeda Listrik</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $sepedaListrikHariIni }} <span class="text-sm font-medium text-purple-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">⚡</div>
                </div>
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpListrik['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpListrik['persen']), 1, ',', '.') }}% (+{{ number_format(abs($cmpListrik['selisih']), 0, ',', '.') }} Unit)</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpListrik['persen']), 1, ',', '.') }}% (-{{ number_format(abs($cmpListrik['selisih']), 0, ',', '.') }} Unit)</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-rose-400 to-rose-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-rose-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Pegawai RSUD</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $pegawaiRsudHariIni }} <span class="text-sm font-medium text-rose-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">🏥</div>
                </div>
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpRsud['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpRsud['persen']), 1, ',', '.') }}% (+{{ number_format(abs($cmpRsud['selisih']), 0, ',', '.') }} Unit)</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpRsud['persen']), 1, ',', '.') }}% (-{{ number_format(abs($cmpRsud['selisih']), 0, ',', '.') }} Unit)</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-green-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Pendapatan Bersih</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-90">💵</div>
                </div>
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpPendapatan['is_naik'])
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
                        <h5 class="text-red-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Total Omzet Real</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-90">💰</div>
                </div>
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpOmzet['is_naik'])
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
                        <h5 class="text-blue-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Pendapatan Bulanan</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-90">📈</div>
                </div>
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpBulan['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpBulan['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($cmpBulan['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpBulan['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($cmpBulan['selisih']), 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-amber-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Total Gaji Pegawai</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($gajiHariIni, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-90">👷</div>
                </div>
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpGaji['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpGaji['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($cmpGaji['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpGaji['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($cmpGaji['selisih']), 0, ',', '.') }})</span>
                    @endif
                </div>
            </div>

        </div>

        <div class="mb-6 mt-12 text-center md:text-left">
            <h2 class="text-2xl font-bold text-gray-800">Pendapatan Kendaraan (Murni Parkir)</h2>
            <p class="text-gray-500 text-sm mt-1">Total uang masuk murni dari tiket parkir tanpa tambahan kas operasional.</p>
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
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpParkirHari['is_naik'])
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
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpParkirKemarin['is_naik'])
                        <svg class="w-3 h-3 text-white mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>Naik {{ number_format(abs($cmpParkirKemarin['persen']), 1, ',', '.') }}% (+Rp {{ number_format(abs($cmpParkirKemarin['selisih']), 0, ',', '.') }})</span>
                    @else
                        <svg class="w-3 h-3 text-red-200 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <span class="text-red-100">Turun {{ number_format(abs($cmpParkirKemarin['persen']), 1, ',', '.') }}% (-Rp {{ number_format(abs($cmpParkirKemarin['selisih']), 0, ',', '.') }})</span>
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
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpParkir7['is_naik'])
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
                <div class="mt-3 flex items-center text-xs font-semibold bg-white/20 w-fit px-2 py-1 rounded-md z-10">
                    @if($cmpParkirBulan['is_naik'])
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

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Estimasi Gaji Pegawai (Hari Ini)</h3>
                <span class="text-[10px] bg-purple-100 text-purple-800 px-2 py-1 rounded-full font-bold uppercase">Dihitung Otomatis</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Pegawai</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sistem Gaji</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Estimasi Diterima</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($employeeSalaries ?? [] as $salary)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-800">
                                    {{ $salary->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    @if($salary->type == 'percentage')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            Bagi Hasil ({{ (float)$salary->amount }}%)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                                            Flat (Rp {{ number_format($salary->amount, 0, ',', '.') }})
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-purple-600 text-lg">
                                    Rp {{ number_format($salary->earned, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">Data pegawai tidak tersedia.</td>
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
                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full font-semibold">Murni Parkir</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Total Kendaraan Keluar</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total Omzet (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($revenue_transactions ?? [] as $trx)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">
                                    {{ \Carbon\Carbon::parse($trx->tanggal)->translatedFormat('d F Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600 font-medium">
                                    <span class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded border border-blue-100">
                                        {{ $trx->total_kendaraan }} Unit
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-indigo-600 text-base md:text-lg">
                                    Rp {{ number_format($trx->total_omzet, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">Belum ada pendapatan parkir terekam.</td>
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

            // Chart Harian (Line)
            if(rawChartData.harian && document.getElementById('chartHarianPublic')) {
                const ctxH = document.getElementById('chartHarianPublic').getContext('2d');
                new Chart(ctxH, {
                    type: 'line',
                    data: {
                        labels: rawChartData.harian.labels,
                        datasets: [{
                            label: 'Pendapatan Bersih (Rp)',
                            data: rawChartData.harian.data,
                            borderColor: '#10b981', // Emerald green
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

            // Chart Bulanan (Bar)
            if(rawChartData.bulanan && document.getElementById('chartBulananPublic')) {
                const ctxB = document.getElementById('chartBulananPublic').getContext('2d');
                new Chart(ctxB, {
                    type: 'bar',
                    data: {
                        labels: rawChartData.bulanan.labels,
                        datasets: [{
                            label: 'Pendapatan Bersih (Rp)',
                            data: rawChartData.bulanan.data,
                            backgroundColor: '#3b82f6', // Blue
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
