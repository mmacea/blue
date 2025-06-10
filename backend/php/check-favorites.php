<?php
require_once '../config/database.php';

// Verificar si el usuario está autenticado
$user = validateSession();
if (!$user) {
    sendJsonResponse(false, "No autenticado", null, 401);
    exit;
}

try {
    $productId = isset($_GET['productId']) ? (int)$_GET['productId'] : null;
    
    if (!$productId) {
        sendJsonResponse(false, "ID de producto no proporcionado", null, 400);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el producto está en favoritos
    $query = "SELECT idFavorito FROM favoritos WHERE idUsuario = :user_id AND idProducto = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    
    $isFavorite = $stmt->rowCount() > 0;
    
    sendJsonResponse(true, "Estado de favorito obtenido", [
        'is_favorite' => $isFavorite,
        'product_id' => $productId
    ]);
    
} catch (Exception $e) {
    error_log("Check favorite error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
