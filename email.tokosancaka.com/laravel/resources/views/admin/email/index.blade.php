<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Sancaka Mail') }} — {{ Auth::user()->email ?? config('mail.from.address', 'admin@tokosancaka.com') }}</title>

    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link rel="shortcut icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">

    <!-- Inter Font -->
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
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Hide Scrollbar for Mobile Horizontal Tabs */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Gmail-style Quill Customization */
        .ql-toolbar.ql-snow {
            border: none !important;
            border-top: 1px solid #f1f5f9 !important;
            padding: 8px 12px !important;
            background: #ffffff;
            flex-shrink: 0;
        }
        .ql-container.ql-snow {
            border: none !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 14px !important;
            flex: 1 1 auto;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .ql-editor {
            flex: 1 1 auto;
            min-height: 180px;
            padding: 12px 16px !important;
            color: #1e293b;
        }
        @media (min-width: 640px) {
            .ql-editor {
                min-height: 210px;
                max-height: 320px;
            }
        }
    </style>
</head>
<body class="bg-[#f6f8fc] text-slate-800 antialiased h-screen h-dvh flex flex-col overflow-hidden select-none">

    @php
        $currentUserName = Auth::user()->nama_lengkap ?? Auth::user()->name ?? 'Admin Sancaka';
        $currentUserEmail = Auth::user()->email ?? config('mail.from.address', 'admin@tokosancaka.com');
        $mailServerHost = config('imap.accounts.default.host') ?: config('mail.mailers.smtp.host', 'server4.hostdata.id');
        $appName = config('app.name', 'Sancaka Mail');
    @endphp

    <!-- ===================================================================== -->
    <!-- 1. TOP HEADER BAR (RESPONSIVE HP, TABLET & PC)                        -->
    <!-- ===================================================================== -->
    <header class="h-14 sm:h-16 px-2.5 sm:px-4 flex items-center justify-between gap-2 sm:gap-4 shrink-0 bg-[#f6f8fc] z-20">
        <!-- Kiri: Hamburger & Brand Logo -->
        <div class="flex items-center gap-1.5 sm:gap-3 lg:w-60 shrink-0">
            <button
                type="button"
                onclick="toggleSidebar()"
                class="p-2 sm:p-2.5 rounded-full hover:bg-slate-200/70 active:bg-slate-200 text-slate-600 transition"
                title="Menu navigasi"
                aria-label="Buka menu navigasi"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a href="{{ route('admin.email.index') }}" class="flex items-center gap-2 focus:outline-none">
                <img
                    src="https://tokosancaka.com/storage/uploads/sancaka.png"
                    alt="{{ $appName }}"
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg object-contain shrink-0"
                >
                <span class="hidden sm:inline text-[18px] lg:text-[20px] font-semibold text-slate-800 tracking-tight">
                    Mail
                </span>
            </a>
        </div>

        <!-- Tengah: Pill Search Bar Dinamis -->
        <div class="flex-1 max-w-2xl min-w-0">
            <div class="relative flex items-center bg-[#eaf1fb] focus-within:bg-white focus-within:shadow-md focus-within:ring-1 focus-within:ring-slate-200 rounded-full px-3 sm:px-4 h-10 sm:h-11 transition-all">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/>
                </svg>
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Telusuri di Kotak Masuk..."
                    class="w-full min-w-0 bg-transparent border-none outline-none px-2.5 sm:px-3 text-xs sm:text-sm text-slate-800 placeholder-slate-500"
                    onkeyup="handleSearchKeyup(event)"
                >
                <button
                    type="button"
                    id="clearSearchBtn"
                    onclick="clearSearch()"
                    class="hidden p-1 rounded-full hover:bg-slate-200 text-slate-500 shrink-0"
                    title="Bersihkan pencarian"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Kanan: Pintasan, Info Akun & Dropdown Profile -->
        <div class="relative flex items-center gap-1.5 sm:gap-2.5 shrink-0" id="accountMenuWrapper">
            <a
                href="https://tokosancaka.com"
                target="_blank"
                rel="noopener noreferrer"
                class="hidden xl:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-slate-600 hover:bg-slate-200/70 border border-slate-200/80 bg-white transition"
            >
                <span>tokosancaka.com</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>

            <!-- Trigger Account Popover -->
            <button
                type="button"
                onclick="toggleAccountMenu()"
                class="flex items-center gap-2 p-1 pr-1.5 sm:pr-2.5 rounded-full hover:bg-slate-200/70 transition focus:outline-none"
                title="Info Akun & Keluar"
            >
                <div class="text-right hidden md:block">
                    <div class="text-xs font-semibold text-slate-800 max-w-[140px] truncate">{{ $currentUserName }}</div>
                    <div class="text-[11px] text-slate-500 max-w-[160px] truncate">{{ $currentUserEmail }}</div>
                </div>
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[#0b57d0] text-white flex items-center justify-center text-xs font-bold shadow-sm">
                    {{ strtoupper(substr($currentUserName, 0, 1)) }}
                </div>
            </button>

            <!-- Dropdown Account Popover (Responsif untuk HP, Tablet & PC) -->
            <div
                id="accountDropdown"
                class="hidden absolute right-0 top-12 w-72 sm:w-80 bg-white rounded-2xl shadow-xl border border-slate-200/90 p-4 z-50 text-left"
            >
                <div class="flex items-center gap-3 pb-3.5 border-b border-slate-100">
                    <div class="w-11 h-11 rounded-full bg-[#0b57d0] text-white flex items-center justify-center text-base font-bold shrink-0">
                        {{ strtoupper(substr($currentUserName, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $currentUserName }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $currentUserEmail }}</p>
                        <span class="inline-flex items-center gap-1.5 mt-1 text-[10px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                            <span id="popoverStatusDot" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span id="popoverStatusText">Terhubung • {{ $mailServerHost }}</span>
                        </span>
                    </div>
                </div>

                <div class="py-2.5 space-y-1 text-xs">
                    <a href="{{ url('/') }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 transition">
                        <span>Halaman Utama Sancaka Mail</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="https://tokosancaka.com/kirim" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 transition">
                        <span>Kirim Paket Multi-Ekspedisi</span>
                        <span class="text-slate-400">&#8599;</span>
                    </a>
                    <a href="https://tokosancaka.com/etalase" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 transition">
                        <span>Marketplace Etalase Sancaka</span>
                        <span class="text-slate-400">&#8599;</span>
                    </a>
                </div>

                <div class="pt-2.5 border-t border-slate-100">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-red-600 bg-red-50/70 hover:bg-red-100/80 transition cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Keluar dari Akun</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 2. MAIN WORKSPACE (RESPONSIVE DRAWER SIDEBAR + CONTENT CARD)          -->
    <!-- ===================================================================== -->
    <div class="relative flex-1 flex overflow-hidden sm:pr-4 sm:pb-4">

        <!-- Backdrop Gelap untuk Mobile & Tablet Drawer -->
        <div
            id="sidebarBackdrop"
            onclick="closeMobileSidebar()"
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-[1px] z-30 hidden lg:hidden transition-opacity"
        ></div>

        <!-- LEFT SIDEBAR (OFF-CANVAS DI HP/TABLET, STATIC DI PC) -->
        <aside
            id="sidebar"
            class="fixed inset-y-0 left-0 z-40 w-72 bg-[#f6f8fc] shadow-2xl transform -translate-x-full transition-transform duration-200 ease-out lg:static lg:translate-x-0 lg:w-64 lg:shadow-none shrink-0 flex flex-col justify-between pr-3 pt-3 lg:pt-0 overflow-y-auto"
        >
            <div>
                <!-- Header Mobile di dalam Drawer -->
                <div class="flex items-center justify-between px-4 pb-3 mb-2 border-b border-slate-200/80 lg:hidden">
                    <div class="flex items-center gap-2.5">
                        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="w-7 h-7 object-contain">
                        <span class="font-semibold text-base text-slate-800">{{ $appName }}</span>
                    </div>
                    <button type="button" onclick="closeMobileSidebar()" class="p-1.5 rounded-full hover:bg-slate-200 text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Tombol Tulis -->
                <div class="px-3 mb-3 sm:mb-4">
                    <button
                        type="button"
                        onclick="openComposeModal(); closeMobileSidebar();"
                        class="w-full sm:w-auto inline-flex items-center justify-center sm:justify-start gap-3 bg-[#c2e7ff] hover:shadow-md hover:bg-[#b5dffc] active:scale-[0.99] text-[#001d35] font-semibold text-sm px-6 py-3.5 sm:py-4 rounded-2xl transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        <span>Tulis</span>
                    </button>
                </div>

                <!-- Navigasi Folder (Dirender Dinamis oleh JS) -->
                <nav id="folderNavContainer" class="space-y-0.5 text-sm"></nav>

                <!-- Label Section Dinamis (Bisa Tambah, Filter & Hapus Label) -->
                <div class="mt-5 pt-4 border-t border-slate-200/70 pl-6 pr-3">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mb-2">
                        <span>Label</span>
                        <button
                            type="button"
                            onclick="toggleAddLabelForm()"
                            class="w-6 h-6 rounded-full hover:bg-slate-200/80 text-slate-600 flex items-center justify-center text-base leading-none transition"
                            title="Buat label baru"
                        >
                            +
                        </button>
                    </div>

                    <!-- Form Tambah Label Baru (Tersembunyi secara default) -->
                    <form id="addLabelForm" onsubmit="createCustomLabel(event)" class="hidden mb-2.5 flex items-center gap-1.5">
                        <input
                            type="text"
                            id="newLabelInput"
                            placeholder="Nama label..."
                            maxlength="30"
                            class="w-full min-w-0 px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-[#0b57d0]"
                        >
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-[#0b57d0] text-white text-xs font-medium shrink-0">
                            Simpan
                        </button>
                    </form>

                    <!-- Daftar Label Dinamis -->
                    <div id="dynamicLabelList" class="space-y-1 text-xs text-slate-600"></div>
                </div>
            </div>

            <!-- Footer Status Server Dinamis -->
            <div class="pl-6 pr-4 py-3 text-[11px] text-slate-400 flex items-center gap-2 border-t border-slate-200/50 lg:border-none mt-4">
                <span id="serverStatusDot" class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span class="truncate" title="IMAP/SMTP • {{ $mailServerHost }}">IMAP/SMTP • {{ $mailServerHost }}</span>
            </div>
        </aside>

        <!-- MAIN WHITE ROUNDED CONTAINER -->
        <main class="flex-1 min-w-0 bg-white sm:rounded-2xl border-t sm:border border-slate-200/70 flex flex-col overflow-hidden">

            <!-- ============================================================= -->
            <!-- VIEW A: LIST EMAIL VIEW                                       -->
            <!-- ============================================================= -->
            <div id="emailListView" class="flex-1 flex flex-col overflow-hidden">

                <!-- Action Toolbar -->
                <div class="h-12 px-2.5 sm:px-4 border-b border-slate-100 flex items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                        <input
                            type="checkbox"
                            id="selectAllCheckbox"
                            onchange="toggleSelectAll(this)"
                            class="w-4 h-4 rounded border-slate-300 text-[#0b57d0] focus:ring-0 cursor-pointer shrink-0"
                            title="Pilih semua"
                        >

                        <!-- Refresh Button -->
                        <button
                            type="button"
                            id="refreshBtn"
                            onclick="fetchEmails()"
                            class="p-2 rounded-full hover:bg-slate-100 text-slate-600 transition shrink-0"
                            title="Muat ulang kotak surat"
                        >
                            <svg id="refreshIcon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>

                        <!-- Bulk Delete Button -->
                        <button
                            type="button"
                            id="bulkDeleteBtn"
                            onclick="deleteSelectedEmails()"
                            class="hidden inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full text-xs font-medium text-red-600 hover:bg-red-50 transition shrink-0"
                            title="Hapus email terpilih"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus (<span id="selectedCount">0</span>)</span>
                        </button>

                        <!-- Indikator Folder / Filter Aktif di Mobile & Tablet -->
                        <span id="activeContextBadge" class="lg:hidden truncate text-xs font-semibold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-full">
                            Kotak Masuk
                        </span>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center gap-1.5 sm:gap-3 text-[11px] sm:text-xs text-slate-500 shrink-0">
                        <span id="paginationInfo" class="whitespace-nowrap">Memuat...</span>
                        <div class="flex items-center gap-0.5 sm:gap-1">
                            <button
                                type="button"
                                id="prevPageBtn"
                                onclick="changePage(-1)"
                                disabled
                                class="p-1.5 rounded-full hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none text-slate-600 transition"
                                title="Halaman sebelumnya"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <button
                                type="button"
                                id="nextPageBtn"
                                onclick="changePage(1)"
                                disabled
                                class="p-1.5 rounded-full hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none text-slate-600 transition"
                                title="Halaman berikutnya"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Gmail Category Tabs (Scrollable di HP/Tablet, Dirender Dinamis) -->
                <div id="categoryTabs" class="flex border-b border-slate-100 px-1 sm:px-2 shrink-0 overflow-x-auto no-scrollbar"></div>

                <!-- Email Rows Container -->
                <div id="emailListContainer" class="flex-1 overflow-y-auto divide-y divide-slate-100"></div>
            </div>

            <!-- ============================================================= -->
            <!-- VIEW B: READING PANE (DETAIL EMAIL RESPONSIF)                 -->
            <!-- ============================================================= -->
            <div id="emailDetailView" class="hidden flex-1 flex flex-col overflow-hidden">
                <!-- Detail Toolbar -->
                <div class="h-12 px-2.5 sm:px-4 border-b border-slate-100 flex items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center gap-1 sm:gap-2">
                        <button
                            type="button"
                            onclick="closeEmailDetail()"
                            class="p-2 rounded-full hover:bg-slate-100 text-slate-600 transition"
                            title="Kembali ke daftar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </button>
                        <button
                            type="button"
                            onclick="toggleDetailStar()"
                            id="detailStarBtn"
                            class="p-2 rounded-full hover:bg-slate-100 text-slate-600 transition"
                            title="Beri bintang"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </button>
                        <button
                            type="button"
                            onclick="deleteCurrentDetailEmail()"
                            class="p-2 rounded-full hover:bg-slate-100 text-slate-600 hover:text-red-600 transition"
                            title="Hapus pesan"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>

                    <button
                        type="button"
                        onclick="replyCurrentEmail()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-medium text-slate-700 border border-slate-200 hover:bg-slate-50 transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <span>Balas</span>
                    </button>
                </div>

                <!-- Detail Content Scrollable -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 md:px-10 select-text">
                    <div class="flex items-start sm:items-center gap-2.5 flex-wrap mb-5">
                        <h1 id="detailSubject" class="text-lg sm:text-xl font-medium text-slate-900 break-words">(Tanpa Subjek)</h1>
                        <span id="detailFolderTag" class="px-2 py-0.5 text-[11px] font-medium rounded bg-slate-100 text-slate-600">Kotak Masuk</span>
                    </div>

                    <!-- Sender Header -->
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 pb-5 border-b border-slate-100">
                        <div class="flex items-start gap-3 min-w-0">
                            <div id="detailAvatar" class="w-10 h-10 rounded-full bg-[#eaf1fb] text-[#0b57d0] font-semibold text-sm flex items-center justify-center shrink-0">
                                A
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span id="detailFromName" class="text-sm font-semibold text-slate-900 break-words">Pengirim</span>
                                    <span id="detailFromAddress" class="text-xs text-slate-500 break-all">&lt;email@domain.com&gt;</span>
                                </div>
                                <div id="detailToInfo" class="text-xs text-slate-500 mt-0.5 break-all">kepada saya</div>
                            </div>
                        </div>
                        <div id="detailDate" class="text-[11px] sm:text-xs text-slate-500 shrink-0 pl-13 sm:pl-0"></div>
                    </div>

                    <!-- HTML Message Body -->
                    <div id="detailBody" class="py-5 sm:py-6 text-sm text-slate-800 leading-relaxed overflow-x-auto break-words"></div>

                    <!-- Attachments Section -->
                    <div id="detailAttachmentsWrapper" class="hidden pt-5 border-t border-slate-100">
                        <div class="text-xs font-medium text-slate-500 mb-3">Lampiran (<span id="detailAttachmentCount">0</span>)</div>
                        <div id="detailAttachmentsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"></div>
                    </div>

                    <!-- Bottom Reply Action -->
                    <div class="pt-6 pb-8">
                        <button
                            type="button"
                            onclick="replyCurrentEmail()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium text-slate-700 border border-slate-300 hover:bg-slate-50 transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                            <span>Balas Pesan</span>
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Floating Action Button (FAB) Tulis Khusus HP & Tablet -->
    <button
        type="button"
        id="mobileComposeFab"
        onclick="openComposeModal()"
        class="lg:hidden fixed bottom-5 right-5 z-20 inline-flex items-center gap-2.5 bg-[#c2e7ff] hover:bg-[#b5dffc] active:scale-95 text-[#001d35] font-semibold text-sm px-5 py-3.5 rounded-2xl shadow-lg shadow-slate-900/15 transition"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
        </svg>
        <span>Tulis</span>
    </button>

    <!-- ===================================================================== -->
    <!-- 3. RESPONSIVE COMPOSE MODAL (FULL-SCREEN DI HP, FLOATING DI PC)       -->
    <!-- ===================================================================== -->
    <div
        id="composeModal"
        class="hidden fixed inset-0 sm:inset-auto sm:bottom-0 sm:right-6 w-full sm:w-[520px] sm:max-w-[calc(100vw-3rem)] h-screen h-dvh sm:h-auto sm:max-h-[85vh] bg-white sm:rounded-t-xl shadow-2xl border-0 sm:border sm:border-slate-200 z-50 flex flex-col"
    >
        <!-- Compose Header -->
        <div class="bg-[#f2f6fc] px-4 py-3 sm:py-2.5 sm:rounded-t-xl flex items-center justify-between border-b border-slate-200/70 shrink-0">
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeComposeModal()" class="sm:hidden p-1 -ml-1 rounded-full hover:bg-slate-200/70 text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </button>
                <span class="text-sm font-semibold text-slate-800">Pesan Baru</span>
            </div>
            <button type="button" onclick="closeComposeModal()" class="hidden sm:inline-flex p-1 rounded hover:bg-slate-200/70 text-slate-600 transition" title="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Compose Form -->
        <form id="composeForm" onsubmit="submitSendEmail(event)" class="flex-1 flex flex-col min-h-0">
            <!-- Dari (Dinamis) -->
            <div class="border-b border-slate-100 px-4 py-2 flex items-center gap-2 text-xs text-slate-500 shrink-0">
                <span class="w-12 shrink-0">Dari</span>
                <span class="truncate text-slate-700 font-medium">{{ $currentUserEmail }}</span>
            </div>

            <!-- Kepada + Autocomplete -->
            <div class="relative border-b border-slate-100 px-4 py-2.5 sm:py-2 flex items-center gap-2 shrink-0">
                <label for="composeTo" class="text-xs text-slate-500 w-12 shrink-0">Kepada</label>
                <input
                    type="email"
                    id="composeTo"
                    name="to"
                    required
                    autocomplete="off"
                    placeholder="nama@email.com atau cari pengguna..."
                    oninput="handleAutocompleteUser(this.value)"
                    class="flex-1 min-w-0 text-sm text-slate-800 outline-none bg-transparent"
                >
                <!-- Autocomplete Dropdown -->
                <div id="userAutocompleteList" class="hidden absolute left-0 right-0 top-full bg-white border border-slate-200 shadow-lg rounded-b-lg max-h-48 overflow-y-auto z-50 divide-y divide-slate-100"></div>
            </div>

            <!-- Subjek -->
            <div class="border-b border-slate-100 px-4 py-2.5 sm:py-2 flex items-center gap-2 shrink-0">
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
            <div id="quillEditor" class="flex-1 min-h-0 flex flex-col"></div>

            <!-- Preview File Lampiran Terpilih -->
            <div id="composeAttachmentPreview" class="hidden px-4 py-2 bg-slate-50 border-t border-slate-100 flex flex-wrap gap-2 max-h-24 overflow-y-auto shrink-0"></div>

            <!-- Bottom Action Bar -->
            <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-white shrink-0">
                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        id="sendSubmitBtn"
                        class="bg-[#0b57d0] hover:bg-[#0842a0] text-white text-sm font-semibold px-6 py-2.5 sm:py-2 rounded-full transition flex items-center gap-2"
                    >
                        <span>Kirim</span>
                    </button>

                    <!-- Attachment File Trigger -->
                    <label class="p-2 rounded-full hover:bg-slate-100 text-slate-600 cursor-pointer transition" title="Lampirkan file">
                        <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <input type="file" id="composeAttachments" name="attachments[]" multiple class="hidden" onchange="renderSelectedAttachments(this)">
                    </label>
                </div>

                <button
                    type="button"
                    onclick="closeComposeModal(true)"
                    class="p-2 rounded-full hover:bg-slate-100 text-slate-500 hover:text-red-600 transition"
                    title="Buang draf"
                >
                    <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Clean Minimalist Toast Notification -->
    <div id="toastBanner" class="hidden fixed bottom-5 left-4 right-4 sm:left-6 sm:right-auto bg-slate-900 text-white text-xs font-medium px-4 py-3 rounded-xl shadow-lg z-50 transition-all text-center sm:text-left"></div>

    <!-- ===================================================================== -->
    <!-- 4. JAVASCRIPT DINAMIS (FETCH, FILTER, LABELS, RESPONSIVE STATE)       -->
    <!-- ===================================================================== -->
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const USER_STORAGE_KEY = 'sancaka_mail_labels_{{ md5($currentUserEmail) }}';

        // Konfigurasi Folder Dinamis
        const FOLDERS = [
            {
                key: 'inbox',
                label: 'Kotak Masuk',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>'
            },
            {
                key: 'starred',
                label: 'Berbintang',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>'
            },
            {
                key: 'snoozed',
                label: 'Ditunda',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            },
            {
                key: 'sent',
                label: 'Terkirim',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>'
            },
            {
                key: 'draft',
                label: 'Draf',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'
            },
            {
                key: 'trash',
                label: 'Sampah',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>'
            }
        ];

        // Konfigurasi Kategori Tab Dinamis
        const CATEGORIES = [
            {
                key: 'primary',
                label: 'Utama',
                keywords: [],
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>'
            },
            {
                key: 'promotions',
                label: 'Promosi',
                keywords: ['promo', 'diskon', 'penawaran', 'voucher', 'cashback', 'newsletter', 'sale'],
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>'
            },
            {
                key: 'social',
                label: 'Sosial',
                keywords: ['facebook', 'instagram', 'linkedin', 'twitter', 'youtube', 'tiktok', 'undangan', 'komunitas'],
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'
            },
            {
                key: 'updates',
                label: 'Info Terbaru',
                keywords: ['invoice', 'resi', 'tagihan', 'pembayaran', 'settlement', 'verifikasi', 'otp', 'notifikasi', 'laporan'],
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            }
        ];

        const LABEL_COLORS = ['#0b57d0', '#059669', '#d97706', '#7c3aed', '#db2777', '#0891b2'];

        let currentFolder = 'inbox';
        let currentCategory = 'primary';
        let currentLabelFilter = '';
        let currentPage = 1;
        let currentSearch = '';
        let currentEmails = [];
        let folderCounts = {};
        let selectedIds = new Set();
        let activeDetailEmail = null;
        let searchTimeout = null;
        let autocompleteTimeout = null;

        // Inisialisasi Quill Editor
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

        document.addEventListener('DOMContentLoaded', () => {
            renderSidebarFolders();
            renderCategoryTabs();
            renderCustomLabels();
            fetchEmails();
        });

        // Tutup dropdown akun jika klik di luar area
        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById('accountMenuWrapper');
            const dropdown = document.getElementById('accountDropdown');
            if (wrapper && dropdown && !wrapper.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        function toggleAccountMenu() {
            document.getElementById('accountDropdown').classList.toggle('hidden');
        }

        function showToast(msg, isError = false) {
            const toast = document.getElementById('toastBanner');
            toast.textContent = msg;
            toast.className = `fixed bottom-5 left-4 right-4 sm:left-6 sm:right-auto text-xs font-medium px-4 py-3 rounded-xl shadow-lg z-50 text-center sm:text-left ${isError ? 'bg-red-600 text-white' : 'bg-slate-900 text-white'}`;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3500);
        }

        // =====================================================================
        // SIDEBAR RESPONSIVE (DRAWER DI HP/TABLET, COLLAPSE DI PC)
        // =====================================================================
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            if (window.innerWidth < 1024) {
                const isClosed = sidebar.classList.contains('-translate-x-full');
                if (isClosed) {
                    sidebar.classList.remove('-translate-x-full');
                    backdrop.classList.remove('hidden');
                } else {
                    closeMobileSidebar();
                }
            } else {
                sidebar.classList.toggle('lg:hidden');
            }
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
        }

        // =====================================================================
        // RENDER FOLDER NAVIGASI DINAMIS
        // =====================================================================
        function renderSidebarFolders() {
            const nav = document.getElementById('folderNavContainer');
            nav.innerHTML = FOLDERS.map(f => {
                const isActive = (currentFolder === f.key && !currentLabelFilter);
                const countVal = folderCounts[f.key] || 0;
                const activeClasses = isActive
                    ? 'font-semibold bg-[#d3e3fd] text-[#001d35]'
                    : 'text-slate-700 hover:bg-slate-200/60 font-medium';

                return `
                    <button
                        type="button"
                        onclick="switchFolder('${f.key}')"
                        data-folder="${f.key}"
                        class="nav-folder w-full flex items-center justify-between pl-6 pr-4 py-2.5 sm:py-2 rounded-r-full transition ${activeClasses}"
                    >
                        <span class="flex items-center gap-3.5 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                ${f.icon}
                            </svg>
                            <span class="truncate">${f.label}</span>
                        </span>
                        <span class="text-xs font-bold ml-2">${countVal > 0 ? countVal : ''}</span>
                    </button>
                `;
            }).join('');
        }

        function switchFolder(folder) {
            currentFolder = folder;
            currentLabelFilter = '';
            currentPage = 1;
            selectedIds.clear();
            updateBulkToolbar();
            closeEmailDetail();
            closeMobileSidebar();

            const activeObj = FOLDERS.find(f => f.key === folder);
            const folderTitle = activeObj ? activeObj.label : 'Kotak Masuk';
            document.getElementById('searchInput').placeholder = `Telusuri di ${folderTitle}...`;
            document.getElementById('activeContextBadge').textContent = folderTitle;

            renderSidebarFolders();
            renderCustomLabels();

            // Tab kategori hanya tampil untuk inbox
            document.getElementById('categoryTabs').style.display = (folder === 'inbox') ? 'flex' : 'none';

            fetchEmails();
        }

        // =====================================================================
        // RENDER KATEGORI TAB DINAMIS
        // =====================================================================
        function renderCategoryTabs() {
            const container = document.getElementById('categoryTabs');
            container.innerHTML = CATEGORIES.map(cat => {
                const isActive = currentCategory === cat.key;
                return `
                    <button
                        type="button"
                        onclick="selectCategoryTab('${cat.key}')"
                        class="cat-tab relative flex items-center gap-2.5 px-4 py-3 sm:py-3.5 shrink-0 min-w-[125px] sm:w-44 lg:w-52 text-xs sm:text-sm font-medium transition ${isActive ? 'text-[#0b57d0]' : 'text-slate-600 hover:bg-slate-50'}"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            ${cat.icon}
                        </svg>
                        <span class="truncate">${cat.label}</span>
                        ${isActive ? '<span class="tab-indicator absolute bottom-0 left-3 right-3 h-[3px] bg-[#0b57d0] rounded-t-full"></span>' : ''}
                    </button>
                `;
            }).join('');
        }

        function selectCategoryTab(categoryKey) {
            currentCategory = categoryKey;
            renderCategoryTabs();
            renderEmailList(filterEmailsByCategoryAndLabel(currentEmails));
        }

        function filterEmailsByCategoryAndLabel(emails) {
            let filtered = [...emails];

            // Filter kategori jika di Inbox & bukan 'primary'
            if (currentFolder === 'inbox' && currentCategory !== 'primary') {
                const catObj = CATEGORIES.find(c => c.key === currentCategory);
                if (catObj && catObj.keywords.length > 0) {
                    filtered = filtered.filter(e => {
                        const haystack = `${e.subject || ''} ${e.from_name || ''} ${e.from_address || ''} ${stripHtml(e.body || '')}`.toLowerCase();
                        return catObj.keywords.some(kw => haystack.includes(kw));
                    });
                }
            }

            // Filter label kustom jika aktif
            if (currentLabelFilter) {
                const kw = currentLabelFilter.toLowerCase();
                filtered = filtered.filter(e => {
                    const haystack = `${e.subject || ''} ${e.from_name || ''} ${e.from_address || ''} ${stripHtml(e.body || '')}`.toLowerCase();
                    return haystack.includes(kw);
                });
            }

            return filtered;
        }

        // =====================================================================
        // SISTEM LABEL KUSTOM DINAMIS (TAMBAH, FILTER, HAPUS)
        // =====================================================================
        function getSavedLabels() {
            try {
                const raw = localStorage.getItem(USER_STORAGE_KEY);
                if (raw) {
                    const parsed = JSON.parse(raw);
                    if (Array.isArray(parsed)) return parsed;
                }
            } catch (e) {}

            // Default inisial label
            return [
                { name: 'Sancaka Express', color: '#0b57d0' },
                { name: 'Payment', color: '#059669' }
            ];
        }

        function saveLabels(labels) {
            try {
                localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(labels));
            } catch (e) {}
        }

        function toggleAddLabelForm() {
            const form = document.getElementById('addLabelForm');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                document.getElementById('newLabelInput').focus();
            }
        }

        function createCustomLabel(e) {
            e.preventDefault();
            const input = document.getElementById('newLabelInput');
            const name = input.value.trim();
            if (!name) return;

            const labels = getSavedLabels();
            if (!labels.some(l => l.name.toLowerCase() === name.toLowerCase())) {
                const color = LABEL_COLORS[labels.length % LABEL_COLORS.length];
                labels.push({ name, color });
                saveLabels(labels);
            }

            input.value = '';
            document.getElementById('addLabelForm').classList.add('hidden');
            renderCustomLabels();
            showToast(`Label "${name}" ditambahkan.`);
        }

        function deleteCustomLabel(index) {
            const labels = getSavedLabels();
            const removed = labels.splice(index, 1);
            saveLabels(labels);
            if (removed[0] && currentLabelFilter === removed[0].name) {
                currentLabelFilter = '';
                renderEmailList(filterEmailsByCategoryAndLabel(currentEmails));
            }
            renderCustomLabels();
        }

        function filterByLabel(labelName) {
            currentLabelFilter = (currentLabelFilter === labelName) ? '' : labelName;
            closeEmailDetail();
            closeMobileSidebar();
            renderSidebarFolders();
            renderCustomLabels();

            const badge = document.getElementById('activeContextBadge');
            badge.textContent = currentLabelFilter ? `Label: ${currentLabelFilter}` : (FOLDERS.find(f => f.key === currentFolder)?.label || 'Kotak Masuk');

            renderEmailList(filterEmailsByCategoryAndLabel(currentEmails));
        }

        function renderCustomLabels() {
            const container = document.getElementById('dynamicLabelList');
            const labels = getSavedLabels();

            if (!labels.length) {
                container.innerHTML = '<p class="text-[11px] text-slate-400 py-1">Belum ada label.</p>';
                return;
            }

            container.innerHTML = labels.map((lbl, idx) => {
                const isActive = currentLabelFilter === lbl.name;
                return `
                    <div class="group flex items-center justify-between px-2 py-1.5 rounded-lg cursor-pointer transition ${isActive ? 'bg-[#d3e3fd] text-[#001d35] font-semibold' : 'hover:bg-slate-200/60 text-slate-700'}">
                        <button type="button" onclick="filterByLabel('${escapeHtml(lbl.name)}')" class="flex items-center gap-2.5 min-w-0 flex-1 text-left">
                            <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background-color: ${escapeHtml(lbl.color)}"></span>
                            <span class="truncate">${escapeHtml(lbl.name)}</span>
                        </button>
                        <button
                            type="button"
                            onclick="deleteCustomLabel(${idx})"
                            class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-600 px-1 transition"
                            title="Hapus label"
                        >
                            &times;
                        </button>
                    </div>
                `;
            }).join('');
        }

        // =====================================================================
        // PENCARIAN & PAGINASI
        // =====================================================================
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

        function updateConnectionIndicator(isConnected) {
            const dot1 = document.getElementById('serverStatusDot');
            const dot2 = document.getElementById('popoverStatusDot');
            const text2 = document.getElementById('popoverStatusText');

            if (isConnected) {
                if (dot1) dot1.className = 'w-2 h-2 rounded-full bg-emerald-500 shrink-0';
                if (dot2) dot2.className = 'w-1.5 h-1.5 rounded-full bg-emerald-500';
                if (text2) text2.textContent = 'Terhubung • {{ $mailServerHost }}';
            } else {
                if (dot1) dot1.className = 'w-2 h-2 rounded-full bg-red-500 shrink-0';
                if (dot2) dot2.className = 'w-1.5 h-1.5 rounded-full bg-red-500';
                if (text2) text2.textContent = 'Koneksi Terputus';
            }
        }

        // =====================================================================
        // FETCH DAFTAR EMAIL DARI SERVER
        // =====================================================================
        async function fetchEmails() {
            const container = document.getElementById('emailListContainer');
            const refreshIcon = document.getElementById('refreshIcon');
            selectedIds.clear();
            document.getElementById('selectAllCheckbox').checked = false;
            updateBulkToolbar();

            if (refreshIcon) refreshIcon.classList.add('animate-spin');

            // Skeleton Loading Responsif
            container.innerHTML = Array(7).fill(`
                <div class="px-3 sm:px-4 py-3.5 flex items-center gap-3 sm:gap-4 animate-pulse">
                    <div class="w-4 h-4 bg-slate-200 rounded shrink-0"></div>
                    <div class="w-8 h-8 md:w-4 md:h-4 bg-slate-200 rounded-full shrink-0"></div>
                    <div class="flex-1 space-y-2 md:space-y-0 md:flex md:items-center md:gap-4">
                        <div class="w-32 sm:w-44 h-3.5 bg-slate-200 rounded"></div>
                        <div class="flex-1 h-3.5 bg-slate-100 rounded"></div>
                    </div>
                    <div class="w-12 h-3 bg-slate-200 rounded shrink-0"></div>
                </div>
            `).join('');

            try {
                const url = `{{ route('admin.email.fetch') }}?folder=${encodeURIComponent(currentFolder)}&page=${currentPage}&search=${encodeURIComponent(currentSearch)}&category=${encodeURIComponent(currentCategory)}`;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.error || 'Gagal memuat daftar email.');
                }

                updateConnectionIndicator(true);
                currentEmails = data.emails || [];

                // Sinkronkan jumlah badge folder secara dinamis
                if (data.counts && typeof data.counts === 'object') {
                    folderCounts = { ...folderCounts, ...data.counts };
                } else if (currentFolder === 'inbox' && typeof data.unread_count !== 'undefined') {
                    folderCounts.inbox = data.unread_count;
                }
                renderSidebarFolders();

                // Update Pagination UI
                const pg = data.pagination || { current_page: 1, last_page: 1, has_more: false };
                document.getElementById('paginationInfo').textContent = `Hal ${pg.current_page} / ${pg.last_page}`;
                document.getElementById('prevPageBtn').disabled = pg.current_page <= 1;
                document.getElementById('nextPageBtn').disabled = !pg.has_more;

                renderEmailList(filterEmailsByCategoryAndLabel(currentEmails));

            } catch (err) {
                updateConnectionIndicator(false);
                container.innerHTML = `
                    <div class="p-8 sm:p-12 text-center space-y-3">
                        <p class="text-sm font-medium text-red-600">${escapeHtml(err.message)}</p>
                        <button type="button" onclick="fetchEmails()" class="px-4 py-2 rounded-full bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700 transition">
                            Coba Muat Ulang
                        </button>
                    </div>
                `;
            } finally {
                if (refreshIcon) refreshIcon.classList.remove('animate-spin');
            }
        }

        // =====================================================================
        // RENDER DAFTAR EMAIL (DUAL LAYOUT: MOBILE STACKED & DESKTOP ROW)
        // =====================================================================
        function getAvatarColor(name) {
            const colors = [
                'bg-blue-100 text-blue-700',
                'bg-emerald-100 text-emerald-700',
                'bg-amber-100 text-amber-700',
                'bg-purple-100 text-purple-700',
                'bg-rose-100 text-rose-700',
                'bg-cyan-100 text-cyan-700'
            ];
            let hash = 0;
            const str = String(name || 'A');
            for (let i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
            }
            return colors[Math.abs(hash) % colors.length];
        }

        function renderEmailList(emails) {
            const container = document.getElementById('emailListContainer');

            if (!emails.length) {
                const activeFolderObj = FOLDERS.find(f => f.key === currentFolder);
                const emptyText = currentSearch
                    ? `Tidak ada percakapan yang cocok dengan "${escapeHtml(currentSearch)}".`
                    : currentLabelFilter
                        ? `Tidak ada percakapan untuk label "${escapeHtml(currentLabelFilter)}".`
                        : `Folder ${activeFolderObj ? activeFolderObj.label : 'ini'} masih kosong.`;

                container.innerHTML = `
                    <div class="py-16 sm:py-24 px-4 text-center">
                        <p class="text-sm text-slate-500">${emptyText}</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = emails.map(email => {
                const isUnread = !email.read_at;
                const rowBg = isUnread ? 'bg-white' : 'bg-[#f2f6fc]/50';
                const rawName = (currentFolder === 'sent' && email.to_address)
                    ? `Kepada: ${email.to_address}`
                    : (email.from_name || email.from_address || 'Unknown');
                const displayName = escapeHtml(rawName);
                const initial = escapeHtml((email.from_name || email.to_address || 'A').trim().charAt(0).toUpperCase());
                const avatarStyle = getAvatarColor(rawName);

                const plainSnippet = escapeHtml(stripHtml(email.body || '').substring(0, 100));
                const subjectText = escapeHtml(email.subject || '(Tanpa Subjek)');
                const formattedDate = formatShortDate(email.created_at);

                const starButtonHtml = `
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
                `;

                return `
                    <div
                        onclick="openEmailDetail('${email.id}')"
                        class="group px-3 sm:px-4 py-3 md:py-2.5 cursor-pointer transition-shadow hover:shadow-[inset_1px_0_0_#dadce0,inset_-1px_0_0_#dadce0,0_1px_2px_0_rgba(60,64,67,.25)] hover:z-10 ${rowBg}"
                    >
                        <!-- TAMPILAN MOBILE (< md) -->
                        <div class="flex md:hidden items-start gap-3">
                            <div class="flex flex-col items-center gap-2 pt-0.5 shrink-0">
                                <input
                                    type="checkbox"
                                    value="${email.id}"
                                    onclick="event.stopPropagation(); toggleSelectRow('${email.id}', this.checked)"
                                    class="row-checkbox w-4 h-4 rounded border-slate-300 text-[#0b57d0] focus:ring-0 cursor-pointer"
                                >
                                <div class="w-8 h-8 rounded-full ${avatarStyle} flex items-center justify-center text-xs font-bold">
                                    ${initial}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs truncate ${isUnread ? 'font-bold text-slate-900' : 'font-medium text-slate-700'}">
                                        ${displayName}
                                    </span>
                                    <span class="text-[11px] shrink-0 ${isUnread ? 'font-bold text-[#0b57d0]' : 'text-slate-400'}">
                                        ${formattedDate}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-1 mt-0.5">
                                    <p class="text-xs truncate ${isUnread ? 'font-semibold text-slate-900' : 'text-slate-800'}">
                                        ${subjectText}
                                    </p>
                                    ${starButtonHtml}
                                </div>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                    ${plainSnippet || '(Tidak ada pratinjau teks)'}
                                </p>
                            </div>
                        </div>

                        <!-- TAMPILAN TABLET & PC (>= md) -->
                        <div class="hidden md:flex items-center gap-3.5 text-sm ${isUnread ? 'font-semibold text-slate-900' : 'font-normal text-slate-700'}">
                            <input
                                type="checkbox"
                                value="${email.id}"
                                onclick="event.stopPropagation(); toggleSelectRow('${email.id}', this.checked)"
                                class="row-checkbox w-4 h-4 rounded border-slate-300 text-[#0b57d0] focus:ring-0 cursor-pointer shrink-0"
                            >

                            ${starButtonHtml}

                            <div class="w-44 lg:w-52 truncate shrink-0">
                                ${displayName}
                            </div>

                            <div class="flex-1 min-w-0 truncate">
                                <span class="${isUnread ? 'text-slate-900 font-semibold' : 'text-slate-800'}">${subjectText}</span>
                                <span class="text-slate-500 font-normal"> — ${plainSnippet}</span>
                            </div>

                            <div class="w-20 text-right text-xs shrink-0 ${isUnread ? 'text-slate-900 font-semibold' : 'text-slate-500'}">
                                ${formattedDate}
                            </div>
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
            document.getElementById('mobileComposeFab').classList.add('hidden');

            document.getElementById('detailSubject').textContent = 'Memuat pesan...';
            document.getElementById('detailBody').innerHTML = '<div class="py-12 text-center text-xs text-slate-400">Mengambil isi pesan dari server...</div>';
            document.getElementById('detailAttachmentsWrapper').classList.add('hidden');

            try {
                const res = await fetch(`{{ url('admin/email') }}/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const email = await res.json();

                if (!res.ok) throw new Error(email.error || 'Gagal membuka email.');

                activeDetailEmail = email;
                const folderLabel = FOLDERS.find(f => f.key === currentFolder)?.label || currentFolder.toUpperCase();

                document.getElementById('detailSubject').textContent = email.subject || '(Tanpa Subjek)';
                document.getElementById('detailFolderTag').textContent = folderLabel;
                document.getElementById('detailFromName').textContent = email.from_name || 'Unknown';
                document.getElementById('detailFromAddress').textContent = `<${email.from_address || ''}>`;
                document.getElementById('detailAvatar').textContent = (email.from_name || 'A').charAt(0).toUpperCase();
                document.getElementById('detailToInfo').textContent = email.to_address ? `kepada ${email.to_address}` : 'kepada saya';
                document.getElementById('detailDate').textContent = email.created_at || '';
                document.getElementById('detailBody').innerHTML = email.body || '';

                updateDetailStarIcon(Boolean(email.is_starred));

                // Tandai sudah dibaca di state lokal
                const listItem = currentEmails.find(e => String(e.id) === String(id));
                if (listItem && !listItem.read_at) {
                    listItem.read_at = new Date().toISOString();
                    if (currentFolder === 'inbox' && folderCounts.inbox > 0) {
                        folderCounts.inbox -= 1;
                        renderSidebarFolders();
                    }
                }

                // Render Lampiran jika ada
                const attWrapper = document.getElementById('detailAttachmentsWrapper');
                const attGrid = document.getElementById('detailAttachmentsGrid');
                if (email.attachments && email.attachments.length > 0) {
                    attWrapper.classList.remove('hidden');
                    document.getElementById('detailAttachmentCount').textContent = email.attachments.length;
                    attGrid.innerHTML = email.attachments.map(att => `
                        <a href="${att.url}" target="_blank" rel="noopener noreferrer" class="group border border-slate-200 rounded-xl overflow-hidden bg-slate-50 hover:border-[#0b57d0] transition flex flex-col">
                            ${att.thumbnail
                                ? `<img src="${att.thumbnail}" alt="${escapeHtml(att.name)}" class="h-32 w-full object-cover border-b border-slate-200">`
                                : `<div class="h-24 flex items-center justify-center bg-slate-100 text-slate-400 text-xs font-semibold">FILE LAMPIRAN</div>`
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
                document.getElementById('detailBody').innerHTML = `<div class="text-sm text-red-600">${escapeHtml(err.message)}</div>`;
            }
        }

        function closeEmailDetail() {
            document.getElementById('emailDetailView').classList.add('hidden');
            document.getElementById('emailListView').classList.remove('hidden');
            document.getElementById('mobileComposeFab').classList.remove('hidden');
            activeDetailEmail = null;
            renderEmailList(filterEmailsByCategoryAndLabel(currentEmails));
        }

        function updateDetailStarIcon(isStarred) {
            const btn = document.getElementById('detailStarBtn');
            btn.innerHTML = isStarred
                ? `<svg class="w-4 h-4 text-[#f4b400] fill-[#f4b400]" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`
                : `<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>`;
        }

        async function toggleDetailStar() {
            if (!activeDetailEmail) return;
            const nextState = !activeDetailEmail.is_starred;
            await toggleStar(activeDetailEmail.id, nextState);
            activeDetailEmail.is_starred = nextState;
            updateDetailStarIcon(nextState);
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
        // BINTANG & HAPUS MASSAL
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
                    renderEmailList(filterEmailsByCategoryAndLabel(currentEmails));
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
            document.getElementById('mobileComposeFab').classList.add('hidden');
            document.getElementById('composeTo').focus();
        }

        function closeComposeModal(reset = false) {
            document.getElementById('composeModal').classList.add('hidden');
            document.getElementById('userAutocompleteList').classList.add('hidden');
            if (!activeDetailEmail) {
                document.getElementById('mobileComposeFab').classList.remove('hidden');
            }
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
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-white border border-slate-200 text-xs text-slate-700 max-w-full">
                    <span class="truncate">${escapeHtml(f.name)}</span>
                    <span class="text-slate-400 shrink-0">(${Math.round(f.size / 1024)} KB)</span>
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
                        <button type="button" onclick="selectAutocompleteEmail('${escapeHtml(u.email)}')" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center justify-between gap-2 text-xs">
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-800 truncate">${escapeHtml(u.nama_lengkap || u.name || u.email)}</div>
                                <div class="text-slate-500 truncate">${escapeHtml(u.email)}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] shrink-0">${escapeHtml(u.role || 'User')}</span>
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
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
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
