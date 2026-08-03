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
                <p class="text-gray-500 text-sm">Bergabunglah bersama kami tanpa biaya pendaftaran.</p>
            </div>

            {{-- Info Pendaftaran Gratis --}}
            <div class="bg-green-50 p-6 rounded-xl border border-green-200 mb-8 text-center shadow-sm">
                <i class="fas fa-gift text-4xl text-green-500 mb-3"></i>
                <p class="text-green-800 font-extrabold text-xl">Pendaftaran 100% GRATIS!</p>
                <p class="text-green-600 text-sm mt-2">Tidak ada syarat minimal saldo awal ataupun potongan biaya server.</p>
            </div>

            {{-- Form Action --}}
            <form action="{{ route('agent.register.process') }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('Yakin ingin mengupgrade akun menjadi Agen secara gratis?')"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex justify-center items-center gap-2">
                    <span>Daftar Jadi Agen Sekarang</span>
                    <i class="fas fa-rocket"></i>
                </button>
            </form>

        </div>
    </div>
</div>
@endsection
