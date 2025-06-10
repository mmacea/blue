<?php
require_once '../config/database.php';

try {
    $user_id = isset($_GET['userId']) ? (int)$_GET['userId'] : null;
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    if (!$user_id) {
        $user = validateSession();
        if (!$user) {
            sendJsonResponse(false, "No autenticado", null, 401);
        }
        $user_id = $user['id'];
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT pp.cantidad, pp.precioUnitario, p.nombre as producto_nombre, 
                     ped.fecha, ped.montoTotal
              FROM pedidos ped
              JOIN pedidoproducto pp ON ped.idPedido = pp.idPedido
              JOIN producto p ON pp.idProducto = p.idProducto
              WHERE ped.idUsuario = :user_id";
    
    // Aplicar filtro de fecha
    switch ($filter) {
        case 'week':
            $query .= " AND ped.fecha >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $query .= " AND ped.fecha >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $query .= " AND ped.fecha >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
    
    $query .= " ORDER BY ped.fecha DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $purchases = $stmt->fetchAll();
    
    // Agregar imagen placeholder para cada producto
    foreach ($purchases as &$purchase) {
        $purchase['imagen'] = "img/main/placeholder-product.png";
    }
    
    sendJsonResponse(true, "Compras obtenidas exitosamente", [
        'purchases' => $purchases
    ]);
    
} catch (Exception $e) {
    error_log("Get purchases error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
