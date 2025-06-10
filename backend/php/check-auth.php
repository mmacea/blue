<?php
require_once '../config/database.php';

try {
    $user = validateSession();
    
    if ($user) {
        sendJsonResponse(true, "Usuario autenticado", [
            'authenticated' => true,
            'user' => $user
        ]);
    } else {
        sendJsonResponse(false, "No autenticado", [
            'authenticated' => false
        ], 401);
    }
} catch (Exception $e) {
    error_log("Check auth error: " . $e->getMessage());
    sendJsonResponse(false, "Error del servidor", null, 500);
}
?>
