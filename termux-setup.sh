#!/bin/bash
# termux-setup.sh - Setup khusus Termux

echo "====================================="
echo "📦 PRODUCT FINDER - TERMUX SETUP"
echo "====================================="
echo ""

# Update packages
echo "📥 Updating packages..."
pkg update -y

# Install PHP
echo "📥 Installing PHP..."
pkg install php -y

# Buat folder data
echo "📁 Creating data folder..."
mkdir -p data
chmod 777 data

# Copy database
if [ -f "barcodesheet (1).json" ]; then
    echo "📋 Copying database..."
    cp "barcodesheet (1).json" data/barcodesheet.json
    chmod 666 data/barcodesheet.json
else
    echo "⚠️ No database found, creating empty..."
    echo "[]" > data/barcodesheet.json
fi

# Buat backup
cp data/barcodesheet.json data/barcodesheet.json.backup

echo ""
echo "✅ SETUP COMPLETE!"
echo ""
echo "🚀 To start server:"
echo "   ./start.sh"
echo ""
echo "📱 Access at: http://localhost:8080"
echo "====================================="
