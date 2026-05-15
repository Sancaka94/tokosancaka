@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Pengaturan API Pembayaran</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label><strong>Mode DANA (Sandbox / Production)</strong></label>
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" class="custom-control-input" id="danaModeToggle" 
                                   {{ $danaMode == '1' ? 'checked' : '' }} 
                                   onchange="toggleDanaMode(this.checked)">
                            
                            <label class="custom-control-label" for="danaModeToggle">
                                Status Saat Ini: <span id="modeLabel" class="badge {{ $danaMode == '1' ? 'badge-danger' : 'badge-secondary' }}">
                                    {{ $danaMode == '1' ? 'PRODUCTION' : 'SANDBOX' }}
                                </span>
                            </label>
                        </div>
                        <small class="text-muted">Jika diaktifkan (Production), sistem akan menggunakan uang asli.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Gunakan @push('scripts') atau @section('scripts') tergantung bawaan template app.blade.php kamu --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
function toggleDanaMode(isChecked) {
    // Ubah boolean jadi angka 1 atau 0
    let modeValue = isChecked ? '1' : '0';
    let labelSpan = document.getElementById('modeLabel');

    // Ubah teks dan warna label sementara sambil menunggu respon server
    if (isChecked) {
        labelSpan.innerText = 'PRODUCTION';
        labelSpan.className = 'badge badge-danger';
    } else {
        labelSpan.innerText = 'SANDBOX';
        labelSpan.className = 'badge badge-secondary';
    }

    // Kirim data ke Controller pakai Axios
    axios.post('{{ route("admin.settingapi.update-dana-mode") }}', {
        _token: '{{ csrf_token() }}', // Wajib bawa CSRF token di Laravel
        mode: modeValue
    })
    .then(function (response) {
        if(response.data.success) {
            // Munculkan notifikasi sukses (bisa diganti pakai SweetAlert/Toastr kalau ada)
            alert(response.data.message); 
        }
    })
    .catch(function (error) {
        alert('Gagal mengubah mode! Silakan cek koneksi atau hubungi developer.');
        // Kembalikan saklar ke posisi semula jika gagal
        document.getElementById('danaModeToggle').checked = !isChecked;
        
        // Kembalikan UI label ke posisi semula
        if (!isChecked) {
            labelSpan.innerText = 'PRODUCTION';
            labelSpan.className = 'badge badge-danger';
        } else {
            labelSpan.innerText = 'SANDBOX';
            labelSpan.className = 'badge badge-secondary';
        }
    });
}
</script>
@endpush