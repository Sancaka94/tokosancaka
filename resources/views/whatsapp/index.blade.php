@extends('layouts.admin')

@section('content')

<style>
    .wa-container {
        height: 80vh; /* Tinggi area chat */
        background-color: #f0f2f5;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
    }
    
    /* SIDEBAR KONTAK (KIRI) */
    .wa-sidebar {
        width: 30%;
        background: #fff;
        border-right: 1px solid #ddd;
        display: flex;
        flex-direction: column;
    }
    .wa-contacts-list {
        overflow-y: auto;
        flex: 1;
    }
    .wa-contact-item {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        align-items: center;
    }
    .wa-contact-item:hover { background-color: #f5f5f5; }
    .wa-contact-item.active { background-color: #ebebeb; }
    
    .avatar-circle {
        width: 45px; height: 45px;
        background-color: #ddd;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin-right: 15px; font-size: 20px; color: #666;
    }

    /* AREA CHAT (KANAN) */
    .wa-chat-area {
        width: 70%;
        display: flex;
        flex-direction: column;
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); /* Background WA */
        background-color: #efe7dd; 
    }
    
    .chat-header {
        padding: 10px 20px;
        background: #f0f2f5;
        border-bottom: 1px solid #ddd;
        display: flex; align-items: center;
    }

    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* BUBBLE CHAT */
    .message-bubble {
        max-width: 60%;
        padding: 8px 12px;
        border-radius: 7px;
        position: relative;
        font-size: 14px;
        line-height: 1.4;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    }
    
    /* Pesan Masuk (Kiri - Putih) */
    .message-bubble.incoming {
        align-self: flex-start;
        background-color: #ffffff;
        border-top-left-radius: 0;
    }
    
    /* Pesan Keluar (Kanan - Hijau Muda) */
    .message-bubble.outgoing {
        align-self: flex-end;
        background-color: #d9fdd3;
        border-top-right-radius: 0;
    }

    .msg-time {
        display: block;
        font-size: 10px;
        color: #999;
        text-align: right;
        margin-top: 4px;
    }

    /* INPUT AREA */
    .chat-input-area {
        padding: 10px;
        background: #f0f2f5;
        display: flex;
        align-items: center;
    }
    .chat-input-area textarea {
        resize: none;
        border-radius: 20px;
        padding: 10px 15px;
    }
</style>

<div class="container-fluid">
    <h3 class="mb-3">WhatsApp Inbox</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="wa-container">
        
        <div class="wa-sidebar">
            <div class="p-3 bg-light border-bottom">
                <input type="text" class="form-control rounded-pill" placeholder="Cari chat...">
            </div>
            
            <div class="wa-contacts-list">
                @foreach($contacts as $contact)
                    <a href="{{ route('whatsapp.index', ['phone' => $contact->sender_number]) }}" class="text-decoration-none text-dark">
                        <div class="wa-contact-item {{ $activePhone == $contact->sender_number ? 'active' : '' }}">
                            <div class="avatar-circle">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $contact->sender_name ?: $contact->sender_number }}</strong>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($contact->last_msg_time)->format('H:i') }}</small>
                                </div>
                                <small class="text-muted text-truncate d-block" style="max-width: 150px;">
                                    {{ $contact->sender_number }}
                                </small>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="wa-chat-area">
            @if($activePhone)
                <div class="chat-header">
                    <div class="avatar-circle" style="width: 40px; height: 40px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h6 class="m-0">{{ $activePhone }}</h6>
                        <small class="text-muted">Online</small>
                    </div>
                </div>

                <div class="chat-messages" id="chatBox">
                    @forelse($activeChat as $chat)
                        <div class="message-bubble {{ $chat->type == 'outgoing' ? 'outgoing' : 'incoming' }}">
                            {{ $chat->message }}
                            <span class="msg-time">
                                {{ \Carbon\Carbon::parse($chat->created_at)->format('H:i') }}
                                @if($chat->type == 'outgoing')
                                    <i class="fas fa-check-double text-primary" style="font-size: 10px;"></i>
                                @endif
                            </span>
                        </div>
                    @empty
                        <div class="text-center mt-5">
                            <span class="badge bg-secondary">Belum ada riwayat chat</span>
                        </div>
                    @endforelse
                </div>

                <div class="chat-input-area">
                    <form action="{{ url('/whatsapp/send') }}" method="POST" class="d-flex w-100 gap-2">
                        @csrf
                        <input type="hidden" name="target" value="{{ $activePhone }}">
                        
                        <textarea name="message" class="form-control" rows="1" placeholder="Ketik pesan..." required></textarea>
                        
                        <button type="submit" class="btn btn-success rounded-circle" style="width: 50px; height: 45px;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

            @else
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                    <i class="fab fa-whatsapp" style="font-size: 80px; color: #ddd;"></i>
                    <h5 class="mt-3">WhatsApp Web Laravel</h5>
                    <p>Pilih kontak di sebelah kiri untuk melihat pesan.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Fitur: Otomatis Scroll ke Bawah saat halaman dimuat
    document.addEventListener("DOMContentLoaded", function() {
        var chatBox = document.getElementById("chatBox");
        if(chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    });
</script>

@endsection