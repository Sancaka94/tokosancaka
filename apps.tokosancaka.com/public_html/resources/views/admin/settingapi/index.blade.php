@extends('layouts.app')

@section('content')
<div class="p-6 max-w-5xl mx-auto w-full">

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Pengaturan API & Pembayaran</h2>
        <p class="text-sm text-slate-500 mt-1">Kelola kredensial dan environment gateway untuk seluruh sistem aplikasi.</p>
    </div>

    {{-- ALERT SUCCESS JIKA ADA --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl flex items-center gap-3 shadow-sm">
            <i class="fas fa-check-circle text-lg"></i>
            <span class="text-sm font-semibold">{!! session('success') !!}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-xl flex items-center gap-3 shadow-sm">
            <i class="fas fa-exclamation-triangle text-lg"></i>
            <span class="text-sm font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    {{-- 1. BAGIAN TOGGLE MODE DANA --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="p-6 sm:p-8 flex flex-col sm:flex-row items-start justify-between gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                        <i class="fas fa-toggle-on text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Mode Sistem DANA Aktif</h3>
                </div>
                <p class="text-sm text-slate-500 ml-13 sm:ml-0">
                    Pilih environment mana yang saat ini digunakan oleh sistem. Jika beralih ke <strong class="text-red-500">Production</strong>, pastikan kredensial di tab Production sudah diisi dengan benar.
                </p>
            </div>

            <div class="flex flex-col items-center gap-3 min-w-[120px] pt-2">
                <label class="relative inline-flex items-center cursor-pointer group">
                    <input type="checkbox" id="danaModeToggle" class="sr-only peer"
                           {{ $danaMode == '1' ? 'checked' : '' }}
                           onchange="toggleDanaMode(this.checked)">
                    <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-red-500 shadow-inner"></div>
                </label>
                <span id="modeLabel" class="px-3 py-1 text-[10px] font-bold rounded-lg tracking-wider uppercase transition-colors duration-300 shadow-sm border {{ $danaMode == '1' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-slate-50 text-slate-600 border-slate-200' }}">
                    {{ $danaMode == '1' ? 'PRODUCTION' : 'SANDBOX' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- 2. KARTU DANA ENTERPRISE --}}
    {{-- ========================================================== --}}
    <div x-data="{ tab: '{{ $dana['mode'] == 'production' ? 'production' : 'sandbox' }}' }" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="flex border-b border-slate-200 bg-slate-50/50">
            <div class="px-6 py-4 flex items-center gap-3 border-r border-slate-200 w-1/4">
                <i class="fas fa-wallet text-blue-600 text-xl"></i>
                <h3 class="font-bold text-slate-800">DANA API</h3>
            </div>
            <button type="button" @click="tab = 'sandbox'" :class="tab === 'sandbox' ? 'border-b-2 border-blue-600 text-blue-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors focus:outline-none">
                <i class="fas fa-flask mr-2"></i> Sandbox
            </button>
            <button type="button" @click="tab = 'production'" :class="tab === 'production' ? 'border-b-2 border-red-600 text-red-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors focus:outline-none">
                <i class="fas fa-rocket mr-2"></i> Production
            </button>
        </div>

        <form action="{{ route('admin.settings.api.update') }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="type" value="dana">
            <input type="hidden" name="dana_mode" :value="tab">

            <div class="p-6 sm:p-8 min-h-[300px]">
                {{-- TAB SANDBOX CONTENT --}}
                <div x-show="tab === 'sandbox'" x-transition.opacity class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant ID (Sandbox)</label>
                            <input type="text" name="dana_merchant_id" :disabled="tab !== 'sandbox'" value="{{ $dana['sandbox']['merchant_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Client ID / Partner ID</label>
                            <input type="text" name="dana_client_id" :disabled="tab !== 'sandbox'" value="{{ $dana['sandbox']['client_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Client Secret</label>
                            <input type="text" name="dana_client_secret" :disabled="tab !== 'sandbox'" value="{{ $dana['sandbox']['client_secret'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Private Key (RSA)</label>
                            <textarea name="dana_private_key" :disabled="tab !== 'sandbox'" rows="4" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs font-mono py-2.5 px-3">{{ $dana['sandbox']['private_key'] ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">DANA Public Key</label>
                            <textarea name="dana_public_key" :disabled="tab !== 'sandbox'" rows="4" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs font-mono py-2.5 px-3">{{ $dana['sandbox']['public_key'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- TAB PRODUCTION CONTENT --}}
                <div x-show="tab === 'production'" style="display: none;" x-transition.opacity class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant ID (Production)</label>
                            <input type="text" name="dana_merchant_id" :disabled="tab !== 'production'" value="{{ $dana['production']['merchant_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Client ID / Partner ID</label>
                            <input type="text" name="dana_client_id" :disabled="tab !== 'production'" value="{{ $dana['production']['client_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Client Secret</label>
                            <input type="text" name="dana_client_secret" :disabled="tab !== 'production'" value="{{ $dana['production']['client_secret'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Private Key (RSA)</label>
                            <textarea name="dana_private_key" :disabled="tab !== 'production'" rows="4" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs font-mono py-2.5 px-3">{{ $dana['production']['private_key'] ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">DANA Public Key</label>
                            <textarea name="dana_public_key" :disabled="tab !== 'production'" rows="4" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs font-mono py-2.5 px-3">{{ $dana['production']['public_key'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-save"></i> Simpan DANA
                </button>
            </div>
        </form>
    </div>

    {{-- ========================================================== --}}
    {{-- 3. KARTU TRIPAY --}}
    {{-- ========================================================== --}}
    <div x-data="{ tab: '{{ $tripay['mode'] == 'production' ? 'production' : 'sandbox' }}' }" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="flex border-b border-slate-200 bg-slate-50/50">
            <div class="px-6 py-4 flex items-center gap-3 border-r border-slate-200 w-1/4">
                <i class="fas fa-credit-card text-blue-600 text-xl"></i>
                <h3 class="font-bold text-slate-800">Tripay</h3>
            </div>
            <button type="button" @click="tab = 'sandbox'" :class="tab === 'sandbox' ? 'border-b-2 border-blue-600 text-blue-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors">
                <i class="fas fa-flask mr-2"></i> Sandbox
            </button>
            <button type="button" @click="tab = 'production'" :class="tab === 'production' ? 'border-b-2 border-red-600 text-red-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors">
                <i class="fas fa-rocket mr-2"></i> Production
            </button>
        </div>

        <form action="{{ route('admin.settings.api.update') }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="type" value="tripay">
            <input type="hidden" name="tripay_mode" :value="tab">

            <div class="p-6 sm:p-8 min-h-[220px]">
                {{-- TAB SANDBOX --}}
                <div x-show="tab === 'sandbox'" x-transition.opacity class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant Code</label>
                        <input type="text" name="tripay_merchant_code" :disabled="tab !== 'sandbox'" value="{{ $tripay['sandbox']['merchant_code'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">API Key</label>
                        <input type="text" name="tripay_api_key" :disabled="tab !== 'sandbox'" value="{{ $tripay['sandbox']['api_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Private Key</label>
                        <input type="text" name="tripay_private_key" :disabled="tab !== 'sandbox'" value="{{ $tripay['sandbox']['private_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                </div>

                {{-- TAB PRODUCTION --}}
                <div x-show="tab === 'production'" style="display: none;" x-transition.opacity class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant Code</label>
                        <input type="text" name="tripay_merchant_code" :disabled="tab !== 'production'" value="{{ $tripay['production']['merchant_code'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">API Key</label>
                        <input type="text" name="tripay_api_key" :disabled="tab !== 'production'" value="{{ $tripay['production']['api_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Private Key</label>
                        <input type="text" name="tripay_private_key" :disabled="tab !== 'production'" value="{{ $tripay['production']['private_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 shadow-sm transition-colors">
                    <i class="fas fa-save mr-2"></i> Simpan Tripay
                </button>
            </div>
        </form>
    </div>

    {{-- ========================================================== --}}
    {{-- 4. KARTU MIDTRANS --}}
    {{-- ========================================================== --}}
    <div x-data="{ tab: '{{ $midtrans['mode'] == 'production' ? 'production' : 'sandbox' }}' }" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="flex border-b border-slate-200 bg-slate-50/50">
            <div class="px-6 py-4 flex items-center gap-3 border-r border-slate-200 w-1/4">
                <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                <h3 class="font-bold text-slate-800">Midtrans</h3>
            </div>
            <button type="button" @click="tab = 'sandbox'" :class="tab === 'sandbox' ? 'border-b-2 border-blue-600 text-blue-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors">
                <i class="fas fa-flask mr-2"></i> Sandbox
            </button>
            <button type="button" @click="tab = 'production'" :class="tab === 'production' ? 'border-b-2 border-red-600 text-red-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors">
                <i class="fas fa-rocket mr-2"></i> Production
            </button>
        </div>

        <form action="{{ route('admin.settings.api.update') }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="type" value="midtrans">
            <input type="hidden" name="midtrans_mode" :value="tab">

            <div class="p-6 sm:p-8 min-h-[300px]">
                {{-- TAB SANDBOX --}}
                <div x-show="tab === 'sandbox'" x-transition.opacity class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant ID</label>
                        <input type="text" name="midtrans_merchant_id" :disabled="tab !== 'sandbox'" value="{{ $midtrans['sandbox']['merchant_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Client Key</label>
                        <input type="text" name="midtrans_client_key" :disabled="tab !== 'sandbox'" value="{{ $midtrans['sandbox']['client_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Server Key</label>
                        <input type="text" name="midtrans_server_key" :disabled="tab !== 'sandbox'" value="{{ $midtrans['sandbox']['server_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                        <p class="text-xs font-bold text-slate-800 uppercase mb-3">SNAP BI Credentials</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">SNAP Client ID</label>
                        <input type="text" name="midtrans_snap_client_id" :disabled="tab !== 'sandbox'" value="{{ $midtrans['sandbox']['snap_client_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">SNAP Client Secret</label>
                        <input type="text" name="midtrans_snap_client_secret" :disabled="tab !== 'sandbox'" value="{{ $midtrans['sandbox']['snap_client_secret'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                </div>

                {{-- TAB PRODUCTION --}}
                <div x-show="tab === 'production'" style="display: none;" x-transition.opacity class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant ID</label>
                        <input type="text" name="midtrans_merchant_id" :disabled="tab !== 'production'" value="{{ $midtrans['production']['merchant_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Client Key</label>
                        <input type="text" name="midtrans_client_key" :disabled="tab !== 'production'" value="{{ $midtrans['production']['client_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Server Key</label>
                        <input type="text" name="midtrans_server_key" :disabled="tab !== 'production'" value="{{ $midtrans['production']['server_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                        <p class="text-xs font-bold text-slate-800 uppercase mb-3">SNAP BI Credentials</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">SNAP Client ID</label>
                        <input type="text" name="midtrans_snap_client_id" :disabled="tab !== 'production'" value="{{ $midtrans['production']['snap_client_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">SNAP Client Secret</label>
                        <input type="text" name="midtrans_snap_client_secret" :disabled="tab !== 'production'" value="{{ $midtrans['production']['snap_client_secret'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 shadow-sm transition-colors">
                    <i class="fas fa-save mr-2"></i> Simpan Midtrans
                </button>
            </div>
        </form>
    </div>

    {{-- ========================================================== --}}
    {{-- 5. KARTU KIRIMINAJA --}}
    {{-- ========================================================== --}}
    <div x-data="{ tab: '{{ $kiriminaja['mode'] == 'production' ? 'production' : 'staging' }}' }" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="flex border-b border-slate-200 bg-slate-50/50">
            <div class="px-6 py-4 flex items-center gap-3 border-r border-slate-200 w-1/4">
                <i class="fas fa-truck text-blue-600 text-xl"></i>
                <h3 class="font-bold text-slate-800">KiriminAja</h3>
            </div>
            <button type="button" @click="tab = 'staging'" :class="tab === 'staging' ? 'border-b-2 border-blue-600 text-blue-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors">
                <i class="fas fa-flask mr-2"></i> Staging (Sandbox)
            </button>
            <button type="button" @click="tab = 'production'" :class="tab === 'production' ? 'border-b-2 border-red-600 text-red-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors">
                <i class="fas fa-rocket mr-2"></i> Production
            </button>
        </div>

        <form action="{{ route('admin.settings.api.update') }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="type" value="kiriminaja">
            <input type="hidden" name="kiriminaja_mode" :value="tab">

            <div class="p-6 sm:p-8 min-h-[180px]">
                {{-- TAB STAGING --}}
                <div x-show="tab === 'staging'" x-transition.opacity class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">API Token</label>
                        <input type="text" name="kiriminaja_token" :disabled="tab !== 'staging'" value="{{ $kiriminaja['staging']['token'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Base URL</label>
                        <input type="url" name="kiriminaja_base_url" :disabled="tab !== 'staging'" value="{{ $kiriminaja['staging']['base_url'] ?? '' }}" placeholder="Otomatis terisi jika kosong" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                </div>

                {{-- TAB PRODUCTION --}}
                <div x-show="tab === 'production'" style="display: none;" x-transition.opacity class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">API Token</label>
                        <input type="text" name="kiriminaja_token" :disabled="tab !== 'production'" value="{{ $kiriminaja['production']['token'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Base URL</label>
                        <input type="url" name="kiriminaja_base_url" :disabled="tab !== 'production'" value="{{ $kiriminaja['production']['base_url'] ?? '' }}" placeholder="Otomatis terisi jika kosong" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 shadow-sm transition-colors">
                    <i class="fas fa-save mr-2"></i> Simpan KiriminAja
                </button>
            </div>
        </form>
    </div>

    {{-- ========================================================== --}}
    {{-- 6. KARTU DOKU --}}
    {{-- ========================================================== --}}
    <div x-data="{ tab: '{{ $doku['env'] == 'production' ? 'production' : 'sandbox' }}' }" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="flex border-b border-slate-200 bg-slate-50/50">
            <div class="px-6 py-4 flex items-center gap-3 border-r border-slate-200 w-1/4">
                <i class="fas fa-shield-alt text-blue-600 text-xl"></i>
                <h3 class="font-bold text-slate-800">DOKU API</h3>
            </div>
            <button type="button" @click="tab = 'sandbox'" :class="tab === 'sandbox' ? 'border-b-2 border-blue-600 text-blue-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors">
                <i class="fas fa-flask mr-2"></i> Sandbox
            </button>
            <button type="button" @click="tab = 'production'" :class="tab === 'production' ? 'border-b-2 border-red-600 text-red-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors">
                <i class="fas fa-rocket mr-2"></i> Production
            </button>
        </div>

        <form action="{{ route('admin.settings.api.update') }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="type" value="doku">
            <input type="hidden" name="doku_env" :value="tab">

            <div class="p-6 sm:p-8 min-h-[300px]">
                {{-- TAB SANDBOX --}}
                <div x-show="tab === 'sandbox'" x-transition.opacity class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Client ID</label>
                        <input type="text" name="doku_client_id" :disabled="tab !== 'sandbox'" value="{{ $doku['sandbox']['client_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Secret Key</label>
                        <input type="text" name="doku_secret_key" :disabled="tab !== 'sandbox'" value="{{ $doku['sandbox']['secret_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Public Key</label>
                        <textarea name="doku_public_key" :disabled="tab !== 'sandbox'" rows="3" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs font-mono py-2.5 px-3">{{ $doku['sandbox']['public_key'] ?? '' }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant Private Key</label>
                        <textarea name="merchant_private_key" :disabled="tab !== 'sandbox'" rows="3" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs font-mono py-2.5 px-3">{{ $doku['sandbox']['merchant_private_key'] ?? '' }}</textarea>
                    </div>
                </div>

                {{-- TAB PRODUCTION --}}
                <div x-show="tab === 'production'" style="display: none;" x-transition.opacity class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Client ID</label>
                        <input type="text" name="doku_client_id" :disabled="tab !== 'production'" value="{{ $doku['production']['client_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Secret Key</label>
                        <input type="text" name="doku_secret_key" :disabled="tab !== 'production'" value="{{ $doku['production']['secret_key'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3 font-mono">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Public Key</label>
                        <textarea name="doku_public_key" :disabled="tab !== 'production'" rows="3" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs font-mono py-2.5 px-3">{{ $doku['production']['public_key'] ?? '' }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant Private Key</label>
                        <textarea name="merchant_private_key" :disabled="tab !== 'production'" rows="3" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs font-mono py-2.5 px-3">{{ $doku['production']['merchant_private_key'] ?? '' }}</textarea>
                    </div>
                </div>

                {{-- GLOBAL DOKU SETTING --}}
                <div class="mt-6 pt-5 border-t border-slate-100">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">DOKU Main SAC ID (Global)</label>
                    <input type="text" name="doku_main_sac_id" value="{{ $doku['sac_id'] ?? '' }}" class="w-full sm:w-1/2 rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 shadow-sm transition-colors">
                    <i class="fas fa-save mr-2"></i> Simpan DOKU
                </button>
            </div>
        </form>
    </div>

    {{-- ========================================================== --}}
    {{-- 7. MAPBOX, ZONASI OJOL & KOMISI (NO TABS) --}}
    {{-- ========================================================== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50 flex items-center gap-3">
            <i class="fas fa-map-marked-alt text-blue-600 text-xl"></i>
            <div>
                <h3 class="font-bold text-slate-800">Mapbox, Zonasi & Komisi Ojol</h3>
                <p class="text-[11px] text-slate-500">Konfigurasi Tarif Ekspedisi Internal (Sancaka Express) & Ojol (Global).</p>
            </div>
        </div>

        <form action="{{ route('admin.settings.api.update') }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="type" value="mapbox">

            <div class="p-6 sm:p-8 space-y-8 min-h-[300px]">

                {{-- A. MAPBOX TOKEN --}}
                <div>
                    <h4 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">A. Mapbox API Token</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Public Token (pk.xxx)</label>
                            <input type="text" name="mapbox_public_token" value="{{ $mapbox['public_token'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Secret Token (sk.xxx)</label>
                            <input type="text" name="mapbox_secret_token" value="{{ $mapbox['secret_token'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 font-mono">
                        </div>
                    </div>
                </div>

                {{-- B. TARIF EXPRESS --}}
                <div>
                    <h4 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">B. Tarif Sancaka Express (Mobil/Kargo)</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Base Fare (Rp)</label>
                            <input type="number" name="base_fare" value="{{ $mapbox['base_fare'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Tarif per KM (Rp)</label>
                            <input type="number" name="price_per_km" value="{{ $mapbox['price_per_km'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Tarif per KG (Rp)</label>
                            <input type="number" name="price_per_kg" value="{{ $mapbox['price_per_kg'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Pembagi Volume</label>
                            <input type="number" name="volume_divisor" value="{{ $mapbox['volume_divisor'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Fee COD (%)</label>
                            <input type="number" step="0.1" name="cod_fee_percent" value="{{ $mapbox['cod_fee_percent'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                    </div>
                </div>

                {{-- C. ZONASI OJOL KEMENHUB --}}
                <div>
                    <h4 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">C. Zonasi Tarif Ojek (Kemenhub)</h4>

                    {{-- Zona 1 --}}
                    <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <h5 class="font-bold text-sm text-slate-700 mb-3">Zona I</h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Wilayah (Pisahkan Koma)</label>
                                <textarea name="zona_1_wilayah" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-2 px-3">{{ $mapbox['zonasi']['zona_1']['wilayah'] ?? '' }}</textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Tarif Minimal (Rp)</label>
                                <input type="number" name="zona_1_tarif_minimal" value="{{ $mapbox['zonasi']['zona_1']['tarif_minimal'] ?? '' }}" class="w-full rounded-lg border-slate-300 shadow-sm text-sm py-2 px-3">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Tarif / KM (Rp)</label>
                                <input type="number" name="zona_1_tarif_per_km" value="{{ $mapbox['zonasi']['zona_1']['tarif_per_km'] ?? '' }}" class="w-full rounded-lg border-slate-300 shadow-sm text-sm py-2 px-3">
                            </div>
                        </div>
                    </div>

                    {{-- Zona 2 --}}
                    <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <h5 class="font-bold text-sm text-slate-700 mb-3">Zona II (Jabodetabek)</h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Wilayah</label>
                                <textarea name="zona_2_wilayah" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm text-xs py-2 px-3">{{ $mapbox['zonasi']['zona_2']['wilayah'] ?? '' }}</textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Tarif Minimal (Rp)</label>
                                <input type="number" name="zona_2_tarif_minimal" value="{{ $mapbox['zonasi']['zona_2']['tarif_minimal'] ?? '' }}" class="w-full rounded-lg border-slate-300 shadow-sm text-sm py-2 px-3">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Tarif / KM (Rp)</label>
                                <input type="number" name="zona_2_tarif_per_km" value="{{ $mapbox['zonasi']['zona_2']['tarif_per_km'] ?? '' }}" class="w-full rounded-lg border-slate-300 shadow-sm text-sm py-2 px-3">
                            </div>
                        </div>
                    </div>

                    {{-- Zona 3 --}}
                    <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <h5 class="font-bold text-sm text-slate-700 mb-3">Zona III</h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Wilayah</label>
                                <textarea name="zona_3_wilayah" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm text-xs py-2 px-3">{{ $mapbox['zonasi']['zona_3']['wilayah'] ?? '' }}</textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Tarif Minimal (Rp)</label>
                                <input type="number" name="zona_3_tarif_minimal" value="{{ $mapbox['zonasi']['zona_3']['tarif_minimal'] ?? '' }}" class="w-full rounded-lg border-slate-300 shadow-sm text-sm py-2 px-3">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Tarif / KM (Rp)</label>
                                <input type="number" name="zona_3_tarif_per_km" value="{{ $mapbox['zonasi']['zona_3']['tarif_per_km'] ?? '' }}" class="w-full rounded-lg border-slate-300 shadow-sm text-sm py-2 px-3">
                            </div>
                        </div>
                    </div>

                    {{-- Fallback Ojol --}}
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tarif Minimal Ojol (Default)</label>
                            <input type="number" name="ojek_base_fare" value="{{ $mapbox['ojek_base_fare'] ?? '' }}" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tarif / KM Ojol (Default)</label>
                            <input type="number" name="ojek_price_per_km" value="{{ $mapbox['ojek_price_per_km'] ?? '' }}" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3">
                        </div>
                    </div>
                </div>

                {{-- D. KOMISI & PAJAK --}}
                <div>
                    <h4 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">D. Komisi & Pajak Aplikasi</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                        <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl">
                            <h5 class="text-xs font-bold text-blue-800 mb-3">Komisi Admin PUSAT</h5>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Tipe</label>
                            <select name="komisi_admin_type" class="w-full mb-3 rounded-lg border-slate-200 text-xs py-2 px-3">
                                <option value="percent" {{ ($mapbox['komisi']['admin_type'] ?? '') == 'percent' ? 'selected' : '' }}>Persen (%)</option>
                                <option value="nominal" {{ ($mapbox['komisi']['admin_type'] ?? '') == 'nominal' ? 'selected' : '' }}>Nominal (Rp)</option>
                            </select>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Besaran</label>
                            <input type="number" name="komisi_admin_amount" value="{{ $mapbox['komisi']['admin_amount'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-xs py-2 px-3">
                        </div>

                        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-xl">
                            <h5 class="text-xs font-bold text-emerald-800 mb-3">Potongan Driver</h5>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Tipe</label>
                            <select name="komisi_driver_type" class="w-full mb-3 rounded-lg border-slate-200 text-xs py-2 px-3">
                                <option value="percent" {{ ($mapbox['komisi']['driver_type'] ?? '') == 'percent' ? 'selected' : '' }}>Persen (%)</option>
                                <option value="nominal" {{ ($mapbox['komisi']['driver_type'] ?? '') == 'nominal' ? 'selected' : '' }}>Nominal (Rp)</option>
                            </select>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Besaran</label>
                            <input type="number" name="komisi_driver_amount" value="{{ $mapbox['komisi']['driver_amount'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-xs py-2 px-3">
                        </div>

                        <div class="bg-orange-50 border border-orange-100 p-4 rounded-xl">
                            <h5 class="text-xs font-bold text-orange-800 mb-3">Biaya Lainnya</h5>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Pajak / PPn (%)</label>
                            <input type="number" step="0.1" name="komisi_pajak_percent" value="{{ $mapbox['komisi']['pajak_percent'] ?? '' }}" class="w-full mb-3 rounded-lg border-slate-200 text-xs py-2 px-3">

                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Biaya Layanan (Rp)</label>
                            <input type="number" name="komisi_biaya_nominal" value="{{ $mapbox['komisi']['biaya_nominal'] ?? '' }}" class="w-full mb-3 rounded-lg border-slate-200 text-xs py-2 px-3">

                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Label Biaya Layanan</label>
                            <input type="text" name="komisi_biaya_ket" value="{{ $mapbox['komisi']['biaya_ket'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-xs py-2 px-3">
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 shadow-sm transition-colors">
                    <i class="fas fa-save mr-2"></i> Simpan Mapbox & Tarif
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
{{-- WAJIB ADA: Memanggil library Alpine.js agar sistem Tab-nya berfungsi --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
function toggleDanaMode(isChecked) {
    let modeValue = isChecked ? '1' : '0';
    let labelSpan = document.getElementById('modeLabel');

    if (isChecked) {
        labelSpan.innerText = 'PRODUCTION';
        labelSpan.className = 'px-3 py-1 text-[10px] font-bold rounded-lg tracking-wider uppercase transition-colors duration-300 shadow-sm border bg-red-50 text-red-600 border-red-200';
    } else {
        labelSpan.innerText = 'SANDBOX';
        labelSpan.className = 'px-3 py-1 text-[10px] font-bold rounded-lg tracking-wider uppercase transition-colors duration-300 shadow-sm border bg-slate-50 text-slate-600 border-slate-200';
    }

    axios.post('{{ route("admin.settings.api.toggleDebug") }}', {
        // Menggunakan endpoint global toggle agar rapi
        _token: '{{ csrf_token() }}',
        mode: modeValue
        // Abaikan parameter payload ini, pastikan route "update-dana-mode" masih ada jika dipakai
    });
}
</script>
@endpush
