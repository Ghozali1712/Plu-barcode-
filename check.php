<?php
// check.php - Cek status file dan permission

$dataFile = 'barcodesheet (1).json';
$backupFile = 'barcodesheet (1).json.backup';

echo "===================================\n";
echo "🔍 PRODUCT FINDER - SYSTEM CHECK\n";
echo "===================================\n\n";

// Cek file exists
echo "📁 File Database: $dataFile\n";
if (file_exists($dataFile)) {
    echo "   ✅ File ditemukan\n";
    
    // Cek permission
    $perms = fileperms($dataFile);
    $perms_str = substr(sprintf('%o', $perms), -4);
    echo "   🔑 Permission: $perms_str\n";
    
    if (is_writable($dataFile)) {
        echo "   ✅ File dapat DITULIS\n";
    } else {
        echo "   ❌ File TIDAK dapat ditulis!\n";
    }
    
    if (is_readable($dataFile)) {
        echo "   ✅ File dapat DIBACA\n";
    } else {
        echo "   ❌ File TIDAK dapat dibaca!\n";
    }
    
    // Cek ukuran file
    $size = filesize($dataFile);
    echo "   📦 Ukuran: " . round($size / 1024, 2) . " KB\n";
    
    // Cek jumlah produk
    $content = file_get_contents($dataFile);
    $data = json_decode($content, true);
    if ($data) {
        echo "   📊 Jumlah produk: " . count($data) . "\n";
    } else {
        echo "   ❌ File JSON tidak valid!\n";
    }
    
} else {
    echo "   ❌ File TIDAK ditemukan!\n";
}

echo "\n";

// Cek backup
echo "💾 File Backup: $backupFile\n";
if (file_exists($backupFile)) {
    echo "   ✅ Backup ditemukan\n";
    $backup_size = filesize($backupFile);
    echo "   📦 Ukuran: " . round($backup_size / 1024, 2) . " KB\n";
} else {
    echo "   ⚠️  Backup belum dibuat\n";
}

echo "\n";
echo "===================================\n";
echo "🔧 Perbaikan jika error:\n";
echo "===================================\n";
echo "1. Set permission:\n";
echo "   chmod 666 barcodesheet\\ \\(1\\).json\n\n";
echo "2. Cek owner file:\n";
echo "   ls -la barcodesheet\\ \\(1\\).json\n\n";
echo "3. Jika masih error, backup dulu:\n";
echo "   cp barcodesheet\\ \\(1\\).json barcodesheet\\ \\(1\\).json.backup\n";
echo "===================================\n";
?>
