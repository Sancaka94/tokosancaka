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

        <div class="mb-8 text-center md:text-left border-b border-gray-200 pb-4">
            <h1 class="text-3xl font-black text-gray-800 tracking-tight">Ringkasan Operasional</h1>
            <p class="text-gray-500 text-sm mt-1">Live Engine Update: {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y - H:i') }} WIB</p>
        </div>

        {{-- 1. KENDARAAN MASUK (Top Statis) --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-12">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">🏍️</span><p class="text-xl font-black text-gray-800">{{ $motorHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Motor</h5></div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">🚗</span><p class="text-xl font-black text-gray-800">{{ $mobilHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mobil</h5></div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">🚲</span><p class="text-xl font-black text-gray-800">{{ $sepedaBiasaHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sepeda</h5></div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">⚡</span><p class="text-xl font-black text-gray-800">{{ $sepedaListrikHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sep Listrik</h5></div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transform hover:-translate-y-1 transition duration-200"><span class="text-2xl block mb-1">🏥</span><p class="text-xl font-black text-gray-800">{{ $pegawaiRsudHariIni ?? 0 }}</p><h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Peg. RSUD</h5></div>
        </div>

        {{-- 2. MASTER DASHBOARD BUILDER (GRID ALL-IN-ONE) --}}
        @php
            $colors = ['blue' => 'from-blue-500 to-blue-600', 'green' => 'from-emerald-400 to-emerald-600', 'red' => 'from-red-500 to-red-600', 'orange' => 'from-orange-400 to-orange-500', 'indigo' => 'from-indigo-500 to-indigo-600', 'fuchsia' => 'from-fuchsia-500 to-fuchsia-600', 'slate' => 'from-slate-600 to-slate-700'];
            $textColors = ['blue' => 'text-blue-600 bg-blue-50', 'green' => 'text-emerald-600 bg-emerald-50', 'red' => 'text-red-600 bg-red-50', 'orange' => 'text-orange-600 bg-orange-50', 'indigo' => 'text-indigo-600 bg-indigo-50', 'fuchsia' => 'text-fuchsia-600 bg-fuchsia-50', 'slate' => 'text-slate-600 bg-slate-50'];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">

            @forelse($widgets as $w)

                {{-- A. JIKA WIDGET = KARTU ANGKA --}}
                @if($w->display_type == 'card')
                    <div class="col-span-1 bg-gradient-to-br {{ $colors[$w->color_theme] ?? 'from-blue-500 to-blue-600' }} rounded-xl shadow-md p-6 flex flex-col justify-center relative overflow-hidden text-white transform transition duration-300 hover:scale-105 hover:shadow-xl">
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

                {{-- B. JIKA WIDGET = GRAFIK --}}
                @elseif($w->display_type == 'chart_line' || $w->display_type == 'chart_bar')
                    <div class="col-span-1 md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                            <h3 class="font-bold text-gray-700 text-sm">{{ $w->icon }} {{ $w->title }}</h3>
                        </div>
                        <div class="p-4 flex-1 min-h-[250px]">
                            <canvas id="chart_{{ $w->id }}"></canvas>
                        </div>
                    </div>

                {{-- C. JIKA WIDGET = GAJI PEGAWAI DINAMIS --}}
                @elseif($w->display_type == 'employee_salary')
                    <div class="col-span-1 md:col-span-2 lg:col-span-4 mt-4">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">{{ $w->icon }} {{ $w->title }}</h2>
                                <p class="text-gray-500 text-sm mt-1">Dasar perhitungan murni: Parkir {{ (float)$w->pct_parkir }}%, Nginap {{ (float)$w->pct_nginap }}%, Toilet {{ (float)$w->pct_toilet }}%</p>
                            </div>
                            <span class="mt-3 md:mt-0 inline-block text-[10px] bg-purple-100 text-purple-800 px-3 py-1.5 rounded-full font-bold uppercase tracking-wider border border-purple-200">
                                ⏳ Rentang Waktu: {{ str_replace('_', ' ', $w->time_range) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            @forelse($w->employee_data ?? [] as $index => $op)
                                @php $colorClass = array_values($textColors)[$index % count($textColors)]; @endphp
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col relative transform transition duration-300 hover:scale-105 hover:shadow-lg">
                                    <div class="flex items-start gap-4 mb-4">
                                        <div class="{{ $colorClass }} p-3 rounded-full flex-shrink-0">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-lg leading-none mb-1.5">{{ $op->name }}</h4>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                                {{ $op->type == 'percentage' ? 'Bagi Hasil ('.(float)$op->amount.'%)' : 'Flat' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Estimasi Hak Diterima</p>
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
                                    Belum ada data pegawai / operator.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif

            @empty
                <div class="col-span-1 md:col-span-4 bg-white p-12 rounded-2xl shadow-sm text-center text-gray-500 font-medium border border-dashed border-gray-300">
                    <span class="text-4xl block mb-3">📭</span>
                    Belum ada widget yang dibuat di Dashboard Builder.
                </div>
            @endforelse
        </div>

        {{-- 3. TABEL AKTIVITAS KENDARAAN (Statis) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center"><h3 class="font-bold text-gray-700 text-sm">Aktivitas Kendaraan</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead><tr class="bg-white"><th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Plat Nomor</th><th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Jenis</th><th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th></tr></thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($recent_transactions as $trx)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><span class="font-black text-gray-700 bg-gray-100 px-3 py-1 rounded border">{{ Str::mask($trx->plate_number, '*', 4, 3) }}</span></td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-600 capitalize">{{ $trx->vehicle_type }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(strtolower($trx->status) == 'masuk') <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2.5 py-1 rounded-md">Sedang Parkir</span>
                                    @else <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-md">Selesai</span> @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Belum ada aktivitas terekam.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <footer class="mt-4 py-8 bg-slate-800 text-center text-slate-400 text-sm border-t border-slate-700">
        <p class="font-bold tracking-wider text-slate-300">SISTEM INFORMASI PARKIR DIGITAL</p>
        <p class="mt-1 opacity-75">&copy; {{ date('Y') }} Sancaka Karya Hutama.</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Chart.defaults.font.family = "'Inter', sans-serif";

            // LOOP Murni mencetak Javascript Grafik berdasarkan Widget yang Anda buat di Admin
            @foreach($widgets as $w)
                @if($w->display_type == 'chart_line' || $w->display_type == 'chart_bar')
                    new Chart(document.getElementById('chart_{{ $w->id }}').getContext('2d'), {
                        type: '{{ $w->display_type == "chart_line" ? "line" : "bar" }}',
                        data: {
                            labels: @json($w->chart_labels),
                            datasets: [{
                                label: 'Pendapatan (Rp)',
                                data: @json($w->chart_data),
                                borderColor: '{{ $w->color_theme == "blue" ? "#3b82f6" : ($w->color_theme == "green" ? "#10b981" : ($w->color_theme == "orange" ? "#f97316" : "#f43f5e")) }}',
                                backgroundColor: '{{ $w->color_theme == "blue" ? "rgba(59, 130, 246, 0.2)" : ($w->color_theme == "green" ? "rgba(16, 185, 129, 0.2)" : ($w->color_theme == "orange" ? "rgba(249, 115, 22, 0.2)" : "rgba(244, 63, 94, 0.2)")) }}',
                                borderWidth: 3, fill: true, tension: 0.4, borderRadius: 6
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                    });
                @endif
            @endforeach
        });
    </script>
</body>
</html>
