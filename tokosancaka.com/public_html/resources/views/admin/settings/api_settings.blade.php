@extends('layouts.admin')

@section('title', 'Konfigurasi API')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>

<div class="min-h-screen bg-zinc-50 py-8 px-4 sm:px-6 lg:px-8" x-data="apiSettings" x-cloak>
    <div class="max-w-7xl mx-auto">

        {{-- Header Page --}}
        <div class="mb-8">
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900">
                Konfigurasi API
            </h2>
            <p class="mt-1 text-sm text-zinc-500">
                Kelola integrasi pihak ketiga untuk mode Sandbox dan Production.
            </p>
        </div>

        {{-- Alert Messages --}}
        @if(session('success'))
            <div class="mb-6 p-4 rounded-md bg-zinc-900 border border-zinc-800 flex items-center shadow-sm">
                <i class="fas fa-check text-white mr-3 text-sm"></i>
                <p class="text-white text-sm font-medium">{!! session('success') !!}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-md bg-white border border-red-200 flex items-center shadow-sm">
                <i class="fas fa-times text-red-600 mr-3 text-sm"></i>
                <p class="text-zinc-900 text-sm font-medium">{{ session('error') }}</p>
            </div>
        @endif

        {{-- MAIN LAYOUT: KIRI (MENU + TOGGLE) - KANAN (FORM) --}}
        <div class="flex flex-col md:flex-row gap-8 items-start">

            {{-- KOLOM KIRI: SIDEBAR MENU DENGAN BG PUTIH --}}
            <div class="w-full md:w-80 shrink-0 flex flex-col gap-4">

                {{-- --- TAMBAHAN KODE APP DEBUG (BARU) --- --}}
                <div class="bg-white rounded-lg border border-zinc-200 shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900">App Debug</h3>
                            <p class="text-[10px] text-zinc-500">Mode Developer Laravel</p>
                        </div>
                        <button type="button"
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="appDebug ? 'bg-green-500' : 'bg-zinc-300'"
                                @click="toggleDebug()">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                  :class="appDebug ? 'translate-x-4' : 'translate-x-0'"></span>
                        </button>
                    </div>
                </div>
                {{-- --- AKHIR TAMBAHAN KODE APP DEBUG --- --}}

                {{-- KOTAK MENU API --}}
                <div class="bg-white rounded-lg border border-zinc-200 shadow-sm p-3 flex flex-col gap-1.5">

                    {{-- Menu KiriminAja --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'kiriminaja' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'kiriminaja'">
                        <span class="text-sm font-semibold" :class="activeTab === 'kiriminaja' ? 'text-zinc-900' : 'text-zinc-600'">KiriminAja</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="kaData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">STG</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="kaData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="kaData.mode = (kaData.mode === 'production' ? 'staging' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="kaData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="kaData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu Tripay --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'tripay' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'tripay'">
                        <span class="text-sm font-semibold" :class="activeTab === 'tripay' ? 'text-zinc-900' : 'text-zinc-600'">Tripay</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="tpData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">SBX</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="tpData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="tpData.mode = (tpData.mode === 'production' ? 'sandbox' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="tpData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="tpData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu DOKU --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'doku' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'doku'">
                        <span class="text-sm font-semibold" :class="activeTab === 'doku' ? 'text-zinc-900' : 'text-zinc-600'">DOKU</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="dokuData.env === 'production' ? 'text-zinc-400' : 'text-zinc-600'">SBX</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="dokuData.env === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="dokuData.env = (dokuData.env === 'production' ? 'sandbox' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="dokuData.env === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="dokuData.env === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu IAK PPOB --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'iak' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'iak'">
                        <span class="text-sm font-semibold" :class="activeTab === 'iak' ? 'text-zinc-900' : 'text-zinc-600'">IAK PPOB</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="iakData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">DEV</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="iakData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="iakData.mode = (iakData.mode === 'production' ? 'development' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="iakData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="iakData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu Darmawisata --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'dharmawisata' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'dharmawisata'">
                        <span class="text-sm font-semibold" :class="activeTab === 'dharmawisata' ? 'text-zinc-900' : 'text-zinc-600'">Darmawisata</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="dwData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">DEV</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="dwData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="dwData.mode = (dwData.mode === 'production' ? 'development' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="dwData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="dwData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu DANA --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'dana' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'dana'">
                        <span class="text-sm font-semibold" :class="activeTab === 'dana' ? 'text-zinc-900' : 'text-zinc-600'">DANA</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="danaData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">SBX</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="danaData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="danaData.mode = (danaData.mode === 'production' ? 'sandbox' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="danaData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="danaData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu Midtrans --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'midtrans' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'midtrans'">
                        <span class="text-sm font-semibold" :class="activeTab === 'midtrans' ? 'text-zinc-900' : 'text-zinc-600'">Midtrans</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="midtransData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">SBX</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="midtransData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="midtransData.mode = (midtransData.mode === 'production' ? 'sandbox' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="midtransData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="midtransData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu Lalamove --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'lalamove' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'lalamove'">
                        <span class="text-sm font-semibold" :class="activeTab === 'lalamove' ? 'text-zinc-900' : 'text-zinc-600'">Lalamove</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="lalamoveData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">SBX</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="lalamoveData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="lalamoveData.mode = (lalamoveData.mode === 'production' ? 'sandbox' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="lalamoveData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="lalamoveData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu PayPal --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'paypal' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'paypal'">
                        <span class="text-sm font-semibold" :class="activeTab === 'paypal' ? 'text-zinc-900' : 'text-zinc-600'">PayPal</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="paypalData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">SBX</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="paypalData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="paypalData.mode = (paypalData.mode === 'production' ? 'sandbox' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="paypalData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="paypalData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu Deliveree --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'deliveree' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'deliveree'">
                        <span class="text-sm font-semibold" :class="activeTab === 'deliveree' ? 'text-zinc-900' : 'text-zinc-600'">Deliveree</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="delivereeData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">SBX</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="delivereeData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="delivereeData.mode = (delivereeData.mode === 'production' ? 'sandbox' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="delivereeData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="delivereeData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu iPaymu --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'ipaymu' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'ipaymu'">
                        <span class="text-sm font-semibold" :class="activeTab === 'ipaymu' ? 'text-zinc-900' : 'text-zinc-600'">iPaymu</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="ipaymuData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">SBX</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="ipaymuData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="ipaymuData.mode = (ipaymuData.mode === 'production' ? 'sandbox' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="ipaymuData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="ipaymuData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu Mandiri --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'mandiri' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'mandiri'">
                        <span class="text-sm font-semibold" :class="activeTab === 'mandiri' ? 'text-zinc-900' : 'text-zinc-600'">Mandiri API</span>
                        <div class="flex items-center space-x-1.5" @click.stop>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="mandiriData.mode === 'production' ? 'text-zinc-400' : 'text-zinc-600'">SBX</span>
                            <button type="button" class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="mandiriData.mode === 'production' ? 'bg-zinc-900' : 'bg-zinc-300'"
                                    @click="mandiriData.mode = (mandiriData.mode === 'production' ? 'sandbox' : 'production')">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="mandiriData.mode === 'production' ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-[9px] font-bold uppercase tracking-wider" :class="mandiriData.mode === 'production' ? 'text-zinc-900' : 'text-zinc-400'">PROD</span>
                        </div>
                    </div>

                    {{-- Menu Fonnte --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'fonnte' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'fonnte'">
                        <span class="text-sm font-semibold" :class="activeTab === 'fonnte' ? 'text-zinc-900' : 'text-zinc-600'">Fonnte</span>
                        <span class="text-[9px] font-bold text-zinc-400 bg-zinc-50 border border-zinc-200 px-1.5 py-0.5 rounded uppercase tracking-wider">GLOBAL</span>
                    </div>

                    {{-- Menu Mapbox --}}
                    <div class="flex items-center justify-between w-full px-3 py-2.5 rounded-md cursor-pointer transition-colors"
                         :class="activeTab === 'mapbox' ? 'bg-zinc-100/80 border border-zinc-200/50 shadow-sm' : 'hover:bg-zinc-50 border border-transparent'"
                         @click="activeTab = 'mapbox'">
                        <span class="text-sm font-semibold" :class="activeTab === 'mapbox' ? 'text-zinc-900' : 'text-zinc-600'">Mapbox API</span>
                        <span class="text-[9px] font-bold text-zinc-400 bg-zinc-50 border border-zinc-200 px-1.5 py-0.5 rounded uppercase tracking-wider">GLOBAL</span>
                    </div>

                </div>

            </div>

            {{-- KOLOM KANAN: KONTEN FORM --}}
            <div class="flex-1 w-full bg-white rounded-lg border border-zinc-200 shadow-sm min-h-[400px]">

                {{-- 1. TAB KIRIMINAJA --}}
                <div x-show="activeTab === 'kiriminaja'" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">KiriminAja</h3>
                        <p class="text-sm text-zinc-500">Konfigurasi token layanan logistik kurir.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="kiriminaja">
                        <input type="hidden" name="kiriminaja_mode" x-model="kaData.mode">

                        <div class="p-3 bg-zinc-50 border border-zinc-200 rounded text-xs text-zinc-600">
                            Status: <span class="font-bold text-zinc-900" x-text="kaData.mode === 'production' ? 'PRODUCTION (LIVE) - Transaksi Nyata' : 'STAGING - Mode Pengetesan'"></span>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase">API Token</label>
                            <input type="text" name="kiriminaja_token" x-model="kaData[kaData.mode].token" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase">Base URL</label>
                            <input type="url" name="kiriminaja_base_url" x-model="kaData[kaData.mode].base_url" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border" placeholder="Otomatis terisi jika dikosongkan">
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 2. TAB TRIPAY --}}
                <div x-show="activeTab === 'tripay'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">Tripay Payment</h3>
                        <p class="text-sm text-zinc-500">Virtual Account & Retail Payment Gateway.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="tripay">
                        <input type="hidden" name="tripay_mode" x-model="tpData.mode">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Merchant Code</label>
                                <input type="text" name="tripay_merchant_code" x-model="tpData[tpData.mode].merchant_code" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">API Key</label>
                                <input type="text" name="tripay_api_key" x-model="tpData[tpData.mode].api_key" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Private Key</label>
                                <input type="text" name="tripay_private_key" x-model="tpData[tpData.mode].private_key" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 3. TAB DOKU --}}
                <div x-show="activeTab === 'doku'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">DOKU</h3>
                        <p class="text-sm text-zinc-500">Konfigurasi Direct API Payment DOKU.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="doku">
                        <input type="hidden" name="doku_env" x-model="dokuData.env">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Client ID</label>
                                <input type="text" name="doku_client_id" x-model="dokuData[dokuData.env].client_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Secret Key</label>
                                <input type="text" name="doku_secret_key" x-model="dokuData[dokuData.env].secret_key" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Public Key</label>
                                <textarea name="doku_public_key" x-model="dokuData[dokuData.env].public_key" rows="2" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono text-xs"></textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Merchant Private Key</label>
                                <textarea name="merchant_private_key" x-model="dokuData[dokuData.env].merchant_private_key" rows="2" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono text-xs"></textarea>
                            </div>
                        </div>

                        <div class="border-t border-zinc-200 pt-4 mt-2">
                            <label class="block text-xs font-bold text-zinc-900 uppercase">DOKU Main SAC ID (Master Account)</label>
                            <input type="text" name="doku_main_sac_id" x-model="dokuData.sac_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" placeholder="SAC-XXXX...">
                            <p class="text-xs text-zinc-500 mt-1">ID Sub-Account untuk keperluan pencairan saldo seller.</p>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 4. TAB IAK (PPOB) --}}
                <div x-show="activeTab === 'iak'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">IAK PPOB</h3>
                        <p class="text-sm text-zinc-500">Gateway produk digital Pulsa, Kuota, dan PPOB.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="iak">
                        <input type="hidden" name="iak_mode" x-model="iakData.mode">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">User HP</label>
                                <input type="text" name="iak_user_hp" x-model="iakData[iakData.mode].user_hp" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">API Key</label>
                                <input type="text" name="iak_api_key" x-model="iakData[iakData.mode].api_key" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Prepaid URL</label>
                                <input type="url" name="iak_prepaid_base_url" x-model="iakData[iakData.mode].prepaid_base_url" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Postpaid URL</label>
                                <input type="url" name="iak_postpaid_base_url" x-model="iakData[iakData.mode].postpaid_base_url" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border">
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 5. TAB DARMAWISATA --}}
                <div x-show="activeTab === 'dharmawisata'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">Darmawisata</h3>
                        <p class="text-sm text-zinc-500">API B2B Tiket Pesawat & Travel.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="dharmawisata">
                        <input type="hidden" name="dharmawisata_mode" x-model="dwData.mode">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">User ID</label>
                                <input type="text" name="dharmawisata_user_id" x-model="dwData[dwData.mode].user_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Access Token</label>
                                <input type="text" name="dharmawisata_access_token" x-model="dwData[dwData.mode].access_token" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Base URL API</label>
                                <input type="url" name="dharmawisata_base_url" x-model="dwData[dwData.mode].base_url" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border">
                            </div>

                            <div class="sm:col-span-2 pt-2">
                                <h4 class="text-xs font-bold text-zinc-800 uppercase mb-2">Auto-Reconnect Auth</h4>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Static Token</label>
                                <input type="text" name="dharmawisata_static_token" x-model="dwData[dwData.mode].static_token" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Password</label>
                                <input type="password" name="dharmawisata_password" x-model="dwData[dwData.mode].password" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 6. TAB FONNTE --}}
                <div x-show="activeTab === 'fonnte'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">Fonnte WhatsApp</h3>
                        <p class="text-sm text-zinc-500">Notifikasi otomatis pesan WhatsApp (Berlaku Global).</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="fonnte">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase">Global API Token</label>
                            <input type="text" name="fonnte_api_key" value="{{ $fonnte['api_key'] ?? '' }}" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" required>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 7. TAB DANA --}}
                <div x-show="activeTab === 'dana'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">DANA Enterprise</h3>
                        <p class="text-sm text-zinc-500">Integrasi API SNAP BI untuk E-Wallet DANA.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="dana">
                        <input type="hidden" name="dana_mode" x-model="danaData.mode">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Merchant ID</label>
                                <input type="text" name="dana_merchant_id" x-model="danaData[danaData.mode].merchant_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Client ID (Partner ID)</label>
                                <input type="text" name="dana_client_id" x-model="danaData[danaData.mode].client_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Client Secret</label>
                                <input type="text" name="dana_client_secret" x-model="danaData[danaData.mode].client_secret" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Asymmetric Private Key</label>
                                <textarea name="dana_private_key" x-model="danaData[danaData.mode].private_key" rows="2" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono text-xs"></textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">DANA Public Key</label>
                                <textarea name="dana_public_key" x-model="danaData[danaData.mode].public_key" rows="2" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono text-xs"></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 8. TAB MIDTRANS --}}
                <div x-show="activeTab === 'midtrans'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">Midtrans SNAP</h3>
                        <p class="text-sm text-zinc-500">Konfigurasi Gateway Multi-Payment Midtrans.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="midtrans">
                        <input type="hidden" name="midtrans_mode" x-model="midtransData.mode">

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase">Merchant ID</label>
                            <input type="text" name="midtrans_merchant_id" x-model="midtransData[midtransData.mode].merchant_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 pt-2">
                            <div class="sm:col-span-2"><h4 class="text-xs font-bold text-zinc-800 uppercase border-b border-zinc-200 pb-2">SNAP BI Credentials (Wajib)</h4></div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">SNAP Client ID</label>
                                <input type="text" name="midtrans_snap_client_id" x-model="midtransData[midtransData.mode].snap_client_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">SNAP Client Secret</label>
                                <input type="text" name="midtrans_snap_client_secret" x-model="midtransData[midtransData.mode].snap_client_secret" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 pt-2">
                            <div class="sm:col-span-2"><h4 class="text-xs font-bold text-zinc-400 uppercase border-b border-zinc-100 pb-2">Core API Legacy (Opsional)</h4></div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 uppercase">Client Key</label>
                                <input type="text" name="midtrans_client_key" x-model="midtransData[midtransData.mode].client_key" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono text-zinc-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 uppercase">Server Key</label>
                                <input type="text" name="midtrans_server_key" x-model="midtransData[midtransData.mode].server_key" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono text-zinc-500">
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 9. TAB LALAMOVE --}}
                <div x-show="activeTab === 'lalamove'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">Lalamove</h3>
                        <p class="text-sm text-zinc-500">Integrasi kurir pengiriman instan on-demand.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="lalamove">
                        <input type="hidden" name="lalamove_mode" x-model="lalamoveData.mode">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">API Key</label>
                                <input type="text" name="lalamove_api_key" x-model="lalamoveData[lalamoveData.mode].api_key" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">API Secret</label>
                                <input type="text" name="lalamove_api_secret" x-model="lalamoveData[lalamoveData.mode].api_secret" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 10. TAB PAYPAL --}}
                <div x-show="activeTab === 'paypal'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <div class="flex items-center gap-4 mb-1">
                            <h3 class="text-lg font-bold text-zinc-900">PayPal REST API</h3>
                            <div class="flex items-center space-x-2 bg-zinc-100 px-2.5 py-1 rounded-full border border-zinc-200">
                                <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sandbox</span>
                                <div class="relative inline-block w-8 align-middle select-none transition duration-200">
                                    <input type="checkbox" class="toggle-checkbox absolute block w-4 h-4 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': paypalData.mode === 'production', 'border-zinc-300': paypalData.mode === 'sandbox'}" @click="paypalData.mode = (paypalData.mode === 'production' ? 'sandbox' : 'production')" :checked="paypalData.mode === 'production'"/>
                                    <label class="toggle-label block overflow-hidden h-4 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                                </div>
                                <span class="text-[10px] font-bold text-zinc-900 uppercase tracking-wider">Production</span>
                            </div>
                        </div>
                        <p class="text-sm text-zinc-500">Menerima pembayaran Global (USD) & Kartu Kredit.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="paypal">
                        <input type="hidden" name="paypal_mode" x-model="paypalData.mode">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-3 bg-zinc-50 border border-zinc-200 rounded">
                                <p class="text-[10px] text-zinc-500 font-bold mb-1 uppercase tracking-wider">API Base URL</p>
                                <p class="text-xs font-mono text-zinc-900 break-all select-all flex items-center">
                                    <i class="fas fa-link text-zinc-400 mr-2"></i>
                                    <span x-text="paypalData.mode === 'production' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com'"></span>
                                </p>
                            </div>
                            <div class="p-3 bg-zinc-50 border border-zinc-200 rounded">
                                <p class="text-[10px] text-zinc-500 font-bold mb-1 uppercase tracking-wider">Webhook URL Endpoint</p>
                                <p class="text-xs font-mono text-zinc-900 break-all select-all flex items-center">
                                    <i class="fas fa-plug text-zinc-400 mr-2"></i>
                                    https://{{ Request::getHost() }}/api/webhook/paypal
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase">Client ID</label>
                            <input type="text" name="paypal_client_id" x-model="paypalData[paypalData.mode].client_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" required>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Secret Key 1</label>
                                <input type="password" name="paypal_secret_1" x-model="paypalData[paypalData.mode].secret_1" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Secret Key 2</label>
                                <input type="password" name="paypal_secret_2" x-model="paypalData[paypalData.mode].secret_2" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase">Webhook ID</label>
                            <input type="text" name="paypal_webhook_id" x-model="paypalData[paypalData.mode].webhook_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" placeholder="Masukkan ID Webhook PayPal">
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 11. TAB DELIVEREE --}}
                <div x-show="activeTab === 'deliveree'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <div class="flex items-center gap-4 mb-1">
                            <h3 class="text-lg font-bold text-zinc-900">Deliveree Logistics</h3>
                            <div class="flex items-center space-x-2 bg-zinc-100 px-2.5 py-1 rounded-full border border-zinc-200">
                                <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sandbox</span>
                                <div class="relative inline-block w-8 align-middle select-none transition duration-200">
                                    <input type="checkbox" class="toggle-checkbox absolute block w-4 h-4 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': delivereeData.mode === 'production', 'border-zinc-300': delivereeData.mode === 'sandbox'}" @click="delivereeData.mode = (delivereeData.mode === 'production' ? 'sandbox' : 'production')" :checked="delivereeData.mode === 'production'"/>
                                    <label class="toggle-label block overflow-hidden h-4 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                                </div>
                                <span class="text-[10px] font-bold text-zinc-900 uppercase tracking-wider">Production</span>
                            </div>
                        </div>
                        <p class="text-sm text-zinc-500">Konfigurasi API Deliveree v10 (Company ID, API Key, Webhook).</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="deliveree">
                        <input type="hidden" name="deliveree_mode" x-model="delivereeData.mode">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Company ID</label>
                                <input type="text" name="deliveree_company_id" x-model="delivereeData[delivereeData.mode].company_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">API Key</label>
                                <input type="text" name="deliveree_api_key" x-model="delivereeData[delivereeData.mode].api_key" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Webhook URL Endpoint</label>
                                <input type="url" name="deliveree_webhook_url" x-model="delivereeData[delivereeData.mode].webhook_url" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" placeholder="https://tokosancaka.com/api/webhook/deliveree">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Base URL (Opsional - Jika Deliveree Update API)</label>
                                <input type="url" name="deliveree_base_url" x-model="delivereeData[delivereeData.mode].base_url" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" placeholder="Biarkan kosong untuk default v10">
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>



                {{-- 12. TAB IPAYMU --}}
                <div x-show="activeTab === 'ipaymu'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <div class="flex items-center gap-4 mb-1">
                            <h3 class="text-lg font-bold text-zinc-900">iPaymu</h3>
                            <div class="flex items-center space-x-2 bg-zinc-100 px-2.5 py-1 rounded-full border border-zinc-200">
                                <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sandbox</span>
                                <div class="relative inline-block w-8 align-middle select-none transition duration-200">
                                    <input type="checkbox" class="toggle-checkbox absolute block w-4 h-4 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': ipaymuData.mode === 'production', 'border-zinc-300': ipaymuData.mode === 'sandbox'}" @click="ipaymuData.mode = (ipaymuData.mode === 'production' ? 'sandbox' : 'production')" :checked="ipaymuData.mode === 'production'"/>
                                    <label class="toggle-label block overflow-hidden h-4 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                                </div>
                                <span class="text-[10px] font-bold text-zinc-900 uppercase tracking-wider">Production</span>
                            </div>
                        </div>
                        <p class="text-sm text-zinc-500">Konfigurasi API Payment Gateway iPaymu v2.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="ipaymu">
                        <input type="hidden" name="ipaymu_mode" x-model="ipaymuData.mode">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Virtual Account (VA)</label>
                                <input type="text" name="ipaymu_va" x-model="ipaymuData[ipaymuData.mode].va" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">API Key</label>
                                <input type="text" name="ipaymu_api_key" x-model="ipaymuData[ipaymuData.mode].api_key" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 13. TAB MANDIRI --}}
                <div x-show="activeTab === 'mandiri'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <div class="flex items-center gap-4 mb-1">
                            <h3 class="text-lg font-bold text-zinc-900">Mandiri API</h3>
                            <div class="flex items-center space-x-2 bg-zinc-100 px-2.5 py-1 rounded-full border border-zinc-200">
                                <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sandbox</span>
                                <div class="relative inline-block w-8 align-middle select-none transition duration-200">
                                    <input type="checkbox" class="toggle-checkbox absolute block w-4 h-4 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': mandiriData.mode === 'production', 'border-zinc-300': mandiriData.mode === 'sandbox'}" @click="mandiriData.mode = (mandiriData.mode === 'production' ? 'sandbox' : 'production')" :checked="mandiriData.mode === 'production'"/>
                                    <label class="toggle-label block overflow-hidden h-4 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                                </div>
                                <span class="text-[10px] font-bold text-zinc-900 uppercase tracking-wider">Production</span>
                            </div>
                        </div>
                        <p class="text-sm text-zinc-500">Konfigurasi API B2B Bank Mandiri (VA & Transfer).</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="p-6 space-y-5">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="mandiri">
                        <input type="hidden" name="mandiri_mode" :value="mandiriData.mode || 'sandbox'">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Client ID</label>
                                <input type="text" name="mandiri_client_id" x-model="mandiriData[mandiriData.mode].client_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Client Secret</label>
                                <input type="text" name="mandiri_client_secret" x-model="mandiriData[mandiriData.mode].client_secret" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Partner ID</label>
                                <input type="text" name="mandiri_partner_id" x-model="mandiriData[mandiriData.mode].partner_id" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" placeholder="Isi 'SANDBOX' jika mode Sandbox">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase">Private Key (Format .pem)</label>
                                <textarea name="mandiri_private_key" x-model="mandiriData[mandiriData.mode].private_key" rows="6" class="mt-1 block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono text-xs whitespace-pre" placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded hover:bg-black text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                {{-- 14. TAB MAPBOX --}}
                <div x-show="activeTab === 'mapbox'" style="display:none;" x-transition.opacity>
                    <div class="p-6 border-b border-zinc-200">
                        <h3 class="text-lg font-bold text-zinc-900 mb-1">Sancaka Express & Mapbox</h3>
                        <p class="text-sm text-zinc-500">Konfigurasi Access Token peta dan rumus tarif pengiriman internal.</p>
                    </div>
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="divide-y divide-zinc-200">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="mapbox">

                        {{-- Section Mapbox Token (Landscape) --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                            <div class="md:col-span-1">
                                <h4 class="text-sm font-semibold text-zinc-900">Mapbox Token</h4>
                                <p class="text-xs text-zinc-500 mt-1">API Token diperlukan untuk menghitung rute dan jarak tempuh kurir Sancaka Express antara titik pengirim dan penerima.</p>
                                <a href="https://account.mapbox.com/access-tokens/" target="_blank" class="inline-block mt-3 text-xs font-medium text-blue-600 hover:text-blue-700">Dapatkan Token &rarr;</a>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-zinc-700 uppercase mb-1">Public Token (pk.xxx...)</label>
                                <input type="text" name="mapbox_token" x-model="mapboxData.token" class="block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border font-mono" required placeholder="pk.eyJ1Ijoi...">
                                <p class="text-[11px] text-zinc-500 mt-1.5">Gunakan token Mapbox yang kolom <strong>URL Restrictions</strong>-nya dikosongkan.</p>
                            </div>
                        </div>

                        {{-- Section Tarif Sancaka (Landscape) --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                            <div class="md:col-span-1">
                                <h4 class="text-sm font-semibold text-zinc-900">Tarif Sancaka Express</h4>
                                <p class="text-xs text-zinc-500 mt-1">Sistem akan mengkalkulasi otomatis berdasarkan jarak dan berat.</p>
                                <div class="mt-3 p-2 bg-zinc-50 border border-zinc-200 rounded text-[10px] font-mono text-zinc-600">
                                    Tarif Dasar + (Jarak x Harga/KM) + (Berat x Harga/KG)
                                </div>
                            </div>
                            <div class="md:col-span-2 space-y-5">
                                <div>
                                    <label class="block text-xs font-medium text-zinc-700 uppercase mb-1">Tarif Dasar (Rp)</label>
                                    <input type="number" name="base_fare" x-model="mapboxData.base_fare" class="block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border" required min="0">
                                    <p class="text-[11px] text-zinc-500 mt-1">Biaya minimal pemanggilan kurir.</p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 uppercase mb-1">Harga per KM (Rp)</label>
                                        <input type="number" name="price_per_km" x-model="mapboxData.price_per_km" class="block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border" required min="0">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 uppercase mb-1">Harga per KG (Rp)</label>
                                        <input type="number" name="price_per_kg" x-model="mapboxData.price_per_kg" class="block w-full rounded-md border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 sm:text-sm p-2 border" required min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end p-6 bg-zinc-50 rounded-b-lg">
                            <button type="submit" class="bg-zinc-900 text-white px-5 py-2 rounded shadow-sm hover:bg-zinc-800 text-sm font-medium transition-colors">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    </div>
</div>

// Alpine JS
<script src="//unpkg.com/alpinejs" defer></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('apiSettings', () => ({
            activeTab: 'ipaymu',

            // --- TAMBAHAN KODE APP DEBUG (BARU) ---
            appDebug: @json((bool) $appDebug),

            // Sinkronisasi data JSON dari Controller PHP
            kaData: @json($kiriminaja),
            tpData: @json($tripay),
            dokuData: @json($doku),
            iakData: @json($iak),
            dwData: @json($dharmawisata),
            danaData: @json($dana),
            midtransData: @json($midtrans),
            lalamoveData: @json($lalamove),
            paypalData: @json($paypal),
            delivereeData: @json($deliveree),
            ipaymuData: @json($ipaymu),
            mandiriData: @json($mandiri),
            mapboxData: @json($mapbox),

            // --- FUNGSI AJAX TOGGLE APP DEBUG (BARU) ---
            async toggleDebug() {
                this.appDebug = !this.appDebug;

                try {
                    let response = await fetch('{{ route("admin.settings.api.toggleDebug") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            app_debug: this.appDebug
                        })
                    });

                    let data = await response.json();

                    if(data.success) {
                        alert(data.message);
                    } else {
                        alert(data.message);
                        this.appDebug = !this.appDebug; // Revert if error
                    }
                } catch (error) {
                    console.error('Error toggling debug:', error);
                    alert('Terjadi kesalahan sistem.');
                    this.appDebug = !this.appDebug; // Revert if error
                }
            }
        }))
    })
</script>
@endsection
