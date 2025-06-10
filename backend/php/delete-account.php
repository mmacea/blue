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
    $user_id = $user['id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Desactivar sesiones
        $query = "UPDATE sesiones SET activa = 0 WHERE idUsuario = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Marcar usuario como inactivo en lugar de eliminarlo
        $query = "UPDATE usuarios SET estado = 'eliminado', email = CONCAT(email, '_deleted_', UNIX_TIMESTAMP()) 
                  WHERE idUsuario = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $db->commit();
        
        // Destruir sesión actual
        session_destroy();
        
        sendJsonResponse(true, "Cuenta eliminada exitosamente");
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete account error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
