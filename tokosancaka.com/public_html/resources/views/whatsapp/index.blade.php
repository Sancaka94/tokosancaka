@extends('layouts.admin')

@section('content')

<style>
    /* --- 1. LAYOUT UTAMA (FULL WIDTH & HEIGHT) --- */
    /* Pastikan container dashboard bawaan tidak membatasi lebar */
    .container-fluid {
        padding: 0 !important; /* Reset padding bawaan dashboard jika ada */
        width: 100% !important;
        max-width: 100% !important;
    }

    .wa-wrapper {
        display: flex;
        /* Tinggi responsif: Full layar dikurangi header dashboard */
        height: 100vh; 
        width: 100%; /* Memaksa lebar penuh */
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #d1d7db;
    }

    /* --- 2. SIDEBAR KIRI (KONTAK) --- */
    .wa-sidebar {
        width: 320px;
        background-color: #fff;
        border-right: 1px solid #e9edef;
        display: flex;
        flex-direction: column;
        flex-shrink: 0; /* Mencegah sidebar mengecil */
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
        width: 45px; height: 45px;
        background-color: #dfe5e7;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 20px;
        margin-right: 12px;
    }
    
    .wa-contact-info { flex: 1; overflow: hidden; }
    .wa-name { font-size: 15px; font-weight: 500; color: #111b21; }
    .wa-number { font-size: 13px; color: #667781; }

    /* --- 3. AREA CHAT KANAN --- */
    .wa-chat-area {
        flex: 1; /* Mengisi sisa ruang (PENTING AGAR FULL WIDTH) */
        display: flex;
        flex-direction: column;
        background-color: #efe7dd;
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        background-repeat: repeat;
        min-width: 0; /* Fix untuk flexbox overflow */
    }

    .wa-chat-header {
        height: 60px;
        background-color: #f0f2f5;
        padding: 10px 16px;
        display: flex; align-items: center;
        border-bottom: 1px solid #d1d7db;
    }

    .wa-messages-box {
        flex: 1;
        padding: 20px 5%;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .wa-bubble {
        max-width: 75%;
        padding: 8px 10px;
        border-radius: 8px;
        font-size: 14.5px;
        line-height: 1.4;
        position: relative;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        word-wrap: break-word;
    }
    .wa-bubble.incoming { align-self: flex-start; background: #fff; border-top-left-radius: 0; }
    .wa-bubble.outgoing { align-self: flex-end; background: #d9fdd3; border-top-right-radius: 0; }
    
    .wa-meta {
        font-size: 10px; color: #667781; float: right; margin-top: 5px; margin-left: 8px; display: flex; align-items: center; gap: 3px;
    }

    /* --- 4. AREA INPUT (PERBAIKAN UTAMA) --- */
    .wa-input-area {
        min-height: 62px;
        background-color: #f0f2f5;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        border-top: 1px solid #d1d7db;
        width: 100%;
    }

    /* Form Pembungkus Input & Tombol */
    .wa-form-wrapper {
        display: flex;
        width: 100%;
        align-items: center; /* Pastikan input & tombol sejajar vertikal */
        gap: 12px; /* Jarak antara input dan tombol */
    }

    /* Input Teks */
    .wa-input {
        flex: 1; /* Input memanjang otomatis */
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid #fff;
        background-color: #fff;
        outline: none;
        font-size: 15px;
        height: 45px; /* Tinggi fix agar seragam */
        width: 100%;
    }
    
    /* Tombol Kirim */
    .btn-send {
        background: none;
        border: none;
        color: #54656f;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        height: 45px; /* Samakan tinggi dengan input */
        width: 45px;
        transition: color 0.2s;
    }
    .btn-send i {
        font-size: 24px; /* Ukuran icon */
    }
    .btn-send:hover { color: #00a884; }

    /* RESPONSIVE HP */
    @media (max-width: 768px) {
        .wa-wrapper {
            height: calc(100vh - 70px); 
            border: none; border-radius: 0;
        }
        .wa-sidebar { width: 100%; display: {{ $activePhone ? 'none' : 'flex' }}; }
        .wa-chat-area { width: 100%; display: {{ $activePhone ? 'flex' : 'none' }}; }
        .wa-input-area { padding: 8px 10px; }
    }
</style>

<div class="container-fluid">
    <div class="wa-wrapper">
        
        <div class="wa-sidebar">
            <div class="wa-sidebar-header">
                <div class="wa-avatar"><i class="fas fa-user-circle"></i></div>
                <h6 class="m-0 ms-2 text-dark">Chat Admin</h6>
            </div>

            <div class="wa-contact-list">
                @foreach($contacts as $contact)
                    <a href="{{ route('whatsapp.index', ['phone' => $contact->sender_number]) }}" 
                       class="wa-contact-item {{ ($activePhone == $contact->sender_number) ? 'active' : '' }}">
                        <div class="wa-avatar"><i class="fas fa-user"></i></div>
                        <div class="wa-contact-info">
                            <div class="d-flex justify-content-between">
                                <span class="wa-name">{{ $contact->sender_name ?: 'Tanpa Nama' }}</span>
                                <span class="wa-time" style="font-size:11px; color:#aaa;">{{ \Carbon\Carbon::parse($contact->last_msg_time)->format('H:i') }}</span>
                            </div>
                            <div class="wa-number text-truncate">{{ $contact->sender_number }}</div>
                        </div>
                    </a>
                @endforeach
                @if($contacts->isEmpty())
                    <div class="text-center p-4 text-muted small">Belum ada riwayat chat.</div>
                @endif
            </div>
        </div>

        <div class="wa-chat-area">
            @if($activePhone)
                
                <div class="wa-chat-header">
                    <a href="{{ route('whatsapp.index') }}" class="d-md-none me-3 text-dark"><i class="fas fa-arrow-left"></i></a>
                    <div class="wa-avatar" style="width:38px; height:38px; font-size:16px;"><i class="fas fa-user"></i></div>
                    <div class="ms-2">
                        <div style="font-weight: 600; font-size:15px;">{{ $activePhone }}</div>
                        <div style="font-size:11px; color:#667781;">Online</div>
                    </div>
                </div>

                <div class="wa-messages-box" id="messageBox">
                    @forelse($activeChat as $chat)
                        <div class="wa-bubble {{ $chat->type == 'outgoing' ? 'outgoing' : 'incoming' }}">
                            @if(!empty($chat->media_url))
                                <div class="mb-1"><a href="{{ $chat->media_url }}" target="_blank" class="text-primary small"><i class="fas fa-paperclip"></i> Lihat Media</a></div>
                            @endif
                            {{ $chat->message }}
                            <span class="wa-meta">
                                {{ $chat->created_at->format('H:i') }}
                                @if($chat->type == 'outgoing')<i class="fas fa-check-double text-primary" style="font-size:9px;"></i>@endif
                            </span>
                        </div>
                    @empty
                        <div class="text-center mt-5 text-muted small">Mulai percakapan baru.</div>
                    @endforelse
                </div>

                <div class="wa-input-area">
                    <form action="{{ route('whatsapp.send') }}" method="POST" class="wa-form-wrapper">
                        @csrf
                        <input type="hidden" name="target" value="{{ $activePhone }}">
                        
                        <input type="text" name="message" class="wa-input" placeholder="Ketik pesan..." autocomplete="off" required>
                        
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

            @else
                <div class="h-100 d-none d-md-flex flex-column justify-content-center align-items-center text-center p-4">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/1200px-WhatsApp.svg.png" width="80" class="mb-3 opacity-25 grayscale">
                    <h5 class="text-secondary fw-light">WhatsApp Web Admin</h5>
                    <p class="text-muted small">Pilih kontak untuk melihat chat.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var messageBox = document.getElementById("messageBox");
        if (messageBox) { messageBox.scrollTop = messageBox.scrollHeight; }
    });
</script>

@endsection