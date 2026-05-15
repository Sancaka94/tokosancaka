@extends('layouts.app')

@section('content')
<div class="p-6 max-w-4xl mx-auto w-full">
    
    {{-- Header Section --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Pengaturan API Pembayaran</h2>
        <p class="text-sm text-slate-500 mt-1">Kelola environment gateway untuk transaksi DANA.</p>
    </div>

    {{-- Card Config --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row items-start justify-between gap-6">
                
                {{-- Text Content --}}
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                            <i class="fas fa-wallet text-lg"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Mode DANA (Sandbox / Production)</h3>
                    </div>
                    <p class="text-sm text-slate-500 leading-relaxed ml-13 sm:ml-0">
                        Saklar ini mengatur jalur koneksi API DANA. Jika diaktifkan ke mode <strong class="text-red-500">Production</strong>, sistem akan memproses transaksi menggunakan **uang asli** secara real-time. Pastikan sistem sudah diuji sebelum mengaktifkan.
                    </p>
                </div>
                
                {{-- Tailwind Toggle Switch --}}
                <div class="flex flex-col items-center gap-3 min-w-[120px] pt-2">
                    <label class="relative inline-flex items-center cursor-pointer group">
                        <input type="checkbox" id="danaModeToggle" class="sr-only peer" 
                               {{ $danaMode == '1' ? 'checked' : '' }} 
                               onchange="toggleDanaMode(this.checked)">
                        
                        {{-- Background Toggle --}}
                        <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-red-500 shadow-inner"></div>
                    </label>
                    
                    {{-- Badge Status --}}
                    <span id="modeLabel" class="px-3 py-1 text-[10px] font-bold rounded-lg tracking-wider uppercase transition-colors duration-300 shadow-sm border
                        {{ $danaMode == '1' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-slate-50 text-slate-600 border-slate-200' }}">
                        {{ $danaMode == '1' ? 'PRODUCTION' : 'SANDBOX' }}
                    </span>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
function toggleDanaMode(isChecked) {
    let modeValue = isChecked ? '1' : '0';
    let labelSpan = document.getElementById('modeLabel');

    // Ubah UI sementara (Optimistic Update)
    if (isChecked) {
        labelSpan.innerText = 'PRODUCTION';
        labelSpan.className = 'px-3 py-1 text-[10px] font-bold rounded-lg tracking-wider uppercase transition-colors duration-300 shadow-sm border bg-red-50 text-red-600 border-red-200';
    } else {
        labelSpan.innerText = 'SANDBOX';
        labelSpan.className = 'px-3 py-1 text-[10px] font-bold rounded-lg tracking-wider uppercase transition-colors duration-300 shadow-sm border bg-slate-50 text-slate-600 border-slate-200';
    }

    // Eksekusi Request ke Server
    axios.post('{{ route("admin.settingapi.update-dana-mode") }}', {
        _token: '{{ csrf_token() }}',
        mode: modeValue
    })
    .then(function (response) {
        if(response.data.success) {
            // Bisa diganti pakai toastr/SweetAlert kalau Sancaka punya komponennya
            console.log(response.data.message); 
        }
    })
    .catch(function (error) {
        alert('Gagal mengubah mode API! Silakan cek koneksi atau Log Server.');
        
        // Kembalikan posisi saklar
        let toggleElement = document.getElementById('danaModeToggle');
        toggleElement.checked = !isChecked;
        
        // Kembalikan UI label
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