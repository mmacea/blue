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
    $address_id = isset($input['idDireccion']) ? (int)$input['idDireccion'] : 0;
    
    if (!$address_id) {
        sendJsonResponse(false, "ID de dirección es requerido", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que la dirección pertenece al usuario
    $query = "DELETE FROM direccionentrega WHERE idDireccion = :address_id AND idUsuario = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':address_id', $address_id);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, "Dirección no encontrada", null, 404);
    }
    
    sendJsonResponse(true, "Dirección eliminada exitosamente");
    
} catch (Exception $e) {
    error_log("Delete address error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
