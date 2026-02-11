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

                    {{-- BAGIAN 1: HEADER (FOTO, NAMA, TOMBOL EDIT) --}}
                    <div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
                        <div class="flex items-center gap-4">
                            {{-- FOTO PROFIL --}}
                            <div class="h-20 w-20 rounded-full overflow-hidden border-2 border-indigo-100 shadow-sm relative shrink-0">
                                @if($user->logo)
                                    <img src="{{ asset('storage/' . $user->logo) }}" class="h-full w-full object-cover">
                                @else
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=E0E7FF&color=4F46E5&bold=true" class="h-full w-full object-cover">
                                @endif
                            </div>

                            {{-- NAMA & ROLE --}}
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ $user->name }}</h3>
                                <p class="text-sm font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded inline-block mt-1">
                                    {{ ucfirst(str_replace('_', ' ', $user->role ?? 'User')) }}
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-gray-800 text-white rounded-xl text-sm font-semibold hover:bg-gray-700 shadow-lg shadow-gray-200 transition-all flex items-center gap-2">
                            <i class="fas fa-edit"></i> Edit Profil
                        </a>
                    </div>

                    <hr class="border-gray-100 mb-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        {{-- BAGIAN 2: INFORMASI AKUN --}}
                        <div class="space-y-6">
                            <h4 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4">
                                <i class="fas fa-user-circle mr-2 text-gray-400"></i> Informasi Akun
                            </h4>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Nama Lengkap</label>
                                <div class="mt-1 text-base font-semibold text-gray-800">{{ $user->name }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Alamat Email</label>
                                <div class="mt-1 text-base font-semibold text-gray-800">{{ $user->email }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">No. WhatsApp</label>
                                <div class="mt-1 text-base font-semibold text-gray-800">
                                    @if($user->phone)
                                        <a href="https://wa.me/{{ $user->phone }}" target="_blank" class="text-green-600 hover:underline flex items-center gap-1">
                                            <i class="fab fa-whatsapp"></i> {{ $user->phone }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 italic">- Belum diatur -</span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Bergabung Sejak</label>
                                <div class="mt-1 text-base font-semibold text-gray-800">
                                    {{ $user->created_at->format('d F Y') }}
                                </div>
                            </div>
                        </div>

                        {{-- BAGIAN 3: ALAMAT PENGIRIMAN (DATA BARU) --}}
                        <div class="space-y-6">
                            <h4 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4">
                                <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i> Alamat Pengiriman
                            </h4>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Alamat Detail</label>
                                <div class="mt-1 text-base text-gray-800 leading-relaxed">
                                    {{ $user->address_detail ?? '-' }}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Kelurahan</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-800">{{ $user->village ?? '-' }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Kecamatan</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-800">{{ $user->district ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Kota/Kabupaten</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-800">{{ $user->regency ?? '-' }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Provinsi</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-800">{{ $user->province ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Kode Pos</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-800">{{ $user->postal_code ?? '-' }}</div>
                                </div>

                                {{-- KOORDINAT MAPS --}}
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Titik Koordinat</label>
                                    @if($user->latitude && $user->longitude)
                                        <a href="https://www.google.com/maps/search/?api=1&query={{ $user->latitude }},{{ $user->longitude }}"
                                           target="_blank"
                                           class="mt-1 inline-flex items-center gap-1 text-sm font-bold text-blue-600 hover:underline">
                                            <i class="fas fa-map-marked-alt"></i> Lihat di Peta
                                        </a>
                                        <div class="text-[10px] text-gray-400 mt-0.5">
                                            {{ substr($user->latitude, 0, 7) }}, {{ substr($user->longitude, 0, 7) }}
                                        </div>
                                    @else
                                        <div class="mt-1 text-sm text-gray-400 italic">- Belum diset -</div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
