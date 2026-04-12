<style>
    /* Reset & Layout Dasar */
    .chat-container { display: flex; height: calc(100vh - 100px); background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
    .sidebar { width: 300px; background: #f8fafc; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; }
    .chat-panel { flex: 1; background: #e5ddd5; position: relative; } /* Warna background ala WA */

    /* Utility Classes */
    .hidden { display: none !important; }

    /* Sidebar Elements */
    .sidebar-header { padding: 15px; background: #f1f5f9; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #e2e8f0; }
    .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .sidebar-search { padding: 10px; background: white; border-bottom: 1px solid #e2e8f0; }
    .search-container { position: relative; }
    .search-container i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .search-container input { width: 100%; padding: 8px 10px 8px 30px; border: 1px solid #e2e8f0; border-radius: 20px; font-size: 13px; outline: none; }
    .conversation-list { flex: 1; overflow-y: auto; }
    .contact-item { display: flex; align-items: center; gap: 10px; padding: 12px 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.2s; }
    .contact-item:hover { background: #f1f5f9; }
    .contact-item.active { background: #e2e8f0; }

    /* Header Chat */
    .chat-header { padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .header-info h4 { margin: 0; font-size: 16px; color: #1e293b; }
    .header-info p { margin: 0; font-size: 12px; color: #64748b; }

    /* Menu Opsi (Titik Tiga) */
    .dropdown-menu { position: absolute; top: 60px; right: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 5px 0; z-index: 100; min-width: 180px; }
    .dropdown-menu button { width: 100%; text-align: left; padding: 10px 15px; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 14px; }
    .text-danger { color: #ef4444; }
    .text-danger:hover { background: #fef2f2; }

    /* Area Pesan */
    .chat-messages { padding: 20px; display: flex; flex-direction: column; gap: 10px; }
    .message { max-width: 70%; padding: 10px 15px; border-radius: 8px; position: relative; font-size: 14px; line-height: 1.4; }
    .message-in { background: white; align-self: flex-start; border-top-left-radius: 0; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .message-out { background: #dcf8c6; align-self: flex-end; border-top-right-radius: 0; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .message-time { font-size: 10px; color: #64748b; text-align: right; margin-top: 5px; display: block; }

    /* Upload Gambar & Typing */
    .image-preview-container { position: absolute; bottom: 80px; left: 20px; background: #fff; padding: 10px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; z-index: 50; }
    .image-preview-container img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; }
    .remove-image-btn { position: absolute; top: -10px; right: -10px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .typing-indicator { font-size: 12px; color: #10b981; font-style: italic; padding: 0 20px 5px; }
</style>
