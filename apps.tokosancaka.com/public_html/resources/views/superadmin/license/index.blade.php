@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">

    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Manajemen Lisensi</h2>
            <p class="text-sm text-gray-600 mt-1">Kelola dan generate kode lisensi untuk tenant atau akses aplikasi.</p>
        </div>

        <div class="mt-4 md:mt-0">
            <form action="{{ route('superadmin.license.generate') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Generate Lisensi Baru
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-5 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-md">
            <p class="text-sm font-medium">{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-6 py-4">No</th>
                        <th class="px-6 py-4">Kode Lisensi</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Digunakan Oleh</th>
                        <th class="px-6 py-4">Tanggal Dibuat</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($licenses ?? [] as $index => $license)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-500">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold text-gray-800 tracking-wider bg-gray-100 px-2 py-1 rounded">
                                    {{ $license->license_code }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($license->status === 'available')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Tersedia
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Terpakai
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $license->used_by_tenant_id ? 'Tenant #'.$license->used_by_tenant_id : '-' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ \Carbon\Carbon::parse($license->created_at)->format('d M Y, H:i') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="copyToClipboard('{{ $license->license_code }}')" class="text-blue-500 hover:text-blue-700 text-xs font-medium focus:outline-none" title="Copy Kode">
                                    <i class="far fa-copy fa-lg"></i>
                                </button>

                                <form action="{{ route('superadmin.license.destroy', $license->id) }}" method="POST" class="inline-block ml-3" onsubmit="return confirm('Yakin ingin menghapus lisensi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium focus:outline-none" title="Hapus Lisensi">
                                        <i class="far fa-trash-alt fa-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-ticket-alt text-4xl text-gray-300 mb-3"></i>
                                    <p>Belum ada kode lisensi yang digenerate.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($licenses) && $licenses->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $licenses->links() }}
            </div>
        @endif
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Kode ' + text + ' berhasil disalin!');
            }, function(err) {
                console.error('Gagal menyalin text: ', err);
            });
        }
    </script>

    </div>
@endsection
