{{-- Menggunakan layout customer Anda --}}
@extends('layouts.customer')

@section('title', 'Support Chat')

@push('styles')
{{-- Memuat ikon dan style dasar untuk chat --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    /* === DESAIN BARU UNTU-K CUSTOMER === */
    :root {
        --background-color: #f0f2f5;
        --chat-panel-background: #e5ddd5;
        --header-background: #f0f2f5;
        --message-sent-background: #dcf8c6;
        --message-received-background: #fff;
        --text-primary: #111b21;
        --text-secondary: #667781;
        --border-color: #e9edef;
    }

    /* Kontainer utama yang mengisi ruang layout */
    .chat-wrapper {
        display: flex;
        flex-direction: column;
        height: 85vh; /* Sesuaikan tinggi ini jika perlu */
        width: 100%;
        max-width: 900px; /* Lebar maksimal untuk tampilan desktop */
        margin: auto;
        background-color: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    /* --- Area Chat --- */
    .chat-panel {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        background-color: var(--chat-panel-background);
        /* PERUBAHAN: Mengembalikan URL gambar latar belakang ke versi asli */
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
        font-size: 1.1rem;
    }

    .chat-messages {
        flex-grow: 1;
        padding: 20px 5%;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
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
        max-width: 75%; /* Pesan bisa sedikit lebih lebar di tampilan customer */
    }
    .message-container.sent {
        align-self: flex-end; /* KANAN untuk user aktif */
        align-items: flex-end;
    }
    .message-container.received {
        align-self: flex-start; /* KIRI untuk admin */
        align-items: flex-start;
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
</style>
@endpush

@section('content')
<div class="chat-wrapper">
    <div class="chat-panel">
        <!-- Header Chat -->
        <div class="chat-header">
            Chat dengan Admin Sancaka
        </div>

        <!-- Area Pesan -->
        <div class="chat-messages" id="chat-messages">
            @if ($errors->any())
                <p class="text-center text-red-500 p-4">{{ $errors->first() }}</p>
            @else
                <p class="text-center text-gray-400 p-4">Memuat percakapan...</p>
            @endif
        </div>

        <!-- Input Pesan -->
        @if (!$errors->any())
        <div class="chat-input-container">
            <button><i class="fa-regular fa-face-smile"></i></button>
            <input type="text" id="message-input" placeholder="Ketik pesan...">
            <button id="send-button"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script>
$(document).ready(function() {
    @if ($errors->any())
        return;
    @endif

    const customerId = {{ auth()->id() }};
    const messagesContainer = $('#chat-messages');

    function createMessageBubble(message, timeString, isSent) {
        const messageSide = isSent ? 'sent' : 'received';
        const bubble = `
            <div class="message-container ${messageSide}">
                <div class="message-bubble">${message}</div>
                <div class="message-time">${timeString}</div>
            </div>
        `;
        return bubble;
    }

    function loadMessages() {
        $.ajax({
            url: "{{ route('customer.chat.fetchMessages') }}",
            method: 'GET',
            success: function(messages) {
                const lastMessageCount = messagesContainer.children().length;
                if (messages.length !== lastMessageCount) {
                    messagesContainer.html('');
                    if (messages.length === 0) {
                        messagesContainer.html('<p class="text-center text-gray-400 p-4">Belum ada percakapan. Mulai sekarang!</p>');
                    } else {
                        messages.forEach(function(msg) {
                            // --- PERBAIKAN DI SINI ---
                            // Menggunakan '==' (loose equality) untuk membandingkan nilai tanpa memperdulikan tipe data (string vs number)
                            const isSent = msg.from_id == customerId;

                            const date = new Date(msg.created_at);
                            const timeString = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                            messagesContainer.append(createMessageBubble(msg.message, timeString, isSent));
                        });
                    }
                    messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
                }
            },
            error: function() {
                messagesContainer.html('<p class="text-center text-red-500 p-4">Gagal memuat percakapan.</p>');
            }
        });
    }

    function sendMessage() {
        const message = $('#message-input').val();
        if (!message.trim()) return;

        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        messagesContainer.append(createMessageBubble(message, timeString, true));
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        $('#message-input').val('');

        $.ajax({
            url: "{{ route('customer.chat.sendMessage') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                message: message
            },
            error: function() {
                alert('Gagal mengirim pesan. Silakan muat ulang halaman.');
                loadMessages();
            }
        });
    }

    $('#send-button').on('click', sendMessage);
    $('#message-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    loadMessages();
    setInterval(loadMessages, 1000);
});
</script>
@endpush
