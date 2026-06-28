{{-- resources/views/admin/logs/viewer.blade.php --}}
@extends('layouts.admin') 

@section('title', 'Raw Log Viewer')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">📂 Isi Log ({{ $maxLines }} Baris Terakhir)</h1>
    
    <div class="flex space-x-2">
        {{-- TOMBOL BULK DELETE --}}
        <button id="bulkDeleteBtn" class="hidden px-4 py-2 bg-yellow-600 text-white font-semibold rounded-lg hover:bg-yellow-700 transition duration-150">
            <i class="fas fa-trash mr-2"></i> Hapus Terpilih
        </button>

        {{-- TOMBOL HAPUS SEMUA LOG --}}
        <button id="clearLogsBtn" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition duration-150">
            <i class="fas fa-trash-alt mr-2"></i> Hapus Semua Log
        </button>
    </div>
</div>

{{-- Checkbox Select All --}}
<div class="mb-4 flex items-center hidden" id="selectAllContainer">
    <input type="checkbox" id="selectAll" class="w-5 h-5 cursor-pointer rounded border-gray-600">
    <label for="selectAll" class="ml-2 text-sm font-semibold cursor-pointer">Pilih Semua Log</label>
</div>

{{-- CONTAINER UNTUK CARD LOGS --}}
<div id="logCardsContainer" class="space-y-4 relative">
    </div>

{{-- SUMBER DATA ASLI (Disembunyikan agar log utuh tidak berubah) --}}
<div class="hidden">
    <pre id="rawLogsData">{!! e($logs) !!}</pre>
</div>

{{-- TOMBOL SCROLL KE ATAS Cepat --}}
<button id="scrollTopBtn" class="hidden fixed bottom-8 right-8 bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition duration-200 z-50 focus:outline-none" title="Scroll ke Atas">
    <i class="fas fa-arrow-up text-xl"></i>
</button>

@endsection

@push('scripts')
<script>
// Global array untuk menyimpan data log yang sudah diparsing
let parsedLogs = [];

// Konfigurasi Toast Alert Otomatis OK di Pojok Kanan Atas
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const rawText = document.getElementById('rawLogsData').innerText;
    const container = document.getElementById('logCardsContainer');
    const selectAllCheckbox = document.getElementById('selectAll');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    // Regex untuk mendeteksi waktu log: [YYYY-MM-DD HH:mm:ss]
    const logPattern = /\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] ([\s\S]*?)(?=\n\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]|$)/g;
    
    let match;
    let index = 0;

    // Proses Parsing Text -> Object (Card)
    while ((match = logPattern.exec(rawText)) !== null) {
        parsedLogs.push({
            id: index++,
            time: match[1],
            content: match[2].trim(),
            full_text: match[0]
        });
    }

    if(parsedLogs.length > 0) {
        document.getElementById('selectAllContainer').classList.remove('hidden');
        renderCards();
    } else {
        container.innerHTML = `<div class="bg-gray-800 border border-gray-700 p-4 rounded-lg text-center text-gray-400">Log kosong atau tidak ada format waktu yang terdeteksi.</div>`;
    }

    // Fungsi Render HTML Card
    function renderCards() {
        container.innerHTML = '';
        parsedLogs.forEach(log => {
            const card = document.createElement('div');
            card.className = "bg-gray-800 border border-gray-700 rounded-lg shadow-sm transition hover:border-gray-500 log-card-item";
            card.id = `log-card-${log.id}`;
            
            card.innerHTML = `
                <div class="flex justify-between items-center p-3 border-b border-gray-700 bg-gray-900 rounded-t-lg">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" class="log-checkbox w-4 h-4 cursor-pointer rounded" value="${log.id}">
                        <span class="text-sm font-semibold text-blue-400"><i class="far fa-clock mr-1"></i> ${log.time}</span>
                    </div>
                    <div class="flex space-x-4">
                        <button onclick="copyLog(${log.id})" class="text-gray-400 hover:text-green-400 transition" title="Copy Log">
                            <i class="far fa-copy text-lg"></i>
                        </button>
                        <button onclick="deleteSingleLog(${log.id})" class="text-gray-400 hover:text-red-500 transition" title="Hapus Log Ini">
                            <i class="fas fa-trash-alt text-lg"></i>
                        </button>
                    </div>
                </div>
                
                {{-- Area Konten Log dengan fitur Read More --}}
                <div id="log-content-${log.id}" class="p-3 overflow-hidden text-green-300 max-h-32 transition-all duration-300 relative" style="font-family: monospace; font-size: 13px;">
                    <pre class="whitespace-pre-wrap break-words">${log.content}</pre>
                </div>
                
                {{-- Tombol Read More (Disembunyikan via JS jika teks pendek) --}}
                <div id="btn-container-${log.id}" class="px-3 pb-2 pt-1 bg-gray-900 rounded-b-lg border-t border-gray-700 text-center">
                    <button onclick="toggleReadMore(${log.id})" id="btn-readmore-${log.id}" class="text-xs text-blue-400 hover:text-blue-300 font-semibold focus:outline-none">
                        <i class="fas fa-chevron-down mr-1"></i> Tampilkan Lebih Banyak
                    </button>
                </div>
            `;
            container.appendChild(card);
        });

        attachCheckboxListeners();
        checkAndHideReadMoreButtons();
    }

    // Fungsi menyembunyikan tombol Read More jika log-nya pendek
    function checkAndHideReadMoreButtons() {
        setTimeout(() => {
            parsedLogs.forEach(log => {
                const contentDiv = document.getElementById(`log-content-${log.id}`);
                const btnContainer = document.getElementById(`btn-container-${log.id}`);
                // Jika tinggi teks aslinya lebih kecil atau sama dengan tinggi max-h-32 (128px), sembunyikan tombol
                if (contentDiv && contentDiv.scrollHeight <= 128) {
                    btnContainer.classList.add('hidden');
                }
            });
        }, 100);
    }

    // Logic Checkbox & Select All
    function attachCheckboxListeners() {
        const checkboxes = document.querySelectorAll('.log-checkbox');
        
        selectAllCheckbox.addEventListener('change', (e) => {
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            toggleBulkButton();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const allChecked = document.querySelectorAll('.log-checkbox:checked').length === checkboxes.length;
                selectAllCheckbox.checked = allChecked;
                toggleBulkButton();
            });
        });
    }

    function toggleBulkButton() {
        const checkedCount = document.querySelectorAll('.log-checkbox:checked').length;
        if(checkedCount > 0) {
            bulkDeleteBtn.classList.remove('hidden');
            bulkDeleteBtn.innerHTML = `<i class="fas fa-trash mr-2"></i> Hapus Terpilih (${checkedCount})`;
        } else {
            bulkDeleteBtn.classList.add('hidden');
        }
    }
});

// ==========================================
// SCROLL TO TOP & READ MORE LOGIC
// ==========================================

// Fitur Scroll Cepat
const scrollTopBtn = document.getElementById('scrollTopBtn');
window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        scrollTopBtn.classList.remove('hidden');
    } else {
        scrollTopBtn.classList.add('hidden');
    }
});

scrollTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'instant' }); // 'instant' agar naik ke atas dengan sangat cepat
});

// Fitur Read More Toggle
window.toggleReadMore = function(id) {
    const contentDiv = document.getElementById(`log-content-${id}`);
    const btn = document.getElementById(`btn-readmore-${id}`);
    
    if (contentDiv.classList.contains('max-h-32')) {
        // Expand
        contentDiv.classList.remove('max-h-32');
        contentDiv.classList.add('max-h-full');
        btn.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Tampilkan Lebih Sedikit';
    } else {
        // Collapse
        contentDiv.classList.remove('max-h-full');
        contentDiv.classList.add('max-h-32');
        btn.innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Tampilkan Lebih Banyak';
    }
}

// ==========================================
// ACTION FUNCTIONS (COPY, DELETE, BULK, ALL)
// ==========================================

// 1. Copy Log
window.copyLog = function(id) {
    const log = parsedLogs.find(l => l.id === id);
    if(log) {
        navigator.clipboard.writeText(log.full_text).then(() => {
            Toast.fire({ icon: 'success', title: 'Log berhasil disalin!' });
        }).catch(err => {
            Toast.fire({ icon: 'error', title: 'Gagal menyalin log' });
        });
    }
}

// 2. Fungsi Komunikasi Hapus Fisik ke Controller
function deletePermanentFromBackend(textsArray) {
    fetch('{{ route('admin.logs.destroy.selected') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ texts_to_delete: textsArray })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Toast.fire({ icon: 'success', title: data.message });
        } else {
            Toast.fire({ icon: 'error', title: data.message || 'Gagal menghapus secara permanen.' });
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        Toast.fire({ icon: 'error', title: 'Terjadi kesalahan saat menghapus di server.' });
    });
}

// 3. Single Delete Permanen
window.deleteSingleLog = function(id) {
    const log = parsedLogs.find(l => l.id === id);
    if(!log) return;

    // Hapus Card dari antarmuka layar
    document.getElementById(`log-card-${id}`).remove();
    
    // Panggil Controller untuk hapus permanen dari file laravel.log
    deletePermanentFromBackend([log.full_text]);
}

// 4. Bulk Delete Permanen
document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
    const checkedBoxes = document.querySelectorAll('.log-checkbox:checked');
    const selectedIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
    const textsToDelete = selectedIds.map(id => parsedLogs.find(l => l.id === id).full_text);
    
    // Hapus Card dari antarmuka layar
    selectedIds.forEach(id => {
        document.getElementById(`log-card-${id}`).remove();
    });

    // Reset status UI
    document.getElementById('selectAll').checked = false;
    this.classList.add('hidden');

    // Panggil Controller untuk hapus permanen kumpulan log tersebut dari file
    deletePermanentFromBackend(textsToDelete);
});

// 5. Delete All (Clear Logs Bawaan)
document.getElementById('clearLogsBtn').addEventListener('click', function() {
    fetch('{{ route('admin.logs.clear') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Toast.fire({
                icon: 'success',
                title: data.message || 'Semua log berhasil dihapus!'
            }).then(() => window.location.reload());
        } else {
            Toast.fire({ icon: 'error', title: data.message || 'Gagal menghapus log!' });
        }
    })
    .catch(error => {
        Toast.fire({ icon: 'error', title: 'Terjadi kesalahan server.' });
    });
});
</script>
@endpush