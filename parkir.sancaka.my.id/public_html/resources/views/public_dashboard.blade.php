<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Info Parkir</title>
    <link rel="icon" type="image/jpeg" href="https://tokosancaka.com/storage/uploads/logo.jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap'); body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }</style>
</head>
<body class="text-gray-800">

    <nav class="bg-blue-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <div class="flex items-center gap-3">
                <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" class="h-8 w-8 bg-white rounded-full p-1">
                <span class="font-bold text-xl tracking-wide">Portal Info Parkir</span>
            </div>
            <a href="{{ route('login') }}" class="text-sm font-semibold bg-white text-blue-600 hover:bg-gray-100 px-4 py-2 rounded-full shadow-sm">Login &rarr;</a>
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

        {{-- ======================================================= --}}
        {{-- 1. KENDARAAN MASUK (Top Statis)                           --}}
        {{-- ======================================================= --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-12">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">🏍️</span><p class="text-xl font-black text-gray-800">{{ $motorHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Motor</h5></div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">🚗</span><p class="text-xl font-black text-gray-800">{{ $mobilHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mobil</h5></div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">🚲</span><p class="text-xl font-black text-gray-800">{{ $sepedaBiasaHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sepeda</h5></div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">⚡</span><p class="text-xl font-black text-gray-800">{{ $sepedaListrikHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sep Listrik</h5></div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">🏥</span><p class="text-xl font-black text-gray-800">{{ $pegawaiRsudHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Peg. RSUD</h5></div>
        </div>

        @php
            $colors = [
                'blue' => 'from-blue-500 to-blue-600',
                'green' => 'from-emerald-400 to-emerald-600',
                'red' => 'from-red-500 to-red-600',
                'orange' => 'from-orange-400 to-orange-500',
                'indigo' => 'from-indigo-500 to-indigo-600',
                'fuchsia' => 'from-fuchsia-500 to-fuchsia-600',
                'slate' => 'from-slate-600 to-slate-700'
            ];

            // PENGELOMPOKAN WIDGET BERDASARKAN TIPE AGAR RAPI
            // Satukan Kartu Angka & Kartu Gaji ke dalam 1 Grid
            $cards = collect($widgets ?? [])->whereIn('display_type', ['card', 'employee_salary']);
            $charts = collect($widgets ?? [])->whereIn('display_type', ['chart_line', 'chart_bar']);

            // Mengelompokkan berdasarkan kata pertama pada Judul
            $groupedCards = $cards->groupBy(function($w) {
                return strtoupper(explode(' ', trim($w->title))[0]);
            });
        @endphp

        {{-- ======================================================= --}}
        {{-- 2. KELOMPOK KARTU PENDAPATAN & GAJI (BERDASARKAN NAMA)  --}}
        {{-- ======================================================= --}}
        @if($groupedCards->count() > 0)
            <div class="mb-2 border-b border-gray-200 pb-2">
                <h2 class="text-2xl font-bold text-gray-800">Laporan Pendapatan & Gaji</h2>
                <p class="text-gray-500 text-sm mt-1">Kartu di bawah ini dikelompokkan secara otomatis dari Dashboard Builder.</p>
            </div>

            @foreach($groupedCards as $groupName => $groupCards)
                <div class="mt-8 mb-4">
                    <h3 class="text-lg font-black text-gray-700 border-l-4 border-blue-500 pl-3 uppercase tracking-widest bg-gray-100 py-1 w-fit pr-4 rounded-r-md shadow-sm">
                        KATEGORI: {{ $groupName }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    @foreach($groupCards as $w)

                        {{-- A. JIKA WIDGET = KARTU ANGKA BIASA --}}
                        @if($w->display_type == 'card')
                            <div class="bg-gradient-to-br {{ $colors[$w->color_theme] ?? 'from-blue-500 to-blue-600' }} rounded-xl shadow-md p-6 flex flex-col justify-center relative overflow-hidden text-white transform transition duration-300 hover:scale-105 hover:shadow-xl">
                                <div class="flex items-center justify-between z-10">
                                    <div>
                                        <h5 class="text-xs font-bold uppercase tracking-wider mb-1 opacity-90">{{ $w->title }}</h5>
                                        <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($w->calculated_value ?? 0, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="text-4xl opacity-90">{{ $w->icon }}</div>
                                </div>
                                <div class="mt-4 flex items-center gap-2 z-10">
                                    <span class="text-[10px] font-bold bg-white/20 px-2 py-1 rounded-md uppercase tracking-wider backdrop-blur-sm">
                                        ⏳ {{ str_replace('_', ' ', $w->time_range) }}
                                    </span>
                                </div>
                                <div class="absolute -right-4 -bottom-4 opacity-10 text-9xl transform -rotate-12 pointer-events-none">{{ $w->icon }}</div>
                            </div>

                        {{-- B. JIKA WIDGET = KARTU GAJI PEGAWAI --}}
                        @elseif($w->display_type == 'employee_salary' && isset($w->employee_data))
                            <div class="bg-gradient-to-br {{ $colors[$w->color_theme] ?? 'from-blue-500 to-blue-600' }} rounded-xl shadow-md p-6 flex flex-col justify-center relative overflow-hidden text-white transform transition duration-300 hover:scale-105 hover:shadow-xl ring-2 ring-white/30">
                                <div class="flex items-center justify-between z-10 mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="bg-white/20 p-2 rounded-full">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        </div>
                                        <h5 class="text-sm font-bold uppercase tracking-wider opacity-90 truncate max-w-[120px]">{{ $w->employee_data->name }}</h5>
                                    </div>
                                    <div class="text-2xl opacity-90">{{ $w->icon }}</div>
                                </div>
                                <div class="z-10 mt-1 border-t border-white/20 pt-2">
                                    <p class="text-[10px] font-bold uppercase tracking-wider mb-1 opacity-80">{{ $w->title }}</p>
                                    <p class="text-2xl md:text-3xl font-black text-yellow-300 drop-shadow-md">Rp {{ number_format($w->employee_data->earned ?? 0, 0, ',', '.') }}</p>
                                </div>
                                <div class="mt-4 flex items-center justify-between gap-2 z-10">
                                    <span class="text-[10px] font-bold bg-white/20 px-2 py-1 rounded-md uppercase tracking-wider backdrop-blur-sm">
                                        ⏳ {{ str_replace('_', ' ', $w->time_range) }}
                                    </span>
                                    <span class="text-[10px] font-bold bg-white/20 px-2 py-1 rounded-md uppercase tracking-wider backdrop-blur-sm">
                                        {{ $w->employee_data->type == 'percentage' ? (float)$w->employee_data->amount.'% POTONGAN' : 'FLAT' }}
                                    </span>
                                </div>
                                <div class="absolute -right-4 -bottom-4 opacity-10 text-9xl transform -rotate-12 pointer-events-none">{{ $w->icon }}</div>
                            </div>
                        @endif

                    @endforeach
                </div>
            @endforeach
        @else
            <div class="bg-white p-12 rounded-2xl shadow-sm text-center text-gray-500 font-medium border border-dashed border-gray-300 mt-8">
                <span class="text-4xl block mb-3">📭</span>
                Belum ada Kartu Pendapatan atau Gaji Pegawai yang dibuat di Dashboard Builder.
            </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 3. KELOMPOK GRAFIK (CHARTS)                             --}}
        {{-- ======================================================= --}}
        @if($charts->count() > 0)
            <div class="mb-4 mt-12 border-b border-gray-200 pb-2">
                <h2 class="text-2xl font-bold text-gray-800">Analisis Visual (Grafik)</h2>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
                @foreach($charts as $w)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                            <h3 class="font-bold text-gray-700 text-sm">{{ $w->icon }} {{ $w->title }}</h3>
                        </div>
                        <div class="p-4 flex-1 min-h-[250px] w-full">
                            <canvas id="chart_{{ $w->id }}"></canvas>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 4. TABEL TRANSPARANSI KAS MANUAL                        --}}
        {{-- ======================================================= --}}
        <div class="mb-6 mt-16 text-center md:text-left border-b border-gray-200 pb-2">
            <h2 class="text-2xl font-bold text-gray-800">Transparansi Keuangan (Buku Kas)</h2>
            <p class="text-gray-500 text-sm mt-1">Laporan global akumulasi semua pemasukan dan pengeluaran manual.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between hover:shadow-md transition">
                <div><h5 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Total Akumulasi Toilet</h5><p class="text-2xl font-black text-emerald-600 mt-1">Rp {{ number_format($totalPemasukanToilet ?? 0, 0, ',', '.') }}</p></div>
                <div class="text-4xl opacity-50 bg-emerald-50 w-16 h-16 flex items-center justify-center rounded-full">🚻</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between hover:shadow-md transition">
                <div><h5 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Total Akumulasi Nginap</h5><p class="text-2xl font-black text-indigo-600 mt-1">Rp {{ number_format($totalPemasukanNginap ?? 0, 0, ',', '.') }}</p></div>
                <div class="text-4xl opacity-50 bg-indigo-50 w-16 h-16 flex items-center justify-center rounded-full">🌙</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between hover:shadow-md transition">
                <div><h5 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Semua Pemasukan Kas</h5><p class="text-2xl font-black text-green-600 mt-1">Rp {{ number_format($totalPemasukanKas ?? 0, 0, ',', '.') }}</p></div>
                <div class="text-3xl opacity-50">📥</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between hover:shadow-md transition">
                <div><h5 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Semua Pengeluaran Kas</h5><p class="text-2xl font-black text-red-600 mt-1">Rp {{ number_format($totalPengeluaranKas ?? 0, 0, ',', '.') }}</p></div>
                <div class="text-3xl opacity-50">📤</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between border-l-4 border-l-blue-500 hover:shadow-md transition">
                <div><h5 class="text-blue-500 text-xs font-bold uppercase tracking-wider">Saldo Uang Kas Di Laci</h5><p class="text-2xl font-black text-blue-700 mt-1">Rp {{ number_format($saldoKas ?? 0, 0, ',', '.') }}</p></div>
                <div class="text-3xl opacity-50">💰</div>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 5. TABEL AKTIVITAS KENDARAAN                            --}}
        {{-- ======================================================= --}}
        @if(isset($recent_transactions) && count($recent_transactions) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Aktivitas Kendaraan Masuk / Keluar</h3>
                <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-bold border border-blue-200">Data Disamarkan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead><tr class="bg-white"><th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Plat Nomor</th><th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis</th><th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu Mulai</th><th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th></tr></thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($recent_transactions as $trx)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap"><span class="font-black text-gray-700 tracking-wider bg-gray-100 px-3 py-1 rounded border shadow-sm">{{ Str::mask($trx->plate_number, '*', 4, 3) }}</span></td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-600 capitalize">{{ $trx->vehicle_type }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500 font-medium">{{ $trx->entry_time ? \Carbon\Carbon::parse($trx->entry_time)->translatedFormat('d M, H:i') . ' WIB' : '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(strtolower($trx->status) == 'masuk') <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-yellow-100 text-yellow-800"><span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>Sedang Parkir</span>
                                    @else <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-green-100 text-green-800">✔ Selesai</span> @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($recent_transactions->hasPages())<div class="px-6 py-4 bg-gray-50 border-t border-gray-100">{{ $recent_transactions->links() }}</div>@endif
        </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 6. TABEL BUKU KAS                                       --}}
        {{-- ======================================================= --}}
        @if(isset($recent_financials) && count($recent_financials) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4"><h3 class="font-bold text-gray-700 text-sm">Entri Buku Kas Terbaru</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead><tr class="bg-white"><th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Input</th><th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori Catatan</th><th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aliran Dana (Rp)</th></tr></thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($recent_financials as $kas)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">{{ \Carbon\Carbon::parse($kas->tanggal)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600"><span class="font-black text-gray-700 bg-gray-100 px-2 py-0.5 rounded text-xs uppercase">{{ $kas->kategori }}</span> @if($kas->keterangan)<span class="text-gray-500 block mt-1 text-xs italic">"{{ $kas->keterangan }}"</span>@endif</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-sm">
                                    @if($kas->jenis == 'pemasukan') <span class="text-emerald-600 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-100">+ {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                                    @else <span class="text-rose-600 bg-rose-50 px-3 py-1 rounded-lg border border-rose-100">- {{ number_format($kas->nominal, 0, ',', '.') }}</span> @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($recent_financials->hasPages())<div class="px-6 py-4 bg-gray-50 border-t border-gray-100">{{ $recent_financials->links() }}</div>@endif
        </div>
        @endif

    </main>

    <footer class="mt-4 py-8 bg-slate-800 text-center text-slate-400 text-sm border-t border-slate-700">
        <p class="font-bold tracking-wider text-slate-300">SISTEM INFORMASI PARKIR DIGITAL</p>
        <p class="mt-1 opacity-75">&copy; {{ date('Y') }} Sancaka Karya Hutama.</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Chart.defaults.font.family = "'Inter', sans-serif";

            // Mencetak Grafik dari Collection Charts
            @if(isset($charts) && $charts->count() > 0)
                @foreach($charts as $w)
                    new Chart(document.getElementById('chart_{{ $w->id }}').getContext('2d'), {
                        type: '{{ $w->display_type == "chart_line" ? "line" : "bar" }}',
                        data: {
                            labels: @json($w->chart_labels),
                            datasets: [{
                                label: 'Pendapatan (Rp)',
                                data: @json($w->chart_data),
                                borderColor: '{{ $w->color_theme == "blue" ? "#3b82f6" : ($w->color_theme == "green" ? "#10b981" : ($w->color_theme == "orange" ? "#f97316" : ($w->color_theme == "indigo" ? "#6366f1" : "#f43f5e"))) }}',
                                backgroundColor: '{{ $w->color_theme == "blue" ? "rgba(59, 130, 246, 0.2)" : ($w->color_theme == "green" ? "rgba(16, 185, 129, 0.2)" : ($w->color_theme == "orange" ? "rgba(249, 115, 22, 0.2)" : ($w->color_theme == "indigo" ? "rgba(99, 102, 241, 0.2)" : "rgba(244, 63, 94, 0.2)"))) }}',
                                borderWidth: 3, fill: true, tension: 0.4, borderRadius: 6
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                    });
                @endforeach
            @endif
        });
    </script>
</body>
</html>
