@extends('layouts.app')

@section('title', 'Manajemen Pegawai')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <h1 class="text-2xl font-bold text-gray-800">Manajemen Pegawai</h1>
    <a href="{{ route('employees.create') }}" class="btn-primary shadow-md">
        + Tambah Pegawai Baru
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-b-2 border-blue-600">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-sm">Daftar Akun Operator & Admin</span>
    </div>
    <div class="card-body p-0 overflow-x-auto">
        <table class="table-custom min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Nama Lengkap</th>
                    <th class="text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Email (Username)</th>
                    <th class="text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Peran (Role)</th>
                    <th class="text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Tanggal Bergabung</th>
                    <th class="text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees as $emp)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="font-bold text-gray-800">{{ $emp->name }}</td>
                        <td class="text-gray-600">{{ $emp->email }}</td>
                        <td>
                            @if($emp->role == 'admin')
                                <span class="bg-blue-100 text-blue-800 px-2.5 py-0.5 rounded-full text-xs font-bold border border-blue-200">Admin</span>
                            @else
                                <span class="bg-gray-100 text-gray-800 px-2.5 py-0.5 rounded-full text-xs font-bold border border-gray-200">Operator</span>
                            @endif
                        </td>
                        <td class="text-gray-500 text-sm">{{ $emp->created_at->translatedFormat('d F Y') }}</td>
                        <td class="text-center">
                            <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus akun pegawai ini? Mereka tidak akan bisa login lagi.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded text-xs font-bold transition-colors">
                                    Hapus Akses
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                            Belum ada pegawai lain di cabang ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
