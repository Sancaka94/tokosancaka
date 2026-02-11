@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Detail Profil Saya') }}
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- HEADER: FOTO & TOMBOL EDIT --}}
                    <div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
                        <div class="flex items-center gap-4">
                            {{-- LOGIC TAMPIL LOGO --}}
                            <div class="h-20 w-20 rounded-full overflow-hidden border-2 border-indigo-100 shadow-sm relative">
                                @if($user->logo)
                                    <img src="{{ asset('storage/' . $user->logo) }}" class="h-full w-full object-cover">
                                @else
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=E0E7FF&color=4F46E5&bold=true" class="h-full w-full object-cover">
                                @endif
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ $user->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $user->role ?? 'User' }}</p>
                            </div>
                        </div>

                        <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all flex items-center gap-2">
                            <i class="fas fa-edit"></i> Edit Profil & Password
                        </a>
                    </div>

                    <hr class="border-gray-100 mb-6">

                    {{-- GRID INFORMASI --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <div class="border-b border-gray-100 pb-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Nama Lengkap</label>
                            <div class="mt-1 text-base font-semibold text-gray-800">{{ $user->name }}</div>
                        </div>

                        <div class="border-b border-gray-100 pb-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Alamat Email</label>
                            <div class="mt-1 text-base font-semibold text-gray-800">{{ $user->email }}</div>
                        </div>

                        {{-- TAMBAHAN: NO WHATSAPP --}}
                        <div class="border-b border-gray-100 pb-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">No. WhatsApp</label>
                            <div class="mt-1 text-base font-semibold text-gray-800">
                                {{ $user->phone ?? '-' }}
                            </div>
                        </div>

                        <div class="border-b border-gray-100 pb-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Bergabung Sejak</label>
                            <div class="mt-1 text-base font-semibold text-gray-800">
                                {{ $user->created_at->format('d M Y') }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
