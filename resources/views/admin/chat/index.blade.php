@extends('layouts.admin')

@section('title', 'Chat Pelanggan')
@section('page-title', 'Chat Pelanggan')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    /* === DESAIN BARU (DISAMAKAN DENGAN CUSTOMER) === */
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

    /* --- Sidebar (Daftar Pengguna) --- */
    .user-list {
        width: 30%;
        min-width: 300px;
        max-width: 400px;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        background-color: var(--sidebar-background);
    }
    
    .user-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s;
    }
    
    .user-item:hover {
        background-color: var(--active-chat-background);
    }

    .user-item.active {
        background-color: #e9edef;
    }
    
    .user-item .avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        margin-right: 12px;
        background-color: #667781; /* Warna avatar diubah agar konsisten */
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        flex-shrink: 0;
    }

    .user-details {
        flex-grow: 1;
        overflow: hidden;
    }

    .user-details .font-semibold {
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .user-details .last-message {
        font-size: 0.9rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* --- Area Chat --- */
    .chat-area {
        width: 70%;
        display: flex;
        flex-direction: column;
        background-color: var(--chat-panel-background);
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
    }

    .chat-header {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        background-color: var(--header-background);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-weight: 600;
    }

    .chat-messages {
        flex-grow: 1;
        padding: 20px 5%;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    
    #chat-welcome {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
        color: #54656f;
        background-color: #f0f2f5;
        font-size: 1.1rem;
    }

    .chat-input-container {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        background-color: var(--header-background);
        border-top: 1px solid var(--border-color);
    }
    
    .chat-input-container input {
        flex-grow: 1;
        border: none;
        padding: 12px 16px;
        border-radius: 20px;
        outline: none;
        font-size: 1rem;
        margin: 0 10px;
    }

    .chat-input-container button {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-size: 1.5rem;
        cursor: pointer;
    }

    /* --- Gelembung Pesan --- */
    .message-container {
        display: flex;
        flex-direction: column;
        margin-bottom: 8px;
        max-width: 65%;
    }
    .message-container.sent {
        align-self: flex-end;
    }
    .message-container.received {
        align-self: flex-start;
    }
    .message-bubble {
        padding: 8px 12px;
        border-radius: 8px;
        word-wrap: break-word;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
        position: relative;
        color: var(--text-primary);
    }
    .message-container.sent .message-bubble {
        background-color: var(--message-sent-background);
    }
    .message-container.received .message-bubble {
        background-color: var(--message-received-background);
    }
    .message-time {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-top: 4px;
        padding: 0 4px;
    }
    
    .hidden {
        display: none !important;
    }
</style>
@endpush

@section('content')
<div class="chat-container">
    <!-- Daftar Pengguna -->
    <div class="user-list" id="user-list">
        @forelse ($users as $user)
            <div class="user-item" data-id="{{ $user->id_pengguna }}">
                <div class="avatar">
                    {{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}
                </div>
                <div class="user-details">
                    <p class="font-semibold">{{ $user->nama_lengkap }}</p>
                    <p class="last-message">Klik untuk memulai percakapan...</p>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 20px; color: var(--text-secondary);">Tidak ada pelanggan ditemukan.</div>
        @endforelse
    </div>

    <!-- Area Chat -->
    <div class="chat-area">
        <div id="chat-welcome">
            <p>Pilih pelanggan untuk memulai percakapan.</p>
        </div>
        <div id="chat-box" class="hidden" style="display: flex; flex-direction: column; height: 100%;">
            <div class="chat-header" id="chat-header-name"></div>
            <div class="chat-messages" id="chat-messages"></div>
            <div class="chat-input-container">
                <button><i class="fa-regular fa-face-smile"></i></button>
                <input type="text" id="message-input" placeholder="Ketik pesan...">
                <button id="send-button"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script>
// SINTAKS JAVASCRIPT ANDA TIDAK DIUBAH SAMA SEKALI
$(document).ready(function() {
    let currentUserId = null;
    const adminId = {{ auth()->id() }};
    let pollingInterval = null;
    let lastMessageCount = 0;

    const notificationSound = new Audio('{{ asset("sounds/beep.mp3") }}');

    function loadMessages(userId) {
        currentUserId = userId;
        lastMessageCount = 0;
        const userName = $(`.user-item[data-id=${userId}] .font-semibold`).text();

        $('#chat-header-name').text(`${userName}`);
        $('#chat-welcome').addClass('hidden');
        $('#chat-box').removeClass('hidden');
        $('#message-input').val('').focus();

        $('.user-item').removeClass('active');
        $(`.user-item[data-id=${userId}]`).addClass('active');

        fetchMessages();

        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(fetchMessages, 2000);
    }

    function fetchMessages() {
        if (!currentUserId) return;

        $.ajax({
            url: `/admin/chat/messages/${currentUserId}`,
            method: 'GET',
            success: function(messages) {
                const messagesContainer = $('#chat-messages');

                if (messages.length !== lastMessageCount) {
                    if (lastMessageCount > 0 && messages.length > 0 && messages[messages.length - 1].from_id != adminId) {
                        notificationSound.play().catch(e => console.error("Gagal memutar suara:", e));
                    }

                    messagesContainer.html('');
                    messages.forEach(function(msg) {
                        // Logika Kanan/Kiri: 'sent' untuk admin, 'received' untuk pelanggan
                        const messageSide = msg.from_id == adminId ? 'sent' : 'received';
                        const date = new Date(msg.created_at);
                        const timeString = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                        
                        const messageHtml = `
                            <div class="message-container ${messageSide}">
                                <div class="message-bubble">${msg.message}</div>
                                <div class="message-time">${timeString}</div>
                            </div>
                        `;
                        messagesContainer.append(messageHtml);
                    });
                    
                    messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
                    lastMessageCount = messages.length;
                }
            },
            error: function() {
                console.error('Gagal memuat pesan.');
            }
        });
    }

    function sendMessage() {
        const message = $('#message-input').val();
        if (!message.trim() || !currentUserId) return;

        $.ajax({
            url: `/admin/chat/messages/${currentUserId}`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', message: message },
            success: function(response) {
                $('#message-input').val('');
                fetchMessages();
            },
            error: function(jqXHR) {
                alert('Gagal mengirim pesan. Silakan coba lagi.');
            }
        });
    }

    $('.user-item').on('click', function() { loadMessages($(this).data('id')); });
    $('#send-button').on('click', sendMessage);
    $('#message-input').on('keypress', function(e) { if (e.which === 13) sendMessage(); });
});
</script>
@endpush
