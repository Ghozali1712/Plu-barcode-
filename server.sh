#!/bin/bash
# server.sh - Jalankan server dengan write permission ke file asli

echo "==================================="
echo "🚀 Product Finder Server"
echo "==================================="
echo ""

# Cek apakah file JSON ada
if [ ! -f "barcodesheet (1).json" ]; then
    echo "❌ File barcodesheet (1).json tidak ditemukan!"
    echo "📁 Pastikan file JSON ada di folder: $(pwd)"
    exit 1
fi

# Cek permission file
echo "🔍 Cek permission file..."
ls -la barcodesheet\ \(1\).json

# Set permission write untuk semua user
echo "📝 Mengatur permission file..."
chmod 666 barcodesheet\ \(1\).json

if [ $? -eq 0 ]; then
    echo "✅ Permission berhasil diatur: 666 (read-write untuk semua)"
else
    echo "❌ Gagal mengatur permission"
    exit 1
fi

# Buat backup awal
cp barcodesheet\ \(1\).json barcodesheet\ \(1\).json.backup
echo "💾 Backup awal dibuat: barcodesheet (1).json.backup"

# Cari port yang tersedia
PORT=8080
while netstat -an 2>/dev/null | grep -q ":$PORT "; do
    PORT=$((PORT + 1))
done

echo ""
echo "📁 Working directory: $(pwd)"
echo "📦 Database: barcodesheet (1).json (READ-WRITE)"
echo "💾 Backup: barcodesheet (1).json.backup"
echo "🌐 Server: http://localhost:$PORT"
echo ""

# Dapatkan IP lokal
IP=$(ifconfig 2>/dev/null | grep -oP 'inet\s+\d+\.\d+\.\d+\.\d+' | grep -v '127.0.0.1' | head -1 | awk '{print $2}')
if [ -n "$IP" ]; then
    echo "📱 Akses dari HP lain: http://$IP:$PORT"
else
    echo "📱 Untuk akses dari HP lain, gunakan IP lokal perangkat"
fi

echo ""
echo "⏳ Tekan Ctrl+C untuk menghentikan server"
echo "==================================="
echo ""

# Jalankan PHP server
php -S 0.0.0.0:$PORT -t .
