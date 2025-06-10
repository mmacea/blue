<?php
require_once '../config/database.php';

try {
    $user_id = isset($_GET['userId']) ? (int)$_GET['userId'] : null;
    
    if (!$user_id) {
        $user = validateSession();
        if (!$user) {
            sendJsonResponse(false, "No autenticado", null, 401);
        }
        $user_id = $user['id'];
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT p.idProducto, p.nombre, p.descripcion, p.precio, p.stock, c.nombre as categoria_nombre, f.idFavorito, f.fechaAgregado
              FROM favoritos f
              JOIN producto p ON f.idProducto = p.idProducto
              LEFT JOIN categoria c ON p.idCategoria = c.idCategoria
              WHERE f.idUsuario = :user_id
              ORDER BY f.fechaAgregado DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $favorites = $stmt->fetchAll();
    
    // Agregar imagen placeholder para cada producto y asegurar tipos de datos correctos
    foreach ($favorites as &$favorite) {
        $favorite['idProducto'] = (int)$favorite['idProducto'];
        $favorite['precio'] = (float)$favorite['precio'];
        $favorite['stock'] = (int)$favorite['stock'];
        $favorite['imagen'] = "img/main/placeholder-product.png";
    }
    
    sendJsonResponse(true, "Favoritos obtenidos exitosamente", [
        'favorites' => $favorites
    ]);
    
} catch (Exception $e) {
    error_log("Get favorites error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
