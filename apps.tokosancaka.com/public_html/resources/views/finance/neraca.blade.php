@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">

    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900">Laporan Neraca</h1>
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
            <label class="text-sm font-bold text-slate-500">Posisi Per:</label>
            <input type="date" name="date" value="{{ $asOfDate }}" onchange="this.form.submit()" class="border-slate-300 rounded-lg text-sm font-bold shadow-sm">
        </form>
        <div class="flex gap-2">
            <a href="?date={{$asOfDate}}&export=excel" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm shadow hover:bg-emerald-700 transition">Download Excel</a>
            <a href="?date={{$asOfDate}}&export=pdf" class="bg-rose-600 text-white px-4 py-2 rounded-lg font-bold text-sm shadow hover:bg-rose-700 transition">Download PDF</a>
        </div>
    </div>

    {{-- BALANCE SHEET --}}
    <div class="bg-white shadow-xl rounded-lg overflow-hidden border border-slate-200">
        <div class="bg-slate-800 text-white p-6 text-center">
            <h2 class="text-2xl font-bold uppercase tracking-widest">Balance Sheet</h2>
            <p class="opacity-75 text-sm mt-1">Per Tanggal {{ date('d F Y', strtotime($asOfDate)) }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2">
            {{-- LEFT COLUMN: ASSETS --}}
            <div class="p-8 border-b md:border-b-0 md:border-r border-slate-200">
                <h3 class="text-lg font-black text-blue-600 uppercase mb-6 border-b-2 border-blue-600 pb-2 inline-block">Aktiva (Assets)</h3>
                <table class="w-full text-sm mb-6">
                    @foreach($assets as $a)
                    <tr class="border-b border-slate-50 hover:bg-slate-50">
                        <td class="py-3 text-slate-600">{{ $a->code }} - {{ $a->name }}</td>
                        <td class="py-3 text-right font-mono text-slate-800 font-medium">{{ number_format($a->balance) }}</td>
                    </tr>
                    @endforeach
                </table>
                <div class="bg-blue-50 rounded-lg p-4 flex justify-between items-center mt-auto">
                    <span class="font-bold text-blue-800 uppercase text-sm">Total Aset</span>
                    <span class="font-bold font-mono text-blue-800 text-lg">{{ number_format($totalAsset) }}</span>
                </div>
            </div>

            {{-- RIGHT COLUMN: LIABILITIES & EQUITY --}}
            <div class="p-8 bg-slate-50/30">
                {{-- Liabilities --}}
                <div class="mb-8">
                    <h3 class="text-lg font-black text-rose-600 uppercase mb-6 border-b-2 border-rose-600 pb-2 inline-block">Kewajiban (Liabilities)</h3>
                    <table class="w-full text-sm">
                        @foreach($liabilities as $l)
                        <tr class="border-b border-slate-50 hover:bg-slate-50">
                            <td class="py-3 text-slate-600">{{ $l->code }} - {{ $l->name }}</td>
                            <td class="py-3 text-right font-mono text-slate-800 font-medium">{{ number_format($l->balance) }}</td>
                        </tr>
                        @endforeach
                        <tr class="font-bold text-rose-700 bg-rose-50/50">
                            <td class="py-2 pl-2 text-xs uppercase">Total Kewajiban</td>
                            <td class="py-2 text-right font-mono pr-2">{{ number_format($totalLiability) }}</td>
                        </tr>
                    </table>
                </div>

                {{-- Equity --}}
                <div>
                    <h3 class="text-lg font-black text-emerald-600 uppercase mb-6 border-b-2 border-emerald-600 pb-2 inline-block">Modal (Equity)</h3>
                    <table class="w-full text-sm">
                        @foreach($equities as $e)
                        <tr class="border-b border-slate-50 hover:bg-slate-50">
                            <td class="py-3 text-slate-600">{{ $e->code }} - {{ $e->name }}</td>
                            <td class="py-3 text-right font-mono text-slate-800 font-medium">{{ number_format($e->balance) }}</td>
                        </tr>
                        @endforeach
                        <tr class="bg-yellow-50 border-l-4 border-yellow-400">
                            <td class="py-3 pl-3 text-slate-700 font-bold italic">Laba Rugi Berjalan</td>
                            <td class="py-3 text-right font-mono text-slate-800 font-bold pr-2">{{ number_format($labaBerjalan) }}</td>
                        </tr>
                    </table>
                </div>

                <div class="bg-slate-200 rounded-lg p-4 flex justify-between items-center mt-8">
                    <span class="font-bold text-slate-800 uppercase text-sm">Total Pasiva</span>
                    <span class="font-bold font-mono text-slate-800 text-lg">{{ number_format($totalLiability + $totalEquity) }}</span>
                </div>
            </div>
        </div>

        {{-- BALANCE CHECKER --}}
        @php $balanced = $totalAsset == ($totalLiability + $totalEquity); @endphp
        <div class="p-4 text-center {{ $balanced ? 'bg-emerald-600' : 'bg-rose-600' }} text-white">
            @if($balanced)
                <span class="font-bold flex justify-center items-center gap-2"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> BALANCE (SEIMBANG)</span>
            @else
                <span class="font-bold flex justify-center items-center gap-2"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> TIDAK BALANCE (Selisih: {{ number_format($totalAsset - ($totalLiability + $totalEquity)) }})</span>
            @endif
        </div>
    </div>
</div>
@endsection
