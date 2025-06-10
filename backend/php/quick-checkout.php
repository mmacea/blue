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

// Obtener ID del producto
$productId = isset($_GET['product']) ? (int)$_GET['product'] : 0;

if (!$productId) {
    header('Location: index.php');
    exit;
}

// Obtener detalles del producto
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el producto existe y tiene stock
    $query = "SELECT p.*, c.nombre as categoria_nombre
              FROM producto p 
              LEFT JOIN categoria c ON p.idCategoria = c.idCategoria 
              WHERE p.idProducto = :product_id AND p.stock > 0";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Location: index.php');
        exit;
    }
    
    $product = $stmt->fetch();
    
    // Obtener direcciones del usuario
    $query_addresses = "SELECT idDireccion, direccionCompleta FROM direccionentrega WHERE idUsuario = :user_id ORDER BY idDireccion DESC";
    $stmt_addresses = $db->prepare($query_addresses);
    $stmt_addresses->bindParam(':user_id', $user['id']);
    $stmt_addresses->execute();
    $addresses = $stmt_addresses->fetchAll();
    
    // Obtener métodos de pago
    $query_payment = "SELECT idMetodoPago, tipo, detalles FROM metodopago ORDER BY idMetodoPago";
    $stmt_payment = $db->prepare($query_payment);
    $stmt_payment->execute();
    $paymentMethods = $stmt_payment->fetchAll();
    
} catch (Exception $e) {
    error_log("Quick checkout error: " . $e->getMessage());
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra Rápida - Blue</title>
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
            max-width: 1000px;
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

        /* Quick Checkout Layout */
        .quick-checkout-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            align-items: start;
        }

        .checkout-main {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Product Section */
        .product-section {
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 25px;
            background: #f8f9fa;
        }

        .product-display {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .product-image {
            width: 120px;
            height: 120px;
            background-color: #e8e8e8;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .product-image img {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .product-description {
            color: #718096;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 600;
            color: #667eea;
        }

        .product-stock {
            font-size: 0.9rem;
            color: #38a169;
            margin-top: 5px;
        }

        /* Quantity Section */
        .quantity-section {
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }

        .quantity-label {
            font-weight: 600;
            margin-bottom: 15px;
            color: #2d3748;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-btn {
            width: 45px;
            height: 45px;
            border: 2px solid #e2e8f0;
            background-color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 1.2rem;
            font-weight: bold;
            color: #4a5568;
        }

        .quantity-btn:hover {
            border-color: #667eea;
            background-color: #f7fafc;
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .quantity-input {
            width: 80px;
            text-align: center;
            border: 2px solid #e2e8f0;
            background: white;
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            padding: 12px;
            border-radius: 8px;
            transition: border-color 0.3s ease;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Section Styles */
        .checkout-section {
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 25px;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            width: 24px;
            height: 24px;
            color: #667eea;
        }

        /* Address Selection */
        .address-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 15px;
        }

        .address-option {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .address-option:hover {
            border-color: #667eea;
            background-color: #f7fafc;
        }

        .address-option.selected {
            border-color: #667eea;
            background-color: #ebf8ff;
        }

        .address-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .address-text {
            font-weight: 500;
            color: #2d3748;
        }

        .add-address-btn {
            width: 100%;
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #4a5568;
            font-weight: 500;
        }

        .add-address-btn:hover {
            border-color: #667eea;
            background-color: #ebf8ff;
            color: #667eea;
        }

        /* Payment Methods */
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .payment-option {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .payment-option:hover {
            border-color: #667eea;
            background-color: #f7fafc;
        }

        .payment-option.selected {
            border-color: #667eea;
            background-color: #ebf8ff;
        }

        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            background-color: #f7fafc;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .payment-details {
            flex: 1;
        }

        .payment-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 3px;
        }

        .payment-description {
            font-size: 0.9rem;
            color: #718096;
        }

        /* Order Summary */
        .order-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            position: sticky;
            top: 130px;
        }

        .summary-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .summary-item:last-child {
            border-bottom: none;
            font-size: 1.2rem;
            font-weight: 600;
            padding-top: 20px;
            margin-top: 10px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
        }

        .summary-label {
            opacity: 0.9;
        }

        .summary-value {
            font-weight: 600;
        }

        .checkout-btn {
            width: 100%;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: "Poppins", sans-serif;
            margin-top: 25px;
        }

        .checkout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .checkout-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .back-btn {
            width: 100%;
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: "Poppins", sans-serif;
            margin-top: 15px;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 8% auto;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #a0aec0;
            padding: 5px;
        }

        .modal-close:hover {
            color: #4a5568;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #4a5568;
        }

        .form-group input,
        .form-group textarea {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: "Poppins", sans-serif;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: "Poppins", sans-serif;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
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
        }

        .btn-secondary:hover {
            background: #cbd5e0;
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
            .main-content {
                margin-top: 70px;
                padding: 20px 15px;
            }

            .page-title {
                font-size: 2rem;
            }

            .quick-checkout-container {
                padding: 20px;
            }

            .checkout-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .order-summary {
                position: static;
                order: -1;
            }

            .product-display {
                flex-direction: column;
                text-align: center;
            }

            .product-image {
                width: 100px;
                height: 100px;
            }

            .quantity-controls {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .quick-checkout-container {
                padding: 15px;
            }

            .checkout-section,
            .product-section {
                padding: 20px;
            }

            .page-title {
                font-size: 1.8rem;
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
            <h1 class="page-title">Compra Rápida</h1>
            <p class="page-subtitle">Completa tu compra en unos pocos pasos</p>
        </div>

        <div class="quick-checkout-container">
            <div class="checkout-grid">
                <div class="checkout-main">
                    <!-- Product Section -->
                    <div class="product-section">
                        <div class="product-display">
                            <div class="product-image">
                                <img src="img/main/placeholder-product.png" alt="<?= htmlspecialchars($product['nombre']) ?>">
                            </div>
                            <div class="product-details">
                                <h2 class="product-name"><?= htmlspecialchars($product['nombre']) ?></h2>
                                <p class="product-description"><?= htmlspecialchars($product['descripcion']) ?></p>
                                <div class="product-price">$<?= number_format($product['precio'], 0, ',', '.') ?></div>
                                <div class="product-stock">Stock disponible: <?= $product['stock'] ?> unidades</div>
                            </div>
                        </div>
                        
                        <div class="quantity-section">
                            <div class="quantity-label">Cantidad:</div>
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateQuantity(-1)" id="decreaseBtn">-</button>
                                <input type="number" class="quantity-input" value="1" min="1" max="<?= $product['stock'] ?>" id="quantityInput" onchange="validateQuantity()">
                                <button class="quantity-btn" onclick="updateQuantity(1)" id="increaseBtn">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="checkout-section">
                        <h3 class="section-title">
                            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            Dirección de Entrega
                        </h3>
                        <div class="address-list" id="addressList">
                            <?php if (empty($addresses)): ?>
                                <p style="color: #718096; margin-bottom: 15px;">No tienes direcciones guardadas. Agrega una dirección para continuar.</p>
                            <?php else: ?>
                                <?php foreach ($addresses as $index => $address): ?>
                                    <div class="address-option <?= $index === 0 ? 'selected' : '' ?>" onclick="selectAddress(this, <?= $address['idDireccion'] ?>)">
                                        <input type="radio" name="address" value="<?= $address['idDireccion'] ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                        <div class="address-text"><?= htmlspecialchars($address['direccionCompleta']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <button type="button" class="add-address-btn" onclick="openAddressModal()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Agregar nueva dirección
                            </button>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="checkout-section">
                        <h3 class="section-title">
                            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Método de Pago
                        </h3>
                        <div class="payment-methods">
                            <?php foreach ($paymentMethods as $index => $method): ?>
                                <div class="payment-option <?= $index === 0 ? 'selected' : '' ?>" onclick="selectPayment(this, <?= $method['idMetodoPago'] ?>)">
                                    <input type="radio" name="payment" value="<?= $method['idMetodoPago'] ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                    <div class="payment-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                                            <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                                            <line x1="18" y1="12" x2="18" y2="12"></line>
                                        </svg>
                                    </div>
                                    <div class="payment-details">
                                        <div class="payment-name"><?= htmlspecialchars($method['tipo']) ?></div>
                                        <div class="payment-description"><?= htmlspecialchars($method['detalles']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h3 class="summary-title">Resumen de Compra</h3>
                    
                    <div class="summary-item">
                        <span class="summary-label">Producto</span>
                        <span class="summary-value" id="productTotal">$<?= number_format($product['precio'], 0, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Cantidad</span>
                        <span class="summary-value" id="quantityDisplay">1</span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value" id="subtotal">$<?= number_format($product['precio'], 0, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Envío</span>
                        <span class="summary-value">$2.500</span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Total</span>
                        <span class="summary-value" id="total">$<?= number_format($product['precio'] + 2500, 0, ',', '.') ?></span>
                    </div>

                    <button class="checkout-btn" onclick="finalizeQuickOrder()" id="finalizeBtn">
                        Confirmar Compra
                        <span class="spinner" id="finalizeSpinner" style="display: none;"></span>
                    </button>
                    
                    <button class="back-btn" onclick="goBack()">Volver</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para agregar dirección -->
    <div class="modal" id="addressModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Agregar Dirección</h3>
                <button class="modal-close" onclick="closeModal('addressModal')">&times;</button>
            </div>
            <form id="addressForm">
                <div class="form-group">
                    <label for="addressText">Dirección completa</label>
                    <textarea id="addressText" name="addressText" rows="3" placeholder="Ingresa tu dirección completa incluyendo ciudad, barrio, calle y número" required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addressModal')">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/global-search.js"></script>
    <script>
        // Variables globales
        const productPrice = <?= $product['precio'] ?>;
        const maxStock = <?= $product['stock'] ?>;
        const productId = <?= $productId ?>;
        let selectedAddressId = <?= !empty($addresses) ? $addresses[0]['idDireccion'] : 'null' ?>;
        let selectedPaymentId = <?= !empty($paymentMethods) ? $paymentMethods[0]['idMetodoPago'] : 'null' ?>;
        let currentQuantity = 1;

        document.addEventListener("DOMContentLoaded", () => {
            updateSummary();
            updateFinalizeButton();
        });

        function updateQuantity(change) {
            const quantityInput = document.getElementById('quantityInput');
            const newQuantity = Math.max(1, Math.min(maxStock, currentQuantity + change));
            
            if (newQuantity !== currentQuantity) {
                currentQuantity = newQuantity;
                quantityInput.value = currentQuantity;
                updateSummary();
                updateQuantityButtons();
            }
        }

        function validateQuantity() {
            const quantityInput = document.getElementById('quantityInput');
            let value = parseInt(quantityInput.value) || 1;
            
            value = Math.max(1, Math.min(maxStock, value));
            
            if (value !== currentQuantity) {
                currentQuantity = value;
                quantityInput.value = currentQuantity;
                updateSummary();
                updateQuantityButtons();
            }
        }

        function updateQuantityButtons() {
            const decreaseBtn = document.getElementById('decreaseBtn');
            const increaseBtn = document.getElementById('increaseBtn');
            
            decreaseBtn.disabled = currentQuantity <= 1;
            increaseBtn.disabled = currentQuantity >= maxStock;
        }

        function updateSummary() {
            const subtotal = productPrice * currentQuantity;
            const shipping = 2500;
            const total = subtotal + shipping;
            
            document.getElementById('quantityDisplay').textContent = currentQuantity;
            document.getElementById('productTotal').textContent = formatPrice(productPrice);
            document.getElementById('subtotal').textContent = formatPrice(subtotal);
            document.getElementById('total').textContent = formatPrice(total);
        }

        function formatPrice(price) {
            return '$' + new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        }

        function selectAddress(element, addressId) {
            // Remover selección anterior
            document.querySelectorAll('.address-option').forEach(option => {
                option.classList.remove('selected');
                option.querySelector('input[type="radio"]').checked = false;
            });
            
            // Seleccionar nueva dirección
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
            selectedAddressId = addressId;
            
            updateFinalizeButton();
        }

        function selectPayment(element, paymentId) {
            // Remover selección anterior
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
                option.querySelector('input[type="radio"]').checked = false;
            });
            
            // Seleccionar nuevo método
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
            selectedPaymentId = paymentId;
            
            updateFinalizeButton();
        }

        function updateFinalizeButton() {
            const finalizeBtn = document.getElementById('finalizeBtn');
            const hasAddress = selectedAddressId !== null;
            const hasPayment = selectedPaymentId !== null;
            
            finalizeBtn.disabled = !hasAddress || !hasPayment;
        }

        function openAddressModal() {
            const modal = document.getElementById("addressModal");
            const form = document.getElementById("addressForm");
            
            form.reset();
            modal.style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Manejar envío del formulario de dirección
        document.getElementById("addressForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch("backend/php/add_address.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        direccionCompleta: data.addressText
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    showNotification("Dirección agregada exitosamente", "success");
                    closeModal("addressModal");
                    
                    // Recargar la página para mostrar la nueva dirección
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showNotification(error.message || "Error al agregar la dirección", "error");
            }
        });

        async function finalizeQuickOrder() {
            if (!selectedAddressId) {
                showNotification("Por favor selecciona una dirección de entrega", "error");
                return;
            }

            if (!selectedPaymentId) {
                showNotification("Por favor selecciona un método de pago", "error");
                return;
            }

            const finalizeBtn = document.getElementById('finalizeBtn');
            const spinner = document.getElementById('finalizeSpinner');
            
            try {
                finalizeBtn.disabled = true;
                spinner.style.display = 'inline-block';
                
                const response = await fetch("backend/php/create-quick-order.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        productId: productId,
                        quantity: currentQuantity,
                        addressId: selectedAddressId,
                        paymentMethodId: selectedPaymentId
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    showNotification("¡Compra realizada exitosamente!", "success");
                    
                    // Redirigir a página de confirmación después de un breve delay
                    setTimeout(() => {
                        window.location.href = `order-confirmation.php?order=${result.data.orderId}`;
                    }, 2000);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error("Quick order creation error:", error);
                showNotification(error.message || "Error al procesar la compra", "error");
                finalizeBtn.disabled = false;
                spinner.style.display = 'none';
            }
        }

        function goBack() {
            window.history.back();
        }

        // Notification system
        function showNotification(message, type = "info") {
            // Create notification container if it doesn't exist
            let container = document.getElementById("notification-container");
            if (!container) {
                container = document.createElement("div");
                container.id = "notification-container";
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
                `;
                document.body.appendChild(container);
            }

            // Create notification element
            const notification = document.createElement("div");
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
            `;

            const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
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
            `;

            container.appendChild(notification);

            // Show notification
            setTimeout(() => {
                notification.style.transform = "translateX(0)";
                notification.style.opacity = "1";
            }, 100);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.transform = "translateX(120%)";
                notification.style.opacity = "0";
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }, 5000);
        }

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
