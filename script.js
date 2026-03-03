// Konfigurasi
const PRODUCTS_FILE = './barcodesheet (1).json';
let products = [];
let barcodeCache = new Map();
let imageCache = new Map();

// Load data produk
async function loadProducts() {
    try {
        const response = await fetch(PRODUCTS_FILE);
        products = await response.json();
        console.log(`✓ Berhasil memuat ${products.length} produk`);
        updateStats();
    } catch (error) {
        console.error('Gagal memuat produk:', error);
        showError('Gagal memuat database produk');
    }
}

// Generate barcode menggunakan bwip-js (via CDN)
async function generateBarcode(barcode) {
    if (!barcode || barcode === 'null' || barcode === '') return null;
    
    // Cek cache
    if (barcodeCache.has(barcode)) {
        return barcodeCache.get(barcode);
    }
    
    try {
        // Gunakan bwip-js via CDN
        const canvas = document.createElement('canvas');
        await bwipjs.toCanvas(canvas, {
            bcid: 'code128',
            text: barcode.toString(),
            scale: 3,
            height: 10,
            includetext: true,
            textxalign: 'center',
        });
        
        const dataUrl = canvas.toDataURL('image/png');
        barcodeCache.set(barcode, dataUrl);
        return dataUrl;
    } catch (error) {
        console.error('Gagal generate barcode:', error);
        return null;
    }
}

// Fungsi pencarian produk
function searchProducts(query) {
    const queries = splitMultiQuery(query);
    const results = [];
    
    for (const q of queries) {
        const lowerQ = q.toLowerCase();
        
        // Cari berdasarkan PLU
        let product = products.find(p => p.plu === q);
        
        // Cari berdasarkan nama
        if (!product) {
            product = products.find(p => 
                p.nama && p.nama.toLowerCase().includes(lowerQ)
            );
        }
        
        if (product && !results.some(r => r.plu === product.plu)) {
            results.push(product);
        }
    }
    
    return results;
}

// Pisah multi query
function splitMultiQuery(query) {
    return query.split(/[,\n]/)
        .map(q => q.trim())
        .filter(q => q.length > 0);
}

// Tampilkan hasil pencarian
async function displayResults(products) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><p>Memproses gambar...</p></div>';
    
    if (products.length === 0) {
        resultsDiv.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>Tidak ada produk ditemukan</h3>
                <p>Coba gunakan kata kunci atau PLU yang berbeda</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    for (const product of products) {
        html += await createProductCard(product);
    }
    
    resultsDiv.innerHTML = html;
    
    // Add event listeners untuk tombol
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', downloadProductImage);
    });
    
    document.querySelectorAll('.copy-plu-btn').forEach(btn => {
        btn.addEventListener('click', copyPLU);
    });
    
    document.querySelectorAll('.copy-barcode-btn').forEach(btn => {
        btn.addEventListener('click', copyBarcode);
    });
    
    document.querySelectorAll('.product-image').forEach(img => {
        img.addEventListener('click', openModal);
    });
}

// Buat card produk
async function createProductCard(product) {
    const barcodeUrl = await generateBarcode(product.barcode);
    const hasImage = product.gambar && product.gambar !== 'null';
    const isRokok = product.nama && (
        product.nama.toLowerCase().includes('rokok') ||
        product.nama.toLowerCase().includes('filter') ||
        product.nama.toLowerCase().includes('sampoerna') ||
        product.nama.toLowerCase().includes('djarum')
    );
    
    return `
        <div class="product-card">
            <div class="product-header">
                <h3>${product.nama || 'Produk Tanpa Nama'}</h3>
            </div>
            
            <div class="product-image">
                ${hasImage ? 
                    `<img src="${product.gambar}" alt="${product.nama}" loading="lazy" onerror="this.src='https://via.placeholder.com/300x200?text=Gambar+Tidak+Tersedia'">` :
                    `<div style="text-align: center; color: #999;"><i class="fas fa-image" style="font-size: 50px;"></i><p>Gambar Tidak Tersedia</p></div>`
                }
            </div>
            
            ${isRokok ? `
                <div style="text-align: center; padding: 10px;">
                    <span style="display: inline-block; width: 50px; height: 50px; background: red; color: white; border-radius: 50%; line-height: 50px; font-weight: bold; font-size: 24px;">21+</span>
                </div>
            ` : ''}
            
            <div class="barcode-section">
                ${barcodeUrl ? 
                    `<img src="${barcodeUrl}" class="barcode-img" alt="Barcode">` :
                    `<p style="color: #999;">Barcode tidak tersedia</p>`
                }
                <div class="barcode-number">${product.barcode || '-'}</div>
            </div>
            
            <div class="product-details">
                <div class="detail-row">
                    <span class="detail-label">PLU:</span>
                    <span class="detail-value plu">${product.plu}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Barcode:</span>
                    <span class="detail-value">${product.barcode || '-'}</span>
                </div>
            </div>
            
            <div class="product-actions">
                <button class="action-btn download-btn" data-plu="${product.plu}" data-nama="${product.nama}">
                    <i class="fas fa-download"></i> Download
                </button>
                <button class="action-btn copy-plu-btn" data-plu="${product.plu}">
                    <i class="fas fa-copy"></i> PLU
                </button>
                <button class="action-btn copy-barcode-btn" data-barcode="${product.barcode}">
                    <i class="fas fa-copy"></i> Barcode
                </button>
            </div>
        </div>
    `;
}

