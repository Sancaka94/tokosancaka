<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka Mail — {{ config('mail.from.address', 'admin@tokosancaka.com') }}</title>

    <!-- Inter Font (Next.js / Clean UI Standard) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Quill.js Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

    <style>
        /* Clean Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Gmail-style Quill Customization */
        .ql-toolbar.ql-snow {
            border: none !important;
            border-top: 1px solid #f1f5f9 !important;
            padding: 6px 12px !important;
            background: #ffffff;
        }
        .ql-container.ql-snow {
            border: none !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 14px !important;
        }
        .ql-editor {
            min-height: 200px;
            max-height: 300px;
            padding: 12px 16px !important;
            color: #1e293b;
        }
    </style>
</head>
<body class="bg-[#f6f8fc] text-slate-800 antialiased h-screen flex flex-col overflow-hidden select-none">

    <!-- ===================================================================== -->
    <!-- 1. TOP HEADER BAR (GMAIL / NEXT.JS CLEAN STYLE)                       -->
    <!-- ===================================================================== -->
    <header class="h-16 px-4 flex items-center justify-between gap-4 shrink-0">
        <!-- Kiri: Hamburger & Logo -->
        <div class="flex items-center gap-3 w-60 shrink-0">
            <button onclick="toggleSidebar()" class="p-2.5 rounded-full hover:bg-slate-200/70 text-slate-600 transition" title="Menu utama">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <a href="{{ route('admin.email.index') }}" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-[#0b57d0] flex items-center justify-center text-white font-semibold text-sm tracking-tight">
                    M
                </div>
                <span class="text-[20px] font-medium text-slate-700 tracking-tight">Mail</span>
            </a>
        </div>

        <!-- Tengah: Pill Search Bar -->
        <div class="flex-1 max-w-2xl">
            <div class="relative flex items-center bg-[#eaf1fb] focus-within:bg-white focus-within:shadow-md focus-within:ring-1 focus-within:ring-slate-200 rounded-full px-4 h-11 transition-all">
                <svg class="w-5 h-5 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/>
                </svg>
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Telusuri email"
                    class="w-full bg-transparent border-none outline-none px-3 text-sm text-slate-800 placeholder-slate-500"
                    onkeyup="handleSearchKeyup(event)"
                >
                <button id="clearSearchBtn" onclick="clearSearch()" class="hidden p-1 rounded-full hover:bg-slate-200 text-slate-500" title="Bersihkan pencarian">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Kanan: Info Akun & Pintasan Toko Sancaka -->
        <div class="flex items-center gap-2 shrink-0">
            <a href="https://tokosancaka.com" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-slate-600 hover:bg-slate-200/70 border border-slate-200/80 bg-white transition">
                <span>tokosancaka.com</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
            <div class="flex items-center gap-2 pl-2">
                <div class="text-right hidden md:block">
                    <div class="text-xs font-medium text-slate-800">{{ Auth::user()->nama_lengkap ?? 'Admin Sancaka' }}</div>
                    <div class="text-[11px] text-slate-500">{{ config('mail.from.address', 'admin@tokosancaka.com') }}</div>
                </div>
                <div class="w-9 h-9 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-semibold">
                    {{ strtoupper(substr(Auth::user()->nama_lengkap ?? 'A', 0, 1)) }}
                </div>
            </div>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 2. MAIN WORKSPACE (SIDEBAR + CONTENT CARD)                            -->
    <!-- ===================================================================== -->
    <div class="flex-1 flex overflow-hidden pr-4 pb-4">

        <!-- LEFT SIDEBAR -->
        <aside id="sidebar" class="w-64 shrink-0 flex flex-col justify-between pr-3 transition-all duration-200">
            <div>
                <!-- Tombol Tulis (Gmail Signature Rounded-2xl #c2e7ff) -->
                <div class="px-3 mb-4">
                    <button onclick="openComposeModal()" class="inline-flex items-center gap-3 bg-[#c2e7ff] hover:shadow-md hover:bg-[#b5dffc] active:scale-[0.99] text-[#001d35] font-medium text-sm px-6 py-4 rounded-2xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        <span>Tulis</span>
                    </button>
                </div>

                <!-- Navigasi Folder -->
                <nav class="space-y-0.5 text-sm">
                    <button onclick="switchFolder('inbox')" data-folder="inbox" class="nav-folder w-full flex items-center justify-between pl-6 pr-4 py-2 rounded-r-full font-semibold bg-[#d3e3fd] text-[#001d35] transition">
                        <span class="flex items-center gap-3.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <span>Kotak Masuk</span>
                        </span>
                        <span id="unreadBadge" class="text-xs font-bold">0</span>
                    </button>

                    <button onclick="switchFolder('starred')" data-folder="starred" class="nav-folder w-full flex items-center justify-between pl-6 pr-4 py-2 rounded-r-full text-slate-700 hover:bg-slate-200/60 transition">
                        <span class="flex items-center gap-3.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                            <span>Berbintang</span>
                        </span>
                    </button>

                    <button onclick="switchFolder('snoozed')" data-folder="snoozed" class="nav-folder w-full flex items-center justify-between pl-6 pr-4 py-2 rounded-r-full text-slate-700 hover:bg-slate-200/60 transition">
                        <span class="flex items-center gap-3.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Ditunda</span>
                        </span>
                    </button>

                    <button onclick="switchFolder('sent')" data-folder="sent" class="nav-folder w-full flex items-center justify-between pl-6 pr-4 py-2 rounded-r-full text-slate-700 hover:bg-slate-200/60 transition">
                        <span class="flex items-center gap-3.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            <span>Terkirim</span>
                        </span>
                    </button>

                    <button onclick="switchFolder('draft')" data-folder="draft" class="nav-folder w-full flex items-center justify-between pl-6 pr-4 py-2 rounded-r-full text-slate-700 hover:bg-slate-200/60 transition">
                        <span class="flex items-center gap-3.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Draf</span>
                        </span>
                    </button>

                    <button onclick="switchFolder('trash')" data-folder="trash" class="nav-folder w-full flex items-center justify-between pl-6 pr-4 py-2 rounded-r-full text-slate-700 hover:bg-slate-200/60 transition">
                        <span class="flex items-center gap-3.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Sampah</span>
                        </span>
                    </button>
                </nav>

                <!-- Label Section (Clean Next.js Style) -->
                <div class="mt-6 pt-4 border-t border-slate-200/70 pl-6 pr-4">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mb-2">
                        <span>Label</span>
                        <span class="text-slate-400 text-base leading-none">+</span>
                    </div>
                    <div class="space-y-1 text-xs text-slate-600">
                        <div class="flex items-center gap-2.5 py-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-[#0b57d0]"></span>
                            <span>Sancaka Express</span>
                        </div>
                        <div class="flex items-center gap-2.5 py-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-slate-400"></span>
                            <span>Payment & Settlement</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Info -->
            <div class="pl-6 pr-4 py-2 text-[11px] text-slate-400">
                IMAP/SMTP • server4.hostdata.id
            </div>
        </aside>

        <!-- MAIN WHITE ROUNDED CONTAINER -->
        <main class="flex-1 bg-white rounded-2xl border border-slate-200/70 flex flex-col overflow-hidden">

            <!-- ============================================================= -->
            <!-- VIEW A: LIST EMAIL VIEW                                       -->
            <!-- ============================================================= -->
            <div id="emailListView" class="flex-1 flex flex-col overflow-hidden">

                <!-- Action Toolbar -->
                <div class="h-12 px-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="w-4 h-4 rounded border-slate-300 text-[#0b57d0] focus:ring-0 cursor-pointer">

                        <!-- Default Refresh Button -->
                        <button id="refreshBtn" onclick="fetchEmails()" class="p-2 rounded-full hover:bg-slate-100 text-slate-600 transition" title="Muat ulang">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>

                        <!-- Bulk Delete Button (Tampil saat ada checkbox dicentang) -->
                        <button id="bulkDeleteBtn" onclick="deleteSelectedEmails()" class="hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-red-600 hover:bg-red-50 transition" title="Hapus email terpilih">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus (<span id="selectedCount">0</span>)</span>
                        </button>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        <span id="paginationInfo">Memuat...</span>
                        <div class="flex items-center gap-1">
                            <button id="prevPageBtn" onclick="changePage(-1)" disabled class="p-1.5 rounded-full hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none text-slate-600 transition" title="Lebih baru">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <button id="nextPageBtn" onclick="changePage(1)" disabled class="p-1.5 rounded-full hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none text-slate-600 transition" title="Lebih lama">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Gmail Category Tabs (Hanya tampil di Kotak Masuk) -->
                <div id="categoryTabs" class="flex border-b border-slate-100 px-2 shrink-0">
                    <button onclick="selectCategoryTab(this)" class="cat-tab relative flex items-center gap-3 px-4 py-3.5 w-52 text-sm font-medium text-[#0b57d0] hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <span>Utama</span>
                        <span class="tab-indicator absolute bottom-0 left-3 right-3 h-[3px] bg-[#0b57d0] rounded-t-full"></span>
                    </button>
                    <button onclick="selectCategoryTab(this)" class="cat-tab relative flex items-center gap-3 px-4 py-3.5 w-52 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span>Promosi</span>
                    </button>
                    <button onclick="selectCategoryTab(this)" class="cat-tab relative flex items-center gap-3 px-4 py-3.5 w-52 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Sosial</span>
                    </button>
                    <button onclick="selectCategoryTab(this)" class="cat-tab relative flex items-center gap-3 px-4 py-3.5 w-52 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Info Terbaru</span>
                    </button>
                </div>

                <!-- Email Rows Container -->
                <div id="emailListContainer" class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    <!-- Diisi oleh JavaScript -->
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- VIEW B: READING PANE (DETAIL EMAIL)                           -->
            <!-- ============================================================= -->
            <div id="emailDetailView" class="hidden flex-1 flex flex-col overflow-hidden">
                <!-- Detail Toolbar -->
                <div class="h-12 px-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <button onclick="closeEmailDetail()" class="p-2 rounded-full hover:bg-slate-100 text-slate-600 transition" title="Kembali ke daftar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </button>
                        <button onclick="deleteCurrentDetailEmail()" class="p-2 rounded-full hover:bg-slate-100 text-slate-600 hover:text-red-600 transition" title="Hapus pesan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    <button onclick="replyCurrentEmail()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-medium text-slate-700 border border-slate-200 hover:bg-slate-50 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <span>Balas</span>
                    </button>
                </div>

                <!-- Detail Content Scrollable -->
                <div class="flex-1 overflow-y-auto p-6 md:px-10 select-text">
                    <div class="flex items-center gap-3 mb-6">
                        <h1 id="detailSubject" class="text-xl font-normal text-slate-900">(Tanpa Subjek)</h1>
                        <span id="detailFolderTag" class="px-2 py-0.5 text-[11px] font-medium rounded bg-slate-100 text-slate-600">Kotak Masuk</span>
                    </div>

                    <!-- Sender Header -->
                    <div class="flex items-start justify-between gap-4 pb-6 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div id="detailAvatar" class="w-10 h-10 rounded-full bg-[#eaf1fb] text-[#0b57d0] font-semibold text-sm flex items-center justify-center shrink-0">
                                A
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span id="detailFromName" class="text-sm font-semibold text-slate-900">Pengirim</span>
                                    <span id="detailFromAddress" class="text-xs text-slate-500">&lt;email@domain.com&gt;</span>
                                </div>
                                <div id="detailToInfo" class="text-xs text-slate-500 mt-0.5">kepada saya</div>
                            </div>
                        </div>
                        <div id="detailDate" class="text-xs text-slate-500 shrink-0"></div>
                    </div>

                    <!-- HTML Message Body -->
                    <div id="detailBody" class="py-6 text-sm text-slate-800 leading-relaxed overflow-x-auto"></div>

                    <!-- Attachments Section -->
                    <div id="detailAttachmentsWrapper" class="hidden pt-6 border-t border-slate-100">
                        <div class="text-xs font-medium text-slate-500 mb-3">Lampiran</div>
                        <div id="detailAttachmentsGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3"></div>
                    </div>

                    <!-- Bottom Reply Action -->
                    <div class="pt-6">
                        <button onclick="replyCurrentEmail()" class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-slate-700 border border-slate-300 hover:bg-slate-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                            <span>Balas</span>
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ===================================================================== -->
    <!-- 3. FLOATING GMAIL COMPOSE MODAL (POJOK KANAN BAWAH)                   -->
    <!-- ===================================================================== -->
    <div id="composeModal" class="hidden fixed bottom-0 right-6 w-full max-w-[520px] bg-white rounded-t-xl shadow-2xl border border-slate-200 z-50 flex flex-col">
        <!-- Compose Header -->
        <div class="bg-[#f2f6fc] px-4 py-2.5 rounded-t-xl flex items-center justify-between border-b border-slate-200/70">
            <span class="text-sm font-medium text-slate-800">Pesan Baru</span>
            <button onclick="closeComposeModal()" class="p-1 rounded hover:bg-slate-200/70 text-slate-600 transition" title="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Compose Form -->
        <form id="composeForm" onsubmit="submitSendEmail(event)" class="flex flex-col">
            <!-- Kepada + Autocomplete -->
            <div class="relative border-b border-slate-100 px-4 py-2 flex items-center gap-2">
                <label class="text-xs text-slate-500 w-12">Kepada</label>
                <input
                    type="email"
                    id="composeTo"
                    name="to"
                    required
                    autocomplete="off"
                    placeholder="nama@email.com atau cari pengguna..."
                    oninput="handleAutocompleteUser(this.value)"
                    class="flex-1 text-sm text-slate-800 outline-none bg-transparent"
                >
                <!-- Autocomplete Dropdown -->
                <div id="userAutocompleteList" class="hidden absolute left-0 right-0 top-full bg-white border border-slate-200 shadow-lg rounded-b-lg max-h-48 overflow-y-auto z-50 divide-y divide-slate-100"></div>
            </div>

            <!-- Subjek -->
            <div class="border-b border-slate-100 px-4 py-2 flex items-center gap-2">
                <input
                    type="text"
                    id="composeSubject"
                    name="subject"
                    required
                    placeholder="Subjek"
                    class="w-full text-sm text-slate-800 outline-none bg-transparent"
                >
            </div>

            <!-- Quill Rich Text Editor -->
            <div id="quillEditor"></div>

            <!-- Preview File Lampiran Terpilih -->
            <div id="composeAttachmentPreview" class="hidden px-4 py-2 bg-slate-50 border-t border-slate-100 flex flex-wrap gap-2"></div>

            <!-- Bottom Action Bar -->
            <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-white">
                <div class="flex items-center gap-2">
                    <button type="submit" id="sendSubmitBtn" class="bg-[#0b57d0] hover:bg-[#0842a0] text-white text-sm font-medium px-6 py-2 rounded-full transition flex items-center gap-2">
                        <span>Kirim</span>
                    </button>

                    <!-- Attachment File Trigger -->
                    <label class="p-2 rounded-full hover:bg-slate-100 text-slate-600 cursor-pointer transition" title="Lampirkan file">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <input type="file" id="composeAttachments" name="attachments[]" multiple class="hidden" onchange="renderSelectedAttachments(this)">
                    </label>
                </div>

                <button type="button" onclick="closeComposeModal(true)" class="p-2 rounded-full hover:bg-slate-100 text-slate-500 hover:text-red-600 transition" title="Buang draf">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Clean Minimalist Toast Notification -->
    <div id="toastBanner" class="hidden fixed bottom-5 left-6 bg-slate-900 text-white text-xs font-medium px-4 py-3 rounded-lg shadow-lg z-50 transition-all"></div>

    <!-- ===================================================================== -->
    <!-- 4. JAVASCRIPT LOGIC (FETCH, SHOW, SEND, STAR, DELETE, AUTOCOMPLETE)   -->
    <!-- ===================================================================== -->
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentFolder = 'inbox';
        let currentPage = 1;
        let currentSearch = '';
        let currentEmails = [];
        let selectedIds = new Set();
        let activeDetailEmail = null;
        let searchTimeout = null;
        let autocompleteTimeout = null;

        // Inisialisasi Quill Editor (Clean Toolbar)
        const quill = new Quill('#quillEditor', {
            theme: 'snow',
            placeholder: 'Tulis pesan Anda di sini...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });

        // Load awal
        document.addEventListener('DOMContentLoaded', () => {
            fetchEmails();
        });

        function showToast(msg, isError = false) {
            const toast = document.getElementById('toastBanner');
            toast.textContent = msg;
            toast.className = `fixed bottom-5 left-6 text-xs font-medium px-4 py-3 rounded-lg shadow-lg z-50 ${isError ? 'bg-red-600 text-white' : 'bg-slate-900 text-white'}`;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3500);
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        }

        function selectCategoryTab(btn) {
            document.querySelectorAll('.cat-tab').forEach(el => {
                el.classList.remove('text-[#0b57d0]');
                el.classList.add('text-slate-600');
                const ind = el.querySelector('.tab-indicator');
                if (ind) ind.remove();
            });
            btn.classList.remove('text-slate-600');
            btn.classList.add('text-[#0b57d0]');
            btn.insertAdjacentHTML('beforeend', '<span class="tab-indicator absolute bottom-0 left-3 right-3 h-[3px] bg-[#0b57d0] rounded-t-full"></span>');
        }

        function switchFolder(folder) {
            currentFolder = folder;
            currentPage = 1;
            selectedIds.clear();
            updateBulkToolbar();
            closeEmailDetail();

            // Update styling menu aktif
            document.querySelectorAll('.nav-folder').forEach(btn => {
                if (btn.dataset.folder === folder) {
                    btn.className = 'nav-folder w-full flex items-center justify-between pl-6 pr-4 py-2 rounded-r-full font-semibold bg-[#d3e3fd] text-[#001d35] transition';
                } else {
                    btn.className = 'nav-folder w-full flex items-center justify-between pl-6 pr-4 py-2 rounded-r-full text-slate-700 hover:bg-slate-200/60 transition';
                }
            });

            // Tab kategori hanya untuk inbox
            document.getElementById('categoryTabs').style.display = (folder === 'inbox') ? 'flex' : 'none';

            fetchEmails();
        }

        function handleSearchKeyup(e) {
            const val = e.target.value.trim();
            document.getElementById('clearSearchBtn').classList.toggle('hidden', val.length === 0);

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = val;
                currentPage = 1;
                closeEmailDetail();
                fetchEmails();
            }, 450);
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('clearSearchBtn').classList.add('hidden');
            currentSearch = '';
            currentPage = 1;
            fetchEmails();
        }

        function changePage(delta) {
            currentPage += delta;
            if (currentPage < 1) currentPage = 1;
            fetchEmails();
        }

        // =====================================================================
        // FETCH DAFTAR EMAIL
        // =====================================================================
        async function fetchEmails() {
            const container = document.getElementById('emailListContainer');
            selectedIds.clear();
            document.getElementById('selectAllCheckbox').checked = false;
            updateBulkToolbar();

            // Skeleton Loading (Next.js Style)
            container.innerHTML = Array(8).fill(`
                <div class="px-4 py-3 flex items-center gap-4 animate-pulse">
                    <div class="w-4 h-4 bg-slate-200 rounded"></div>
                    <div class="w-4 h-4 bg-slate-200 rounded-full"></div>
                    <div class="w-36 h-3.5 bg-slate-200 rounded"></div>
                    <div class="flex-1 h-3.5 bg-slate-100 rounded"></div>
                    <div class="w-12 h-3 bg-slate-200 rounded"></div>
                </div>
            `).join('');

            try {
                const url = `{{ route('admin.email.fetch') }}?folder=${encodeURIComponent(currentFolder)}&page=${currentPage}&search=${encodeURIComponent(currentSearch)}`;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.error || 'Gagal memuat daftar email.');
                }

                currentEmails = data.emails || [];

                if (currentFolder === 'inbox' && typeof data.unread_count !== 'undefined') {
                    document.getElementById('unreadBadge').textContent = data.unread_count > 0 ? data.unread_count : '';
                }

                // Update Pagination UI
                const pg = data.pagination || { current_page: 1, last_page: 1, has_more: false };
                document.getElementById('paginationInfo').textContent = `Halaman ${pg.current_page} dari ${pg.last_page}`;
                document.getElementById('prevPageBtn').disabled = pg.current_page <= 1;
                document.getElementById('nextPageBtn').disabled = !pg.has_more;

                renderEmailList(currentEmails);

            } catch (err) {
                container.innerHTML = `
                    <div class="p-12 text-center text-sm text-red-600">
                        ${err.message}
                    </div>
                `;
            }
        }

        function renderEmailList(emails) {
            const container = document.getElementById('emailListContainer');

            if (!emails.length) {
                container.innerHTML = `
                    <div class="py-20 text-center">
                        <p class="text-sm text-slate-500">Tidak ada percakapan di folder ini.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = emails.map(email => {
                const isUnread = !email.read_at;
                const rowBg = isUnread ? 'bg-white font-semibold text-slate-900' : 'bg-[#f2f6fc]/50 font-normal text-slate-700';
                const displayName = (currentFolder === 'sent' && email.to_address)
                    ? `Kepada: ${escapeHtml(email.to_address)}`
                    : escapeHtml(email.from_name || email.from_address || 'Unknown');

                const plainSnippet = stripHtml(email.body || '').substring(0, 90);
                const formattedDate = formatShortDate(email.created_at);

                return `
                    <div onclick="openEmailDetail('${email.id}')" class="group px-4 py-2.5 flex items-center gap-3.5 text-sm cursor-pointer transition-shadow hover:shadow-[inset_1px_0_0_#dadce0,inset_-1px_0_0_#dadce0,0_1px_2px_0_rgba(60,64,67,.25)] hover:z-10 ${rowBg}">
                        <input
                            type="checkbox"
                            value="${email.id}"
                            onclick="event.stopPropagation(); toggleSelectRow('${email.id}', this.checked)"
                            class="row-checkbox w-4 h-4 rounded border-slate-300 text-[#0b57d0] focus:ring-0 cursor-pointer shrink-0"
                        >

                        <button
                            type="button"
                            onclick="event.stopPropagation(); toggleStar('${email.id}', ${!email.is_starred})"
                            class="p-1 rounded-full hover:bg-slate-200/70 transition shrink-0"
                            title="${email.is_starred ? 'Berbintang' : 'Beri bintang'}"
                        >
                            ${email.is_starred
                                ? `<svg class="w-4 h-4 text-[#f4b400] fill-[#f4b400]" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`
                                : `<svg class="w-4 h-4 text-slate-400 hover:text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>`
                            }
                        </button>

                        <div class="w-44 sm:w-48 truncate shrink-0">
                            ${displayName}
                        </div>

                        <div class="flex-1 truncate">
                            <span class="${isUnread ? 'text-slate-900 font-semibold' : 'text-slate-800'}">${escapeHtml(email.subject || '(Tanpa Subjek)')}</span>
                            <span class="text-slate-500 font-normal"> — ${escapeHtml(plainSnippet)}</span>
                        </div>

                        <div class="w-20 text-right text-xs shrink-0 ${isUnread ? 'text-slate-900 font-semibold' : 'text-slate-500'}">
                            ${formattedDate}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // =====================================================================
        // BACA DETAIL EMAIL (SHOW)
        // =====================================================================
        async function openEmailDetail(id) {
            document.getElementById('emailListView').classList.add('hidden');
            document.getElementById('emailDetailView').classList.remove('hidden');

            document.getElementById('detailSubject').textContent = 'Memuat pesan...';
            document.getElementById('detailBody').innerHTML = '<div class="py-10 text-center text-xs text-slate-400">Mengambil isi pesan dari server...</div>';
            document.getElementById('detailAttachmentsWrapper').classList.add('hidden');

            try {
                const res = await fetch(`{{ url('admin/email') }}/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const email = await res.json();

                if (!res.ok) throw new Error(email.error || 'Gagal membuka email.');

                activeDetailEmail = email;
                document.getElementById('detailSubject').textContent = email.subject || '(Tanpa Subjek)';
                document.getElementById('detailFolderTag').textContent = currentFolder.toUpperCase();
                document.getElementById('detailFromName').textContent = email.from_name || 'Unknown';
                document.getElementById('detailFromAddress').textContent = `<${email.from_address || ''}>`;
                document.getElementById('detailAvatar').textContent = (email.from_name || 'A').charAt(0).toUpperCase();
                document.getElementById('detailToInfo').textContent = email.to_address ? `kepada ${email.to_address}` : 'kepada saya';
                document.getElementById('detailDate').textContent = email.created_at || '';
                document.getElementById('detailBody').innerHTML = email.body || '';

                // Render Lampiran jika ada
                const attWrapper = document.getElementById('detailAttachmentsWrapper');
                const attGrid = document.getElementById('detailAttachmentsGrid');
                if (email.attachments && email.attachments.length > 0) {
                    attWrapper.classList.remove('hidden');
                    attGrid.innerHTML = email.attachments.map(att => `
                        <a href="${att.url}" target="_blank" class="group border border-slate-200 rounded-xl overflow-hidden bg-slate-50 hover:border-[#0b57d0] transition flex flex-col">
                            ${att.thumbnail
                                ? `<img src="${att.thumbnail}" alt="${escapeHtml(att.name)}" class="h-32 w-full object-cover border-b border-slate-200">`
                                : `<div class="h-32 flex items-center justify-center bg-slate-100 text-slate-400 text-xs font-medium">DOKUMEN</div>`
                            }
                            <div class="px-3 py-2 text-xs font-medium text-slate-700 truncate group-hover:text-[#0b57d0]">
                                ${escapeHtml(att.name)}
                            </div>
                        </a>
                    `).join('');
                } else {
                    attWrapper.classList.add('hidden');
                }

            } catch (err) {
                document.getElementById('detailBody').innerHTML = `<div class="text-sm text-red-600">${err.message}</div>`;
            }
        }

        function closeEmailDetail() {
            document.getElementById('emailDetailView').classList.add('hidden');
            document.getElementById('emailListView').classList.remove('hidden');
            activeDetailEmail = null;
        }

        function replyCurrentEmail() {
            if (!activeDetailEmail) return;
            openComposeModal();
            document.getElementById('composeTo').value = activeDetailEmail.from_address || '';
            const subj = activeDetailEmail.subject || '';
            document.getElementById('composeSubject').value = subj.startsWith('Re:') ? subj : `Re: ${subj}`;
            quill.focus();
        }

        async function deleteCurrentDetailEmail() {
            if (!activeDetailEmail) return;
            if (!confirm('Hapus email ini?')) return;

            await executeBulkDelete([activeDetailEmail.id]);
            closeEmailDetail();
            fetchEmails();
        }

        // =====================================================================
        // BINTANG & HAPUS MASAL
        // =====================================================================
        async function toggleStar(id, nextState) {
            try {
                const res = await fetch(`{{ url('admin/email') }}/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ is_starred: nextState })
                });
                if (res.ok) {
                    const target = currentEmails.find(e => String(e.id) === String(id));
                    if (target) target.is_starred = nextState;
                    renderEmailList(currentEmails);
                }
            } catch (e) {
                showToast('Gagal memperbarui status bintang.', true);
            }
        }

        function toggleSelectRow(id, checked) {
            if (checked) selectedIds.add(String(id));
            else selectedIds.delete(String(id));
            updateBulkToolbar();
        }

        function toggleSelectAll(master) {
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = master.checked;
                if (master.checked) selectedIds.add(String(cb.value));
                else selectedIds.delete(String(cb.value));
            });
            updateBulkToolbar();
        }

        function updateBulkToolbar() {
            const count = selectedIds.size;
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('bulkDeleteBtn').classList.toggle('hidden', count === 0);
        }

        async function deleteSelectedEmails() {
            if (selectedIds.size === 0) return;
            if (!confirm(`Hapus ${selectedIds.size} email yang dipilih?`)) return;

            await executeBulkDelete(Array.from(selectedIds));
            fetchEmails();
        }

        async function executeBulkDelete(ids) {
            try {
                const res = await fetch(`{{ route('admin.email.destroy') }}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids, folder: currentFolder })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || 'Email berhasil dihapus.');
                } else {
                    showToast(data.error || 'Gagal menghapus email.', true);
                }
            } catch (e) {
                showToast('Terjadi kesalahan saat menghapus email.', true);
            }
        }

        // =====================================================================
        // COMPOSE MODAL & AUTOCOMPLETE PENGGUNA
        // =====================================================================
        function openComposeModal() {
            document.getElementById('composeModal').classList.remove('hidden');
            document.getElementById('composeTo').focus();
        }

        function closeComposeModal(reset = false) {
            document.getElementById('composeModal').classList.add('hidden');
            document.getElementById('userAutocompleteList').classList.add('hidden');
            if (reset) {
                document.getElementById('composeForm').reset();
                quill.setContents([]);
                document.getElementById('composeAttachmentPreview').innerHTML = '';
                document.getElementById('composeAttachmentPreview').classList.add('hidden');
            }
        }

        function renderSelectedAttachments(input) {
            const preview = document.getElementById('composeAttachmentPreview');
            if (!input.files || input.files.length === 0) {
                preview.classList.add('hidden');
                preview.innerHTML = '';
                return;
            }
            preview.classList.remove('hidden');
            preview.innerHTML = Array.from(input.files).map(f => `
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-white border border-slate-200 text-xs text-slate-700">
                    <span>${escapeHtml(f.name)}</span>
                    <span class="text-slate-400">(${Math.round(f.size / 1024)} KB)</span>
                </span>
            `).join('');
        }

        function handleAutocompleteUser(query) {
            const dropdown = document.getElementById('userAutocompleteList');
            clearTimeout(autocompleteTimeout);

            if (query.trim().length < 2) {
                dropdown.classList.add('hidden');
                return;
            }

            autocompleteTimeout = setTimeout(async () => {
                try {
                    const res = await fetch(`{{ route('admin.email.searchUsers') }}?q=${encodeURIComponent(query)}`);
                    const users = await res.json();

                    if (!users.length) {
                        dropdown.classList.add('hidden');
                        return;
                    }

                    dropdown.innerHTML = users.map(u => `
                        <button type="button" onclick="selectAutocompleteEmail('${escapeHtml(u.email)}')" class="w-full text-left px-4 py-2 hover:bg-slate-50 flex items-center justify-between text-xs">
                            <div>
                                <div class="font-medium text-slate-800">${escapeHtml(u.nama_lengkap)}</div>
                                <div class="text-slate-500">${escapeHtml(u.email)}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px]">${escapeHtml(u.role || 'User')}</span>
                        </button>
                    `).join('');
                    dropdown.classList.remove('hidden');
                } catch (e) {
                    dropdown.classList.add('hidden');
                }
            }, 300);
        }

        function selectAutocompleteEmail(email) {
            document.getElementById('composeTo').value = email;
            document.getElementById('userAutocompleteList').classList.add('hidden');
            document.getElementById('composeSubject').focus();
        }

        async function submitSendEmail(e) {
            e.preventDefault();
            const btn = document.getElementById('sendSubmitBtn');
            const htmlBody = quill.root.innerHTML;

            if (quill.getText().trim().length === 0) {
                showToast('Isi pesan email tidak boleh kosong.', true);
                return;
            }

            const formData = new FormData(document.getElementById('composeForm'));
            formData.set('body', htmlBody);

            btn.disabled = true;
            btn.innerHTML = '<span>Mengirim...</span>';

            try {
                const res = await fetch(`{{ route('admin.email.send') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    showToast(data.message || 'Email berhasil dikirim!');
                    closeComposeModal(true);
                    if (currentFolder === 'sent') fetchEmails();
                } else {
                    showToast(data.message || 'Gagal mengirim email.', true);
                }
            } catch (err) {
                showToast('Terjadi kesalahan jaringan saat mengirim.', true);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Kirim</span>';
            }
        }

        // =====================================================================
        // UTILITIES
        // =====================================================================
        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function stripHtml(html) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        }

        function formatShortDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return dateStr;

            const now = new Date();
            const isToday = d.toDateString() === now.toDateString();
            if (isToday) {
                return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        }
    </script>
</body>
</html>
