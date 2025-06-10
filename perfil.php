<?php
// Suprimir errores en producción
error_reporting(0); // Consider changing to E_ALL for development/debugging
ini_set('display_errors', 0); // Consider changing to 1 for development/debugging
require_once 'backend/config/database.php';

// Verificar autenticación
$user = validateSession(); //
if (!$user) {
  header('Location: login.php');
  exit;
}

// Inicializar variables con valores por defecto para evitar errores si hay excepciones tempranas
$userProfile = [
    'idUsuario' => $user['id'] ?? 0,
    'nombre' => $user['name'] ?? 'Usuario',
    'email' => $user['email'] ?? '',
    'telefono' => $user['phone'] ?? '',
    'rol' => $user['role'] ?? 'usuario',
    'estado' => 'activo',
    'fechaRegistro' => null
];
$addresses = [];
$favorites = []; // Initialized as empty
$notifications = [];
$purchases = [];

// Obtener datos del usuario
try {
  $database = new Database(); // Ensure your Database class sets PDO::ATTR_ERRMODE to PDO::ERRMODE_EXCEPTION
  $db = $database->getConnection(); //
  
  // Obtener información completa del usuario
  $query_user = "SELECT idUsuario, nombre, email, telefono, rol, estado, fechaRegistro FROM usuarios WHERE idUsuario = :user_id"; //
  $stmt_user = $db->prepare($query_user);
  $stmt_user->bindParam(':user_id', $user['id']);
  $stmt_user->execute(); //
  
  if ($stmt_user->rowCount() > 0) {
      $dbUserProfile = $stmt_user->fetch(PDO::FETCH_ASSOC);
      $userProfile = [
          'idUsuario' => $dbUserProfile['idUsuario'],
          'nombre'    => $dbUserProfile['nombre'] ?? $user['name'] ?? 'Usuario',
          'email'     => $dbUserProfile['email'] ?? $user['email'] ?? '',
          'telefono'  => $dbUserProfile['telefono'] ?? $user['phone'] ?? '', 
          'rol'       => $dbUserProfile['rol'] ?? $user['role'] ?? 'usuario',
          'estado'    => $dbUserProfile['estado'] ?? 'activo',
          'fechaRegistro' => $dbUserProfile['fechaRegistro'] ?? null
      ];
      error_log("User Profile Data (from DB merged with session fallback): " . print_r($userProfile, true));
  } else {
      error_log("CRITICAL: User with ID " . ($user['id'] ?? 'UNKNOWN') . " not found in 'usuarios' table, despite valid session. Using session data as primary source for profile.");
  }
  

  
  // Obtener favoritos
  $query_fav = "SELECT f.idFavorito, f.idUsuario, f.idProducto, f.fechaAgregado, 
                       p.nombre, p.precio, p.descripcion 
                FROM favoritos f 
                JOIN producto p ON f.idProducto = p.idProducto 
                WHERE f.idUsuario = :user_id 
                ORDER BY f.fechaAgregado DESC"; 
  $stmt_fav = $db->prepare($query_fav);
  $stmt_fav->bindParam(':user_id', $user['id']);
  $stmt_fav->execute();
  $favorites = $stmt_fav->fetchAll(PDO::FETCH_ASSOC); 
  // For debugging the "no favorites shown" issue:
  // error_log("Favorites Query User ID: " . $user['id']);
  // error_log("Favorites Fetched Count: " . count($favorites));
  // error_log("Favorites Data: " . print_r($favorites, true));
  
  // Obtener notificaciones
  $query_notif = "SELECT * FROM notificaciones WHERE idUsuario = :user_id ORDER BY fecha DESC LIMIT 10"; //
  $stmt_notif = $db->prepare($query_notif);
  $stmt_notif->bindParam(':user_id', $user['id']);
  $stmt_notif->execute();
  $notifications = $stmt_notif->fetchAll(PDO::FETCH_ASSOC); //
  
  // Obtener historial de compras
  $query_purch = "SELECT p.idPedido, p.fecha, p.montoTotal, ep.estado 
            FROM pedidos p 
            JOIN estadopedido ep ON p.idEstado = ep.idEstado 
            WHERE p.idUsuario = :user_id 
            ORDER BY p.fecha DESC LIMIT 10"; // Se ha corregido para seleccionar campos específicos y evitar ambigüedad.
  $stmt_purch = $db->prepare($query_purch);
  $stmt_purch->bindParam(':user_id', $user['id']);
  $stmt_purch->execute();
  $purchases = $stmt_purch->fetchAll(PDO::FETCH_ASSOC); //
  
} catch (Exception $e) {
  error_log("CRITICAL PROFILE DATA FETCH ERROR in perfil.php: " . $e->getMessage() . " - Stack Trace: " . $e->getTraceAsString());
  // $favorites will remain its initial empty array if an error occurs before it's populated.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Perfil - Blue</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="img/header/betterfaviconblue1.png"/>
  
  <style>
      /* ... (CSS sin cambios) ... */
      * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
      }

      body {
          font-family: "Poppins", sans-serif;
          background-color: #f8f9fa;
          color: #333;
          line-height: 1.6;
      }

      /* Header Styles */
      .HeaderSite {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 0 40px;
          height: 106px;
          background-color: #ffffff;
          border-bottom: 1px solid #eee;
          box-sizing: border-box;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          z-index: 1000;
      }

      .Logo img {
          height: 80px;
          width: 260px;
          object-fit: contain;
      }

      .HeaderIcons {
          display: flex;
          align-items: center;
          gap: 25px;
      }

      .CircularButtons {
          width: 54px;
          height: 54px;
          background-color: #f0f0f0;
          border-radius: 50%;
          display: flex;
          justify-content: center;
          align-items: center;
          transition: background-color 0.2s ease;
      }

      .CircularButtons:hover {
          background-color: #e0e0e0;
      }

      .CircularButtons .Link {
          display: flex;
          justify-content: center;
          align-items: center;
      }

      .CircularButtons img {
          width: 28px;
          height: 28px;
          object-fit: contain;
      }

      /* Menu toggle button */
      .menu-toggle {
          display: none;
          background: none;
          border: none;
          padding: 10px;
          cursor: pointer;
          z-index: 1010;
      }

      .menu-toggle .bar {
          display: block;
          width: 24px;
          height: 2px;
          background-color: #333;
          margin: 5px 0;
          transition: all 0.3s ease;
      }

      /* Mobile menu overlay */
      .mobile-menu-overlay {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          z-index: 999;
          opacity: 0;
          transition: opacity 0.3s ease;
      }

      .mobile-menu-overlay.active {
          display: block;
          opacity: 1;
      }

      /* Responsive styles */
      @media (max-width: 768px) {
          .HeaderSite {
              padding: 0 20px;
              height: 70px;
          }

          .Logo img {
              height: 45px;
              width: auto;
          }

          .menu-toggle {
              display: block;
              order: 1;
          }

          .Nav {
              position: fixed;
              top: 0;
              right: -280px;
              left: auto;
              width: 280px;
              height: 100%;
              background-color: #ffffff;
              z-index: 1000;
              transition: right 0.3s ease;
              box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
              overflow-y: auto;
              padding-top: 0;
          }

          .Nav.active {
              right: 0;
          }

          .HeaderIcons {
              display: flex;
              flex-direction: column;
              gap: 0;
              padding: 0;
              margin-top: 0;
          }

          .CircularButtons {
              width: 100%;
              height: auto;
              background-color: transparent;
              border-radius: 0;
              border-bottom: 1px solid #eee;
          }

          .CircularButtons:last-child {
              border-bottom: none;
          }

          .CircularButtons .Link {
              display: flex;
              align-items: center;
              justify-content: flex-start;
              gap: 15px;
              padding: 15px 20px;
              border-radius: 0;
              width: 100%;
              background-color: transparent;
              transition: background-color 0.3s ease;
              text-decoration: none;
              box-sizing: border-box;
          }

          .CircularButtons .Link:hover {
              background-color: #f0f0f0;
          }

          .CircularButtons .Link:active {
              background-color: #e0e0e0;
          }

          .CircularButtons img {
              width: 22px;
              height: 22px;
              object-fit: contain;
          }

          .CircularButtons .Link::after {
              content: attr(title);
              font-size: 16px;
              font-weight: 500;
              color: #333;
              text-align: left;
              flex-grow: 1;
          }

          .menu-toggle.active .bar:nth-child(1) {
              transform: translateY(7px) rotate(45deg);
          }

          .menu-toggle.active .bar:nth-child(2) {
              opacity: 0;
          }

          .menu-toggle.active .bar:nth-child(3) {
              transform: translateY(-7px) rotate(-45deg);
          }

          .main-content {
              margin-top: 70px;
              padding: 20px 15px;
          }

          .profile-header {
              padding: 30px 20px;
          }

          .profile-avatar {
              width: 80px;
              height: 80px;
              font-size: 2rem;
          }

          .profile-name {
              font-size: 1.5rem;
          }

          .profile-nav {
              flex-direction: column;
              gap: 5px;
          }

          .nav-tab {
              min-width: auto;
          }

          .content-section {
              padding: 20px;
          }

          .profile-form {
              grid-template-columns: 1fr;
          }

          .favorites-grid {
              grid-template-columns: 1fr;
          }

          .purchase-header,
          .purchase-details {
              flex-direction: column;
              align-items: flex-start;
              gap: 10px;
          }

          .address-item {
              flex-direction: column;
              align-items: flex-start;
              gap: 15px;
          }
      }

      /* Main Content */
      .main-content {
          margin-top: 106px;
          padding: 40px 20px;
          max-width: 1200px;
          margin-left: auto;
          margin-right: auto;
      }

      .profile-header {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          padding: 40px;
          border-radius: 20px;
          margin-bottom: 30px;
          text-align: center;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      }

      .profile-avatar {
          width: 120px;
          height: 120px;
          border-radius: 50%;
          background-color: rgba(255, 255, 255, 0.2);
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 20px;
          font-size: 3rem;
          font-weight: bold;
      }

      .profile-name {
          font-size: 2rem;
          font-weight: 600;
          margin-bottom: 10px;
      }

      .profile-email {
          font-size: 1.1rem;
          opacity: 0.9;
      }

      /* Navigation Tabs */
      .profile-nav {
          display: flex;
          background: white;
          border-radius: 15px;
          padding: 10px;
          margin-bottom: 30px;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
          overflow-x: auto;
      }

      .nav-tab {
          flex: 1;
          padding: 15px 20px;
          text-align: center;
          border-radius: 10px;
          cursor: pointer;
          transition: all 0.3s ease;
          font-weight: 500;
          white-space: nowrap;
          min-width: 120px;
      }

      .nav-tab:hover {
          background-color: #f8f9fa;
      }

      .nav-tab.active {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
      }

      /* Content Sections */
      .content-section {
          display: none;
          background: white;
          border-radius: 15px;
          padding: 30px;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
      }

      .content-section.active {
          display: block;
      }

      .section-title {
          font-size: 1.5rem;
          font-weight: 600;
          margin-bottom: 25px;
          color: #2d3748;
      }

      /* Profile Form */
      .profile-form {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
          gap: 25px;
      }

      .form-group {
          display: flex;
          flex-direction: column;
      }

      .form-group label {
          font-weight: 500;
          margin-bottom: 8px;
          color: #4a5568;
      }

      .form-group input,
      .form-group textarea {
          padding: 12px 16px;
          border: 2px solid #e2e8f0;
          border-radius: 10px;
          font-size: 1rem;
          font-family: "Poppins", sans-serif;
          transition: border-color 0.3s ease;
      }

      .form-group input:focus,
      .form-group textarea:focus {
          outline: none;
          border-color: #667eea;
          box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      }

      /* Campo email readonly */
      .form-group input[readonly] {
          background-color: #f8f9fa;
          cursor: not-allowed;
          opacity: 0.7;
          color: #6c757d;
      }

      .btn-primary {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          border: none;
          padding: 12px 24px;
          border-radius: 10px;
          font-size: 1rem;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
          font-family: "Poppins", sans-serif;
      }

      .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
      }

      .btn-secondary {
          background: #e2e8f0;
          color: #4a5568;
          border: none;
          padding: 12px 24px;
          border-radius: 10px;
          font-size: 1rem;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
          font-family: "Poppins", sans-serif;
      }

      .btn-secondary:hover {
          background: #cbd5e0;
      }

      .btn-danger {
          background: #e53e3e;
          color: white;
          border: none;
          padding: 12px 24px;
          border-radius: 10px;
          font-size: 1rem;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
          font-family: "Poppins", sans-serif;
      }

      .btn-danger:hover {
          background: #c53030;
          transform: translateY(-1px);
      }

      /* Cards */
      .card {
          background: white;
          border-radius: 12px;
          padding: 20px;
          margin-bottom: 20px;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
          transition: transform 0.3s ease, box-shadow 0.3s ease;
      }

      .card:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
      }

      .card-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 15px;
          padding-bottom: 15px;
          border-bottom: 1px solid #e2e8f0;
      }

      .card-title {
          font-size: 1.2rem;
          font-weight: 600;
          color: #2d3748;
      }

      .card-content {
          color: #4a5568;
          line-height: 1.6;
      }

      /* Favorites Grid */
      .favorites-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
          gap: 20px;
      }

      .favorite-item {
          background: white;
          border-radius: 12px;
          padding: 20px;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
          transition: transform 0.3s ease;
      }

      .favorite-item:hover {
          transform: translateY(-2px);
      }

      .favorite-image {
          width: 100%;
          height: 150px;
          background-color: #f7fafc;
          border-radius: 8px;
          margin-bottom: 15px;
          display: flex;
          align-items: center;
          justify-content: center;
      }

      .favorite-name {
          font-weight: 600;
          margin-bottom: 8px;
          color: #2d3748;
      }

      .favorite-price {
          font-size: 1.1rem;
          font-weight: 600;
          color: #667eea;
          margin-bottom: 15px;
      }

      .favorite-actions {
          display: flex;
          gap: 10px;
      }

      /* Notifications */
      .notification-item {
          display: flex;
          align-items: flex-start;
          padding: 15px;
          border-radius: 10px;
          margin-bottom: 15px;
          transition: background-color 0.3s ease;
      }

      .notification-item:hover {
          background-color: #f7fafc;
      }

      .notification-item.unread {
          background-color: #ebf8ff;
          border-left: 4px solid #667eea;
      }

      .notification-icon {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          margin-right: 15px;
          flex-shrink: 0;
      }

      .notification-icon.success {
          background-color: #f0fff4;
          color: #38a169;
      }

      .notification-icon.info {
          background-color: #ebf8ff;
          color: #667eea;
      }

      .notification-content {
          flex: 1;
      }

      .notification-title {
          font-weight: 600;
          margin-bottom: 5px;
          color: #2d3748;
      }

      .notification-message {
          color: #4a5568;
          font-size: 0.9rem;
          margin-bottom: 5px;
      }

      .notification-time {
          color: #a0aec0;
          font-size: 0.8rem;
      }

      /* Addresses */
      .address-item {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 20px;
          border: 2px solid #e2e8f0;
          border-radius: 12px;
          margin-bottom: 15px;
          transition: border-color 0.3s ease;
      }

      .address-item:hover {
          border-color: #667eea;
      }

      .address-content {
          flex: 1;
      }

      .address-text {
          font-weight: 500;
          color: #2d3748;
      }

      .address-actions {
          display: flex;
          gap: 10px;
      }

      /* Purchases */
      .purchase-item {
          border: 1px solid #e2e8f0;
          border-radius: 12px;
          padding: 20px;
          margin-bottom: 20px;
      }

      .purchase-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 15px;
          padding-bottom: 15px;
          border-bottom: 1px solid #e2e8f0;
      }

      .purchase-id {
          font-weight: 600;
          color: #2d3748;
      }

      .purchase-status {
          padding: 6px 12px;
          border-radius: 20px;
          font-size: 0.8rem;
          font-weight: 500;
      }

      .status-pendiente {
          background-color: #fef5e7;
          color: #d69e2e;
      }

      .status-procesando {
          background-color: #ebf8ff;
          color: #3182ce;
      }

      .status-enviado {
          background-color: #f0fff4;
          color: #38a169;
      }

      .status-entregado {
          background-color: #f0fff4;
          color: #38a169;
      }

      .status-cancelado {
          background-color: #fed7d7;
          color: #e53e3e;
      }

      .purchase-details {
          display: flex;
          justify-content: space-between;
          align-items: center;
      }

      .purchase-date {
          color: #4a5568;
          font-size: 0.9rem;
      }

      .purchase-total {
          font-size: 1.1rem;
          font-weight: 600;
          color: #2d3748;
      }

      /* Empty States */
      .empty-state {
          text-align: center;
          padding: 60px 20px;
          color: #a0aec0;
      }

      .empty-state svg {
          width: 80px;
          height: 80px;
          margin-bottom: 20px;
          opacity: 0.5;
      }

      .empty-state h3 {
          font-size: 1.2rem;
          margin-bottom: 10px;
          color: #4a5568;
      }

      .empty-state p {
          font-size: 0.9rem;
      }

      /* Modal */
      .modal {
          display: none;
          position: fixed;
          z-index: 1000;
          left: 0;
          top: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.6);
          backdrop-filter: blur(5px);
      }

      .modal-content {
          background-color: white;
          margin: 8% auto;
          padding: 40px;
          border-radius: 20px;
          width: 90%;
          max-width: 500px;
          box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
          animation: modalFadeIn 0.3s ease-out;
      }

      @keyframes modalFadeIn {
          from {
              opacity: 0;
              transform: translateY(-50px) scale(0.9);
          }
          to {
              opacity: 1;
              transform: translateY(0) scale(1);
          }
      }

      .modal-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 20px;
      }

      .modal-title {
          font-size: 1.5rem;
          font-weight: 600;
          color: #2d3748;
      }

      .modal-close {
          background: none;
          border: none;
          font-size: 1.5rem;
          cursor: pointer;
          color: #a0aec0;
          padding: 5px;
      }

      .modal-close:hover {
          color: #4a5568;
      }

      /* Actualizar los estilos de favoritos para que coincidan con las tarjetas de productos */
      .favorites-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
          gap: 20px;
      }

      .product-card.favorite-item {
          width: 100%;
          min-height: 340px;
          background-color: #d0d0d0;
          border-radius: 8px;
          padding: 7px;
          display: flex;
          flex-direction: column;
          transition: transform 0.3s ease, box-shadow 0.3s ease;
          cursor: pointer;
          box-sizing: border-box;
      }

      .product-card.favorite-item:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }

      .product-card.favorite-item .product-card-inner {
          width: 100%;
          min-height: 320px;
          background-color: #ffffff;
          border-radius: 6px;
          display: flex;
          flex-direction: column;
          overflow: hidden;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
          flex-grow: 1;
          transition: box-shadow 0.3s ease;
      }

      .product-card.favorite-item .product-image {
          width: calc(100% - 18px);
          height: 178px;
          background-color: #e8e8e8;
          margin: 9px auto 0;
          border-radius: 4px;
          position: relative;
          display: flex;
          align-items: center;
          justify-content: center;
          flex-shrink: 0;
      }

      .product-card.favorite-item .favorite-btn {
          position: absolute;
          top: 8px;
          right: 8px;
          width: 39px;
          height: 39px;
          background-color: #e74c3c;
          border: 1px solid #e74c3c;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          transition: all 0.2s ease;
          color: #fff;
      }

      .product-card.favorite-item .favorite-btn:hover {
          background-color: #c0392b;
          transform: scale(1.05);
      }

      .product-card.favorite-item .product-info {
          padding: 12px;
          display: flex;
          flex-direction: column;
          flex-grow: 1;
          justify-content: space-between;
          min-height: 120px;
      }

      .product-card.favorite-item .product-name {
          font-size: 0.9rem;
          font-weight: 500;
          color: #333;
          margin-bottom: 8px;
          overflow: hidden;
          text-overflow: ellipsis;
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          line-height: 1.3;
          flex-shrink: 0;
      }

      .product-card.favorite-item .product-price {
          font-size: 1rem;
          font-weight: 600;
          color: #1f202e;
          margin-bottom: 12px;
          display: flex;
          align-items: center;
          flex-shrink: 0;
      }

      .product-card.favorite-item .product-actions {
          display: flex;
          gap: 8px;
          margin-top: auto;
          padding-top: 8px;
          flex-shrink: 0;
      }

      .product-card.favorite-item .btn-buy-now,
      .product-card.favorite-item .btn-add-cart {
          width: 52px;
          height: 28px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.2s ease;
          font-size: 0.8rem;
          font-weight: 500;
      }

      .product-card.favorite-item .btn-buy-now {
          background-color: #a5d6a7;
          color: #fff;
      }

      .product-card.favorite-item .btn-buy-now:hover {
          background-color: #81c784;
          transform: translateY(-1px);
      }

      .product-card.favorite-item .btn-add-cart {
          background-color: #fff59d;
          color: #333;
      }

      .product-card.favorite-item .btn-add-cart:hover {
          background-color: #ffeb3b;
          transform: translateY(-1px);
      }
  </style>
