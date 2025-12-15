@extends('layouts.admin')

@section('content')

<audio id="wa-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

<style>
    /* --- Layout Utama --- */
    .wa-wrapper {
        display: flex;
        height: 85vh;
        background-color: #fff;
        border-radius: 0; /* WA Web biasanya kotak */
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
        overflow: hidden;
        margin-right: 15px;
        flex-shrink: 0;
        background-color: #dfe5e7;
    }
    .wa-avatar img { width: 100%; height: 100%; object-fit: cover; }

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
        /* HAPUS LOGO WA BACKGROUND (Poin 4) -> Ganti pattern halus */
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        background-repeat: repeat;
        background-size: 400px; 
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
        gap: 5px;
    }

    /* Bubble Chat */
    .wa-bubble {
        max-width: 65%;
        padding: 6px 7px 8px 9px;
        border-radius: 7.5px;
        font-size: 14.2px;
        line-height: 19px;
        position: relative;
        box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
        word-wrap: break-word;
        margin-bottom: 4px;
    }

    /* Poin 3: Incoming (Kiri/Putih) */
    .wa-bubble.incoming {
        align-self: flex-start;
        background-color: #ffffff;
        border-top-left-radius: 0;
    }

    /* Poin 3: Outgoing (Kanan/Hijau) */
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

    /* Poin 1: Input Area LEBAR */
    .wa-input-area {
        min-height: 62px;
        background-color: #f0f2f5;
        padding: 10px 16px;
        display: flex; align-items: center;
        width: 100%;
    }

    .wa-form {
        display: flex;
        width: 100%;
        gap: 10px;
        align-items: center;
    }

    .wa-input {
        flex: 1; /* Supaya input mentok memenuhi ruang */
        width: 100%;
        padding: 9px 12px;
        border-radius: 8px;
        border: 1px solid #fff;
        background-color: #fff;
        outline: none;
        font-size: 15px;
        height: 40px;
    }
    
    .btn-send {
        background: transparent; border: none; font-size: 24px; color: #54656f; cursor: pointer;
        padding: 0 10px;
    }

    /* Poin 6: Flasher Sukses (Toast) */
    .flash-toast {
        position: absolute; top: 80px; right: 20px; z-index: 999;
        padding: 12px 20px; border-radius: 5px; color: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        animation: fadeOut 3s forwards;
        font-weight: 500;
    }
    .bg-success-toast { background-color: #25d366; }
    .bg-error-toast { background-color: #dc3545; }

    @keyframes fadeOut {
        0% { opacity: 1; } 80% { opacity: 1; } 100% { opacity: 0; display: none; }
    }
</style>

<div class="container-fluid py-3" style="background: #e9edef; min-height: 100vh;">
    
    @if(session('success'))
        <div class="flash-toast bg-success-toast">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-toast bg-error-toast">{{ session('error') }}</div>
    @endif

    <div class="wa-wrapper">
        
        <div class="wa-sidebar">
            <div class="wa-sidebar-header">
                <div class="d-flex align-items-center">
                    <div class="wa-avatar">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=ddd&color=333" alt="Admin">
                    </div>
                    <h6 class="m-0 ms-2 text-dark">Chat Admin</h6>
                </div>
            </div>

            <div class="wa-contact-list">
                @foreach($contacts as $contact)
                    @php
                        $isActive = ($activePhone == $contact->sender_number);
                        // Poin 2: Logic Nama Unknown
                        $displayName = $contact->sender_name ?: $contact->sender_number;
                    @endphp
                    <a href="{{ route('whatsapp.index', ['phone' => $contact->sender_number]) }}" class="wa-contact-item {{ $isActive ? 'active' : '' }}">
                        <div class="wa-avatar">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($displayName) }}&background=random&color=fff" alt="User">
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
                    // Ambil detail kontak aktif dari collection yang sudah ada
                    $activeContact = $contacts->where('sender_number', $activePhone)->first();
                    $activeName = $activeContact ? ($activeContact->sender_name ?: $activePhone) : $activePhone;
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
                                <div class="media-preview mb-1">
                                    <a href="{{ $chat->media_url }}" target="_blank" style="text-decoration: underline; color: #00a884;">
                                        Lihat Media / File
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
                <div class="h-100 d-flex flex-column justify-content-center align-items-center text-center">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/120px-WhatsApp.svg.png" width="80" class="mb-3 opacity-50" style="filter: grayscale(100%);">
                    <h4 class="text-secondary fw-light">WhatsApp Web</h4>
                    <p class="text-muted small">Pilih kontak untuk memulai chat.</p>
                </div>
            @endif
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var messageBox = document.getElementById("messageBox");
        var audio = document.getElementById("wa-sound");
        var lastCount = document.querySelectorAll('.wa-bubble').length;

        // Scroll Bawah Awal
        if (messageBox) { messageBox.scrollTop = messageBox.scrollHeight; }

        // Logic Auto Refresh (Simple Fetch)
        @if($activePhone)
            setInterval(function() {
                fetch(window.location.href)
                .then(res => res.text())
                .then(html => {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var newBox = doc.getElementById('messageBox');

                    if(newBox) {
                        var newCount = newBox.querySelectorAll('.wa-bubble').length;
                        if(newCount > lastCount) {
                            // Ada pesan baru
                            document.getElementById('messageBox').innerHTML = newBox.innerHTML;
                            // Play Sound
                            if(audio) audio.play().catch(e => console.log(e));
                            // Scroll Bawah
                            document.getElementById('messageBox').scrollTop = document.getElementById('messageBox').scrollHeight;
                            lastCount = newCount;
                        }
                    }
                })
                .catch(err => console.log('Polling err')); // Silent error biar gak ganggu user
            }, 2000); // Cek tiap 2 detik
        @endif
    });
</script>

@endsection