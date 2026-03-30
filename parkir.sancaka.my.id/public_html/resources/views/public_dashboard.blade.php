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
            if (!function_exists('hitungPersen')) {
                function hitungPersen($sekarang, $kemarin) {
                    $selisih = $sekarang - $kemarin;
                    $persen = $kemarin > 0 ? ($selisih / $kemarin) * 100 : ($sekarang > 0 ? 100 : 0);
                    return ['selisih' => $selisih, 'persen' => $persen, 'is_naik' => $selisih >= 0];
                }
            }

            // Hitung persentase untuk Card (Murni matematika perbandingan)
            $cmpPendapatanKemarin = hitungPersen($data['pendapatan_kemarin'] ?? 0, 0);
            $cmpPendapatan = hitungPersen($data['total_pendapatan'] ?? 0, $data['pendapatan_kemarin'] ?? 0);
            $cmpOmzet = hitungPersen($omzetHariIni ?? 0, $omzetKemarin ?? 0);
            $cmpBulan = hitungPersen($data['pendapatan_bulan_ini'] ?? 0, $data['pendapatan_bulan_kemarin'] ?? 0);

            $cmpParkirHari = hitungPersen($data['parkir_hari_ini'] ?? 0, $data['parkir_kemarin'] ?? 0);
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- KARTU 1: MOTOR --}}
            <div class="bg-gradient-to-br from-sky-400 to-sky-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-sky-100 text-xs font-bold uppercase tracking-wider mb-1">Motor (Hari Ini)</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $data['motor_masuk'] ?? 0 }} <span class="text-sm font-medium text-sky-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">🏍️</div>
                </div>
            </div>

            {{-- KARTU 2: SEPEDA --}}
            <div class="bg-gradient-to-br from-teal-400 to-teal-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-teal-100 text-xs font-bold uppercase tracking-wider mb-1">Sepeda (Hari Ini)</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $sepedaBiasaHariIni ?? 0 }} <span class="text-sm font-medium text-teal-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">🚲</div>
                </div>
            </div>

            {{-- KARTU 3: SEPEDA LISTRIK --}}
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-purple-100 text-xs font-bold uppercase tracking-wider mb-1">Sepeda Listrik</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $sepedaListrikHariIni ?? 0 }} <span class="text-sm font-medium text-purple-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">⚡</div>
                </div>
            </div>

            {{-- KARTU 4: PEGAWAI RSUD --}}
            <div class="bg-gradient-to-br from-rose-400 to-rose-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-rose-100 text-xs font-bold uppercase tracking-wider mb-1">Pegawai RSUD</h5>
                        <p class="text-2xl md:text-3xl font-black">{{ $pegawaiRsudHariIni ?? 0 }} <span class="text-sm font-medium text-rose-200">Unit</span></p>
                    </div>
                    <div class="text-4xl opacity-90">🏥</div>
                </div>
            </div>

            {{-- KARTU 5: PROFIT KEMARIN --}}
            @if($setting->tampil_card_harian)
            <div class="bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-orange-100 text-xs font-bold uppercase tracking-wider mb-1">Profit Kemarin</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($data['pendapatan_kemarin'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-90">⏳</div>
                </div>
            </div>
            @endif

            {{-- KARTU 6: PROFIT HARI INI --}}
            @if($setting->tampil_card_harian)
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-green-100 text-xs font-bold uppercase tracking-wider mb-1">Profit Hari Ini</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($data['total_pendapatan'] ?? 0, 0, ',', '.') }}</p>
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
            @endif

            {{-- KARTU 7: OMZET HARI INI --}}
            @if($setting->tampil_card_harian)
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-red-100 text-xs font-bold uppercase tracking-wider mb-1">Omzet Hari Ini</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($omzetHariIni ?? 0, 0, ',', '.') }}</p>
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
            @endif

            {{-- KARTU 8: PROFIT BULAN INI --}}
            @if($setting->tampil_card_bulanan)
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-blue-100 text-xs font-bold uppercase tracking-wider mb-1">Profit Bulan Ini</h5>
                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($data['pendapatan_bulan_ini'] ?? 0, 0, ',', '.') }}</p>
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
            @endif
        </div>

        {{-- PARKIR MURNI KARTU --}}
        <div class="mb-6 mt-12 text-center md:text-left">
            <h2 class="text-2xl font-bold text-gray-800">Profit Kendaraan (Murni Parkir)</h2>
            <p class="text-gray-500 text-sm mt-1">Total uang masuk murni dari tiket parkir tanpa tambahan toilet dan kas.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @if($setting->tampil_card_harian)
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-1">Parkir (Hari Ini)</h5>
                        <p class="text-2xl font-black">Rp {{ number_format($data['parkir_hari_ini'] ?? 0, 0, ',', '.') }}</p>
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
                        <p class="text-2xl font-black">Rp {{ number_format($data['parkir_kemarin'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-80">📅</div>
                </div>
            </div>
            @endif

            @if($setting->tampil_card_mingguan)
            <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-cyan-100 text-xs font-bold uppercase tracking-wider mb-1">Parkir (7 Hari)</h5>
                        <p class="text-2xl font-black">Rp {{ number_format($data['parkir_7_hari'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-80">📊</div>
                </div>
            </div>
            @endif

            @if($setting->tampil_card_bulanan)
            <div class="bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 rounded-xl shadow-md p-6 flex flex-col justify-center text-white transform transition duration-300 hover:scale-105 relative overflow-hidden">
                <div class="flex items-center justify-between z-10">
                    <div>
                        <h5 class="text-fuchsia-200 text-xs font-bold uppercase tracking-wider mb-1">Parkir (Bulan Ini)</h5>
                        <p class="text-2xl font-black">Rp {{ number_format($data['parkir_bulan_ini'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl opacity-80">📅</div>
                </div>
            </div>
            @endif
        </div>

        {{-- GRAFIK --}}
        @if($setting->tampil_grafik_harian || $setting->tampil_grafik_bulanan)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            @if($setting->tampil_grafik_harian)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
                    <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (7 Hari Terakhir)</h3>
                </div>
                <div class="p-4">
                    <canvas id="chartHarianPublic" height="250"></canvas>
                </div>
            </div>
            @endif

            @if($setting->tampil_grafik_bulanan)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
                    <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (6 Bulan Terakhir)</h3>
                </div>
                <div class="p-4">
                    <canvas id="chartBulananPublic" height="250"></canvas>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- GAJI PEGAWAI --}}
        <div class="mb-6 mt-12 flex justify-between items-end">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Estimasi Gaji Pegawai (Hari Ini)</h2>
                <p class="text-gray-500 text-sm mt-1">
                    @if($setting->gaji_hanya_dari_parkir)
                        Gaji dihitung otomatis MURNI dari Omzet Parkiran (Otomatis & Kas Manual) tanpa campuran lainnya.
                    @else
                        Gaji dihitung otomatis berdasarkan TOTAL KESELURUHAN Omzet Pendapatan (Parkir, Kas, Nginap, Toilet).
                    @endif
                </p>
            </div>
            <span class="hidden md:inline-block text-[10px] bg-purple-100 text-purple-800 px-3 py-1.5 rounded-full font-bold uppercase tracking-wider border border-purple-200">Dihitung Otomatis</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            @forelse($employeeSalaries ?? [] as $index => $op)
                @php
                    $colors = ['text-blue-600 bg-blue-100', 'text-emerald-600 bg-emerald-100', 'text-amber-600 bg-amber-100', 'text-rose-600 bg-rose-100'];
                    $color = $colors[$index % 4];
                    $totalBulanIni = $op->type == 'percentage' ? ($op->amount / 100) * ($omzetBulanIni ?? 0) : $op->amount * date('j');
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
                        <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Estimasi Diterima</p>
                        <p class="text-2xl font-black text-gray-800">Rp {{ number_format($op->earned, 0, ',', '.') }}</p>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-[11px] font-bold">
                        <span class="text-gray-500 flex items-center bg-gray-50 px-2 py-1 rounded w-full justify-center">
                            {{ $op->status }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="col-span-4 bg-white p-6 rounded-xl shadow-sm text-center text-gray-500 font-medium border border-dashed border-gray-300">
                    Belum ada data pegawai yang terdaftar.
                </div>
            @endforelse
        </div>

        {{-- TABEL RIWAYAT GAJI --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Riwayat Gaji Pegawai Per Hari</h3>
                <span class="text-xs bg-amber-100 text-amber-800 px-2 py-1 rounded-full font-semibold">
                    @if($setting->gaji_hanya_dari_parkir)
                        Gaji Murni Parkiran Saja
                    @else
                        Gaji Dari Total Keseluruhan
                    @endif
                </span>
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
                                        $totalBulanIniTabel = $pegawai->salary_type == 'percentage' ? ($pegawai->salary_amount / 100) * ($omzetBulanIni ?? 0) : $pegawai->salary_amount * date('j');
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
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">
                                    {{ \Carbon\Carbon::parse($rg->tanggal)->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    <span class="bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded border border-emerald-100 block w-fit font-black">
                                        Rp {{ number_format($rg->pendapatan_kotor, 0, ',', '.') }}
                                    </span>
                                </td>

                                @if(isset($operators) && count($operators) > 0)
                                    @foreach($operators as $pegawai)
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-black text-gray-700 text-base border-l border-gray-100">
                                            @if(isset($rg->gaji_pegawai[$pegawai->name]))
                                                Rp {{ number_format($rg->gaji_pegawai[$pegawai->name]['earned'], 0, ',', '.') }}
                                                @if($rg->gaji_pegawai[$pegawai->name]['status'] == 'Manual')
                                                    <span class="block text-[9px] text-orange-500 uppercase mt-0.5">Edit Manual</span>
                                                @endif
                                            @else
                                                -
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

        {{-- AKTIVITAS KENDARAAN TERBARU --}}
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
            <p class="text-gray-500 text-sm mt-1">Laporan semua pemasukan dan pengeluaran operasional.</p>
        </div>

        {{-- CARD DETAIL PEMASUKAN KAS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <h5 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Total Pemasukan Toilet</h5>
                    <p class="text-2xl font-black text-emerald-600 mt-2">Rp {{ number_format($totalPemasukanToilet ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50">🚻</div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <h5 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Total Pemasukan Nginap</h5>
                    <p class="text-2xl font-black text-indigo-600 mt-2">Rp {{ number_format($totalPemasukanNginap ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50">🌙</div>
            </div>
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

        {{-- TABEL RIWAYAT PENDAPATAN (GABUNGAN 100%) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Riwayat Pendapatan Total</h3>
                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full font-semibold">Parkir + Toilet + Kas Manual</span>
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
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">
                                    {{ \Carbon\Carbon::parse($trx->tanggal)->translatedFormat('d F Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600 font-medium">
                                    <span class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded border border-blue-100">
                                        {{ $trx->total_kendaraan }} Unit
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-gray-600 text-base">
                                    Rp {{ number_format($trx->total_omzet, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-indigo-600 text-base md:text-lg bg-indigo-50/30">
                                    Rp {{ number_format($trx->total_profit_bersih, 0, ',', '.') }}
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
