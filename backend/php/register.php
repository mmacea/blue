<?php
// Configurar headers CORS al inicio
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, "Método no permitido", null, 405);
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Si no hay datos JSON, intentar obtener de $_POST
    if (!$input) {
        $input = $_POST;
    }
    
    // Validar que se recibieron datos
    if (!$input) {
        sendJsonResponse(false, "No se recibieron datos", null, 400);
    }
    
    // Validar campos requeridos
    $required_fields = ['nombre', 'email', 'telefono', 'contrasena'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            sendJsonResponse(false, "El campo {$field} es requerido", null, 400);
        }
    }
    
    $nombre = trim($input['nombre']);
    $email = trim($input['email']);
    $telefono = trim($input['telefono']);
    $password = $input['contrasena'];
    $direccion = isset($input['direccion']) ? trim($input['direccion']) : '';
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(false, "Formato de email inválido", null, 400);
    }
    
    // Validar longitud de contraseña
    if (strlen($password) < 6) {
        sendJsonResponse(false, "La contraseña debe tener al menos 6 caracteres", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el email ya existe
    $query = "SELECT idUsuario FROM usuarios WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        sendJsonResponse(false, "El email ya está registrado", null, 409);
    }
    
    // Verificar si el teléfono ya existe
    $query = "SELECT idUsuario FROM usuarios WHERE telefono = :telefono";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        sendJsonResponse(false, "El teléfono ya está registrado", null, 409);
    }
    
    // Encriptar contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generar token de confirmación
    $token_confirmacion = bin2hex(random_bytes(32));
    
    // Insertar usuario
    $query = "INSERT INTO usuarios (nombre, email, telefono, contrasena, direccion, tokenConfirmacion, rol, estado, emailVerificado) 
              VALUES (:nombre, :email, :telefono, :contrasena, :direccion, :token, 'cliente', 'activo', 0)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':contrasena', $hashed_password);
    $stmt->bindParam(':direccion', $direccion);
    $stmt->bindParam(':token', $token_confirmacion);
    
    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();
        
        // Crear notificación de bienvenida
        try {
            $query = "INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo, fechaCreacion) 
                      VALUES (:user_id, :titulo, :mensaje, 'success', NOW())";
            
            $stmt = $db->prepare($query);
            $titulo = "¡Bienvenido a Blue Pharmacy!";
            $mensaje = "Tu cuenta ha sido creada exitosamente. ¡Comienza a explorar nuestros productos!";
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':mensaje', $mensaje);
            $stmt->execute();
        } catch (Exception $e) {
            // No fallar el registro si no se puede crear la notificación
            error_log("Notification creation failed: " . $e->getMessage());
        }
        
        // Crear sesión automáticamente
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $nombre;
        $_SESSION['user_role'] = 'cliente';
        
        sendJsonResponse(true, "Usuario registrado exitosamente", [
            'user_id' => $user_id,
            'message' => "Cuenta creada exitosamente",
            'user_data' => [
                'id' => $user_id,
                'nombre' => $nombre,
                'email' => $email,
                'rol' => 'cliente'
            ],
            'redirect' => 'index.php'
        ]);
    } else {
        sendJsonResponse(false, "Error al crear la cuenta", null, 500);
    }
    
} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor: " . $e->getMessage(), null, 500);
}
?>
