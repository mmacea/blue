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
    
    if (!isset($input['productId'])) {
        sendJsonResponse(false, "ID del producto es requerido", null, 400);
    }
    
    $product_id = (int)$input['productId'];
    $user_id = $user['id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM carrito WHERE idUsuario = :user_id AND idProducto = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, "Item no encontrado en el carrito", null, 404);
    }
    
    sendJsonResponse(true, "Producto eliminado del carrito exitosamente");
    
} catch (Exception $e) {
    error_log("Remove cart item error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
