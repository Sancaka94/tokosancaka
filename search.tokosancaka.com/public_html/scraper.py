from telethon.sync import TelegramClient
import sqlite3
import asyncio

# 1. Konfigurasi Kredensial Telegram Anda
api_id = 34302401
api_hash = 'c7eec7fb276ef7a4d1da69a8dab2a50d'
target_group = 'https://t.me/forumsalafy' # Ganti dengan grup target

# 2. Setup Database Mesin Pencari
conn = sqlite3.connect('telegram_search.db')
cursor = conn.cursor()

# Membuat tabel virtual SQLite FTS5 untuk pencarian super cepat
cursor.execute('''
    CREATE VIRTUAL TABLE IF NOT EXISTS messages 
    USING fts5(group_name, sender_id, message_text)
''')
conn.commit()

client = TelegramClient('sesi_search_engine', api_id, api_hash)

async def scrape_and_index():
    print(f"[*] Memulai pengambilan riwayat pesan dari {target_group}...")
    
    # Menarik 1000 pesan terakhir
    async for message in client.iter_messages(target_group, limit=1000):
        if message.text:
            cursor.execute("INSERT INTO messages (group_name, sender_id, message_text) VALUES (?, ?, ?)",
                           (target_group, message.sender_id, message.text))
            print(f"[+] Diindeks: {message.text[:30]}...")
            
    conn.commit()
    print("[*] Selesai menyimpan dan mengindeks data Telegram ke database.")

with client:
    client.loop.run_until_complete(scrape_and_index())