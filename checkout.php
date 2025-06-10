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
    
    // Si el carrito está vacío, redirigir
    if (empty($cartItems)) {
        header('Location: carrito.php');
        exit;
    }
    
    // Calcular total
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['precio'] * $item['cantidad'];
    }
    $shipping = 2500;
    $total = $subtotal + $shipping;
    
    // Obtener direcciones del usuario
    $query_addresses = "SELECT idDireccion, direccionCompleta FROM direccionentrega WHERE idUsuario = :user_id ORDER BY idDireccion DESC";
    $stmt_addresses = $db->prepare($query_addresses);
    $stmt_addresses->bindParam(':user_id', $user['id']);
    $stmt_addresses->execute();
    $addresses = $stmt_addresses->fetchAll();
    
} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());
    header('Location: carrito.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Blue</title>
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

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            padding: 0 20px;
        }

        .step {
            display: flex;
            align-items: center;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            width: 60px;
            height: 2px;
            background-color: #e2e8f0;
            margin: 0 20px;
        }

        .step.completed:not(:last-child)::after {
            background-color: #667eea;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e2e8f0;
            color: #a0aec0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }

        .step.active .step-circle {
            background-color: #667eea;
            color: white;
        }

        .step.completed .step-circle {
            background-color: #48bb78;
            color: white;
        }

        .step-label {
            font-weight: 500;
            color: #4a5568;
        }

        .step.active .step-label {
            color: #667eea;
        }

        /* Checkout Layout */
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            align-items: start;
        }

        .checkout-main {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .checkout-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Cart Summary */
        .cart-summary-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 60px;
            height: 60px;
            background-color: #f7fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .item-image img {
            max-width: 50px;
            max-height: 50px;
            object-fit: contain;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .item-quantity {
            font-size: 0.9rem;
            color: #718096;
        }

        .item-price {
            font-weight: 600;
            color: #667eea;
        }

        /* Address Selection */
        .address-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }

        .address-option {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
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
            margin-bottom: 5px;
        }

        .address-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-link {
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: underline;
            padding: 0;
        }

        .btn-link:hover {
            color: #5a67d8;
        }

        .add-address-btn {
            width: 100%;
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
            gap: 15px;
        }

        .payment-option {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
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
            width: 50px;
            height: 50px;
            background-color: #f7fafc;
            border-radius: 8px;
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
            margin-bottom: 5px;
        }

        .payment-description {
            font-size: 0.9rem;
            color: #718096;
        }

        /* Order Summary */
        .order-summary {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 130px;
        }

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

        /* Buttons */
        .btn-primary {
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
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
            margin-top: 15px;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .continue-shopping:hover {
            background: #cbd5e0;
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

            .checkout-layout {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .order-summary {
                position: static;
                order: -1;
            }

            .checkout-section,
            .order-summary {
                padding: 20px;
            }

            .progress-steps {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }

            .step:not(:last-child)::after {
                width: 2px;
                height: 40px;
                margin: 10px 0;
            }

            .step {
                flex-direction: column;
                text-align: center;
            }

            .step-circle {
                margin-right: 0;
                margin-bottom: 5px;
            }
        }

        @media (max-width: 480px) {
            .checkout-section,
            .order-summary {
                padding: 15px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .address-option,
            .payment-option {
                padding: 15px;
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
            <h1 class="page-title">Finalizar Compra</h1>
            <p class="page-subtitle">Revisa tu pedido y confirma los detalles de entrega</p>
        </div>

        <div class="progress-steps">
            <div class="step completed">
                <div class="step-circle">✓</div>
                <div class="step-label">Carrito</div>
            </div>
            <div class="step active">
                <div class="step-circle">2</div>
                <div class="step-label">Entrega</div>
            </div>
            <div class="step">
                <div class="step-circle">3</div>
                <div class="step-label">Pago</div>
            </div>
            <div class="step">
                <div class="step-circle">4</div>
                <div class="step-label">Confirmación</div>
            </div>
        </div>

        <div class="checkout-layout">
            <div class="checkout-main">
                <!-- Resumen del Carrito -->
                <div class="checkout-section">
                    <h2 class="section-title">
                        <span class="section-number">1</span>
                        Resumen de tu Pedido
                    </h2>
                    <div class="cart-summary-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="summary-item">
                                <div class="item-image">
                                    <img src="img/main/placeholder-product.png" alt="<?= htmlspecialchars($item['nombre']) ?>">
                                </div>
                                <div class="item-details">
                                    <div class="item-name"><?= htmlspecialchars($item['nombre']) ?></div>
                                    <div class="item-quantity">Cantidad: <?= $item['cantidad'] ?></div>
                                </div>
                                <div class="item-price">$<?= number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Dirección de Entrega -->
                <div class="checkout-section">
                    <h2 class="section-title">
                        <span class="section-number">2</span>
                        Dirección de Entrega
                    </h2>
                    <div class="address-list" id="addressList">
                        <?php if (empty($addresses)): ?>
                            <p style="color: #718096; margin-bottom: 20px;">No tienes direcciones guardadas. Agrega una dirección para continuar.</p>
                        <?php else: ?>
                            <?php foreach ($addresses as $index => $address): ?>
                                <div class="address-option <?= $index === 0 ? 'selected' : '' ?>" onclick="selectAddress(this, <?= $address['idDireccion'] ?>)">
                                    <input type="radio" name="address" value="<?= $address['idDireccion'] ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                    <div class="address-text"><?= htmlspecialchars($address['direccionCompleta']) ?></div>
                                    <div class="address-actions">
                                        <button type="button" class="btn-link" onclick="event.stopPropagation(); editAddress(<?= $address['idDireccion'] ?>, '<?= htmlspecialchars($address['direccionCompleta'], ENT_QUOTES) ?>')">Editar</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <button type="button" class="add-address-btn" onclick="openAddressModal()">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Agregar nueva dirección
                        </button>
                    </div>
                </div>

                <!-- Método de Pago -->
                <div class="checkout-section">
                    <h2 class="section-title">
                        <span class="section-number">3</span>
                        Método de Pago
                    </h2>
                    <div class="payment-methods">
                        <div class="payment-option selected" onclick="selectPayment(this, 1)">
                            <input type="radio" name="payment" value="1" checked>
                            <div class="payment-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                                    <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                                    <line x1="18" y1="12" x2="18" y2="12"></line>
                                </svg>
                            </div>
                            <div class="payment-details">
                                <div class="payment-name">Contraentrega</div>
                                <div class="payment-description">Paga en efectivo cuando recibas tu pedido</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen del Pedido -->
            <div class="order-summary">
                <h3 class="summary-title">Resumen del Pedido</h3>
                
                <div class="summary-row">
                    <span class="summary-label">Productos (<?= count($cartItems) ?>)</span>
                    <span class="summary-value">$<?= number_format($subtotal, 0, ',', '.') ?></span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Envío</span>
                    <span class="summary-value">$<?= number_format($shipping, 0, ',', '.') ?></span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Total</span>
                    <span class="summary-value">$<?= number_format($total, 0, ',', '.') ?></span>
                </div>

                <button class="btn-primary" onclick="finalizeOrder()" id="finalizeBtn">
                    Confirmar Pedido
                    <span class="spinner" id="finalizeSpinner" style="display: none;"></span>
                </button>
                
                <a href="carrito.php" class="continue-shopping">Volver al carrito</a>
            </div>
        </div>
    </main>

    <!-- Modal para agregar/editar dirección -->
    <div class="modal" id="addressModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="addressModalTitle">Agregar Dirección</h3>
                <button class="modal-close" onclick="closeModal('addressModal')">&times;</button>
            </div>
            <form id="addressForm">
                <input type="hidden" id="addressId" name="addressId">
                <div class="form-group">
                    <label for="addressText">Dirección completa</label>
                    <textarea id="addressText" name="addressText" rows="3" placeholder="Ingresa tu dirección completa incluyendo ciudad, barrio, calle y número" required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addressModal')">Cancelar</button>
                    <button type="submit" class="btn-primary" style="width: auto;">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/global-search.js"></script>
    <script>
        let selectedAddressId = <?= !empty($addresses) ? $addresses[0]['idDireccion'] : 'null' ?>;
        let selectedPaymentId = 1; // Contraentrega por defecto

        document.addEventListener("DOMContentLoaded", () => {
            // Verificar que hay una dirección seleccionada
            updateFinalizeButton();
        });

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

        function openAddressModal(id = null, text = "") {
            const modal = document.getElementById("addressModal");
            const title = document.getElementById("addressModalTitle");
            const form = document.getElementById("addressForm");
            
            if (id) {
                title.textContent = "Editar Dirección";
                document.getElementById("addressId").value = id;
                document.getElementById("addressText").value = text;
            } else {
                title.textContent = "Agregar Dirección";
                form.reset();
                document.getElementById("addressId").value = "";
            }
            
            modal.style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        function editAddress(id, text) {
            openAddressModal(id, text);
        }

        // Manejar envío del formulario de dirección
        document.getElementById("addressForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            const isEdit = data.addressId !== "";

            try {
                const endpoint = isEdit ? "backend/php/update_address.php" : "backend/php/add_address.php";
                const requestData = isEdit ? 
                    { idDireccion: data.addressId, direccion: data.addressText } :
                    { direccionCompleta: data.addressText };
                
                const response = await fetch(endpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(requestData),
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(isEdit ? "Dirección actualizada" : "Dirección agregada", "success");
                    closeModal("addressModal");
                    
                    // Recargar la página para mostrar las direcciones actualizadas
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showNotification(error.message || "Error al guardar la dirección", "error");
            }
        });

        async function finalizeOrder() {
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
                
                const response = await fetch("backend/php/create-order.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        addressId: selectedAddressId,
                        paymentMethodId: selectedPaymentId,
                        notes: ""
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    showNotification("¡Pedido creado exitosamente!", "success");
                    
                    // Redirigir a página de confirmación después de un breve delay
                    setTimeout(() => {
                        window.location.href = `order-confirmation.php?order=${result.data.orderId}`;
                    }, 2000);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error("Order creation error:", error);
                showNotification(error.message || "Error al crear el pedido", "error");
                finalizeBtn.disabled = false;
                spinner.style.display = 'none';
            }
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
