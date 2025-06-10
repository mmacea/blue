<?php
require_once '../config/database.php';

// Habilitar logging de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, "Método no permitido", null, 405);
}

try {
    $user = validateSession();
    if (!$user) {
        sendJsonResponse(false, "No autenticado", null, 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, "Datos JSON inválidos", null, 400);
    }
    
    $address_id = isset($input['addressId']) ? (int)$input['addressId'] : 0;
    $payment_method_id = isset($input['paymentMethodId']) ? (int)$input['paymentMethodId'] : 0;
    $notes = isset($input['notes']) ? trim($input['notes']) : '';
    $user_id = $user['id'];
    
    if (!$address_id || !$payment_method_id) {
        sendJsonResponse(false, "Dirección y método de pago son requeridos", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que la dirección pertenece al usuario
    $query = "SELECT idDireccion, direccionCompleta FROM direccionentrega WHERE idDireccion = :address_id AND idUsuario = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':address_id', $address_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $address_data = $stmt->fetch();
    
    if (!$address_data) {
        sendJsonResponse(false, "Dirección no válida", null, 400);
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Obtener items del carrito
        $query = "SELECT c.idProducto, c.cantidad, p.nombre, p.precio, p.stock
                  FROM carrito c
                  INNER JOIN producto p ON c.idProducto = p.idProducto
                  WHERE c.idUsuario = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cart_items)) {
            $db->rollback();
            sendJsonResponse(false, "El carrito está vacío", null, 400);
        }
        
        // Verificar stock y calcular total
        $total = 0;
        foreach ($cart_items as $item) {
            if ($item['cantidad'] > $item['stock']) {
                $db->rollback();
                sendJsonResponse(false, "Stock insuficiente para " . $item['nombre'], null, 400);
            }
            $total += $item['precio'] * $item['cantidad'];
        }
        
        // Verificar si existe la tabla estadopedido y el estado 'Pendiente'
        $query = "SHOW TABLES LIKE 'estadopedido'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $table_exists = $stmt->fetch();
        
        $estado_id = 1; // Default estado ID
        
        if ($table_exists) {
            $query = "SELECT idEstado FROM estadopedido WHERE estado = 'Pendiente' LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $estado = $stmt->fetch();
            
            if (!$estado) {
                // Crear estado si no existe
                $query = "INSERT INTO estadopedido (estado) VALUES ('Pendiente')";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $estado_id = $db->lastInsertId();
            } else {
                $estado_id = $estado['idEstado'];
            }
        }
        
        // Verificar estructura de la tabla pedidos
        $query = "DESCRIBE pedidos";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Crear pedido con campos que existen
        // NOTA: Eliminamos 'fechaPedido' ya que no existe en la tabla
        if (in_array('idDireccion', $columns) && in_array('idMetodoPago', $columns)) {
            $query = "INSERT INTO pedidos (idUsuario, idEstado, idDireccion, idMetodoPago, montoTotal) 
                      VALUES (:user_id, :estado_id, :address_id, :payment_method_id, :total)";
        } else {
            // Fallback si las columnas no existen
            $query = "INSERT INTO pedidos (idUsuario, idEstado, montoTotal) 
                      VALUES (:user_id, :estado_id, :total)";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':estado_id', $estado_id, PDO::PARAM_INT);
        $stmt->bindParam(':total', $total);
        
        if (in_array('idDireccion', $columns) && in_array('idMetodoPago', $columns)) {
            $stmt->bindParam(':address_id', $address_id, PDO::PARAM_INT);
            $stmt->bindParam(':payment_method_id', $payment_method_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $order_id = $db->lastInsertId();
        
        // Verificar estructura de la tabla pedidoproducto
        $query = "SHOW TABLES LIKE 'pedidoproducto'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $pedidoproducto_exists = $stmt->fetch();
        
        if (!$pedidoproducto_exists) {
            // Crear tabla si no existe
            $query = "CREATE TABLE pedidoproducto (
                idPedidoProducto INT AUTO_INCREMENT PRIMARY KEY,
                idPedido INT NOT NULL,
                idProducto INT NOT NULL,
                cantidad INT NOT NULL,
                precioUnitario DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (idPedido) REFERENCES pedidos(idPedido),
                FOREIGN KEY (idProducto) REFERENCES producto(idProducto)
            )";
            $stmt = $db->prepare($query);
            $stmt->execute();
        }
        
        // Agregar productos al pedido y actualizar stock
        foreach ($cart_items as $item) {
            // Insertar en pedidoproducto
            $query = "INSERT INTO pedidoproducto (idPedido, idProducto, cantidad, precioUnitario) 
                      VALUES (:order_id, :product_id, :quantity, :price)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['idProducto'], PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $item['cantidad'], PDO::PARAM_INT);
            $stmt->bindParam(':price', $item['precio']);
            $stmt->execute();
            
            // Actualizar stock
            $query = "UPDATE producto SET stock = stock - :quantity WHERE idProducto = :product_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':quantity', $item['cantidad'], PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['idProducto'], PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Limpiar carrito
        $query = "DELETE FROM carrito WHERE idUsuario = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Obtener método de pago
        $payment_method = 'Contraentrega'; // Default
        $query = "SHOW TABLES LIKE 'metodopago'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $metodopago_exists = $stmt->fetch();
        
        if ($metodopago_exists) {
            $query = "SELECT tipo FROM metodopago WHERE idMetodoPago = :payment_method_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':payment_method_id', $payment_method_id, PDO::PARAM_INT);
            $stmt->execute();
            $payment_data = $stmt->fetch();
            
            if ($payment_data) {
                $payment_method = $payment_data['tipo'];
            }
        }
        
        // Crear notificación si la tabla existe
        $query = "SHOW TABLES LIKE 'notificaciones'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $notificaciones_exists = $stmt->fetch();
        
        if ($notificaciones_exists) {
            $query = "INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo) 
                      VALUES (:user_id, :titulo, :mensaje, 'success')";
            
            $stmt = $db->prepare($query);
            $titulo = "Pedido creado exitosamente";
            $mensaje = "Tu pedido #{$order_id} ha sido creado y está siendo procesado";
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':mensaje', $mensaje);
            $stmt->execute();
        }

        // Actualizar historial de pedidos si el campo existe
        $query = "DESCRIBE usuarios";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $user_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('historialPedidos', $user_columns)) {
            $historialData = [
                'idPedido' => $order_id,
                'fecha' => date('Y-m-d H:i:s'),
                'montoTotal' => $total,
                'estado' => 'Pendiente',
                'direccion' => $address_data['direccionCompleta'],
                'metodoPago' => $payment_method,
                'productos' => array_map(function($item) {
                    return [
                        'nombre' => $item['nombre'],
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                        'subtotal' => $item['precio'] * $item['cantidad']
                    ];
                }, $cart_items)
            ];

            // Obtener historial actual del usuario
            $query = "SELECT historialPedidos FROM usuarios WHERE idUsuario = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $currentHistory = $stmt->fetch();

            $historialArray = [];
            if (!empty($currentHistory['historialPedidos'])) {
                $historialArray = json_decode($currentHistory['historialPedidos'], true) ?: [];
            }

            // Agregar nuevo pedido al historial
            array_unshift($historialArray, $historialData);

            // Limitar a los últimos 50 pedidos
            if (count($historialArray) > 50) {
                $historialArray = array_slice($historialArray, 0, 50);
            }

            // Actualizar el campo historialPedidos
            $query = "UPDATE usuarios SET historialPedidos = :historial WHERE idUsuario = :user_id";
            $stmt = $db->prepare($query);
            $historialJson = json_encode($historialArray, JSON_UNESCAPED_UNICODE);
            $stmt->bindParam(':historial', $historialJson);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        $db->commit();
        
        sendJsonResponse(true, "Pedido creado exitosamente", [
            'orderId' => $order_id,
            'total' => $total,
            'address' => $address_data['direccionCompleta'],
            'paymentMethod' => $payment_method,
            'items' => array_map(function($item) {
                return [
                    'name' => $item['nombre'],
                    'quantity' => $item['cantidad'],
                    'subtotal' => $item['precio'] * $item['cantidad']
                ];
            }, $cart_items)
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Transaction error: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Create order error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    sendJsonResponse(false, "Error del servidor: " . $e->getMessage(), null, 500);
}
?>
