@extends('layouts.app')

@section('title', 'Manajemen Pegawai')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Manajemen Pegawai</h1>
    <a href="{{ route('employees.create') }}" class="btn-primary shadow-md w-full sm:w-auto flex items-center justify-center gap-2 transition-colors">
        <span class="text-lg leading-none">+</span> Tambah Pegawai Baru
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-b-2 border-blue-600">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-xs md:text-sm">Daftar Akun Operator & Admin</span>
    </div>

    <div class="card-body p-0 block w-full overflow-x-auto">
        <table class="table-custom min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider whitespace-nowrap">Nama Lengkap</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider whitespace-nowrap">Email (Username)</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider whitespace-nowrap">Peran (Role)</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider whitespace-nowrap">Tanggal Bergabung</th>
                    <th class="px-4 md:px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees as $emp)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap font-bold text-gray-800 text-sm md:text-base">{{ $emp->name }}</td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-gray-600 text-sm md:text-base">{{ $emp->email }}</td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                            @if($emp->role == 'admin')
                                <span class="bg-blue-100 text-blue-800 px-2.5 py-0.5 rounded-full text-xs font-bold border border-blue-200">Admin</span>
                            @else
                                <span class="bg-gray-100 text-gray-800 px-2.5 py-0.5 rounded-full text-xs font-bold border border-gray-200">Operator</span>
                            @endif
                        </td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-gray-500 text-xs md:text-sm">{{ $emp->created_at->translatedFormat('d F Y') }}</td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-center align-middle">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('employees.edit', $emp->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-xs md:text-sm transition-colors bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded">
                                    Edit
                                </a>

                                <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus akun pegawai ini? Mereka tidak akan bisa login lagi.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-xs md:text-sm transition-colors bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500 italic">
                            Belum ada pegawai lain di cabang ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($employees) && method_exists($employees, 'links') && $employees->hasPages())
        <div class="px-4 md:px-6 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
