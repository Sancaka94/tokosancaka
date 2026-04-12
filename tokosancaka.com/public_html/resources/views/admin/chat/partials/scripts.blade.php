<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- ELEMEN DOM ---
    const chatPanel = document.getElementById('main-chat-panel');
    const welcomePanel = document.getElementById('welcome-panel');
    const chatMessages = document.getElementById('chat-messages');
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-button');
    const imageUploadInput = document.getElementById('image-upload-input');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const imagePreview = document.getElementById('image-preview');
    const removeImageBtn = document.getElementById('remove-image-btn');
    const chatOptionsBtn = document.getElementById('chat-options-btn');
    const chatOptionsMenu = document.getElementById('chat-options-menu');

    let activeContactId = null;
    let selectedImageFile = null;

    // --- 1. TOGGLE MENU TITIK TIGA ---
    chatOptionsBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        chatOptionsMenu.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
        chatOptionsMenu.classList.add('hidden');
    });

    // --- 2. LOGIKA UPLOAD GAMBAR ---
    imageUploadInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            selectedImageFile = file;
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreviewContainer.classList.remove('hidden');
                messageInput.disabled = false; // Buka kunci input
                sendBtn.disabled = false;
            }
            reader.readAsDataURL(file);
        }
    });

    removeImageBtn.addEventListener('click', function() {
        selectedImageFile = null;
        imageUploadInput.value = '';
        imagePreviewContainer.classList.add('hidden');
        if(messageInput.value.trim() === '') {
            sendBtn.disabled = true;
        }
    });

    // --- 3. PARSER PRODUK DARI REACT NATIVE ---
    // Fungsi ini wajib agar teks [TANYA PRODUK] berubah jadi Card UI
    function parseMessage(text) {
        if (!text) return '';

        // Cek apakah pesan adalah template produk
        if (text.startsWith('[TANYA PRODUK]') || text.startsWith('[INFO PRODUK]')) {
            const lines = text.split('\n');
            if (lines.length >= 3) {
                let imgUri = 'https://placehold.co/50x50';
                if (lines[3] && lines[3].trim() !== '') {
                    imgUri = lines[3].startsWith('http') ? lines[3] : `/storage/${lines[3]}`;
                }
                return `
                    <div style="border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; background: #f8fafc; margin-bottom: 5px; width: 200px;">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <img src="${imgUri}" style="width: 50px; height: 50px; border-radius: 4px; object-fit: cover;">
                            <div>
                                <div style="font-size: 12px; font-weight: bold; color: #1e293b; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">${lines[1]}</div>
                                <div style="font-size: 12px; color: #dc2626; font-weight: bold; margin-top: 2px;">${lines[2].replace('Harga: ', '')}</div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        // Jika teks biasa, aman-kan dari XSS
        return text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    // --- 4. RENDER PESAN KE LAYAR ---
    function appendMessage(sender, text, time, imageUrl = null) {
        const isOut = sender === 'admin';
        let innerHTML = '';

        // Tampilkan gambar jika ada
        if (imageUrl) {
            innerHTML += `<img src="${imageUrl}" style="max-width: 100%; border-radius: 6px; margin-bottom: 5px; max-height: 200px; object-fit: contain;">`;
        }

        // Tampilkan teks / Card Produk
        innerHTML += parseMessage(text);
        innerHTML += `<span class="message-time">${time}</span>`;

        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${isOut ? 'message-out' : 'message-in'}`;
        msgDiv.innerHTML = innerHTML;

        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight; // Auto scroll ke bawah
    }

    // --- 5. LOGIKA INPUT TEKS & KIRIM ---
    messageInput.addEventListener('input', function() {
        sendBtn.disabled = this.value.trim() === '' && !selectedImageFile;
    });

    sendBtn.addEventListener('click', function() {
        const text = messageInput.value.trim();
        if (text === '' && !selectedImageFile) return;

        // Tampilkan di layar (Optimistic UI)
        const now = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        appendMessage('admin', text, now, selectedImageFile ? URL.createObjectURL(selectedImageFile) : null);

        // Reset Input
        messageInput.value = '';
        selectedImageFile = null;
        imageUploadInput.value = '';
        imagePreviewContainer.classList.add('hidden');
        sendBtn.disabled = true;

        /*
        // TODO: Kirim data pakai Fetch / Axios ke endpoint Laravel
        let formData = new FormData();
        formData.append('contact_id', activeContactId);
        formData.append('message', text);
        if(selectedImageFile) { formData.append('image', selectedImageFile); }

        axios.post('/api/admin/chat/send', formData).then(...)
        */
    });

    // Enter untuk kirim pesan
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendBtn.click();
        }
    });
});
</script>
