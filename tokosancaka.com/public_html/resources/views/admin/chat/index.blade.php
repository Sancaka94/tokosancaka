@extends('layouts.admin')

@section('title', 'Chat Pelanggan')
@section('page-title', 'Chat Pelanggan')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    /* === DESAIN BARU === */
    :root {
        --background-color: #f0f2f5;
        --sidebar-background: #fff;
        --chat-panel-background: #e5ddd5;
        --header-background: #f0f2f5;
        --message-sent-background: #dcf8c6;
        --message-received-background: #fff;
        --text-primary: #111b21;
        --text-secondary: #667781;
        --border-color: #e9edef;
        --active-chat-background: #f5f5f5;
    }

    .chat-container {
        display: flex;
        height: 85vh;
        width: 100%;
        max-width: 1600px;
        margin: auto;
        background-color: var(--sidebar-background);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    /* --- Sidebar --- */
    .user-list {
        width: 30%;
        min-width: 300px;
        max-width: 400px;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        background-color: var(--sidebar-background);
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

    .user-item:hover { background-color: var(--active-chat-background); }
    .user-item.active { background-color: #e9edef; }

    .user-item .avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        margin-right: 12px;
        background-color: #667781;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        flex-shrink: 0;
        background-size: cover;
        background-position: center;
    }

    .user-details { flex-grow: 1; overflow: hidden; }
    .user-details .font-semibold { font-weight: 600; color: var(--text-primary); }
    .user-details .last-message { font-size: 0.9rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* --- Area Chat --- */
    .chat-area {
        width: 70%;
        display: flex;
        flex-direction: column;
        background-color: var(--chat-panel-background);
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        background-repeat: repeat;
    }

    .chat-header {
        display: flex; align-items: center; padding: 10px 16px;
        background-color: var(--header-background);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary); font-weight: 600; flex-shrink: 0;
    }

    .chat-messages { flex-grow: 1; padding: 20px 5%; overflow-y: auto; display: flex; flex-direction: column; }

    #chat-welcome { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100%; color: #54656f; background-color: #f0f2f5; font-size: 1.1rem; text-align: center; }
    #chat-welcome i { font-size: 4rem; margin-bottom: 1rem; color: #aebac1; }

    /* === KODE BARU: PREVIEW GAMBAR & PRODUCT CARD === */
    #image-preview-container {
        padding: 10px 16px;
        background-color: var(--header-background);
        border-top: 1px solid var(--border-color);
        display: flex; align-items: center; gap: 15px;
    }
    .preview-box { position: relative; display: inline-block; }
    .preview-box img { height: 80px; width: 80px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border-color); }
    .preview-box button {
        position: absolute; top: -8px; right: -8px; background: #ef4444; color: white;
        border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer;
    }

    .chat-product-card {
        border: 1px solid #e9edef; padding: 8px; border-radius: 8px; background: #ffffff; margin-bottom: 5px; min-width: 220px; display: flex; gap: 10px; align-items: center;
    }
    .chat-product-card img { width: 50px; height: 50px; border-radius: 4px; object-fit: cover; }
    .chat-product-info { flex: 1; }
    .chat-product-title { font-size: 12px; font-weight: bold; color: #111b21; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .chat-product-price { font-size: 12px; color: #dc2626; font-weight: bold; margin-top: 2px; }
    /* =============================================== */

    .chat-input-container {
        display: flex; align-items: center; padding: 10px 16px;
        background-color: var(--header-background);
        border-top: 1px solid var(--border-color); flex-shrink: 0;
    }

    .chat-input-container input[type="text"] {
        flex-grow: 1; border: none; padding: 12px 16px; border-radius: 20px;
        outline: none; font-size: 1rem; margin: 0 10px; background-color: white;
    }

    .chat-input-container button {
        background: none; border: none; color: var(--text-secondary);
        font-size: 1.5rem; cursor: pointer; padding: 8px;
    }

    /* === CSS INDIKATOR ONLINE & CENTANG === */
    .avatar-wrapper {
        position: relative;
        display: inline-block;
    }
    .online-badge {
        position: absolute;
        bottom: 2px;
        right: 0px;
        width: 12px;
        height: 12px;
        background-color: #25D366; /* Hijau WhatsApp */
        border: 2px solid white;
        border-radius: 50%;
    }
    .status-text {
        font-size: 12px;
        color: #10b981; /* Hijau teks */
        margin-top: 2px;
    }
    .msg-tick {
        font-size: 0.7rem;
        margin-left: 5px;
    }
    .tick-read { color: #ef4444; } /* Merah */
    .tick-sent { color: #9ca3af; } /* Abu-abu */

    .chat-input-container button:hover { color: #1e2a33; }

    .message-container { display: flex; flex-direction: column; margin-bottom: 8px; max-width: 65%; width: fit-content; }
    .message-container.sent { align-self: flex-end; }
    .message-container.received { align-self: flex-start; }
    .message-bubble { padding: 8px 12px; border-radius: 8px; word-wrap: break-word; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05); position: relative; color: var(--text-primary); font-size: 0.95rem; line-height: 1.4; }
    .message-container.sent .message-bubble { background-color: var(--message-sent-background); }
    .message-container.received .message-bubble { background-color: var(--message-received-background); }
    .message-time { font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; padding: 0 4px; text-align: right; }
    .message-container.sent .message-time { text-align: right; }
    .message-container.received .message-time { text-align: left; }

    /* --- CSS SCROLLBAR BARU --- */
    /* Berikan margin nol agar mepet kanan */
    .user-list::-webkit-scrollbar { width: 6px; }
    .user-list::-webkit-scrollbar-track { background: transparent; } /* Transparan agar tidak terlihat kotak putih */
    .user-list::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    .user-list::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

    .chat-messages::-webkit-scrollbar { width: 6px; }
    .chat-messages::-webkit-scrollbar-track { background: transparent; }
    .chat-messages::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 3px; }
</style>
@endpush

@section('content')
<div class="chat-container">

    <div class="sidebar" style="width: 30%; min-width: 320px; max-width: 420px; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; background-color: white; z-index: 10;">

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; background-color: var(--header-background); border-bottom: 1px solid var(--border-color);">
            <h1 style="font-size: 20px; font-weight: bold; color: var(--text-primary); margin: 0;">Chats</h1>
            <div style="display: flex; gap: 20px; color: #54656f; font-size: 1.2rem;">
                <button title="New Chat" style="background:none; border:none; cursor:pointer; color:inherit;"><i class="fa-solid fa-pen-to-square"></i></button>
                <button title="Menu" style="background:none; border:none; cursor:pointer; color:inherit;"><i class="fa-solid fa-ellipsis-vertical"></i></button>
            </div>
        </div>

        <div style="padding: 10px 14px; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; background-color: var(--header-background); border-radius: 8px; padding: 6px 12px;">
                <i class="fa-solid fa-magnifying-glass" style="color: #8696a0; font-size: 14px; margin-right: 15px;"></i>
                <input type="text" id="search-chat" placeholder="Search or start a new chat" style="border: none; background: transparent; width: 100%; outline: none; font-size: 14px; color: var(--text-primary);">
            </div>
            <div style="display: flex; gap: 8px; margin-top: 12px; overflow-x: auto;">
                <button class="filter-btn active" data-filter="all" style="background: #e2e8f0; border: none; padding: 6px 14px; border-radius: 20px; font-size: 13px; color: #111b21; cursor: pointer;">All</button>
                <button class="filter-btn" data-filter="unread" style="background: var(--header-background); border: none; padding: 6px 14px; border-radius: 20px; font-size: 13px; color: #54656f; cursor: pointer;">Unread</button>
                <button class="filter-btn" data-filter="groups" style="background: var(--header-background); border: none; padding: 6px 14px; border-radius: 20px; font-size: 13px; color: #54656f; cursor: pointer;">Groups</button>
            </div>
        </div>

        <div class="user-list" id="user-list" style="flex-grow: 1; overflow-y: auto;">
            @forelse ($users as $user)
                @php
                    $avatarUrl = $user->store_logo_path ?? '';
                    $initial = strtoupper(substr($user->nama_lengkap ?? 'U', 0, 1));
                    $finalAvatarUrl = $avatarUrl ? (str_starts_with($avatarUrl, 'http') ? $avatarUrl : asset('storage/' . $avatarUrl)) : '';

                    // Logika Online
                    $isOnline = $user->last_seen && \Carbon\Carbon::parse($user->last_seen)->diffInMinutes(now()) < 5;

                    // Logika Pesan Terakhir
                    $lastMsg = $user->last_message_data;
                    $msgText = 'Belum ada pesan...';
                    $timeText = '';
                    $tickHtml = '';
                    $unreadCount = $user->unread_count ?? 0;

                    if ($lastMsg) {
                        // Teks Pesan (Jika format produk, ubah jadi teks singkat)
                        if (str_starts_with($lastMsg->message, '[TANYA PRODUK]')) {
                            $msgText = '📦 Bertanya tentang produk';
                        } elseif ($lastMsg->image_url && !$lastMsg->message) {
                            $msgText = '📷 Mengirim gambar';
                        } else {
                            $msgText = $lastMsg->message;
                        }

                        // Format Waktu (Hari ini = Jam, Kemarin = Yesterday, Lebih Lama = Tanggal)
                        $msgDate = \Carbon\Carbon::parse($lastMsg->created_at);
                        if ($msgDate->isToday()) {
                            $timeText = $msgDate->format('H:i');
                        } elseif ($msgDate->isYesterday()) {
                            $timeText = 'Yesterday';
                        } else {
                            $timeText = $msgDate->format('d/m/Y');
                        }

                        // Logika Centang jika pengirim adalah Admin
                        if ($lastMsg->from_id == auth()->id()) {
                            if ($lastMsg->read_at) {
                                // Centang Biru/Merah (Sudah dibaca)
                                $tickHtml = '<i class="fa-solid fa-check-double" style="color: #3b82f6; font-size: 11px; margin-right: 4px;"></i>';
                            } else {
                                // Centang Abu-abu
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
                     data-online="{{ $isOnline ? 'true' : 'false' }}"
                     style="display: flex; align-items: center; padding: 12px 16px; cursor: pointer; border-bottom: 1px solid var(--border-color);">

                    <div class="avatar-wrapper" style="margin-right: 15px; position: relative;">
                        <div class="avatar" style="width: 48px; height: 48px; border-radius: 50%; background-color: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b; background-size: cover; background-position: center; {{ $finalAvatarUrl ? 'background-image: url(' . $finalAvatarUrl . '); color: transparent;' : '' }}">
                            @if(!$finalAvatarUrl) {{ $initial }} @endif
                        </div>
                        @if($isOnline)
                            <div class="online-badge" style="position: absolute; bottom: 2px; right: 0; width: 12px; height: 12px; background: #25D366; border: 2px solid white; border-radius: 50%;"></div>
                        @endif
                    </div>

                    <div class="user-details" style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                            <p class="font-semibold" style="font-size: 16px; color: #111b21; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->nama_lengkap }}</p>
                            <span style="font-size: 12px; color: {{ $unreadCount > 0 ? '#25D366' : '#667781' }}; font-weight: {{ $unreadCount > 0 ? 'bold' : 'normal' }};">{{ $timeText }}</span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; flex: 1; min-width: 0; padding-right: 10px;">
                                {!! $tickHtml !!}
                                <p class="last-message" style="margin: 0; font-size: 13px; color: #667781; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $msgText }}</p>
                            </div>

                            @if($unreadCount > 0)
                                <div style="background-color: #25D366; color: white; font-size: 11px; font-weight: bold; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    {{ $unreadCount }}
                                </div>
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

        <div id="chat-welcome" class="chat-messages">
             <div>
                <i class="fa-regular fa-comments"></i>
                <p>Pilih pelanggan untuk memulai percakapan.</p>
            </div>
        </div>

        <div id="chat-box" class="hidden" style="display: flex; flex-direction: column; height: 100%;">

            <div class="chat-header" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="avatar-wrapper">
                        <img id="header-avatar-img" src="" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; display: none;">
                        <div id="header-avatar-initial" style="width: 42px; height: 42px; border-radius: 50%; background-color: #667781; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;"></div>
                        <div id="header-online-badge" class="online-badge hidden"></div>
                    </div>

                    <div>
                        <div id="chat-header-name" style="font-size: 16px; font-weight: bold; color: #111b21;">Nama Pelanggan</div>
                        <div id="chat-header-status" class="status-text hidden">Online</div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 20px; position: relative;">
                    <button id="wa-call-btn" title="Hubungi via WhatsApp" style="background:none; border:none; cursor:pointer; font-size: 1.4rem; display: none;">
                        <i class="fa-brands fa-whatsapp" style="color: #25D366;"></i>
                    </button>
                    <button id="chat-options-btn" style="background:none; border:none; color: var(--text-secondary); cursor:pointer; font-size: 1.3rem;">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <div id="chat-options-menu" class="hidden" style="position: absolute; right: 0; top: 40px; background: white; border: 1px solid #e9edef; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 5px 0; min-width: 200px; z-index: 1000;">
                        <button id="delete-chat-btn" style="width: 100%; text-align: left; padding: 12px 15px; background: none; border: none; color: #ef4444; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 14px;">
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
                <button title="Emoji (fitur belum aktif)"><i class="fa-regular fa-face-smile"></i></button>

                <input type="file" id="image-upload-input" accept="image/png, image/jpeg, image/webp" class="hidden">
                <button id="attachment-btn" title="Kirim Gambar" onclick="document.getElementById('image-upload-input').click()">
                    <i class="fa-solid fa-paperclip"></i>
                </button>
                <input type="text" id="message-input" placeholder="Ketik pesan..." autocomplete="off">
                <button id="send-button" type="submit" title="Kirim Pesan"><i class="fa-solid fa-paper-plane"></i></button>
            </div>

        </div> </div> </div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/id.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
$(document).ready(function() {
    // === 1. VARIABEL GLOBAL ===
    let currentUserId = null;
    const adminId = {{ auth()->id() }};
    let pollingInterval = null;
    let lastMessageCount = 0;
    let selectedImageFile = null;
    let isCurrentChatOnline = false; // Penanda status online untuk logika centang
    const notificationSound = new Audio('{{ asset("sounds/beep.mp3") }}');

    // === 2. FUNGSI SCROLL ===
    function scrollToBottom(containerSelector = '#chat-messages') {
        const container = $(containerSelector);
        if (container.length) {
            container.scrollTop(container[0].scrollHeight);
        }
    }

    // === 3. FUNGSI PARSER PRODUCT CARD & XSS PROTECTION ===
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

    // === 4. FUNGSI RENDER PESAN (DENGAN LOGIKA CENTANG) ===
    function displayMessage(msg) {
        const messageSide = msg.from_id == adminId ? 'sent' : 'received';
        const timeString = moment(msg.created_at).locale('id').format('HH:mm');

        let contentHtml = '';

        if (msg.image_url) {
            let imgPath = msg.image_url.startsWith('http') ? msg.image_url : `/storage/${msg.image_url}`;
            contentHtml += `<img src="${imgPath}" style="max-width: 100%; border-radius: 6px; margin-bottom: 5px; max-height: 250px; object-fit: cover;">`;
        }

        contentHtml += parseMessage(msg.message);

        // --- Logika Centang ---
        let tickHtml = '';
        if (msg.is_me) { // Centang hanya untuk pesan yang dikirim Admin
            if (msg.is_read) {
                // Centang 2 Merah
                tickHtml = '<i class="fa-solid fa-check-double" style="font-size: 0.7rem; margin-left: 5px; color: #ef4444;"></i>';
            } else if (isCurrentChatOnline) {
                // Centang 2 Abu-abu (Terkirim & User Online)
                tickHtml = '<i class="fa-solid fa-check-double" style="font-size: 0.7rem; margin-left: 5px; color: #9ca3af;"></i>';
            } else {
                // Centang 1 Abu-abu (Terkirim, User Offline)
                tickHtml = '<i class="fa-solid fa-check" style="font-size: 0.7rem; margin-left: 5px; color: #9ca3af;"></i>';
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

    // === 5. FUNGSI TARIK DATA (POLLING) ===
    function fetchMessages() {
        if (!currentUserId) return;

        $.ajax({
            url: `/admin/chat/messages/${currentUserId}`,
            method: 'GET',
            dataType: 'json',
            success: function(messages) {
                const messagesContainer = $('#chat-messages');

                if (messages.length !== lastMessageCount) {
                    if (lastMessageCount > 0 && messages.length > 0 && messages[messages.length - 1].from_id != adminId && messages.length > lastMessageCount) {
                        notificationSound.play().catch(e => console.error("Gagal memutar suara:", e));
                    }

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

    // === 6. LOGIKA PREVIEW GAMBAR ===
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

    // === 7. FUNGSI KIRIM PESAN ===
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

    // === 8. LOGIKA MENU OPSI (TITIK TIGA) ===
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

    // === 9. FUNGSI AKTIVASI CHAT (AVATAR, WA, ONLINE STATUS) ===
    function setActiveChat(userId) {
        const userElement = $(`.user-item[data-id=${userId}]`);
        if (!userElement.length) return;

        const userName = userElement.data('name');
        const userAvatar = userElement.data('avatar');
        const userPhone = userElement.data('phone');

        // Baca status online dari HTML
        const isOnline = userElement.data('online') === true || userElement.data('online') === 'true';
        isCurrentChatOnline = isOnline;

        if (userId === currentUserId && !$('#chat-box').hasClass('hidden')) {
            return;
        }

        // --- Set Header Kiri ---
        $('#chat-header-name').text(userName);

        if (userAvatar && userAvatar !== '') {
            $('#header-avatar-img').attr('src', userAvatar).show();
            $('#header-avatar-initial').hide();
        } else {
            $('#header-avatar-img').hide();
            $('#header-avatar-initial').text(userName.charAt(0).toUpperCase()).show();
        }

        // Tampilkan/Sembunyikan Badge & Teks Online
        if (isOnline) {
            $('#header-online-badge').removeClass('hidden');
            $('#chat-header-status').removeClass('hidden');
        } else {
            $('#header-online-badge').addClass('hidden');
            $('#chat-header-status').addClass('hidden');
        }

        // --- Set Header Kanan (WA) ---
        if (userPhone && userPhone !== '') {
            let phoneStr = String(userPhone).replace(/[-+ \s]/g, '');
            if (phoneStr.startsWith('0')) phoneStr = '62' + phoneStr.substring(1);
            $('#wa-call-btn').show().off('click').on('click', function() {
                window.open(`https://wa.me/${phoneStr}`, '_blank');
            });
        } else {
            $('#wa-call-btn').hide();
        }

        // --- Tampilkan Chat Area ---
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

    // === 10. EVENT LISTENERS UTAMA ===
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

    // === 11. FITUR PENCARIAN (SEARCH BAR) ===
    $('#search-chat').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase();
        $('.user-item').each(function() {
            // Ambil nama dari atribut data-name yang sudah kita buat huruf kecil semua
            const name = $(this).data('name');
            if (name.includes(searchValue)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // === 12. FITUR FILTER (ALL / UNREAD) ===
    $('.filter-btn').on('click', function() {
        // Ganti styling tombol aktif
        $('.filter-btn').css({ 'background': 'var(--header-background)', 'color': '#54656f' }).removeClass('active');
        $(this).css({ 'background': '#e2e8f0', 'color': '#111b21' }).addClass('active');

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
                // Untuk tombol Groups, bisa disembunyikan semua dulu jika belum ada fitur grup
                $(this).hide();
            }
        });

        // Bersihkan kotak pencarian jika filter diklik
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
