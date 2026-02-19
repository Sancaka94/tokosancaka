@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <h2 class="text-3xl font-bold text-gray-800">Data Kontak & Saldo Hutang</h2>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('cashflow.index') }}" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
                <i class="fas fa-chart-line"></i>
                <span class="hidden sm:inline">Dashboard Harian</span>
            </a>

            <a href="{{ route('cashflow.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
                <i class="fas fa-edit"></i>
                <span>Catat Transaksi</span>
            </a>

            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Kontak Baru</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

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
                @forelse($contacts as $key => $item)
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
                        <form action="{{ route('contacts.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus kontak ini? (Hanya bisa dihapus jika tidak ada transaksi yang terhubung)')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded transition-colors">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                        Belum ada data kontak.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">{{ $contacts->links() }}</div>
    </div>

    <div id="addModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-5 border w-full max-w-md shadow-2xl rounded-xl bg-white m-4">
            <div class="flex justify-between items-center mb-5 border-b pb-3">
                <h3 class="text-xl font-bold text-gray-800">Tambah Kontak Baru</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-red-500">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form action="{{ route('contacts.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required placeholder="Contoh: Budi Santoso">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Toko <span class="text-gray-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="store_name" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Contoh: Toko Maju Jaya">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">No WhatsApp / HP</label>
                    <input type="number" name="phone" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="08...">
                </div>
                <div class="mb-5">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Alamat Lengkap</label>
                    <textarea name="address" rows="3" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Jalan, RT/RW, Desa..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-5 py-2.5 rounded-lg transition-colors">Batal</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg transition-colors shadow-md shadow-blue-200">Simpan Kontak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('addModal').addEventListener('click', function(e) {
        if(e.target === this) {
            this.classList.add('hidden');
        }
    });
</script>
@endsection
