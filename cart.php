<?php
session_start();
// เราไม่จำเป็นต้องเชื่อมต่อฐานข้อมูลในส่วน PHP แล้ว เพราะทุกอย่างจัดการโดย JavaScript
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - ข้าวพันธุ์พื้นเมืองเลย</title>
    <meta name="description" content="ตะกร้าสินค้าของคุณ - ข้าวพันธุ์พื้นเมืองจากจังหวัดเลย">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
            color: white;
        }

        .nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        .cart-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .breadcrumb-list {
            display: flex;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .breadcrumb-item a {
            color: #27ae60;
            text-decoration: none;
        }

        /* Page Header */
        .page-header {
            background: #ffffff;
            color: #333;
            padding: 2rem 0;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d5016;
        }

        /* Main Content */
        .main-content {
            padding: 2.5rem 0;
        }

        .cart-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: flex-start;
        }

        /* Cart Items */
        .cart-items {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .cart-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d5016;
        }

        .clear-cart-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 80px 1fr auto auto;
            gap: 1.5rem;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-info .item-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .item-info .item-price {
            color: #666;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .qty-btn {
            background: none;
            border: none;
            padding: 0.5rem 0.8rem;
            cursor: pointer;
        }

        .qty-input {
            width: 40px;
            text-align: center;
            border: none;
            border-left: 1px solid #ddd;
            border-right: 1px solid #ddd;
        }

        .remove-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: #e74c3c;
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-cart p {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .continue-shopping-btn {
            background: #27ae60;
            color: white;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 6px;
        }

        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 110px;
        }

        .summary-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d5016;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 1.2rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .checkout-btn {
            width: 100%;
            background: #27ae60;
            color: white;
            border: none;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 1rem;
        }

        .checkout-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        @media (max-width: 992px) {
            .cart-container {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
                margin-top: 2rem;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="container header-container">
            <a href="index.php" class="logo">
                <span>🌾</span>
                <span>ข้าวพันธุ์พื้นเมืองเลย</span>
            </a>
            <nav class="nav">
                <a href="index.php" class="nav-link">หน้าแรก</a>
                <a href="products.php" class="nav-link">สินค้า</a>
                <a href="about.php" class="nav-link">เกี่ยวกับเรา</a>
                <a href="contact.php" class="nav-link">ติดต่อ</a>
            </nav>
            <button class="cart-btn">
                🛒 ตะกร้า <span id="cartCount">(0)</span>
            </button>
        </div>
    </header>

    <div class="breadcrumb">
        <div class="container">
            <div class="breadcrumb-list">
                <span class="breadcrumb-item"><a href="index.php">หน้าแรก</a></span>
                <span class="breadcrumb-item">&rsaquo;</span>
                <span class="breadcrumb-item">ตะกร้าสินค้า</span>
            </div>
        </div>
    </div>

    <div class="container page-header">
        <h1 class="page-title">ตะกร้าสินค้าของคุณ</h1>
    </div>

    <main class="main-content">
        <div class="container">
            <div class="cart-container">
                <div class="cart-items">
                    <div class="cart-header">
                        <h2 class="cart-title">รายการในตะกร้า</h2>
                        <button id="clearCartBtn" onclick="clearCart()" class="clear-cart-btn">ล้างตะกร้า</button>
                    </div>
                    <div id="cartItemsList">
                    </div>
                    <div id="emptyCart" class="empty-cart" style="display: none;">
                        <p>ยังไม่มีสินค้าในตะกร้าของคุณ</p>
                        <a href="products.php" class="continue-shopping-btn">เลือกซื้อสินค้าต่อ</a>
                    </div>
                </div>

                <div class="cart-summary">
                    <h3 class="summary-title">สรุปยอด</h3>
                    <div class="summary-row">
                        <span>ราคารวม (<span id="totalItems">0</span> ชิ้น)</span>
                        <span id="subtotal">0 บาท</span>
                    </div>
                    <div class="summary-row">
                        <span>ค่าจัดส่ง</span>
                        <span id="shippingCost">0 บาท</span>
                    </div>
                    <div class="summary-row total">
                        <span>ยอดรวมทั้งสิ้น</span>
                        <span id="grandTotal">0 บาท</span>
                    </div>
                    <button id="checkoutBtn" class="checkout-btn" onclick="proceedToCheckout()" disabled>ดำเนินการสั่งซื้อ</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // --- 1. DECLARE GLOBAL VARIABLES ---
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        let productDetails = [];
        let shippingCost = 0; // Set a default, can be changed later
        let discountAmount = 0;

        // --- 2. HELPER FUNCTIONS ---
        function formatPrice(price) {
            return (parseFloat(price) || 0).toLocaleString('th-TH') + ' บาท';
        }

        function updateCartIcon() {
            const cartCountEl = document.getElementById('cartCount');
            if (cartCountEl) {
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                cartCountEl.textContent = `(${totalItems})`;
            }
        }

        // --- 3. CORE LOGIC ---
        async function loadCartDetails() {
            updateCartIcon();
            const cartItemsList = document.getElementById('cartItemsList');
            const emptyCartEl = document.getElementById('emptyCart');
            const clearCartBtn = document.getElementById('clearCartBtn');

            if (!cart || cart.length === 0) {
                cartItemsList.innerHTML = '';
                emptyCartEl.style.display = 'block';
                clearCartBtn.style.display = 'none';
                productDetails = [];
                updateSummary();
                return;
            }

            emptyCartEl.style.display = 'none';
            clearCartBtn.style.display = 'block';
            const productIds = cart.map(item => item.id);

            try {
                const response = await fetch(`get_product_details.php?ids=${productIds.join(',')}`);
                if (!response.ok) throw new Error(`Server responded with status: ${response.status}`);

                const productsFromServer = await response.json();
                if (productsFromServer.error) throw new Error(productsFromServer.error);

                const productDetailsMap = new Map(productsFromServer.map(p => [parseInt(p.id), p]));

                productDetails = cart.map(cartItem => {
                    const details = productDetailsMap.get(parseInt(cartItem.id));
                    return details ? {
                        ...cartItem,
                        ...details,
                        price: details.sale_price || details.price
                    } : null;
                }).filter(item => item !== null);

                renderCartItems();
                updateSummary();

            } catch (error) {
                console.error('Failed to load cart details:', error);
                cartItemsList.innerHTML = `<p style="color:red;text-align:center;padding:2rem;">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>`;
            }
        }

        function renderCartItems() {
            const cartItemsList = document.getElementById('cartItemsList');
            const placeholderSvg = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2280%22%20height%3D%2280%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Crect%20width%3D%2280%22%20height%3D%2280%22%20fill%3D%22%23eee%22%3E%3C/rect%3E%3C/svg%3E';

            cartItemsList.innerHTML = productDetails.map(item => {
                const imageUrl = item.image_main ? `uploads/products/${item.image_main}` : placeholderSvg;
                return `
                <div class="cart-item" data-id="${item.id}">
                    <img src="${imageUrl}" alt="${item.name}" class="item-image">
                    <div class="item-info">
                        <div class="item-name">${item.name || 'สินค้าไม่มีชื่อ'}</div>
                        <div class="item-price">${formatPrice(item.price)}</div>
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                        <input type="number" class="qty-input" value="${item.quantity}" min="1" onchange="updateQuantity(${item.id}, this.value)">
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                    </div>
                    <button class="remove-btn" onclick="removeFromCart(${item.id})">🗑️</button>
                </div>
            `;
            }).join('');
        }

        function updateSummary() {
            const subtotal = productDetails.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const totalItems = productDetails.reduce((sum, item) => sum + item.quantity, 0);
            const grandTotal = subtotal + shippingCost - discountAmount;

            document.getElementById('totalItems').textContent = totalItems;
            document.getElementById('subtotal').textContent = formatPrice(subtotal);
            document.getElementById('shippingCost').textContent = formatPrice(shippingCost);
            document.getElementById('grandTotal').textContent = formatPrice(grandTotal);

            const checkoutBtn = document.getElementById('checkoutBtn');
            const hasItems = productDetails.length > 0;

            checkoutBtn.disabled = !hasItems;
            checkoutBtn.style.cursor = hasItems ? 'pointer' : 'not-allowed';
            checkoutBtn.style.backgroundColor = hasItems ? '#27ae60' : '#ccc';
        }

        // --- 4. EVENT HANDLER FUNCTIONS ---
        function updateQuantity(id, newQuantity) {
            newQuantity = parseInt(newQuantity);
            const itemIndex = cart.findIndex(item => item.id == id);
            if (itemIndex > -1) {
                newQuantity > 0 ? cart[itemIndex].quantity = newQuantity : cart.splice(itemIndex, 1);
            }
            localStorage.setItem('cart', JSON.stringify(cart));
            loadCartDetails();
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id != id);
            localStorage.setItem('cart', JSON.stringify(cart));
            loadCartDetails();
        }

        function clearCart() {
            if (confirm('คุณต้องการล้างตะกร้าสินค้าใช่หรือไม่?')) {
                cart = [];
                productDetails = [];
                shippingCost = 0;
                discountAmount = 0;
                localStorage.removeItem('cart');
                loadCartDetails();
            }
        }

        function proceedToCheckout() {
            if (productDetails.length === 0) {
                alert('ตะกร้าสินค้าของคุณว่างเปล่า');
                return;
            }
            const orderData = {
                items: productDetails,
                subtotal: productDetails.reduce((sum, item) => sum + (item.price * item.quantity), 0),
                shippingCost: shippingCost,
                discountAmount: discountAmount,
                grandTotal: (productDetails.reduce((sum, item) => sum + (item.price * item.quantity), 0)) + shippingCost - discountAmount
            };
            localStorage.setItem('orderForCheckout', JSON.stringify(orderData));
            window.location.href = 'checkout.php';
        }

        // --- 5. INITIALIZE THE SCRIPT ---
        document.addEventListener('DOMContentLoaded', loadCartDetails);
    </script>
</body>

</html>