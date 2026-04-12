{{-- Menggunakan layout customer Anda --}}
@extends('layouts.customer')

@section('title', 'Support Chat')

@push('styles')
{{-- Memuat ikon dan style dasar untuk chat --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    /* === DESAIN BARU UNTUK CUSTOMER === */
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
        height: 85vh;
        width: 100%;
        max-width: 900px;
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
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
    }

    .chat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
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

    /* --- Preview Gambar --- */
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

    /* --- Card Produk --- */
    .chat-product-card {
        border: 1px solid #e9edef; padding: 8px; border-radius: 8px; background: #ffffff; margin-bottom: 5px; min-width: 220px; display: flex; gap: 10px; align-items: center;
    }
    .chat-product-card img { width: 50px; height: 50px; border-radius: 4px; object-fit: cover; }
    .chat-product-info { flex: 1; }
    .chat-product-title { font-size: 12px; font-weight: bold; color: #111b21; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .chat-product-price { font-size: 12px; color: #dc2626; font-weight: bold; margin-top: 2px; }

    /* --- Input Box --- */
    .chat-input-container {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        background-color: var(--header-background);
        border-top: 1px solid var(--border-color);
    }

    .chat-input-container input[type="text"] {
        flex-grow: 1; border: none; padding: 12px 16px; border-radius: 20px;
        outline: none; font-size: 1rem; margin: 0 10px;
    }

    .chat-input-container button {
        background: none; border: none; color: var(--text-secondary);
        font-size: 1.5rem; cursor: pointer; padding: 8px;
    }
    .chat-input-container button:hover { color: #1e2a33; }

    /* --- Indikator Status & Avatar --- */
    .avatar-wrapper { position: relative; display: inline-block; }
    .online-badge { position: absolute; bottom: 2px; right: 0px; width: 12px; height: 12px; background-color: #25D366; border: 2px solid white; border-radius: 50%; }
    .status-text { font-size: 12px; color: #10b981; margin-top: 2px; font-weight: normal; }
    .msg-tick { font-size: 0.7rem; margin-left: 5px; }
    .tick-read { color: #ef4444; }
    .tick-sent { color: #9ca3af; }

    /* --- Gelembung Pesan --- */
    .message-container { display: flex; flex-direction: column; margin-bottom: 8px; max-width: 75%; }
    .message-container.sent { align-self: flex-end; align-items: flex-end; }
    .message-container.received { align-self: flex-start; align-items: flex-start; }
    .message-bubble { padding: 8px 12px; border-radius: 8px; word-wrap: break-word; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05); position: relative; color: var(--text-primary); font-size: 0.95rem; line-height: 1.4; }
    .message-container.sent .message-bubble { background-color: var(--message-sent-background); }
    .message-container.received .message-bubble { background-color: var(--message-received-background); }
    .message-time { font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; padding: 0 4px; }

    .hidden { display: none !important; }
</style>
@endpush

@section('content')
<div class="chat-wrapper">
    <div class="chat-panel">

        <div class="chat-header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="avatar-wrapper">
                    <div style="width: 42px; height: 42px; border-radius: 50%; background-color: #dc2626; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <div id="admin-online-badge" class="online-badge hidden"></div>
                </div>
                <div>
                    <div style="font-size: 16px; font-weight: bold; color: #111b21;">Admin Sancaka</div>
                    <div id="admin-status-text" class="status-text hidden">Online</div>
                </div>
            </div>

            <div>
                <button style="background:none; border:none; color: var(--text-secondary); font-size: 1.2rem; cursor: pointer;">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
            </div>
        </div>

        <div class="chat-messages" id="chat-messages">
            @if ($errors->any())
                <p class="text-center text-red-500 p-4">{{ $errors->first() }}</p>
            @else
                <p class="text-center text-gray-400 p-4">Memuat percakapan...</p>
            @endif
        </div>

        @if (!$errors->any())
            <div id="image-preview-container" class="hidden">
                <div class="preview-box">
                    <img id="image-preview" src="" alt="Preview">
                    <button id="remove-image-btn" title="Hapus Gambar"><i class="fa-solid fa-times"></i></button>
                </div>
            </div>

            <div class="chat-input-container">
                <button title="Emoji"><i class="fa-regular fa-face-smile"></i></button>

                <input type="file" id="image-upload-input" accept="image/png, image/jpeg, image/webp" class="hidden">
                <button id="attachment-btn" title="Kirim Gambar" onclick="document.getElementById('image-upload-input').click()">
                    <i class="fa-solid fa-paperclip"></i>
                </button>

                <input type="text" id="message-input" placeholder="Ketik pesan..." autocomplete="off">
                <button id="send-button" type="submit" title="Kirim Pesan"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        @endif

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
    @if ($errors->any()) return; @endif

    // === VARIABEL GLOBAL ===
    const customerId = {{ auth()->id() }};
    const messagesContainer = $('#chat-messages');
    let lastMessageCount = 0;
    let selectedImageFile = null;
    let isAdminOnline = false; // Penanda status online admin
    const notificationSound = new Audio('{{ asset("sounds/beep.mp3") }}'); // Pastikan file suara ada

    function scrollToBottom() {
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }

    // === FUNGSI PARSER PRODUCT CARD & XSS PROTECTION ===
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
                    <div class="chat-product-card">
                        <img src="${imgUri}">
                        <div class="chat-product-info">
                            <div class="chat-product-title">${lines[1]}</div>
                            <div class="chat-product-price">${lines[2].replace('Harga: ', '')}</div>
                        </div>
                    </div>
                `;
            }
        }
        return safeText.replace(/\n/g, '<br>');
    }

    // === FUNGSI RENDER PESAN (DENGAN GAMBAR & CENTANG) ===
    function createMessageBubble(msg) {
        // Toleransi jika API mereturn format berbeda, pastikan cek isSent
        const isSent = (msg.from_id == customerId) || msg.is_me;
        const messageSide = isSent ? 'sent' : 'received';
        const timeString = moment(msg.created_at).locale('id').format('HH:mm');

        let contentHtml = '';

        // Render Gambar
        if (msg.image_url) {
            let imgPath = msg.image_url.startsWith('http') ? msg.image_url : `/storage/${msg.image_url}`;
            contentHtml += `<img src="${imgPath}" style="max-width: 100%; border-radius: 6px; margin-bottom: 5px; max-height: 250px; object-fit: cover;">`;
        }

        // Render Text/Card
        contentHtml += parseMessage(msg.message);

        // Render Centang (Hanya untuk pesan yang dikirim Customer)
        let tickHtml = '';
        if (isSent) {
            if (msg.is_read || msg.read_at) {
                tickHtml = '<i class="fa-solid fa-check-double msg-tick tick-read"></i>';
            } else if (isAdminOnline) {
                tickHtml = '<i class="fa-solid fa-check-double msg-tick tick-sent"></i>';
            } else {
                tickHtml = '<i class="fa-solid fa-check msg-tick tick-sent"></i>';
            }
        }

        return `
            <div class="message-container ${messageSide}">
                <div class="message-bubble">
                    ${contentHtml}
                    <div class="message-time" style="text-align: ${isSent ? 'right' : 'left'}">${timeString} ${tickHtml}</div>
                </div>
            </div>
        `;
    }

    // === FUNGSI TARIK DATA (POLLING) ===
    function loadMessages() {
        $.ajax({
            url: "{{ route('customer.chat.fetchMessages') }}",
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                // Handle jika response berupa object { messages: [...], admin_online: true } atau langsung array [...]
                let messages = response.messages ? response.messages : response;

                // Set Admin Online Status (Jika API backend mengirimkannya)
                if (response.admin_online !== undefined) {
                    isAdminOnline = response.admin_online;
                    if (isAdminOnline) {
                        $('#admin-online-badge').removeClass('hidden');
                        $('#admin-status-text').removeClass('hidden');
                    } else {
                        $('#admin-online-badge').addClass('hidden');
                        $('#admin-status-text').addClass('hidden');
                    }
                }

                if (messages.length !== lastMessageCount) {
                    // Bunyikan notifikasi jika ada pesan baru dari Admin
                    if (lastMessageCount > 0 && messages.length > 0) {
                        const latestMsg = messages[messages.length - 1];
                        if (latestMsg.from_id != customerId) {
                            notificationSound.play().catch(e => console.log("Notif sound blocked by browser"));
                        }
                    }

                    messagesContainer.html('');
                    if (messages.length === 0) {
                        messagesContainer.html('<p class="text-center text-gray-400 p-4">Belum ada percakapan. Mulai sekarang!</p>');
                    } else {
                        messages.forEach(function(msg) {
                            messagesContainer.append(createMessageBubble(msg));
                        });
                    }
                    scrollToBottom();
                    lastMessageCount = messages.length;
                }
            },
            error: function() {
                console.error("Gagal memuat percakapan.");
            }
        });
    }

    // === LOGIKA UPLOAD GAMBAR ===
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

    // === FUNGSI KIRIM PESAN DENGAN FORMDATA ===
    function sendMessage() {
        const messageInput = $('#message-input');
        const message = messageInput.val().trim();
        const sendButton = $('#send-button');

        // Validasi: Tidak boleh kirim jika teks dan gambar kosong
        if ((!message && !selectedImageFile) || sendButton.prop('disabled')) return;

        sendButton.prop('disabled', true);
        messageInput.prop('disabled', true);

        // Gunakan FormData untuk mengirim Text + File Gambar
        let formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        if (message) formData.append('message', message);
        if (selectedImageFile) formData.append('image', selectedImageFile);

        $.ajax({
            url: "{{ route('customer.chat.sendMessage') }}",
            method: 'POST',
            data: formData,
            processData: false, // Wajib untuk FormData
            contentType: false, // Wajib untuk FormData
            dataType: 'json',
            success: function(response) {
                messageInput.val('');
                selectedImageFile = null;
                $('#image-upload-input').val('');
                $('#image-preview-container').addClass('hidden');

                lastMessageCount = 0; // Trigger render ulang
                loadMessages();
            },
            error: function(jqXHR) {
                toastr.error('Gagal mengirim pesan. Silakan coba lagi.', 'Error');
            },
            complete: function() {
                sendButton.prop('disabled', false);
                messageInput.prop('disabled', false).focus();
            }
        });
    }

    // === EVENT LISTENER ===
    $('#send-button').on('click', sendMessage);
    $('#message-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Setup Toastr
    toastr.options = {
        "positionClass": "toast-top-right",
        "progressBar": true,
        "timeOut": "4000",
    };

    // Jalankan loadMessages pertama kali & Polling setiap 3 detik (Samakan dengan Admin)
    loadMessages();
    setInterval(loadMessages, 3000);
});
</script>
@endpush
