<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User info</title>
    <style>
    body {
        background-color: #f0f2f5; /* Light grey background to make modal pop */
        margin: 10px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .modal {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        position: fixed;
        inset: 0; /* Shorthand for top, left, right, bottom: 0 */
        background-color: rgba(0, 0, 0, 0.4); /* Darken background */
        padding: 20px;
        padding: 30px 10px; 
        overflow-y: auto;
    }

    .modal-content {
        background-color: #fff;
        padding: 30px; /* Reduced from 100px for better balance */
        border-radius: 12px;
        width: 100%;
        max-width: 450px; /* Slimmer profile looks more modern */
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        border-top: 5px solid #e67e22; 
        animation: modalPop 0.3s ease-out;
        height: auto; 
        margin-bottom: 40px; /* Extra buffer for the bottom */
        
    }

    .modal-content h2 {
        margin-top: 0;
        margin-bottom: 1.5rem;
        font-size: 1.6rem;
        text-align: center;
        color: #e67e22;
    }

    .modal-content label {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        color: #666;
        margin-bottom: 6px;
        text-transform: uppercase; /* Adds a clean, UI feel */
    }

    .modal-content input {
        width: 100%;
        padding: 10px 14px;
        margin-bottom: 1.2rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        box-sizing: border-box;
        transition: all 0.2s;
    }

    .modal-content input:focus {
        border-color: #e67e22;
        outline: none;
        box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.1);
    }

    .modal-content input[readonly] {
        background-color: #f9f9f9;
        color: #888;
        cursor: not-allowed;
        border: 1px dashed #ccc;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .modal-content button {
        flex: 1; /* Makes buttons equal width */
        padding: 12px;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
        transition: transform 0.1s, background-color 0.2s;
    }

    button[type="submit"] {
        background-color: #e67e22;
        color: white;
    }

    button[type="button"] {
        background-color: #f1f1f1;
        color: #555;
    }

    .modal-content button:hover {
        filter: brightness(0.9);
        transform: translateY(-1px);
    }

    .modal-content button:active {
        transform: translateY(0);
    }

    @keyframes modalPop {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>
<body>
     <div  class="modal">
            <div class="modal-content">
                <h2 style="color: #e67e22;">Update Products</h2>

                <form action="/edit" method="GET">
                            <label>Id:</label>
                            <input type="number" name="id" value="<%= myData.id %>" required>

                            <label>Product Name:</label>
                            <input type="text" name="product_name" value="<%= myData.product_name %>" required>

                            <label>Category:</label>
                            <input type="text" name="category" value="<%= myData.category %>" required>

                            <label>Price(%):</label>
                            <input type="number" name="price" value="<%= myData.price %>" required>

                            <label>Quantity:</label>
                            <input type="number" name="quantity" value="<%= myData.quantity %>" required>

                            <label>Max Quantity:</label>
                            <input type="number" name="max_quantity" value="<%= myData.max_quantity %>" required>

                            <button type="submit">Update</button>
                            <button type="button" onclick="window.location.href='/inventory'">Cancel</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>

</body>
</html>