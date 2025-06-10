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
    
    // Log para debugging
    error_log("Login input: " . print_r($input, true));
    
    if (!isset($input['email']) || !isset($input['contrasena'])) {
        sendJsonResponse(false, "Email y contraseña son requeridos", null, 400);
    }
    
    $email = trim($input['email']);
    $password = $input['contrasena'];
    
    if (empty($email) || empty($password)) {
        sendJsonResponse(false, "Email y contraseña no pueden estar vacíos", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar usuario por email - usar nombres de columnas correctos
    $query = "SELECT idUsuario, nombre, email, contrasena, rol, estado, emailVerificado 
              FROM usuarios 
              WHERE email = :email";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, "Credenciales incorrectas", null, 401);
    }
    
    $user = $stmt->fetch();
    
    // Verificar estado del usuario
    if ($user['estado'] !== 'activo') {
        sendJsonResponse(false, "Cuenta desactivada. Contacta al administrador", null, 403);
    }
    
    // Verificar contraseña
    if (!password_verify($password, $user['contrasena'])) {
        sendJsonResponse(false, "Credenciales incorrectas", null, 401);
    }
    
    // Crear sesión
    session_start();
    $session_id = bin2hex(random_bytes(32));
    $expiration = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Guardar sesión en base de datos
    try {
        $query = "INSERT INTO sesiones (idSesion, idUsuario, fechaExpiracion) 
                  VALUES (:session_id, :user_id, :expiration)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':session_id', $session_id);
        $stmt->bindParam(':user_id', $user['idUsuario']);
        $stmt->bindParam(':expiration', $expiration);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Session creation failed: " . $e->getMessage());
        // Continuar sin sesión en BD si falla
    }
    
    // Configurar variables de sesión
    $_SESSION['user_id'] = $user['idUsuario'];
    $_SESSION['session_id'] = $session_id;
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['rol'];
    
    // Crear notificación de bienvenida
    try {
        $query = "INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo) 
                  VALUES (:user_id, :titulo, :mensaje, 'success')";
        
        $stmt = $db->prepare($query);
        $titulo = "Bienvenido de vuelta";
        $mensaje = "Has iniciado sesión exitosamente en Blue Pharmacy";
        $stmt->bindParam(':user_id', $user['idUsuario']);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':mensaje', $mensaje);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Notification creation failed: " . $e->getMessage());
        // No fallar el login si no se puede crear la notificación
    }
    
    sendJsonResponse(true, "Inicio de sesión exitoso", [
        'user' => [
            'id' => $user['idUsuario'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'rol' => $user['rol']
        ],
        'redirect' => 'index.php'
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor: " . $e->getMessage(), null, 500);
}
?>
