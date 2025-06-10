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
    $direccionCompleta = isset($input['direccionCompleta']) ? trim($input['direccionCompleta']) : '';
    $setAsMain = isset($input['setAsMain']) ? (bool)$input['setAsMain'] : false;
    
    if (empty($direccionCompleta)) {
        sendJsonResponse(false, "La dirección es requerida", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // If user wants to set as main address, update usuarios table
    if ($setAsMain) {
        $query = "UPDATE usuarios SET direccion = :direccion WHERE idUsuario = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':direccion', $direccionCompleta);
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->execute();
        
        sendJsonResponse(true, "Dirección principal actualizada exitosamente");
    } else {
        // Add to direccionentrega table
        $query = "INSERT INTO direccionentrega (idUsuario, direccionCompleta) VALUES (:user_id, :direccion)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->bindParam(':direccion', $direccionCompleta);
        
        if ($stmt->execute()) {
            sendJsonResponse(true, "Dirección agregada exitosamente");
        } else {
            sendJsonResponse(false, "Error al agregar la dirección", null, 500);
        }
    }
    
} catch (Exception $e) {
    error_log("Add address error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
