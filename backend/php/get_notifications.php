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
    
    $query = "SELECT idNotificacion, titulo, mensaje, tipo, leida, fecha
              FROM notificaciones
              WHERE idUsuario = :user_id
              ORDER BY fecha DESC
              LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll();
    
    sendJsonResponse(true, "Notificaciones obtenidas exitosamente", [
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    error_log("Get notifications error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
