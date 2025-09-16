@extends('layouts.admin')

@section('content')
<div class="bg-gray-800 p-6 rounded-lg shadow-lg">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <h1 class="text-3xl font-bold text-white">Laporan Laba Rugi</h1>
    </div>

    <!-- Filter Tanggal -->
    <div class="bg-gray-900 p-4 rounded-lg mb-6">
        <form method="GET" action="{{ route('admin.laporan.labaRugi') }}">
            <div class="flex items-center space-x-4">
                <div>
                    <label for="start_date" class="text-sm text-gray-400">Dari Tanggal:</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="bg-gray-700 text-white rounded-md border-gray-600 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="end_date" class="text-sm text-gray-400">Sampai Tanggal:</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="bg-gray-700 text-white rounded-md border-gray-600 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Konten Laporan -->
    <div class="bg-gray-900 p-6 rounded-lg">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-semibold text-white">PT. Sancaka Express</h2>
            <h3 class="text-xl text-gray-300">Laporan Laba Rugi</h3>
            <p class="text-gray-400">Untuk Periode yang Berakhir pada {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</p>
        </div>

        <!-- Pendapatan -->
        <div class="mb-6">
            <h4 class="text-lg font-semibold text-indigo-400 mb-2 border-b border-gray-700 pb-1">Pendapatan</h4>
            <table class="w-full text-sm text-gray-300">
                <tbody>
                    @forelse ($pendapatan as $akun)
                        @php $subtotal = $akun->journalTransactions->sum('credit'); @endphp
                        @if($subtotal > 0)
                        <tr>
                            <td class="py-2 pl-4">{{ $akun->nama }}</td>
                            <td class="py-2 pr-4 text-right font-mono">{{ number_format($subtotal / 100, 2, ',', '.') }}</td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td class="py-2 pl-4 italic text-gray-500">Tidak ada pendapatan tercatat.</td>
                            <td class="py-2 pr-4 text-right font-mono">0,00</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-bold border-t border-gray-700">
                        <td class="py-2 pl-4">Total Pendapatan</td>
                        <td class="py-2 pr-4 text-right font-mono">{{ number_format($totalPendapatan / 100, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Beban -->
        <div class="mb-6">
            <h4 class="text-lg font-semibold text-indigo-400 mb-2 border-b border-gray-700 pb-1">Beban</h4>
             <table class="w-full text-sm text-gray-300">
                <tbody>
                    @forelse ($beban as $akun)
                        @php $subtotal = $akun->journalTransactions->sum('debit'); @endphp
                        @if($subtotal > 0)
                        <tr>
                            <td class="py-2 pl-4">{{ $akun->nama }}</td>
                            <td class="py-2 pr-4 text-right font-mono">({{ number_format($subtotal / 100, 2, ',', '.') }})</td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td class="py-2 pl-4 italic text-gray-500">Tidak ada beban tercatat.</td>
                            <td class="py-2 pr-4 text-right font-mono">(0,00)</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-bold border-t border-gray-700">
                        <td class="py-2 pl-4">Total Beban</td>
                        <td class="py-2 pr-4 text-right font-mono">({{ number_format($totalBeban / 100, 2, ',', '.') }})</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Laba / Rugi Bersih -->
        <div class="border-t-2 border-gray-700 pt-4 mt-6">
            <div class="flex justify-between items-center text-white text-xl font-bold">
                {{-- PERBAIKAN: Menggunakan variabel $labaRugi --}}
                <span>{{ $labaRugi >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</span>
                <span class="font-mono {{ $labaRugi >= 0 ? 'text-green-400' : 'text-red-400' }}">
                    Rp {{ number_format(abs($labaRugi) / 100, 2, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection

