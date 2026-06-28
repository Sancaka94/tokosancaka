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

{{-- CONTAINER TOMBOL SCROLL Cepat (UP & DOWN) --}}
{{-- PERBAIKAN: Posisi diubah dari right-8 ke right-24 agar tidak tertutup tab samping, dan z-index dinaikkan --}}
<div class="fixed bottom-10 right-24 flex flex-col space-y-3 z-[9999]">
    <button id="scrollTopBtn" class="hidden bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition duration-200 focus:outline-none" title="Scroll ke Atas">
        <i class="fas fa-arrow-up text-xl"></i>
    </button>
    <button id="scrollBottomBtn" class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition duration-200 focus:outline-none" title="Scroll ke Bawah">
        <i class="fas fa-arrow-down text-xl"></i>
    </button>
</div>

@endsection

@push('scripts')
<script>
let parsedLogs = [];

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
    
    const logPattern = /\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] ([\s\S]*?)(?=\n\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]|$)/g;
    
    let match;
    let index = 0;
    let tempGroups = {};

    while ((match = logPattern.exec(rawText)) !== null) {
        let fullTimeStr = match[1]; 
        let fullText = match[0];    
        
        let timeParts = fullTimeStr.split(':'); 
        let hourStr = timeParts[0];          
        let minute = parseInt(timeParts[1]); 
        
        let bucketMin = Math.floor(minute / 5) * 5;
        let bucketMinStr = bucketMin.toString().padStart(2, '0');
        let bucketEndMinStr = (bucketMin + 4).toString().padStart(2, '0'); 
        
        let groupKey = `${hourStr}:${bucketMinStr} - ${hourStr}:${bucketEndMinStr}`;
        
        if (!tempGroups[groupKey]) {
            tempGroups[groupKey] = {
                id: index++,
                timeLabel: groupKey,
                content: '',
                full_texts: [] 
            };
        }
        
        tempGroups[groupKey].content += fullText + "\n"; 
        tempGroups[groupKey].full_texts.push(fullText);  
    }

    parsedLogs = Object.values(tempGroups);

    if(parsedLogs.length > 0) {
        document.getElementById('selectAllContainer').classList.remove('hidden');
        renderCards();
    } else {
        container.innerHTML = `<div class="bg-gray-800 border border-gray-700 p-4 rounded-lg text-center text-gray-400">Log kosong atau tidak ada format waktu yang terdeteksi.</div>`;
    }

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
                        <span class="text-sm font-semibold text-blue-400"><i class="far fa-clock mr-1"></i> ${log.timeLabel}</span>
                        <span class="text-xs bg-gray-700 text-gray-300 px-2 py-0.5 rounded-full ml-2">${log.full_texts.length} Logs</span>
                    </div>
                    <div class="flex space-x-4">
                        <button onclick="copyLog(${log.id})" class="text-gray-400 hover:text-green-400 transition" title="Copy Log Grup Ini">
                            <i class="far fa-copy text-lg"></i>
                        </button>
                        <button onclick="deleteSingleLog(${log.id})" class="text-gray-400 hover:text-red-500 transition" title="Hapus Semua Log di Grup Ini">
                            <i class="fas fa-trash-alt text-lg"></i>
                        </button>
                    </div>
                </div>
                
                <div id="log-content-${log.id}" class="p-3 overflow-hidden text-green-300 max-h-32 transition-all duration-300 relative" style="font-family: monospace; font-size: 13px;">
                    <pre class="whitespace-pre-wrap break-words">${log.content}</pre>
                </div>
                
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
        
        // PERBAIKAN: Paksa pengecekan posisi scroll setelah card dimuat
        setTimeout(checkScrollPosition, 200);
    }

    function checkAndHideReadMoreButtons() {
        setTimeout(() => {
            parsedLogs.forEach(log => {
                const contentDiv = document.getElementById(`log-content-${log.id}`);
                const btnContainer = document.getElementById(`btn-container-${log.id}`);
                if (contentDiv && contentDiv.scrollHeight <= 128) {
                    btnContainer.classList.add('hidden');
                }
            });
        }, 100);
    }

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
            bulkDeleteBtn.innerHTML = `<i class="fas fa-trash mr-2"></i> Hapus Terpilih (${checkedCount} Grup)`;
        } else {
            bulkDeleteBtn.classList.add('hidden');
        }
    }
});

