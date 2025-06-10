<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, "Método no permitido", null, 405);
}

try {
    $user = validateSession();
    if (!$user) {
        sendJsonResponse(false, "No autenticado", null, 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['productId']) || !isset($input['quantity'])) {
        sendJsonResponse(false, "ID del producto y cantidad son requeridos", null, 400);
    }
    
    $product_id = (int)$input['productId'];
    $quantity = (int)$input['quantity'];
    $user_id = $user['id'];
    
    if ($quantity <= 0) {
        sendJsonResponse(false, "La cantidad debe ser mayor a 0", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el producto existe y tiene stock
    $query = "SELECT idProducto, nombre, precio, stock FROM producto WHERE idProducto = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, "Producto no encontrado", null, 404);
    }
    
    $product = $stmt->fetch();
    
    if ($product['stock'] < $quantity) {
        sendJsonResponse(false, "Stock insuficiente. Disponible: " . $product['stock'], null, 400);
    }
    
    // Verificar si el producto ya está en el carrito
    $query = "SELECT idCarrito, cantidad FROM carrito WHERE idUsuario = :user_id AND idProducto = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Actualizar cantidad existente
        $cart_item = $stmt->fetch();
        $new_quantity = $cart_item['cantidad'] + $quantity;
        
        if ($new_quantity > $product['stock']) {
            sendJsonResponse(false, "Stock insuficiente. Disponible: " . $product['stock'], null, 400);
        }
        
        $query = "UPDATE carrito SET cantidad = :quantity WHERE idCarrito = :cart_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $new_quantity);
        $stmt->bindParam(':cart_id', $cart_item['idCarrito']);
        $stmt->execute();
    } else {
        // Agregar nuevo item al carrito
        $query = "INSERT INTO carrito (idUsuario, idProducto, cantidad) VALUES (:user_id, :product_id, :quantity)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();
    }
    
    // Obtener total de items en carrito
    $query = "SELECT SUM(cantidad) as total_items FROM carrito WHERE idUsuario = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $cart_count = $stmt->fetch()['total_items'] ?? 0;
    
    sendJsonResponse(true, "Producto agregado al carrito", [
        'cart_count' => (int)$cart_count
    ]);
    
} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
