@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-8">
        <h1 class="text-3xl font-black text-gray-800 tracking-tight">Pengaturan Dashboard Public</h1>
        <p class="text-gray-500 mt-2">Atur rumus pembagian profit, dasar perhitungan gaji, dan elemen apa saja yang ingin ditampilkan di portal publik.</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm flex items-center" role="alert">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="font-bold">{{ session('success') }}</p>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-slate-800 px-6 py-4 border-b border-slate-700">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span>⚙️</span> Pengaturan Rumus Profit & Gaji
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-800">Bagi 2 Omzet Parkiran</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Uang parkir (sistem & manual) dibagi 2 untuk profit bos.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="parkir_dibagi_dua" value="0">
                            <input type="checkbox" name="parkir_dibagi_dua" value="1" class="sr-only peer" {{ $setting->parkir_dibagi_dua ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <hr class="border-gray-100">

                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-800">Bagi 2 Omzet Nginap</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Uang kas nginap dibagi 2 untuk profit bos.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="nginap_dibagi_dua" value="0">
                            <input type="checkbox" name="nginap_dibagi_dua" value="1" class="sr-only peer" {{ $setting->nginap_dibagi_dua ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <hr class="border-gray-100">

                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-800">Toilet Masuk Profit Bos 100%</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Seluruh pemasukan toilet jadi hak profit bersih (tidak dibagi).</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="toilet_masuk_profit" value="0">
                            <input type="checkbox" name="toilet_masuk_profit" value="1" class="sr-only peer" {{ $setting->toilet_masuk_profit ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <hr class="border-gray-100">

                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-indigo-700">Gaji Murni Dari Parkiran Saja</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Jika AKTIF: Persentase gaji dikali dari parkir saja.<br>Jika MATI: Gaji dikali dari Total Omzet Semua.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="gaji_hanya_dari_parkir" value="0">
                            <input type="checkbox" name="gaji_hanya_dari_parkir" value="1" class="sr-only peer" {{ $setting->gaji_hanya_dari_parkir ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-indigo-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-slate-100 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span>🖥️</span> Visibilitas Halaman Publik
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider mb-2">Tampilan Card Omzet</h3>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-700">Tampilkan Data Harian</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="tampil_card_harian" value="0">
                            <input type="checkbox" name="tampil_card_harian" value="1" class="sr-only peer" {{ $setting->tampil_card_harian ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-700">Tampilkan Data 7 Hari (Mingguan)</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="tampil_card_mingguan" value="0">
                            <input type="checkbox" name="tampil_card_mingguan" value="1" class="sr-only peer" {{ $setting->tampil_card_mingguan ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-700">Tampilkan Data Bulanan</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="tampil_card_bulanan" value="0">
                            <input type="checkbox" name="tampil_card_bulanan" value="1" class="sr-only peer" {{ $setting->tampil_card_bulanan ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>

                    <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider mb-2 mt-6 pt-4 border-t border-gray-100">Tampilan Grafik (Chart)</h3>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-700">Tampilkan Grafik Harian</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="tampil_grafik_harian" value="0">
                            <input type="checkbox" name="tampil_grafik_harian" value="1" class="sr-only peer" {{ $setting->tampil_grafik_harian ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-700">Tampilkan Grafik Bulanan</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="tampil_grafik_bulanan" value="0">
                            <input type="checkbox" name="tampil_grafik_bulanan" value="1" class="sr-only peer" {{ $setting->tampil_grafik_bulanan ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>

                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-black py-3 px-8 rounded-xl shadow-lg transform transition duration-200 hover:scale-105">
                💾 Simpan Konfigurasi Dashboard
            </button>
        </div>

    </form>
</div>
@endsection
