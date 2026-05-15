@extends('layouts.app')

@section('content')
<div class="p-6 max-w-5xl mx-auto w-full">
    
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Pengaturan API Pembayaran</h2>
        <p class="text-sm text-slate-500 mt-1">Kelola kredensial dan environment gateway untuk transaksi DANA.</p>
    </div>

    {{-- ALERT SUCCESS JIKA ADA --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <span class="text-sm font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    {{-- 1. BAGIAN TOGGLE MODE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="p-6 sm:p-8 flex flex-col sm:flex-row items-start justify-between gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                        <i class="fas fa-toggle-on text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Mode Sistem Aktif</h3>
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

    {{-- 2. BAGIAN FORM KREDENSIAL DENGAN ALPINE JS TABS --}}
    <div x-data="{ tab: 'sandbox' }" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        {{-- Header Tabs --}}
        <div class="flex border-b border-slate-200 bg-slate-50/50">
            <button @click="tab = 'sandbox'" :class="tab === 'sandbox' ? 'border-b-2 border-blue-600 text-blue-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors focus:outline-none">
                <i class="fas fa-flask mr-2"></i> Kredensial Sandbox
            </button>
            <button @click="tab = 'production'" :class="tab === 'production' ? 'border-b-2 border-red-600 text-red-600 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-4 px-6 text-sm font-bold uppercase tracking-wider transition-colors focus:outline-none">
                <i class="fas fa-rocket mr-2"></i> Kredensial Production
            </button>
        </div>

        <form action="{{ route('admin.settingapi.save-credentials') }}" method="POST">
            @csrf
            
            <div class="p-6 sm:p-8">
                {{-- TAB SANDBOX CONTENT --}}
                <div x-show="tab === 'sandbox'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant ID (Sandbox)</label>
                            <input type="text" name="dana_sandbox_merchant_id" value="{{ $settings['dana_sandbox_merchant_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Client ID / Partner ID</label>
                            <input type="text" name="dana_sandbox_client_id" value="{{ $settings['dana_sandbox_client_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Client Secret</label>
                        <input type="text" name="dana_sandbox_client_secret" value="{{ $settings['dana_sandbox_client_secret'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Private Key (RSA)</label>
                        <textarea name="dana_sandbox_private_key" rows="6" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs font-mono py-2.5 px-3">{{ $settings['dana_sandbox_private_key'] ?? '' }}</textarea>
                    </div>
                </div>

                {{-- TAB PRODUCTION CONTENT --}}
                <div x-show="tab === 'production'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5">
                    <div class="p-4 bg-red-50 border border-red-100 rounded-xl flex gap-3 mb-2 text-red-700">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <p class="text-xs font-medium leading-relaxed"><strong>Peringatan:</strong> Data di bawah ini akan digunakan untuk transaksi riil yang memotong saldo pengguna. Pastikan seluruh teks tidak ada spasi yang tertinggal saat di-copy.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant ID (Production)</label>
                            <input type="text" name="dana_prod_merchant_id" value="{{ $settings['dana_prod_merchant_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Client ID / Partner ID</label>
                            <input type="text" name="dana_prod_client_id" value="{{ $settings['dana_prod_client_id'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Client Secret</label>
                        <input type="text" name="dana_prod_client_secret" value="{{ $settings['dana_prod_client_secret'] ?? '' }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5 px-3">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Private Key (RSA)</label>
                        <textarea name="dana_prod_private_key" rows="6" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs font-mono py-2.5 px-3">{{ $settings['dana_prod_private_key'] ?? '' }}</textarea>
                    </div>
                </div>

            </div>
            
            {{-- Form Footer --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-save"></i> Simpan Kredensial
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
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

    axios.post('{{ route("admin.settingapi.update-dana-mode") }}', {
        _token: '{{ csrf_token() }}',
        mode: modeValue
    })
    .then(function (response) {
        if(response.data.success) {
            console.log(response.data.message); 
        }
    })
    .catch(function (error) {
        alert('Gagal mengubah mode API!');
        let toggleElement = document.getElementById('danaModeToggle');
        toggleElement.checked = !isChecked;
        if (!isChecked) {
            labelSpan.innerText = 'PRODUCTION';
            labelSpan.className = 'px-3 py-1 text-[10px] font-bold rounded-lg tracking-wider uppercase transition-colors duration-300 shadow-sm border bg-red-50 text-red-600 border-red-200';
        } else {
            labelSpan.innerText = 'SANDBOX';
            labelSpan.className = 'px-3 py-1 text-[10px] font-bold rounded-lg tracking-wider uppercase transition-colors duration-300 shadow-sm border bg-slate-50 text-slate-600 border-slate-200';
        }
    });
}
</script>
@endpush