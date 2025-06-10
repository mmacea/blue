<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, "Método no permitido", null, 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email'])) {
        sendJsonResponse(false, "Email es requerido", null, 400);
    }
    
    $email = trim($input['email']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(false, "Email inválido", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el email existe
    $query = "SELECT idUsuario, nombre FROM usuarios WHERE email = :email AND estado = 'activo'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Por seguridad, siempre devolvemos éxito aunque el email no exista
        sendJsonResponse(true, "Si el email existe, recibirás un enlace de recuperación");
        return;
    }
    
    $user = $stmt->fetch();
    
    // Generar token de recuperación
    $reset_token = bin2hex(random_bytes(32));
    $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Guardar token (en una implementación real, necesitarías una tabla para tokens de reset)
    $query = "UPDATE usuarios SET token_confirmacion = :token WHERE idUsuario = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $reset_token);
    $stmt->bindParam(':user_id', $user['idUsuario']);
    $stmt->execute();
    
    // En una implementación real, aquí enviarías el email
    // Por ahora solo simulamos el envío
    
    sendJsonResponse(true, "Si el email existe, recibirás un enlace de recuperación", [
        'message' => "Enlace de recuperación enviado"
    ]);
    
} catch (Exception $e) {
    error_log("Forgot password error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
