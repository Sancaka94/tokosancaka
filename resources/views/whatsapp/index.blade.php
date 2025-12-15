@extends('layouts.admin')

@section('content')

<style>
    /* Layout Utama */
    .wa-wrapper {
        display: flex;
        height: 85vh;
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: 1px solid #d1d7db;
    }

    /* --- SIDEBAR KIRI (DAFTAR KONTAK) --- */
    .wa-sidebar {
        width: 300px; /* DIPERBAIKI: Dikurangi dari 350px ke 300px agar area kanan lebih lebar */
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
        width: 45px; /* Sedikit diperkecil agar pas dengan sidebar 300px */
        height: 45px;
        background-color: #dfe5e7;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 20px;
        margin-right: 12px;
    }

    .wa-contact-info {
        flex: 1;
        overflow: hidden;
    }

    .wa-contact-top {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;
    }

    .wa-name { font-size: 15px; font-weight: 500; color: #111b21; } /* Font disesuaikan */
    .wa-time { font-size: 11px; color: #667781; }
    .wa-number { font-size: 13px; color: #667781; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }

    /* --- AREA CHAT KANAN --- */
    .wa-chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background-color: #efe7dd;
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        background-repeat: repeat;
        position: relative; /* Penting untuk positioning */
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
        padding: 20px 5%; /* Padding horizontal pakai % agar responsif */
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* Bubble Chat */
    .wa-bubble {
        max-width: 75%; /* DIPERBAIKI: Diperlebar dari 65% ke 75% */
        padding: 6px 7px 8px 9px;
        border-radius: 7.5px;
        font-size: 14.2px;
        line-height: 19px;
        position: relative;
        box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
        word-wrap: break-word;
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

    /* Media Handling */
    .media-preview img {
        max-width: 100%;
        border-radius: 5px;
        margin-bottom: 5px;
        cursor: pointer;
    }
    .media-file-link {
        display: flex; align-items: center; gap: 10px;
        background: rgba(0,0,0,0.05); padding: 10px; border-radius: 5px;
        text-decoration: none; color: #333; font-weight: 500;
        margin-bottom: 5px;
    }

    .wa-meta {
        float: right;
        margin-top: 4px;
        margin-left: 10px;
        font-size: 11px;
        color: #667781;
        display: flex; align-items: center; gap: 3px;
        position: relative;
        top: 4px;
    }

    /* --- INPUT AREA (BAGIAN YANG ANDA MINTA LEBARKAN) --- */
    .wa-input-area {
        min-height: 62px;
        background-color: #f0f2f5;
        padding: 10px 16px;
        display: flex; align-items: center;
        gap: 10px;
        width: 100%; /* Pastikan area input full width */
    }

    .wa-input {
        flex: 1; /* Wajib ada untuk fill space */
        width: 100%; /* DIPERBAIKI: Memaksa input selebar mungkin */
        min-width: 0; /* Mencegah overflow flex item */
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
        background: transparent; border: none; font-size: 24px; /* Ikon diperbesar sedikit */
        color: #54656f; cursor: pointer;
        padding: 0 10px;
    }
    .btn-send:hover { color: #00a884; }

    /* Scrollbar Halus */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 3px; }

    /* Responsif untuk Layar Kecil (Laptop Kecil / Tablet) */
    @media (max-width: 992px) {
        .wa-sidebar { width: 250px; } /* Sidebar mengecil */
        .wa-name { font-size: 14px; }
        .wa-avatar { width: 35px; height: 35px; }
    }
</style>

<div class="container-fluid py-3">
    @if(session('success'))
        <div class="alert alert-success mb-2 py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-2 py-2">{{ session('error') }}</div>
    @endif

    <div class="wa-wrapper">
        
        <div class="wa-sidebar">
            <div class="wa-sidebar-header">
                <div class="wa-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h6 class="m-0 ms-2 text-dark text-truncate">Chat Admin</h6>
            </div>

            <div class="wa-contact-list">
                @foreach($contacts as $contact)
                    @php
                        $isActive = ($activePhone == $contact->sender_number);
                    @endphp
                    <a href="{{ route('whatsapp.index', ['phone' => $contact->sender_number]) }}" class="wa-contact-item {{ $isActive ? 'active' : '' }}">
                        <div class="wa-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="wa-contact-info">
                            <div class="wa-contact-top">
                                <span class="wa-name text-truncate">{{ $contact->sender_name ?: 'Tanpa Nama' }}</span>
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
                
                <div class="wa-chat-header">
                    <div class="wa-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="ms-3">
                        <div style="font-weight: 500; font-size:16px;">{{ $activePhone }}</div>
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
                                            <img src="{{ $chat->media_url }}" alt="Image">
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
                                        <a href="{{ $chat->media_url }}" target="_blank" class="media-file-link">
                                            <i class="fas fa-file-alt fa-lg text-danger"></i>
                                            <span>Lihat Dokumen</span>
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
                                    <i class="fas fa-check-double text-primary"></i>
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
                    <form action="{{ route('whatsapp.send') }}" method="POST" class="w-100 d-flex align-items-center" style="gap: 10px;">
                        @csrf
                        <input type="hidden" name="target" value="{{ $activePhone }}">
                        
                        <button type="button" class="btn-send text-muted"><i class="far fa-smile"></i></button>
                        
                        <input type="text" name="message" class="wa-input" placeholder="Ketik pesan..." autocomplete="off" required>
                        
                        <button type="submit" class="btn-send ms-2">
                            <i class="fas fa-paper-plane text-secondary"></i>
                        </button>
                    </form>
                </div>

            @else
                <div class="h-100 d-flex flex-column justify-content-center align-items-center text-center">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/1200px-WhatsApp.svg.png" width="80" class="mb-3 opacity-50" style="filter: grayscale(100%);">
                    <h4 class="text-secondary fw-light">WhatsApp Web Laravel</h4>
                    <p class="text-muted small">Kirim dan terima pesan tanpa perlu membuka ponsel Anda.<br>Pilih kontak di sebelah kiri untuk memulai.</p>
                </div>
            @endif
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var messageBox = document.getElementById("messageBox");
        if (messageBox) {
            messageBox.scrollTop = messageBox.scrollHeight;
        }
    });
</script>

@endsection