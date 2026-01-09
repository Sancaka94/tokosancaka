{{-- File: resources/views/admin/logs/viewer.blade.php --}}
@extends('layouts.app') 

@section('title', 'Raw Log Viewer')

{{-- Load SweetAlert2 --}}
@push('styles')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-800">
                <i class="fas fa-terminal text-gray-600 mr-2"></i>System Log Viewer
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Menampilkan <b>{{ $maxLines }}</b> baris terakhir dari file <code>laravel.log</code>.
            </p>
        </div>
        
        {{-- Tombol Hapus Log --}}
        <button id="clearLogsBtn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-lg shadow-red-200 transition-all flex items-center gap-2">
            <i class="fas fa-trash-alt"></i> Hapus Semua Log
        </button>
    </div>

    {{-- Log Content Box (Terminal Style) --}}
    <div class="bg-gray-900 border border-gray-700 rounded-2xl shadow-2xl overflow-hidden">
        {{-- Terminal Header --}}
        <div class="bg-gray-800 px-4 py-2 border-b border-gray-700 flex justify-between items-center">
            <div class="flex gap-2">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <div class="text-xs text-gray-400 font-mono">storage/logs/laravel.log</div>
        </div>

        {{-- Log Body --}}
        <div class="p-4 overflow-x-auto custom-scrollbar">
            <pre class="font-mono text-xs leading-relaxed whitespace-pre" 
                 style="font-family: 'Fira Code', 'Consolas', monospace; color: #a6e22e;">
@if(empty(trim($logs)))
<span class="text-gray-500 italic">// File log saat ini bersih/kosong.</span>
@else
{!! e($logs) !!}
@endif
            </pre>
        </div>
    </div>

</div>
@endsection

{{-- Script untuk Handle Tombol Hapus --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clearBtn = document.getElementById('clearLogsBtn');

    if(clearBtn) {
        clearBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Hapus File Log?',
                text: "Tindakan ini akan mengosongkan seluruh isi file laravel.log secara permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#fff',
                customClass: {
                    popup: 'rounded-2xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    // Tampilkan Loading
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang membersihkan file log.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Request AJAX ke Route
                    fetch("{{ route('admin.logs.clear') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'Terjadi kesalahan.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Gagal menghubungi server atau file tidak ditemukan.',
                        });
                    });
                }
            });
        });
    }
});
</script>
@endpush