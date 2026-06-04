@extends('layouts.admin')

@section('title', 'Konfigurasi API')

@section('content')
{{-- Style Kustom Monokrom untuk Toggle Switch ala Next.js --}}
<style>
    [x-cloak] { display: none !important; }
    .toggle-checkbox:checked {
        border-color: #18181b; /* zinc-900 */
    }
    .toggle-checkbox:checked + .toggle-label {
        background-color: #18181b; /* zinc-900 */
    }
    .toggle-checkbox:not(:checked) + .toggle-label {
        background-color: #e4e4e7; /* zinc-200 */
    }
</style>

<div class="min-h-screen bg-zinc-50/50 py-8 px-4 sm:px-6 lg:px-8" x-data="apiSettings" x-cloak>
    <div class="max-w-6xl mx-auto">

        {{-- Header Minimalis --}}
        <div class="mb-8 pb-6 border-b border-zinc-200">
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 flex items-center">
                <i class="fas fa-sliders-h text-zinc-900 mr-3 text-xl"></i> Konfigurasi Integrasi API
            </h2>
            <p class="mt-1 text-sm text-zinc-500">
                Kelola kredensial pihak ketiga secara dinamis. Data Sandbox dan Live/Production disimpan terpisah.
            </p>
        </div>

        {{-- Alert Messages Monokrom --}}
        @if(session('success'))
            <div class="mb-6 p-4 rounded-lg bg-zinc-900 border border-zinc-800 flex items-center shadow-sm">
                <i class="fas fa-check text-white mr-3 text-sm"></i>
                <p class="text-white text-sm font-medium">{!! session('success') !!}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-lg bg-white border border-zinc-200 flex items-center shadow-sm">
                <i class="fas fa-times text-zinc-900 mr-3 text-sm"></i>
                <p class="text-zinc-900 text-sm font-medium">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Navigasi Tab Sleek Monokrom --}}
        <div class="mb-8 border-b border-zinc-200 overflow-x-auto scrollbar-none">
            <div class="flex space-x-1">
                <button @click="activeTab = 'kiriminaja'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'kiriminaja', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'kiriminaja' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    KiriminAja
                </button>
                <button @click="activeTab = 'tripay'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'tripay', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'tripay' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    Tripay
                </button>
                <button @click="activeTab = 'doku'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'doku', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'doku' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    DOKU
                </button>
                <button @click="activeTab = 'iak'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'iak', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'iak' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    IAK PPOB
                </button>
                <button @click="activeTab = 'dharmawisata'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'dharmawisata', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'dharmawisata' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    Darmawisata
                </button>
                <button @click="activeTab = 'fonnte'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'fonnte', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'fonnte' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    Fonnte
                </button>
                <button @click="activeTab = 'dana'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'dana', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'dana' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    DANA
                </button>
                <button @click="activeTab = 'midtrans'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'midtrans', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'midtrans' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    Midtrans
                </button>
                <button @click="activeTab = 'lalamove'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'lalamove', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'lalamove' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    Lalamove
                </button>
                <button @click="activeTab = 'paypal'" :class="{ 'border-zinc-900 text-zinc-900 font-semibold border-b-2': activeTab === 'paypal', 'text-zinc-400 hover:text-zinc-900': activeTab !== 'paypal' }" class="px-4 py-3 text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    PayPal
                </button>
            </div>
        </div>

        {{-- Area Konten Split View (Kiri: Judul & Info, Kanan: Form Box) --}}
        <div class="py-4">

            {{-- 1. TAB KIRIMINAJA --}}
            <div x-show="activeTab === 'kiriminaja'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">KiriminAja Logistics</h3>
                        <p class="text-xs text-zinc-500 mt-1">Konfigurasi token kurir pengiriman Sancaka Express.</p>
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <span class="text-xs font-medium" :class="kaData.mode === 'staging' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">SANDBOX</span>
                        <div class="relative inline-block w-10 align-middle select-none transition duration-200">
                            <input type="checkbox" id="ka_toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': kaData.mode === 'production', 'border-zinc-300': kaData.mode === 'staging'}" @click="kaData.mode = (kaData.mode === 'production' ? 'staging' : 'production')" :checked="kaData.mode === 'production'"/>
                            <label for="ka_toggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                        </div>
                        <span class="text-xs font-medium" :class="kaData.mode === 'production' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">PRODUCTION</span>
                    </div>
                    <div class="p-3 rounded-md border border-zinc-200 bg-zinc-50/50 text-xs text-zinc-600">
                        <i class="fas fa-info-circle mr-1"></i> <span x-text="kaData.mode === 'production' ? 'Mode Produksi Aktif. Transaksi nyata memotong saldo asli.' : 'Mode Sandbox Aktif. Aman untuk pengetesan.'"></span>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="kiriminaja">
                        <input type="hidden" name="kiriminaja_mode" x-model="kaData.mode">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">API Token</label>
                            <input type="text" name="kiriminaja_token" x-model="kaData[kaData.mode].token" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs font-mono p-2.5 border" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Base URL Override</label>
                            <input type="url" name="kiriminaja_base_url" x-model="kaData[kaData.mode].base_url" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border" placeholder="Kosongkan untuk auto-generate default">
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">Simpan KiriminAja</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 2. TAB TRIPAY --}}
            <div x-show="activeTab === 'tripay'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" style="display: none;">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">Tripay Payment</h3>
                        <p class="text-xs text-zinc-500 mt-1">Konfigurasi Virtual Account & Retail Payment Tripay.</p>
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <span class="text-xs font-medium" :class="tpData.mode === 'sandbox' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">SANDBOX</span>
                        <div class="relative inline-block w-10 align-middle select-none transition duration-200">
                            <input type="checkbox" id="tp_toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': tpData.mode === 'production', 'border-zinc-300': tpData.mode === 'sandbox'}" @click="tpData.mode = (tpData.mode === 'production' ? 'sandbox' : 'production')" :checked="tpData.mode === 'production'"/>
                            <label for="tp_toggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                        </div>
                        <span class="text-xs font-medium" :class="tpData.mode === 'production' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">PRODUCTION</span>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="tripay">
                        <input type="hidden" name="tripay_mode" x-model="tpData.mode">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Merchant Code</label>
                                <input type="text" name="tripay_merchant_code" x-model="tpData[tpData.mode].merchant_code" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">API Key</label>
                                <input type="text" name="tripay_api_key" x-model="tpData[tpData.mode].api_key" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Private Key</label>
                            <input type="text" name="tripay_private_key" x-model="tpData[tpData.mode].private_key" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs font-mono p-2.5 border">
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">Simpan Tripay</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 3. TAB DOKU --}}
            <div x-show="activeTab === 'doku'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" style="display: none;">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">DOKU Payment</h3>
                        <p class="text-xs text-zinc-500 mt-1">Konfigurasi Kredensial Payment Gateway DOKU Direct API.</p>
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <span class="text-xs font-medium" :class="dokuData.env === 'sandbox' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">SANDBOX</span>
                        <div class="relative inline-block w-10 align-middle select-none transition duration-200">
                            <input type="checkbox" id="doku_toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': dokuData.env === 'production', 'border-zinc-300': dokuData.env === 'sandbox'}" @click="dokuData.env = (dokuData.env === 'production' ? 'sandbox' : 'production')" :checked="dokuData.env === 'production'"/>
                            <label for="doku_toggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                        </div>
                        <span class="text-xs font-medium" :class="dokuData.env === 'production' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">PRODUCTION</span>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="doku">
                        <input type="hidden" name="doku_env" x-model="dokuData.env">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Client ID</label>
                                <input type="text" name="doku_client_id" x-model="dokuData[dokuData.env].client_id" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Secret Key</label>
                                <input type="text" name="doku_secret_key" x-model="dokuData[dokuData.env].secret_key" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">DOKU Public Key</label>
                            <textarea name="doku_public_key" x-model="dokuData[dokuData.env].public_key" rows="2" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs font-mono p-2 border"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Merchant Private Key</label>
                            <textarea name="merchant_private_key" x-model="dokuData[dokuData.env].merchant_private_key" rows="2" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs font-mono p-2 border"></textarea>
                        </div>
                        <div class="border-t border-zinc-100 pt-4 mt-2">
                            <label class="block text-xs font-bold text-zinc-800 uppercase tracking-wider mb-1">DOKU Main SAC ID</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-zinc-400 text-xs font-mono">SAC-</span>
                                </div>
                                <input type="text" name="doku_main_sac_id" x-model="dokuData.sac_id" class="focus:ring-zinc-900 focus:border-zinc-900 block w-full pl-12 text-xs border-zinc-200 rounded-md p-2.5 border" placeholder="0000-0000000000001">
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">Simpan DOKU</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 4. TAB IAK (PPOB) --}}
            <div x-show="activeTab === 'iak'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" style="display: none;">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">IAK PPOB Gateway</h3>
                        <p class="text-xs text-zinc-500 mt-1">Integrasi produk digital pulsa, kuota, dan tagihan (PPOB).</p>
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <span class="text-xs font-medium" :class="iakData.mode === 'development' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">DEVELOPMENT</span>
                        <div class="relative inline-block w-10 align-middle select-none transition duration-200">
                            <input type="checkbox" id="iak_toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': iakData.mode === 'production', 'border-zinc-300': iakData.mode === 'development'}" @click="iakData.mode = (iakData.mode === 'production' ? 'development' : 'production')" :checked="iakData.mode === 'production'"/>
                            <label for="iak_toggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                        </div>
                        <span class="text-xs font-medium" :class="iakData.mode === 'production' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">PRODUCTION</span>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="iak">
                        <input type="hidden" name="iak_mode" x-model="iakData.mode">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">User HP Account</label>
                                <input type="text" name="iak_user_hp" x-model="iakData[iakData.mode].user_hp" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">API Key</label>
                                <input type="text" name="iak_api_key" x-model="iakData[iakData.mode].api_key" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Prepaid Base URL</label>
                                <input type="url" name="iak_prepaid_base_url" x-model="iakData[iakData.mode].prepaid_base_url" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Postpaid Base URL</label>
                                <input type="url" name="iak_postpaid_base_url" x-model="iakData[iakData.mode].postpaid_base_url" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">Simpan IAK</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 5. TAB DARMAWISATA --}}
            <div x-show="activeTab === 'dharmawisata'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" style="display: none;">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">Darmawisata H2H</h3>
                        <p class="text-xs text-zinc-500 mt-1">Konfigurasi core konektivitas API Tiket Pesawat Maskapai Penerbangan.</p>
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <span class="text-xs font-medium" :class="dwData.mode === 'development' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">DEVELOPMENT</span>
                        <div class="relative inline-block w-10 align-middle select-none transition duration-200">
                            <input type="checkbox" id="dw_toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': dwData.mode === 'production', 'border-zinc-300': dwData.mode === 'development'}" @click="dwData.mode = (dwData.mode === 'production' ? 'development' : 'production')" :checked="dwData.mode === 'production'"/>
                            <label for="dw_toggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                        </div>
                        <span class="text-xs font-medium" :class="dwData.mode === 'production' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">PRODUCTION</span>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="dharmawisata">
                        <input type="hidden" name="dharmawisata_mode" x-model="dwData.mode">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">User ID</label>
                                <input type="text" name="dharmawisata_user_id" x-model="dwData[dwData.mode].user_id" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Access Token</label>
                                <input type="text" name="dharmawisata_access_token" x-model="dwData[dwData.mode].access_token" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Base URL API</label>
                            <input type="url" name="dharmawisata_base_url" x-model="dwData[dwData.mode].base_url" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                        </div>
                        <div class="border-t border-zinc-100 pt-4 mt-2">
                            <h4 class="text-xs font-bold text-zinc-800 uppercase tracking-wider mb-3">Auto-Reconnect Credentials</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Static Token</label>
                                    <input type="text" name="dharmawisata_static_token" x-model="dwData[dwData.mode].static_token" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Password / Security Code</label>
                                    <input type="text" name="dharmawisata_password" x-model="dwData[dwData.mode].password" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">Simpan Darmawisata</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 6. TAB FONNTE --}}
            <div x-show="activeTab === 'fonnte'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" style="display: none;">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">Fonnte WhatsApp</h3>
                        <p class="text-xs text-zinc-500 mt-1">Konfigurasi API gateway pengiriman notifikasi otomatis WhatsApp.</p>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="fonnte">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">App API Key / Token Global</label>
                            <input type="text" name="fonnte_api_key" value="{{ $fonnte['api_key'] }}" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs font-mono p-2.5 border" placeholder="Masukkan Token Fonnte">
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">Simpan Fonnte</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 7. TAB DANA --}}
            <div x-show="activeTab === 'dana'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" style="display: none;">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">DANA Enterprise</h3>
                        <p class="text-xs text-zinc-500 mt-1">Koneksi standardisasi SNAP BI untuk dompet digital DANA.</p>
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <span class="text-xs font-medium" :class="danaData.mode === 'sandbox' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">SANDBOX</span>
                        <div class="relative inline-block w-10 align-middle select-none transition duration-200">
                            <input type="checkbox" id="dana_toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': danaData.mode === 'production', 'border-zinc-300': danaData.mode === 'sandbox'}" @click="danaData.mode = (danaData.mode === 'production' ? 'sandbox' : 'production')" :checked="danaData.mode === 'production'"/>
                            <label for="dana_toggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                        </div>
                        <span class="text-xs font-medium" :class="danaData.mode === 'production' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">PRODUCTION</span>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="dana">
                        <input type="hidden" name="dana_mode" x-model="danaData.mode">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Merchant ID</label>
                                <input type="text" name="dana_merchant_id" x-model="danaData[danaData.mode].merchant_id" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Client ID (Partner ID)</label>
                                <input type="text" name="dana_client_id" x-model="danaData[danaData.mode].client_id" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Client Secret</label>
                            <input type="text" name="dana_client_secret" x-model="danaData[danaData.mode].client_secret" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Asymmetric Private Key</label>
                            <textarea name="dana_private_key" x-model="danaData[danaData.mode].private_key" rows="2" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs font-mono p-2 border"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">DANA Public Key</label>
                            <textarea name="dana_public_key" x-model="danaData[danaData.mode].public_key" rows="2" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs font-mono p-2 border"></textarea>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">Simpan DANA</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 8. TAB MIDTRANS --}}
            <div x-show="activeTab === 'midtrans'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" style="display: none;">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">Midtrans SNAP</h3>
                        <p class="text-xs text-zinc-500 mt-1">Konfigurasi kredensial utama sistem Midtrans BI-SNAP Gateway.</p>
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <span class="text-xs font-medium" :class="midtransData.mode === 'sandbox' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">SANDBOX</span>
                        <div class="relative inline-block w-10 align-middle select-none transition duration-200">
                            <input type="checkbox" id="midtrans_toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': midtransData.mode === 'production', 'border-zinc-300': midtransData.mode === 'sandbox'}" @click="midtransData.mode = (midtransData.mode === 'production' ? 'sandbox' : 'production')" :checked="midtransData.mode === 'production'"/>
                            <label for="midtrans_toggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                        </div>
                        <span class="text-xs font-medium" :class="midtransData.mode === 'production' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">PRODUCTION</span>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="midtrans">
                        <input type="hidden" name="midtrans_mode" x-model="midtransData.mode">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Merchant ID</label>
                            <input type="text" name="midtrans_merchant_id" x-model="midtransData[midtransData.mode].merchant_id" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border" placeholder="G850780499">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-zinc-100 pt-4">
                            <div class="col-span-2"><h4 class="text-xs font-bold text-zinc-800 uppercase tracking-wider">SNAP BI Credentials (Wajib)</h4></div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">SNAP Client ID</label>
                                <input type="text" name="midtrans_snap_client_id" x-model="midtransData[midtransData.mode].snap_client_id" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">SNAP Client Secret</label>
                                <input type="text" name="midtrans_snap_client_secret" x-model="midtransData[midtransData.mode].snap_client_secret" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-zinc-100 pt-4">
                            <div class="col-span-2"><h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Core API Legacy (Opsional)</h4></div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-400 uppercase tracking-wider">Client Key</label>
                                <input type="text" name="midtrans_client_key" x-model="midtransData[midtransData.mode].client_key" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-400 uppercase tracking-wider">Server Key</label>
                                <input type="text" name="midtrans_server_key" x-model="midtransData[midtransData.mode].server_key" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">Simpan Midtrans</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 9. TAB LALAMOVE --}}
            <div x-show="activeTab === 'lalamove'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" style="display: none;">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">Lalamove Delivery</h3>
                        <p class="text-xs text-zinc-500 mt-1">Konfigurasi API pengiriman instan on-demand Lalamove.</p>
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <span class="text-xs font-medium" :class="lalamoveData.mode === 'sandbox' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">SANDBOX</span>
                        <div class="relative inline-block w-10 align-middle select-none transition duration-200">
                            <input type="checkbox" id="lalamove_toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': lalamoveData.mode === 'production', 'border-zinc-300': lalamoveData.mode === 'sandbox'}" @click="lalamoveData.mode = (lalamoveData.mode === 'production' ? 'sandbox' : 'production')" :checked="lalamoveData.mode === 'production'"/>
                            <label for="lalamove_toggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                        </div>
                        <span class="text-xs font-medium" :class="lalamoveData.mode === 'production' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">PRODUCTION</span>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="lalamove">
                        <input type="hidden" name="lalamove_mode" x-model="lalamoveData.mode">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">API Key</label>
                                <input type="text" name="lalamove_api_key" x-model="lalamoveData[lalamoveData.mode].api_key" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">API Secret</label>
                                <input type="text" name="lalamove_api_secret" x-model="lalamoveData[lalamoveData.mode].api_secret" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border">
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">Simpan Lalamove</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 10. TAB PAYPAL (NEW TWO-COLUMN DESIGN) --}}
            <div x-show="activeTab === 'paypal'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" style="display: none;">
                {{-- Kolom Kiri: Judul & Informasi Singkat --}}
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900">PayPal International</h3>
                        <p class="text-xs text-zinc-500 mt-1">
                            Konfigurasi REST API Gateway untuk menerima pembayaran global kartu kredit dan saldo PayPal.
                        </p>
                    </div>
                    
                    {{-- Switcher Mode Minimalis --}}
                    <div class="flex items-center space-x-2 pt-2">
                        <span class="text-xs font-medium" :class="paypalData.mode === 'sandbox' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">SANDBOX</span>
                        <div class="relative inline-block w-10 align-middle select-none transition duration-200">
                            <input type="checkbox" id="paypal_toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all transform translate-x-0" :class="{'translate-x-full border-zinc-900': paypalData.mode === 'production', 'border-zinc-300': paypalData.mode === 'sandbox'}" @click="paypalData.mode = (paypalData.mode === 'production' ? 'sandbox' : 'production')" :checked="paypalData.mode === 'production'"/>
                            <label for="paypal_toggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-200 cursor-pointer transition-colors duration-300"></label>
                        </div>
                        <span class="text-xs font-medium" :class="paypalData.mode === 'production' ? 'text-zinc-900 font-bold' : 'text-zinc-400'">PRODUCTION</span>
                    </div>

                    <div class="p-3 rounded-md border border-zinc-200 bg-zinc-50/50 text-xs text-zinc-500">
                        <p><i class="fas fa-link mr-1"></i> <b>Webhook URL Listener:</b></p>
                        <p class="font-mono bg-zinc-100 p-1.5 rounded mt-1 text-[10px] break-all select-all">https://{{ Request::getHost() }}/api/webhook/paypal</p>
                    </div>
                </div>

                {{-- Kolom Kanan: Kotak Form Input --}}
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-xl p-6 shadow-sm">
                    <form action="{{ route('admin.settings.api.update') }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="paypal">
                        <input type="hidden" name="paypal_mode" x-model="paypalData.mode">

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Client ID (<span x-text="paypalData.mode.toUpperCase()"></span>)</label>
                            <input type="text" name="paypal_client_id" x-model="paypalData[paypalData.mode].client_id" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border font-mono" placeholder="Masukkan Client ID PayPal" required>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Secret Key (<span x-text="paypalData.mode.toUpperCase()"></span>)</label>
                            <input type="text" name="paypal_secret" x-model="paypalData[paypalData.mode].secret" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border font-mono" placeholder="Masukkan Secret Key PayPal" required>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 uppercase tracking-wider">Webhook ID (<span x-text="paypalData.mode.toUpperCase()"></span>)</label>
                            <input type="text" name="paypal_webhook_id" x-model="paypalData[paypalData.mode].webhook_id" class="mt-1 block w-full rounded-md border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-xs p-2.5 border font-mono" placeholder="Contoh: 8YA33094D2492333M">
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-black text-xs font-medium transition-colors shadow-sm">
                                Simpan PayPal
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Alpine JS --}}
<script src="//unpkg.com/alpinejs" defer></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('apiSettings', () => ({
            activeTab: 'kiriminaja', // Default tab yang aktif saat pertama buka

            // Sinkronisasi data JSON terstruktur aman dari Controller Laravel
            kaData: @json($kiriminaja),
            tpData: @json($tripay),
            dokuData: @json($doku),
            iakData: @json($iak),
            dwData: @json($dharmawisata),
            danaData: @json($dana),
            midtransData: @json($midtrans),
            lalamoveData: @json($lalamove),
            paypalData: @json($paypal),
        }))
    })
</script>
@endsection