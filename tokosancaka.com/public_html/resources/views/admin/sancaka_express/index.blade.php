@extends('layouts.admin') {{-- Sesuaikan dengan layout admin Anda --}}

@section('content')
<div class="py-10 bg-[#fafafa] min-h-screen font-sans">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

        {{-- Header Ala Next.js --}}
        <div class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 tracking-tight">Pengaturan Sancaka Express</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola integrasi Mapbox API dan tentukan rumus perhitungan tarif pengiriman internal.</p>
        </div>

        {{-- Alert Area --}}
        <div id="alert-container" class="mb-6 space-y-4">
            @if (session('success'))
                <div class="p-4 bg-white border border-green-200 rounded-lg shadow-sm flex items-start text-sm">
                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span class="text-gray-700 font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div class="p-4 bg-white border border-red-200 rounded-lg shadow-sm flex items-start text-sm">
                    <svg class="w-5 h-5 mr-3 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    <div class="text-gray-700 font-medium">
                        @if (session('error')) {{ session('error') }} @endif
                        @if ($errors->any())
                            <ul class="list-disc list-inside mt-1">
                                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="bg-white shadow-sm border border-gray-200 sm:rounded-lg">
            <form method="POST" action="{{ route('admin.sancaka_express.update') }}">
                @csrf
                @method('PUT')

                {{-- BAGIAN 1: API MAPBOX (Landscape Grid) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-8 border-b border-gray-200">
                    <div class="md:col-span-1">
                        <h3 class="text-base font-medium text-gray-900">Integrasi Peta (Mapbox)</h3>
                        <p class="text-sm text-gray-500 mt-2">API Token Mapbox diperlukan untuk menghitung rute dan jarak (KM) secara presisi antara titik pengirim dan penerima.</p>
                        <a href="https://account.mapbox.com/access-tokens/" target="_blank" class="inline-flex items-center mt-3 text-sm text-red-600 hover:text-red-700 font-medium">
                            Dapatkan Token Mapbox &rarr;
                        </a>
                    </div>

                    <div class="md:col-span-2">
                        <label for="mapbox_token" class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                        <input id="mapbox_token" class="block w-full border-gray-200 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm py-2 px-3 transition-colors font-mono text-gray-600" type="text" name="mapbox_token" value="{{ old('mapbox_token', $mapboxToken) }}" required placeholder="pk.eyJ1Ijoic2FuY2FrYSIsImEi..." />
                        <p class="mt-2 text-xs text-gray-500">Pastikan token memiliki scope `directions` agar bisa menghitung rute kendaraan.</p>
                    </div>
                </div>

                {{-- BAGIAN 2: TARIF PENGIRIMAN (Landscape Grid) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-8 border-b border-gray-200">
                    <div class="md:col-span-1">
                        <h3 class="text-base font-medium text-gray-900">Kalkulasi Ongkos Kirim</h3>
                        <p class="text-sm text-gray-500 mt-2">Rumus dasar perhitungan sistem adalah:</p>
                        <div class="mt-3 p-3 bg-gray-50 rounded border border-gray-200 text-xs text-gray-700 font-mono">
                            Total = Tarif Dasar + (Jarak x Harga/KM) + (Berat x Harga/KG)
                        </div>
                        <p class="text-xs text-gray-400 mt-2">*Hasil akhir akan dibulatkan ke kelipatan Rp500 terdekat.</p>
                    </div>

                    <div class="md:col-span-2 space-y-6">

                        <div>
                            <label for="base_fare" class="block text-sm font-medium text-gray-700 mb-1">Tarif Dasar (Rp)</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input id="base_fare" class="block w-full pl-10 border-gray-200 rounded-md focus:border-red-500 focus:ring-red-500 sm:text-sm py-2 transition-colors" type="number" name="base_fare" value="{{ old('base_fare', $baseFare) }}" required min="0" />
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Biaya minimal pemanggilan kurir.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="price_per_km" class="block text-sm font-medium text-gray-700 mb-1">Harga Per Kilometer (Rp)</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <input id="price_per_km" class="block w-full pl-10 pr-12 border-gray-200 rounded-md focus:border-red-500 focus:ring-red-500 sm:text-sm py-2 transition-colors" type="number" name="price_per_km" value="{{ old('price_per_km', $pricePerKm) }}" required min="0" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">/ KM</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="price_per_kg" class="block text-sm font-medium text-gray-700 mb-1">Harga Per Kilogram (Rp)</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <input id="price_per_kg" class="block w-full pl-10 pr-12 border-gray-200 rounded-md focus:border-red-500 focus:ring-red-500 sm:text-sm py-2 transition-colors" type="number" name="price_per_kg" value="{{ old('price_per_kg', $pricePerKg) }}" required min="0" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">/ KG</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- FOOTER / ACTION BUTTON --}}
                <div class="bg-gray-50 px-8 py-5 flex items-center justify-end rounded-b-lg border-t border-gray-200">
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-gray-900 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
