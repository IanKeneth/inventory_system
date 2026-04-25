function loadInventory() {
    const grid = document.getElementById('inventory-grid');
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">Loading catalog...</div>';

    fetch('get_product_api.php')
        .then(response => response.json())
        .then(result => {
            grid.innerHTML = ''; 

            if (result.status === 'success' && result.data && result.data.length > 0) {
                let allCardsHTML = '';

                result.data.forEach(product => {
                    const max = parseInt(product.max_quantity) || 100;
                    const current = parseInt(product.quantity) || 0;
                    const percent = Math.min((current / max) * 100, 100);
                    const healthColor = percent <= 15 ? '#e74c3c' : '#2ecc71';
                    
                    // FIXED PATHS: Points to ../uploads/
                    const imgBase = '../uploads/';
                    const fileName = (product.image_path && product.image_path !== 'default') ? product.image_path : 'default-product.png';
                    const imageSrc = imgBase + fileName;

                    allCardsHTML += `
                        <div class="product-card">
                            <div class="card-actions">
                                <a href="../add_products/edit_product.php?id=${product.id}" class="action-btn">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <div class="action-btn" onclick="confirmDelete(${product.id})" style="color: #e74c3c; cursor: pointer;">
                                    <i class="fa-solid fa-trash"></i>
                                </div>
                            </div>
                            
                            <div class="card-image-wrapper" style="height: 200px; display: flex; align-items: center; justify-content: center; background: #fff; padding: 10px;">
                                <img src="${imageSrc}?t=${new Date().getTime()}" 
                                     alt="${product.product_name}" 
                                     style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                     onerror="this.onerror=null; this.src='${imgBase}default-product.png';">
                            </div>
                            
                            <div class="card-info" style="padding: 15px;">
                                <div class="card-category" style="font-size: 0.7rem; color: #f28c28; background: #fff3e0; padding: 2px 8px; border-radius: 4px; display: inline-block;">
                                    ${product.category || 'General'}
                                </div>
                                <div class="card-title" style="font-weight: 700; font-size: 1.1rem; margin: 10px 0 5px 0; color: #2c3e50;">
                                    ${product.product_name || 'Unnamed Product'}
                                </div>
                                <div class="card-variation" style="font-size: 0.85rem; color: #7f8c8d; font-style: italic; margin-bottom: 15px;">
                                    ${product.variation || 'Standard'}
                                </div>
                                
                                <div class="progress-bar-bg" style="width: 100%; height: 6px; background: #eee; border-radius: 10px; overflow: hidden; margin-bottom: 5px;">
                                    <div class="progress-fill" style="width:${percent}%; background:${healthColor}; height: 100%;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <small style="color: #7f8c8d;">Stock Level</small>
                                    <small style="font-weight: bold; color: ${healthColor};">${Math.round(percent)}%</small>
                                </div>
                                
                                <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f1f1; padding-top: 10px;">
                                    <span class="price-tag" style="font-weight: 800; font-size: 1.2rem; color: #2c3e50;">₱${parseFloat(product.price).toFixed(2)}</span>
                                    <span class="qty-tag" style="background: #e8f5e9; color: #2e7d32; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">${current} pcs</span>
                                </div>
                            </div>
                        </div>`;
                });

                grid.innerHTML = allCardsHTML;
            } else {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">No products found.</div>';
            }
        })
}

function confirmDelete(id) {
    if (!confirm("Are you sure you want to delete this product? All logs for this item will be lost.")) {
        return;
    }

    // Using a standard GET request to avoid CORS or Method-Not-Allowed issues
    fetch(`../add_products/delete_product.php?id=${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(result => {
            if (result.status === 'success') {
                alert("Product removed successfully!");
                loadInventory(); // Refresh the grid
            } else {
                // This shows the specific error from PHP (e.g., Snapshot error)
                alert("Database Error: " + result.message);
            }
        })
        .catch(error => {
            console.error('Delete Error:', error);
            alert("Could not connect to the server. Please check your connection.");
        });
}
// Search Logic
document.getElementById('inventorySearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');

    cards.forEach(card => {
        const cardText = card.textContent.toLowerCase();
        card.style.display = cardText.includes(searchTerm) ? '' : 'none';
    });
});