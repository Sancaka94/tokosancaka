@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">

    {{-- NAVIGATION --}}
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900">Laporan Laba Rugi</h1>
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

    {{-- FILTER & EXPORT --}}
    <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 mb-8 flex flex-wrap justify-between items-center gap-4">
        <form method="GET" class="flex gap-2 items-center">
            <select name="month" onchange="this.form.submit()" class="border-slate-300 rounded-lg text-sm font-bold shadow-sm">
                @for($i=1;$i<=12;$i++) <option value="{{$i}}" {{$i==$month?'selected':''}}>{{date('F', mktime(0,0,0,$i,10))}}</option> @endfor
            </select>
            <select name="year" onchange="this.form.submit()" class="border-slate-300 rounded-lg text-sm font-bold shadow-sm">
                @for($y=date('Y');$y>=2024;$y--) <option value="{{$y}}" {{$y==$year?'selected':''}}>{{$y}}</option> @endfor
            </select>
        </form>
        <div class="flex gap-2">
            <a href="?month={{$month}}&year={{$year}}&export=excel" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm shadow hover:bg-emerald-700 transition">Download Excel</a>
            <a href="?month={{$month}}&year={{$year}}&export=pdf" class="bg-rose-600 text-white px-4 py-2 rounded-lg font-bold text-sm shadow hover:bg-rose-700 transition">Download PDF</a>
        </div>
    </div>

    {{-- REPORT PAPER --}}
    <div class="bg-white rounded shadow-2xl overflow-hidden max-w-4xl mx-auto border-t-8 border-slate-800">
        <div class="p-10">
            <div class="text-center mb-10">
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-widest">Income Statement</h2>
                <p class="text-slate-500 font-medium mt-1">Periode: {{ date('F', mktime(0,0,0,$month,10)) }} {{ $year }}</p>
            </div>

            {{-- REVENUE --}}
            <div class="mb-8">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Pendapatan (Revenue)</h3>
                <table class="w-full text-sm">
                    @foreach($revenues as $r)
                    <tr>
                        <td class="py-2 text-slate-700 pl-4">{{ $r->code }} - {{ $r->name }}</td>
                        <td class="py-2 text-right font-mono text-slate-700">{{ number_format($r->balance) }}</td>
                    </tr>
                    @endforeach
                    <tr class="font-bold bg-emerald-50 text-emerald-800">
                        <td class="py-3 pl-4">TOTAL PENDAPATAN</td>
                        <td class="py-3 text-right font-mono pr-2">{{ number_format($totalRevenue) }}</td>
                    </tr>
                </table>
            </div>

            {{-- EXPENSES --}}
            <div class="mb-8">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Beban (Expenses)</h3>
                <table class="w-full text-sm">
                    @foreach($expenses as $e)
                    <tr>
                        <td class="py-2 text-slate-700 pl-4">{{ $e->code }} - {{ $e->name }}</td>
                        <td class="py-2 text-right font-mono text-slate-700">{{ number_format($e->balance) }}</td>
                    </tr>
                    @endforeach
                    <tr class="font-bold bg-rose-50 text-rose-800">
                        <td class="py-3 pl-4">TOTAL BEBAN</td>
                        <td class="py-3 text-right font-mono pr-2">({{ number_format($totalExpense) }})</td>
                    </tr>
                </table>
            </div>

            {{-- NET INCOME --}}
            <div class="border-t-2 border-slate-800 pt-4 mt-8 flex justify-between items-center">
                <h3 class="text-xl font-black text-slate-900 uppercase">Laba Bersih</h3>
                <div class="text-right">
                    <p class="text-2xl font-mono font-bold {{ $netIncome >= 0 ? 'text-slate-900' : 'text-rose-600' }}">
                        Rp {{ number_format($netIncome) }}
                    </p>
                    <div class="h-1 w-full border-t border-slate-900 mt-1"></div>
                    <div class="h-1 w-full border-t border-slate-900 mt-0.5"></div>
                </div>
            </div>
        </div>
        <div class="bg-slate-50 p-4 text-center text-xs text-slate-400 font-mono border-t border-slate-200">
            Generated by System at {{ date('Y-m-d H:i:s') }}
        </div>
    </div>
</div>
@endsection
