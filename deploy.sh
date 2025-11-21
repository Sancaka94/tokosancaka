#!/bin/bash
cd /home/tokq3391/public_html || exit

# Tambahkan semua perubahan
git add .

# Commit dengan timestamp supaya tidak bentrok
git commit -m "Auto commit from server on $(date '+%Y-%m-%d %H:%M:%S')"

# Push ke GitHub
git push origin master
