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
    
    $query = "SELECT idDireccion, direccionCompleta 
              FROM direccionentrega 
              WHERE idUsuario = :user_id 
              ORDER BY idDireccion DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $addresses = $stmt->fetchAll();
    
    sendJsonResponse(true, "Direcciones obtenidas exitosamente", [
        'addresses' => $addresses
    ]);
    
} catch (Exception $e) {
    error_log("Get addresses error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
