<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $query = "SELECT p.idProducto, p.nombre, p.descripcion, p.precio, p.stock, 
                     c.nombre as categoria_nombre
              FROM producto p 
              LEFT JOIN categoria c ON p.idCategoria = c.idCategoria 
              WHERE p.stock > 0";
    
    $params = [];
    
    if ($category_id) {
        $query .= " AND p.idCategoria = :category_id";
        $params[':category_id'] = $category_id;
    }
    
    $query .= " ORDER BY p.nombre ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll();
    
    // Agregar imagen placeholder para cada producto
    foreach ($products as &$product) {
        $product['imagen'] = "img/main/placeholder-product.png";
    }
    
    sendJsonResponse(true, "Productos obtenidos exitosamente", [
        'products' => $products,
        'total' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log("Get products error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
