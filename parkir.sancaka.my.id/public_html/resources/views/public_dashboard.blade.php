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

        <div class="mb-8 text-center md:text-left border-b border-gray-200 pb-4 flex flex-col md:flex-row md:justify-between md:items-end">
            <div>
                <h1 class="text-3xl font-black text-gray-800 tracking-tight">Ringkasan Operasional</h1>
                <p class="text-gray-500 text-sm mt-1">Sistem Terintegrasi Otomatis (Live Update)</p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1.5 rounded-full border border-blue-200">
                    🕒 {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y - H:i') }} WIB
                </span>
            </div>
        </div>

        {{-- PROTEKSI VARIABEL & MAPPING WARNA --}}
        @php
            // Proteksi: Jika Controller lupa mengirim $setting, halaman tidak akan error
            $set = $setting ?? (object)[
                'tampil_grafik_harian' => true,
                'tampil_grafik_bulanan' => true,
                'gaji_hanya_dari_parkir' => true,
            ];

            // Mapping Warna untuk Kartu Builder
            $colorMap = [
                'blue' => 'from-blue-500 to-blue-600 text-blue-100',
                'green' => 'from-emerald-400 to-emerald-600 text-emerald-100',
                'red' => 'from-red-500 to-red-600 text-red-100',
                'orange' => 'from-orange-400 to-orange-500 text-orange-100',
                'indigo' => 'from-indigo-500 to-indigo-600 text-indigo-100',
                'fuchsia' => 'from-fuchsia-500 to-fuchsia-600 text-fuchsia-100',
                'slate' => 'from-slate-600 to-slate-700 text-slate-200',
            ];
        @endphp

        {{-- 1. KENDARAAN MASUK HARI INI (Statistik Operasional) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-12">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200">
                <span class="text-2xl block mb-1">🏍️</span>
                <p class="text-xl font-black text-gray-800">{{ $motorHariIni ?? 0 }}</p>
                <h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Motor</h5>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200">
                <span class="text-2xl block mb-1">🚗</span>
                <p class="text-xl font-black text-gray-800">{{ $mobilHariIni ?? 0 }}</p>
                <h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mobil</h5>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200">
                <span class="text-2xl block mb-1">🚲</span>
                <p class="text-xl font-black text-gray-800">{{ $sepedaBiasaHariIni ?? 0 }}</p>
                <h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sepeda</h5>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200">
                <span class="text-2xl block mb-1">⚡</span>
                <p class="text-xl font-black text-gray-800">{{ $sepedaListrikHariIni ?? 0 }}</p>
                <h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sepeda Listrik</h5>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200">
                <span class="text-2xl block mb-1">🏥</span>
                <p class="text-xl font-black text-gray-800">{{ $pegawaiRsudHariIni ?? 0 }}</p>
                <h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pegawai RSUD</h5>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 2. WIDGET BUILDER DINAMIS (KARTU OTOMATIS)                --}}
        {{-- ======================================================= --}}
        <div class="mb-6 flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Laporan Pendapatan Khusus</h2>
                <p class="text-gray-500 text-sm mt-1">Kartu di bawah ini dibuat secara dinamis oleh Admin melalui Dashboard Builder.</p>
            </div>
            <span class="hidden md:inline-block text-[10px] bg-sky-100 text-sky-800 px-3 py-1.5 rounded-full font-bold uppercase tracking-wider border border-sky-200">Live Engine</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            @if(isset($widgets) && count($widgets) > 0)
                @foreach($widgets as $w)
                    @php $themeClass = $colorMap[$w->color_theme] ?? $colorMap['blue']; @endphp

                    <div class="bg-gradient-to-br {{ $themeClass }} rounded-xl shadow-md p-6 flex flex-col justify-center transform transition duration-300 hover:scale-105 hover:shadow-xl relative overflow-hidden">
                        <div class="flex items-center justify-between z-10">
                            <div>
                                <h5 class="text-xs font-bold uppercase tracking-wider mb-1 opacity-90">{{ $w->title }}</h5>
                                <p class="text-2xl md:text-3xl font-black text-white">Rp {{ number_format($w->calculated_value ?? 0, 0, ',', '.') }}</p>
                            </div>
                            <div class="text-4xl opacity-90">{{ $w->icon }}</div>
                        </div>

                        <div class="mt-4 flex items-center gap-2 z-10">
                            <span class="text-[10px] font-bold bg-white/20 text-white px-2 py-1 rounded-md uppercase tracking-wider backdrop-blur-sm">
                                ⏳ {{ str_replace('_', ' ', $w->time_range) }}
                            </span>
                        </div>

                        <div class="absolute -right-4 -bottom-4 opacity-10 text-9xl pointer-events-none transform -rotate-12">{{ $w->icon }}</div>
                    </div>
                @endforeach
            @else
                <div class="col-span-4 bg-white p-10 rounded-2xl shadow-sm text-center text-gray-500 font-medium border border-dashed border-gray-300">
                    <span class="text-3xl block mb-2">📭</span>
                    Belum ada kartu pendapatan yang dirancang. <br>Admin dapat membuatnya di menu <b>Pengaturan Dashboard</b>.
                </div>
            @endif
        </div>

        {{-- ======================================================= --}}
        {{-- 3. GRAFIK (DIKONTROL OLEH SETTING DATABASE)             --}}
        {{-- ======================================================= --}}
        @if($set->tampil_grafik_harian || $set->tampil_grafik_bulanan)
            <div class="grid grid-cols-1 {{ ($set->tampil_grafik_harian && $set->tampil_grafik_bulanan) ? 'lg:grid-cols-2' : '' }} gap-6 mb-12">

                @if($set->tampil_grafik_harian)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                        <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (7 Hari Terakhir)</h3>
                    </div>
                    <div class="p-4">
                        <canvas id="chartHarianPublic" height="250"></canvas>
                    </div>
                </div>
                @endif

                @if($set->tampil_grafik_bulanan)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                        <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (6 Bulan Terakhir)</h3>
                    </div>
                    <div class="p-4">
                        <canvas id="chartBulananPublic" height="250"></canvas>
                    </div>
                </div>
                @endif
            </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 4. ESTIMASI GAJI PEGAWAI (TEKS DINAMIS)                 --}}
        {{-- ======================================================= --}}
        @if(isset($employeeSalaries))
        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Estimasi Gaji Pegawai (Hari Ini)</h2>
                <p class="text-gray-500 text-sm mt-1">
                    @if($set->gaji_hanya_dari_parkir)
                        Gaji dihitung otomatis <span class="font-bold text-indigo-600">MURNI DARI OMZET PARKIRAN Saja</span> (Tanpa Kas Nginap & Toilet).
                    @else
                        Gaji dihitung otomatis berdasarkan <span class="font-bold text-emerald-600">TOTAL SELURUH PENDAPATAN</span> (Parkir, Nginap, Toilet, dll).
                    @endif
                </p>
            </div>
            <span class="mt-3 md:mt-0 inline-block text-[10px] bg-purple-100 text-purple-800 px-3 py-1.5 rounded-full font-bold uppercase tracking-wider border border-purple-200">Dihitung Otomatis</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            @forelse($employeeSalaries as $index => $op)
                @php
                    $colors = ['text-blue-600 bg-blue-100', 'text-emerald-600 bg-emerald-100', 'text-amber-600 bg-amber-100', 'text-rose-600 bg-rose-100'];
                    $color = $colors[$index % 4];
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col relative overflow-hidden transform transition duration-300 hover:scale-105 hover:shadow-lg">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="{{ $color }} p-3 rounded-full flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 text-lg leading-none mb-1.5">{{ $op->name }}</h4>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $op->type == 'percentage' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-green-50 text-green-600 border border-green-100' }}">
                                {{ $op->type == 'percentage' ? 'Bagi Hasil ('.(float)$op->amount.'%)' : 'Gaji Tetap (Flat)' }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Estimasi Diterima Hari Ini</p>
                        <p class="text-2xl font-black text-gray-800">Rp {{ number_format($op->earned ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-[11px] font-bold">
                        <span class="text-gray-500 flex items-center bg-gray-50 border border-gray-100 px-2 py-1.5 rounded w-full justify-center">
                            {{ $op->status }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="col-span-4 bg-white p-6 rounded-xl shadow-sm text-center text-gray-500 font-medium border border-dashed border-gray-300">
                    Belum ada data pegawai / operator yang terdaftar di Master Data.
                </div>
            @endforelse
        </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 5. TABEL RIWAYAT GAJI PEGAWAI PER HARI                  --}}
        {{-- ======================================================= --}}
        @if(isset($riwayat_gaji))
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                <h3 class="font-bold text-gray-700 text-sm">Riwayat Gaji Pegawai Per Hari</h3>
                <span class="text-xs bg-amber-100 text-amber-800 px-3 py-1 rounded-full font-semibold border border-amber-200">
                    Sumber Kalkulasi: {{ $set->gaji_hanya_dari_parkir ? 'Omzet Parkir Saja' : 'Omzet Keseluruhan' }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Dasar Omzet Hitung</th>
                            @if(isset($operators) && count($operators) > 0)
                                @foreach($operators as $pegawai)
                                    <th class="px-6 py-4 text-right tracking-wider border-l border-gray-100">
                                        <span class="block text-[10px] text-gray-400 font-bold uppercase mb-1">Pendapatan</span>
                                        <span class="block text-xs font-black text-indigo-600 uppercase">{{ $pegawai->name }}</span>
                                    </th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($riwayat_gaji as $rg)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">
                                    {{ \Carbon\Carbon::parse($rg->tanggal)->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    <span class="bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded border border-emerald-100 block w-fit font-black">
                                        Rp {{ number_format($rg->pendapatan_kotor ?? 0, 0, ',', '.') }}
                                    </span>
                                </td>
                                @if(isset($operators) && count($operators) > 0)
                                    @foreach($operators as $pegawai)
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-black text-gray-700 text-base border-l border-gray-100">
                                            @if(isset($rg->gaji_pegawai[$pegawai->name]))
                                                Rp {{ number_format($rg->gaji_pegawai[$pegawai->name]['earned'], 0, ',', '.') }}
                                                @if($rg->gaji_pegawai[$pegawai->name]['status'] == 'Manual')
                                                    <span class="block text-[9px] text-orange-500 uppercase mt-0.5 font-bold">Edit Manual</span>
                                                @endif
                                            @else
                                                <span class="text-gray-300">-</span>
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
        @endif

        {{-- ======================================================= --}}
        {{-- 6. TABEL AKTIVITAS KENDARAAN TERBARU                    --}}
        {{-- ======================================================= --}}
        @if(isset($recent_transactions))
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Aktivitas Kendaraan Masuk / Keluar</h3>
                <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-bold border border-blue-200">Data Disamarkan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis Kendaraan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu Mulai</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status Transaksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($recent_transactions as $trx)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-black text-gray-700 tracking-wider bg-gray-100 px-3 py-1 rounded border border-gray-200 shadow-sm">
                                        {{ Str::mask($trx->plate_number, '*', 4, 3) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-600 capitalize">
                                    {{ $trx->vehicle_type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500 font-medium">
                                    {{ $trx->entry_time ? \Carbon\Carbon::parse($trx->entry_time)->translatedFormat('d M, H:i') . ' WIB' : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(strtolower($trx->status) == 'masuk')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                            <span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                            Sedang Parkir
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                            ✔ Selesai / Keluar
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">Belum ada aktivitas kendaraan terekam hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($recent_transactions->hasPages())
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    {{ $recent_transactions->links() }}
                </div>
            @endif
        </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 7. REKAPITULASI TRANSPARANSI KAS MANUAL                 --}}
        {{-- ======================================================= --}}
        <div class="mb-6 mt-12 text-center md:text-left">
            <h2 class="text-2xl font-bold text-gray-800">Transparansi Keuangan (Buku Kas)</h2>
            <p class="text-gray-500 text-sm mt-1">Laporan global akumulasi semua pemasukan dan pengeluaran manual.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between hover:shadow-md transition">
                <div>
                    <h5 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Total Akumulasi Toilet</h5>
                    <p class="text-2xl font-black text-emerald-600 mt-1">Rp {{ number_format($totalPemasukanToilet ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50 bg-emerald-50 w-16 h-16 flex items-center justify-center rounded-full">🚻</div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between hover:shadow-md transition">
                <div>
                    <h5 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Total Akumulasi Nginap</h5>
                    <p class="text-2xl font-black text-indigo-600 mt-1">Rp {{ number_format($totalPemasukanNginap ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50 bg-indigo-50 w-16 h-16 flex items-center justify-center rounded-full">🌙</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between hover:shadow-md transition">
                <div>
                    <h5 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Semua Pemasukan Kas</h5>
                    <p class="text-2xl font-black text-green-600 mt-1">Rp {{ number_format($totalPemasukanKas ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-3xl opacity-50">📥</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between hover:shadow-md transition">
                <div>
                    <h5 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Semua Pengeluaran Kas</h5>
                    <p class="text-2xl font-black text-red-600 mt-1">Rp {{ number_format($totalPengeluaranKas ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-3xl opacity-50">📤</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between border-l-4 border-l-blue-500 hover:shadow-md transition">
                <div>
                    <h5 class="text-blue-500 text-xs font-bold uppercase tracking-wider">Saldo Uang Kas Di Laci</h5>
                    <p class="text-2xl font-black text-blue-700 mt-1">Rp {{ number_format($saldoKas ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-3xl opacity-50">💰</div>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 8. TABEL RIWAYAT PENDAPATAN GLOBAL                      --}}
        {{-- ======================================================= --}}
        @if(isset($revenue_transactions))
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                <h3 class="font-bold text-gray-700 text-sm">Riwayat Pendapatan Global</h3>
                <span class="text-xs bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full font-bold border border-indigo-200">
                    Gabungan 100% dari Tiket Parkir + Buku Kas Masuk
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Buka</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Kendaraan Terlayani</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total Uang Kotor (Omzet)</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-indigo-700 uppercase tracking-wider bg-indigo-50/50">Total Profit Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($revenue_transactions as $trx)
                            <tr class="hover:bg-blue-50/30 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">
                                    {{ \Carbon\Carbon::parse($trx->tanggal)->translatedFormat('l, d F Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-600">
                                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-md border border-gray-200">
                                        {{ $trx->total_kendaraan }} Unit
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-gray-600 text-base">
                                    Rp {{ number_format($trx->total_omzet, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-indigo-600 text-lg bg-indigo-50/30">
                                    Rp {{ number_format($trx->total_profit_bersih, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">Belum ada pendapatan terekam dalam sistem.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($revenue_transactions->hasPages())
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    {{ $revenue_transactions->links() }}
                </div>
            @endif
        </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 9. TABEL CATATAN BUKU KAS TERBARU                       --}}
        {{-- ======================================================= --}}
        @if(isset($recent_financials))
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Entri Buku Kas Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Input</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori Catatan</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aliran Dana (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($recent_financials as $kas)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">
                                    {{ \Carbon\Carbon::parse($kas->tanggal)->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span class="font-black text-gray-700 bg-gray-100 px-2 py-0.5 rounded text-xs uppercase">{{ $kas->kategori }}</span>
                                    @if($kas->keterangan)
                                        <span class="text-gray-500 block mt-1 text-xs italic">"{{ $kas->keterangan }}"</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-sm">
                                    @if($kas->jenis == 'pemasukan')
                                        <span class="text-emerald-600 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-100">+ {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-rose-600 bg-rose-50 px-3 py-1 rounded-lg border border-rose-100">- {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">Belum ada catatan kas yang diinput.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($recent_financials->hasPages())
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    {{ $recent_financials->links() }}
                </div>
            @endif
        </div>
        @endif

    </main>

    <footer class="mt-4 py-8 bg-slate-800 text-center text-slate-400 text-sm border-t border-slate-700">
        <p class="font-bold tracking-wider text-slate-300">SISTEM INFORMASI PARKIR DIGITAL</p>
        <p class="mt-1 opacity-75">&copy; {{ date('Y') }} Sancaka Karya Hutama. Hak Cipta Dilindungi.</p>
    </footer>

    {{-- SCRIPT GRAFIK (HANYA JALAN JIKA DATA ADA) --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Chart.defaults.font.family = "'Inter', sans-serif";

            // Pastikan variabel json dari Controller dikirim. Jika tidak ada, grafik tidak dijalankan.
            @if(isset($chartData))
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
            @endif
        });
    </script>
</body>
</html>
