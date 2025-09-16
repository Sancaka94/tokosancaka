@extends('layouts.customer') {{-- Atau layout lain yang sesuai --}}

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">

                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                <h2 class="text-2xl font-semibold text-gray-800">
                    Selamat Datang di Dashboard Toko Anda!
                </h2>

                <p class="mt-2 text-gray-600">
                    Ini adalah halaman dashboard untuk toko Anda: <strong>{{ $store->name }}</strong>.
                </p>

                <div class="mt-6">
                    <a href="#" class="text-indigo-600 hover:text-indigo-900">Kelola Produk &rarr;</a>
                    <a href="#" class="ml-6 text-indigo-600 hover:text-indigo-900">Lihat Pesanan Masuk &rarr;</a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection