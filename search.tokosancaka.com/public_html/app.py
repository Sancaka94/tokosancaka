import streamlit as st
import pandas as pd
from telethon.sync import TelegramClient
import asyncio
import os

# Konfigurasi Halaman (Centered ala Google)
st.set_page_config(page_title="Sancaka Search", page_icon="🔍", layout="centered")

FILE_CSV = 'list_group_telegram.csv'
MEDIA_DIR = os.path.abspath('media_downloads')

# Inisialisasi Memori
if "messages" not in st.session_state:
    st.session_state.messages = [] 
if "admin_logged_in" not in st.session_state:
    st.session_state.admin_logged_in = False

# --- TAMPILAN BERANDA (GOOGLE CLONE) ---
def tampilkan_beranda():
    st.markdown("""
    <div style='text-align: center; margin-top: 10vh;'>
        <h1 style='font-size: 5rem; font-weight: bold; letter-spacing: -3px;'>
            <span style='color:#4285F4'>S</span><span style='color:#EA4335'>a</span><span style='color:#FBBC05'>n</span><span style='color:#4285F4'>c</span><span style='color:#34A853'>a</span><span style='color:#EA4335'>k</span><span style='color:#FBBC05'>a</span>
        </h1>
        <p style='color: gray; font-size: 1.2rem; margin-top: -20px;'>Mesin Pencari Artikel & Kajian Salafy</p>
    </div>
    """, unsafe_allow_html=True)

# --- LOGIKA UTAMA ---
if len(st.session_state.messages) == 0:
    tampilkan_beranda()

# Render Riwayat (Jika ada hasil)
for message in st.session_state.messages:
    with st.chat_message(message["role"]):
        st.markdown(message["content"])

# Input Pencarian (Selalu di bawah)
prompt = st.chat_input("Apa yang ingin Anda cari hari ini?")

if prompt:
    # Simulasikan hasil (Ganti dengan fungsi cari_di_grup_terdaftar Anda)
    st.session_state.messages = [{"role": "user", "content": prompt}]
    with st.chat_message("assistant"):
        st.info(f"🔎 Mencari '{prompt}' di Telegram...")
        # Panggil fungsi pencarian Anda di sini...
    st.rerun()

# Tombol Login Admin Tersembunyi (Sidebar)
with st.sidebar:
    if not st.session_state.admin_logged_in:
        with st.expander("🔐 Admin Login"):
            user = st.text_input("Username")
            pw = st.text_input("Password", type="password")
            if st.button("Masuk"):
                if user == st.secrets["admin_username"] and pw == st.secrets["admin_password"]:
                    st.session_state.admin_logged_in = True
                    st.rerun()
    else:
        st.success("👨‍💻 Mode Admin Aktif")
        if st.button("Logout"):
            st.session_state.admin_logged_in = False
            st.rerun()