<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sancaka Express - Live Monitor SPX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        {{-- Header Publik --}}
        <div class="text-center mb-10">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Sancaka Express <span class="text-indigo-600">Monitor</span></h1>
            <p class="mt-2 text-sm text-gray-500">Live Dashboard Performa Scan Paket SPX</p>
            <div class="mt-4 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                <span class="flex w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span> Live Status
            </div>
        </div>

        {{-- Card Monitoring Dashboard --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            {{-- Card 1: Hari Ini --}}
            <div class="bg-indigo-50 rounded-xl p-6 border border-indigo-100 shadow-md transform transition hover:-translate-y-1">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-indigo-800 text-sm font-bold uppercase tracking-wider">Hari Ini</h3>
                    <div class="p-3 bg-indigo-200 rounded-lg text-indigo-700"><i class="fas fa-calendar-day fa-lg"></i></div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold text-gray-900">{{ $countToday }}</span>
                    <span class="text-sm font-medium text-gray-500">paket</span>
                </div>
                <div class="mt-4 text-sm flex items-center gap-1 font-medium">
                    @if($diffToday > 0)
                        <span class="text-green-700 bg-green-200 px-2 py-1 rounded"><i class="fas fa-arrow-up"></i> {{ $pctToday }}%</span>
                        <span class="text-gray-500 text-xs">(+{{ $diffToday }}) dr kemarin</span>
                    @elseif($diffToday < 0)
                        <span class="text-red-700 bg-red-200 px-2 py-1 rounded"><i class="fas fa-arrow-down"></i> {{ abs($pctToday) }}%</span>
                        <span class="text-gray-500 text-xs">({{ $diffToday }}) dr kemarin</span>
                    @else
                        <span class="text-gray-600 bg-gray-200 px-2 py-1 rounded"><i class="fas fa-minus"></i> 0%</span>
                        <span class="text-gray-500 text-xs">Sama spt kemarin</span>
                    @endif
                </div>
            </div>

            {{-- Card 2: Kemarin --}}
            <div class="bg-blue-50 rounded-xl p-6 border border-blue-100 shadow-md transform transition hover:-translate-y-1">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-blue-800 text-sm font-bold uppercase tracking-wider">Kemarin</h3>
                    <div class="p-3 bg-blue-200 rounded-lg text-blue-700"><i class="fas fa-history fa-lg"></i></div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold text-gray-900">{{ $countYesterday }}</span>
                    <span class="text-sm font-medium text-gray-500">paket</span>
                </div>
                <div class="mt-4 text-sm flex items-center gap-1 font-medium">
                    @if($diffYesterday > 0)
                        <span class="text-green-700 bg-green-200 px-2 py-1 rounded"><i class="fas fa-arrow-up"></i> {{ $pctYesterday }}%</span>
                        <span class="text-gray-500 text-xs">(+{{ $diffYesterday }}) dr H-2</span>
                    @elseif($diffYesterday < 0)
                        <span class="text-red-700 bg-red-200 px-2 py-1 rounded"><i class="fas fa-arrow-down"></i> {{ abs($pctYesterday) }}%</span>
                        <span class="text-gray-500 text-xs">({{ $diffYesterday }}) dr H-2</span>
                    @else
                        <span class="text-gray-600 bg-gray-200 px-2 py-1 rounded"><i class="fas fa-minus"></i> 0%</span>
                        <span class="text-gray-500 text-xs">Sama spt H-2</span>
                    @endif
                </div>
            </div>

            {{-- Card 3: Bulan Ini --}}
            <div class="bg-purple-50 rounded-xl p-6 border border-purple-100 shadow-md transform transition hover:-translate-y-1">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-purple-800 text-sm font-bold uppercase tracking-wider">Bulan Ini</h3>
                    <div class="p-3 bg-purple-200 rounded-lg text-purple-700"><i class="fas fa-calendar-alt fa-lg"></i></div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold text-gray-900">{{ $countThisMonth }}</span>
                    <span class="text-sm font-medium text-gray-500">paket</span>
                </div>
                <div class="mt-4 text-sm flex items-center gap-1 font-medium">
                    @if($diffMonth > 0)
                        <span class="text-green-700 bg-green-200 px-2 py-1 rounded"><i class="fas fa-arrow-up"></i> {{ $pctMonth }}%</span>
                        <span class="text-gray-500 text-xs">(+{{ $diffMonth }}) dr bln lalu</span>
                    @elseif($diffMonth < 0)
                        <span class="text-red-700 bg-red-200 px-2 py-1 rounded"><i class="fas fa-arrow-down"></i> {{ abs($pctMonth) }}%</span>
                        <span class="text-gray-500 text-xs">({{ $diffMonth }}) dr bln lalu</span>
                    @else
                        <span class="text-gray-600 bg-gray-200 px-2 py-1 rounded"><i class="fas fa-minus"></i> 0%</span>
                        <span class="text-gray-500 text-xs">Sama spt bln lalu</span>
                    @endif
                </div>
            </div>

            {{-- Card 4: Status Input (Copied vs Belum) --}}
            <div class="bg-emerald-50 rounded-xl p-6 border border-emerald-100 shadow-md flex flex-col justify-between transform transition hover:-translate-y-1">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-emerald-800 text-sm font-bold uppercase tracking-wider">Status Input</h3>
                    <div class="p-3 bg-emerald-200 rounded-lg text-emerald-700"><i class="fas fa-clipboard-check fa-lg"></i></div>
                </div>
                <div class="flex flex-col gap-3 mt-1">
                    <div class="flex justify-between items-center bg-white px-4 py-3 rounded-lg border border-emerald-200 shadow-sm">
                        <span class="text-sm font-semibold text-emerald-700"><i class="fas fa-check-double mr-1"></i> Telah Diproses</span>
                        <span class="text-xl font-bold text-gray-900">{{ $countCopied }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-white px-4 py-3 rounded-lg border border-red-200 shadow-sm">
                        <span class="text-sm font-semibold text-red-600"><i class="fas fa-minus-circle mr-1"></i> Belum Diproses</span>
                        <span class="text-xl font-bold text-gray-900">{{ $countNotCopied }}</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer Credit --}}
        <div class="text-center mt-12 text-sm text-gray-400">
            &copy; {{ date('Y') }} Sancaka Express. Data diperbarui secara real-time.
        </div>

    </div>

</body>
</html>