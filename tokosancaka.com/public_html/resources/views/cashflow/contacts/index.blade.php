@extends('layouts.admin')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-3xl font-bold text-gray-800">Data Kontak & Saldo Hutang</h2>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            + Tambah Kontak Baru
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                    <th class="px-6 py-4">No</th>
                    <th class="px-6 py-4">Nama & Toko</th>
                    <th class="px-6 py-4">Kontak (HP/Alamat)</th>
                    <th class="px-6 py-4 text-right">Sisa Saldo</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contacts as $key => $item)
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $contacts->firstItem() + $key }}</td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-900">{{ $item->name }}</div>
                        <div class="text-xs text-gray-500">{{ $item->store_name ?? '-' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div>{{ $item->phone ?? '-' }}</div>
                        <div class="text-xs text-gray-400">{{ Str::limit($item->address, 30) }}</div>
                    </td>
                    <td class="px-6 py-4 text-right font-mono text-lg font-bold {{ $item->balance > 0 ? 'text-green-600' : ($item->balance < 0 ? 'text-red-600' : 'text-gray-400') }}">
                        Rp {{ number_format(abs($item->balance), 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($item->balance > 0)
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">PIUTANG (Dia Hutang)</span>
                        @elseif($item->balance < 0)
                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">HUTANG (Kita Hutang)</span>
                        @else
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">LUNAS</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <form action="{{ route('contacts.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus kontak ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $contacts->links() }}</div>
    </div>

    <div id="addModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold mb-4">Tambah Kontak Baru</h3>
            <form action="{{ route('contacts.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap</label>
                    <input type="text" name="name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Toko (Opsional)</label>
                    <input type="text" name="store_name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">No HP</label>
                    <input type="number" name="phone" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Alamat</label>
                    <textarea name="address" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="bg-gray-300 text-gray-700 px-4 py-2 rounded">Batal</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
