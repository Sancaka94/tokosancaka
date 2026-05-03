@extends('layouts.admin')

@push('styles')
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        .email-item.active {
            background-color: #e8f0fe;
            border-left: 4px solid #1a73e8;
        }
        .email-item:not(.active):hover {
            background-color: #f8fafc;
            box-shadow: inset 1px 0 0 #e2e8f0, inset -1px 0 0 #e2e8f0;
        }
        #compose-modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        /* Kustomisasi Scrollbar agar lebih rapi */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent; 
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; 
        }
        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
@endpush

@section('content')
    <!-- Container Utama: Menyesuaikan sisa tinggi layar admin -->
    <div id="app" class="w-full flex h-[calc(100vh-8rem)] min-h-[600px] bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- KOLOM 1: Sidebar Kiri -->
        <aside class="w-56 lg:w-64 flex-shrink-0 flex flex-col h-full border-r border-gray-200 bg-gray-50/50">
            <div class="px-5 h-16 flex items-center border-b border-transparent">
                <h1 class="text-xl font-bold text-gray-800"><i class="fa-solid fa-envelope-open-text text-blue-600 mr-2"></i>EmailApp</h1>
            </div>
            <div class="p-3">
                <button id="compose-btn" class="w-full flex items-center justify-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 hover:shadow transition-all duration-200 text-gray-700 font-semibold py-2.5 px-4 rounded-xl shadow-sm">
                    <i class="fa-solid fa-pen text-sm"></i> Tulis Pesan
                </button>
            </div>
            <nav id="folder-nav" class="mt-2 flex-1 p-3 space-y-1 overflow-y-auto">
                <a href="#" data-folder="inbox" class="folder-link flex items-center justify-between px-3 py-2.5 rounded-lg bg-blue-50 text-blue-700 font-semibold transition-colors">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-inbox w-4 text-center"></i>
                        <span class="text-sm">Kotak Masuk</span>
                    </div>
                    <span class="text-xs font-bold bg-blue-200/50 text-blue-800 px-2 py-0.5 rounded-full" id="unread-count">0</span>
                </a>
                <a href="#" data-folder="starred" class="folder-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 text-gray-600 font-medium transition-colors">
                    <i class="fa-solid fa-star w-4 text-center"></i>
                    <span class="text-sm">Berbintang</span>
                </a>
                <a href="#" data-folder="sent" class="folder-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 text-gray-600 font-medium transition-colors">
                    <i class="fa-solid fa-paper-plane w-4 text-center"></i>
                    <span class="text-sm">Terkirim</span>
                </a>
                <a href="#" data-folder="spam" class="folder-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 text-gray-600 font-medium transition-colors">
                    <i class="fa-solid fa-shield-virus w-4 text-center"></i>
                    <span class="text-sm">Spam</span>
                </a>
                <a href="#" data-folder="trash" class="folder-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 text-gray-600 font-medium transition-colors">
                    <i class="fa-solid fa-trash w-4 text-center"></i>
                    <span class="text-sm">Sampah</span>
                </a>
            </nav>
        </aside>

        <!-- KOLOM 2: Daftar Email (Inbox) -->
        <main class="w-[300px] lg:w-[360px] flex-shrink-0 flex flex-col h-full border-r border-gray-200 bg-white z-10 shadow-[4px_0_10px_rgba(0,0,0,0.02)]">
            <!-- Header Search Bar -->
            <header class="h-16 flex items-center px-4 border-b border-gray-200 flex-shrink-0 bg-white">
                <form id="search-form" class="w-full">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fa-solid fa-search text-gray-400 text-sm"></i>
                        </span>
                        <input type="search" id="search-input" name="search" placeholder="Cari di email..." class="w-full bg-gray-100/80 border-transparent focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg py-2 pl-9 pr-4 text-sm transition-all duration-200 outline-none">
                    </div>
                </form>
            </header>
            
            <!-- List Container -->
            <div id="email-list" class="flex-1 overflow-y-auto bg-white">
                <!-- Elemen Email Injeksi JS -->
            </div>
        </main>

        <!-- KOLOM 3: Detail Email (Reading Pane) -->
        <section id="email-view" class="flex-1 flex flex-col h-full bg-[#f8fafc] relative overflow-hidden">
            <!-- Placeholder saat tidak ada email yg dipilih -->
            <div id="email-placeholder" class="flex flex-col items-center justify-center h-full text-gray-400">
                <div class="bg-gray-100 p-6 rounded-full mb-4 shadow-inner">
                    <i class="fa-solid fa-envelope-open text-5xl text-gray-300"></i>
                </div>
                <p class="text-lg font-medium text-gray-500">Pilih email untuk dibaca</p>
                <p class="text-sm mt-1">Klik salah satu pesan dari panel sebelah kiri.</p>
            </div>

            <!-- Konten Email -->
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
                
                <!-- Body Isi Pesan -->
                <div class="flex-1 p-8 overflow-y-auto text-gray-700 leading-relaxed text-sm" id="email-body"></div>
            </div>
        </section>
    </div>

    <!-- Modal Tulis Email -->
    <div id="compose-modal" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm flex justify-end items-end z-[100] opacity-0 pointer-events-none transform translate-y-8 transition-all duration-300">
        <div class="bg-white rounded-t-xl shadow-2xl w-full max-w-xl h-[70%] flex flex-col mr-4 md:mr-10 border border-gray-200">
            <header class="bg-gray-800 text-white px-4 py-3 rounded-t-xl flex justify-between items-center shadow-sm">
                <h3 class="text-sm font-semibold tracking-wide flex items-center gap-2"><i class="fa-solid fa-paper-plane text-xs"></i> Pesan Baru</h3>
                <button id="close-compose-btn" class="p-1 hover:bg-gray-600 rounded-lg text-lg leading-none transition-colors w-7 h-7 flex items-center justify-center">&times;</button>
            </header>
            <form id="compose-form" class="p-4 flex-1 flex flex-col gap-3">
                <input type="email" id="compose-to" name="to_address" placeholder="Kepada" class="w-full pb-2 text-sm border-b border-gray-200 focus:outline-none focus:border-blue-500 transition-colors" required>
                <input type="text" id="compose-subject" name="subject" placeholder="Subjek" class="w-full pb-2 text-sm border-b border-gray-200 focus:outline-none focus:border-blue-500 transition-colors" required>
                <textarea id="compose-body" name="body" class="w-full flex-1 text-sm resize-none focus:outline-none mt-2" placeholder="Tulis email Anda di sini..." required></textarea>
            </form>
            <footer class="px-4 py-3 border-t flex justify-between items-center bg-gray-50 rounded-b-xl">
                <button id="send-email-btn" class="bg-blue-600 text-white text-sm font-semibold px-6 py-2.5 rounded-lg hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-2">
                    Kirim Pesan <i class="fa-solid fa-location-arrow"></i>
                </button>
            </footer>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const API_BASE_URL = '/admin/api/email';
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const CSRF_TOKEN = csrfMeta ? csrfMeta.getAttribute('content') : '{{ csrf_token() }}';

    const emailListContainer = document.getElementById('email-list');
    const emailPlaceholder = document.getElementById('email-placeholder');
    const emailContent = document.getElementById('email-content');
    const unreadCountEl = document.getElementById('unread-count');
    
    const composeBtn = document.getElementById('compose-btn');
    const composeModal = document.getElementById('compose-modal');
    const closeComposeBtn = document.getElementById('close-compose-btn');
    const sendEmailBtn = document.getElementById('send-email-btn');
    const composeForm = document.getElementById('compose-form');
    const folderNav = document.getElementById('folder-nav');

    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    
    let currentFolder = 'inbox';

    // Fetch List
    async function fetchEmails(folder = 'inbox', searchQuery = '') {
        showLoader(emailListContainer);
        
        try {
            let url = `${API_BASE_URL}?folder=${folder}`;
            if (searchQuery) url += `&search=${encodeURIComponent(searchQuery)}`;

            const response = await fetch(url, {
                headers: { 
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!response.ok) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat',
                        text: 'Silakan periksa log server.'
                    });
                }
                emailListContainer.innerHTML = `<div class="p-8 text-center text-sm text-red-500">Gagal mengambil data email.</div>`;
                return;
            }

            renderEmailList(result.emails);
            if(unreadCountEl && result.unread_count !== undefined) {
                unreadCountEl.textContent = result.unread_count;
                // Sembunyikan badge angka 0 jika tidak ada pesan baru
                unreadCountEl.style.display = result.unread_count > 0 ? 'inline-block' : 'none';
            }
            
        } catch (error) {
            emailListContainer.innerHTML = `<div class="p-8 text-center text-sm text-gray-400"><i class="fa-solid fa-wifi mb-2 text-xl block"></i>Terjadi masalah koneksi internet.</div>`;
        }
    }

    // Fetch Detail
    async function fetchEmailDetail(id) {
        showLoader(emailContent);
        emailPlaceholder.classList.add('hidden');

        try {
            const response = await fetch(`${API_BASE_URL}/${id}`);
            if (!response.ok) throw new Error('Gagal memuat pesan');
            const email = await response.json();
            renderEmailDetail(email);
            fetchEmails(currentFolder, searchInput.value.trim()); // Auto-update status read
        } catch (error) {
            emailContent.innerHTML = `<div class="p-8 text-center text-red-500"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Gagal memuat detail email.</div>`;
        }
    }

    // Send Mail
    async function sendEmail() {
        const data = {
            to: document.getElementById('compose-to').value,
            subject: document.getElementById('compose-subject').value,
            body: document.getElementById('compose-body').value
        };

        // UI Feedback
        const originalText = sendEmailBtn.innerHTML;
        sendEmailBtn.disabled = true;
        sendEmailBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Mengirim...';
        sendEmailBtn.classList.add('opacity-75');

        try {
            const response = await fetch(`${API_BASE_URL}/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json', 
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Terkirim',
                    text: 'Pesan berhasil dikirim.',
                    timer: 2000,
                    showConfirmButton: false
                });
                closeComposeModal();
                switchFolder('sent');
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Terjadi kesalahan sistem.' });
            }

        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Kesalahan', text: 'Koneksi terputus ke server.' });
        } finally {
            sendEmailBtn.disabled = false;
            sendEmailBtn.innerHTML = originalText;
            sendEmailBtn.classList.remove('opacity-75');
        }
    }

    // Update Data
    async function updateEmail(id, data) {
        try {
            await fetch(`${API_BASE_URL}/${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(data)
            });
            fetchEmails(currentFolder, searchInput.value.trim());
        } catch (error) {
            console.error('Update error:', error);
        }
    }

    // --- DOM RENDERERS ---

    function renderEmailList(emails = []) {
        emailListContainer.innerHTML = '';
        
        if (emails.length === 0) {
            emailListContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full p-10 text-gray-400">
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-inbox-8316262-6632282.png" class="w-32 opacity-70 mb-2 grayscale" alt="Empty">
                    <p class="text-sm font-medium">Folder ini kosong.</p>
                </div>`;
            return;
        }

        emails.forEach(email => {
            const isUnread = !email.read_at;
            const item = document.createElement('div');
            
            // Highlight jika unread
            item.className = `email-item flex items-start gap-3 p-3.5 border-b border-gray-100 cursor-pointer transition-colors relative ${isUnread ? 'bg-[#f4f8ff] font-semibold border-l-4 border-l-blue-500' : 'bg-white font-normal border-l-4 border-l-transparent'}`;
            item.dataset.id = email.id;

            const starIconClass = email.is_starred ? 'fa-solid fa-star text-yellow-400 drop-shadow-sm' : 'fa-regular fa-star text-gray-300';
            const userInitial = email.from_name.charAt(0).toUpperCase();
            // Memberikan warna background random pada avatar list
            const bgColors = ['bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500'];
            const randomColor = bgColors[email.from_name.length % bgColors.length];

            item.innerHTML = `
                <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-full ${randomColor} text-white flex items-center justify-center text-xs font-bold shadow-sm">
                    ${userInitial}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-baseline mb-1">
                        <span class="truncate w-36 lg:w-48 text-[13px] ${isUnread ? 'text-gray-900' : 'text-gray-700'}">${email.from_name}</span>
                        <span class="text-[11px] ${isUnread ? 'text-blue-600 font-bold' : 'text-gray-400'} flex-shrink-0">${formatDate(email.created_at)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <p class="truncate text-[13px] ${isUnread ? 'text-gray-800' : 'text-gray-500'} flex-1">${email.subject}</p>
                        <button class="star-btn p-1 rounded-full hover:bg-gray-200 flex-shrink-0 z-10 transition" data-id="${email.id}" data-starred="${email.is_starred}">
                            <i class="${starIconClass} text-xs"></i>
                        </button>
                    </div>
                </div>
            `;
            emailListContainer.appendChild(item);
        });
    }

    function renderEmailDetail(email) {
        emailContent.classList.remove('hidden');
        emailContent.classList.add('flex');
        
        document.getElementById('email-subject').textContent = email.subject;
        document.getElementById('email-sender-name').textContent = email.from_name;
        document.getElementById('email-sender-address').textContent = `<${email.from_address}>`;
        
        // Format Tanggal Detail
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute:'2-digit' };
        document.getElementById('email-timestamp').textContent = new Date(email.created_at).toLocaleDateString('id-ID', options);
        
        document.getElementById('email-body').innerHTML = email.body;
        document.getElementById('email-avatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(email.from_name)}&background=random&color=fff&size=128`;
    }
    
    function showLoader(element) {
        element.innerHTML = `<div class="flex flex-col justify-center items-center h-full w-full text-gray-400"><div class="loader mb-3"></div><p class="text-xs">Memuat...</p></div>`;
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    }

    // --- EVENT LISTENERS & TRIGGERS ---

    function openComposeModal() {
        composeForm.reset();
        composeModal.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-8');
    }

    function closeComposeModal() {
        composeModal.classList.add('opacity-0', 'pointer-events-none', 'translate-y-8');
    }
    
    function switchFolder(folder) {
        currentFolder = folder;
        
        // Reset state Nav
        document.querySelectorAll('.folder-link').forEach(link => {
            link.classList.remove('bg-blue-50', 'text-blue-700', 'font-semibold');
            link.classList.add('hover:bg-gray-100', 'text-gray-600', 'font-medium');
            
            if (link.dataset.folder === folder) {
                link.classList.add('bg-blue-50', 'text-blue-700', 'font-semibold');
                link.classList.remove('hover:bg-gray-100', 'text-gray-600', 'font-medium');
            }
        });

        // Hide detail view & clear search
        emailContent.classList.add('hidden');
        emailContent.classList.remove('flex');
        emailPlaceholder.classList.remove('hidden');
        searchInput.value = '';
        
        fetchEmails(folder);
    }

    emailListContainer.addEventListener('click', (e) => {
        const starButton = e.target.closest('.star-btn');
        if (starButton) {
            e.stopPropagation();
            const id = starButton.dataset.id;
            const isStarred = starButton.dataset.starred === 'true';
            updateEmail(id, { is_starred: !isStarred });
            return;
        }

        const emailItem = e.target.closest('.email-item');
        if (emailItem) {
            const emailId = parseInt(emailItem.dataset.id);
            fetchEmailDetail(emailId);
            
            document.querySelectorAll('.email-item').forEach(item => item.classList.remove('active'));
            emailItem.classList.add('active');
        }
    });
    
    folderNav.addEventListener('click', (e) => {
        e.preventDefault();
        const link = e.target.closest('.folder-link');
        if (link && link.dataset.folder) {
            switchFolder(link.dataset.folder);
        }
    });
    
    composeBtn.addEventListener('click', openComposeModal);
    closeComposeBtn.addEventListener('click', closeComposeModal);
    sendEmailBtn.addEventListener('click', sendEmail);

    // Filter Pencarian
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const query = searchInput.value.trim();
        fetchEmails(currentFolder, query);
    });

    let searchTimeout = null;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchEmails(currentFolder, searchInput.value.trim());
        }, 500); // Auto-search typing delay 500ms
    });

    // --- INISIALISASI ---
    fetchEmails(currentFolder);
});
</script>
@endpush