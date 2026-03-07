<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sancaka Express - Live Monitor SPX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Header Publik --}}
        <div class="text-center mb-10">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Sancaka Express <span class="text-indigo-600">Monitor</span></h1>
            <p class="mt-2 text-sm text-gray-500">Live Dashboard Performa Scan Paket SPX</p>
            <div class="mt-4 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                <span class="flex w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span> Live Status
            </div>
        </div>

        {{-- Card Monitoring Dashboard --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            {{-- Card 1: Hari Ini --}}
            <div class="bg-indigo-50 rounded-xl p-6 border border-indigo-100 shadow-md transform transition hover:-translate-y-1">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-indigo-800 text-sm font-bold uppercase tracking-wider">Hari Ini</h3>
                    <div class="p-3 bg-indigo-200 rounded-lg text-indigo-700"><i class="fas fa-calendar-day fa-lg"></i></div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold text-gray-900">{{ $countToday }}</span>
                    <span class="text-sm font-medium text-gray-500">paket</span>
                </div>
                <div class="mt-4 text-sm flex items-center gap-1 font-medium">
                    @if($diffToday > 0)
                        <span class="text-green-700 bg-green-200 px-2 py-1 rounded"><i class="fas fa-arrow-up"></i> {{ $pctToday }}%</span>
                        <span class="text-gray-500 text-xs">(+{{ $diffToday }}) dr kemarin</span>
                    @elseif($diffToday < 0)
                        <span class="text-red-700 bg-red-200 px-2 py-1 rounded"><i class="fas fa-arrow-down"></i> {{ abs($pctToday) }}%</span>
                        <span class="text-gray-500 text-xs">({{ $diffToday }}) dr kemarin</span>
                    @else
                        <span class="text-gray-600 bg-gray-200 px-2 py-1 rounded"><i class="fas fa-minus"></i> 0%</span>
                        <span class="text-gray-500 text-xs">Sama spt kemarin</span>
                    @endif
                </div>
            </div>

            {{-- Card 2: Kemarin --}}
            <div class="bg-blue-50 rounded-xl p-6 border border-blue-100 shadow-md transform transition hover:-translate-y-1">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-blue-800 text-sm font-bold uppercase tracking-wider">Kemarin</h3>
                    <div class="p-3 bg-blue-200 rounded-lg text-blue-700"><i class="fas fa-history fa-lg"></i></div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold text-gray-900">{{ $countYesterday }}</span>
                    <span class="text-sm font-medium text-gray-500">paket</span>
                </div>
                <div class="mt-4 text-sm flex items-center gap-1 font-medium">
                    @if($diffYesterday > 0)
                        <span class="text-green-700 bg-green-200 px-2 py-1 rounded"><i class="fas fa-arrow-up"></i> {{ $pctYesterday }}%</span>
                        <span class="text-gray-500 text-xs">(+{{ $diffYesterday }}) dr H-2</span>
                    @elseif($diffYesterday < 0)
                        <span class="text-red-700 bg-red-200 px-2 py-1 rounded"><i class="fas fa-arrow-down"></i> {{ abs($pctYesterday) }}%</span>
                        <span class="text-gray-500 text-xs">({{ $diffYesterday }}) dr H-2</span>
                    @else
                        <span class="text-gray-600 bg-gray-200 px-2 py-1 rounded"><i class="fas fa-minus"></i> 0%</span>
                        <span class="text-gray-500 text-xs">Sama spt H-2</span>
                    @endif
                </div>
            </div>

            {{-- Card 3: Bulan Ini --}}
            <div class="bg-purple-50 rounded-xl p-6 border border-purple-100 shadow-md transform transition hover:-translate-y-1">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-purple-800 text-sm font-bold uppercase tracking-wider">Bulan Ini</h3>
                    <div class="p-3 bg-purple-200 rounded-lg text-purple-700"><i class="fas fa-calendar-alt fa-lg"></i></div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold text-gray-900">{{ $countThisMonth }}</span>
                    <span class="text-sm font-medium text-gray-500">paket</span>
                </div>
                <div class="mt-4 text-sm flex items-center gap-1 font-medium">
                    @if($diffMonth > 0)
                        <span class="text-green-700 bg-green-200 px-2 py-1 rounded"><i class="fas fa-arrow-up"></i> {{ $pctMonth }}%</span>
                        <span class="text-gray-500 text-xs">(+{{ $diffMonth }}) dr bln lalu</span>
                    @elseif($diffMonth < 0)
                        <span class="text-red-700 bg-red-200 px-2 py-1 rounded"><i class="fas fa-arrow-down"></i> {{ abs($pctMonth) }}%</span>
                        <span class="text-gray-500 text-xs">({{ $diffMonth }}) dr bln lalu</span>
                    @else
                        <span class="text-gray-600 bg-gray-200 px-2 py-1 rounded"><i class="fas fa-minus"></i> 0%</span>
                        <span class="text-gray-500 text-xs">Sama spt bln lalu</span>
                    @endif
                </div>
            </div>

            {{-- Card 4: Status Input (Copied vs Belum) --}}
            <div class="bg-emerald-50 rounded-xl p-6 border border-emerald-100 shadow-md flex flex-col justify-between transform transition hover:-translate-y-1">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-emerald-800 text-sm font-bold uppercase tracking-wider">Status Input</h3>
                    <div class="p-3 bg-emerald-200 rounded-lg text-emerald-700"><i class="fas fa-clipboard-check fa-lg"></i></div>
                </div>
                <div class="flex flex-col gap-3 mt-1">
                    <div class="flex justify-between items-center bg-white px-4 py-3 rounded-lg border border-emerald-200 shadow-sm">
                        <span class="text-sm font-semibold text-emerald-700"><i class="fas fa-check-double mr-1"></i> Telah Diproses</span>
                        <span class="text-xl font-bold text-gray-900">{{ $countCopied }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-white px-4 py-3 rounded-lg border border-red-200 shadow-sm">
                        <span class="text-sm font-semibold text-red-600"><i class="fas fa-minus-circle mr-1"></i> Belum Diproses</span>
                        <span class="text-xl font-bold text-gray-900">{{ $countNotCopied }}</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Tabel Data Surat Jalan --}}
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 bg-white flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-lg">Daftar Surat Jalan Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Jumlah Paket</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Scan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nomor Surat Jalan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($suratJalans as $index => $sj)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800">
                                    {{ $sj->user->nama_lengkap ?? $sj->kontak->nama ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <span class="bg-indigo-100 text-indigo-800 py-1 px-3 rounded-full text-xs font-bold shadow-sm">
                                        {{ $sj->jumlah_paket }} Paket
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $sj->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{-- Menggabungkan list resi untuk dikirim ke Modal JS --}}
                                    @php
                                        // Mengakomodasi kolom resi atau resi_number (jaga-jaga jika ada perbedaan)
                                        $resiList = $sj->packages->map(function($p) { return $p->resi ?? $p->resi_number; })->implode(',');
                                    @endphp
                                    <button onclick="openModal('{{ $sj->kode_surat_jalan }}', '{{ $sj->user->nama_lengkap ?? $sj->kontak->nama ?? 'N/A' }}', '{{ $sj->jumlah_paket }}', '{{ $sj->created_at->format('d M Y, H:i') }}', '{{ $resiList }}')"
                                        class="text-indigo-600 hover:text-indigo-900 font-bold underline decoration-indigo-300 decoration-2 underline-offset-4 transition">
                                        {{ $sj->kode_surat_jalan }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fas fa-box-open fa-3x mb-3 text-gray-300"></i>
                                    <p>Belum ada data surat jalan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

         {{-- KODE BARU: Menampilkan navigasi pagination --}}
            <div class="mt-4 px-4">
                {{ $suratJalans->links() }}
            </div>

        {{-- MODAL SURAT JALAN (Hidden by default) --}}
        <div id="sjModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full backdrop-blur-sm transition-opacity duration-300 flex items-center justify-center">
            <div class="relative mx-auto p-6 border w-11/12 md:w-1/2 lg:w-1/3 shadow-2xl rounded-2xl bg-white transform transition-all">
                <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                    <h3 class="text-xl font-extrabold text-gray-800"><i class="fas fa-file-invoice text-indigo-500 mr-2"></i> Detail Surat Jalan</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 transition focus:outline-none">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>
                <div class="mt-5 space-y-4">
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="text-gray-500 text-sm font-medium">Kode Surat Jalan</span>
                        <span id="modal-kode" class="font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded"></span>
                    </div>
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="text-gray-500 text-sm font-medium">Nama Pengirim</span>
                        <span id="modal-nama" class="font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="text-gray-500 text-sm font-medium">Tanggal Scan</span>
                        <span id="modal-tanggal" class="text-gray-800 text-sm font-medium"></span>
                    </div>
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="text-gray-500 text-sm font-medium">Jumlah Paket</span>
                        <span id="modal-jumlah" class="font-bold text-gray-800"></span>
                    </div>
                    <div class="pt-2">
                        <span class="text-gray-500 text-sm font-medium block mb-2">Daftar Resi dalam Surat Jalan:</span>
                        <ul id="modal-resi-list" class="bg-gray-50 border border-gray-100 rounded-lg p-3 text-sm text-gray-700 max-h-48 overflow-y-auto space-y-2 custom-scrollbar">
                            </ul>
                    </div>
                </div>
                <div class="mt-8 flex justify-end">
                    <button onclick="closeModal()" class="px-5 py-2.5 bg-gray-800 text-white hover:bg-gray-900 rounded-xl font-medium shadow-md transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        {{-- SCRIPT UNTUK MODAL --}}
        <script>
            function openModal(kode, nama, jumlah, tanggal, resiString) {
                document.getElementById('modal-kode').innerText = kode;
                document.getElementById('modal-nama').innerText = nama;
                document.getElementById('modal-jumlah').innerText = jumlah + ' Paket';
                document.getElementById('modal-tanggal').innerText = tanggal;

                // Memecah string resi menjadi array dan membuat HTML List
                let resiList = resiString.split(',');
                let resiHtml = '';

                if(resiString.trim() !== '') {
                    resiList.forEach((resi, index) => {
                        // KODE YANG DIUBAH: Menambahkan tombol copy di sisi kanan list
                        resiHtml += `
                            <li class="flex items-center justify-between p-2.5 bg-white rounded border border-gray-100 shadow-sm hover:border-indigo-200 transition">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-barcode text-indigo-400"></i>
                                    <span class="font-medium tracking-wide text-gray-700">${resi}</span>
                                </div>
                                <button type="button" onclick="copyResiModal('${resi}', 'copy-icon-modal-${index}')" class="text-gray-400 hover:text-indigo-600 focus:outline-none transition-colors" title="Salin Nomor Resi">
                                    <i id="copy-icon-modal-${index}" class="fas fa-copy"></i>
                                </button>
                            </li>`;
                    });
                } else {
                    resiHtml = '<li class="text-gray-400 italic text-center py-2">Tidak ada data resi</li>';
                }
                document.getElementById('modal-resi-list').innerHTML = resiHtml;

                // Tampilkan Modal
                document.getElementById('sjModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Mencegah background di-scroll
            }

            function closeModal() {
                document.getElementById('sjModal').classList.add('hidden');
                document.body.style.overflow = 'auto'; // Mengembalikan scroll
            }

            // Menutup modal jika user klik area gelap di luar modal
            window.onclick = function(event) {
                let modal = document.getElementById('sjModal');
                if (event.target == modal) {
                    closeModal();
                }
            }

            // KODE BARU: Fungsi untuk eksekusi tombol copy di dalam modal
            function copyResiModal(text, iconId) {
                navigator.clipboard.writeText(text).then(function() {
                    let iconElement = document.getElementById(iconId);
                    // Ubah icon jadi centang hijau sebentar
                    iconElement.className = 'fas fa-check text-green-500';

                    // Kembalikan ke icon copy semula setelah 2 detik
                    setTimeout(() => {
                        iconElement.className = 'fas fa-copy';
                    }, 2000);
                }).catch(function(err) {
                    console.error('Gagal menyalin text: ', err);
                });
            }
        </script>

        {{-- Footer Credit --}}
        <div class="text-center mt-12 text-sm text-gray-400">
            &copy; {{ date('Y') }} Sancaka Express. Data diperbarui secara real-time.
        </div>

    </div>

</body>
</html>
