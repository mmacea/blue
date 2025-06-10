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

// Obtener ID del pedido
$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;

if (!$orderId) {
    header('Location: index.php');
    exit;
}

// Obtener detalles del pedido
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el pedido pertenece al usuario
    $query = "SELECT p.*, ep.estado, d.direccionCompleta, mp.tipo as metodoPago
              FROM pedidos p
              JOIN estadopedido ep ON p.idEstado = ep.idEstado
              JOIN direccionentrega d ON p.idDireccion = d.idDireccion
              JOIN metodopago mp ON p.idMetodoPago = mp.idMetodoPago
              WHERE p.idPedido = :order_id AND p.idUsuario = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $orderId);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Location: index.php');
        exit;
    }
    
    $order = $stmt->fetch();
    
    // Obtener productos del pedido
    $query = "SELECT pp.*, p.nombre, p.descripcion
              FROM pedidoproducto pp
              JOIN producto p ON pp.idProducto = p.idProducto
              WHERE pp.idPedido = :order_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $orderId);
    $stmt->execute();
    $orderItems = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Order confirmation error: " . $e->getMessage());
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - Blue</title>
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

        /* Main Content */
        .main-content {
            margin-top: 106px;
            padding: 40px 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .confirmation-header {
            text-align: center;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 60px 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 2.5rem;
        }

        .confirmation-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .confirmation-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .order-number {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        /* Order Details */
        .order-details {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .details-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 30px;
            text-align: center;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .detail-item {
            text-align: center;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            background-color: #fef5e7;
            color: #d69e2e;
        }

        /* Order Items */
        .order-items {
            border-top: 1px solid #e2e8f0;
            padding-top: 30px;
        }

        .items-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-info {
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

        .item-total {
            font-weight: 600;
            color: #667eea;
        }

        /* Order Total */
        .order-total {
            border-top: 2px solid #e2e8f0;
            padding-top: 20px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .total-row.final {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            margin-top: 10px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn-primary {
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

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
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

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .HeaderSite {
                height: 70px;
                padding: 0 15px;
            }

            .Logo img {
                height: 45px;
                width: auto;
            }

            .main-content {
                margin-top: 70px;
                padding: 20px 15px;
            }

            .confirmation-header {
                padding: 40px 20px;
            }

            .confirmation-title {
                font-size: 2rem;
            }

            .order-details {
                padding: 20px;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                max-width: 300px;
            }

            .item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .item-total {
                align-self: flex-end;
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
    </header>

    <main class="main-content">
        <div class="confirmation-header">
            <div class="success-icon">✓</div>
            <h1 class="confirmation-title">¡Pedido Confirmado!</h1>
            <p class="confirmation-subtitle">Tu pedido ha sido procesado exitosamente</p>
            <p class="order-number">Número de pedido: #<?= htmlspecialchars($order['idPedido']) ?></p>
        </div>

        <div class="order-details">
            <h2 class="details-title">Detalles del Pedido</h2>
            
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Fecha del Pedido</div>
                    <div class="detail-value"><?= date('d/m/Y H:i', strtotime($order['fecha'])) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Estado</div>
                    <div class="detail-value">
                        <span class="status-badge"><?= htmlspecialchars($order['estado']) ?></span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Método de Pago</div>
                    <div class="detail-value"><?= htmlspecialchars($order['metodoPago']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Dirección de Entrega</div>
                    <div class="detail-value"><?= htmlspecialchars($order['direccionCompleta']) ?></div>
                </div>
            </div>

            <div class="order-items">
                <h3 class="items-title">Productos Pedidos</h3>
                <?php foreach ($orderItems as $item): ?>
                    <div class="item">
                        <div class="item-info">
                            <div class="item-name"><?= htmlspecialchars($item['nombre']) ?></div>
                            <div class="item-quantity">Cantidad: <?= $item['cantidad'] ?> × $<?= number_format($item['precioUnitario'], 0, ',', '.') ?></div>
                        </div>
                        <div class="item-total">$<?= number_format($item['cantidad'] * $item['precioUnitario'], 0, ',', '.') ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="order-total">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($order['montoTotal'] - 2500, 0, ',', '.') ?></span>
                    </div>
                    <div class="total-row">
                        <span>Envío:</span>
                        <span>$2.500</span>
                    </div>
                    <div class="total-row final">
                        <span>Total:</span>
                        <span>$<?= number_format($order['montoTotal'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="perfil.php#purchases" class="btn-primary">Ver mis pedidos</a>
            <a href="index.php" class="btn-secondary">Seguir comprando</a>
        </div>
    </main>

    <script src="js/global-search.js"></script>
</body>
</html>
