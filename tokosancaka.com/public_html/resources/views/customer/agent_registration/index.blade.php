@extends('layouts.customer')

@section('title', 'Daftar Agen Resmi')

@section('content')
<div class="min-h-screen flex items-center justify-center py-10 bg-gray-50">
    <div class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-2 gap-8 bg-white rounded-3xl shadow-2xl overflow-hidden">
        
        {{-- Sisi Kiri: Informasi / Benefit --}}
        <div class="bg-blue-600 p-10 text-white flex flex-col justify-center relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl font-extrabold mb-4">Upgrade ke Agen Sancaka</h2>
                <p class="text-blue-100 mb-8 leading-relaxed">
                    Dapatkan akses eksklusif untuk mengatur harga sendiri, cetak struk dengan nama tokomu, dan raih keuntungan maksimal.
                </p>
                
                <ul class="space-y-4">
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check"></i>
                        </div>
                        <span>Atur Harga Jual Sendiri</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-print"></i>
                        </div>
                        <span>Cetak Struk Nama Toko</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <span>Markup Harga Otomatis</span>
                    </li>
                </ul>
            </div>
            
            {{-- Dekorasi Background --}}
            <i class="fas fa-store text-9xl absolute -bottom-10 -right-10 text-blue-700 opacity-20"></i>
        </div>

        {{-- Sisi Kanan: Form Eksekusi --}}
        <div class="p-10 flex flex-col justify-center">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold text-gray-800">Syarat & Ketentuan</h3>
                <p class="text-gray-500 text-sm">Cek saldo Anda sebelum melanjutkan.</p>
            </div>

            {{-- Info Saldo User --}}
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 mb-6">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-gray-600 text-sm">Saldo Anda Saat Ini</span>
                    <span class="font-bold {{ $user->saldo >= 2000000 ? 'text-green-600' : 'text-red-600' }}">
                        Rp {{ number_format($user->saldo, 0, ',', '.') }}
                    </span>
                </div>
                
                {{-- Progress Bar Visual --}}
                @php
                    $percent = min(($user->saldo / 2000000) * 100, 100);
                @endphp
                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                    <div class="bg-{{ $user->saldo >= 2000000 ? 'green' : 'red' }}-600 h-2.5 rounded-full transition-all duration-1000" style="width: {{ $percent }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-2 text-right">Target: Rp 2.000.000</p>
            </div>

            {{-- Rincian Biaya --}}
            <div class="space-y-3 mb-8 border-t border-b border-gray-100 py-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Syarat Minimal Saldo</span>
                    <span class="font-bold text-gray-800">Rp 2.000.000</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Biaya Server (Sekali Bayar)</span>
                    <span class="font-bold text-red-600">- Rp 100.000</span>
                </div>
                <div class="flex justify-between text-sm bg-blue-50 p-2 rounded">
                    <span class="text-blue-800 font-bold">Sisa Saldo Aktif</span>
                    <span class="font-bold text-blue-800">Rp 1.900.000</span>
                </div>
            </div>

            {{-- Form Action --}}
            <form action="{{ route('agent.register.process') }}" method="POST">
                @csrf
                
                @if($user->saldo >= 2000000)
                    <button type="submit" onclick="return confirm('Yakin ingin mendaftar? Saldo akan terpotong Rp 100.000')" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex justify-center items-center gap-2">
                        <span>Daftar Jadi Agen Sekarang</span>
                        <i class="fas fa-rocket"></i>
                    </button>
                @else
                    <a href="{{ route('topup.index') }}" class="block w-full bg-red-50 hover:bg-red-100 text-red-600 font-bold py-4 rounded-xl text-center border border-red-200 transition">
                        <i class="fas fa-wallet mr-2"></i> Top Up Saldo Dulu
                    </a>
                    <p class="text-center text-xs text-red-500 mt-3">
                        *Saldo Anda kurang <strong>Rp {{ number_format(2000000 - $user->saldo, 0, ',', '.') }}</strong>
                    </p>
                @endif
            </form>

        </div>
    </div>
</div>
@endsection