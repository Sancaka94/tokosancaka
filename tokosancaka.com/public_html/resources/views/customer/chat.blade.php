{{-- resources/views/customer/chat.blade.php --}}
@extends('layouts.customer')

@section('title', 'Support Chat')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    /* === DESAIN BARU (WHATSAPP WEB CLONE - CUSTOMER VIEW) === */
    :root {
        --app-background: #e2e8f0;
        --sidebar-background: #ffffff;
        --chat-panel-background: #efeae2;
        --header-background: #f0f2f5;
        --message-sent-background: #d9fdd3;
        --message-received-background: #ffffff;
        --text-primary: #111b21;
        --text-secondary: #667781;
        --border-color: #e9edef;
        --active-chat-background: #f0f2f5;
        --hover-background: #f5f6f6;
        --wa-green: #25D366;
        --wa-blue-tick: #53bdeb;
    }

    body { background-color: var(--app-background); }

    .chat-container {
        display: flex; height: 85vh; width: 100%; max-width: 1600px; margin: auto;
        background-color: var(--sidebar-background); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color);
    }

    /* === SIDEBAR KIRI === */
    .sidebar {
        width: 35%; min-width: 320px; max-width: 420px; border-right: 1px solid var(--border-color);
        display: flex; flex-direction: column; background-color: var(--sidebar-background); z-index: 10;
    }

    .sidebar-header {
        display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; height: 65px;
        background-color: var(--header-background); border-bottom: 1px solid var(--border-color); box-sizing: border-box;
    }

    .customer-profile { display: flex; align-items: center; gap: 10px; }
    .customer-avatar {
        width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: #cbd5e1;
        display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 16px;
    }

    /* DROPDOWN MENU */
    .action-btn-wrapper { position: relative; }
    .action-btn { background: none; border: none; cursor: pointer; color: #54656f; font-size: 1.2rem; padding: 5px 10px; transition: 0.2s; }
    .action-btn:hover { color: var(--text-primary); }

    .dropdown-menu {
        position: absolute; right: 0; top: 100%; background: white; border: 1px solid var(--border-color);
        border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 5px 0; min-width: 180px; z-index: 1000;
    }
    .dropdown-item {
        width: 100%; text-align: left; padding: 12px 15px; background: none; border: none;
        color: var(--text-primary); cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 14px; transition: 0.2s;
    }
    .dropdown-item:hover { background-color: var(--hover-background); }
    .dropdown-item.danger { color: #ef4444; }
    .dropdown-item.danger:hover { background-color: #fef2f2; }

    /* SEARCH & FILTER */
    .search-section { padding: 10px 14px; border-bottom: 1px solid var(--border-color); }
    .search-box { display: flex; align-items: center; background-color: var(--header-background); border-radius: 8px; padding: 6px 12px; }
    .search-box input { border: none; background: transparent; width: 100%; outline: none; font-size: 14px; color: var(--text-primary); margin-left: 15px; }

    .filter-tabs { display: flex; gap: 8px; margin-top: 12px; overflow-x: auto; padding-bottom: 4px; }
    .filter-btn { background: var(--header-background); border: none; padding: 6px 14px; border-radius: 20px; font-size: 13px; color: #54656f; cursor: pointer; transition: 0.2s; white-space: nowrap; }
    .filter-btn.active { background: #dcf8c6; color: #111b21; font-weight: bold; }

    /* USER LIST */
    .user-list { flex-grow: 1; overflow-y: auto; position: relative; }
    .user-item { display: flex; align-items: center; padding: 12px 16px; cursor: pointer; border-bottom: 1px solid var(--border-color); transition: background-color 0.2s; position: relative; }
    .user-item:hover { background-color: var(--hover-background); }
    .user-item.active { background-color: var(--active-chat-background); }

    .chat-checkbox { margin-right: 15px; transform: scale(1.3); accent-color: var(--wa-green); cursor: pointer; }

    .avatar-wrapper { margin-right: 15px; position: relative; }
    .avatar { width: 48px; height: 48px; border-radius: 50%; background-color: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b; background-size: cover; background-position: center; border: 1px solid var(--border-color); }
    .online-badge { position: absolute; bottom: 2px; right: 0px; width: 12px; height: 12px; background-color: var(--wa-green); border: 2px solid white; border-radius: 50%; }

    .user-details { flex: 1; min-width: 0; }
    .user-details-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; }
    .user-name { font-weight: 600; font-size: 16px; color: var(--text-primary); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-time { font-size: 12px; color: var(--text-secondary); }
    .user-time.unread { color: var(--wa-green); font-weight: bold; }

    .user-details-bottom { display: flex; justify-content: space-between; align-items: center; }
    .last-message { margin: 0; font-size: 13px; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .unread-badge { background-color: var(--wa-green); color: white; font-size: 11px; font-weight: bold; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

    /* === AREA CHAT KANAN === */
    .chat-area { width: 65%; display: flex; flex-direction: column; background-color: var(--chat-panel-background); background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-repeat: repeat; position: relative; flex-grow: 1; }

    .chat-header { display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; height: 65px; background-color: var(--header-background); border-bottom: 1px solid var(--border-color); box-sizing: border-box; flex-shrink: 0; }
    .chat-header-info { display: flex; align-items: center; gap: 15px; }
    #chat-header-name { font-size: 16px; font-weight: bold; color: var(--text-primary); }
    .status-text { font-size: 12px; color: var(--wa-green); margin-top: 2px; }

    #chat-welcome { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100%; color: #54656f; background-color: var(--header-background); font-size: 1.1rem; text-align: center; position: absolute; width: 100%; z-index: 5; }
    #chat-welcome i { font-size: 4rem; margin-bottom: 1rem; color: #aebac1; }

    .chat-messages { flex-grow: 1; padding: 20px 5%; overflow-y: auto; display: flex; flex-direction: column; z-index: 1; }

    /* BUBBLE CHAT */
    .message-container { display: flex; flex-direction: column; margin-bottom: 8px; max-width: 65%; width: fit-content; }
    .message-container.sent { align-self: flex-end; }
    .message-container.received { align-self: flex-start; }
    .message-bubble { padding: 6px 7px 20px 9px; border-radius: 7.5px; word-wrap: break-word; box-shadow: 0 1px 0.5px rgba(11,20,26,.13); position: relative; color: var(--text-primary); font-size: 0.95rem; line-height: 1.4; min-width: 80px; }
    .message-container.sent .message-bubble { background-color: var(--message-sent-background); border-top-right-radius: 0; }
    .message-container.received .message-bubble { background-color: var(--message-received-background); border-top-left-radius: 0; }
    .message-time { position: absolute; right: 8px; bottom: 4px; font-size: 11px; color: var(--text-secondary); display: flex; align-items: center; gap: 3px; white-space: nowrap; }

    /* PREVIEW & PRODUCT CARD */
    #image-preview-container { padding: 10px 16px; background-color: var(--header-background); border-top: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px; }
    .preview-box { position: relative; display: inline-block; }
    .preview-box img { height: 80px; width: 80px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border-color); }
    .preview-box button { position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; }

    .chat-product-card { border: 1px solid #e9edef; padding: 8px; border-radius: 8px; background: #ffffff; margin-bottom: 5px; min-width: 220px; display: flex; gap: 10px; align-items: center; }
    .chat-product-card img { width: 50px; height: 50px; border-radius: 4px; object-fit: cover; }
    .chat-product-info { flex: 1; }
    .chat-product-title { font-size: 12px; font-weight: bold; color: var(--text-primary); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .chat-product-price { font-size: 12px; color: #dc2626; font-weight: bold; margin-top: 2px; }

    /* FORM INPUT */
    .chat-input-container { display: flex; align-items: center; padding: 10px 16px; background-color: var(--header-background); border-top: 1px solid var(--border-color); flex-shrink: 0; z-index: 2; }
    .chat-input-container input[type="text"] { flex-grow: 1; border: none; padding: 12px 16px; border-radius: 8px; outline: none; font-size: 1rem; margin: 0 10px; background-color: #ffffff; }
    .chat-input-btn { background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer; padding: 8px; transition: 0.2s; }
    .chat-input-btn:hover { color: var(--text-primary); }
    .hidden { display: none !important; }

    /* SCROLLBAR */
    .user-list::-webkit-scrollbar, .chat-messages::-webkit-scrollbar { width: 6px; }
    .user-list::-webkit-scrollbar-track, .chat-messages::-webkit-scrollbar-track { background: transparent; }
    .user-list::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    .chat-messages::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 3px; }
    .user-list::-webkit-scrollbar-thumb:hover, .chat-messages::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>
@endpush

@section('content')
<div class="chat-container">

    <div class="sidebar">

        <div class="sidebar-header">
            <div class="customer-profile">
                @php $myInitial = strtoupper(substr(auth()->user()->nama_lengkap ?? auth()->user()->name ?? 'C', 0, 1)); @endphp
                <div class="customer-avatar">{{ $myInitial }}</div>
                <h1 style="font-size: 16px; font-weight: bold; color: var(--text-primary); margin: 0;">{{ auth()->user()->nama_lengkap ?? auth()->user()->name }}</h1>
            </div>

            <div class="action-btn-wrapper">
                <button class="action-btn" id="sidebar-menu-btn" title="Menu"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                <div id="sidebar-dropdown" class="dropdown-menu hidden">
                    <button class="dropdown-item" id="btn-select-chats"><i class="fa-solid fa-check-square"></i> Pilih Chat</button>
                    <button class="dropdown-item danger" id="btn-delete-selected" style="display: none;"><i class="fa-solid fa-trash"></i> Hapus yang Dipilih</button>
                </div>
            </div>
        </div>

        <div class="search-section">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass" style="color: #8696a0; font-size: 14px;"></i>
                <input type="text" id="search-chat" placeholder="Cari nama toko atau chat...">
            </div>
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all">Semua</button>
                <button class="filter-btn" data-filter="unread">Belum Dibaca</button>
                <button class="filter-btn" data-filter="read">Sudah Dibaca</button>
            </div>
        </div>

        <div class="user-list" id="user-list">
            @forelse ($users ?? [] as $user)
                @php
                    // MENGGUNAKAN id_pengguna KARENA DATABASE MENGGUNAKAN ITU
                    $userIdDb = $user->id_pengguna ?? $user->id;

                    // Logika Avatar
                    $avatarUrl = $user->store_logo_path ?? $user->profile_photo_path ?? '';
                    $initial = strtoupper(substr($user->nama_lengkap ?? $user->name ?? 'U', 0, 1));
                    $finalAvatarUrl = $avatarUrl ? (str_starts_with($avatarUrl, 'http') ? $avatarUrl : asset('storage/' . $avatarUrl)) : '';

                    // Logika Status Online
                    $isOnline = $user->last_seen && \Carbon\Carbon::parse($user->last_seen)->diffInMinutes(now()) < 5;

                    // Logika Pesan Terakhir
                    $lastMsg = $user->last_message_data ?? null;
                    $msgText = 'Belum ada pesan...';
                    $timeText = '';
                    $tickHtml = '';
                    $unreadCount = $user->unread_count ?? 0;

                    if ($lastMsg) {
                        if (str_starts_with($lastMsg->message, '[TANYA PRODUK]')) {
                            $msgText = '📦 Bertanya tentang produk';
                        } elseif ($lastMsg->image_url && !$lastMsg->message) {
                            $msgText = '📷 Mengirim gambar';
                        } else {
                            $msgText = $lastMsg->message;
                        }

                        $msgDate = \Carbon\Carbon::parse($lastMsg->created_at);
                        if ($msgDate->isToday()) { $timeText = $msgDate->format('H:i'); }
                        elseif ($msgDate->isYesterday()) { $timeText = 'Kemarin'; }
                        else { $timeText = $msgDate->format('d/m/Y'); }

                        if ($lastMsg->from_id == auth()->id() || $lastMsg->from_id == (auth()->user()->id_pengguna ?? 0)) {
                            if ($lastMsg->read_at) {
                                $tickHtml = '<i class="fa-solid fa-check-double" style="color: var(--wa-blue-tick); font-size: 11px; margin-right: 4px;"></i>';
                            } else {
                                $tickHtml = '<i class="fa-solid fa-check-double" style="color: #8696a0; font-size: 11px; margin-right: 4px;"></i>';
                            }
                        }
                    }
                @endphp

                <div class="user-item"
                     data-id="{{ $userIdDb }}"
                     data-name="{{ strtolower($user->nama_lengkap ?? $user->name ?? '') }}"
                     data-unread="{{ $unreadCount > 0 ? 'true' : 'false' }}"
                     data-avatar="{{ $finalAvatarUrl }}"
                     data-online="{{ $isOnline ? 'true' : 'false' }}">

                    <input type="checkbox" class="chat-checkbox hidden" value="{{ $userIdDb }}">

                    <div class="avatar-wrapper">
                        <div class="avatar" style="{{ $finalAvatarUrl ? 'background-image: url(' . $finalAvatarUrl . '); color: transparent;' : '' }}">
                            @if(!$finalAvatarUrl) {{ $initial }} @endif
                        </div>
                        @if($isOnline) <div class="online-badge"></div> @endif
                    </div>

                    <div class="user-details">
                        <div class="user-details-top">
                            <p class="user-name">{{ $user->nama_lengkap ?? $user->name ?? 'Toko Sancaka' }}</p>
                            <span class="user-time {{ $unreadCount > 0 ? 'unread' : '' }}">{{ $timeText }}</span>
                        </div>

                        <div class="user-details-bottom">
                            <div style="display: flex; align-items: center; flex: 1; min-width: 0; padding-right: 10px;">
                                {!! $tickHtml !!}
                                <p class="last-message">{{ $msgText }}</p>
                            </div>
                            @if($unreadCount > 0)
                                <div class="unread-badge">{{ $unreadCount }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                    <i class="fa-solid fa-inbox" style="font-size: 50px; margin-bottom: 15px; color: #cbd5e1;"></i>
                    <p style="font-size: 16px;">Belum ada riwayat chat.</p>
                </div>
            @endforelse
        </div>
    </div>


    <div class="chat-area">

        <div id="chat-welcome">
             <div>
                <i class="fa-brands fa-whatsapp"></i>
                <p>Sancaka Express Web</p>
                <small>Pilih percakapan di sebelah kiri untuk mulai berkirim pesan.</small>
            </div>
        </div>

        <div id="chat-box" class="hidden" style="display: flex; flex-direction: column; height: 100%;">

            <div class="chat-header">
                <div class="chat-header-info">
                    <div class="avatar-wrapper">
                        <img id="header-avatar-img" src="" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; display: none;">
                        <div id="header-avatar-initial" style="width: 42px; height: 42px; border-radius: 50%; background-color: #667781; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;"></div>
                        <div id="header-online-badge" class="online-badge hidden"></div>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <div id="chat-header-name" style="font-size: 16px; font-weight: bold; color: var(--text-primary);">Nama Toko</div>
                        <div id="chat-header-status" class="status-text hidden">Online</div>
                    </div>
                </div>

                <div class="action-btn-wrapper">
                    <button class="action-btn" id="chat-menu-btn" title="Options">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <div id="chat-dropdown" class="dropdown-menu hidden">
                        <button class="dropdown-item danger" id="btn-delete-chat"><i class="fa-solid fa-trash"></i> Bersihkan Chat</button>
                    </div>
                </div>
            </div>

            <div class="chat-messages custom-scrollbar" id="chat-messages"></div>

            <div id="image-preview-container" class="hidden">
                <div class="preview-box">
                    <img id="image-preview" src="" alt="Preview">
                    <button id="remove-image-btn" title="Hapus Gambar"><i class="fa-solid fa-times"></i></button>
                </div>
            </div>

            <div class="chat-input-container">
                <button class="chat-input-btn" title="Emoji"><i class="fa-regular fa-face-smile"></i></button>
                <input type="file" id="image-upload-input" accept="image/png, image/jpeg, image/webp" class="hidden">
                <button id="attachment-btn" class="chat-input-btn" title="Kirim Gambar" onclick="document.getElementById('image-upload-input').click()">
                    <i class="fa-solid fa-paperclip"></i>
                </button>
                <input type="text" id="message-input" placeholder="Ketik pesan..." autocomplete="off">
                <button id="send-button" class="chat-input-btn" type="submit" title="Kirim Pesan">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/id.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
$(document).ready(function() {
    const customerId = {{ auth()->user()->id_pengguna ?? auth()->id() }};
    let currentTargetId = null;
    let pollingInterval = null;
    let lastMessageCount = 0;
    let selectedImageFile = null;
    let isTargetOnline = false;
    let isSelectMode = false;
    const notificationSound = new Audio('{{ asset("sounds/beep.mp3") }}');

    function scrollToBottom() {
        const container = $('#chat-messages');
        if (container.length) container.scrollTop(container[0].scrollHeight);
    }

    // === DROPDOWN MENUS (TITIK TIGA) ===
    $('#sidebar-menu-btn').on('click', function(e) {
        e.stopPropagation();
        $('#chat-dropdown').addClass('hidden');
        $('#sidebar-dropdown').toggleClass('hidden');
    });

    $('#chat-menu-btn').on('click', function(e) {
        e.stopPropagation();
        $('#sidebar-dropdown').addClass('hidden');
        $('#chat-dropdown').toggleClass('hidden');
    });

    $(document).on('click', function() {
        $('#sidebar-dropdown').addClass('hidden');
        $('#chat-dropdown').addClass('hidden');
    });

    // === SEARCH & FILTER ===
    $('#search-chat').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase();
        $('.user-item').each(function() {
            const name = $(this).data('name');
            if (name.includes(searchValue)) { $(this).show(); }
            else { $(this).hide(); }
        });
    });

    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');

        const filterType = $(this).data('filter');
        $('.user-item').each(function() {
            const isUnread = $(this).data('unread') === true || $(this).data('unread') === 'true';
            if (filterType === 'all') { $(this).show(); }
            else if (filterType === 'unread' && isUnread) { $(this).show(); }
            else if (filterType === 'read' && !isUnread) { $(this).show(); }
            else { $(this).hide(); }
        });
        $('#search-chat').val('');
    });

    // === MODE PILIH (CHECKBOX) ===
    $('#btn-select-chats').on('click', function() {
        isSelectMode = !isSelectMode;
        if(isSelectMode) {
            $('.chat-checkbox').removeClass('hidden');
            $('#btn-delete-selected').show();
            toastr.info('Mode pilih diaktifkan. Ceklis chat yang ingin dihapus.');
        } else {
            $('.chat-checkbox').addClass('hidden').prop('checked', false);
            $('#btn-delete-selected').hide();
        }
    });

    $('#btn-delete-selected').on('click', function() {
        let selectedIds = [];
        $('.chat-checkbox:checked').each(function() { selectedIds.push($(this).val()); });

        if(selectedIds.length === 0) return toastr.warning('Tidak ada chat yang dipilih.');

        if(confirm(`Yakin ingin menghapus ${selectedIds.length} riwayat chat ini?`)) {
            toastr.info('Sedang menghapus riwayat chat...');
            // Di sini nanti ditaruh AJAX untuk menghapus multiple chat
            setTimeout(() => location.reload(), 1000);
        }
    });

    $('#btn-delete-chat').on('click', function() {
        if(confirm('Yakin ingin membersihkan semua pesan dengan toko ini?')) {
            $.ajax({
                url: `/customer/chat/messages/delete-all`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', contact_id: currentTargetId },
                success: function(res) {
                    if (res.success) {
                        $('#chat-messages').empty();
                        lastMessageCount = 0;
                        toastr.success('Riwayat chat berhasil dibersihkan.');
                    }
                }
            });
        }
    });

    // === FUNGSI RENDER PESAN ===
    function parseMessage(text) {
        if (!text) return '';
        let safeText = $('<div>').text(text).html();

        if (safeText.startsWith('[TANYA PRODUK]')) {
            const lines = safeText.split('\n');
            if (lines.length >= 3) {
                let imgUri = lines[3] ? (lines[3].startsWith('http') ? lines[3] : `/storage/${lines[3]}`) : 'https://placehold.co/100x100.png';
                return `<div class="chat-product-card"><img src="${imgUri}"><div class="chat-product-info"><div class="chat-product-title">${lines[1]}</div><div class="chat-product-price">${lines[2].replace('Harga: ', '')}</div></div></div>`;
            }
        }
        return safeText.replace(/\n/g, '<br>');
    }

    function createMessageBubble(msg) {
        const isSent = (msg.from_id == customerId) || msg.is_me;
        const messageSide = isSent ? 'sent' : 'received';
        const timeString = moment(msg.created_at).locale('id').format('HH:mm');

        let contentHtml = '';
        if (msg.image_url) {
            let imgPath = msg.image_url.startsWith('http') ? msg.image_url : `/storage/${msg.image_url}`;
            contentHtml += `<img src="${imgPath}" style="max-width: 100%; border-radius: 6px; margin-bottom: 5px; max-height: 250px; object-fit: cover;">`;
        }

        contentHtml += parseMessage(msg.message);

        let tickHtml = '';
        if (isSent) {
            if (msg.is_read || msg.read_at) { tickHtml = '<i class="fa-solid fa-check-double" style="font-size: 0.7rem; color: var(--wa-blue-tick);"></i>'; }
            else if (isTargetOnline) { tickHtml = '<i class="fa-solid fa-check-double" style="font-size: 0.7rem; color: #9ca3af;"></i>'; }
            else { tickHtml = '<i class="fa-solid fa-check" style="font-size: 0.7rem; color: #9ca3af;"></i>'; }
        }

        return `<div class="message-container ${messageSide}"><div class="message-bubble">${contentHtml}<div class="message-time">${timeString} ${tickHtml}</div></div></div>`;
    }

    function loadMessages() {
        if (!currentTargetId) return;

        $.ajax({
            url: `/customer/chat/messages/${currentTargetId}`,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                let messages = response.messages ? response.messages : response;

                if (messages.length !== lastMessageCount) {
                    if (lastMessageCount > 0 && messages.length > 0 && messages[messages.length - 1].from_id != customerId) {
                        notificationSound.play().catch(e => {});
                    }

                    const messagesContainer = $('#chat-messages');
                    messagesContainer.html('');

                    if (messages.length === 0) {
                        messagesContainer.html('<p class="text-center text-gray-500 p-4" style="background: white; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); width: fit-content; margin: 0 auto;">Belum ada percakapan. Mulai sekarang!</p>');
                    } else {
                        messages.forEach(function(msg) { messagesContainer.append(createMessageBubble(msg)); });
                    }
                    scrollToBottom();
                    lastMessageCount = messages.length;
                }
            }
        });
    }

    // === LOGIKA SIDEBAR KLIK (PILIH OBROLAN) ===
    $('#user-list').on('click', '.user-item', function(e) {
        // Jika mode checklist aktif, jangan buka chat
        if(isSelectMode && $(e.target).is('input[type="checkbox"]')) return;
        if(isSelectMode) return;

        const targetId = $(this).data('id');
        const targetName = $(this).find('.user-name').text();
        const targetAvatar = $(this).data('avatar');
        const targetOnline = $(this).data('online') === true || $(this).data('online') === 'true';

        isTargetOnline = targetOnline;

        // Set Header Kanan
        $('#chat-header-name').text(targetName);
        if (targetAvatar && targetAvatar !== '') {
            $('#header-avatar-img').attr('src', targetAvatar).show();
            $('#header-avatar-initial').hide();
        } else {
            $('#header-avatar-img').hide();
            $('#header-avatar-initial').text(targetName.charAt(0).toUpperCase()).show();
        }

        if (targetOnline) {
            $('#header-online-badge').removeClass('hidden');
            $('#chat-header-status').removeClass('hidden');
        } else {
            $('#header-online-badge').addClass('hidden');
            $('#chat-header-status').addClass('hidden');
        }

        $('#chat-welcome').addClass('hidden');
        $('#chat-box').removeClass('hidden');
        $('#message-input').focus();

        $('.user-item').removeClass('active');
        $(this).addClass('active');

        if (pollingInterval) clearInterval(pollingInterval);

        currentTargetId = targetId;
        lastMessageCount = 0;
        loadMessages();
        pollingInterval = setInterval(loadMessages, 3000);
    });

    // === LOGIKA UPLOAD & KIRIM ===
    $('#image-upload-input').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            selectedImageFile = file;
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result);
                $('#image-preview-container').removeClass('hidden');
                $('#message-input').focus();
            }
            reader.readAsDataURL(file);
        }
    });

    $('#remove-image-btn').on('click', function() {
        selectedImageFile = null;
        $('#image-upload-input').val('');
        $('#image-preview-container').addClass('hidden');
    });

    function sendMessage() {
        const messageInput = $('#message-input');
        const message = messageInput.val().trim();
        const sendButton = $('#send-button');

        if ((!message && !selectedImageFile) || !currentTargetId || sendButton.prop('disabled')) return;

        sendButton.prop('disabled', true);
        messageInput.prop('disabled', true);

        let formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        if (message) formData.append('message', message);
        if (selectedImageFile) formData.append('image', selectedImageFile);

        $.ajax({
            url: `/customer/chat/messages/${currentTargetId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function() {
                messageInput.val('');
                selectedImageFile = null;
                $('#image-upload-input').val('');
                $('#image-preview-container').addClass('hidden');
                lastMessageCount = 0;
                loadMessages();
            },
            error: function() { toastr.error('Gagal mengirim pesan.', 'Error'); },
            complete: function() {
                sendButton.prop('disabled', false);
                messageInput.prop('disabled', false).focus();
            }
        });
    }

    $('#send-button').on('click', sendMessage);
    $('#message-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    toastr.options = { "positionClass": "toast-top-right", "progressBar": true, "timeOut": "4000" };
});
</script>
@endpush
