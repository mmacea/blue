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
    
    // Map the form field names to database column names
    $nombre = isset($input['nombre']) ? trim($input['nombre']) : '';
    $telefono = isset($input['telefono']) ? trim($input['telefono']) : '';
    $direccion = isset($input['direccion']) ? trim($input['direccion']) : '';
    $user_id = $user['id'];
    
    if (empty($nombre)) {
        sendJsonResponse(false, "El nombre es requerido", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el teléfono ya existe (si se está cambiando)
    if (!empty($telefono)) {
        $query = "SELECT idUsuario FROM usuarios WHERE telefono = :telefono AND idUsuario != :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            sendJsonResponse(false, "El teléfono ya está registrado por otro usuario", null, 409);
        }
    }
    
    $query = "UPDATE usuarios SET nombre = :nombre, telefono = :telefono 
          WHERE idUsuario = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        // Get updated user data to return
        $query = "SELECT idUsuario, nombre, email, telefono FROM usuarios WHERE idUsuario = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $updatedUser = $stmt->fetch();
        
        sendJsonResponse(true, "Perfil actualizado exitosamente", $updatedUser);
    } else {
        sendJsonResponse(false, "Error al actualizar el perfil", null, 500);
    }
    
} catch (Exception $e) {
    error_log("Update profile error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
