<?php
require_once '../config/database.php';

try {
    $search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($search_term)) {
        sendJsonResponse(false, "Término de búsqueda requerido", null, 400);
    }
    
    if (strlen($search_term) < 2) {
        sendJsonResponse(false, "El término de búsqueda debe tener al menos 2 caracteres", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $search_pattern = '%' . $search_term . '%';
    
    $query = "SELECT p.idProducto, p.nombre, p.descripcion, p.precio, p.stock,
                     c.nombre as categoria_nombre
              FROM producto p
              LEFT JOIN categoria c ON p.idCategoria = c.idCategoria
              WHERE (p.nombre LIKE :search_term 
                     OR p.descripcion LIKE :search_term 
                     OR c.nombre LIKE :search_term)
              AND p.stock > 0
              ORDER BY 
                CASE 
                  WHEN p.nombre LIKE :exact_term THEN 1
                  WHEN p.nombre LIKE :start_term THEN 2
                  ELSE 3
                END,
                p.nombre ASC
              LIMIT 20";
    
    $stmt = $db->prepare($query);
    $exact_term = $search_term . '%';
    $start_term = $search_term . '%';
    
    $stmt->bindParam(':search_term', $search_pattern);
    $stmt->bindParam(':exact_term', $exact_term);
    $stmt->bindParam(':start_term', $start_term);
    $stmt->execute();
    
    $products = $stmt->fetchAll();
    
    // Agregar imagen placeholder para cada producto
    foreach ($products as &$product) {
        $product['imagen'] = "img/main/placeholder-product.png";
    }
    
    sendJsonResponse(true, "Búsqueda completada", [
        'products' => $products,
        'search_term' => $search_term,
        'total_results' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log("Search products error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
