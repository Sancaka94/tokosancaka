@extends('layouts.admin')

@section('title', 'Konfigurasi API')

@section('content')
{{-- Tambahkan Style untuk mencegah flicker (kedip) saat loading --}}
<style>
    [x-cloak] { display: none !important; }
    /* Toggle Switch Custom Style */
    .toggle-checkbox:checked {
        /* right: 0;  <-- HAPUS INI karena bikin tombol bablas ke kanan */
        border-color: #ef4444; /* Red-500 */
    }
    .toggle-checkbox:checked + .toggle-label {
        background-color: #ef4444; /* Red-500 */
    }
    .toggle-checkbox:not(:checked) + .toggle-label {
        background-color: #6366f1; /* Indigo-500 */
    }
</style>

<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8" x-data="apiSettings" x-cloak>
    <div class="max-w-5xl mx-auto">

        {{-- Header --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-network-wired text-indigo-600 mr-2"></i> Konfigurasi API
                </h2>
                <p class="mt-2 text-sm text-gray-500">
                    Kelola kredensial pihak ketiga. Data tersimpan terpisah antara <b>Sandbox</b> dan <b>Production</b>.
                </p>
            </div>
        </div>

        {{-- Alert Messages --}}
        @if(session('success'))
            <div class="mb-6 p-4 rounded-lg bg-green-50 border-l-4 border-green-400 flex items-center shadow-sm">
                <i class="fas fa-check-circle text-green-500 mr-3 text-xl"></i>
                <p class="text-green-800 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-400 flex items-center shadow-sm">
                <i class="fas fa-exclamation-circle text-red-500 mr-3 text-xl"></i>
                <p class="text-red-800 font-medium">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Tabs Navigation --}}
        <div class="bg-white shadow-sm rounded-t-xl border-b border-gray-200 overflow-x-auto">
            <div class="flex">
                <button @click="activeTab = 'kiriminaja'" :class="{ 'bg-indigo-50 text-indigo-700 border-b-2 border-indigo-600': activeTab === 'kiriminaja', 'text-gray-500 hover:text-gray-700 hover:bg-gray-50': activeTab !== 'kiriminaja' }" class="px-6 py-4 font-medium text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    <i class="fas fa-shipping-fast mr-2"></i> KiriminAja
                </button>
                <button @click="activeTab = 'tripay'" :class="{ 'bg-indigo-50 text-indigo-700 border-b-2 border-indigo-600': activeTab === 'tripay', 'text-gray-500 hover:text-gray-700 hover:bg-gray-50': activeTab !== 'tripay' }" class="px-6 py-4 font-medium text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    <i class="fas fa-wallet mr-2"></i> Tripay
                </button>
                <button @click="activeTab = 'doku'" :class="{ 'bg-indigo-50 text-indigo-700 border-b-2 border-indigo-600': activeTab === 'doku', 'text-gray-500 hover:text-gray-700 hover:bg-gray-50': activeTab !== 'doku' }" class="px-6 py-4 font-medium text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    <i class="fas fa-credit-card mr-2"></i> DOKU
                </button>
                <button @click="activeTab = 'fonnte'" :class="{ 'bg-indigo-50 text-indigo-700 border-b-2 border-indigo-600': activeTab === 'fonnte', 'text-gray-500 hover:text-gray-700 hover:bg-gray-50': activeTab !== 'fonnte' }" class="px-6 py-4 font-medium text-sm focus:outline-none transition-all whitespace-nowrap flex items-center">
                    <i class="fab fa-whatsapp mr-2"></i> Fonnte
                </button>
            </div>
        </div>

        {{-- Content Area --}}
        <div class="bg-white shadow-xl rounded-b-xl border border-gray-100 p-6 sm:p-8">

            {{-- 1. TAB KIRIMINAJA --}}
            <div x-show="activeTab === 'kiriminaja'" x-transition.opacity>
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">KiriminAja Configuration</h3>
                        <p class="text-xs text-gray-500 mt-1">Status Aktif:
                            <span class="px-2 py-0.5 rounded text-xs font-bold transition-colors duration-300"
                                  :class="kaData.mode === 'production' ? 'bg-red-100 text-red-700' : 'bg-indigo-100 text-indigo-700'"
                                  x-text="kaData.mode === 'production' ? 'PRODUCTION (LIVE)' : 'STAGING (TEST)'">
                            </span>
                        </p>
                    </div>

                    {{-- Toggle Switch KiriminAja --}}
                    <div class="flex items-center">
                        <span class="mr-3 text-sm font-medium" :class="kaData.mode === 'staging' ? 'text-indigo-600 font-bold' : 'text-gray-500'">SANDBOX</span>

                        {{-- Tambahkan mr-3 agar ada jarak lebih --}}
                        <div class="relative inline-block w-12 mr-3 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" name="kiriminaja_mode_toggle" id="ka_toggle"
                                   class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 transform translate-x-0"
                                   :class="{'translate-x-full border-red-500': kaData.mode === 'production', 'border-indigo-500': kaData.mode === 'staging'}"
                                   @click="kaData.mode = (kaData.mode === 'production' ? 'staging' : 'production')"
                                   :checked="kaData.mode === 'production'"/>
                            <label for="ka_toggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-colors duration-300"
                                   :class="{'bg-red-500': kaData.mode === 'production', 'bg-indigo-500': kaData.mode === 'staging'}"></label>
                        </div>

                        <span class="ml-1 text-sm font-medium" :class="kaData.mode === 'production' ? 'text-red-600 font-bold' : 'text-gray-500'">PRODUCTION</span>
                    </div>
                </div>

                <form action="{{ route('admin.settings.api.update') }}" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="type" value="kiriminaja">
                    {{-- Hidden input untuk mengirim value mode yang sebenarnya ke controller --}}
                    <input type="hidden" name="kiriminaja_mode" x-model="kaData.mode">

                    <div class="space-y-6">
                        {{-- Visual Warning --}}
                        <div class="p-4 rounded-lg border flex items-start"
                             :class="kaData.mode === 'production' ? 'bg-red-50 border-red-200' : 'bg-indigo-50 border-indigo-200'">
                            <div class="flex-shrink-0 mt-0.5">
                                <i class="fas" :class="kaData.mode === 'production' ? 'fa-exclamation-triangle text-red-500' : 'fa-info-circle text-indigo-500'"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium" :class="kaData.mode === 'production' ? 'text-red-800' : 'text-indigo-800'" x-text="kaData.mode === 'production' ? 'Mode Produksi Aktif' : 'Mode Sandbox Aktif'"></h3>
                                <div class="mt-1 text-sm" :class="kaData.mode === 'production' ? 'text-red-700' : 'text-indigo-700'">
                                    <p x-text="kaData.mode === 'production' ? 'Hati-hati! Transaksi bersifat nyata dan akan memotong saldo atau biaya asli.' : 'Aman untuk testing. Transaksi hanya simulasi dan tidak memotong biaya.'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Input Fields (Dynamic Binding) --}}
                        <div x-show="kaData.mode" x-transition.opacity>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">API Token (<span x-text="kaData.mode.toUpperCase()"></span>)</label>
                                <input type="text" name="kiriminaja_token"
                                       x-model="kaData[kaData.mode].token"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3 border font-mono text-xs transition-all duration-300"
                                       placeholder="Masukkan Token..." required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Base URL (<span x-text="kaData.mode.toUpperCase()"></span>)</label>
                                <input type="url" name="kiriminaja_base_url"
                                       x-model="kaData[kaData.mode].base_url"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3 border transition-all duration-300"
                                       placeholder="Biarkan kosong untuk auto-generate">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium shadow-md transition-colors flex items-center">
                            <i class="fas fa-save mr-2"></i> Simpan KiriminAja
                        </button>
                    </div>
                </form>
            </div>

            {{-- 2. TAB TRIPAY --}}
            <div x-show="activeTab === 'tripay'" style="display: none;">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Tripay Payment</h3>
                        <p class="text-xs text-gray-500 mt-1">Status: <span x-text="tpData.mode.toUpperCase()" class="font-bold"></span></p>
                    </div>

                    {{-- Toggle Switch Tripay --}}
                    <div class="flex items-center">
                        <span class="mr-3 text-sm font-medium" :class="tpData.mode === 'sandbox' ? 'text-indigo-600 font-bold' : 'text-gray-500'">SANDBOX</span>

                        {{-- Tambahkan mr-3 --}}
                        <div class="relative inline-block w-12 mr-3 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" id="tp_toggle"
                                   class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 transform translate-x-0"
                                   :class="{'translate-x-full border-red-500': tpData.mode === 'production', 'border-indigo-500': tpData.mode === 'sandbox'}"
                                   @click="tpData.mode = (tpData.mode === 'production' ? 'sandbox' : 'production')"
                                   :checked="tpData.mode === 'production'"/>
                            <label for="tp_toggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-colors duration-300"
                                   :class="{'bg-red-500': tpData.mode === 'production', 'bg-indigo-500': tpData.mode === 'sandbox'}"></label>
                        </div>

                        <span class="ml-1 text-sm font-medium" :class="tpData.mode === 'production' ? 'text-red-600 font-bold' : 'text-gray-500'">PRODUCTION</span>
                    </div>
                </div>

                <form action="{{ route('admin.settings.api.update') }}" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="type" value="tripay">
                    <input type="hidden" name="tripay_mode" x-model="tpData.mode">

                    <div class="space-y-6">
                        {{-- Visual Warning --}}
                        <div class="p-4 rounded-lg border flex items-start"
                             :class="tpData.mode === 'production' ? 'bg-red-50 border-red-200' : 'bg-indigo-50 border-indigo-200'">
                            <div class="flex-shrink-0 mt-0.5">
                                <i class="fas" :class="tpData.mode === 'production' ? 'fa-exclamation-triangle text-red-500' : 'fa-info-circle text-indigo-500'"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium" :class="tpData.mode === 'production' ? 'text-red-800' : 'text-indigo-800'" x-text="tpData.mode === 'production' ? 'Mode Produksi Aktif' : 'Mode Sandbox Aktif'"></h3>
                            </div>
                        </div>

                        <div x-show="tpData.mode" x-transition.opacity>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Merchant Code</label>
                                    <input type="text" name="tripay_merchant_code" x-model="tpData[tpData.mode].merchant_code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">API Key</label>
                                    <input type="text" name="tripay_api_key" x-model="tpData[tpData.mode].api_key" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Private Key</label>
                                <input type="text" name="tripay_private_key" x-model="tpData[tpData.mode].private_key" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium shadow-md transition-colors">
                            Simpan Tripay
                        </button>
                    </div>
                </form>
            </div>

            {{-- 3. TAB DOKU --}}
            <div x-show="activeTab === 'doku'" style="display: none;">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">DOKU Payment</h3>
                        <p class="text-xs text-gray-500 mt-1">Status: <span x-text="dokuData.env.toUpperCase()" class="font-bold"></span></p>
                    </div>

                    {{-- Toggle Switch DOKU --}}
                    <div class="flex items-center">
                        <span class="mr-3 text-sm font-medium" :class="dokuData.env === 'sandbox' ? 'text-indigo-600 font-bold' : 'text-gray-500'">SANDBOX</span>

                        {{-- Tambahkan mr-3 --}}
                        <div class="relative inline-block w-12 mr-3 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" id="doku_toggle"
                                   class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 transform translate-x-0"
                                   :class="{'translate-x-full border-red-500': dokuData.env === 'production', 'border-indigo-500': dokuData.env === 'sandbox'}"
                                   @click="dokuData.env = (dokuData.env === 'production' ? 'sandbox' : 'production')"
                                   :checked="dokuData.env === 'production'"/>
                            <label for="doku_toggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-colors duration-300"
                                   :class="{'bg-red-500': dokuData.env === 'production', 'bg-indigo-500': dokuData.env === 'sandbox'}"></label>
                        </div>

                        <span class="ml-1 text-sm font-medium" :class="dokuData.env === 'production' ? 'text-red-600 font-bold' : 'text-gray-500'">PRODUCTION</span>
                    </div>
                </div>

                <form action="{{ route('admin.settings.api.update') }}" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="type" value="doku">
                    <input type="hidden" name="doku_env" x-model="dokuData.env">

                    <div class="space-y-6">
                        {{-- Visual Warning --}}
                        <div class="p-4 rounded-lg border flex items-start"
                             :class="dokuData.env === 'production' ? 'bg-red-50 border-red-200' : 'bg-indigo-50 border-indigo-200'">
                            <div class="flex-shrink-0 mt-0.5">
                                <i class="fas" :class="dokuData.env === 'production' ? 'fa-exclamation-triangle text-red-500' : 'fa-info-circle text-indigo-500'"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium" :class="dokuData.env === 'production' ? 'text-red-800' : 'text-indigo-800'" x-text="dokuData.env === 'production' ? 'Mode Produksi Aktif' : 'Mode Sandbox Aktif'"></h3>
                            </div>
                        </div>

                        <div x-show="dokuData.env" x-transition.opacity>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Client ID</label>
                                    <input type="text" name="doku_client_id" x-model="dokuData[dokuData.env].client_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Secret Key</label>
                                    <input type="text" name="doku_secret_key" x-model="dokuData[dokuData.env].secret_key" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">DOKU Public Key</label>
                                <textarea name="doku_public_key" x-model="dokuData[dokuData.env].public_key" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 font-mono text-xs"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Merchant Private Key</label>
                                <textarea name="merchant_private_key" x-model="dokuData[dokuData.env].merchant_private_key" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 font-mono text-xs"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- === TAMBAHAN BARU: DOKU MAIN SAC ID === --}}
                    <div class="mt-8 border-t border-gray-100 pt-6">
                        <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wide mb-4">
                            <i class="fas fa-university mr-2 text-indigo-500"></i> Akun Utama (Master Account)
                        </h4>

                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4 rounded-r-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        ID ini diperlukan untuk fitur <b>Pencairan Saldo Utama</b> ke Dompet Seller. Pastikan ID ini adalah <b>Sub Account ID</b> milik akun pusat Sancaka Express.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">DOKU Main SAC ID</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">SAC-</span>
                                </div>
                                {{-- Perhatikan x-model ini: mengambil data global dari JSON controller --}}
                                <input type="text"
                                    name="doku_main_sac_id"
                                    x-model="dokuData.sac_id"
                                    class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 sm:text-sm border-gray-300 rounded-md border p-2"
                                    placeholder="Contoh: 0000-0000000000001">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Format biasanya diawali dengan SAC-XXXX-XXXXX.</p>
                        </div>
                    </div>
                    {{-- ========================================= --}}


                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium shadow-md transition-colors">
                            Simpan DOKU
                        </button>
                    </div>
                </form>
            </div>

            {{-- 4. TAB FONNTE --}}
            <div x-show="activeTab === 'fonnte'" style="display: none;">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Fonnte (WhatsApp)</h3>
                        <p class="text-xs text-gray-500 mt-1">Status: GLOBAL</p>
                    </div>
                </div>

                <form action="{{ route('admin.settings.api.update') }}" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="type" value="fonnte">

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">API Key / Token</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fab fa-whatsapp text-green-500"></i>
                                </div>
                                <input type="text" name="fonnte_api_key" value="{{ $fonnte['api_key'] }}" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md border p-3" placeholder="Masukkan Token Fonnte">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-lg hover:bg-green-700 text-sm font-medium shadow-md transition-colors">
                            Simpan Fonnte
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- Alpine JS --}}
<script src="//unpkg.com/alpinejs" defer></script>

{{-- Inisialisasi Data Alpine di Script Tag (Lebih Aman) --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('apiSettings', () => ({
            activeTab: 'kiriminaja',

            // Mengambil Data Langsung dari PHP Variable yang dilempar Controller
            // Tanpa perlu merinci satu per satu jika struktur di Controller sudah benar
            kaData: @json($kiriminaja),
            tpData: @json($tripay),
            dokuData: @json($doku),
        }))
    })
</script>
@endsection
