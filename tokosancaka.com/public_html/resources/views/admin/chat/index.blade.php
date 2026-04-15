@extends('layouts.admin')

@section('title', 'Chat Pelanggan')
@section('page-title', 'Chat Pelanggan')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    /* === DESAIN BARU (WHATSAPP WEB CLONE) === */
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

    body {
        background-color: var(--app-background);
    }

    .chat-container {
        display: flex;
        height: 85vh;
        width: 100%;
        max-width: 1600px;
        margin: auto;
        background-color: var(--sidebar-background);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border-radius: 8px;
        overflow: hidden;
    }

    /* === SIDEBAR (DAFTAR KONTAK) === */
    .sidebar {
        width: 30%;
        min-width: 320px;
        max-width: 420px;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        background-color: var(--sidebar-background);
        z-index: 10;
    }

    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
        height: 65px;
        background-color: var(--header-background);
        border-bottom: 1px solid var(--border-color);
        box-sizing: border-box;
    }

    .sidebar-header h1 {
        font-size: 20px;
        font-weight: bold;
        color: var(--text-primary);
        margin: 0;
    }

    .sidebar-actions {
        display: flex;
        gap: 20px;
        color: #54656f;
        font-size: 1.2rem;
    }

    .sidebar-actions button {
        background: none;
        border: none;
        cursor: pointer;
        color: inherit;
        transition: color 0.2s;
    }

    .sidebar-actions button:hover {
        color: var(--text-primary);
    }

    .search-section {
        padding: 10px 14px;
        border-bottom: 1px solid var(--border-color);
    }

    .search-box {
        display: flex;
        align-items: center;
        background-color: var(--header-background);
        border-radius: 8px;
        padding: 6px 12px;
    }

    .search-box input {
        border: none;
        background: transparent;
        width: 100%;
        outline: none;
        font-size: 14px;
        color: var(--text-primary);
        margin-left: 15px;
    }

    .filter-tabs {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .filter-btn {
        background: var(--header-background);
        border: none;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        color: #54656f;
        cursor: pointer;
        transition: 0.2s;
    }

    .filter-btn.active {
        background: #e2e8f0;
        color: #111b21;
    }

    .user-list {
        flex-grow: 1;
        overflow-y: auto;
    }

    .user-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s;
    }

    .user-item:hover { background-color: var(--hover-background); }
    .user-item.active { background-color: var(--active-chat-background); }

    .avatar-wrapper {
        margin-right: 15px;
        position: relative;
    }

    .avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #64748b;
        background-size: cover;
        background-position: center;
    }

    .online-badge {
        position: absolute;
        bottom: 2px;
        right: 0px;
        width: 12px;
        height: 12px;
        background-color: var(--wa-green);
        border: 2px solid white;
        border-radius: 50%;
    }

    .user-details {
        flex: 1;
        min-width: 0;
    }

    .user-details-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3px;
    }

    .user-name {
        font-weight: 600;
        font-size: 16px;
        color: var(--text-primary);
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-time {
        font-size: 12px;
    }

    .user-details-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .last-message {
        margin: 0;
        font-size: 13px;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .unread-badge {
        background-color: var(--wa-green);
        color: white;
        font-size: 11px;
        font-weight: bold;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* === AREA CHAT UTAMA === */
    .chat-area {
        width: 70%;
        display: flex;
        flex-direction: column;
        background-color: var(--chat-panel-background);
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        background-repeat: repeat;
        position: relative;
    }

    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 20px;
        height: 65px;
        background-color: var(--header-background);
        border-bottom: 1px solid var(--border-color);
        box-sizing: border-box;
        flex-shrink: 0;
    }

    .chat-header-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .chat-header-text {
        display: flex;
        flex-direction: column;
    }

    #chat-header-name {
        font-size: 16px;
        font-weight: bold;
        color: var(--text-primary);
    }

    .status-text {
        font-size: 12px;
        color: var(--wa-green);
        margin-top: 2px;
    }

    .chat-header-actions {
        display: flex;
        align-items: center;
        gap: 20px;
        position: relative;
    }

    .header-action-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.4rem;
        color: var(--text-secondary);
        transition: color 0.2s;
    }

    .header-action-btn:hover { color: var(--text-primary); }

    .chat-options-menu {
        position: absolute;
        right: 0;
        top: 45px;
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 5px 0;
        min-width: 200px;
        z-index: 1000;
    }

    #delete-chat-btn {
        width: 100%;
        text-align: left;
        padding: 12px 15px;
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
    }

    #delete-chat-btn:hover { background-color: #fef2f2; }

    #chat-welcome {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100%;
        color: #54656f;
        background-color: var(--header-background);
        font-size: 1.1rem;
        text-align: center;
        position: absolute;
        width: 100%;
        z-index: 5;
    }

    #chat-welcome i { font-size: 4rem; margin-bottom: 1rem; color: #aebac1; }

    .chat-messages {
        flex-grow: 1;
        padding: 20px 5%;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        z-index: 1;
    }

    /* === BUBBLE CHAT & WAKTU (WHATSAPP STYLE) === */
    .message-container {
        display: flex;
        flex-direction: column;
        margin-bottom: 8px;
        max-width: 65%;
        width: fit-content;
    }

    .message-container.sent { align-self: flex-end; }
    .message-container.received { align-self: flex-start; }

    .message-bubble {
        /* Padding bawah dilebarkan agar waktu bisa masuk ke dalam tanpa menabrak teks */
        padding: 6px 7px 20px 9px;
        border-radius: 7.5px;
        word-wrap: break-word;
        box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
        position: relative;
        color: var(--text-primary);
        font-size: 0.95rem;
        line-height: 1.4;
        min-width: 80px;
    }

    .message-container.sent .message-bubble {
        background-color: var(--message-sent-background);
        border-top-right-radius: 0;
    }

    .message-container.received .message-bubble {
        background-color: var(--message-received-background);
        border-top-left-radius: 0;
    }

    .message-time {
        position: absolute;
        right: 8px;
        bottom: 4px;
        font-size: 11px;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap;
    }

    /* === FORM INPUT BAWAH === */
    .chat-input-container {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        background-color: var(--header-background);
        border-top: 1px solid var(--border-color);
        flex-shrink: 0;
        z-index: 2;
    }

    .chat-input-container input[type="text"] {
        flex-grow: 1;
        border: none;
        padding: 12px 16px;
        border-radius: 8px;
        outline: none;
        font-size: 1rem;
        margin: 0 10px;
        background-color: #ffffff;
    }

    .chat-input-btn {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 8px;
        transition: 0.2s;
    }

    .chat-input-btn:hover { color: var(--text-primary); }

    /* === PREVIEW GAMBAR === */
    #image-preview-container {
        padding: 10px 16px;
        background-color: var(--header-background);
        border-top: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .preview-box { position: relative; display: inline-block; }
    .preview-box img { height: 80px; width: 80px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border-color); }
    .preview-box button {
        position: absolute; top: -8px; right: -8px; background: #ef4444; color: white;
        border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer;
    }

    .hidden { display: none !important; }

    /* --- SCROLLBAR STYLE --- */
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
            <h1>Chats</h1>
            <div class="sidebar-actions">
                <button title="New Chat"><i class="fa-solid fa-pen-to-square"></i></button>
                <button title="Menu"><i class="fa-solid fa-ellipsis-vertical"></i></button>
            </div>
        </div>

        <div class="search-section">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass" style="color: #8696a0; font-size: 14px;"></i>
                <input type="text" id="search-chat" placeholder="Search or start a new chat">
            </div>
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="unread">Unread</button>
                <button class="filter-btn" data-filter="groups">Groups</button>
            </div>
        </div>

        <div class="user-list" id="user-list">
            @forelse ($users as $user)
                @php
                    $avatarUrl = $user->store_logo_path ?? '';
                    $initial = strtoupper(substr($user->nama_lengkap ?? 'U', 0, 1));
                    $finalAvatarUrl = $avatarUrl ? (str_starts_with($avatarUrl, 'http') ? $avatarUrl : asset('storage/' . $avatarUrl)) : '';

                    // Logika Online
                    $isOnline = $user->last_seen && \Carbon\Carbon::parse($user->last_seen)->diffInMinutes(now()) < 5;

                    // Logika Pesan Terakhir
                    $lastMsg = $user->last_message_data ?? null;
                    $msgText = 'Belum ada pesan...';
                    $timeText = '';
                    $tickHtml = '';
                    $unreadCount = $user->unread_count ?? 0;

                    if ($lastMsg) {
                        if (str_starts_with($lastMsg->message, '[TANYA PRODUK]') || str_starts_with($lastMsg->message, '[INFO PRODUK]')) {
                            $msgText = '📦 Bertanya tentang produk';
                        } elseif ($lastMsg->image_url && !$lastMsg->message) {
                            $msgText = '📷 Mengirim gambar';
                        } else {
                            $msgText = $lastMsg->message;
                        }

                        $msgDate = \Carbon\Carbon::parse($lastMsg->created_at);
                        if ($msgDate->isToday()) {
                            $timeText = $msgDate->format('H:i');
                        } elseif ($msgDate->isYesterday()) {
                            $timeText = 'Yesterday';
                        } else {
                            $timeText = $msgDate->format('d/m/Y');
                        }

                        // Centang Biru Ala WA (Hanya jika admin yg ngirim)
                        if ($lastMsg->from_id == auth()->id()) {
                            if ($lastMsg->read_at) {
                                $tickHtml = '<i class="fa-solid fa-check-double" style="color: var(--wa-blue-tick); font-size: 11px; margin-right: 4px;"></i>';
                            } else {
                                $tickHtml = '<i class="fa-solid fa-check-double" style="color: #8696a0; font-size: 11px; margin-right: 4px;"></i>';
                            }
                        }
                    }
                @endphp

                <div class="user-item"
                     data-id="{{ $user->getKey() }}"
                     data-name="{{ strtolower($user->nama_lengkap) }}"
                     data-unread="{{ $unreadCount > 0 ? 'true' : 'false' }}"
                     data-phone="{{ $user->no_wa ?? '' }}"
                     data-avatar="{{ $finalAvatarUrl }}"
                     data-online="{{ $isOnline ? 'true' : 'false' }}">

                    <div class="avatar-wrapper">
                        <div class="avatar" style="{{ $finalAvatarUrl ? 'background-image: url(' . $finalAvatarUrl . '); color: transparent;' : '' }}">
                            @if(!$finalAvatarUrl) {{ $initial }} @endif
                        </div>

                        <div class="online-badge sidebar-badge" style="display: {{ $isOnline ? 'block' : 'none' }}; z-index: 20;"></div>
                    </div>

                    <div class="user-details">
                        <div class="user-details-top">
                            <p class="user-name">{{ $user->nama_lengkap }}</p>
                            <span class="user-time" style="color: {{ $unreadCount > 0 ? 'var(--wa-green)' : 'var(--text-secondary)' }}; font-weight: {{ $unreadCount > 0 ? 'bold' : 'normal' }};">
                                {{ $timeText }}
                            </span>
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
                <div style="text-align: center; padding: 20px; color: var(--text-secondary);">Tidak ada pelanggan.</div>
            @endforelse
        </div>
    </div>


    <div class="chat-area">

        <div id="chat-welcome">
             <div>
                <i class="fa-regular fa-comments"></i>
                <p>Pilih pelanggan untuk memulai percakapan.</p>
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

                    <div class="chat-header-text">
                        <div id="chat-header-name">Nama Pelanggan</div>
                        <div id="chat-header-status" class="status-text hidden">Online</div>
                    </div>
                </div>

                <div class="chat-header-actions">
                    <button id="wa-call-btn" class="header-action-btn" title="Hubungi via WhatsApp" style="display: none;">
                        <i class="fa-brands fa-whatsapp" style="color: var(--wa-green);"></i>
                    </button>
                    <button id="chat-options-btn" class="header-action-btn">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>

                    <div id="chat-options-menu" class="chat-options-menu hidden">
                        <button id="delete-chat-btn">
                            <i class="fa-solid fa-trash"></i> Hapus Riwayat Chat
                        </button>
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
                <button class="chat-input-btn" title="Emoji (fitur belum aktif)"><i class="fa-regular fa-face-smile"></i></button>

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
    let currentUserId = null;
    const adminId = {{ auth()->id() }};
    let pollingInterval = null;
    let lastMessageCount = 0;
    let selectedImageFile = null;
    let isCurrentChatOnline = false;
    const notificationSound = new Audio('{{ asset("sounds/beep.mp3") }}');

    function scrollToBottom(containerSelector = '#chat-messages') {
        const container = $(containerSelector);
        if (container.length) {
            container.scrollTop(container[0].scrollHeight);
        }
    }

    function parseMessage(text) {
        if (!text) return '';

        let safeText = $('<div>').text(text).html();

        if (safeText.startsWith('[TANYA PRODUK]') || safeText.startsWith('[INFO PRODUK]')) {
            const lines = safeText.split('\n');
            if (lines.length >= 3) {
                let imgUri = 'https://placehold.co/100x100.png';
                if (lines[3] && lines[3].trim() !== '') {
                    imgUri = lines[3].startsWith('http') ? lines[3] : `/storage/${lines[3]}`;
                }
                return `
                    <div class="chat-product-card" style="border: 1px solid #e9edef; padding: 8px; border-radius: 8px; background: #ffffff; margin-bottom: 5px; min-width: 220px; display: flex; gap: 10px; align-items: center;">
                        <img src="${imgUri}" style="width: 50px; height: 50px; border-radius: 4px; object-fit: cover;">
                        <div class="chat-product-info" style="flex: 1;">
                            <div class="chat-product-title" style="font-size: 12px; font-weight: bold; color: #111b21; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${lines[1]}</div>
                            <div class="chat-product-price" style="font-size: 12px; color: #dc2626; font-weight: bold; margin-top: 2px;">${lines[2].replace('Harga: ', '')}</div>
                        </div>
                    </div>
                `;
            }
        }
        return safeText.replace(/\n/g, '<br>');
    }

    function displayMessage(msg) {
        const messageSide = msg.from_id == adminId ? 'sent' : 'received';
        const timeString = moment(msg.created_at).locale('id').format('HH:mm');

        let contentHtml = '';

        if (msg.image_url) {
            let imgPath = msg.image_url.startsWith('http') ? msg.image_url : `/storage/${msg.image_url}`;
            contentHtml += `<img src="${imgPath}" style="max-width: 100%; border-radius: 6px; margin-bottom: 5px; max-height: 250px; object-fit: cover;">`;
        }

        contentHtml += parseMessage(msg.message);

        let tickHtml = '';
        if (msg.is_me) {
            if (msg.is_read) {
                // Diubah warnanya jadi Biru WA
                tickHtml = '<i class="fa-solid fa-check-double" style="font-size: 0.7rem; color: var(--wa-blue-tick);"></i>';
            } else if (isCurrentChatOnline) {
                tickHtml = '<i class="fa-solid fa-check-double" style="font-size: 0.7rem; color: #9ca3af;"></i>';
            } else {
                tickHtml = '<i class="fa-solid fa-check" style="font-size: 0.7rem; color: #9ca3af;"></i>';
            }
        }

        const messageHtml = `
            <div class="message-container ${messageSide}">
                <div class="message-bubble">
                    ${contentHtml}
                    <div class="message-time">${timeString} ${tickHtml}</div>
                </div>
            </div>
        `;
        $('#chat-messages').append(messageHtml);
    }

    function fetchMessages() {
        if (!currentUserId) return;

        $.ajax({
            url: `/admin/chat/messages/${currentUserId}`,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                let messages = response.messages || response;

                    let isTargetOnline = response.target_online || false;
                    isCurrentChatOnline = isTargetOnline;

                    let activeUserItem = $(`.user-item[data-id="${currentUserId}"]`);
                    let activeSidebarBadge = activeUserItem.find('.online-badge');

                    // [TAMBAHAN BARU] Jika elemen badge belum ada di HTML sidebar, buatkan otomatis!
                    if (activeSidebarBadge.length === 0) {
                        activeUserItem.find('.avatar-wrapper').append('<div class="online-badge hidden"></div>');
                        activeSidebarBadge = activeUserItem.find('.online-badge'); // Ambil ulang elemennya
                    }

                    // Update atribut data supaya tidak glitch saat klik/ganti chat
                    activeUserItem.attr('data-online', isTargetOnline ? 'true' : 'false');
                    activeUserItem.data('online', isTargetOnline ? 'true' : 'false');

                    if (isTargetOnline) {
                        $('#header-online-badge').removeClass('hidden');
                        $('#chat-header-status').removeClass('hidden');
                        activeSidebarBadge.removeClass('hidden');
                    } else {
                        $('#header-online-badge').addClass('hidden');
                        $('#chat-header-status').addClass('hidden');
                        activeSidebarBadge.addClass('hidden');
                    }


                if (messages.length !== lastMessageCount) {
                    if (lastMessageCount > 0 && messages.length > 0 && messages[messages.length - 1].from_id != adminId && messages.length > lastMessageCount) {
                        notificationSound.play().catch(e => console.error("Gagal memutar suara:", e));
                    }

                    const messagesContainer = $('#chat-messages');
                    messagesContainer.html('');
                    if (messages.length > 0) {
                        messages.forEach(displayMessage);
                    } else {
                        messagesContainer.html('<p style="text-align: center; padding: 20px; color: var(--text-secondary);">Belum ada pesan.</p>');
                    }

                    scrollToBottom();
                    lastMessageCount = messages.length;
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Gagal memuat pesan:', textStatus, errorThrown);
            }
        });
    }

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

        if ((!message && !selectedImageFile) || !currentUserId || sendButton.prop('disabled')) return;

        sendButton.prop('disabled', true);
        messageInput.prop('disabled', true);

        let formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        if (message) formData.append('message', message);
        if (selectedImageFile) formData.append('image', selectedImageFile);

        $.ajax({
            url: `/admin/chat/messages/${currentUserId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'Pesan terkirim!' || response.success) {
                    messageInput.val('');

                    selectedImageFile = null;
                    $('#image-upload-input').val('');
                    $('#image-preview-container').addClass('hidden');

                    lastMessageCount = 0;
                    fetchMessages();
                } else {
                     toastr.error(response.status || 'Gagal mengirim pesan.', 'Error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 let errorMsg = 'Gagal mengirim pesan. Silakan coba lagi.';
                 if(jqXHR.responseJSON && jqXHR.responseJSON.message) {
                     errorMsg = jqXHR.responseJSON.message;
                 }
                 toastr.error(errorMsg, 'Error Server');
            },
            complete: function() {
                sendButton.prop('disabled', false);
                messageInput.prop('disabled', false).focus();
            }
        });
    }

    $('#chat-options-btn').on('click', function(e) {
        e.stopPropagation();
        $('#chat-options-menu').toggleClass('hidden');
    });

    $(document).on('click', function() {
        $('#chat-options-menu').addClass('hidden');
    });

    $('#delete-chat-btn').on('click', function() {
        if(confirm('Yakin ingin menghapus semua riwayat chat dengan pengguna ini?')) {
            alert('Fitur hapus pesan akan disambungkan ke backend!');
            $('#chat-options-menu').addClass('hidden');
        }
    });

    function setActiveChat(userId) {
        const userElement = $(`.user-item[data-id=${userId}]`);
        if (!userElement.length) return;

        const userName = userElement.data('name');
        const userAvatar = userElement.data('avatar');
        const userPhone = userElement.data('phone');

        // const isOnline = userElement.data('online') === true || userElement.data('online') === 'true';
        const isOnline = userElement.attr('data-online') === 'true';
        isCurrentChatOnline = isOnline;

        if (userId === currentUserId && !$('#chat-box').hasClass('hidden')) {
            return;
        }

        $('#chat-header-name').text(userName);

        if (userAvatar && userAvatar !== '') {
            $('#header-avatar-img').attr('src', userAvatar).show();
            $('#header-avatar-initial').hide();
        } else {
            $('#header-avatar-img').hide();
            $('#header-avatar-initial').text(userName.charAt(0).toUpperCase()).show();
        }

        if (isOnline) {
            $('#header-online-badge').removeClass('hidden');
            $('#chat-header-status').removeClass('hidden');
        } else {
            $('#header-online-badge').addClass('hidden');
            $('#chat-header-status').addClass('hidden');
        }

        if (userPhone && userPhone !== '') {
            let phoneStr = String(userPhone).replace(/[-+ \s]/g, '');
            if (phoneStr.startsWith('0')) phoneStr = '62' + phoneStr.substring(1);
            $('#wa-call-btn').show().off('click').on('click', function() {
                window.open(`https://wa.me/${phoneStr}`, '_blank');
            });
        } else {
            $('#wa-call-btn').hide();
        }

        $('#chat-welcome').addClass('hidden');
        $('#chat-box').removeClass('hidden');
        $('#message-input').val('').focus();

        $('.user-item').removeClass('active');
        userElement.addClass('active');

        if (pollingInterval) clearInterval(pollingInterval);

        currentUserId = userId;
        lastMessageCount = 0;
        fetchMessages();
        pollingInterval = setInterval(fetchMessages, 3000);
    }

    $('#user-list').on('click', '.user-item', function() {
        const userId = $(this).data('id');
        setActiveChat(userId);
    });

    $('#send-button').on('click', sendMessage);

    $('#message-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    $('#search-chat').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase();
        $('.user-item').each(function() {
            const name = $(this).data('name');
            if (name.includes(searchValue)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');

        const filterType = $(this).data('filter');

        $('.user-item').each(function() {
            if (filterType === 'all') {
                $(this).show();
            } else if (filterType === 'unread') {
                const isUnread = $(this).data('unread') === true || $(this).data('unread') === 'true';
                if (isUnread) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            } else {
                $(this).hide();
            }
        });

        $('#search-chat').val('');
    });

    const urlParams = new URLSearchParams(window.location.search);
    const chatWithUserId = urlParams.get('chat_with');

    if (chatWithUserId) {
        setActiveChat(chatWithUserId);
         const contactElement = $(`.user-item[data-id="${chatWithUserId}"]`);
         if(contactElement.length) {
              $('#user-list').scrollTop(contactElement.offset().top - $('#user-list').offset().top + $('#user-list').scrollTop() - 50);
         }
    }

    toastr.options = {
        "positionClass": "toast-top-right",
        "progressBar": true,
        "timeOut": "4000",
    };
});
</script>
@endpush
