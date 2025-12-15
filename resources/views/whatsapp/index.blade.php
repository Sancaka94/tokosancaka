@extends('layouts.admin')

@section('content')

<style>
    /* --- 1. LAYOUT UTAMA (FULL SCREEN) --- */
    .wa-wrapper {
        display: flex;
        /* Tinggi responsif: 100vh dikurangi tinggi navbar admin (sekitar 70-100px) */
        height: calc(100vh - 100px); 
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #d1d7db;
        position: relative;
    }

    /* --- 2. SIDEBAR KIRI (KONTAK) --- */
    .wa-sidebar {
        width: 320px; /* Lebar standar WA Web */
        background-color: #fff;
        border-right: 1px solid #e9edef;
        display: flex;
        flex-direction: column;
        z-index: 5;
    }

    /* Header Sidebar */
    .wa-sidebar-header {
        background-color: #f0f2f5;
        padding: 10px 16px;
        height: 60px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #e9edef;
        flex-shrink: 0;
    }

    /* List Kontak (Scrollable) */
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
        flex: 1;
        display: flex;
        flex-direction: column;
        background-color: #efe7dd; /* Warna dasar WA */
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        background-repeat: repeat;
        position: relative;
    }

    /* Header Chat */
    .wa-chat-header {
        height: 60px;
        background-color: #f0f2f5;
        padding: 10px 16px;
        display: flex; align-items: center;
        border-bottom: 1px solid #d1d7db;
        z-index: 10;
        flex-shrink: 0;
    }

    /* Kotak Pesan (Scrollable) */
    .wa-messages-box {
        flex: 1;
        padding: 20px 5%;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    /* Bubble Chat */
    .wa-bubble {
        max-width: 80%; /* Lebih lebar agar muat banyak teks */
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

    /* --- 4. AREA INPUT (FULL WIDTH) --- */
    .wa-input-area {
        min-height: 62px;
        background-color: #f0f2f5;
        padding: 8px 10px; /* Padding dikurangi agar input lebih luas */
        display: flex; align-items: center;
        gap: 8px;
        width: 100%;
        border-top: 1px solid #d1d7db;
    }

    /* Input text dibuat sangat fleksibel */
    .wa-input {
        flex: 1; /* Mengisi seluruh sisa ruang */
        width: 100%; 
        padding: 10px 15px;
        border-radius: 8px;
        border: 1px solid #fff;
        background-color: #fff;
        outline: none;
        font-size: 15px;
        height: 42px;
    }
    .btn-send {
        background: none; border: none; font-size: 22px; color: #54656f; cursor: pointer; padding: 0 8px;
    }
    .btn-send:hover { color: #00a884; }

    /* Scrollbar Cantik */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 3px; }

    /* --- 5. RESPONSIVE MOBILE (PENTING!) --- */
    @media (max-width: 768px) {
        /* Wrapper full layar HP */
        .wa-wrapper {
            height: calc(100vh - 60px); 
            border: none; border-radius: 0; margin: 0 -15px; /* Hilangkan margin container */
        }

        /* Logika Tampilan: 
           Jika ada chat aktif -> Sidebar Sembunyi, Chat Tampil 
           Jika tidak ada -> Sidebar Tampil, Chat Sembunyi
        */
        .wa-sidebar {
            width: 100%; /* Sidebar Full Width */
            display: {{ $activePhone ? 'none' : 'flex' }};
        }
        
        .wa-chat-area {
            width: 100%; /* Chat Full Width */
            display: {{ $activePhone ? 'flex' : 'none' }};
        }

        /* Input area di HP */
        .wa-input-area { padding: 5px 8px; }
        .wa-input { font-size: 16px; /* Mencegah zoom in di iOS */ }
        .wa-avatar { width: 40px; height: 40px; font-size: 18px; }
    }
</style>

<div class="container-fluid px-0 py-0"> <div class="wa-wrapper">
        
        <div class="wa-sidebar">
            <div class="wa-sidebar-header">
                <div class="wa-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h6 class="m-0 ms-2 text-dark">Chat Admin</h6>
            </div>

            <div class="wa-contact-list">
                @foreach($contacts as $contact)
                    <a href="{{ route('whatsapp.index', ['phone' => $contact->sender_number]) }}" 
                       class="wa-contact-item {{ ($activePhone == $contact->sender_number) ? 'active' : '' }}">
                        <div class="wa-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="wa-contact-info">
                            <div class="d-flex justify-content-between">
                                <span class="wa-name">{{ $contact->sender_name ?: 'Tanpa Nama' }}</span>
                                <span class="wa-time" style="font-size:11px; color:#aaa;">
                                    {{ \Carbon\Carbon::parse($contact->last_msg_time)->format('H:i') }}
                                </span>
                            </div>
                            <div class="wa-number text-truncate">
                                {{ $contact->sender_number }}
                            </div>
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
                    <a href="{{ route('whatsapp.index') }}" class="btn btn-sm btn-link text-dark d-md-none me-2 p-0" style="font-size: 20px;">
                        <i class="fas fa-arrow-left"></i>
                    </a>

                    <div class="wa-avatar" style="width:38px; height:38px; font-size:16px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="ms-2">
                        <div style="font-weight: 600; font-size:15px; line-height:1.2;">{{ $activePhone }}</div>
                        <div style="font-size:11px; color:#667781;">Online</div>
                    </div>
                </div>

                <div class="wa-messages-box" id="messageBox">
                    @forelse($activeChat as $chat)
                        <div class="wa-bubble {{ $chat->type == 'outgoing' ? 'outgoing' : 'incoming' }}">
                            @if(!empty($chat->media_url))
                                <div class="mb-1">
                                    <a href="{{ $chat->media_url }}" target="_blank" class="text-primary small text-decoration-none">
                                        <i class="fas fa-paperclip"></i> Lihat Media
                                    </a>
                                </div>
                            @endif
                            
                            {{ $chat->message }}
                            
                            <span class="wa-meta">
                                {{ $chat->created_at->format('H:i') }}
                                @if($chat->type == 'outgoing')
                                    <i class="fas fa-check-double text-primary" style="font-size:9px;"></i>
                                @endif
                            </span>
                        </div>
                    @empty
                        <div class="text-center mt-5 text-muted small bg-light p-2 rounded mx-auto" style="width:fit-content;">
                            Mulai percakapan baru dengan pelanggan ini.
                        </div>
                    @endforelse
                </div>

                <div class="wa-input-area">
                    <form action="{{ route('whatsapp.send') }}" method="POST" class="w-100 d-flex align-items-center m-0">
                        @csrf
                        <input type="hidden" name="target" value="{{ $activePhone }}">
                        
                        <input type="text" name="message" class="wa-input" placeholder="Ketik pesan..." autocomplete="off" required>
                        
                        <button type="submit" class="btn-send ms-2">
                            <i class="fas fa-paper-plane text-primary"></i>
                        </button>
                    </form>
                </div>

            @else
                <div class="h-100 d-none d-md-flex flex-column justify-content-center align-items-center text-center p-4">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/1200px-WhatsApp.svg.png" width="80" class="mb-3 opacity-25 grayscale">
                    <h5 class="text-secondary fw-light">WhatsApp Web Admin</h5>
                    <p class="text-muted small">Pilih kontak di sebelah kiri untuk melihat chat.</p>
                </div>
            @endif
        </div>

    </div>
</div>

<script>
    // Auto Scroll ke Bawah
    document.addEventListener("DOMContentLoaded", function() {
        var messageBox = document.getElementById("messageBox");
        if (messageBox) {
            messageBox.scrollTop = messageBox.scrollHeight;
        }
    });
</script>

@endsection