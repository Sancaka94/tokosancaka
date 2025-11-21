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
        height: 85vh; /* Sesuaikan jika perlu */
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
        overflow-y: auto; /* Tambahkan scroll jika daftar panjang */
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
        background-color: #e9edef; /* Warna aktif */
    }

    .user-item .avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        margin-right: 12px;
        background-color: #667781; /* Warna avatar */
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        flex-shrink: 0;
        /* Tambahkan gambar jika ada */
        background-size: cover;
        background-position: center;
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
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); /* Background WA */
        background-repeat: repeat; /* Ulangi background */
    }

    .chat-header {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        background-color: var(--header-background);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-weight: 600;
        flex-shrink: 0; /* Header tidak menyusut */
    }

    .chat-messages {
        flex-grow: 1;
        padding: 20px 5%;
        overflow-y: auto; /* Scroll pesan */
        display: flex;
        flex-direction: column;
    }

    #chat-welcome {
        display: flex;
        flex-direction: column; /* Ubah ke kolom */
        justify-content: center;
        align-items: center;
        height: 100%;
        color: #54656f;
        background-color: #f0f2f5;
        font-size: 1.1rem;
        text-align: center;
    }
     #chat-welcome i { /* Styling ikon */
         font-size: 4rem;
         margin-bottom: 1rem;
         color: #aebac1;
     }

    .chat-input-container {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        background-color: var(--header-background);
        border-top: 1px solid var(--border-color);
        flex-shrink: 0; /* Footer tidak menyusut */
    }

    .chat-input-container input {
        flex-grow: 1;
        border: none;
        padding: 12px 16px;
        border-radius: 20px;
        outline: none;
        font-size: 1rem;
        margin: 0 10px;
        background-color: white; /* Input putih */
    }

    .chat-input-container button {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 8px; /* Beri padding agar mudah diklik */
    }
    .chat-input-container button:hover {
        color: #1e2a33; /* Warna hover */
    }

    /* --- Gelembung Pesan --- */
    .message-container {
        display: flex;
        flex-direction: column;
        margin-bottom: 8px;
        max-width: 65%;
        width: fit-content; /* Lebar sesuai konten */
    }
    .message-container.sent {
        align-self: flex-end; /* Pesan terkirim rata kanan */
    }
    .message-container.received {
        align-self: flex-start; /* Pesan diterima rata kiri */
    }
    .message-bubble {
        padding: 8px 12px;
        border-radius: 8px;
        word-wrap: break-word;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
        position: relative;
        color: var(--text-primary);
        font-size: 0.95rem; /* Ukuran font pesan */
        line-height: 1.4; /* Jarak antar baris */
    }
    .message-container.sent .message-bubble {
        background-color: var(--message-sent-background); /* Warna bubble terkirim */
    }
    .message-container.received .message-bubble {
        background-color: var(--message-received-background); /* Warna bubble diterima */
    }
    .message-time {
        font-size: 0.75rem; /* Waktu lebih kecil */
        color: var(--text-secondary);
        margin-top: 4px;
        padding: 0 4px;
        text-align: right; /* Waktu rata kanan */
    }
    /* Atur waktu agar dibawah bubble */
    .message-container.sent .message-time { text-align: right; }
    .message-container.received .message-time { text-align: left; } /* Atau bisa juga rata kanan */


    .hidden {
        display: none !important;
    }
    /* Styling scrollbar (opsional) */
    .user-list::-webkit-scrollbar, .chat-messages::-webkit-scrollbar { width: 6px; }
    .user-list::-webkit-scrollbar-track, .chat-messages::-webkit-scrollbar-track { background: #f1f1f1; }
    .user-list::-webkit-scrollbar-thumb, .chat-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px;}
    .user-list::-webkit-scrollbar-thumb:hover, .chat-messages::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>
@endpush