</head>
<body>
  <header class="HeaderSite">
      <div class="Logo">
          <a href="index.php" title="Blue">
              <img src="img/header/betterblueLogoNoBackground.png" alt="Logo de Blue"/>
          </a>
      </div>
      <button class="menu-toggle" aria-label="Abrir menú" aria-expanded="false">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
      </button>
      <nav class="Nav">
          <div class="HeaderIcons">
              <div class="CircularButtons">
                  <a href="#" title="Buscar" class="Link search-trigger">
                      <img src="img/header/lupaBuscar.png" alt="Botón de Búsqueda"/>
                  </a>
              </div>
              <div class="CircularButtons">
                  <a href="#favorites" title="Destacados" class="Link nav-tab-trigger" data-section="favorites">
                      <img src="img/header/corazonGuardado.png" alt="Botón de Destacados"/>
                  </a>
              </div>
              <div class="CircularButtons">
                  <a href="carrito.php" title="Carrito de Compra" class="Link">
                      <img src="img/header/carritoDeCompra.png" alt="Botón de Carrito de Compra"/>
                  </a>
              </div>
              <div class="CircularButtons">
                  <a href="#notifications" title="Notificaciones" class="Link nav-tab-trigger" data-section="notifications">
                      <img src="img/header/campanaNotificacion.png" alt="Botón notificaciones"/>
                  </a>
              </div>
              <div class="CircularButtons">
                  <a href="perfil.php" title="<?= htmlspecialchars($userProfile['nombre']) ?>" class="Link">
                      <img src="img/header/usuarioPerfil.png" alt="Perfil de <?= htmlspecialchars($userProfile['nombre']) ?>"/>
                  </a>
              </div>
          </div>
      </nav>
      <div class="mobile-menu-overlay"></div>
  </header>

  <main class="main-content">
      <div class="profile-header">
          <div class="profile-avatar">
              <?= strtoupper(substr($userProfile['nombre'] ?? 'U', 0, 1)) ?>
          </div>
          <h1 class="profile-name"><?= htmlspecialchars($userProfile['nombre'] ?? 'Usuario') ?></h1>
          <p class="profile-email"><?= htmlspecialchars($userProfile['email'] ?? '') ?></p>
      </div>

      <nav class="profile-nav">
          <div class="nav-tab active" data-section="profile">Mi Información</div>
          <div class="nav-tab" data-section="addresses">Direcciones</div>
          <div class="nav-tab" data-section="favorites">Favoritos</div>
          <div class="nav-tab" data-section="purchases">Compras</div>
          <div class="nav-tab" data-section="notifications">Notificaciones</div>
          <div class="nav-tab" data-section="settings">Configuración</div>
      </nav>

      <section id="profile" class="content-section active">
          <h2 class="section-title">Mi Información Personal</h2>
          <form class="profile-form" id="profileForm">
              <div class="form-group">
                  <label for="nombre">Nombre completo</label>
                  <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($userProfile['nombre'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                  <label for="email">Correo electrónico</label>
                  <input type="email" id="email" name="email" value="<?= htmlspecialchars($userProfile['email'] ?? '') ?>" readonly>
              </div>
              <div class="form-group">
                  <label for="telefono">Teléfono</label>
                  <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($userProfile['telefono'] ?? '') ?>">
              </div>
              <div class="form-group" style="grid-column: 1 / -1;">
                  <button type="submit" class="btn-primary">Actualizar información</button>
              </div>
          </form>
      </section>

      <section id="addresses" class="content-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
              <h2 class="section-title" style="margin-bottom: 0;">Mis Direcciones</h2>
              <button class="btn-primary" onclick="openAddressModal()">Agregar dirección</button>
          </div>
          
          <div id="addressesList">
              <?php if (empty($addresses)): ?>
                  <div class="empty-state">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                          <circle cx="12" cy="10" r="3"></circle>
                      </svg>
                      <h3>No tienes direcciones guardadas</h3>
                      <p>Agrega una dirección para facilitar tus compras</p>
                  </div>
              <?php else: ?>
                  <?php foreach ($addresses as $address): ?>
                      <div class="address-item" data-address-id="<?= $address['idDireccion'] ?>">
                          <div class="address-content">
                              <div class="address-text"><?= htmlspecialchars($address['direccionCompleta']) ?></div>
                          </div>
                          <div class="address-actions">
                              <button class="btn-secondary" onclick="editAddress(<?= $address['idDireccion'] ?>, '<?= htmlspecialchars($address['direccionCompleta'], ENT_QUOTES) ?>')">Editar</button>
                              <button class="btn-danger" onclick="deleteAddress(<?= $address['idDireccion'] ?>)">Eliminar</button>
                          </div>
                      </div>
                  <?php endforeach; ?>
              <?php endif; ?>
          </div>
      </section>

      
      <section id="favorites" class="content-section">
          <h2 class="section-title">Mis Productos Favoritos</h2>
          
          <?php if (empty($favorites)): ?>
              <div class="empty-state">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                  </svg>
                  <h3>No tienes productos favoritos</h3>
                  <p>Explora nuestro catálogo y marca tus productos favoritos</p>
              </div>
          <?php else: ?>
              <div class="favorites-grid">
                  <?php foreach ($favorites as $favorite): ?>
                      <article class="product-card favorite-item" data-product-id="<?= $favorite['idProducto'] ?>">
                          <div class="product-card-inner">
                              <div class="product-image">
                                  <img src="img/main/placeholder-product.png" alt="<?= htmlspecialchars($favorite['nombre']) ?>">
                                  <button class="favorite-btn active" aria-label="Quitar de favoritos" data-product-id="<?= $favorite['idProducto'] ?>" onclick="removeFavorite(<?= $favorite['idProducto'] ?>, this)">
                                      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"> <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                  </button>
                              </div>
                              <div class="product-info">
                                  <h3 class="product-name"><?= htmlspecialchars($favorite['nombre']) ?></h3>
                                  <p class="product-price">$<?= number_format($favorite['precio'], 0, ',', '.') ?></p>
                                  <div class="product-actions">
                                      <button class="btn-buy-now" aria-label="Comprar ahora" onclick="buyNow(<?= $favorite['idProducto'] ?>)">
                                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                              <circle cx="9" cy="21" r="1"></circle>
                                              <circle cx="20" cy="21" r="1"></circle>
                                              <path d="m1 1 4 4 2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                          </svg>
                                      </button>
                                      <button class="btn-add-cart" aria-label="Añadir al carrito" onclick="addToCart(<?= $favorite['idProducto'] ?>)">
                                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                              <path d="M12 5v14M5 12h14"></path>
                                          </svg>
                                      </button>
                                  </div>
                              </div>
                          </div>
                      </article>
                  <?php endforeach; ?>
              </div>
          <?php endif; ?>
      </section>

      <section id="purchases" class="content-section">
          <h2 class="section-title">Historial de Compras</h2>
          
          <?php if (empty($purchases)): ?>
              <div class="empty-state">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="9" cy="21" r="1"></circle>
                      <circle cx="20" cy="21" r="1"></circle>
                      <path d="m1 1 4 4 2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                  </svg>
                  <h3>No tienes compras registradas</h3>
                  <p>Cuando realices tu primera compra aparecerá aquí</p>
              </div>
          <?php else: ?>
              <?php foreach ($purchases as $purchase): ?>
                  <div class="purchase-item">
                      <div class="purchase-header">
                          <div class="purchase-id">Pedido #<?= htmlspecialchars($purchase['idPedido']) ?></div>
                          <div class="purchase-status status-<?= strtolower(htmlspecialchars($purchase['estado'])) // ?>">
                              <?= htmlspecialchars($purchase['estado']) // ?>
                          </div>
                      </div>
                      <div class="purchase-details">
                          <div class="purchase-date"><?= date('d/m/Y H:i', strtotime($purchase['fecha'])) // ?></div>
                          <div class="purchase-total">$<?= number_format($purchase['montoTotal'], 0, ',', '.') // ?></div>
                      </div>
                  </div>
              <?php endforeach; ?>
          <?php endif; ?>
      </section>

      <section id="notifications" class="content-section">
          <h2 class="section-title">Notificaciones</h2>
          
          <?php if (empty($notifications)): ?>
              <div class="empty-state">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                      <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                  </svg>
                  <h3>No tienes notificaciones</h3>
                  <p>Te notificaremos sobre el estado de tus pedidos y ofertas especiales</p>
              </div>
          <?php else: ?>
              <?php foreach ($notifications as $notification): ?>
                  <div class="notification-item <?= $notification['leida'] ? '' : 'unread' // ?>" data-notification-id="<?= $notification['idNotificacion'] // ?>" onclick="markNotificationAsRead(<?= $notification['idNotificacion'] ?>, this)">
                      <div class="notification-icon <?= htmlspecialchars($notification['tipo']) // ?>">
                          <?php if ($notification['tipo'] === 'success'): ?>
                              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                  <polyline points="22,4 12,14.01 9,11.01"></polyline>
                              </svg>
                          <?php else: // Default to info icon ?>
                              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <circle cx="12" cy="12" r="10"></circle>
                                  <line x1="12" y1="16" x2="12" y2="12"></line>
                                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                              </svg>
                          <?php endif; ?>
                      </div>
                      <div class="notification-content">
                          <div class="notification-title"><?= htmlspecialchars($notification['titulo']) // ?></div>
                          <div class="notification-message"><?= htmlspecialchars($notification['mensaje']) // ?></div>
                          <div class="notification-time"><?= date('d/m/Y H:i', strtotime($notification['fecha'])) // ?></div>
                      </div>
                  </div>
              <?php endforeach; ?>
          <?php endif; ?>
      </section>

      <section id="settings" class="content-section">
          <h2 class="section-title">Configuración de la Cuenta</h2>
          
          <div class="card">
              <div class="card-header">
                  <div class="card-title">Cambiar Contraseña</div>
              </div>
              <form id="passwordForm">
                  <div class="form-group">
                      <label for="current_password">Contraseña actual</label>
                      <input type="password" id="current_password" name="current_password" required>
                  </div>
                  <div class="form-group">
                      <label for="new_password">Nueva contraseña</label>
                      <input type="password" id="new_password" name="new_password" required>
                  </div>
                  <div class="form-group">
                      <label for="confirm_new_password">Confirmar nueva contraseña</label>
                      <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                  </div>
                  <button type="submit" class="btn-primary">Cambiar contraseña</button>
              </form>
          </div>

          <div class="card">
              <div class="card-header">
                  <div class="card-title">Cerrar Sesión</div>
              </div>
              <div class="card-content">
                  <p>Cierra tu sesión en este dispositivo de forma segura.</p>
                  <button class="btn-secondary" onclick="logout()">Cerrar sesión</button>
              </div>
          </div>

          <div class="card">
              <div class="card-header">
                  <div class="card-title">Eliminar Cuenta</div>
              </div>
              <div class="card-content">
                  <p style="color: #e53e3e; margin-bottom: 15px;">Esta acción no se puede deshacer. Se eliminarán todos tus datos permanentemente.</p>
                  <button class="btn-danger" onclick="confirmDeleteAccount()">Eliminar cuenta</button>
              </div>
          </div>
      </section>
  </main>

  <div class="modal" id="addressModal">
      <div class="modal-content">
          <div class="modal-header">
              <h3 class="modal-title" id="addressModalTitle">Agregar Dirección</h3>
              <button class="modal-close" onclick="closeModal('addressModal')">&times;</button>
          </div>
          <form id="addressForm">
              <input type="hidden" id="addressId" name="addressId">
              <div class="form-group">
                  <label for="addressText">Dirección completa</label>
                  <textarea id="addressText" name="addressText" rows="3" placeholder="Ingresa tu dirección completa" required></textarea>
              </div>
              <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                  <button type="button" class="btn-secondary" onclick="closeModal('addressModal')">Cancelar</button>
                  <button type="submit" class="btn-primary">Guardar</button>
              </div>
          </form>
      </div>
  </div>

  <script src="js/global-search.js"></script>
  <script>
      document.addEventListener("DOMContentLoaded", () => {
          // Navigation tabs
          const navTabs = document.querySelectorAll(".nav-tab");
          const contentSections = document.querySelectorAll(".content-section");
          const headerNavLinks = document.querySelectorAll(".HeaderIcons .nav-tab-trigger");


          function activateTab(sectionName) {
              const targetTab = document.querySelector(`.nav-tab[data-section="${sectionName}"]`);
              const targetSection = document.getElementById(sectionName);

              if (targetTab && targetSection) {
                  navTabs.forEach((t) => t.classList.remove("active"));
                  targetTab.classList.add("active");

                  contentSections.forEach((s) => {
                      s.classList.remove("active");
                  });
                  targetSection.classList.add("active");
                  window.location.hash = sectionName;

                  if (sectionName === 'addresses' && document.getElementById('addressesList')) {
                loadAddresses();
                  }
              }
          }

          navTabs.forEach((tab) => {
              tab.addEventListener("click", () => {
                  const targetSectionName = tab.dataset.section;
                  activateTab(targetSectionName);
              });
          });
          
          headerNavLinks.forEach((link) => {
            link.addEventListener("click", (e) => {
                e.preventDefault(); // Prevenir el comportamiento por defecto del ancla
                const targetSectionName = link.dataset.section;
                activateTab(targetSectionName);
                
                // Si el menú móvil está activo, ciérralo
                const menuToggle = document.querySelector(".menu-toggle");
                if (menuToggle && menuToggle.classList.contains("active")) {
                    toggleMobileMenu(false);
                }
            });
        });

          // Handle URL hash on load
          const hash = window.location.hash.substring(1);
          if (hash) {
              activateTab(hash);
          } else {
              // Activar la primera pestaña por defecto si no hay hash
              const firstTab = document.querySelector(".nav-tab");
              if (firstTab) {
                  activateTab(firstTab.dataset.section);
              }
          }

          // Profile form submission
          document.getElementById("profileForm").addEventListener("submit", async (e) => { //
              e.preventDefault()
              
              const formData = new FormData(e.target)
              const data = Object.fromEntries(formData.entries())

              try {
                  const response = await fetch("backend/php/update-profile.php", { //
                      method: "POST",
                      headers: {
                          "Content-Type": "application/json",
                      },
                      body: JSON.stringify(data),
                  })

                  const result = await response.json()

                  if (result.success) {
                      showNotification("Perfil actualizado exitosamente", "success")
                      
                      // Update the profile header and form with new data if provided
                      if (result.data) { //
                          const profileNameHeader = document.querySelector('.profile-name')
                          const profileAvatar = document.querySelector('.profile-avatar')
                          const nombreInput = document.getElementById('nombre');
                          const telefonoInput = document.getElementById('telefono');
                          
                          if (profileNameHeader && result.data.nombre) {
                              profileNameHeader.textContent = result.data.nombre
                          }
                          if (profileAvatar && result.data.nombre) {
                              profileAvatar.textContent = result.data.nombre.charAt(0).toUpperCase()
                          }
                          if (nombreInput && result.data.nombre) {
                              nombreInput.value = result.data.nombre;
                          }
                          // Asegurarse de que result.data.telefono exista antes de asignarlo
                          if (telefonoInput && result.data.telefono !== undefined) {
                              telefonoInput.value = result.data.telefono;
                          }
                      }
                  } else {
                      throw new Error(result.message)
                  }
              } catch (error) {
                  showNotification(error.message || "Error al actualizar el perfil", "error")
              }
          });

          // Password form submission
          document.getElementById("passwordForm").addEventListener("submit", async (e) => { //
              e.preventDefault()
              
              const formData = new FormData(e.target)
              const data = Object.fromEntries(formData.entries())

              if (data.new_password !== data.confirm_new_password) {
                  showNotification("Las contraseñas no coinciden", "error")
                  return
              }

              try {
                  const response = await fetch("backend/php/change-password.php", { //
                      method: "POST",
                      headers: {
                          "Content-Type": "application/json",
                      },
                      body: JSON.stringify(data),
                  })

                  const result = await response.json()

                  if (result.success) {
                      showNotification("Contraseña cambiada exitosamente", "success")
                      e.target.reset()
                  } else {
                      throw new Error(result.message)
                  }
              } catch (error) {
                  showNotification(error.message || "Error al cambiar la contraseña", "error")
              }
          });

          // Address form submission
          document.getElementById("addressForm").addEventListener("submit", async (e) => { //
              e.preventDefault()
              
              const formData = new FormData(e.target)
              const data = Object.fromEntries(formData.entries())
              const isEdit = data.addressId !== ""

              try {
                  const endpoint = isEdit ? "backend/php/update_address.php" : "backend/php/add_address.php" //
                  const requestData = isEdit ? 
                      { idDireccion: data.addressId, direccion: data.addressText } : //
                      { direccionCompleta: data.addressText } //
                  
                  const response = await fetch(endpoint, {
                      method: "POST",
                      headers: {
                          "Content-Type": "application/json",
                      },
                      body: JSON.stringify(requestData),
                  })

                  const result = await response.json()

                  if (result.success) {
                      showNotification(isEdit ? "Dirección actualizada" : "Dirección agregada", "success")
                      closeModal("addressModal") //
                      
                      // Actualizar dinámicamente la lista de direcciones sin recargar toda la página
                      await loadAddresses(); 
                  } else {
                      throw new Error(result.message)
                  }
              } catch (error) {
                  showNotification(error.message || "Error al guardar la dirección", "error")
              }
          });
          // Cargar direcciones al inicio y definir la función para recargarla
          window.loadAddresses = async function() {
            try {
                // Usaremos el endpoint get_addresses.php que ya existe.
                // No necesita userId si la sesión es válida.
                const response = await fetch("backend/php/get_addresses.php"); //
                const result = await response.json();

                if (result.success && result.data && result.data.addresses) {
                    const addressesListDiv = document.getElementById('addressesList');
                    if (result.data.addresses.length === 0) {
                        addressesListDiv.innerHTML = `
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <h3>No tienes direcciones guardadas</h3>
                                <p>Agrega una dirección para facilitar tus compras</p>
                            </div>`;
                    } else {
                        addressesListDiv.innerHTML = result.data.addresses.map(address => `
                            <div class="address-item" data-address-id="${address.idDireccion}">
                                <div class="address-content">
                                    <div class="address-text">${escapeHTML(address.direccionCompleta)}</div>
                                </div>
                                <div class="address-actions">
                                    <button class="btn-secondary" onclick="editAddress(${address.idDireccion}, '${escapeHTML(address.direccionCompleta, true)}')">Editar</button>
                                    <button class="btn-danger" onclick="deleteAddress(${address.idDireccion})">Eliminar</button>
                                </div>
                            </div>
                        `).join('');
                    }
                } else if (!result.success && result.message === "No autenticado") {
                     // No hacer nada o redirigir, ya que la carga inicial de perfil.php debería manejar esto.
                }
                 else {
                   // console.error("Error al cargar direcciones:", result.message);
                   // Mantener el estado vacío por si acaso.
                    const addressesListDiv = document.getElementById('addressesList');
                    addressesListDiv.innerHTML = `
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <h3>Error al cargar las direcciones</h3>
                            <p>Intenta recargar la página.</p>
                        </div>`;
                }
            } catch (error) {
                // console.error("Excepción al cargar direcciones:", error);
                const addressesListDiv = document.getElementById('addressesList');
                addressesListDiv.innerHTML = `
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <h3>Error al cargar las direcciones</h3>
                        <p>Intenta recargar la página.</p>
                    </div>`;
            }
        };
        if (document.getElementById('addressesList')) { // Solo cargar si la sección de direcciones está presente.
             // loadAddresses(); // La carga inicial ya la hace el PHP. Se llamará después de add/delete.
        }


      }); // Fin DOMContentLoaded

      // Funciones de Utilidad y Manejadores de Eventos (Modal, Edit, Delete, etc.)
      function escapeHTML(str, forAttribute = false) {
          if (str === null || str === undefined) return '';
          let replaced = String(str)
              .replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;');
          if (forAttribute) {
              replaced = replaced.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
          }
          return replaced;
      }


      // Modal functions
      function openAddressModal(id = null, text = "") { //
          const modal = document.getElementById("addressModal")
          const title = document.getElementById("addressModalTitle")
          const form = document.getElementById("addressForm")
          
          if (id) {
              title.textContent = "Editar Dirección"
              document.getElementById("addressId").value = id
              document.getElementById("addressText").value = text
          } else {
              title.textContent = "Agregar Dirección"
              form.reset()
              document.getElementById("addressId").value = ""; // Asegurar que no haya ID al agregar
          }
          
          modal.style.display = "block"
      }

      function closeModal(modalId) { //
          document.getElementById(modalId).style.display = "none"
      }

      window.editAddress = function(id, text) { // Hacer global para onclick
          openAddressModal(id, text) //
      }

      window.deleteAddress = async function(id) { // Hacer global y async //
          if (!confirm("¿Estás seguro de que quieres eliminar esta dirección?")) {
              return
          }

          try {
              const response = await fetch("backend/php/delete_address.php", { //
                  method: "POST",
                  headers: {
                      "Content-Type": "application/json",
                  },
                  body: JSON.stringify({ idDireccion: id }), //
              })

              const result = await response.json()

              if (result.success) {
                  showNotification("Dirección eliminada", "success")
                  await loadAddresses(); // Recargar la lista de direcciones dinámicamente
              } else {
                  throw new Error(result.message)
              }
          } catch (error) {
              showNotification(error.message || "Error al eliminar la dirección", "error")
          }
      }

      window.buyNow = async function(productId) { // Hacer global y async
          console.log("Buy Now clicked for product ID:", productId);
    // Redirigir directamente a la página de compra rápida
    window.location.href = `quick-checkout.php?product=${productId}`;
}
      
      window.addToCart = async function(productId) { // Hacer global y async //
          try {
              const response = await fetch("backend/php/add-to-cart.php", { //
                  method: "POST",
                  headers: {
                      "Content-Type": "application/json",
                  },
                  body: JSON.stringify({ productId, quantity: 1 }), //
              })

              const result = await response.json()

              if (result.success) {
                  showNotification("Producto agregado al carrito", "success")
                  // Aquí podrías actualizar un contador del carrito si existe en la UI
              } else {
                  throw new Error(result.message)
              }
          } catch (error) {
              showNotification(error.message || "Error al agregar al carrito", "error")
          }
      }
      
      window.removeFavorite = async function(productId, buttonElement) {
    try {
        const response = await fetch("backend/php/toggle-favorite.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ productId }),
        });

        const result = await response.json();

        if (result.success) {
            // Eliminar la tarjeta de producto de la vista
            const productCard = buttonElement.closest('.favorite-item');
            if (productCard) {
                productCard.style.opacity = "0";
                productCard.style.transform = "scale(0.8)";
                setTimeout(() => {
                    productCard.remove();
                    
                    // Verificar si no quedan más favoritos
                    const remainingFavorites = document.querySelectorAll("#favorites .product-card");
                    if (remainingFavorites.length === 0) {
                        document.querySelector("#favorites").innerHTML = `
                            <h2 class="section-title">Mis Productos Favoritos</h2>
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                                <h3>No tienes productos favoritos</h3>
                                <p>Explora nuestro catálogo y marca tus productos favoritos</p>
                            </div>
                        `;
                    }
                }, 300);
            }
            
            showNotification("Producto removido de favoritos", "info");
        } else {
            throw new Error(result.message || "No se pudo actualizar la lista de favoritos.");
        }
    } catch (error) {
        showNotification(error.message || "Error al remover de favoritos", "error");
    }
}

      window.logout = async function() { // Hacer global y async //
          if (!confirm("¿Estás seguro de que quieres cerrar sesión?")) {
              return
          }

          try {
              const response = await fetch("backend/php/logout.php", { //
                  method: "POST",
              })

              const result = await response.json()

              if (result.success) {
                  window.location.href = "index.php"
              } else {
                  throw new Error(result.message)
              }
          } catch (error) {
              showNotification(error.message || "Error al cerrar sesión", "error")
          }
      }

      window.confirmDeleteAccount = async function() { // Hacer global y async //
          const confirmation = prompt("Para eliminar tu cuenta, escribe 'ELIMINAR' en mayúsculas:")
          
          if (confirmation !== "ELIMINAR") {
              showNotification("Eliminación cancelada", "info")
              return
          }

          try {
              const response = await fetch("backend/php/delete-account.php", { //
                  method: "POST",
              })

              const result = await response.json()

              if (result.success) {
                  alert("Tu cuenta ha sido eliminada. Serás redirigido al inicio.")
                  window.location.href = "index.php"
              } else {
                  throw new Error(result.message)
              }
          } catch (error) {
              showNotification(error.message || "Error al eliminar la cuenta", "error")
          }
      }
      
      window.markNotificationAsRead = async function(notificationId, element) {
        if (element.classList.contains('unread')) {
            try {
                const response = await fetch("backend/php/mark-notification-read.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ idNotificacion: notificationId })
                });
                const result = await response.json();
                if (result.success) {
                    element.classList.remove('unread');
                    // Podrías actualizar un contador de notificaciones no leídas aquí si lo tuvieras
                } else {
                    showNotification(result.message || "Error al marcar la notificación.", "error");
                }
            } catch (error) {
                showNotification("Error de conexión al marcar notificación.", "error");
            }
        }
        window.buyNow = async function(productId) {
          console.log("Buy Now clicked for product ID:", productId);
          
          // This is an example implementation. You'll need to define what "Buy Now" actually does.
          // For instance, add to cart and redirect to checkout.
          try {
              const response = await fetch("backend/php/add-to-cart.php", {
                  method: "POST",
                  headers: {
                      "Content-Type": "application/json",
                  },
                  body: JSON.stringify({ productId: productId, quantity: 1 }),
              });
              const result = await response.json();

              if (result.success) {
                  showNotification("Producto agregado al carrito. Redirigiendo...", "success");
                  // Optionally, update cart counter if you have one visible
                  // if (typeof updateCartCounter === 'function' && result.data && result.data.cart_count !== undefined) {
                  //     updateCartCounter(result.data.cart_count);
                  // }
                  setTimeout(() => {
                      window.location.href = 'carrito.php'; // Redirect to cart page
                  }, 1500);
              } else {
                  throw new Error(result.message || "No se pudo agregar el producto al carrito.");
              }
          } catch (error) {
              showNotification(error.message || "Error al procesar la compra inmediata.", "error");
          }
      };
    }


      // Notification system
      function showNotification(message, type = "info") { //
          // Create notification container if it doesn't exist
          let container = document.getElementById("notification-container")
          if (!container) {
              container = document.createElement("div")
              container.id = "notification-container"
              container.style.cssText = `
                  position: fixed;
                  top: 20px;
                  right: 20px;
                  z-index: 9999;
                  display: flex;
                  flex-direction: column;
                  gap: 10px;
                  max-width: 400px;
                  width: 100%;
                  pointer-events: none;
              `
              document.body.appendChild(container)
          }

          // Create notification element
          const notification = document.createElement("div")
          notification.style.cssText = `
              background-color: white;
              border-radius: 8px;
              box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
              padding: 16px;
              display: flex;
              align-items: center;
              gap: 12px;
              transform: translateX(120%);
              opacity: 0;
              transition: transform 0.3s ease, opacity 0.3s ease;
              pointer-events: auto;
              border-left: 4px solid ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
          `

          const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'
          notification.innerHTML = `
              <div style="color: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'}; font-weight: bold; font-size: 18px;">
                  ${icon}
              </div>
              <div style="flex-grow: 1; color: #333; font-size: 14px;">
                  ${escapeHTML(message)}
              </div>
              <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #999; cursor: pointer; font-size: 18px; padding: 0; width: 20px; height: 20px;">
                  ×
              </button>
          `

          container.appendChild(notification)

          // Show notification
          setTimeout(() => {
              notification.style.transform = "translateX(0)"
              notification.style.opacity = "1"
          }, 100)

          // Auto remove after 5 seconds
          setTimeout(() => {
              notification.style.transform = "translateX(120%)"
              notification.style.opacity = "0"
              setTimeout(() => {
                  if (notification.parentElement) {
                      notification.remove()
                  }
              }, 300)
          }, 5000)
      }
      // Responsive Header
      document.addEventListener("DOMContentLoaded", () => { //
          const header = document.querySelector(".HeaderSite");
          const nav = header ? header.querySelector(".Nav") : null;
          const menuToggle = header ? header.querySelector(".menu-toggle") : null;
          const overlay = header ? header.querySelector(".mobile-menu-overlay") : null;

          if (!header || !nav || !menuToggle || !overlay) {
              // console.warn("Header elements not found for mobile menu.")
              return;
          }
          
          function toggleMobileMenu(show) {
              menuToggle.setAttribute("aria-expanded", show.toString());
              if (show) {
                  menuToggle.classList.add("active");
                  nav.classList.add("active");
                  overlay.classList.add("active");
                  document.body.style.overflow = "hidden";
              } else {
                  menuToggle.classList.remove("active");
                  nav.classList.remove("active");
                  overlay.classList.remove("active");
                  document.body.style.overflow = "";
              }
          }


          menuToggle.addEventListener("click", () => {
              const isExpanded = menuToggle.getAttribute("aria-expanded") === "true" || false;
              toggleMobileMenu(!isExpanded);
          });

          overlay.addEventListener("click", () => {
              toggleMobileMenu(false);
          });

          const navLinks = nav.querySelectorAll(".Link"); // Se cambió a .Link para que coincida con los elementos clicables reales
          navLinks.forEach((link) => {
              // No cerrar el menú para todos los links, solo para acciones que navegan o cambian de vista principal.
              // Los triggers de tabs ya son manejados por su propia lógica.
              if (!link.classList.contains('nav-tab-trigger') && !link.classList.contains('search-trigger')) {
                 link.addEventListener("click", (e) => {
                    // Si es un enlace de navegación real (ej. carrito.php, perfil.php)
                    // y no una acción en la misma página (como abrir búsqueda o cambiar de tab)
                    if (link.getAttribute('href') && link.getAttribute('href') !== '#') {
                         toggleMobileMenu(false);
                    }
                 });
              }
          });

          window.addEventListener("resize", () => {
              if (window.innerWidth > 768 && nav.classList.contains("active")) {
                  toggleMobileMenu(false);
              }
          });
          // Asegurar que la variable toggleMobileMenu esté disponible globalmente si es necesaria
          window.toggleMobileMenu = toggleMobileMenu; 
      });
  </script>
</body>
</html>
