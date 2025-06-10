<?php
require_once '../config/database.php';

try {
    $user = validateSession();
    if (!$user) {
        sendJsonResponse(false, "No autenticado", null, 401);
    }
    
    $user_id = $user['id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT c.idCarrito, c.cantidad, c.fechaAgregado,
                     p.idProducto, p.nombre, p.descripcion, p.precio, p.stock
              FROM carrito c
              JOIN producto p ON c.idProducto = p.idProducto
              WHERE c.idUsuario = :user_id
              ORDER BY c.fechaAgregado DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $cart_items = $stmt->fetchAll();
    $items = [];
    $total = 0;
    
    foreach ($cart_items as $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $total += $subtotal;
        
        $stock_issue = $item['cantidad'] > $item['stock'];
        
        $items[] = [
            'id' => $item['idProducto'],
            'cart_id' => $item['idCarrito'],
            'name' => $item['nombre'],
            'description' => $item['descripcion'],
            'price' => (float)$item['precio'],
            'quantity' => (int)$item['cantidad'],
            'stock' => (int)$item['stock'],
            'subtotal' => $subtotal,
            'image' => "img/main/placeholder-product.png",
            'stockIssue' => $stock_issue
        ];
    }
    
    sendJsonResponse(true, "Carrito obtenido exitosamente", [
        'items' => $items,
        'total' => $total,
        'item_count' => count($items)
    ]);
    
} catch (Exception $e) {
    error_log("Get cart error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
