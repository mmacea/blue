<?php
// Suprimir errores en producción
error_reporting(0);
ini_set('display_errors', 0);

require_once 'backend/config/database.php';

// Verificar autenticación
$user = validateSession();
if (!$user) {
    header('Location: login.php');
    exit;
}

// Obtener items del carrito
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT c.*, p.nombre, p.precio, p.descripcion, p.stock 
              FROM carrito c 
              JOIN producto p ON c.idProducto = p.idProducto 
              WHERE c.idUsuario = :user_id 
              ORDER BY c.fechaAgregado DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    $cartItems = $stmt->fetchAll();
    
    // Calcular total
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    
} catch (Exception $e) {
    error_log("Cart error: " . $e->getMessage());
    $cartItems = [];
    $total = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - Blue</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/header/betterfaviconblue1.png"/>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        /* Header Styles */
        .HeaderSite {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 40px;
            height: 106px;
            background-color: #ffffff;
            border-bottom: 1px solid #eee;
            box-sizing: border-box;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        .Logo img {
            height: 80px;
            width: 260px;
            object-fit: contain;
        }

        .HeaderIcons {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .CircularButtons {
            width: 54px;
            height: 54px;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.2s ease;
        }

        .CircularButtons:hover {
            background-color: #e0e0e0;
        }

        .CircularButtons .Link {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .CircularButtons img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        /* Menu toggle button */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            padding: 10px;
            cursor: pointer;
            z-index: 1010;
        }

        .menu-toggle .bar {
            display: block;
            width: 24px;
            height: 2px;
            background-color: #333;
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        /* Mobile menu overlay */
        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-menu-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .HeaderSite {
                height: 70px;
                padding: 0 15px;
            }

            .Logo img {
                height: 45px;
                width: auto;
            }

            .menu-toggle {
                display: block;
                order: 1;
            }

            .Nav {
                position: fixed;
                top: 0;
                right: -280px;
                left: auto;
                width: 280px;
                height: 100%;
                background-color: #ffffff;
                z-index: 1000;
                transition: right 0.3s ease;
                box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
                overflow-y: auto;
                padding-top: 0;
            }

            .Nav.active {
                right: 0;
            }

            .HeaderIcons {
                display: flex;
                flex-direction: column;
                gap: 0;
                padding: 0;
                margin-top: 0;
            }

            .CircularButtons {
                width: 100%;
                height: auto;
                background-color: transparent;
                border-radius: 0;
                border-bottom: 1px solid #eee;
            }

            .CircularButtons:last-child {
                border-bottom: none;
            }

            .CircularButtons .Link {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                gap: 15px;
                padding: 15px 20px;
                border-radius: 0;
                width: 100%;
                background-color: transparent;
                transition: background-color 0.3s ease;
                text-decoration: none;
                box-sizing: border-box;
            }

            .CircularButtons .Link:hover {
                background-color: #f0f0f0;
            }

            .CircularButtons .Link:active {
                background-color: #e0e0e0;
            }

            .CircularButtons img {
                width: 22px;
                height: 22px;
                object-fit: contain;
            }

            .CircularButtons .Link::after {
                content: attr(title);
                font-size: 16px;
                font-weight: 500;
                color: #333;
                text-align: left;
                flex-grow: 1;
            }

            .menu-toggle.active .bar:nth-child(1) {
                transform: translateY(7px) rotate(45deg);
            }

            .menu-toggle.active .bar:nth-child(2) {
                opacity: 0;
            }

            .menu-toggle.active .bar:nth-child(3) {
                transform: translateY(-7px) rotate(-45deg);
            }
        }

        /* Main Content */
        .main-content {
            margin-top: 106px;
            padding: 40px 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: #718096;
        }

        /* Cart Layout */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            align-items: start;
        }

        .cart-items {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .cart-summary {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 130px;
        }

        /* Cart Items */
        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.3s ease;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item:hover {
            background-color: #f7fafc;
            border-radius: 10px;
            margin: 0 -10px;
            padding: 20px 10px;
        }

        .item-image {
            width: 100px;
            height: 100px;
            background-color: #f7fafc;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .item-image img {
            max-width: 80px;
            max-height: 80px;
            object-fit: contain;
        }

        .item-details {
            flex: 1;
            margin-right: 20px;
        }

        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .item-description {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 10px;
        }

        .item-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: #667eea;
        }

        .item-controls {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 50px; /* Separación de 20px entre botones */
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            background-color: #f7fafc;
            border-radius: 8px;
            padding: 5px;
        }

        .quantity-btn {
            width: 35px;
            height: 35px;
            border: none;
            background-color: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background-color 0.2s ease;
            font-size: 1.2rem;
            font-weight: bold;
            color: #4a5568;
        }

        .quantity-btn:hover {
            background-color: #e2e8f0;
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: none;
            background: transparent;
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
            padding: 5px;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #e53e3e;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-btn:hover {
            background-color: #fed7d7;
            transform: scale(1.1);
        }

        /* Cart Summary */
        .summary-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            padding-top: 20px;
            margin-top: 10px;
            border-top: 2px solid #e2e8f0;
        }

        .summary-label {
            color: #4a5568;
        }

        .summary-value {
            font-weight: 600;
            color: #2d3748;
        }

        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: "Poppins", sans-serif;
            margin-top: 20px;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .checkout-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .continue-shopping {
            width: 100%;
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: "Poppins", sans-serif;
            margin-top: 20px;
        }

        .continue-shopping:hover {
            background: #cbd5e0;
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .empty-cart svg {
            width: 120px;
            height: 120px;
            color: #a0aec0;
            margin-bottom: 30px;
        }

        .empty-cart h2 {
            font-size: 1.8rem;
            color: #4a5568;
            margin-bottom: 15px;
        }

        .empty-cart p {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 30px;
        }

        .shop-now-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: "Poppins", sans-serif;
            text-decoration: none;
            display: inline-block;
        }

        .shop-now-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .HeaderSite {
                padding: 0 20px;
                height: 70px;
            }

            .Logo img {
                height: 45px;
                width: auto;
            }

            .main-content {
                margin-top: 70px;
                padding: 20px 15px;
            }

            .page-title {
                font-size: 2rem;
            }

            .cart-layout {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .cart-summary {
                position: static;
                order: -1;
            }

            .cart-items,
            .cart-summary {
                padding: 20px;
            }

            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 20px 0;
            }

            .cart-item:hover {
                margin: 0;
                padding: 20px 0;
            }

            .item-image {
                margin-right: 0;
                align-self: center;
            }

            .item-details {
                margin-right: 0;
                text-align: center;
                width: 100%;
            }

            .item-controls {
                flex-direction: row;
                justify-content: space-between;
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .cart-items,
            .cart-summary {
                padding: 15px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .item-image {
                width: 80px;
                height: 80px;
            }

            .item-image img {
                max-width: 60px;
                max-height: 60px;
            }
        }
    </style>
</head>
<body>
    <header class="HeaderSite">
        <div class="Logo">
            <a href="index.php" title="Blue">
                <img src="img/header/betterblueLogoNoBackground.png" alt="Logo de Blue"/>
            </a>
        </div>
        <button class="menu-toggle" aria-label="Abrir menú" aria-expanded="false">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        <nav class="Nav">
            <div class="HeaderIcons">
                <div class="CircularButtons">
                    <a href="#" title="Buscar" class="Link search-trigger">
                        <img src="img/header/lupaBuscar.png" alt="Botón de Búsqueda"/>
                    </a>
                </div>
                <div class="CircularButtons">
                    <a href="perfil.php#favorites" title="Destacados" class="Link">
                        <img src="img/header/corazonGuardado.png" alt="Botón de Destacados"/>
                    </a>
                </div>
                <div class="CircularButtons">
                    <a href="carrito.php" title="Carrito de Compra" class="Link">
                        <img src="img/header/carritoDeCompra.png" alt="Botón de Carrito de Compra"/>
                    </a>
                </div>
                <div class="CircularButtons">
                    <a href="perfil.php#notifications" title="Notificaciones" class="Link">
                        <img src="img/header/campanaNotificacion.png" alt="Botón notificaciones"/>
                    </a>
                </div>
                <div class="CircularButtons">
                    <a href="perfil.php" title="<?= htmlspecialchars($user['name']) ?>" class="Link">
                        <img src="img/header/usuarioPerfil.png" alt="Perfil de <?= htmlspecialchars($user['name']) ?>"/>
                    </a>
                </div>
            </div>
        </nav>
        <div class="mobile-menu-overlay"></div>
    </header>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Mi Carrito de Compras</h1>
            <p class="page-subtitle">Revisa y confirma tus productos antes de proceder al pago</p>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="m1 1 4 4 2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <h2>Tu carrito está vacío</h2>
                <p>Explora nuestro catálogo y agrega productos a tu carrito</p>
                <a href="index.php" class="shop-now-btn">Comenzar a comprar</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" data-item-id="<?= $item['idCarrito'] ?>" data-product-id="<?= $item['idProducto'] ?>">
                            <div class="item-image">
                                <img src="img/main/placeholder-product.png" alt="<?= htmlspecialchars($item['nombre']) ?>">
                            </div>
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['nombre']) ?></div>
                                <div class="item-description"><?= htmlspecialchars($item['descripcion']) ?></div>
                                <div class="item-price">$<?= number_format($item['precio'], 0, ',', '.') ?></div>
                            </div>
                            <div class="item-controls">
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="updateQuantity(<?= $item['idCarrito'] ?>, <?= $item['cantidad'] - 1 ?>)" <?= $item['cantidad'] <= 1 ? 'disabled' : '' ?>>-</button>
                                    <input type="number" class="quantity-input" value="<?= $item['cantidad'] ?>" min="1" max="<?= $item['stock'] ?>" onchange="updateQuantity(<?= $item['idCarrito'] ?>, this.value)">
                                    <button class="quantity-btn" onclick="updateQuantity(<?= $item['idCarrito'] ?>, <?= $item['cantidad'] + 1 ?>)" <?= $item['cantidad'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                                </div>
                                <button class="remove-btn" onclick="removeItem(<?= $item['idCarrito'] ?>)" title="Eliminar producto">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3,6 5,6 21,6"></polyline>
                                        <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h3 class="summary-title">Resumen del Pedido</h3>
                    
                    <div class="summary-row">
                        <span class="summary-label">Productos (<?= count($cartItems) ?>)</span>
                        <span class="summary-value" id="subtotal">$<?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Envío</span>
                        <span class="summary-value">$2.500</span>
                    </div>

                    <div class="summary-row">
                        <span class="summary-label">Total</span>
                        <span class="summary-value" id="total">$<?= number_format($total + 2500, 0, ',', '.') ?></span>
                    </div>

                    <button class="checkout-btn" onclick="proceedToCheckout()" <?= empty($cartItems) ? 'disabled' : '' ?>>
                        Proceder al Pago
                        <span class="spinner" id="checkoutSpinner" style="display: none;"></span>
                    </button>
                    
                    <a href="index.php" class="continue-shopping">Continuar comprando</a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="js/global-search.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Initialize cart functionality
            updateCartTotals()
        })

        async function updateQuantity(cartId, newQuantity) {
            if (newQuantity < 1) {
                removeItem(cartId)
                return
            }

            const cartItem = document.querySelector(`[data-item-id="${cartId}"]`)
            const productId = cartItem.dataset.productId
            
            try {
                cartItem.classList.add('loading')
                
                const response = await fetch("backend/php/update-cart-item.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        productId: parseInt(productId),
                        quantity: parseInt(newQuantity)
                    }),
                })

                const result = await response.json()

                if (result.success) {
                    // Update quantity input
                    const quantityInput = cartItem.querySelector('.quantity-input')
                    quantityInput.value = newQuantity
                    
                    // Update quantity buttons state
                    updateQuantityButtons(cartItem, newQuantity)
                    
                    // Update totals
                    updateCartTotals()
                    
                    showNotification("Cantidad actualizada", "success")
                } else {
                    throw new Error(result.message)
                }
            } catch (error) {
                console.error("Error updating quantity:", error)
                showNotification(error.message || "Error al actualizar cantidad", "error")
                
                // Revert quantity input
                location.reload()
            } finally {
                cartItem.classList.remove('loading')
            }
        }

        async function removeItem(cartId) {
            if (!confirm("¿Estás seguro de que quieres eliminar este producto del carrito?")) {
                return
            }

            const cartItem = document.querySelector(`[data-item-id="${cartId}"]`)
            const productId = cartItem.dataset.productId
            
            try {
                cartItem.classList.add('loading')
                
                const response = await fetch("backend/php/remove-cart-item.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        productId: parseInt(productId)
                    }),
                })

                const result = await response.json()

                if (result.success) {
                    // Remove item from DOM
                    cartItem.remove()
                    
                    // Update totals
                    updateCartTotals()
                    
                    // Check if cart is empty
                    const remainingItems = document.querySelectorAll('.cart-item')
                    if (remainingItems.length === 0) {
                        location.reload() // Reload to show empty cart state
                    }
                    
                    showNotification("Producto eliminado del carrito", "success")
                } else {
                    throw new Error(result.message)
                }
            } catch (error) {
                console.error("Error removing item:", error)
                showNotification(error.message || "Error al eliminar producto", "error")
            } finally {
                cartItem.classList.remove('loading')
            }
        }

        function updateQuantityButtons(cartItem, quantity) {
            const decreaseBtn = cartItem.querySelector('.quantity-btn:first-child')
            const increaseBtn = cartItem.querySelector('.quantity-btn:last-child')
            const quantityInput = cartItem.querySelector('.quantity-input')
            
            // Update decrease button
            decreaseBtn.disabled = quantity <= 1
            
            // Update increase button (check stock)
            const maxStock = parseInt(quantityInput.getAttribute('max'))
            increaseBtn.disabled = quantity >= maxStock
        }

        function updateCartTotals() {
            let subtotal = 0
            const cartItems = document.querySelectorAll('.cart-item')
            
            cartItems.forEach(item => {
                const priceText = item.querySelector('.item-price').textContent
                const price = parseInt(priceText.replace(/[^\d]/g, ''))
                const quantity = parseInt(item.querySelector('.quantity-input').value)
                subtotal += price * quantity
            })
            
            // Update subtotal
            document.getElementById('subtotal').textContent = formatPrice(subtotal)
            
            // Update total (subtotal + shipping cost)
            const shipping = 2500
            document.getElementById('total').textContent = formatPrice(subtotal + shipping)
        }

        function formatPrice(price) {
            return '$' + new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price)
        }

        async function proceedToCheckout() {
    const checkoutBtn = document.querySelector('.checkout-btn')
    const spinner = document.getElementById('checkoutSpinner')
    
    try {
        checkoutBtn.disabled = true
        spinner.style.display = 'inline-block'
        
        // Get cart items for validation
        const cartItems = document.querySelectorAll('.cart-item')
        if (cartItems.length === 0) {
            throw new Error("El carrito está vacío")
        }
        
        // Validate stock for all items
        let hasStockIssues = false
        for (const item of cartItems) {
            const quantity = parseInt(item.querySelector('.quantity-input').value)
            const maxStock = parseInt(item.querySelector('.quantity-input').getAttribute('max'))
            
            if (quantity > maxStock) {
                hasStockIssues = true
                break
            }
        }
        
        if (hasStockIssues) {
            throw new Error("Algunos productos no tienen stock suficiente")
        }
        
        // Redirect to checkout page
        window.location.href = "checkout.php"
        
    } catch (error) {
        console.error("Checkout error:", error)
        showNotification(error.message || "Error al proceder al pago", "error")
        checkoutBtn.disabled = false
        spinner.style.display = 'none'
    }
}

        // Notification system
        function showNotification(message, type = "info") {
            // Create notification container if it doesn't exist
            let container = document.getElementById("notification-container")
            if (!container) {
                container = document.createElement("div")
                container.id = "notification-container"
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    max-width: 400px;
                    width: 100%;
                    pointer-events: none;
                `
                document.body.appendChild(container)
            }

            // Create notification element
            const notification = document.createElement("div")
            notification.style.cssText = `
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                padding: 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                transform: translateX(120%);
                opacity: 0;
                transition: transform 0.3s ease, opacity 0.3s ease;
                pointer-events: auto;
                border-left: 4px solid ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
            `

            const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'
            notification.innerHTML = `
                <div style="color: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'}; font-weight: bold; font-size: 18px;">
                    ${icon}
                </div>
                <div style="flex-grow: 1; color: #333; font-size: 14px;">
                    ${message}
                </div>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #999; cursor: pointer; font-size: 18px; padding: 0; width: 20px; height: 20px;">
                    ×
                </button>
            `

            container.appendChild(notification)

            // Show notification
            setTimeout(() => {
                notification.style.transform = "translateX(0)"
                notification.style.opacity = "1"
            }, 100)

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.transform = "translateX(120%)"
                notification.style.opacity = "0"
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove()
                    }
                }, 300)
            }, 5000)
        }

        // Handle quantity input changes
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('quantity-input')) {
                const cartItem = e.target.closest('.cart-item')
                const cartId = cartItem.dataset.itemId
                const newQuantity = parseInt(e.target.value)
                
                if (newQuantity > 0) {
                    updateQuantity(cartId, newQuantity)
                } else {
                    e.target.value = 1
                }
            }
        })

        // Prevent form submission on quantity input
        document.addEventListener('keydown', (e) => {
            if (e.target.classList.contains('quantity-input') && e.key === 'Enter') {
                e.preventDefault()
                e.target.blur()
            }
        })

        // Responsive Header
        document.addEventListener("DOMContentLoaded", () => {
            const header = document.querySelector(".HeaderSite");
            const nav = header ? header.querySelector(".Nav") : null;
            const menuToggle = header ? header.querySelector(".menu-toggle") : null;
            const overlay = header ? header.querySelector(".mobile-menu-overlay") : null;

            if (!header || !nav || !menuToggle || !overlay) {
                return;
            }

            menuToggle.addEventListener("click", () => {
                const isExpanded = menuToggle.getAttribute("aria-expanded") === "true" || false;
                toggleMobileMenu(!isExpanded);
            });

            overlay.addEventListener("click", () => {
                toggleMobileMenu(false);
            });

            const navLinks = nav.querySelectorAll("a");
            navLinks.forEach((link) => {
                link.addEventListener("click", () => {
                    toggleMobileMenu(false);
                });
            });

            window.addEventListener("resize", () => {
                if (window.innerWidth > 768 && nav.classList.contains("active")) {
                    toggleMobileMenu(false);
                }
            });

            function toggleMobileMenu(show) {
                menuToggle.setAttribute("aria-expanded", show.toString());
                if (show) {
                    menuToggle.classList.add("active");
                    nav.classList.add("active");
                    overlay.classList.add("active");
                    document.body.style.overflow = "hidden";
                } else {
                    menuToggle.classList.remove("active");
                    nav.classList.remove("active");
                    overlay.classList.remove("active");
                    document.body.style.overflow = "";
                }
            }
        });
    </script>
</body>
</html>
