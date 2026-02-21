@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">

        <div class="px-6 py-5 bg-gray-50 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">Redeem Kode Lisensi</h2>
            <p class="text-sm text-gray-600 mt-1">Masukkan kode lisensi yang telah digenerate dari sistem untuk mengaktifkan fitur di subdomain apps dan admin.</p>
        </div>

        <div class="p-6">

            @if (session('success'))
                <div class="mb-5 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('superadmin.license.redeem') }}" method="POST">
                @csrf

                <div class="mb-6">
                    <label for="license_code" class="block text-sm font-semibold text-gray-700 mb-2">Kode Lisensi</label>

                    <input type="text"
                           name="license_code"
                           id="license_code"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 uppercase font-mono text-center tracking-widest text-lg shadow-sm transition duration-150 ease-in-out"
                           placeholder="XXXX-XXXX-XXXX-XXXX"
                           value="{{ old('license_code') }}"
                           required>

                    @error('license_code')
                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-gray-100">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors w-full sm:w-auto">
                        Validasi & Redeem
                    </button>
                </div>

                </form>
        </div>
    </div>
</div>
@endsection
