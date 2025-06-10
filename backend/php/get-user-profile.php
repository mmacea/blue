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
    
    $query = "SELECT idUsuario, nombre, email, telefono, rol, estado, fechaRegistro FROM usuarios WHERE idUsuario = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, "Usuario no encontrado", null, 404);
    }
    
    $profile = $stmt->fetch();
    
    // Asegurar que todos los campos existan con valores por defecto
    $profile = array_merge([
        'nombre' => '',
        'email' => '',
        'telefono' => '',
        'rol' => 'usuario',
        'estado' => 'activo'
    ], $profile);
    
    // Log para debugging
    error_log("Profile data retrieved: " . print_r($profile, true));
    
    sendJsonResponse(true, "Perfil obtenido exitosamente", $profile);
    
} catch (Exception $e) {
    error_log("Get user profile error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
