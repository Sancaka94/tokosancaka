@extends('layouts.admin')

@section('title', 'Kotak Masuk')
@section('page-title', 'Kotak Masuk')

@section('content')
{{-- Pastikan layout Anda memiliki meta tag CSRF di <head> untuk AJAX --}}
{{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}

<div class="flex flex-col h-[calc(100vh-150px)] bg-white rounded-xl shadow-lg overflow-hidden">

    {{-- Header & Pencarian --}}
    <header class="flex items-center justify-between p-3 border-b border-gray-200 flex-shrink-0">
        <div class="relative w-full max-w-md hidden sm:block">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" placeholder="Cari di email..." class="w-full bg-gray-100 border border-transparent rounded-full py-2.5 pl-12 pr-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white">
        </div>

        <div class="flex items-center gap-4 ml-auto">
            <a href="{{ route('admin.email.index') }}" class="p-2 rounded-full hover:bg-gray-100 text-gray-600" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </a>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-700 hidden lg:block">{{ Auth::user()->email ?? 'pengguna@email.com' }}</span>
                <img src="https://placehold.co/32x32/7F9CF5/EBF4FF?text={{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}" alt="Avatar" class="w-8 h-8 rounded-full">
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        {{-- Sidebar Navigasi --}}
        <aside class="w-64 bg-white border-r border-gray-200 flex-shrink-0 hidden md:flex md:flex-col">
            <div class="p-4">
                <a href="{{ route('admin.email.create') }}" class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg shadow hover:bg-blue-700 transition-colors duration-200">
                    <i class="fas fa-pencil-alt"></i>
                    <span>Tulis Email</span>
                </a>
            </div>
            <nav class="p-2 flex-1">
                <ul>
                    {{-- PERBAIKAN: Menambahkan kelas aktif secara dinamis --}}
                    <li><a href="{{ route('admin.email.index') }}" class="flex items-center gap-3 py-2 px-4 rounded-lg {{ request()->routeIs('admin.email.index') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-100' }}"><i class="fas fa-inbox fa-fw"></i> Inbox</a></li>
                    <li><a href="#" class="flex items-center gap-3 py-2 px-4 rounded-lg text-gray-600 hover:bg-gray-100"><i class="fas fa-paper-plane fa-fw"></i> Terkirim</a></li>
                    <li><a href="#" class="flex items-center gap-3 py-2 px-4 rounded-lg text-gray-600 hover:bg-gray-100"><i class="fas fa-file-alt fa-fw"></i> Draft</a></li>
                    <li><a href="#" class="flex items-center gap-3 py-2 px-4 rounded-lg text-gray-600 hover:bg-gray-100"><i class="fas fa-trash fa-fw"></i> Sampah</a></li>
                </ul>
            </nav>
        </aside>

        {{-- Konten Utama (Daftar Email dari IMAP) --}}
        <main class="flex-1 flex flex-col overflow-hidden">
            
            <div id="alert-container" class="px-4 pt-4">
                {{-- Pesan feedback akan ditampilkan di sini oleh JavaScript --}}
            </div>

            {{-- Menampilkan Pesan Feedback dari Controller (saat reload halaman) --}}
            @if (Session::has('success'))
                <div class="m-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                    <p>{{ Session::get('success') }}</p>
                </div>
            @endif
            @if (Session::has('error') || $errors->has('connection'))
                <div class="m-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Error</p>
                    <p>{{ Session::get('error') ?? $errors->first('connection') }}</p>
                </div>
            @endif

            {{-- Header Daftar Email --}}
            <div class="hidden md:flex border-b border-gray-200 px-4 py-2 font-semibold text-gray-600 text-sm">
                <div class="w-[25%]">Pengirim</div>
                <div class="w-[50%]">Subjek</div>
                <div class="w-[25%] text-right">Tanggal</div>
            </div>

            {{-- Daftar Email (Hasil dari IMAP) --}}
            <div class="flex-1 overflow-y-auto">
                <div class="divide-y divide-gray-200">
                    @isset($messages)
                        @forelse ($messages as $message)
                            @php
                                $dt = \Carbon\Carbon::parse($message->getDate());
                                $uid = $message->getUid();
                            @endphp
                            {{-- PERBAIKAN: Mengubah struktur untuk menambahkan tombol hapus --}}
                            <div id="message-{{ $uid }}" class="email-item group flex items-center gap-4 p-4 {{ $message->getFlags()->has('seen') ? 'bg-white' : 'bg-blue-50 font-semibold' }} hover:shadow-md transition-shadow duration-150">
                                <a href="{{ route('admin.email.show', ['messageId' => $uid]) }}" class="flex-grow flex items-center gap-4 truncate">
                                    <div class="w-[25%] truncate hidden md:block">
                                        {{ $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail ?? 'Tidak diketahui' }}
                                    </div>
                                    <div class="w-full md:w-[50%]">
                                        <p class="truncate">{{ $message->getSubject() ?? '(Tanpa Subjek)' }}</p>
                                    </div>
                                </a>
                                <div class="w-[25%] flex items-center justify-end text-right text-gray-500 text-sm ml-auto">
                                    {{-- Kode Baru --}}
                                    <span>{{ \Carbon\Carbon::parse($message->getDate())->translatedFormat('l, d F Y H:i') }}</span>
                                    {{-- Tombol Hapus (hanya muncul saat hover) --}}
                                    <button data-id="{{ $uid }}" class="delete-btn opacity-0 group-hover:opacity-100 transition-opacity duration-200 p-2 rounded-full hover:bg-red-100 text-gray-500 hover:text-red-600" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center h-96 text-gray-500">
                                <i class="fas fa-envelope-open fa-4x mb-4 text-gray-300"></i>
                                <h3 class="text-xl font-medium">Kotak Masuk Kosong</h3>
                            </div>
                        @endforelse
                    @endisset
                </div>
            </div>

            {{-- Footer Paginasi --}}
            @if(isset($messages) && $messages instanceof \Illuminate\Pagination\LengthAwarePaginator && $messages->hasPages())
                <footer class="flex items-center justify-between p-3 border-t bg-white flex-shrink-0 text-sm text-gray-600">
                    <div>
                        Menampilkan
                        <span class="font-semibold">{{ $messages->firstItem() }}</span> - <span class="font-semibold">{{ $messages->lastItem() }}</span> dari <span class="font-semibold">{{ $messages->total() }}</span> hasil
                    </div>
                    <div>
                        {{ $messages->links() }}
                    </div>
                </footer>
            @endif
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation(); // Mencegah klik trigger link ke detail email

            const messageId = this.dataset.id;
            
            // Praktik terbaik adalah menggunakan modal custom, bukan confirm()
            if (confirm('Apakah Anda yakin ingin menghapus email ini secara permanen?')) {
                fetch(`/admin/imap/${messageId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const emailRow = document.getElementById(`message-${messageId}`);
                        if (emailRow) {
                            emailRow.style.transition = 'opacity 0.5s ease';
                            emailRow.style.opacity = '0';
                            setTimeout(() => emailRow.remove(), 500);
                        }
                        showAlert('success', data.message || 'Email berhasil dihapus.');
                    } else {
                        showAlert('error', data.message || 'Gagal menghapus email.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Terjadi kesalahan. Silakan coba lagi.');
                });
            }
        });
    });

    function showAlert(type, message) {
        const alertContainer = document.getElementById('alert-container');
        const alertClass = type === 'success' 
            ? 'bg-green-100 border-green-500 text-green-700' 
            : 'bg-red-100 border-red-500 text-red-700';

        const alertElement = `
            <div class="alert-item ${alertClass} border-l-4 p-4 rounded-md" role="alert">
                <p>${message}</p>
            </div>
        `;
        
        alertContainer.innerHTML = alertElement;

        setTimeout(() => {
            const alertItem = alertContainer.querySelector('.alert-item');
            if (alertItem) {
                alertItem.style.transition = 'opacity 0.5s ease';
                alertItem.style.opacity = '0';
                setTimeout(() => alertItem.remove(), 500);
            }
        }, 3000); // Pesan akan hilang setelah 3 detik
    }
});
</script>
@endpush
