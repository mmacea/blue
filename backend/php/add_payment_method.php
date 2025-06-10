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
    
    $tipo = isset($_POST['paymentType']) ? trim($_POST['paymentType']) : '';
    $detalles = isset($_POST['cardDetails']) ? trim($_POST['cardDetails']) : '';
    
    if (empty($tipo)) {
        sendJsonResponse(false, "El tipo de método de pago es requerido", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO metodopago (tipo, detalles) VALUES (:tipo, :detalles)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':detalles', $detalles);
    
    if ($stmt->execute()) {
        $method_id = $db->lastInsertId();
        
        sendJsonResponse(true, "Método de pago agregado exitosamente", [
            'id' => $method_id,
            'tipo' => $tipo,
            'detalles' => $detalles
        ]);
    } else {
        sendJsonResponse(false, "Error al agregar el método de pago", null, 500);
    }
    
} catch (Exception $e) {
    error_log("Add payment method error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
