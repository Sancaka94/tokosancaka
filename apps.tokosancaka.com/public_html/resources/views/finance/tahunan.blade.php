@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">

    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900">Perbandingan Tahunan</h1>
        <div class="flex bg-white rounded-lg p-1 shadow-sm border border-slate-200">
            <a href="{{ route('finance.index') }}" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm font-bold shadow-md">
                Jurnal
            </a>
            <a href="{{ route('finance.laba_rugi') }}" class="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-md text-sm font-medium transition">
                Laba Rugi
            </a>
            <a href="{{ route('finance.neraca') }}" class="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-md text-sm font-medium transition">
                Neraca
            </a>
            <a href="{{ route('finance.tahunan') }}" class="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-md text-sm font-medium transition">
                Tahunan
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 mb-8 flex flex-wrap justify-between items-center gap-4">
        <form method="GET" class="flex gap-2 items-center">
            <label class="text-sm font-bold text-slate-500">Pilih Tahun:</label>
            <select name="year" onchange="this.form.submit()" class="border-slate-300 rounded-lg text-sm font-bold shadow-sm">
                @for($y=date('Y');$y>=2024;$y--) <option value="{{$y}}" {{$y==$year?'selected':''}}>{{$y}}</option> @endfor
            </select>
        </form>
        <div class="flex gap-2">
            <a href="?year={{$year}}&export=excel" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm shadow hover:bg-emerald-700 transition">Download Excel</a>
            <a href="?year={{$year}}&export=pdf" class="bg-rose-600 text-white px-4 py-2 rounded-lg font-bold text-sm shadow hover:bg-rose-700 transition">Download PDF</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-800 text-white uppercase font-bold text-xs">
                    <tr>
                        <th class="px-6 py-4">Bulan</th>
                        <th class="px-6 py-4 text-right bg-slate-700/50">Omzet (Jual)</th>
                        <th class="px-6 py-4 text-right">Beban & HPP</th>
                        <th class="px-6 py-4 text-right bg-slate-900">Laba Bersih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php $totalBersih = 0; @endphp
                    @foreach($reportData as $row)
                    @php $totalBersih += $row['bersih']; @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 font-bold text-slate-700">{{ $row['bulan'] }}</td>
                        <td class="px-6 py-4 text-right font-mono text-emerald-600 bg-slate-50/50">{{ number_format($row['omzet']) }}</td>
                        <td class="px-6 py-4 text-right font-mono text-rose-500">({{ number_format($row['beban'] + $row['hpp']) }})</td>
                        <td class="px-6 py-4 text-right font-mono font-bold bg-slate-50 text-slate-900 border-l border-slate-200">
                            {{ number_format($row['bersih']) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-100 border-t-2 border-slate-300">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right font-black text-slate-600 uppercase">Total Profit Tahun {{ $year }}</td>
                        <td class="px-6 py-4 text-right font-mono font-black text-xl text-slate-900">{{ number_format($totalBersih) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
