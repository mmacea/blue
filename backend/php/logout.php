<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, "Método no permitido", null, 405);
}

try {
    session_start();
    
    if (isset($_SESSION['session_id'])) {
        $database = new Database();
        $db = $database->getConnection();
        
        // Desactivar sesión en base de datos
        $query = "UPDATE sesiones SET activa = 0 WHERE idSesion = :session_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':session_id', $_SESSION['session_id']);
        $stmt->execute();
    }
    
    // Destruir sesión
    session_destroy();
    
    sendJsonResponse(true, "Sesión cerrada exitosamente");
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
