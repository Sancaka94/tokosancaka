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
                    
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Informasi Akun</h3>
                        <a href="{{ route('profile.edit') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                            Edit Profil
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="border-b border-gray-200 pb-4">
                            <label class="block text-sm font-medium text-gray-500">Nama Lengkap</label>
                            <div class="mt-1 text-lg font-semibold text-gray-800">{{ $user->name }}</div>
                        </div>

                        <div class="border-b border-gray-200 pb-4">
                            <label class="block text-sm font-medium text-gray-500">Alamat Email</label>
                            <div class="mt-1 text-lg font-semibold text-gray-800">{{ $user->email }}</div>
                        </div>

                        <div class="border-b border-gray-200 pb-4">
                            <label class="block text-sm font-medium text-gray-500">Bergabung Sejak</label>
                            <div class="mt-1 text-lg font-semibold text-gray-800">
                                {{ $user->created_at->format('d M Y') }}
                            </div>
                        </div>

                        <div class="border-b border-gray-200 pb-4">
                            <label class="block text-sm font-medium text-gray-500">Status Akun</label>
                            <div class="mt-1">
                                @if($user->email_verified_at)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Terverifikasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Belum Verifikasi
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection