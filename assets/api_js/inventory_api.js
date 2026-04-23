function loadInventory() {
    fetch('get_product_api.php')
        .then(response => response.json())
        .then(result => {
            const tbody = document.getElementById('inventory-data');
            tbody.innerHTML = ''; 

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(product => {
                    // Calculations
                    const max = parseInt(product.max_quantity) || 100;
                    const current = parseInt(product.quantity) || 0;
                    const percent = Math.min((current / max) * 100, 100);
                    const healthColor = percent <= 15 ? '#e74c3c' : '#2ecc71';
                    
                    const row = `
                        <tr>
                            <td>#${product.id}</td>
                            <td><span class="category-tag">${product.category}</span></td>
                            <td><strong>${product.product_name}</strong></td>
                            <td><span class="variation-text">${product.variation || 'Standard'}</span></td>
                            <td><span class="desc-text">${product.description || '<i>No description</i>'}</span></td>
                            <td>₱${parseFloat(product.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            <td>${current} / ${max}</td>
                            <td>
                                <div class="progress-wrapper">
                                    <div class="progress-bar-bg">
                                        <div class="progress-fill" style="width:${percent}%; background:${healthColor};"></div>
                                    </div>
                                    <small>${Math.round(percent)}%</small>
                                </div>
                            </td>
                            <td>
                                <a href="../add_products/edit_product.php?id=${product.id}" style="color:#f28c28;"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a href="javascript:void(0)" onclick="confirmDelete(${product.id})" style="color:#e74c3c; margin-left: 10px;">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>`;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="9">No products found.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('inventory-data').innerHTML = '<tr><td colspan="9">Error loading data.</td></tr>';
        });
}

// 2. Real-time Search Function
document.getElementById('inventorySearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#inventory-data tr');

    rows.forEach(row => {
        // Search across the entire text content of the row
        const rowText = row.textContent.toLowerCase();
        row.style.display = rowText.includes(searchTerm) ? '' : 'none';
    });
});

// Load everything when the page opens
document.addEventListener('DOMContentLoaded', loadInventory);