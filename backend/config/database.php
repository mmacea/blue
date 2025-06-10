<?php
// Habilitar logging de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Cambiar a 1 temporalmente para ver errores
ini_set('log_errors', 1);

class Database {
    private $host = "localhost";
    private $port = "3306"; // Puerto estándar de MySQL (cambiado de 3307)
    private $db_name = "blue";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Primero intentar conexión sin puerto específico
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
            
            // Test the connection
            $this->conn->query("SELECT 1");
            
        } catch(PDOException $exception) {
            // Si falla, intentar con puerto 3307
            try {
                $dsn = "mysql:host=" . $this->host . ";port=3307;dbname=" . $this->db_name . ";charset=utf8mb4";
                
                $this->conn = new PDO(
                    $dsn,
                    $this->username,
                    $this->password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                        PDO::ATTR_EMULATE_PREPARES => false
                    )
                );
                
                $this->conn->query("SELECT 1");
                
            } catch(PDOException $exception2) {
                error_log("Connection error: " . $exception2->getMessage());
                throw new Exception("Error de conexión a la base de datos. Verifica que MySQL esté ejecutándose y que la base de datos 'blue' exista.");
            }
        }

        return $this->conn;
    }
}

// Función helper para respuestas JSON
function sendJsonResponse($success, $message = "", $data = null, $httpCode = 200) {
    // Limpiar cualquier salida anterior
    if (ob_get_length()) ob_clean();
    
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Función para validar sesión
function validateSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
        return false;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si la tabla sesiones existe
        $query = "SHOW TABLES LIKE 'sesiones'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $sesiones_exists = $stmt->fetch();
        
        if ($sesiones_exists) {
            $query = "SELECT s.*, u.idUsuario, u.nombre, u.email, u.telefono, u.rol, u.estado 
                      FROM sesiones s 
                      INNER JOIN usuarios u ON s.idUsuario = u.idUsuario 
                      WHERE s.idSesion = :session_id 
                      AND s.idUsuario = :user_id 
                      AND s.activa = 1 
                      AND s.fechaExpiracion > NOW()
                      AND u.estado = 'activo'";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':session_id', $_SESSION['session_id']);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                return [
                    'id' => $user['idUsuario'] ?? 0,
                    'name' => $user['nombre'] ?? 'Usuario',
                    'email' => $user['email'] ?? '',
                    'phone' => $user['telefono'] ?? '',
                    'role' => $user['rol'] ?? 'usuario'
                ];
            }
        } else {
            // Fallback: validar directamente con usuarios si no existe tabla sesiones
            $query = "SELECT idUsuario, nombre, email, telefono, rol 
                      FROM usuarios 
                      WHERE idUsuario = :user_id AND estado = 'activo'";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                return [
                    'id' => $user['idUsuario'],
                    'name' => $user['nombre'] ?? 'Usuario',
                    'email' => $user['email'] ?? '',
                    'phone' => $user['telefono'] ?? '',
                    'role' => $user['rol'] ?? 'usuario'
                ];
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }
}

// Configuración de CORS mejorada
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}
?>
