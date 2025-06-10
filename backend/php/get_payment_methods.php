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
    
    // Por ahora devolvemos métodos de pago predeterminados
    // En una implementación real, estos estarían asociados al usuario
    $query = "SELECT idMetodoPago, tipo, detalles FROM metodopago ORDER BY idMetodoPago";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $methods = $stmt->fetchAll();
    
    // Marcar el primero como seleccionado por defecto
    foreach ($methods as $index => &$method) {
        $method['selected'] = $index === 0;
    }
    
    sendJsonResponse(true, "Métodos de pago obtenidos exitosamente", [
        'methods' => $methods
    ]);
    
} catch (Exception $e) {
    error_log("Get payment methods error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
