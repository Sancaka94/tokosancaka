@extends('layouts.admin')

@push('styles')
    <!-- FontAwesome & SweetAlert (Gunakan CDN ini jika belum ada di layout admin) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        .email-item.active {
            background-color: #e8f0fe;
            border-left: 3px solid #1a73e8;
            font-weight: bold;
        }
        .email-item:not(.active):hover {
            background-color: #f5f5f5;
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
            z-index: 10;
        }
        #compose-modal {
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* Style untuk dropdown menu */
        #user-menu {
            transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
        }
    </style>
@endpush

@section('content')
    <!-- Jika Tailwind & SweetAlert belum diload secara global di layouts.admin, aktifkan baris di bawah -->
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->

    <div id="app" class="h-[calc(100vh-6rem)] w-full flex overflow-hidden rounded-xl shadow-lg border border-gray-200">
        
        <!-- Sidebar Kiri -->
        <aside class="w-64 bg-gray-50 flex flex-col flex-shrink-0 h-full border-r">
            <div class="px-4 h-16 flex items-center">
                <h1 class="text-2xl font-bold text-gray-700">EmailApp</h1>
            </div>
            <div class="p-2">
                <button id="compose-btn" class="w-full flex items-center justify-center gap-2 bg-white hover:shadow-lg transition-shadow duration-200 text-gray-600 font-semibold py-3 px-4 rounded-full shadow-md">
                    <i class="fa-solid fa-pencil"></i>
                    Tulis Email
                </button>
            </div>
            <nav id="folder-nav" class="mt-4 flex-1 p-2">
                <a href="#" data-folder="inbox" class="folder-link flex items-center justify-between px-4 py-2 rounded-r-full bg-blue-100 text-blue-700 font-bold">
                    <div class="flex items-center gap-4">
                        <i class="fa-solid fa-inbox"></i>
                        <span>Kotak Masuk</span>
                    </div>
                    <span class="text-xs font-bold bg-white px-2 py-0.5 rounded-full" id="unread-count">0</span>
                </a>
                <a href="#" data-folder="starred" class="folder-link flex items-center gap-4 px-4 py-2 rounded-r-full hover:bg-gray-200 text-gray-600">
                    <i class="fa-solid fa-star"></i>
                    <span>Berbintang</span>
                </a>
                <a href="#" data-folder="sent" class="folder-link flex items-center gap-4 px-4 py-2 rounded-r-full hover:bg-gray-200 text-gray-600">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Terkirim</span>
                </a>
                <a href="#" data-folder="spam" class="folder-link flex items-center gap-4 px-4 py-2 rounded-r-full hover:bg-gray-200 text-gray-600">
                    <i class="fa-solid fa-shield-virus"></i>
                    <span>Spam</span>
                </a>
                <a href="#" data-folder="trash" class="folder-link flex items-center gap-4 px-4 py-2 rounded-r-full hover:bg-gray-200 text-gray-600">
                    <i class="fa-solid fa-trash"></i>
                    <span>Sampah</span>
                </a>
            </nav>
        </aside>

        <!-- Daftar Email (Kolom Tengah) -->
        <main class="flex-1 bg-white flex flex-col h-full overflow-hidden">
            <header class="h-16 flex items-center justify-between px-4 gap-4 border-b flex-shrink-0">
                <form id="search-form" class="flex-1 max-w-2xl">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fa-solid fa-search text-gray-400"></i>
                        </span>
                        <input type="search" id="search-input" name="search" placeholder="Cari di email" class="w-full bg-gray-100 rounded-lg py-2.5 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    </div>
                </form>
                <!-- Info Akun Pengguna dengan Dropdown -->
                <div class="relative">
                    <button id="user-avatar-btn" class="flex items-center gap-4">
                         <span id="user-email" class="text-sm text-gray-600 hidden md:block">{{ Auth::user()->email ?? 'pengguna@email.com' }}</span>
                         <img id="user-avatar" src="https://placehold.co/32x32/7F9CF5/EBF4FF?text={{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}" alt="User Avatar" class="w-8 h-8 rounded-full border shadow-sm">
                    </button>
                    <!-- Dropdown Menu -->
                    <div id="user-menu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 opacity-0 pointer-events-none transform scale-95">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profil</a>
                        <a href="{{ route('logout') }}" 
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                           Keluar
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </div>
                </div>
            </header>
            <div id="email-list" class="flex-1 overflow-y-auto bg-gray-50/50">
                <!-- Email items will be injected here -->
            </div>
        </main>

        <!-- Detail Email (Kolom Kanan) -->
        <section id="email-view" class="w-full lg:w-3/5 xl:w-2/3 bg-white h-full flex flex-col border-l overflow-hidden">
            <div id="email-placeholder" class="flex flex-col items-center justify-center h-full text-gray-500">
                <i class="fa-regular fa-envelope-open text-6xl text-gray-300 mb-4"></i>
                <p class="text-lg">Pilih email untuk dibaca</p>
            </div>
            <div id="email-content" class="hidden flex-1 flex flex-col">
                <header class="p-4 border-b flex-shrink-0">
                    <h2 id="email-subject" class="text-2xl font-semibold text-gray-800 mb-2"></h2>
                    <div class="flex items-center">
                        <img id="email-avatar" src="https://placehold.co/40x40/EFEFEF/333333?text=A" alt="Avatar" class="w-10 h-10 rounded-full mr-3">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800" id="email-sender-name"></p>
                            <p class="text-sm text-gray-500" id="email-sender-address"></p>
                        </div>
                        <div class="text-sm text-gray-500" id="email-timestamp"></div>
                    </div>
                </header>
                <div class="flex-1 p-6 overflow-y-auto text-gray-700" id="email-body"></div>
                <footer class="p-4 border-t bg-gray-50">
                    <!-- Reply/Forward buttons -->
                </footer>
            </div>
        </section>
    </div>

    <!-- Modal Tulis Email -->
    <div id="compose-modal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-end items-end z-50 opacity-0 pointer-events-none transform translate-y-10 transition-all duration-300">
        <div class="bg-white rounded-t-xl shadow-2xl w-full max-w-2xl h-[70%] flex flex-col mr-4 md:mr-10">
            <header class="bg-gray-800 text-white px-4 py-3 rounded-t-xl flex justify-between items-center shadow-sm">
                <h3 class="text-sm font-semibold tracking-wide">Pesan Baru</h3>
                <button id="close-compose-btn" class="p-1 hover:bg-gray-600 rounded-full text-2xl leading-none transition-colors">&times;</button>
            </header>
            <form id="compose-form" class="p-4 flex-1 flex flex-col gap-2">
                <input type="email" id="compose-to" name="to_address" placeholder="Kepada" class="w-full p-2 border-b focus:outline-none focus:border-blue-500 transition-colors" required>
                <input type="text" id="compose-subject" name="subject" placeholder="Subjek" class="w-full p-2 border-b focus:outline-none focus:border-blue-500 transition-colors" required>
                <textarea id="compose-body" name="body" class="w-full p-2 flex-1 resize-none focus:outline-none mt-2" placeholder="Tulis email Anda di sini..." required></textarea>
            </form>
            <footer class="p-4 border-t flex justify-between items-center bg-gray-50">
                <button id="send-email-btn" class="bg-blue-600 text-white font-semibold px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">Kirim</button>
            </footer>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    // --- KONFIGURASI & ELEMEN DOM ---
    const API_BASE_URL = '/admin/api/email';
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    // Mencegah error JS jika token CSRF tidak ditemukan di layout
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

    const userAvatarBtn = document.getElementById('user-avatar-btn');
    const userMenu = document.getElementById('user-menu');
    
    let currentFolder = 'inbox'; // Folder default

    // --- FUNGSI API ---

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
                // JIKA GAGAL (Status 4xx/5xx): Munculkan Modal JSON
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        html: `
                            <div class="text-left">
                                <p class="mb-2 text-sm">Server merespon dengan error:</p>
                                <pre class="bg-red-50 p-3 text-xs rounded border border-red-200 overflow-x-auto text-red-700">${JSON.stringify(result, null, 2)}</pre>
                            </div>
                        `,
                        confirmButtonText: 'Tutup'
                    });
                }
                emailListContainer.innerHTML = `<div class="p-10 text-center text-gray-400">Gagal mengambil email. Silakan muat ulang halaman.</div>`;
                return;
            }

            // JIKA SUKSES
            renderEmailList(result.emails);
            if(unreadCountEl && result.unread_count !== undefined) {
                unreadCountEl.textContent = result.unread_count;
            }
            
        } catch (error) {
            // JIKA KESALAHAN JARINGAN (CORS/Offline)
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Masalah Koneksi',
                    html: `<pre class="text-left bg-gray-100 p-2 text-xs">${JSON.stringify({message: error.message}, null, 2)}</pre>`
                });
            }
        }
    }

    async function fetchEmailDetail(id) {
        showLoader(emailContent);
        emailPlaceholder.classList.add('hidden');

        try {
            const response = await fetch(`${API_BASE_URL}/${id}`);
            if (!response.ok) throw new Error('Gagal mengambil detail email');
            const email = await response.json();
            renderEmailDetail(email);
            fetchEmails(currentFolder, searchInput.value.trim());
        } catch (error) {
            console.error('Error fetching email detail:', error);
            emailContent.innerHTML = `<p class="p-4 text-red-500">Gagal memuat detail email.</p>`;
        }
    }

    async function sendEmail() {
        const formData = new FormData(composeForm);
        const data = {
            to: document.getElementById('compose-to').value,
            subject: document.getElementById('compose-subject').value,
            body: document.getElementById('compose-body').value
        };

        sendEmailBtn.disabled = true;
        sendEmailBtn.textContent = 'Mengirim...';

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
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: result.message,
                        confirmButtonColor: '#1a73e8'
                    });
                }
                closeComposeModal();
                switchFolder('sent');
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengirim!',
                        text: result.message || 'Terjadi kesalahan pada server SMTP.'
                    });
                }
            }

        } catch (error) {
            console.error('Error detail:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Sistem',
                    text: 'Tidak dapat menghubungi server: ' + error.message
                });
            }
        } finally {
            sendEmailBtn.disabled = false;
            sendEmailBtn.textContent = 'Kirim';
        }
    }

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
            console.error('Error updating email:', error);
        }
    }


    // --- FUNGSI RENDER TAMPILAN ---

    function renderEmailList(emails = []) {
        emailListContainer.innerHTML = '';
        
        if (emails.length === 0) {
            emailListContainer.innerHTML = `<div class="p-10 text-center text-gray-500 flex flex-col items-center gap-2"><i class="fa-solid fa-folder-open text-4xl opacity-50"></i>Folder ini kosong.</div>`;
            return;
        }

        emails.forEach(email => {
            const isRead = email.read_at !== null;
            const isUnread = !email.read_at;
            const item = document.createElement('div');
            
            // Kombinasi class berdasarkan status read/unread
            item.className = `email-item flex items-center gap-4 p-3 border-b cursor-pointer transition-all duration-150 relative ${isUnread ? 'bg-blue-50 font-bold border-l-4 border-l-blue-500' : 'bg-white font-normal'}`;
            item.dataset.id = email.id;

            const starIconClass = email.is_starred ? 'fa-solid fa-star text-yellow-500' : 'fa-regular fa-star text-gray-400';

            item.innerHTML = `
                <input type="checkbox" class="h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300 flex-shrink-0 cursor-pointer z-10" onclick="event.stopPropagation()">
                <button class="star-btn p-1 rounded-full hover:bg-gray-200 flex-shrink-0 z-10" data-id="${email.id}" data-starred="${email.is_starred}">
                    <i class="${starIconClass}"></i>
                </button>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-baseline mb-0.5">
                        <span class="truncate w-32 md:w-48 text-gray-800">${email.from_name}</span>
                        <span class="text-xs text-gray-500 font-normal flex-shrink-0">${formatDate(email.created_at)}</span>
                    </div>
                    <p class="truncate text-sm text-gray-600">${email.subject}</p>
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
        document.getElementById('email-timestamp').textContent = new Date(email.created_at).toLocaleString('id-ID');
        document.getElementById('email-body').innerHTML = email.body;
        document.getElementById('email-avatar').src = `https://placehold.co/40x40/EFEFEF/333333?text=${email.from_name.charAt(0).toUpperCase()}`;
    }
    
    function showLoader(element) {
        element.innerHTML = `<div class="flex justify-center items-center h-full w-full"><div class="loader"></div></div>`;
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    }


    // --- FUNGSI UTILITAS & EVENT HANDLER ---

    function openComposeModal() {
        composeForm.reset();
        composeModal.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-10');
    }

    function closeComposeModal() {
        composeModal.classList.add('opacity-0', 'pointer-events-none', 'translate-y-10');
    }
    
    function switchFolder(folder) {
        currentFolder = folder;
        document.querySelectorAll('.folder-link').forEach(link => {
            link.classList.remove('bg-blue-100', 'text-blue-700', 'font-bold');
            if (link.dataset.folder === folder) {
                link.classList.add('bg-blue-100', 'text-blue-700', 'font-bold');
            }
        });
        searchInput.value = '';
        fetchEmails(folder);
    }

    // --- EVENT LISTENERS ---

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

    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const query = searchInput.value.trim();
        if (query) {
            fetchEmails(currentFolder, query);
        }
    });

    searchInput.addEventListener('input', () => {
        if (searchInput.value.trim() === '') {
            fetchEmails(currentFolder);
        }
    });

    userAvatarBtn.addEventListener('click', () => {
        userMenu.classList.toggle('opacity-0');
        userMenu.classList.toggle('pointer-events-none');
        userMenu.classList.toggle('transform');
        userMenu.classList.toggle('scale-95');
    });

    // Tutup dropdown jika klik di luar
    window.addEventListener('click', (e) => {
        if (!userAvatarBtn.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.add('opacity-0', 'pointer-events-none', 'transform', 'scale-95');
        }
    });

    // --- INISIALISASI ---
    fetchEmails(currentFolder);
});
</script>
@endpush