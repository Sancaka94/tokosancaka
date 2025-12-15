@extends('layouts.admin')

@section('content')

<audio id="wa-notification-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

<style>
    /* --- Layout Utama --- */
    .wa-wrapper {
        display: flex;
        height: 85vh;
        background-color: #fff;
        border-radius: 0; /* WA Web biasanya kotak penuh atau radius kecil */
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: 1px solid #d1d7db;
        position: relative;
        font-family: "Segoe UI", "Helvetica Neue", Helvetica, Arial, sans-serif;
    }

    /* --- SIDEBAR KIRI --- */
    .wa-sidebar {
        width: 30%; /* WA standard sekitar 30-35% */
        min-width: 300px;
        background-color: #fff;
        border-right: 1px solid #e9edef;
        display: flex;
        flex-direction: column;
    }

    /* Header Sidebar (User Profile) */
    .wa-sidebar-header {
        background-color: #f0f2f5;
        padding: 10px 16px;
        height: 59px;
        display: flex;
        align-items: center;
        justify-content: space-between; /* Icon di kanan */
        border-bottom: 1px solid #d1d7db;
        flex-shrink: 0;
    }

    .wa-sidebar-icons {
        display: flex;
        gap: 20px;
        color: #54656f;
    }
    .wa-sidebar-icons i { cursor: pointer; font-size: 18px; }

    /* Search Bar di Sidebar (Opsional, style visual saja) */
    .wa-search-bar {
        height: 49px;
        border-bottom: 1px solid #e9edef;
        background: #fff;
        display: flex;
        align-items: center;
        padding: 0 12px;
        flex-shrink: 0;
    }
    .wa-search-box {
        background: #f0f2f5;
        border-radius: 8px;
        height: 35px;
        width: 100%;
        display: flex;
        align-items: center;
        padding: 0 10px;
        font-size: 14px;
        color: #54656f;
    }

    /* List Kontak */
    .wa-contact-list {
        flex: 1;
        overflow-y: auto;
        background-color: #fff;
    }

    .wa-contact-item {
        display: flex;
        align-items: center;
        padding: 0 15px; /* Tinggi baris WA sekitar 72px */
        height: 72px;
        border-bottom: 1px solid #f0f2f5;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        color: inherit;
        position: relative;
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
    .wa-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .wa-contact-info {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
        border-bottom: 1px solid #f0f2f5; /* Garis pemisah */
        padding-right: 10px;
    }

    /* Text Styles */
    .wa-name { font-size: 17px; font-weight: 400; color: #111b21; margin-bottom: 3px; }
    .wa-time { font-size: 12px; color: #667781; }
    .wa-preview { font-size: 13px; color: #667781; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }

    /* Badge Notif */
    .wa-badge {
        background-color: #25d366; color: white;
        min-width: 20px; height: 20px;
        border-radius: 50%; font-size: 11px; font-weight: 600;
        display: flex; align-items: center; justify-content: center;
        padding: 0 5px; flex-shrink: 0;
    }

    /* --- CHAT AREA KANAN --- */
    .wa-chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background-color: #efe7dd;
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        background-repeat: repeat;
        background-size: 400px;
        position: relative;
    }

    /* Header Chat */
    .wa-chat-header {
        height: 59px;
        background-color: #f0f2f5;
        padding: 10px 16px;
        display: flex; align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #d1d7db;
        z-index: 10;
        flex-shrink: 0;
    }

    .wa-chat-header-info { display: flex; align-items: center; cursor: pointer; }
    .wa-chat-header-icons { display: flex; gap: 24px; color: #54656f; font-size: 18px; }

    /* Pesan */
    .wa-messages-box {
        flex: 1;
        padding: 20px 60px; /* Padding samping lebih lebar seperti WA Web */
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 2px; /* Jarak antar bubble rapat */
    }

    /* Bubble Styles */
    .wa-bubble {
        max-width: 65%;
        padding: 6px 7px 8px 9px;
        border-radius: 7.5px;
        font-size: 14.2px;
        line-height: 19px;
        position: relative;
        box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
        word-wrap: break-word;
        margin-bottom: 8px;
    }

    .wa-bubble.incoming {
        align-self: flex-start;
        background-color: #ffffff;
        border-top-left-radius: 0;
    }

    .wa-bubble.outgoing {
        align-self: flex-end;
        background-color: #d9fdd3;
        border-top-right-radius: 0;
    }

    .wa-meta {
        float: right; margin-top: 4px; margin-left: 10px;
        font-size: 11px; color: #667781;
        display: flex; align-items: center; gap: 3px;
        position: relative; top: 4px;
    }

    /* Input Area */
    .wa-input-area {
        min-height: 62px;
        background-color: #f0f2f5;
        padding: 5px 16px;
        display: flex; align-items: center;
        gap: 15px;
        width: 100%;
        flex-shrink: 0;
        z-index: 10;
    }

    .wa-input {
        flex: 1;
        padding: 9px 12px;
        border-radius: 8px;
        border: 1px solid #fff;
        background-color: #fff;
        outline: none;
        font-size: 15px;
        height: 42px;
    }
    
    .btn-icon { background: none; border: none; font-size: 24px; color: #54656f; cursor: pointer; }

    /* Flash Toast */
    .flash-toast {
        position: absolute; top: 80px; right: 20px; z-index: 100;
        padding: 12px 24px; border-radius: 4px; color: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        animation: fadeOut 4s forwards;
    }
    .flash-success { background-color: #00a884; } 
    .flash-error { background-color: #ea0038; }

    @keyframes fadeOut {
        0% { opacity: 1; } 80% { opacity: 1; } 100% { opacity: 0; display: none; }
    }

    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); }
</style>

<div class="container-fluid py-3" style="background: #d1d7db; height: 100vh;">
    
    @if(session('success'))
        <div class="flash-toast flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-toast flash-error">{{ session('error') }}</div>
    @endif

    <div class="wa-wrapper">
        
        <div class="wa-sidebar">
            <div class="wa-sidebar-header">
                <div class="wa-avatar" style="width: 40px; height: 40px; margin: 0; cursor: pointer;">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=dfe5e7&color=fff" alt="Me">
                </div>
                <div class="wa-sidebar-icons">
                    <i class="fas fa-users" title="Komunitas"></i>
                    <i class="fas fa-circle-notch" title="Status"></i>
                    <i class="fas fa-message" title="Chat Baru"></i>
                    <i class="fas fa-ellipsis-v" title="Menu"></i>
                </div>
            </div>

            <div class="wa-search-bar">
                <div class="wa-search-box">
                    <i class="fas fa-search me-3"></i>
                    <span>Cari atau mulai chat baru</span>
                </div>
            </div>

            <div class="wa-contact-list">
                @foreach($contacts as $contact)
                    @php
                        // --- SOLUSI MASALAH 1: LOGIC FILTER ---
                        // Ganti '628819435180' dengan variabel nomor admin Anda jika ada di session/config
                        // atau pastikan Logic di Controller sudah memfilter 'sender_number' != Admin.
                        // Di sini kita pakai cara kasar di View: Skip jika nama/nomor terdeteksi sebagai Admin.
                        if(strpos(strtolower($contact->sender_name), 'admin') !== false || strpos(strtolower($contact->sender_name), 'me') !== false) {
                            continue; 
                        }

                        $isActive = ($activePhone == $contact->sender_number);
                        $displayName = (!empty($contact->sender_name) && strtolower($contact->sender_name) !== 'unknown') 
                                        ? $contact->sender_name 
                                        : $contact->sender_number;
                        
                        $unreadCount = $contact->unread ?? 0; 
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
                            
                            <div class="wa-contact-bottom">
                                <div class="wa-preview">
                                    {{-- Preview pesan terakhir (opsional jika data ada) --}}
                                    {{ $contact->sender_number }}
                                </div>
                                
                                @if($unreadCount > 0)
                                    <div class="wa-badge">{{ $unreadCount }}</div>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="wa-chat-area">
            @if($activePhone)
                @php
                    $activeContact = $contacts->where('sender_number', $activePhone)->first();
                    $activeName = $activeContact && !empty($activeContact->sender_name) && strtolower($activeContact->sender_name) !== 'unknown'
                                  ? $activeContact->sender_name 
                                  : $activePhone;
                @endphp

                <div class="wa-chat-header">
                    <div class="wa-chat-header-info">
                        <div class="wa-avatar" style="width: 40px; height: 40px;">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($activeName) }}&background=random&color=fff" alt="User">
                        </div>
                        <div class="ms-3">
                            <div style="font-weight: 400; font-size:16px; color:#111b21;">{{ $activeName }}</div>
                            <div style="font-size:12px; color:#667781;">Online</div>
                        </div>
                    </div>
                    <div class="wa-chat-header-icons">
                        <i class="fas fa-search"></i>
                        <i class="fas fa-ellipsis-v"></i>
                    </div>
                </div>

                <div class="wa-messages-box" id="messageBox">
                    @forelse($activeChat as $chat)
                        <div class="wa-bubble {{ $chat->type == 'outgoing' ? 'outgoing' : 'incoming' }}">
                            
                            @if(!empty($chat->media_url))
                                <div class="media-preview mb-1">
                                    <a href="{{ $chat->media_url }}" target="_blank" style="text-decoration: underline; color: #00a884;">
                                        <i class="fas fa-file"></i> Lihat Media
                                    </a>
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
                    <button class="btn-icon"><i class="far fa-smile"></i></button>
                    <button class="btn-icon"><i class="fas fa-plus"></i></button>
                    
                    <form action="{{ route('whatsapp.send') }}" method="POST" class="d-flex flex-grow-1">
                        @csrf
                        <input type="hidden" name="target" value="{{ $activePhone }}">
                        
                        <input type="text" name="message" class="wa-input" placeholder="Ketik pesan" autocomplete="off" required autofocus>
                        
                        <button type="submit" class="btn-icon ms-3">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    
                    <button class="btn-icon"><i class="fas fa-microphone"></i></button>
                </div>

            @else
                <div class="h-100 d-flex flex-column justify-content-center align-items-center text-center" style="background-color: #f0f2f5; border-bottom: 6px solid #43c960;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/120px-WhatsApp.svg.png" width="80" class="mb-4 opacity-50" style="filter: grayscale(100%);">
                    <h2 style="color: #41525d; font-weight: 300;">WhatsApp Web</h2>
                    <p style="color: #667781; font-size: 14px;">Kirim dan terima pesan tanpa perlu menghubungkan telepon.</p>
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

        // Auto Scroll ke Bawah
        if (messageBox) { messageBox.scrollTop = messageBox.scrollHeight; }

        // Realtime Refresh Logic
        @if($activePhone)
            setInterval(function() {
                fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    
                    var newMessageBox = doc.getElementById('messageBox');
                    var newContactList = doc.querySelector('.wa-contact-list');
                    
                    // Update Pesan
                    if (newMessageBox) {
                        var newMessageCount = newMessageBox.querySelectorAll('.wa-bubble').length;
                        if (newMessageCount > lastMessageCount) {
                            document.getElementById('messageBox').innerHTML = newMessageBox.innerHTML;
                            if(audio) audio.play().catch(e => console.log(e));
                            document.getElementById('messageBox').scrollTop = document.getElementById('messageBox').scrollHeight;
                            lastMessageCount = newMessageCount;
                        }
                    }

                    // Update List Kontak
                    if(newContactList) {
                        document.querySelector('.wa-contact-list').innerHTML = newContactList.innerHTML;
                    }
                })
                .catch(err => console.error('Error fetching chat', err));
            }, 2000); 
        @endif
    });
</script>

@endsection