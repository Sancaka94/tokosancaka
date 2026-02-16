@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header Greeting --}}
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-800">
            Halo, <span class="text-blue-600">{{ $user->name }}</span>
        </h2>
    </div>

    {{-- Card Container --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

        {{-- Card Header --}}
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <h3 class="text-lg font-semibold text-slate-700">Informasi Toko</h3>
        </div>

        {{-- Table Section --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm whitespace-nowrap">
                <tbody class="divide-y divide-slate-100">
                    {{-- Row 1: Nama Toko --}}
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-4 text-slate-500 font-medium bg-slate-50/30 w-1/3">
                            Nama Toko
                        </td>
                        <td class="px-6 py-4 text-slate-800 font-bold">
                            {{ $tenant->name }}
                        </td>
                    </tr>

                    {{-- Row 2: Subdomain --}}
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-4 text-slate-500 font-medium bg-slate-50/30">
                            Subdomain / URL
                        </td>
                        <td class="px-6 py-4">
                            <a href="https://{{ $tenant->subdomain }}.tokosancaka.com" target="_blank" class="text-blue-600 hover:text-blue-700 hover:underline flex items-center gap-1">
                                {{ $tenant->subdomain }}.tokosancaka.com
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        </td>
                    </tr>

                    {{-- Row 3: Status --}}
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-4 text-slate-500 font-medium bg-slate-50/30">
                            Status Langganan
                        </td>
                        <td class="px-6 py-4">
                            @if($tenant->status == 'active')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    {{ ucfirst($tenant->status) }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    {{ ucfirst($tenant->status) }}
                                </span>
                            @endif
                        </td>
                    </tr>

                    {{-- Row 4: Sisa Masa Aktif --}}
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-4 text-slate-500 font-medium bg-slate-50/30">
                            Sisa Masa Aktif
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold {{ $daysLeft < 7 ? 'text-red-600' : 'text-slate-700' }}">
                                {{ $daysLeft }} Hari
                            </span>
                            @if($daysLeft < 7)
                                <span class="ml-2 text-xs text-red-500 italic">(Segera perpanjang)</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Card Footer (Actions) --}}
        <div class="px-6 py-5 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center gap-3">
            <a href="https://{{ $tenant->subdomain }}.tokosancaka.com/dashboard"
               target="_blank"
               class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm shadow-blue-200 transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-blue-600">
               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
               Buka Dashboard Toko
            </a>

            <a href="{{ route('tenant.settings') }}"
               class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg shadow-sm transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
               <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
               Pengaturan Profil
            </a>
        </div>

    </div>
</div>
@endsection
