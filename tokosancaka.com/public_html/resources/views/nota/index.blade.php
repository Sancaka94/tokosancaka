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

<!-- Toast Notification (Pop-up modern untuk notif Copy) -->
<div id="toast-success" class="fixed bottom-5 right-5 bg-gray-900 text-white px-5 py-3 rounded-lg shadow-2xl transform transition-all duration-300 translate-y-20 opacity-0 z-50 flex items-center gap-3">
    <i class="fa-solid fa-circle-check text-green-400 text-lg"></i>
    <span id="toast-message" class="text-sm font-medium tracking-wide">Link pembayaran berhasil disalin!</span>
</div>

<!-- ======================================================== -->
<!-- MODAL KIRIM EMAIL TAGIHAN                                -->
<!-- ======================================================== -->
<div id="emailInvoiceModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-[1000] hidden transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform scale-95 transition-transform overflow-hidden relative">
        <div class="bg-slate-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-gray-800"><i class="fa-solid fa-paper-plane mr-2 text-blue-600"></i> Kirim Tagihan ke Email</h3>
            <button onclick="closeEmailModal()" class="text-gray-400 hover:text-red-500 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="formKirimEmail" class="p-6">
            @csrf
            <input type="hidden" id="email_no_nota" name="no_nota">

            <div class="mb-4 text-sm text-gray-600 bg-blue-50 p-3 rounded-lg border border-blue-100 flex items-start gap-3">
                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                <p>Link <span class="font-bold text-blue-700">Payment Page (beserta info PIN)</span> akan dikirimkan ke email pelanggan di bawah ini.</p>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Kirim Ke (Pelanggan)</label>
                <input type="text" id="email_nama_pembeli" class="w-full bg-gray-100 border border-gray-200 rounded-lg px-4 py-2 text-gray-600 cursor-not-allowed" readonly>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Alamat Email Tujuan <span class="text-red-500">*</span></label>
                <input type="email" id="email_tujuan" name="email_tujuan" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" placeholder="contoh: customer@email.com" required>
            </div>

            <button type="submit" id="btnSubmitEmail" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-md transition flex items-center justify-center">
                <i class="fa-regular fa-envelope mr-2"></i> Kirim Email Sekarang
            </button>
        </form>
    </div>
</div>

<script>
    function copyPaymentLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            const toast = document.getElementById('toast-success');
            document.getElementById('toast-message').innerText = "Link pembayaran berhasil disalin!";
            toast.classList.replace('text-amber-400', 'text-green-400');
            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => toast.classList.add('translate-y-20', 'opacity-0'), 3000);
        }).catch(err => {
            alert('Browser Anda tidak mendukung fitur copy otomatis. URL: ' + url);
        });
    }

    // ==========================================
    // LOGIKA MODAL KIRIM EMAIL INVOICE
    // ==========================================
    const emailModal = document.getElementById('emailInvoiceModal');
    const modalContent = emailModal.querySelector('div');

    function openEmailModal(noNota, namaPembeli, emailPembeli) {
        document.getElementById('email_no_nota').value = noNota;
        document.getElementById('email_nama_pembeli').value = namaPembeli;
        document.getElementById('email_tujuan').value = emailPembeli || ''; // Isi otomatis jika sudah ada

        emailModal.classList.remove('hidden');
        setTimeout(() => modalContent.classList.remove('scale-95'), 10);
    }

    function closeEmailModal() {
        modalContent.classList.add('scale-95');
        setTimeout(() => emailModal.classList.add('hidden'), 200);
    }

    // Ajax Kirim Email
    document.getElementById('formKirimEmail').addEventListener('submit', function(e) {
        e.preventDefault();

        let btn = document.getElementById('btnSubmitEmail');
        let originalText = btn.innerHTML;

        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Mengirim...';
        btn.disabled = true;
        btn.classList.add('opacity-70');

        let noNota = document.getElementById('email_no_nota').value;
        let emailTujuan = document.getElementById('email_tujuan').value;

        fetch('{{ route("nota.send-email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                no_nota: noNota,
                email: emailTujuan
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                closeEmailModal();

                // Panggil Toast Sukses (Re-use toast copy link)
                const toast = document.getElementById('toast-success');
                document.getElementById('toast-message').innerText = data.message;
                toast.classList.remove('translate-y-20', 'opacity-0');

                setTimeout(() => toast.classList.add('translate-y-20', 'opacity-0'), 4000);
            } else {
                alert('Gagal mengirim email: ' + data.message);
            }
        })
        .catch(err => {
            alert('Terjadi kesalahan jaringan.');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            btn.classList.remove('opacity-70');
        });
    });
</script>

@endsection
