@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">

    {{-- HEADER & NAVIGATION --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Jurnal Umum</h1>
            <p class="text-slate-500 mt-1">Pencatatan transaksi harian & arus kas.</p>
        </div>
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

    {{-- ACTION BAR --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">

        {{-- 🔥 TAMBAHKAN BLOK ALERT INI DISINI 🔥 --}}
        @if(session('success'))
        <div class="lg:col-span-12">
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded shadow-sm flex items-center justify-between" role="alert">
                <div class="flex items-center">
                    <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="font-bold">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="lg:col-span-12">
            <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-700 p-4 rounded shadow-sm" role="alert">
                <p class="font-bold">❌ Error:</p>
                <p>{{ session('error') }}</p>
            </div>
        </div>
        @endif
        {{-- 🔥 BATAS ALERT 🔥 --}}

        {{-- FILTER PANEL --}}
        <div class="lg:col-span-8 bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <form method="GET" action="{{ route('finance.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Periode</label>
                    <select name="filter_type" id="filterType" onchange="toggleInputs()" class="w-full border-slate-300 rounded-lg text-sm font-semibold focus:ring-blue-500 focus:border-blue-500">
                        <option value="monthly" {{ request('filter_type') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        <option value="daily" {{ request('filter_type') == 'daily' ? 'selected' : '' }}>Harian</option>
                        <option value="yearly" {{ request('filter_type') == 'yearly' ? 'selected' : '' }}>Tahunan</option>
                        <option value="all" {{ request('filter_type') == 'all' ? 'selected' : '' }}>Semua</option>
                    </select>
                </div>

                {{-- Dynamic Inputs --}}
                <div id="inputDaily" class="w-full md:w-1/4 hidden">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date') ?? date('Y-m-d') }}" class="w-full border-slate-300 rounded-lg text-sm">
                </div>
                <div id="inputMonth" class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Bulan</label>
                    <select name="month" class="w-full border-slate-300 rounded-lg text-sm">
                        @for($i=1;$i<=12;$i++) <option value="{{$i}}" {{(request('month')??date('m'))==$i?'selected':''}}>{{date('F', mktime(0,0,0,$i,10))}}</option> @endfor
                    </select>
                </div>
                <div id="inputYear" class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Tahun</label>
                    <select name="year" class="w-full border-slate-300 rounded-lg text-sm">
                        @for($y=date('Y');$y>=2024;$y--) <option value="{{$y}}" {{(request('year')??date('Y'))==$y?'selected':''}}>{{$y}}</option> @endfor
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold text-sm shadow-sm transition">
                        Filter
                    </button>
                    <button type="submit" name="export" value="excel" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-sm shadow-sm" title="Excel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </button>
                    <button type="submit" name="export" value="pdf" class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-2 rounded-lg text-sm shadow-sm" title="PDF">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </button>
                </div>
            </form>
        </div>

        {{-- BUTTONS PANEL --}}
        <div class="lg:col-span-4 flex flex-col gap-3">
            <button onclick="toggleModal('modalInput')" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-lg font-bold shadow-md hover:shadow-lg transition flex justify-center items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Input Transaksi Manual
            </button>

            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('finance.sync') }}" class="flex justify-center items-center gap-2 bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100 px-4 py-2 rounded-lg text-xs font-bold transition">
                    🔄 Smart Sync
                </a>
                <a href="{{ route('finance.reset_sync') }}" onclick="return confirm('⚠️ RESET DATA\n\nYakin hapus jurnal otomatis dan tarik ulang?')" class="flex justify-center items-center gap-2 bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 px-4 py-2 rounded-lg text-xs font-bold transition">
                    🧨 Reset Ulang
                </a>
            </div>
        </div>
    </div>

    {{-- DATA TABLE --}}
    <div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-700">Riwayat Transaksi</h3>
            <span class="text-xs font-mono bg-slate-200 text-slate-600 px-2 py-1 rounded">{{ $journals->total() }} Data</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-xs">
                    <tr>
                        <th class="px-6 py-3 tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 tracking-wider">No. Bukti / Keterangan</th>
                        <th class="px-6 py-3 tracking-wider">Akun (COA)</th>
                        <th class="px-6 py-3 text-right tracking-wider">Debit</th>
                        <th class="px-6 py-3 text-right tracking-wider">Kredit</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($journals as $j)
                    <tr class="hover:bg-blue-50/50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-slate-600 font-medium">
                            {{ date('d/m/Y', strtotime($j->transaction_date)) }}
                        </td>
                        <td class="px-6 py-4 text-slate-800">
                            {{ $j->description }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $j->debit > 0 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $j->code }} - {{ $j->account_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-slate-700 {{ $j->debit > 0 ? 'font-bold' : 'text-slate-300' }}">
                            {{ $j->debit > 0 ? number_format($j->debit) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-slate-700 {{ $j->credit > 0 ? 'font-bold' : 'text-slate-300' }}">
                            {{ $j->credit > 0 ? number_format($j->credit) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('finance.destroy', $j->id) }}" onclick="return confirm('Hapus?')" class="text-slate-400 hover:text-rose-600 transition">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">
                            Data tidak ditemukan untuk periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
            {{ $journals->appends(request()->query())->onEachSide(1)->links() }}
        </div>
    </div>
</div>

{{-- MODAL INPUT MANUAL (CENTERED) --}}
<div id="modalInput" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="toggleModal('modalInput')"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-8">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-xl font-bold leading-6 text-slate-900" id="modal-title">Transaksi Manual</h3>
                            <div class="mt-4">
                                <form action="{{ route('finance.store') }}" method="POST" id="formManual" class="space-y-4">
                                    @csrf
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase">Tanggal</label>
                                            <input type="date" name="transaction_date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase">Nominal</label>
                                            <input type="number" name="amount" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="0" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Keterangan</label>
                                        <input type="text" name="description" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Biaya Listrik" required>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase">Akun</label>
                                            <select name="account_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                                @foreach($accounts as $acc) <option value="{{ $acc->id }}">{{ $acc->name }}</option> @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase">Tipe</label>
                                            <select name="type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                                <option value="debit">Pengeluaran (Debit)</option>
                                                <option value="credit">Pemasukan (Kredit)</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" onclick="document.getElementById('formManual').submit()" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Simpan</button>
                    <button type="button" onclick="toggleModal('modalInput')" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Batal</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleModal(id) { document.getElementById(id).classList.toggle('hidden'); }
    function toggleInputs() {
        let type = document.getElementById('filterType').value;
        ['inputDaily','inputMonth','inputYear'].forEach(id => document.getElementById(id).classList.add('hidden'));
        if(type=='daily') document.getElementById('inputDaily').classList.remove('hidden');
        if(type=='monthly') { document.getElementById('inputMonth').classList.remove('hidden'); document.getElementById('inputYear').classList.remove('hidden'); }
        if(type=='yearly') document.getElementById('inputYear').classList.remove('hidden');
    }
    document.addEventListener('DOMContentLoaded', toggleInputs);
</script>
@endsection
