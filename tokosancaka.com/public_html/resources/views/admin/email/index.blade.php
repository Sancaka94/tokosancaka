@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Tambahkan CSS Quill.js -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <style>
        .email-item.active { background-color: #e8f0fe; border-left: 4px solid #1a73e8; }
        .email-item:not(.active):hover { background-color: #f8fafc; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .loader {
            border: 3px solid #f3f3f3; border-top: 3px solid #3b82f6; border-radius: 50%;
            width: 24px; height: 24px; animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        /* Style untuk body email dari server agar tidak tembus container */
        #email-body iframe, #email-body img { max-width: 100%; height: auto; }
        
        /* Customisasi Quill agar pas di modal */
        .ql-container { min-height: 200px; flex: 1; font-family: inherit; font-size: 14px; }
        .ql-toolbar { border-radius: 0.5rem 0.5rem 0 0; background: #f8fafc; }
        .ql-container { border-radius: 0 0 0.5rem 0.5rem; }
    </style>
@endpush

@section('content')
    <div id="app" class="w-full flex h-[calc(100vh-8rem)] min-h-[600px] bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- Sidebar Kiri -->
        <aside class="w-56 lg:w-64 flex-shrink-0 flex flex-col h-full border-r border-gray-200 bg-gray-50/50">
            <div class="px-5 h-16 flex items-center border-b border-transparent">
                <h1 class="text-xl font-bold text-gray-800"><i class="fa-solid fa-envelope-open-text text-blue-600 mr-2"></i>EmailApp</h1>
            </div>
            <div class="p-3">
                <button id="compose-btn" class="w-full flex items-center justify-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 hover:shadow text-gray-700 font-semibold py-2.5 px-4 rounded-xl shadow-sm transition-all">
                    <i class="fa-solid fa-pen text-sm"></i> Tulis Pesan
                </button>
            </div>
            <nav id="folder-nav" class="mt-2 flex-1 p-3 space-y-1 overflow-y-auto">
                <a href="#" data-folder="inbox" class="folder-link flex items-center justify-between px-3 py-2.5 rounded-lg bg-blue-50 text-blue-700 font-semibold">
                    <div class="flex items-center gap-3"><i class="fa-solid fa-inbox w-4 text-center"></i><span class="text-sm">Kotak Masuk</span></div>
                    <span class="text-xs font-bold bg-blue-200/50 text-blue-800 px-2 py-0.5 rounded-full" id="unread-count" style="display:none;">0</span>
                </a>
                <a href="#" data-folder="starred" class="folder-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 text-gray-600 font-medium">
                    <i class="fa-solid fa-star w-4 text-center"></i><span class="text-sm">Berbintang</span>
                </a>
                <a href="#" data-folder="sent" class="folder-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 text-gray-600 font-medium">
                    <i class="fa-solid fa-paper-plane w-4 text-center"></i><span class="text-sm">Terkirim</span>
                </a>
            </nav>
        </aside>

        <!-- Daftar Email -->
        <main class="w-[300px] lg:w-[360px] flex-shrink-0 flex flex-col h-full border-r border-gray-200 bg-white shadow-[4px_0_10px_rgba(0,0,0,0.02)] z-10">
            <header class="h-auto flex flex-col px-4 py-3 border-b border-gray-200 flex-shrink-0 bg-white gap-3">
                <form id="search-form" class="w-full relative" onsubmit="event.preventDefault();">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i class="fa-solid fa-search text-gray-400 text-sm"></i></span>
                    <input type="search" id="search-input" placeholder="Cari pesan..." class="w-full bg-gray-100/80 focus:bg-white focus:ring-2 focus:ring-blue-200 rounded-lg py-2 pl-9 pr-4 text-sm outline-none transition-all">
                </form>
                
                <!-- Aksi Masal: Checkbox Pilih Semua & Tombol Hapus -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="select-all" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                        <label for="select-all" class="text-xs text-gray-600 cursor-pointer font-medium">Pilih Semua</label>
                    </div>
                    <button id="btn-delete-selected" class="hidden text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded transition-colors" title="Hapus Terpilih">
                        <i class="fa-solid fa-trash-can"></i> Hapus
                    </button>
                </div>
            </header>
            <div id="email-list" class="flex-1 overflow-y-auto bg-white"></div>
        </main>

        <!-- Detail Email -->
        <section id="email-view" class="flex-1 flex flex-col h-full bg-[#f8fafc] relative overflow-hidden">
            <div id="email-placeholder" class="flex flex-col items-center justify-center h-full text-gray-400">
                <div class="bg-gray-100 p-6 rounded-full mb-4 shadow-inner"><i class="fa-solid fa-envelope-open text-5xl text-gray-300"></i></div>
                <p class="text-lg font-medium text-gray-500">Pilih email untuk dibaca</p>
            </div>

            <div id="email-content" class="hidden flex-1 flex flex-col h-full bg-white shadow-[-4px_0_15px_rgba(0,0,0,0.03)]">
                <header class="p-6 border-b border-gray-100 flex-shrink-0">
                    <h2 id="email-subject" class="text-2xl font-bold text-gray-800 mb-4 leading-tight"></h2>
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <img id="email-avatar" src="" alt="Avatar" class="w-11 h-11 rounded-full mr-4 shadow-sm border border-gray-100 object-cover">
                            <div>
                                <p class="font-bold text-gray-800 text-sm" id="email-sender-name"></p>
                                <p class="text-xs text-gray-500 mt-0.5" id="email-sender-address"></p>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 font-medium bg-gray-50 px-2.5 py-1 rounded-md" id="email-timestamp"></div>
                    </div>
                </header>
                <!-- Menampilkan HTML langsung -->
                <div class="flex-1 p-8 overflow-y-auto text-gray-800 text-sm" id="email-body"></div>
            </div>
        </section>
    </div>

    <!-- Modal Tulis -->
    <div id="compose-modal" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm flex justify-end items-end z-[100] opacity-0 pointer-events-none transform translate-y-8 transition-all duration-300">
        <div class="bg-white rounded-t-xl shadow-2xl w-full max-w-2xl h-[80%] flex flex-col mr-4 md:mr-10 border border-gray-200">
            <header class="bg-gray-800 text-white px-4 py-3 rounded-t-xl flex justify-between items-center shadow-sm">
                <h3 class="text-sm font-semibold tracking-wide flex items-center gap-2"><i class="fa-solid fa-paper-plane text-xs"></i> Pesan Baru</h3>
                <button id="close-compose-btn" class="p-1 hover:bg-gray-600 rounded-lg text-lg leading-none transition-colors w-7 h-7">&times;</button>
            </header>
            
            <form id="compose-form" class="p-4 flex-1 flex flex-col gap-3 overflow-y-auto">
                <!-- Autocomplete Input Kepada -->
                <div class="relative w-full">
                    <div class="flex items-center border-b border-gray-200 focus-within:border-blue-500 pb-2">
                        <span class="text-gray-400 mr-2 text-sm">Kepada:</span>
                        <input type="text" id="compose-to" placeholder="Cari nama, email, atau no. WA pengguna..." class="w-full text-sm focus:outline-none bg-transparent" required autocomplete="off">
                    </div>
                    <!-- Dropdown List Hasil Pencarian -->
                    <div id="user-suggestions" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-56 overflow-y-auto">
                        <!-- Isi dropdown akan di-generate via JS -->
                    </div>
                </div>

                <!-- Input Subjek -->
                <input type="text" id="compose-subject" placeholder="Subjek email..." class="w-full pb-2 text-sm border-b border-gray-200 focus:outline-none focus:border-blue-500" required>
                
                <!-- Container untuk Rich Text Editor (Quill) -->
                <div class="flex-1 flex flex-col mt-2">
                    <div id="editor-container"></div>
                </div>

                <!-- Preview file lampiran -->
                <div id="attachment-list" class="flex flex-wrap gap-2 mt-2"></div>
                <!-- Input file tersembunyi -->
                <input type="file" id="compose-attachments" class="hidden" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip">
            </form>
            
            <footer class="px-4 py-3 border-t bg-gray-50 rounded-b-xl flex justify-between items-center">
                <!-- Tombol Lampiran -->
                <button type="button" id="btn-attach" class="text-gray-500 hover:text-blue-600 p-2 rounded-full hover:bg-blue-50 transition-colors flex items-center justify-center" title="Lampirkan File">
                    <i class="fa-solid fa-paperclip text-lg"></i>
                </button>

                <!-- Tombol Kirim -->
                <button id="send-email-btn" class="bg-blue-600 text-white text-sm font-semibold px-6 py-2.5 rounded-lg hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-2">
                    Kirim Pesan <i class="fa-solid fa-location-arrow"></i>
                </button>
            </footer>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const API_BASE_URL = '/admin/api/email';
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const CSRF_TOKEN = csrfMeta ? csrfMeta.getAttribute('content') : '{{ csrf_token() }}';

    let currentFolder = 'inbox';
    const ui = {
        list: document.getElementById('email-list'), 
        content: document.getElementById('email-content'),
        placeholder: document.getElementById('email-placeholder'), 
        unreadBadge: document.getElementById('unread-count'),
        search: document.getElementById('search-input'),
        selectAll: document.getElementById('select-all'),
        deleteBtn: document.getElementById('btn-delete-selected')
    };

    // --- INISIALISASI QUILL EDITOR ---
    const quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Tulis email Anda di sini...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'], 
                [{ 'list': 'ordered'}, { 'list': 'bullet' }], 
                [{ 'align': [] }],                            
                ['link', 'image'],                            
                ['clean']                                     
            ]
        }
    });

    // --- LOGIKA LAMPIRAN FILE ---
    const fileInput = document.getElementById('compose-attachments');
    const attachBtn = document.getElementById('btn-attach');
    const attachmentList = document.getElementById('attachment-list');

    attachBtn.onclick = () => fileInput.click();

    fileInput.addEventListener('change', function() {
        attachmentList.innerHTML = '';
        Array.from(this.files).forEach(file => {
            attachmentList.innerHTML += `
                <span class="bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1 rounded-full text-xs flex items-center gap-2 shadow-sm mb-1">
                    <i class="fa-solid fa-file-lines"></i> <span class="truncate max-w-[150px]">${file.name}</span>
                </span>`;
        });
    });

    // --- FITUR AUTOCOMPLETE PENGGUNA ---
    const inputTo = document.getElementById('compose-to');
    const suggestionsBox = document.getElementById('user-suggestions');
    let searchTimeout;

    inputTo.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            suggestionsBox.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                suggestionsBox.innerHTML = '<div class="p-3 text-xs text-gray-400 text-center"><i class="fa-solid fa-spinner fa-spin"></i> Mencari...</div>';
                suggestionsBox.classList.remove('hidden');

                const res = await fetch(`/admin/api/cari-pengguna-email?q=${encodeURIComponent(query)}`);
                const users = await res.json();

                suggestionsBox.innerHTML = '';

                if (users.length > 0) {
                    users.forEach(user => {
                        const item = document.createElement('div');
                        item.className = 'p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors';
                        
                        item.innerHTML = `
                            <div class="flex justify-between items-center">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-800">
                                        ${user.nama_lengkap} 
                                        <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full ml-1 font-normal">${user.role}</span>
                                    </span>
                                    <span class="text-xs text-gray-500 mt-0.5"><i class="fa-solid fa-envelope text-gray-400 mr-1"></i> ${user.email}</span>
                                </div>
                                <div class="text-xs text-green-600 font-medium">
                                    <i class="fa-brands fa-whatsapp"></i> ${user.no_wa || '-'}
                                </div>
                            </div>
                        `;
                        
                        item.onclick = () => {
                            inputTo.value = user.email;
                            suggestionsBox.classList.add('hidden');
                        };
                        suggestionsBox.appendChild(item);
                    });
                } else {
                    suggestionsBox.innerHTML = '<div class="p-3 text-xs text-red-400 text-center">Pengguna tidak ditemukan</div>';
                }
            } catch (e) {
                console.error("Gagal mengambil data user:", e);
                suggestionsBox.innerHTML = '<div class="p-3 text-xs text-red-500 text-center">Terjadi kesalahan koneksi</div>';
            }
        }, 400); 
    });

    document.addEventListener('click', function(e) {
        if (!inputTo.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.classList.add('hidden');
        }
    });

    // --- MENGATUR VISIBILITAS TOMBOL HAPUS MASAL ---
    function toggleDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.email-checkbox:checked');
        const allBoxes = document.querySelectorAll('.email-checkbox');
        
        if (checkedBoxes.length > 0) {
            ui.deleteBtn.classList.remove('hidden');
        } else {
            ui.deleteBtn.classList.add('hidden');
        }

        // Sinkronisasi status checkbox 'Pilih Semua'
        if(allBoxes.length > 0 && checkedBoxes.length === allBoxes.length) {
            ui.selectAll.checked = true;
        } else {
            ui.selectAll.checked = false;
        }
    }

    // --- EVENT LISTENER PILIH SEMUA ---
    ui.selectAll.addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.email-checkbox').forEach(cb => {
            cb.checked = isChecked;
        });
        toggleDeleteButton();
    });

   // --- EVENT LISTENER HAPUS MASAL ---
    ui.deleteBtn.addEventListener('click', async () => {
        const checkedBoxes = document.querySelectorAll('.email-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(cb => cb.value);

        if (ids.length === 0) return;

        const result = await Swal.fire({
            title: 'Hapus Pesan?',
            text: `${ids.length} pesan terpilih akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        });

        if (result.isConfirmed) {
            ui.deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            try {
                // UBAH URL DITAMBAH /destroy DAN METHOD JADI POST
                const res = await fetch(`${API_BASE_URL}/destroy`, { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids: ids })
                });

                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Gagal menghapus pesan.');

                Swal.fire('Terhapus!', data.message, 'success');
                
                // Reset UI
                ui.selectAll.checked = false;
                toggleDeleteButton();
                ui.content.classList.add('hidden');
                ui.placeholder.classList.remove('hidden');
                
                // Refresh list
                fetchEmails(currentFolder, ui.search.value);

            } catch (err) {
                Swal.fire('Error', err.message, 'error');
                ui.deleteBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Hapus';
            }
        }
    });

    // --- FUNGSI MENGAMBIL DAFTAR EMAIL ---
    async function fetchEmails(folder = 'inbox', query = '') {
        ui.list.innerHTML = `<div class="flex flex-col items-center justify-center h-full text-gray-400"><div class="loader mb-3"></div><p class="text-xs">Sinkronisasi ${folder}...</p></div>`;
        ui.selectAll.checked = false;
        toggleDeleteButton();

        try {
            const res = await fetch(`${API_BASE_URL}?folder=${folder}&search=${encodeURIComponent(query)}`, { 
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'} 
            });
            const data = await res.json();
            if(!res.ok) throw new Error(data.error);
            
            renderList(data.emails);
            
            if(data.unread_count !== undefined) {
                ui.unreadBadge.textContent = data.unread_count;
                ui.unreadBadge.style.display = data.unread_count > 0 ? 'inline-block' : 'none';
            }
        } catch (err) {
            ui.list.innerHTML = `<div class="p-8 text-center text-sm text-red-500">Gagal memuat: ${err.message}</div>`;
        }
    }

    // --- FUNGSI MENGAMBIL DETAIL EMAIL ---
    async function fetchDetail(id) {
        ui.placeholder.classList.add('hidden');
        ui.content.classList.remove('hidden', 'flex');
        ui.content.classList.add('flex');
        document.getElementById('email-body').innerHTML = '<div class="flex justify-center mt-10"><div class="loader"></div></div>';

        try {
            const res = await fetch(`${API_BASE_URL}/${id}`);
            const data = await res.json();
            if(!res.ok) throw new Error();
            
            document.getElementById('email-subject').textContent = data.subject;
            document.getElementById('email-sender-name').textContent = data.from_name;
            document.getElementById('email-sender-address').textContent = `<${data.from_address}>`;
            document.getElementById('email-timestamp').textContent = new Date(data.created_at).toLocaleString('id-ID');
            document.getElementById('email-body').innerHTML = data.body;
            document.getElementById('email-avatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.from_name)}&background=random&color=fff&size=128`;
            
            fetchEmails(currentFolder, ui.search.value);
        } catch {
            document.getElementById('email-body').innerHTML = '<p class="text-red-500 text-center mt-10">Gagal memuat isi pesan.</p>';
        }
    }

    // --- FUNGSI RENDER LIST EMAIL (Dengan Checkbox) ---
    function renderList(emails) {
        ui.list.innerHTML = '';
        if(emails.length === 0) return ui.list.innerHTML = `<div class="p-10 text-center text-gray-400 text-sm">Kosong.</div>`;
        
        emails.forEach(em => {
            const isUnread = !em.read_at;
            const star = em.is_starred ? 'fa-solid fa-star text-yellow-400' : 'fa-regular fa-star text-gray-300';
            const color = ['bg-red-500', 'bg-blue-500', 'bg-green-500'][em.from_name.length % 3];
            
            ui.list.innerHTML += `
                <div class="email-item flex items-start gap-3 p-3.5 border-b cursor-pointer ${isUnread ? 'bg-[#f4f8ff] font-semibold border-l-4 border-l-blue-500' : 'border-l-4 border-l-transparent'}" data-id="${em.id}">
                    <!-- Checkbox -->
                    <div class="flex items-center h-full pt-1.5 z-10">
                        <input type="checkbox" value="${em.id}" class="email-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                    </div>

                    <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-full ${color} text-white flex items-center justify-center text-xs font-bold">${em.from_name.charAt(0).toUpperCase()}</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline mb-1">
                            <span class="truncate w-32 lg:w-44 text-[13px]">${em.from_name}</span>
                            <span class="text-[11px] ${isUnread ? 'text-blue-600' : 'text-gray-400'}">${new Date(em.created_at).toLocaleDateString('id-ID',{day:'numeric',month:'short'})}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <p class="truncate text-[13px] ${isUnread ? 'text-gray-800' : 'text-gray-500'} flex-1">${em.subject}</p>
                            <button class="star-btn p-1 hover:bg-gray-200 rounded-full z-10" data-id="${em.id}" data-starred="${em.is_starred}"><i class="${star} text-xs"></i></button>
                        </div>
                    </div>
                </div>`;
        });
    }

    // --- MODAL KIRIM EMAIL ---
    const modal = document.getElementById('compose-modal');
    document.getElementById('compose-btn').onclick = () => modal.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-8');
    document.getElementById('close-compose-btn').onclick = () => modal.classList.add('opacity-0', 'pointer-events-none', 'translate-y-8');

    // --- MENGIRIM EMAIL ---
    document.getElementById('send-email-btn').onclick = async function(e) {
        e.preventDefault();

        const to = document.getElementById('compose-to').value;
        const subject = document.getElementById('compose-subject').value;
        const bodyHTML = quill.root.innerHTML;

        if(!to || !subject || quill.getText().trim().length === 0) {
            Swal.fire('Peringatan', 'Kepada, Subjek, dan Isi Pesan tidak boleh kosong!', 'warning');
            return;
        }

        const btn = this; 
        btn.disabled = true; 
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';
        
        const formData = new FormData();
        formData.append('to', to);
        formData.append('subject', subject);
        formData.append('body', bodyHTML);
        
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append('attachments[]', fileInput.files[i]);
        }

        try {
            const res = await fetch(`${API_BASE_URL}/send`, {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const data = await res.json();
            
            if(res.ok) {
                Swal.fire({icon: 'success', title: 'Pesan Terkirim', showConfirmButton: false, timer: 1500});
                modal.classList.add('opacity-0', 'pointer-events-none', 'translate-y-8');
                document.getElementById('compose-form').reset();
                quill.setContents([]); 
                attachmentList.innerHTML = ''; 
                fileInput.value = ''; 
            } else {
                throw new Error(data.message || 'Gagal mengirim pesan.');
            }
        } catch(err) { 
            Swal.fire('Error', err.message, 'error'); 
        }
        
        btn.disabled = false; 
        btn.innerHTML = 'Kirim Pesan <i class="fa-solid fa-location-arrow"></i>';
    };

    // --- NAVIGASI FOLDER ---
    document.querySelectorAll('.folder-link').forEach(link => {
        link.onclick = (e) => {
            e.preventDefault();
            document.querySelectorAll('.folder-link').forEach(l => {
                l.classList.replace('bg-blue-50','hover:bg-gray-100');
                l.classList.remove('text-blue-700','font-semibold');
            });
            link.classList.add('bg-blue-50', 'text-blue-700', 'font-semibold');
            
            currentFolder = link.dataset.folder;
            ui.content.classList.add('hidden'); 
            ui.placeholder.classList.remove('hidden');
            
            fetchEmails(currentFolder);
        }
    });

    // --- KLIK LIST EMAIL ---
    ui.list.onclick = (e) => {
        // Cegah klik checkbox agar tidak membuka detail email
        if(e.target.classList.contains('email-checkbox')) {
            toggleDeleteButton();
            return;
        }

        const star = e.target.closest('.star-btn');
        if(star) {
            e.stopPropagation();
            fetch(`${API_BASE_URL}/${star.dataset.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ is_starred: !(star.dataset.starred === 'true') })
            }).then(() => fetchEmails(currentFolder, ui.search.value));
            return;
        }
        
        const item = e.target.closest('.email-item');
        if(item) {
            document.querySelectorAll('.email-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
            fetchDetail(item.dataset.id);
        }
    };

    // --- PENCARIAN EMAIL ---
    let toSearch; 
    ui.search.oninput = () => { 
        clearTimeout(toSearch); 
        toSearch = setTimeout(() => fetchEmails(currentFolder, ui.search.value), 500); 
    };
    
    // Inisialisasi awal
    fetchEmails(); 
});
</script>
@endpush