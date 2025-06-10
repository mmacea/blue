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
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($quantity <= 0) {
        // Eliminar item del carrito
        $query = "DELETE FROM carrito WHERE idUsuario = :user_id AND idProducto = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        sendJsonResponse(true, "Producto eliminado del carrito");
        return;
    }
    
    // Verificar stock disponible
    $query = "SELECT stock FROM producto WHERE idProducto = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, "Producto no encontrado", null, 404);
    }
    
    $product = $stmt->fetch();
    
    if ($quantity > $product['stock']) {
        sendJsonResponse(false, "Stock insuficiente. Disponible: " . $product['stock'], null, 400);
    }
    
    // Actualizar cantidad
    $query = "UPDATE carrito SET cantidad = :quantity WHERE idUsuario = :user_id AND idProducto = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, "Item no encontrado en el carrito", null, 404);
    }
    
    sendJsonResponse(true, "Cantidad actualizada exitosamente");
    
} catch (Exception $e) {
    error_log("Update cart item error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
