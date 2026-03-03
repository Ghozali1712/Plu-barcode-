const express = require('express');
const path = require('path');
const app = express();
const PORT = 3000;

// Serve static files
app.use(express.static(path.join(__dirname)));

// API endpoint untuk mendapatkan produk (optional)
app.get('/api/products', (req, res) => {
    const products = require('./barcodesheet (1).json');
    res.json(products);
});

// API endpoint untuk pencarian
app.get('/api/search', (req, res) => {
    const { q } = req.query;
    const products = require('./barcodesheet (1).json');
    
    if (!q) {
        return res.json([]);
    }
    
    const queries = q.split(/[,\n]/).map(s => s.trim().toLowerCase());
    const results = [];
    
    for (const query of queries) {
        // Cari PLU
        let product = products.find(p => p.plu === query);
        
        // Cari nama
        if (!product) {
            product = products.find(p => 
                p.nama && p.nama.toLowerCase().includes(query)
            );
        }
        
        if (product && !results.some(r => r.plu === product.plu)) {
            results.push(product);
        }
    }
    
    res.json(results);
});

app.listen(PORT, () => {
    console.log(`🚀 Server running at http://localhost:${PORT}`);
});
