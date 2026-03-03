#!/bin/bash
# railway-setup.sh - Setup khusus Railway

echo "====================================="
echo "🚂 PRODUCT FINDER - RAILWAY SETUP"
echo "====================================="
echo ""

# Buat storage directory
echo "📁 Creating storage directory..."
mkdir -p /storage/data
chmod 777 /storage/data

# Copy database
if [ -f "barcodesheet (1).json" ]; then
    echo "📋 Copying database..."
    cp "barcodesheet (1).json" /storage/data/barcodesheet.json
else
    echo "⚠️ No database found, creating empty..."
    echo "[]" > /storage/data/barcodesheet.json
fi

chmod 666 /storage/data/barcodesheet.json

# Buat backup
cp /storage/data/barcodesheet.json /storage/data/barcodesheet.json.backup

echo "📂 Storage contents:"
ls -la /storage/data/

echo ""
echo "✅ SETUP COMPLETE!"
echo "====================================="
