
function loadInventory() {
    const grid = document.getElementById('inventory-grid');
    

    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">Loading catalog...</div>';

    fetch('get_product_api.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(result => {
            console.log("Inventory Data Received:", result); // Debugging log
            grid.innerHTML = ''; 

            if (result.status === 'success' && result.data && result.data.length > 0) {
                let allCardsHTML = '';

            result.data.forEach(product => {
                const max = parseInt(product.max_quantity) || 100;
                const current = parseInt(product.quantity) || 0;
                const percent = Math.min((current / max) * 100, 100);
                const healthColor = percent <= 15 ? '#e74c3c' : '#2ecc71';
                const imgBase = '../uploads/';
                const fileName = (product.image_path && product.image_path !== 'default') ? product.image_path : 'default-product.png';
                const imageSrc = imgBase + fileName;
                const displayPrice = product.price ? parseFloat(product.price).toFixed(2) : '0.00';

                allCardsHTML += `
                    <div class="product-card" style="position: relative;">
                        <!-- Category Tag floating at the top -->
                        <div class="card-category" style="position: absolute; top: 15px; left: 15px; z-index: 2; margin-bottom: 0;">
                            ${product.category || 'General'}
                        </div>

                        <div class="card-actions">
                            <a href="../add_products/edit_product.php?id=${product.id}" class="action-btn">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <div class="action-btn" onclick="confirmDelete(${product.id})" style="color: #e74c3c; cursor: pointer;">
                                <i class="fa-solid fa-trash"></i>
                            </div>
                        </div>
                        
                        <div class="card-image-wrapper">
                            <img src="${imageSrc}?t=${new Date().getTime()}" 
                                alt="${product.product_name}" 
                                onerror="this.onerror=null; this.src='${imgBase}default-product.png';">
                        </div>
                        
                        <div class="card-info">
                            <div class="card-title" style="margin-top: 0;">${product.product_name || 'Unnamed Product'}</div>
                            <div class="card-variation" style="margin-bottom: 8px;">${product.variation || 'Standard'}</div>

                            <!-- Description now has more room at the bottom -->
                            <div class="card-description" style="font-size: 0.8rem; color: #5f6769; line-height: 1.4; margin-bottom: 15px; font-style: normal;">
                                ${product.description}
                            </div>
                            
                            <div class="progress-bar-bg" style="width: 100%; height: 6px; background: #eee; border-radius: 10px; overflow: hidden; margin-bottom: 5px;">
                                <div class="progress-fill" style="width:${percent}%; background:${healthColor}; height: 100%;"></div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <small style="color: #7f8c8d;">Stock Level</small>
                                <small style="font-weight: bold; color: ${healthColor};">${Math.round(percent)}%</small>
                            </div>
                            
                            <div class="card-footer">
                                <span class="price-tag">₱${displayPrice}</span>
                                <span class="qty-tag">${current} pcs</span>
                            </div>
                        </div>
                    </div>`;
                });

                grid.innerHTML = allCardsHTML;
            } else {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">No products found.</div>';
            }
        })
        .catch(error => {
            console.error('Inventory Load Error:', error);
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #e74c3c;">Failed to load inventory. Check console for details.</div>';
        });
}


function confirmDelete(id) {
    if (!confirm("Are you sure you want to delete this product? All logs for this item will be lost.")) {
        return;
    }

    fetch(`../add_products/delete_product.php?id=${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(result => {
            if (result.status === 'success') {
                alert("Product removed successfully!");
                loadInventory(); 
            } else {
                alert("Database Error: " + (result.message || "Unknown error"));
            }
        })
        .catch(error => {
            console.error('Delete Error:', error);
            alert("Could not connect to the server. Please check your connection.");
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