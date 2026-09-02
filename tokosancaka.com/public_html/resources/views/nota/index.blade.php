@extends('layouts.admin')

@section('content')
<div class="w-full px-4 py-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <h3 class="text-2xl font-bold text-gray-800">Riwayat Nota</h3>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('nota.export-excel') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium py-2 px-4 rounded-lg shadow-sm flex items-center transition">
                <i class="fa-solid fa-file-excel mr-2"></i> Excel
            </a>
            <a href="{{ route('nota.export-pdf') }}" class="bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium py-2 px-4 rounded-lg shadow-sm flex items-center transition">
                <i class="fa-solid fa-file-pdf mr-2"></i> PDF
            </a>
            <a href="{{ route('nota.create') }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-lg shadow-sm flex items-center transition">
                <i class="fa-solid fa-plus mr-2"></i> Buat Nota
            </a>
        </div>
    </div>

    <!-- Alert Success Flash -->
    @if(session('success'))
    <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center">
        <i class="fa-solid fa-circle-check mr-2"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">No. Nota</th>
                        <th class="px-6 py-4">Kepada</th>
                        <th class="px-6 py-4">Total</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($notas as $nota)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">{{ \Carbon\Carbon::parse($nota->tanggal)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 font-bold text-gray-900">{{ $nota->no_nota }}</td>
                        <td class="px-6 py-4">{{ $nota->kepada }}<br><span class="text-xs font-normal text-gray-400">{{ $nota->nama_pembeli }}</span></td>
                        <td class="px-6 py-4 font-bold text-emerald-600">Rp {{ number_format($nota->total_harga, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            @if(strtoupper($nota->status) === 'PAID')
                                <span class="bg-green-100 text-green-800 text-[10px] font-bold px-2.5 py-1 rounded border border-green-200 uppercase tracking-wider">Lunas</span>
                            @elseif(strtoupper($nota->status) === 'FAILED')
                                <span class="bg-red-100 text-red-800 text-[10px] font-bold px-2.5 py-1 rounded border border-red-200 uppercase tracking-wider">Gagal</span>
                            @else
                                <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2.5 py-1 rounded border border-amber-200 uppercase tracking-wider">Unpaid</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-1.5 flex-wrap">

                                <!-- 1. Icon Copy Link -->
                                <button onclick="copyPaymentLink('{{ route('nota.pay', $nota->no_nota) }}')" class="text-slate-600 hover:bg-slate-100 border border-slate-200 w-8 h-8 rounded flex items-center justify-center transition shadow-sm" title="Copy Link Pembayaran">
                                    <i class="fa-solid fa-copy"></i>
                                </button>

                                <!-- 2. Icon Invoice (Payment Page) -->
                                <a href="{{ route('nota.pay', $nota->no_nota) }}" target="_blank" class="text-indigo-600 hover:bg-indigo-50 border border-indigo-200 w-8 h-8 rounded flex items-center justify-center transition shadow-sm" title="Buka Halaman Payment Page">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </a>

                                <!-- 3. Icon Bayar (Direct Gateway / Metode Pembayaran) -->
                                <a href="{{ $nota->payment_url ?? route('nota.pay', $nota->no_nota) }}" target="_blank" class="text-emerald-600 hover:bg-emerald-50 border border-emerald-200 w-8 h-8 rounded flex items-center justify-center transition shadow-sm" title="Metode Pembayaran / Bayar Sekarang">
                                    <i class="fa-solid fa-wallet"></i>
                                </a>

                                <button onclick="openEmailModal('{{ $nota->no_nota }}', '{{ $nota->nama_pembeli }}', '{{ $nota->email_pembeli }}')" class="text-amber-600 hover:bg-amber-50 border border-amber-200 w-8 h-8 rounded flex items-center justify-center transition shadow-sm" title="Kirim Tagihan ke Email">
                                    <i class="fa-solid fa-envelope"></i>
                                </button>

                                <!-- 4. Download PDF -->
                                <a href="{{ route('nota.download', $nota->id) }}" class="text-blue-600 hover:bg-blue-50 border border-blue-200 w-8 h-8 rounded flex items-center justify-center transition shadow-sm" title="Download PDF">
                                    <i class="fa-solid fa-download"></i>
                                </a>

                                <!-- 5. Edit Nota -->
                                <a href="{{ route('nota.edit', $nota->id) }}" class="text-amber-600 hover:bg-amber-50 border border-amber-200 w-8 h-8 rounded flex items-center justify-center transition shadow-sm" title="Edit Nota">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <!-- 6. Hapus Nota -->
                                <form action="{{ route('nota.destroy', $nota->id) }}" method="POST" onsubmit="return confirm('Hapus nota ini secara permanen?');" class="inline-block m-0 p-0">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:bg-red-50 border border-red-200 w-8 h-8 rounded flex items-center justify-center transition shadow-sm" title="Hapus Nota">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-folder-open text-4xl mb-3"></i>
                            <p>Belum ada data nota.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Area -->
        @if($notas->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $notas->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Toast Notification (Pop-up modern untuk notif Copy) -->
<div id="toast-success" class="fixed bottom-5 right-5 bg-gray-900 text-white px-5 py-3 rounded-lg shadow-2xl transform transition-all duration-300 translate-y-20 opacity-0 z-50 flex items-center gap-3">
    <i class="fa-solid fa-circle-check text-green-400 text-lg"></i>
    <span id="toast-message" class="text-sm font-medium tracking-wide">Link pembayaran berhasil disalin!</span>
</div>

<script>
    function copyPaymentLink(url) {
        // Eksekusi fungsi copy ke clipboard
        navigator.clipboard.writeText(url).then(() => {
            const toast = document.getElementById('toast-success');

            // Tampilkan Toast
            toast.classList.remove('translate-y-20', 'opacity-0');

            // Sembunyikan otomatis setelah 3 detik
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }).catch(err => {
            alert('Browser Anda tidak mendukung fitur copy otomatis. URL: ' + url);
        });
    }
</script>
@endsection
