#!/bin/bash
# start.sh - Universal starter untuk semua environment

echo "========================================="
echo "🚀 PRODUCT FINDER - UNIVERSAL STARTER"
echo "========================================="
echo ""

# Deteksi environment
if [ -d "/storage/data" ]; then
    ENV="railway"
    echo "📡 Environment: RAILWAY"
elif [ -d "/data/data/com.termux" ] || [ -n "$TERMUX_VERSION" ]; then
    ENV="termux"
    echo "📡 Environment: TERMUX"
else
    ENV="standard"
    echo "📡 Environment: STANDARD"
fi

# Setup berdasarkan environment
case $ENV in
    railway)
        echo "📁 Setting up Railway storage..."
        mkdir -p /storage/data
        chmod 777 /storage/data
        
        if [ -f "barcodesheet (1).json" ]; then
            cp "barcodesheet (1).json" /storage/data/barcodesheet.json
        else
            echo "[]" > /storage/data/barcodesheet.json
        fi
        chmod 666 /storage/data/barcodesheet.json
        cp /storage/data/barcodesheet.json /storage/data/barcodesheet.json.backup
        echo "✅ Storage ready at /storage/data"
        ;;
        
    termux)
        echo "📁 Setting up Termux storage..."
        mkdir -p data
        chmod 777 data
        
        if [ -f "barcodesheet (1).json" ]; then
            cp "barcodesheet (1).json" data/barcodesheet.json
        else
            echo "[]" > data/barcodesheet.json
        fi
        chmod 666 data/barcodesheet.json
        cp data/barcodesheet.json data/barcodesheet.json.backup
        echo "✅ Storage ready at ./data"
        ;;
        
    standard)
        echo "📁 Using current directory..."
        if [ ! -f "barcodesheet.json" ] && [ -f "barcodesheet (1).json" ]; then
            cp "barcodesheet (1).json" barcodesheet.json
        fi
        chmod 666 barcodesheet.json 2>/dev/null || true
        ;;
esac

echo ""
echo "🌐 Starting PHP server on port 8080..."
echo "📱 Access at: http://localhost:8080"
echo ""

# Dapatkan IP untuk akses dari HP lain
if command -v ip &> /dev/null; then
    IP=$(ip route get 1 2>/dev/null | awk '{print $NF;exit}')
    if [ -n "$IP" ]; then
        echo "📱 From other devices: http://$IP:8080"
    fi
fi

echo ""
echo "========================================="
echo "✅ Server is running! Press Ctrl+C to stop"
echo "========================================="
echo ""

# Jalankan PHP server
php -S 0.0.0.0:8080 -t .
