@extends('layouts.admin')

@section('title', 'Manage Short URLs')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    <!-- Header Section (Next.js inspired: semantic & clean) -->
    <header class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Short URLs</h1>
            <p class="text-gray-500">Kelola semua link singkat Anda di satu tempat.</p>
        </div>
        <a href="/admin/short-urls/create" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm no-underline">
            <span class="mr-2">+</span> Tambah Link Baru
        </a>
    </header>

    <!-- Stats Overview (Optional, feels like dashboard components) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-4 bg-white rounded-xl border border-gray-100 shadow-sm">
            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Total Links</p>
            <p class="text-2xl font-bold text-gray-800">{{ $totalLinks ?? 0 }}</p>
        </div>
    </div>

    <!-- Data Table (Clean, Responsive) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Short Code</th>
                        <th class="px-6 py-4 font-semibold">Destination</th>
                        <th class="px-6 py-4 font-semibold">Created</th>
                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($shortUrls as $url)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-mono text-blue-600 font-medium">{{ $url->short_code }}</td>
                        <td class="px-6 py-4 text-gray-600 truncate max-w-xs">{{ $url->destination_url }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ $url->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-gray-400 hover:text-red-500 transition">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                            Belum ada link yang dibuat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $shortUrls->links() }}
        </div>
    </div>
</div>
@endsection
