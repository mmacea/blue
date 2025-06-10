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
    
    $product_id = isset($input['productId']) ? (int)$input['productId'] : 0;
    $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
    $address_id = isset($input['addressId']) ? (int)$input['addressId'] : 0;
    $payment_method_id = isset($input['paymentMethodId']) ? (int)$input['paymentMethodId'] : 0;
    $user_id = $user['id'];
    
    if (!$product_id || !$quantity || !$address_id || !$payment_method_id) {
        sendJsonResponse(false, "Datos incompletos para procesar la compra", null, 400);
    }
    
    if ($quantity <= 0) {
        sendJsonResponse(false, "La cantidad debe ser mayor a 0", null, 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Verificar que el producto existe y tiene stock suficiente
        $query = "SELECT nombre, precio, stock FROM producto WHERE idProducto = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            sendJsonResponse(false, "Producto no encontrado", null, 404);
        }
        
        $product = $stmt->fetch();
        
        if ($quantity > $product['stock']) {
            sendJsonResponse(false, "Stock insuficiente. Disponible: " . $product['stock'], null, 400);
        }
        
        // Verificar que la dirección pertenece al usuario
        $query = "SELECT idDireccion FROM direccionentrega WHERE idDireccion = :address_id AND idUsuario = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':address_id', $address_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            sendJsonResponse(false, "Dirección no válida", null, 400);
        }
        
        // Verificar que el método de pago existe
        $query = "SELECT tipo FROM metodopago WHERE idMetodoPago = :payment_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':payment_id', $payment_method_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            sendJsonResponse(false, "Método de pago no válido", null, 400);
        }
        
        $payment_method = $stmt->fetch();
        
        // Calcular total
        $subtotal = $product['precio'] * $quantity;
        $shipping = 2500;
        $total = $subtotal + $shipping;
        
        // Obtener estado "Pendiente"
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
        
        // Crear pedido
        $query = "INSERT INTO pedidos (idUsuario, idEstado, idDireccion, idMetodoPago, montoTotal) 
                  VALUES (:user_id, :estado_id, :address_id, :payment_method_id, :total)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':estado_id', $estado_id);
        $stmt->bindParam(':address_id', $address_id);
        $stmt->bindParam(':payment_method_id', $payment_method_id);
        $stmt->bindParam(':total', $total);
        $stmt->execute();
        
        $order_id = $db->lastInsertId();
        
        // Agregar producto al pedido
        $query = "INSERT INTO pedidoproducto (idPedido, idProducto, cantidad, precioUnitario) 
                  VALUES (:order_id, :product_id, :quantity, :price)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':price', $product['precio']);
        $stmt->execute();
        
        // Actualizar stock
        $query = "UPDATE producto SET stock = stock - :quantity WHERE idProducto = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        // Obtener datos para respuesta
        $query = "SELECT d.direccionCompleta FROM direccionentrega d WHERE d.idDireccion = :address_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':address_id', $address_id);
        $stmt->execute();
        $address_details = $stmt->fetch();
        
        // Crear notificación
        $query = "INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo) 
                  VALUES (:user_id, :titulo, :mensaje, 'success')";
        
        $stmt = $db->prepare($query);
        $titulo = "Compra rápida exitosa";
        $mensaje = "Tu pedido #{$order_id} de {$product['nombre']} ha sido creado y está siendo procesado";
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':mensaje', $mensaje);
        $stmt->execute();
        
        $db->commit();
        
        sendJsonResponse(true, "Compra realizada exitosamente", [
            'orderId' => $order_id,
            'productName' => $product['nombre'],
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total,
            'address' => $address_details['direccionCompleta'],
            'paymentMethod' => $payment_method['tipo']
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Create quick order error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
