@extends('layouts.admin')

@section('content')

<audio id="wa-notification-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

<style>
    /* --- Layout Utama --- */
    .wa-wrapper {
        display: flex;
        height: 85vh;
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: 1px solid #d1d7db;
        position: relative;
    }

    /* --- SIDEBAR KIRI --- */
    .wa-sidebar {
        width: 350px;
        background-color: #fff;
        border-right: 1px solid #e9edef;
        display: flex;
        flex-direction: column;
    }

    .wa-sidebar-header {
        background-color: #f0f2f5;
        padding: 10px 16px;
        height: 60px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #e9edef;
        justify-content: space-between;
    }

    .wa-contact-list {
        flex: 1;
        overflow-y: auto;
        background-color: #fff;
    }

    .wa-contact-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #f5f6f6;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        color: inherit;
    }

    .wa-contact-item:hover { background-color: #f5f6f6; }
    .wa-contact-item.active { background-color: #f0f2f5; }

    .wa-avatar {
        width: 49px; height: 49px;
        border-radius: 50%;
        flex-shrink: 0;
        overflow: hidden;
        margin-right: 15px;
        background-color: #dfe5e7;
    }
    .wa-avatar img {
        width: 100%; height: 100%; object-fit: cover;
    }

    .wa-contact-info {
        flex: 1;
        overflow: hidden;
    }

    .wa-contact-top {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;
    }

    .wa-name { font-size: 16px; font-weight: 500; color: #111b21; }
    .wa-time { font-size: 12px; color: #667781; }
    .wa-number { font-size: 13px; color: #667781; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }

    /* --- AREA CHAT KANAN --- */
    .wa-chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background-color: #efe7dd;
        /* Hapus background logo WA, ganti pattern halus atau warna solid */
        background-image: none; 
        background: #e5ddd5;
    }

    /* Header Chat */
    .wa-chat-header {
        height: 60px;
        background-color: #f0f2f5;
        padding: 10px 16px;
        display: flex; align-items: center;
        border-bottom: 1px solid #d1d7db;
        z-index: 10;
    }

    /* Kotak Pesan */
    .wa-messages-box {
        flex: 1;
        padding: 20px 40px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* Bubble Chat Base */
    .wa-bubble {
        max-width: 65%;
        padding: 6px 7px 8px 9px;
        border-radius: 7.5px;
        font-size: 14.2px;
        line-height: 19px;
        position: relative;
        box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
        word-wrap: break-word;
    }

    /* Incoming (Customer - Kiri - Putih) */
    .wa-bubble.incoming {
        align-self: flex-start;
        background-color: #ffffff;
        border-top-left-radius: 0;
    }

    /* Outgoing (Admin - Kanan - Hijau) */
    .wa-bubble.outgoing {
        align-self: flex-end;
        background-color: #d9fdd3;
        border-top-right-radius: 0;
    }

    /* Meta Info */
    .wa-meta {
        float: right;
        margin-top: 4px;
        margin-left: 10px;
        font-size: 11px;
        color: #667781;
        display: flex; align-items: center; gap: 3px;
        position: relative; top: 4px;
    }

    /* Input Area - LEBAR FIXED */
    .wa-input-area {
        min-height: 62px;
        background-color: #f0f2f5;
        padding: 10px 16px;
        display: flex; align-items: center;
        gap: 10px;
        width: 100%; /* Pastikan full width */
    }

    /* Form wrapper untuk input */
    .wa-form {
        display: flex;
        width: 100%;
        align-items: center;
        gap: 10px;
    }

    .wa-input {
        flex: 1; /* Mengisi sisa ruang */
        width: 100%; /* Memaksa lebar penuh di dalam flex parent */
        padding: 9px 12px;
        border-radius: 8px;
        border: 1px solid #fff;
        background-color: #fff;
        outline: none;
        font-size: 15px;
        resize: none;
        height: 40px;
        overflow-y: hidden;
    }
    
    .btn-send {
        background: transparent; border: none; font-size: 24px; color: #54656f; cursor: pointer;
        padding: 0 10px;
    }
    .btn-send:hover { color: #00a884; }

    /* Flash Message Toast (Pojok Kanan Atas) */
    .flash-toast {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 100;
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        animation: fadeOut 4s forwards;
    }
    .flash-success { background-color: #25D366; }
    .flash-error { background-color: #dc3545; }

    @keyframes fadeOut {
        0% { opacity: 1; }
        70% { opacity: 1; }
        100% { opacity: 0; display: none; }
    }

    /* Scrollbar Halus */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 3px; }
</style>

<div class="container-fluid py-3">
    
    @if(session('success'))
        <div class="flash-toast flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-toast flash-error">{{ session('error') }}</div>
    @endif

    <div class="wa-wrapper">
        
        <div class="wa-sidebar">
            <div class="wa-sidebar-header">
                <div class="d-flex align-items-center">
                    <div class="wa-avatar">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=0D8ABC&color=fff" alt="Admin">
                    </div>
                    <h6 class="m-0 ms-2 text-dark">Chat Admin</h6>
                </div>
                <div>
                    <span id="connection-status" class="badge bg-success" style="font-size: 10px;">Connected</span>
                </div>
            </div>

            <div class="wa-contact-list">
                @foreach($contacts as $contact)
                    @php
                        $isActive = ($activePhone == $contact->sender_number);
                        // LOGIC: Jika nama kosong atau 'Unknown' atau 'unknown', pakai nomor HP
                        $displayName = (!empty($contact->sender_name) && strtolower($contact->sender_name) !== 'unknown') 
                                        ? $contact->sender_name 
                                        : $contact->sender_number;
                    @endphp
                    <a href="{{ route('whatsapp.index', ['phone' => $contact->sender_number]) }}" class="wa-contact-item {{ $isActive ? 'active' : '' }}">
                        <div class="wa-avatar">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($displayName) }}&background=random&color=fff" alt="Profile">
                        </div>
                        <div class="wa-contact-info">
                            <div class="wa-contact-top">
                                <span class="wa-name">{{ $displayName }}</span>
                                <span class="wa-time">{{ \Carbon\Carbon::parse($contact->last_msg_time)->format('H:i') }}</span>
                            </div>
                            <div class="wa-number">
                                {{ $contact->sender_number }}
                            </div>
                        </div>
                    </a>
                @endforeach

                @if($contacts->isEmpty())
                    <div class="text-center p-4 text-muted">
                        <small>Belum ada riwayat chat.</small>
                    </div>
                @endif
            </div>
        </div>

        <div class="wa-chat-area">
            @if($activePhone)
                @php
                    // Ambil nama dari kontak aktif untuk header
                    $activeContact = $contacts->where('sender_number', $activePhone)->first();
                    $activeName = $activeContact && !empty($activeContact->sender_name) && strtolower($activeContact->sender_name) !== 'unknown'
                                  ? $activeContact->sender_name 
                                  : $activePhone;
                @endphp

                <div class="wa-chat-header">
                    <div class="wa-avatar">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($activeName) }}&background=random&color=fff" alt="User">
                    </div>
                    <div class="ms-3">
                        <div style="font-weight: 500; font-size:16px;">{{ $activeName }}</div>
                        <div style="font-size:12px; color:#667781;">Online</div>
                    </div>
                </div>

                <div class="wa-messages-box" id="messageBox">
                    @forelse($activeChat as $chat)
                        <div class="wa-bubble {{ $chat->type == 'outgoing' ? 'outgoing' : 'incoming' }}">
                            
                            @if(!empty($chat->media_url))
                                @php
                                    $ext = pathinfo($chat->media_url, PATHINFO_EXTENSION);
                                    $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $isAudio = in_array(strtolower($ext), ['mp3', 'ogg', 'wav']);
                                    $isVideo = in_array(strtolower($ext), ['mp4', 'mov']);
                                @endphp

                                <div class="media-preview">
                                    @if($isImage)
                                        <a href="{{ $chat->media_url }}" target="_blank">
                                            <img src="{{ $chat->media_url }}" alt="Image" style="max-width: 100%; border-radius: 5px;">
                                        </a>
                                    @elseif($isAudio)
                                        <audio controls style="width: 200px;">
                                            <source src="{{ $chat->media_url }}">
                                        </audio>
                                    @elseif($isVideo)
                                        <video controls style="max-width: 100%; border-radius:5px;">
                                            <source src="{{ $chat->media_url }}">
                                        </video>
                                    @else
                                        <a href="{{ $chat->media_url }}" target="_blank" style="text-decoration: underline;">
                                            Lihat File
                                        </a>
                                    @endif
                                </div>
                            @endif

                            @if($chat->message)
                                <div>{{ $chat->message }}</div>
                            @endif

                            <span class="wa-meta">
                                {{ $chat->created_at->format('H:i') }}
                                @if($chat->type == 'outgoing')
                                    <i class="fas fa-check-double text-primary" style="font-size: 10px;"></i>
                                @endif
                            </span>

                        </div>
                    @empty
                        <div class="text-center mt-5">
                            <span class="badge bg-light text-dark shadow-sm">Mulai percakapan baru</span>
                        </div>
                    @endforelse
                </div>

                <div class="wa-input-area">
                    <form action="{{ route('whatsapp.send') }}" method="POST" class="wa-form">
                        @csrf
                        <input type="hidden" name="target" value="{{ $activePhone }}">
                        
                        <button type="button" class="btn-send"><i class="far fa-smile"></i></button>
                        
                        <input type="text" name="message" class="wa-input" placeholder="Ketik pesan..." autocomplete="off" required autofocus>
                        
                        <button type="submit" class="btn-send ms-1">
                            <i class="fas fa-paper-plane text-secondary"></i>
                        </button>
                    </form>
                </div>

            @else
                <div class="h-100 d-flex flex-column justify-content-center align-items-center text-center" style="background-color: #f0f2f5;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/120px-WhatsApp.svg.png" width="80" class="mb-3 opacity-50" style="filter: grayscale(100%);">
                    <h4 class="text-secondary fw-light">WhatsApp Web Admin</h4>
                    <p class="text-muted small">Pilih kontak di sebelah kiri untuk melihat percakapan.</p>
                </div>
            @endif
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var messageBox = document.getElementById("messageBox");
        var audio = document.getElementById("wa-notification-sound");
        var lastMessageCount = document.querySelectorAll('.wa-bubble').length;

        // 1. Scroll ke bawah saat load pertama
        if (messageBox) {
            scrollToBottom();
        }

        function scrollToBottom() {
            if(messageBox) {
                messageBox.scrollTop = messageBox.scrollHeight;
            }
        }

        // 2. Auto Refresh Logic (Tanpa Reload Browser)
        // Kita menggunakan fetch untuk mengambil konten HTML halaman ini, lalu mengambil bagian pesan barunya saja.
        @if($activePhone)
            setInterval(function() {
                fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    // Parse HTML string menjadi DOM
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    
                    // Ambil konten messageBox yang baru
                    var newMessageBoxContent = doc.getElementById('messageBox').innerHTML;
                    var newContactListContent = doc.querySelector('.wa-contact-list').innerHTML;
                    
                    // Update Message Box
                    // Cek apakah ada pesan baru dengan menghitung jumlah bubble
                    var newMessageCount = doc.querySelectorAll('.wa-bubble').length;

                    if (newMessageCount > lastMessageCount) {
                        // Ada pesan baru!
                        messageBox.innerHTML = newMessageBoxContent;
                        
                        // Play Sound
                        playNotificationSound();
                        
                        // Scroll ke bawah
                        scrollToBottom();
                        
                        // Update counter
                        lastMessageCount = newMessageCount;
                    }

                    // Update Contact List (Untuk update last message / time di sidebar)
                    // Bandingkan string biar efisien
                    var currentContactList = document.querySelector('.wa-contact-list');
                    if(currentContactList.innerHTML.length !== newContactListContent.length) {
                        currentContactList.innerHTML = newContactListContent;
                    }
                    
                    document.getElementById('connection-status').className = 'badge bg-success';
                    document.getElementById('connection-status').innerText = 'Live';

                })
                .catch(err => {
                    console.error('Gagal refresh chat', err);
                    document.getElementById('connection-status').className = 'badge bg-danger';
                    document.getElementById('connection-status').innerText = 'Offline';
                });
            }, 2000); // 2 Detik sekali
        @endif

        function playNotificationSound() {
            if(audio) {
                audio.play().catch(function(error) {
                    console.log("Audio play blocked by browser policy until user interaction.");
                });
            }
        }
    });
</script>

@endsection