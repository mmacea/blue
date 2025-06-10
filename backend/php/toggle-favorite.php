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
    
    // Verificar si ya está en favoritos
    $query = "SELECT idFavorito FROM favoritos WHERE idUsuario = :user_id AND idProducto = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    $is_favorite = $stmt->rowCount() > 0;
    $action = '';
    
    if ($is_favorite) {
        // Eliminar de favoritos
        $query = "DELETE FROM favoritos WHERE idUsuario = :user_id AND idProducto = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        $new_status = false;
        $message = "Producto eliminado de destacados";
        $action = 'removed';
    } else {
        // Agregar a favoritos
        $query = "INSERT INTO favoritos (idUsuario, idProducto) VALUES (:user_id, :product_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        $new_status = true;
        $message = "Producto agregado a destacados";
        $action = 'added';
    }
    
    // Obtener información del producto para la respuesta
    $query = "SELECT p.nombre, p.precio FROM producto p WHERE p.idProducto = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $product_name = $product ? $product['nombre'] : 'Producto';
    $product_price = $product ? $product['precio'] : 0;

    sendJsonResponse(true, $message, [
        'is_favorite' => $new_status,
        'product_id' => $product_id,
        'product_name' => $product_name,
        'product_price' => $product_price,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Toggle favorite error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