// ==========================================
// SCROLL TO TOP, SCROLL TO BOTTOM & READ MORE LOGIC
// ==========================================

const scrollTopBtn = document.getElementById('scrollTopBtn');
const scrollBottomBtn = document.getElementById('scrollBottomBtn');

function checkScrollPosition() {
    // Gunakan window.scrollY atau fallback ke penampung scroll dokumen
    const currentScroll = window.scrollY || document.documentElement.scrollTop;
    const documentHeight = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    const windowHeight = window.innerHeight || document.documentElement.clientHeight;

    if (currentScroll > 300) {
        scrollTopBtn.classList.remove('hidden');
    } else {
        scrollTopBtn.classList.add('hidden');
    }

    if ((windowHeight + currentScroll) >= documentHeight - 100) {
        scrollBottomBtn.classList.add('hidden');
    } else {
        scrollBottomBtn.classList.remove('hidden');
    }
}

window.addEventListener('scroll', checkScrollPosition);
window.addEventListener('resize', checkScrollPosition);

scrollTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' }); 
});

scrollBottomBtn.addEventListener('click', () => {
    const documentHeight = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    window.scrollTo({ top: documentHeight, behavior: 'smooth' }); 
});

window.toggleReadMore = function(id) {
    const contentDiv = document.getElementById(`log-content-${id}`);
    const btn = document.getElementById(`btn-readmore-${id}`);
    
    if (contentDiv.classList.contains('max-h-32')) {
        contentDiv.classList.remove('max-h-32');
        contentDiv.classList.add('max-h-full');
        btn.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Tampilkan Lebih Sedikit';
    } else {
        contentDiv.classList.remove('max-h-full');
        contentDiv.classList.add('max-h-32');
        btn.innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Tampilkan Lebih Banyak';
    }
    
    // Cek ulang scrollbar karena tinggi dokumen berubah setelah Read More diklik
    setTimeout(checkScrollPosition, 300);
}

// ==========================================
// ACTION FUNCTIONS (COPY, DELETE, BULK, ALL)
// ==========================================

window.copyLog = function(id) {
    const log = parsedLogs.find(l => l.id === id);
    if(log) {
        navigator.clipboard.writeText(log.full_texts.join('\n')).then(() => {
            Toast.fire({ icon: 'success', title: 'Grup log disalin!' });
        }).catch(err => {
            Toast.fire({ icon: 'error', title: 'Gagal menyalin log' });
        });
    }
}

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
            setTimeout(checkScrollPosition, 100);
        } else {
            Toast.fire({ icon: 'error', title: data.message || 'Gagal menghapus permanen.' });
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        Toast.fire({ icon: 'error', title: 'Kesalahan saat menghapus di server.' });
    });
}

window.deleteSingleLog = function(id) {
    const log = parsedLogs.find(l => l.id === id);
    if(!log) return;

    document.getElementById(`log-card-${id}`).remove();
    deletePermanentFromBackend(log.full_texts);
}

// 4. Bulk Delete Grup Permanen
document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
    const checkedBoxes = document.querySelectorAll('.log-checkbox:checked');
    const selectedIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
    
    let allTextsToDelete = [];
    
    selectedIds.forEach(id => {
        const log = parsedLogs.find(l => l.id === id);
        if(log) {
            // PERBAIKAN: Ganti spread operator (...) dengan concat() agar aman dari error minifier
            allTextsToDelete = allTextsToDelete.concat(log.full_texts); 
            document.getElementById(`log-card-${id}`).remove();
        }
    });

    document.getElementById('selectAll').checked = false;
    this.classList.add('hidden');

    deletePermanentFromBackend(allTextsToDelete);
});

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