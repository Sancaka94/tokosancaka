@extends('layouts.customer')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
                    {{ __('Buka Toko Baru Anda') }}
                </h2>

                <p class="mb-4 text-gray-600">Lengkapi detail di bawah ini untuk membuat toko Anda.</p>

                <!-- Menampilkan error validasi umum -->
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.seller.register.submit') }}">
                    @csrf

                    <!-- Nama Toko -->
                    <div class="mb-4">
                        <label for="name" class="block font-medium text-sm text-gray-700">{{ __('Nama Toko') }}</label>
                        <input id="name" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Contoh: Sancaka Jaya Store" />
                    </div>

                    <!-- Deskripsi Toko -->
                    <div class="mb-4">
                        <label for="description" class="block font-medium text-sm text-gray-700">{{ __('Deskripsi Singkat Toko') }}</label>
                        <textarea id="description" name="description" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="4" required placeholder="Jelaskan tentang toko Anda, produk apa yang dijual, dll.">{{ old('description') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <a href="{{ route('customer.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                        
                        <!-- Menggunakan button standar -->
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Daftarkan Toko Saya') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
