function loadInventory() {
    const grid = document.getElementById('inventory-grid');
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">Loading catalog...</div>';


    fetch('../admin/get_product_api.php')
        .then(response => {
            if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
            return response.json();
        })
        .then(result => {
            grid.innerHTML = ''; 

            if (result.status === 'success' && result.data && result.data.length > 0) {
                let allCardsHTML = '';

                result.data.forEach(product => {
                    const max = parseInt(product.max_quantity) || 100;
                    const current = parseInt(product.quantity) || 0;
                    const percent = Math.min((current / max) * 100, 100);
                    const healthColor = percent <= 15 ? '#e74c3c' : '#2ecc71';
                    const displayPrice = product.price ? parseFloat(product.price).toFixed(2) : '0.00';
                    const imageSrc = '../uploads/' + (product.image_path || 'default-product.png');

                    const desc = (product.description && product.description.trim() !== "") 
                                ? product.description 
                                : "No description available.";

                    allCardsHTML += `
                        <div class="product-card">
                            <div class="card-category">${product.category || 'General'}</div>

                            <div class="card-actions">
                                <a href="../user/update_stock.php?id=${product.id}" class="action-btn">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
            
                            </div>
                            
                            <div class="card-image-wrapper">
                                <img src="${imageSrc}?t=${new Date().getTime()}" alt="${product.product_name}">
                            </div>
                            
                            <div class="card-info">
                                <div class="card-title">${product.product_name || 'Unnamed Item'}</div>
                                <div class="card-variation">${product.variation || ''}</div>

                                <div class="card-description">
                                    ${desc}
                                </div>
                                
                                <div style="margin-top: auto;">
                                    <div class="progress-bar-bg" style="width: 100%; height: 6px; background: #eee; border-radius: 10px; overflow: hidden;">
                                        <div class="progress-fill" style="width:${percent}%; background:${healthColor}; height: 100%;"></div>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: space-between; margin: 8px 0 15px 0;">
                                        <small style="color: #7f8c8d;">Stock Level</small>
                                        <small style="font-weight: bold; color: ${healthColor};">${Math.round(percent)}%</small>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <span class="price-tag">₱${displayPrice}</span>
                                        <span class="qty-tag">${current} pcs</span>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });

                grid.innerHTML = allCardsHTML;
            } else {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px;">No products found.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #e74c3c;">
                Failed to load inventory. <br> 
                <small>Check if ../admin/get_product_api.php exists.</small>
            </div>`;
        });
        
}
const searchInput = document.getElementById('inventorySearch');
if (searchInput) {
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            card.style.display = cardText.includes(searchTerm) ? '' : 'none';
        });
    });
}
document.addEventListener('DOMContentLoaded', loadInventory);