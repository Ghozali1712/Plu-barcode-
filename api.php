<?php
// api.php - Universal Backend (Works everywhere)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Deteksi environment secara otomatis
function detectEnvironment() {
    // Cek Railway
    if (getenv('RAILWAY_ENVIRONMENT') !== false || getenv('RAILWAY_SERVICE_NAME') !== false) {
        return 'railway';
    }
    
    // Cek Termux
    if (is_dir('/data/data/com.termux') || getenv('TERMUX_VERSION') !== false) {
        return 'termux';
    }
    
    // Cek hosting biasa
    return 'standard';
}

$env = detectEnvironment();

// Tentukan lokasi file berdasarkan environment
switch ($env) {
    case 'railway':
        $storageDir = '/storage/data';
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0777, true);
        }
        $dataFile = $storageDir . '/barcodesheet.json';
        $backupFile = $storageDir . '/barcodesheet.json.backup';
        break;
        
    case 'termux':
        $storageDir = __DIR__ . '/data';
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0777, true);
        }
        $dataFile = $storageDir . '/barcodesheet.json';
        $backupFile = $storageDir . '/barcodesheet.json.backup';
        break;
        
    default:
        $dataFile = __DIR__ . '/barcodesheet.json';
        $backupFile = __DIR__ . '/barcodesheet.json.backup';
        break;
}

// Fungsi membaca data
function readData() {
    global $dataFile;
    
    if (!file_exists($dataFile)) {
        $sourceFile = __DIR__ . '/barcodesheet (1).json';
        if (file_exists($sourceFile)) {
            copy($sourceFile, $dataFile);
            chmod($dataFile, 0666);
        } else {
            file_put_contents($dataFile, json_encode([]));
            chmod($dataFile, 0666);
        }
    }
    
    $content = file_get_contents($dataFile);
    return json_decode($content, true) ?: [];
}

// Fungsi menulis data
function writeData($data) {
    global $dataFile, $backupFile;
    
    if (file_exists($dataFile)) {
        copy($dataFile, $backupFile);
    }
    
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $result = file_put_contents($dataFile, $json, LOCK_EX);
    
    if ($result !== false) {
        chmod($dataFile, 0666);
        return true;
    }
    return false;
}

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'get') {
            $products = readData();
            echo json_encode([
                'success' => true,
                'products' => $products,
                'total' => count($products),
                'environment' => $env,
                'storage' => dirname($dataFile)
            ]);
        }
        elseif ($action === 'search') {
            $query = $_GET['q'] ?? '';
            $products = readData();
            
            if (!$query) {
                echo json_encode(['success' => true, 'products' => []]);
                exit;
            }
            
            $queries = array_map('trim', explode(',', $query));
            $results = [];
            $notFound = [];
            
            foreach ($queries as $q) {
                if (empty($q)) continue;
                
                $lowerQ = strtolower($q);
                $found = false;
                
                // Cari PLU
                foreach ($products as $p) {
                    if (isset($p['plu']) && $p['plu'] == $q) {
                        $results[] = $p;
                        $found = true;
                        break;
                    }
                }
                
                // Cari nama
                if (!$found) {
                    foreach ($products as $p) {
                        if (isset($p['nama']) && stripos($p['nama'], $lowerQ) !== false) {
                            $results[] = $p;
                            $found = true;
                            break;
                        }
                    }
                }
                
                if (!$found) {
                    $notFound[] = $q;
                }
            }
            
            // Hapus duplikat
            $unique = [];
            $seen = [];
            foreach ($results as $p) {
                if (!in_array($p['plu'], $seen)) {
                    $unique[] = $p;
                    $seen[] = $p['plu'];
                }
            }
            
            echo json_encode([
                'success' => true,
                'products' => $unique,
                'notFound' => $notFound,
                'total' => count($unique)
            ]);
        }
        break;
        
    case 'POST':
        if ($action === 'add') {
            $input = json_decode(file_get_contents('php://input'), true);
            $product = $input['product'] ?? null;
            
            if (!$product || empty($product['plu']) || empty($product['nama'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'PLU and Nama are required'
                ]);
                exit;
            }
            
            $products = readData();
            
            // Cek duplikat
            foreach ($products as $p) {
                if ($p['plu'] == $product['plu']) {
                    echo json_encode([
                        'success' => false,
                        'message' => "PLU {$product['plu']} already exists"
                    ]);
                    exit;
                }
            }
            
            $newProduct = [
                'plu' => $product['plu'],
                'barcode' => $product['barcode'] ?? '',
                'nama' => $product['nama'],
                'gambar' => $product['gambar'] ?? ''
            ];
            
            $products[] = $newProduct;
            
            // Urutkan
            usort($products, function($a, $b) {
                return strcmp($a['plu'], $b['plu']);
            });
            
            if (writeData($products)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added successfully',
                    'product' => $newProduct,
                    'total' => count($products)
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to save file'
                ]);
            }
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Method not supported'
        ]);
        break;
}
?>
