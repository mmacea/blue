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
    
    $current_password = isset($input['currentPassword']) ? $input['currentPassword'] : '';
    $new_password = isset($input['newPassword']) ? $input['newPassword'] : '';
    $user_id = $user['id'];
    
    if (empty($current_password) || empty($new_password)) {
        sendJsonResponse(false, "Contraseña actual y nueva contraseña son requeridas", null, 400);
    }
    
    if (strlen($new_password) < 8) {
        sendJsonResponse(false, "La nueva contraseña debe tener al menos 8 caracteres", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar contraseña actual
    $query = "SELECT contrasena FROM usuarios WHERE idUsuario = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $user_data = $stmt->fetch();
    
    if (!password_verify($current_password, $user_data['contrasena'])) {
        sendJsonResponse(false, "La contraseña actual es incorrecta", null, 401);
    }
    
    // Actualizar contraseña
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $query = "UPDATE usuarios SET contrasena = :password WHERE idUsuario = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        // Crear notificación
        $query = "INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo) 
                  VALUES (:user_id, :titulo, :mensaje, 'info')";
        
        $stmt = $db->prepare($query);
        $titulo = "Contraseña actualizada";
        $mensaje = "Tu contraseña ha sido cambiada exitosamente";
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':mensaje', $mensaje);
        $stmt->execute();
        
        sendJsonResponse(true, "Contraseña cambiada exitosamente");
    } else {
        sendJsonResponse(false, "Error al cambiar la contraseña", null, 500);
    }
    
} catch (Exception $e) {
    error_log("Change password error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
