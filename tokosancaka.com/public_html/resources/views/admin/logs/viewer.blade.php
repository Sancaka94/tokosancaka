{{-- resources/views/admin/logs/viewer.blade.php --}}
@extends('layouts.admin') 

@section('title', 'Raw Log Viewer')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">📂 Isi Log ({{ $maxLines }} Baris Terakhir)</h1>
    
    {{-- TOMBOL BARU UNTUK HAPUS LOG --}}
    <button id="clearLogsBtn" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition duration-150">
        <i class="fas fa-trash-alt mr-2"></i> Hapus Semua Log
    </button>
</div>

<div class="bg-gray-800 border border-gray-700 p-4 rounded-lg">
    <pre class="overflow-x-scroll text-green-300" style="font-family: monospace; font-size: 13px;">{!! e($logs) !!}</pre>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('clearLogsBtn').addEventListener('click', function() {
    Swal.fire({
        title: 'Anda Yakin?',
        text: "Anda akan menghapus SEMUA isi log! Tindakan ini tidak dapat dibatalkan.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus Log!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            
            // Lakukan permintaan AJAX POST ke Controller
            fetch('{{ route('admin.logs.clear') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', // Wajib untuk POST di Laravel
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire(
                        'Dihapus!',
                        data.message,
                        'success'
                    ).then(() => {
                        // Reload halaman untuk melihat log yang kosong
                        window.location.reload(); 
                    });
                } else {
                    Swal.fire(
                        'Gagal!',
                        data.message,
                        'error'
                    );
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                Swal.fire(
                    'Error Server!',
                    'Terjadi kesalahan saat menghubungi server.',
                    'error'
                );
            });
        }
    });
});
</script>
@endpush