// Download gambar produk
function downloadProductImage(e) {
    const plu = e.currentTarget.dataset.plu;
    const nama = e.currentTarget.dataset.nama;
    const product = products.find(p => p.plu === plu);
    
    if (product && product.gambar) {
        const link = document.createElement('a');
        link.href = product.gambar;
        link.download = `${plu}_${nama || 'produk'}.jpg`;
        link.click();
    } else {
        alert('Gambar tidak tersedia untuk produk ini');
    }
}

// Copy PLU
function copyPLU(e) {
    const plu = e.currentTarget.dataset.plu;
    navigator.clipboard.writeText(plu).then(() => {
        alert(`PLU ${plu} berhasil disalin!`);
    });
}

// Copy Barcode
function copyBarcode(e) {
    const barcode = e.currentTarget.dataset.barcode;
    if (barcode) {
        navigator.clipboard.writeText(barcode).then(() => {
            alert(`Barcode ${barcode} berhasil disalin!`);
        });
    } else {
        alert('Barcode tidak tersedia');
    }
}

// Open modal untuk gambar besar
function openModal(e) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const captionText = document.getElementById('modalCaption');
    const img = e.currentTarget.querySelector('img');
    
    if (img) {
        modal.style.display = 'block';
        modalImg.src = img.src;
        captionText.innerHTML = img.alt;
    }
}

// Update statistik
function updateStats() {
    const total = products.length;
    const withName = products.filter(p => p.nama && p.nama.trim() !== '').length;
    const withBarcode = products.filter(p => p.barcode && p.barcode !== 'null' && p.barcode !== '').length;
    const withImage = products.filter(p => p.gambar && p.gambar !== 'null' && p.gambar !== '').length;
    
    const statsPanel = document.getElementById('statsPanel');
    statsPanel.innerHTML = `
        <div class="stat-item">
            <i class="fas fa-box"></i>
            <div class="stat-value">${total}</div>
            <div class="stat-label">Total Produk</div>
        </div>
        <div class="stat-item">
            <i class="fas fa-tag"></i>
            <div class="stat-value">${withName}</div>
            <div class="stat-label">Dengan Nama</div>
        </div>
        <div class="stat-item">
            <i class="fas fa-barcode"></i>
            <div class="stat-value">${withBarcode}</div>
            <div class="stat-label">Dengan Barcode</div>
        </div>
        <div class="stat-item">
            <i class="fas fa-image"></i>
            <div class="stat-value">${withImage}</div>
            <div class="stat-label">Dengan Gambar</div>
        </div>
    `;
}

// Show error
function showError(message) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML = `
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <h3>Error</h3>
            <p>${message}</p>
        </div>
    `;
}

// Event listeners
document.addEventListener('DOMContentLoaded', async () => {
    await loadProducts();
    
    // Load bwip-js dari CDN
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/bwip-js@4.2.2/dist/bwip-js-min.js';
    document.head.appendChild(script);
    
    // Search button
    document.getElementById('searchBtn').addEventListener('click', async () => {
        const input = document.getElementById('searchInput').value.trim();
        if (input) {
            const results = searchProducts(input);
            await displayResults(results);
        }
    });
    
    // Enter key (Shift+Enter untuk new line)
    document.getElementById('searchInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('searchBtn').click();
        }
    });
    
    // Multi mode button
    document.getElementById('multiModeBtn').addEventListener('click', () => {
        document.getElementById('searchInput').value = '10000184, 10010091, 10000020';
    });
    
    // Clear button
    document.getElementById('clearBtn').addEventListener('click', () => {
        document.getElementById('searchInput').value = '';
        document.getElementById('results').innerHTML = '';
    });
    
    // Stats button
    document.getElementById('statsBtn').addEventListener('click', () => {
        const statsPanel = document.getElementById('statsPanel');
        statsPanel.classList.toggle('hidden');
    });
    
    // Modal close
    document.querySelector('.close').addEventListener('click', () => {
        document.getElementById('imageModal').style.display = 'none';
    });
    
    window.addEventListener('click', (e) => {
        if (e.target === document.getElementById('imageModal')) {
            document.getElementById('imageModal').style.display = 'none';
        }
    });
});