@section('content')
<div class="chat-container">
    <!-- Daftar Pengguna -->
    <div class="user-list" id="user-list">
         {{-- Header atau search bar bisa ditambahkan di sini --}}
        @forelse ($users as $user)
            {{-- Ganti avatar dengan gambar jika ada --}}
            @php
                $avatarUrl = $user->profile_photo_url ?? ''; // Asumsi path foto profil
                $initial = strtoupper(substr($user->nama_lengkap ?? 'U', 0, 1));
            @endphp
            <div class="user-item" data-id="{{ $user->getKey() }}" data-name="{{ $user->nama_lengkap }}">
                <div class="avatar" style="{{ $avatarUrl ? 'background-image: url(' . asset($avatarUrl) . '); color: transparent;' : '' }}">
                    {{-- Tampilkan initial jika tidak ada gambar --}}
                    @if(!$avatarUrl)
                        {{ $initial }}
                    @endif
                </div>
                <div class="user-details">
                    <p class="font-semibold">{{ $user->nama_lengkap }}</p>
                    {{-- Pesan terakhir akan diisi oleh JS --}}
                    <p class="last-message" id="last-message-{{ $user->getKey() }}">Klik untuk chat...</p>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 20px; color: var(--text-secondary);">Tidak ada pelanggan ditemukan.</div>
        @endforelse
    </div>

    <!-- Area Chat -->
    <div class="chat-area">
        {{-- Pesan Selamat Datang (Placeholder) --}}
        <div id="chat-welcome" class="chat-messages"> {{-- Jadikan bagian dari messages agar bisa di-replace --}}
             <div>
                <i class="fa-regular fa-comments"></i>
                <p>Pilih pelanggan untuk memulai percakapan.</p>
            </div>
        </div>
        {{-- Box Chat Utama (Awalnya Kosong & Tersembunyi) --}}
        <div id="chat-box" class="hidden" style="display: flex; flex-direction: column; height: 100%;">
            {{-- Header Nama Kontak --}}
            <div class="chat-header" id="chat-header-name"></div>
            {{-- Tempat Pesan --}}
            <div class="chat-messages custom-scrollbar" id="chat-messages"></div>
             {{-- Form Input Pesan --}}
            <div class="chat-input-container">
                <button title="Emoji (fitur belum aktif)"><i class="fa-regular fa-face-smile"></i></button>
                <input type="text" id="message-input" placeholder="Ketik pesan..." autocomplete="off">
                <button id="send-button" type="submit" title="Kirim Pesan"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
{{-- Moment JS tetap direkomendasikan untuk format waktu --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/id.min.js"></script>
{{-- Toastr JS untuk notifikasi --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
$(document).ready(function() {
    let currentUserId = null;
    const adminId = {{ auth()->id() }};
    let pollingInterval = null;
    let lastMessageCount = 0; // Untuk cek pembaruan
    const notificationSound = new Audio('{{ asset("sounds/beep.mp3") }}'); // Pastikan file suara ada

    // Fungsi untuk scroll ke bawah
    function scrollToBottom(containerSelector = '#chat-messages') {
        const container = $(containerSelector);
        if (container.length) {
            container.scrollTop(container[0].scrollHeight);
        }
    }

    // Fungsi menampilkan satu pesan
     function displayMessage(msg) {
        const messageSide = msg.from_id == adminId ? 'sent' : 'received';
        // Format waktu menggunakan Moment.js
        const timeString = moment(msg.created_at).locale('id').format('HH:mm'); // Format jam:menit
        const dateString = moment(msg.created_at).locale('id').format('D MMM'); // Format tanggal singkat (opsional)

        // Escape pesan untuk keamanan
        const escapedMessage = $('<div>').text(msg.message).html();

        const messageHtml = `
            <div class="message-container ${messageSide}">
                <div class="message-bubble">${escapedMessage}</div>
                <div class="message-time">${timeString}</div>
            </div>
        `;
        $('#chat-messages').append(messageHtml);
     }

    // Fungsi memuat history pesan
    function fetchMessages() {
        if (!currentUserId) return;

        console.log(`Fetching messages for user ${currentUserId}...`); // Debug

        $.ajax({
            // Gunakan route yang benar (sesuaikan jika perlu)
            url: `/admin/chat/messages/${currentUserId}`,
            method: 'GET',
            dataType: 'json', // Pastikan response adalah JSON
            success: function(messages) {
                console.log(`Received ${messages.length} messages.`); // Debug
                const messagesContainer = $('#chat-messages');

                // Hanya update jika jumlah pesan berubah (optimasi polling)
                if (messages.length !== lastMessageCount) {
                    console.log("Message count changed, updating UI."); // Debug

                     // Cek jika ada pesan baru dari user lain dan mainkan suara
                    if (lastMessageCount > 0 && messages.length > 0 && messages[messages.length - 1].from_id != adminId && messages.length > lastMessageCount) {
                         console.log("Playing notification sound."); // Debug
                        notificationSound.play().catch(e => console.error("Gagal memutar suara:", e));
                    }

                    messagesContainer.html(''); // Kosongkan chat
                    if (messages.length > 0) {
                        messages.forEach(displayMessage); // Tampilkan setiap pesan
                    } else {
                        messagesContainer.html('<p style="text-align: center; padding: 20px; color: var(--text-secondary);">Belum ada pesan.</p>');
                    }

                    scrollToBottom(); // Scroll ke bawah
                    lastMessageCount = messages.length; // Update jumlah pesan terakhir
                } else {
                    // console.log("No new messages."); // Debug (jika tidak ada perubahan)
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Gagal memuat pesan:', textStatus, errorThrown, jqXHR.responseText);
                $('#chat-messages').html('<p style="text-align: center; padding: 20px; color: red;">Gagal memuat pesan.</p>');
                // Hentikan polling jika error?
                // if (pollingInterval) clearInterval(pollingInterval);
            }
        });
    }

    // Fungsi mengirim pesan
    function sendMessage() {
        const messageInput = $('#message-input');
        const message = messageInput.val().trim(); // Ambil & trim pesan
        const sendButton = $('#send-button');

        if (!message || !currentUserId || sendButton.prop('disabled')) return; // Validasi

        console.log(`Sending message to user ${currentUserId}: ${message}`); // Debug

        sendButton.prop('disabled', true); // Nonaktifkan tombol
        messageInput.prop('disabled', true); // Nonaktifkan input

        $.ajax({
            // Gunakan route yang benar (sesuaikan jika perlu)
            url: `/admin/chat/messages/${currentUserId}`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                message: message
            },
            dataType: 'json',
            success: function(response) {
                console.log("Send message response:", response); // Debug
                if (response.status === 'Pesan terkirim!' && response.message) {
                    messageInput.val(''); // Kosongkan input
                    // Tampilkan pesan baru SEGERA (optimistic update)
                    // displayMessage(response.message); // response.message adalah data pesan dari DB
                    // scrollToBottom();
                    // fetchMessages(); // Panggil fetchMessages untuk memastikan sinkron & update waktu
                    lastMessageCount = 0; // Reset count agar fetchMessages berikutnya pasti update
                    fetchMessages(); // Trigger fetch untuk update
                } else {
                     toastr.error(response.status || 'Gagal mengirim pesan (status tidak dikenal).', 'Error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 console.error('Gagal mengirim pesan:', textStatus, errorThrown, jqXHR.responseText);
                 // Coba tampilkan pesan error dari server jika ada
                 let errorMsg = 'Gagal mengirim pesan. Silakan coba lagi.';
                 if(jqXHR.responseJSON && jqXHR.responseJSON.message) {
                     errorMsg = jqXHR.responseJSON.message;
                 } else if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
                      errorMsg = jqXHR.responseJSON.error;
                 }
                 toastr.error(errorMsg, 'Error Server');
            },
            complete: function() {
                sendButton.prop('disabled', false); // Aktifkan tombol kembali
                messageInput.prop('disabled', false).focus(); // Aktifkan input & fokus
            }
        });
    }

    // Fungsi untuk menginisialisasi atau mengubah chat aktif
     function setActiveChat(userId) {
        const userElement = $(`.user-item[data-id=${userId}]`);
        if (!userElement.length) return; // User tidak ada di daftar

        const userName = userElement.data('name');

        if (userId === currentUserId && !$('#chat-box').hasClass('hidden')) {
             console.log(`Chat with user ${userId} is already active.`); // Debug
            return; // Chat sudah aktif
        }

        console.log(`Activating chat for user ${userId} (${userName})`); // Debug

        $('#chat-header-name').text(userName); // Set nama di header
        $('#chat-welcome').addClass('hidden'); // Sembunyikan pesan welcome
        $('#chat-box').removeClass('hidden'); // Tampilkan box chat utama
        $('#message-input').val('').focus(); // Kosongkan & fokus input

        // Update highlight di daftar kontak
        $('.user-item').removeClass('active');
        userElement.addClass('active');

        // Hentikan polling lama (jika ada)
        if (pollingInterval) clearInterval(pollingInterval);

        // Set user ID aktif dan mulai load + polling baru
        currentUserId = userId;
        lastMessageCount = 0; // Reset count agar fetch pertama selalu jalan
        fetchMessages(); // Langsung fetch pesan pertama kali
        pollingInterval = setInterval(fetchMessages, 3000); // Mulai polling setiap 3 detik
     }


    // --- Event Listeners ---
    // Klik pada item user di sidebar
    $('#user-list').on('click', '.user-item', function() {
        const userId = $(this).data('id');
        setActiveChat(userId);
         // Update URL (opsional, untuk bookmark/refresh)
         // history.pushState(null, '', `{{ route('admin.chat.index') }}?chat_with=${userId}`);
    });

    // Klik tombol kirim
    $('#send-button').on('click', sendMessage);

    // Tekan Enter di input pesan
    $('#message-input').on('keypress', function(e) {
        // Cek jika tombol yang ditekan adalah Enter (kode 13)
        if (e.which === 13 && !e.shiftKey) { // Kirim jika Enter ditekan (bukan Shift+Enter)
            e.preventDefault(); // Mencegah baris baru di input
            sendMessage();
        }
    });

    // --- Logika Awal Saat Halaman Dimuat ---
    // Cek apakah ada parameter 'chat_with' di URL
    const urlParams = new URLSearchParams(window.location.search);
    const chatWithUserId = urlParams.get('chat_with');

    if (chatWithUserId) {
        console.log("Found 'chat_with' parameter:", chatWithUserId);
        // Coba aktifkan chat untuk user tersebut
        setActiveChat(chatWithUserId);
         // Scroll kontak ke view (jika perlu)
         const contactElement = $(`.user-item[data-id="${chatWithUserId}"]`);
         if(contactElement.length) {
              $('#user-list').scrollTop(contactElement.offset().top - $('#user-list').offset().top + $('#user-list').scrollTop() - 50);
         }
    } else {
        console.log("No 'chat_with' parameter. Waiting for user selection.");
        // Biarkan #chat-welcome tampil
    }

    // Konfigurasi Toastr
    toastr.options = {
        "positionClass": "toast-top-right",
        "progressBar": true,
        "timeOut": "4000",
    };

});
</script>
@endpush

