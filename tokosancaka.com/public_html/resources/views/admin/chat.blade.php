@extends('layouts.admin')

@section('title', 'Live Chat Admin')

@push('styles')
    @include('chat.partials.styles')
    <style>
        /* Tambahan CSS Dasar untuk Elemen Baru (Pindahkan ke partials.styles nanti) */
        .hidden { display: none !important; }
        .image-preview-container {
            position: absolute; bottom: 70px; left: 20px;
            background: #fff; padding: 10px; border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;
        }
        .image-preview-container img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; }
        .remove-image-btn {
            position: absolute; top: -10px; right: -10px;
            background: #ef4444; color: white; border: none;
            border-radius: 50%; width: 24px; height: 24px; cursor: pointer;
        }
        .chat-header { display: flex; justify-content: space-between; align-items: center; }
        .dropdown-menu {
            position: absolute; top: 60px; right: 20px; background: white;
            border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 10px; z-index: 100;
        }
        .text-danger { color: #ef4444; background: none; border: none; cursor: pointer; padding: 8px; width: 100%; text-align: left;}
        .text-danger:hover { background: #fef2f2; }
        .typing-indicator { font-size: 12px; color: #10b981; font-style: italic; padding: 0 20px 5px; }
    </style>
@endpush

@section('content')
<div class="chat-container">
    <div class="sidebar">
        <header class="sidebar-header">
            <img src="{{ Auth::user()->avatar_url ?? 'https://i.pravatar.cc/150?u=admin' }}" alt="Avatar" class="avatar">
            <h3>Admin Chat</h3>
        </header>
        <div class="sidebar-search">
             <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="search-contact" placeholder="Cari pelanggan...">
             </div>
        </div>
        <div class="conversation-list" id="conversation-list">
            </div>
    </div>

    <div class="chat-panel">
        <div id="welcome-panel" style="display: flex; justify-content:center; align-items:center; height:100%; color: #667781;">
            <h2>Pilih percakapan untuk memulai</h2>
        </div>

        <div id="main-chat-panel" class="hidden" style="position: relative; display: flex; flex-direction: column; height: 100%;">

            <header class="chat-header" id="chat-header">
                <div class="header-info" id="header-user-info" style="display: flex; align-items: center; gap: 10px;">
                    </div>
                <div class="header-actions">
                    <button id="chat-options-btn" style="background:none; border:none; font-size: 18px; cursor: pointer;">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <div id="chat-options-menu" class="hidden dropdown-menu">
                        <button class="text-danger" id="delete-chat-btn">
                            <i class="fa-solid fa-trash"></i> Hapus Riwayat Chat
                        </button>
                    </div>
                </div>
            </header>

            <div class="chat-messages" id="chat-messages" style="flex: 1; overflow-y: auto; padding: 20px;">
                </div>

            <div id="typing-indicator" class="typing-indicator hidden">
                Pelanggan sedang mengetik...
            </div>

            <div id="image-preview-container" class="image-preview-container hidden">
                <img id="image-preview" src="" alt="Preview">
                <button id="remove-image-btn" class="remove-image-btn"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="chat-input" style="padding: 15px; background: #f0f2f5; display: flex; gap: 10px; align-items: center;">
                <button id="emoji-btn"><i class="fa-regular fa-face-smile"></i></button>

                <input type="file" id="image-upload-input" accept="image/png, image/jpeg, image/webp" class="hidden">
                <button id="attachment-btn" onclick="document.getElementById('image-upload-input').click()">
                    <i class="fa-solid fa-paperclip"></i>
                </button>

                <input type="text" id="message-input" placeholder="Ketik pesan" disabled style="flex: 1; padding: 10px; border-radius: 20px; border: 1px solid #ccc;">

                <button id="send-button" disabled style="background: #008071; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer;">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('chat.partials.scripts', ['userRole' => 'admin'])
@endpush
