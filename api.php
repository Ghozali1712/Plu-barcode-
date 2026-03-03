<?php
// api.php - Backend untuk menyimpan langsung ke file JSON asli
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Nama file database ASLI
$dataFile = 'barcodesheet (1).json';
$backupFile = 'barcodesheet (1).json.backup';

// Fungsi untuk membaca data dari file asli
function readData() {
    global $dataFile;
    
    if (!file_exists($dataFile)) {
        // Jika file tidak ada, buat file baru dengan array kosong
        file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    
    $content = file_get_contents($dataFile);
    $data = json_decode($content, true);
    
    if ($data === null) {
        // Jika JSON tidak valid, backup dan buat baru
        if (file_exists($dataFile)) {
            copy($dataFile, $dataFile . '.error.' . date('Y-m-d-His'));
        }
        file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    
    return $data;
}

// Fungsi untuk menulis data ke file asli
function writeData($data) {
    global $dataFile, $backupFile;
    
    // Buat backup sebelum menulis
    if (file_exists($dataFile)) {
        copy($dataFile, $backupFile);
    }
    
    // Tulis data baru dengan format JSON yang rapi
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    // Simpan ke file
    $result = file_put_contents($dataFile, $json, LOCK_EX);
    
    if ($result === false) {
        // Jika gagal, coba dengan permission 666
        chmod($dataFile, 0666);
        $result = file_put_contents($dataFile, $json, LOCK_EX);
    }
    
    return $result !== false;
}

// Fungsi untuk mencari produk
function findProduct($products, $plu) {
    foreach ($products as $index => $product) {
        if (isset($product['plu']) && $product['plu'] == $plu) {
            return $index;
        }
    }
    return -1;
}

// Get action dari request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle berdasarkan method
switch ($method) {
    case 'GET':
        if ($action === 'get') {
            // Ambil semua produk
            $products = readData();
            echo json_encode([
                'success' => true,
                'products' => $products,
                'total' => count($products),
                'file' => basename($dataFile)
            ]);
        }
        elseif ($action === 'search') {
            // Search produk
            $query = $_GET['q'] ?? '';
            $products = readData();
            
            if (!$query) {
                echo json_encode([
                    'success' => true,
                    'products' => []
                ]);
                exit;
            }
            
            $queries = array_map('trim', explode(',', $query));
            $results = [];
            $notFound = [];
            
            foreach ($queries as $q) {
                if (empty($q)) continue;
                
                $lowerQ = strtolower($q);
                $found = false;
                
                // Cari berdasarkan PLU (exact match)
                foreach ($products as $product) {
                    if (isset($product['plu']) && $product['plu'] == $q) {
                        $results[] = $product;
                        $found = true;
                        break;
                    }
                }
                
                // Jika tidak ditemukan, cari berdasarkan nama (partial match)
                if (!$found) {
                    foreach ($products as $product) {
                        if (isset($product['nama']) && stripos($product['nama'], $lowerQ) !== false) {
                            $results[] = $product;
                            $found = true;
                            break;
                        }
                    }
                }
                
                if (!$found) {
                    $notFound[] = $q;
                }
            }
            
            // Hapus duplikat berdasarkan PLU
            $unique = [];
            $seen = [];
            foreach ($results as $product) {
                if (!in_array($product['plu'], $seen)) {
                    $unique[] = $product;
                    $seen[] = $product['plu'];
                }
            }
            
            echo json_encode([
                'success' => true,
                'products' => $unique,
                'notFound' => $notFound,
                'total' => count($unique)
            ]);
        }
        else {
            echo json_encode([
                'success' => false,
                'message' => 'Action tidak dikenal'
            ]);
        }
        break;
        
    case 'POST':
        if ($action === 'add') {
            // Tambah produk baru
            $input = json_decode(file_get_contents('php://input'), true);
            $product = $input['product'] ?? null;
            
            if (!$product || empty($product['plu']) || empty($product['nama'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'PLU dan Nama harus diisi'
                ]);
                exit;
            }
            
            // Baca data existing
            $products = readData();
            
            // Cek duplikat PLU
            if (findProduct($products, $product['plu']) !== -1) {
                echo json_encode([
                    'success' => false,
                    'message' => "PLU {$product['plu']} sudah ada di database"
                ]);
                exit;
            }
            
            // Buat produk baru dengan format sesuai file asli
            $newProduct = [
                'plu' => $product['plu'],
                'barcode' => $product['barcode'] ?? '',
                'nama' => $product['nama'],
                'gambar' => $product['gambar'] ?? ''
            ];
            
            // Tambah ke array
            $products[] = $newProduct;
            
            // Urutkan berdasarkan PLU (optional)
            usort($products, function($a, $b) {
                return strcmp($a['plu'], $b['plu']);
            });
            
            // Simpan ke file ASLI
            if (writeData($products)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Produk berhasil ditambahkan ke database',
                    'product' => $newProduct,
                    'total' => count($products)
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menyimpan ke file. Cek permission file.'
                ]);
            }
        }
        else {
            echo json_encode([
                'success' => false,
                'message' => 'Action tidak dikenal'
            ]);
        }
        break;
        
    case 'PUT':
        // Update produk
        $input = json_decode(file_get_contents('php://input'), true);
        $product = $input['product'] ?? null;
        
        if (!$product || empty($product['plu'])) {
            echo json_encode([
                'success' => false,
                'message' => 'PLU harus diisi'
            ]);
            exit;
        }
        
        $products = readData();
        $index = findProduct($products, $product['plu']);
        
        if ($index === -1) {
            echo json_encode([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ]);
            exit;
        }
        
        // Update data
        if (isset($product['nama'])) $products[$index]['nama'] = $product['nama'];
        if (isset($product['barcode'])) $products[$index]['barcode'] = $product['barcode'];
        if (isset($product['gambar'])) $products[$index]['gambar'] = $product['gambar'];
        
        if (writeData($products)) {
            echo json_encode([
                'success' => true,
                'message' => 'Produk berhasil diupdate'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyimpan ke file'
            ]);
        }
        break;
        
    case 'DELETE':
        // Hapus produk
        $input = json_decode(file_get_contents('php://input'), true);
        $plu = $input['plu'] ?? $_GET['plu'] ?? '';
        
        if (!$plu) {
            echo json_encode([
                'success' => false,
                'message' => 'PLU harus diisi'
            ]);
            exit;
        }
        
        $products = readData();
        $index = findProduct($products, $plu);
        
        if ($index === -1) {
            echo json_encode([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ]);
            exit;
        }
        
        // Hapus produk
        array_splice($products, $index, 1);
        
        if (writeData($products)) {
            echo json_encode([
                'success' => true,
                'message' => "Produk dengan PLU $plu berhasil dihapus"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyimpan ke file'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Method tidak didukung'
        ]);
        break;
}
?>
