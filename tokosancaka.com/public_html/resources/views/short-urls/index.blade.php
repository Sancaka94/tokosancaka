@extends('layouts.admin')

@section('title', 'Manage Short URLs')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    <!-- Header Section -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Short URLs</h1>
            <p class="text-gray-500">Kelola semua link singkat Anda di satu tempat.</p>
        </div>
        <a href="/admin/short-urls/create" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm no-underline">
            <span class="mr-2">+</span> Tambah Link Baru
        </a>
    </header>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-6 bg-white rounded-xl border border-gray-100 shadow-sm">
            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold mb-2">Total Links</p>
            <p class="text-3xl font-bold text-gray-800">{{ $totalLinks ?? 0 }}</p>
        </div>
        <div class="p-6 bg-white rounded-xl border border-gray-100 shadow-sm">
            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold mb-2">Total Clicks</p>
            <p class="text-3xl font-bold text-green-600">{{ $totalClicks ?? 0 }}</p>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
        {{ session('success') }}
    </div>
    @endif

    <!-- Data Table & Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

        <!-- Toolbar (Search & Bulk Delete Button) -->
        <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <!-- Form Pencarian -->
            <form action="/admin/short-urls" method="GET" class="w-full md:w-1/3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari original url atau short code..." class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
            </form>

            <!-- Tombol Bulk Delete (Trigger form di bawah) -->
            <button type="button" onclick="confirmBulkDelete()" class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg font-medium transition text-sm">
                Hapus Terpilih
            </button>
        </div>

        <!-- Form pembungkus tabel untuk Bulk Delete -->
        <form id="bulkDeleteForm" action="/admin/short-urls/bulk-destroy" method="POST">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 w-10">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </th>
                            <th class="px-6 py-4 font-semibold">Short Code</th>
                            <th class="px-6 py-4 font-semibold">Original URL</th>
                            <th class="px-6 py-4 font-semibold text-center">Clicks</th>
                            <th class="px-6 py-4 font-semibold">Created</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($shortUrls as $url)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="ids[]" value="{{ $url->id }}" class="checkbox-item rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </td>
                            <td class="px-6 py-4 font-mono text-blue-600 font-medium">
                                <a href="{{ url('/' . $url->short_code) }}" target="_blank">{{ $url->short_code }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-600 truncate max-w-xs" title="{{ $url->original_url }}">
                                {{ $url->original_url }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-gray-700">
                                <span class="bg-blue-50 text-blue-700 py-1 px-3 rounded-full text-xs">{{ $url->clicks }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $url->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right flex justify-end gap-3">
                                <!-- Tombol Edit -->
                                <a href="/admin/short-urls/{{ $url->id }}/edit" class="text-blue-500 hover:text-blue-700 transition">Edit</a>

                                <!-- Tombol Single Delete (Form dikirim via JS agar tidak bentrok bersarang dengan form bulk delete) -->
                                <button type="button" onclick="deleteSingle({{ $url->id }})" class="text-gray-400 hover:text-red-500 transition">Delete</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                Belum ada link yang dibuat atau ditemukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $shortUrls->links() }}
        </div>
    </div>
</div>

<!-- Form tersembunyi untuk single delete -->
<form id="singleDeleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    // JS untuk Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        let checkboxes = document.querySelectorAll('.checkbox-item');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // JS untuk konfirmasi Bulk Delete
    function confirmBulkDelete() {
        let selected = document.querySelectorAll('.checkbox-item:checked').length;
        if (selected === 0) {
            alert('Pilih setidaknya satu data untuk dihapus.');
            return;
        }
        if (confirm('Yakin ingin menghapus ' + selected + ' data terpilih?')) {
            document.getElementById('bulkDeleteForm').submit();
        }
    }

    // JS untuk Single Delete
    function deleteSingle(id) {
        if (confirm('Yakin ingin menghapus URL ini?')) {
            let form = document.getElementById('singleDeleteForm');
            form.action = '/admin/short-urls/' + id;
            form.submit();
        }
    }
</script>
@endsection
