<?php
require_once 'backend/config/database.php';

// Iniciar buffer de salida para evitar que advertencias o errores afecten la respuesta JSON
ob_start();

// Si ya está autenticado, redirigir al inicio
$user = validateSession();
if ($user) {
  header('Location: index.php');
  exit;
}

// Procesar login si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Limpiar cualquier salida anterior
  ob_clean();
  
  try {
      $input = json_decode(file_get_contents('php://input'), true);
      
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
      
      $query = "SELECT idUsuario, nombre, email, contrasena, rol, estado, email_verificado 
                FROM usuarios 
                WHERE email = :email";
      
      $stmt = $db->prepare($query);
      $stmt->bindParam(':email', $email);
      $stmt->execute();
      
      if ($stmt->rowCount() === 0) {
          sendJsonResponse(false, "Credenciales incorrectas", null, 401);
      }
      
      $user = $stmt->fetch();
      
      if ($user['estado'] !== 'activo') {
          sendJsonResponse(false, "Cuenta desactivada. Contacta al administrador", null, 403);
      }
      
      if (!password_verify($password, $user['contrasena'])) {
          sendJsonResponse(false, "Credenciales incorrectas", null, 401);
      }
      
      session_start();
      $session_id = bin2hex(random_bytes(32));
      $expiration = date('Y-m-d H:i:s', strtotime('+30 days'));
      
      $query = "INSERT INTO sesiones (idSesion, idUsuario, fechaExpiracion) 
                VALUES (:session_id, :user_id, :expiration)";
      
      $stmt = $db->prepare($query);
      $stmt->bindParam(':session_id', $session_id);
      $stmt->bindParam(':user_id', $user['idUsuario']);
      $stmt->bindParam(':expiration', $expiration);
      $stmt->execute();
      
      $_SESSION['user_id'] = $user['idUsuario'];
      $_SESSION['session_id'] = $session_id;
      $_SESSION['user_name'] = $user['nombre'];
      $_SESSION['user_email'] = $user['email'];
      $_SESSION['user_role'] = $user['rol'];
      
      $query = "INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo) 
                VALUES (:user_id, :titulo, :mensaje, 'success')";
      
      $stmt = $db->prepare($query);
      $titulo = "Bienvenido de vuelta";
      $mensaje = "Has iniciado sesión exitosamente en Blue Pharmacy";
      $stmt->bindParam(':user_id', $user['idUsuario']);
      $stmt->bindParam(':titulo', $titulo);
      $stmt->bindParam(':mensaje', $mensaje);
      $stmt->execute();
      
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
      sendJsonResponse(false, "Error del servidor", null, 500);
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicia sesión en Blue</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="img/header/betterfaviconblue1.png"/>
  
  <style>
      * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
      }

      body {
          font-family: "Poppins", sans-serif;
          background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 20px;
          color: #333;
      }

      .form-container {
          background: rgba(255, 255, 255, 0.98);
          backdrop-filter: blur(10px);
          border-radius: 24px;
          padding: 48px 40px;
          box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
          border: 1px solid rgba(255, 255, 255, 0.2);
          width: 100%;
          max-width: 420px;
          text-align: center;
          animation: fadeInUp 0.6s ease-out;
      }

      .logoLoMejor {
          margin-bottom: 32px;
      }

      .logoLoMejor img {
          width: 180px;
          height: auto;
          object-fit: contain;
      }

      .form-container h2 {
          color: #2d3748;
          font-size: 2rem;
          font-weight: 600;
          margin-bottom: 40px;
          letter-spacing: -0.5px;
      }

      .form-container form {
          display: flex;
          flex-direction: column;
          gap: 24px;
          margin-bottom: 32px;
      }

      .form-group {
          display: flex;
          flex-direction: column;
          text-align: left;
      }

      .form-group label {
          color: #2d3748;
          font-weight: 500;
          margin-bottom: 8px;
          font-size: 0.95rem;
      }

      .form-container input[type="email"],
      .form-container input[type="password"],
      .form-container input[type="text"] {
          width: 100%;
          padding: 16px 20px;
          border: 2px solid #e2e8f0;
          border-radius: 16px;
          font-size: 1rem;
          font-family: "Poppins", sans-serif;
          background-color: #f8f9fa;
          transition: all 0.3s ease;
          outline: none;
      }

      .form-container input[type="email"]:focus,
      .form-container input[type="password"]:focus,
      .form-container input[type="text"]:focus {
          border-color: #4299e1;
          background-color: #ffffff;
          box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
          transform: translateY(-1px);
      }

      .form-container input[type="email"]::placeholder,
      .form-container input[type="password"]::placeholder,
      .form-container input[type="text"]::placeholder {
          color: #a0aec0;
          font-weight: 400;
      }

      .password-input-container {
          position: relative;
          display: flex;
          align-items: center;
      }

      .password-input-container input {
          padding-right: 50px !important;
          width: 100% !important;
      }

      .toggle-password {
          position: absolute;
          right: 16px;
          background: none;
          border: none;
          cursor: pointer;
          color: #718096;
          padding: 4px;
          border-radius: 4px;
          transition: color 0.3s ease;
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 10;
          width: 24px;
          height: 24px;
      }

      .toggle-password:hover {
          color: #4299e1;
      }

      .toggle-password svg {
          width: 20px;
          height: 20px;
          pointer-events: none;
      }

      .remember-container {
          display: flex;
          align-items: center;
          justify-content: flex-start;
          margin: 8px 0;
      }

      .checkbox-container {
          display: flex;
          align-items: center;
          cursor: pointer;
          font-size: 0.9rem;
          color: #4a5568;
      }

      .checkbox-container input {
          display: none;
      }

      .checkmark {
          width: 20px;
          height: 20px;
          border: 2px solid #e2e8f0;
          border-radius: 4px;
          margin-right: 12px;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.3s ease;
          flex-shrink: 0;
      }

      .checkbox-container input:checked + .checkmark {
          background-color: #4299e1;
          border-color: #4299e1;
      }

      .checkbox-container input:checked + .checkmark::after {
          content: "✓";
          color: white;
          font-size: 0.8rem;
          font-weight: bold;
      }

      .form-container button[type="submit"] {
          background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
          color: white;
          border: none;
          padding: 16px 24px;
          border-radius: 16px;
          font-size: 1.1rem;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.3s ease;
          font-family: "Poppins", sans-serif;
          position: relative;
          overflow: hidden;
          margin-top: 8px;
      }

      .form-container button[type="submit"]:hover:not(:disabled) {
          transform: translateY(-2px);
          box-shadow: 0 12px 28px rgba(66, 153, 225, 0.4);
      }

      .form-container button[type="submit"]:active {
          transform: translateY(0);
      }

      .form-container button[type="submit"]:disabled {
          opacity: 0.7;
          cursor: not-allowed;
          transform: none;
      }

      .btn-loader {
          display: none;
          width: 20px;
          height: 20px;
          border: 2px solid transparent;
          border-top: 2px solid white;
          border-radius: 50%;
          animation: spin 1s linear infinite;
          margin-left: 10px;
      }

      .form-container button.loading .btn-text {
          opacity: 0.7;
      }

      .form-container button.loading .btn-loader {
          display: inline-block;
      }

      @keyframes spin {
          0% {
              transform: rotate(0deg);
          }
          100% {
              transform: rotate(360deg);
          }
      }

      .TextualButtons {
          margin: 12px 0;
      }

      .TextualButtons .Link {
          color: #4299e1;
          text-decoration: none;
          font-size: 0.95rem;
          font-weight: 500;
          transition: all 0.3s ease;
          display: inline-block;
      }

      .TextualButtons .Link:hover {
          color: #3182ce;
          text-decoration: underline;
          transform: translateY(-1px);
      }

      .links-separator {
          margin: 20px 0;
          border-top: 1px solid #e2e8f0;
          padding-top: 20px;
      }

      .error-message {
          color: #e53e3e;
          font-size: 0.85rem;
          margin-top: 6px;
          text-align: left;
          display: none;
      }

      .error-message.show {
          display: block;
      }

      .form-group input.error {
          border-color: #e53e3e;
          box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
      }

      .form-group input.success {
          border-color: #38a169;
          box-shadow: 0 0 0 3px rgba(56, 161, 105, 0.1);
      }

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
          max-width: 450px;
          text-align: center;
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

      .modal-icon {
          width: 80px;
          height: 80px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 24px;
          font-size: 2rem;
      }

      .modal-icon.success {
          background-color: #f0fff4;
          color: #38a169;
      }

      .modal-icon.error {
          background-color: #fed7d7;
          color: #e53e3e;
      }

      .modal-icon.info {
          background-color: #ebf8ff;
          color: #4299e1;
      }

      .modal-content h2 {
          color: #2d3748;
          margin-bottom: 16px;
          font-size: 1.6rem;
      }

      .modal-content p {
          color: #718096;
          margin-bottom: 24px;
          line-height: 1.6;
      }

      .modal-btn {
          background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
          color: white;
          border: none;
          padding: 12px 28px;
          border-radius: 12px;
          font-size: 1rem;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
          font-family: "Poppins", sans-serif;
          margin: 0 8px;
      }

      .modal-btn:hover {
          transform: translateY(-1px);
          box-shadow: 0 8px 20px rgba(66, 153, 225, 0.3);
      }

      .modal-btn.secondary {
          background: #e2e8f0;
          color: #4a5568;
      }

      .modal-btn.secondary:hover {
          background: #cbd5e0;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      }

      .recovery-form {
          display: flex;
          flex-direction: column;
          gap: 20px;
          margin-top: 20px;
      }

      .recovery-form input {
          width: 100%;
          padding: 14px 18px;
          border: 2px solid #e2e8f0;
          border-radius: 12px;
          font-size: 1rem;
          font-family: "Poppins", sans-serif;
          transition: all 0.3s ease;
      }

      .recovery-form input:focus {
          border-color: #4299e1;
          box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
          outline: none;
      }

      @keyframes fadeInUp {
          from {
              opacity: 0;
              transform: translateY(30px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }

      @media (max-width: 768px) {
          body {
              padding: 15px;
          }

          .form-container {
              padding: 36px 28px;
              border-radius: 20px;
          }

          .form-container h2 {
              font-size: 1.8rem;
              margin-bottom: 32px;
          }

          .logoLoMejor img {
              width: 150px;
          }

          .modal-content {
              margin: 15% auto;
              padding: 32px 24px;
          }
      }

      @media (max-width: 480px) {
          .form-container {
              padding: 28px 20px;
              border-radius: 16px;
          }

          .form-container h2 {
              font-size: 1.6rem;
          }

          .logoLoMejor img {
              width: 130px;
          }

          .form-container input[type="email"],
          .form-container input[type="password"],
          .form-container input[type="text"] {
              padding: 14px 16px;
              font-size: 0.95rem;
          }

          .password-input-container input {
              padding-right: 46px !important;
          }

          .form-container button[type="submit"] {
              padding: 14px 20px;
              font-size: 1rem;
          }
      }

      .form-container input:focus,
      .form-container button:focus,
      .checkbox-container:focus-within {
          outline: 2px solid #4299e1;
          outline-offset: 2px;
      }

      .form-group.loading input {
          background-color: #f7fafc;
          cursor: wait;
      }

      .form-container {
          position: relative;
      }

      .form-container::before {
          content: "";
          position: absolute;
          top: -2px;
          left: -2px;
          right: -2px;
          bottom: -2px;
          background: linear-gradient(45deg, #4299e1, #9f7aea, #4299e1);
          border-radius: 26px;
          z-index: -1;
          opacity: 0;
          transition: opacity 0.3s ease;
      }

      .form-container:hover::before {
          opacity: 0.1;
      }
  </style>
</head>
<body>
  <div class="form-container">
      <div class="logoLoMejor">
          <img src="img/header/loMejorParaYou2.png" alt="Blue - Lo mejor para ti">
      </div>
      
      <h2>Inicia sesión</h2>
      
      <form id="loginForm">
          <div class="form-group">
              <label for="email">Correo electrónico</label>
              <input type="email" id="email" name="email" placeholder="tu@email.com" required>
              <span class="error-message" id="email-error"></span>
          </div>
          
          <div class="form-group">
              <label for="password">Contraseña</label>
              <div class="password-input-container">
                  <input type="password" id="password" name="password" placeholder="Tu contraseña" required>
                  <button type="button" class="toggle-password" id="togglePassword">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                          <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                  </button>
              </div>
              <span class="error-message" id="password-error"></span>
          </div>

          <div class="remember-container">
              <label class="checkbox-container">
                  <input type="checkbox" id="remember" name="remember">
                  <span class="checkmark"></span>
                  Recordarme en este dispositivo
              </label>
          </div>
          
          <button type="submit" id="loginBtn">
              <span class="btn-text">Continuar</span>
              <div class="btn-loader" id="btnLoader"></div>
          </button>
      </form>
      
      <div class="TextualButtons">
          <a href="#" class="Link" id="forgotPasswordLink">¿Olvidaste tu contraseña?</a>
      </div>
      
      <div class="links-separator"></div>
      
      <div class="TextualButtons">
          <a href="register.php" class="Link">¿Aún no tienes una cuenta? Regístrate</a>
      </div>
  </div>

  <!-- Modal de éxito -->
  <div class="modal" id="successModal">
      <div class="modal-content">
          <div class="modal-icon success">
              <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22,4 12,14.01 9,11.01"></polyline>
              </svg>
          </div>
          <h2>¡Bienvenido de vuelta!</h2>
          <p>Has iniciado sesión correctamente. Serás redirigido al panel principal.</p>
          <button class="modal-btn" onclick="window.location.href='index.php'">Ir al inicio</button>
      </div>
  </div>

  <!-- Modal de error -->
  <div class="modal" id="errorModal">
      <div class="modal-content">
          <div class="modal-icon error">
              <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="15" y1="9" x2="9" y2="15"></line>
                  <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
          </div>
          <h2>Error de inicio de sesión</h2>
          <p id="errorMessage">Credenciales incorrectas. Por favor, verifica tu email y contraseña.</p>
          <button class="modal-btn" onclick="closeModal('errorModal')">Intentar de nuevo</button>
      </div>
  </div>

  <!-- Modal de recuperación de contraseña -->
  <div class="modal" id="recoveryModal">
      <div class="modal-content">
          <div class="modal-icon info">
              <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
              </svg>
          </div>
          <h2>Recuperar contraseña</h2>
          <p>Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
          
          <form class="recovery-form" id="recoveryForm">
              <input type="email" id="recoveryEmail" placeholder="tu@email.com" required>
              <div style="display: flex; gap: 12px; justify-content: center; margin-top: 8px;">
                  <button type="button" class="modal-btn secondary" onclick="closeModal('recoveryModal')">Cancelar</button>
                  <button type="submit" class="modal-btn">Enviar enlace</button>
              </div>
          </form>
      </div>
  </div>

  <!-- Modal de confirmación de recuperación -->
  <div class="modal" id="recoverySuccessModal">
      <div class="modal-content">
          <div class="modal-icon success">
              <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22,4 12,14.01 9,11.01"></polyline>
              </svg>
          </div>
          <h2>¡Enlace enviado!</h2>
          <p>Hemos enviado un enlace de recuperación a tu correo electrónico. Revisa tu bandeja de entrada y sigue las instrucciones.</p>
          <button class="modal-btn" onclick="closeModal('recoverySuccessModal')">Entendido</button>
      </div>
  </div>

  <script src="js/global-search.js"></script>
  <script>
      document.addEventListener("DOMContentLoaded", () => {
          const loginForm = document.getElementById("loginForm")
          const recoveryForm = document.getElementById("recoveryForm")
          const formFields = document.querySelectorAll("input")

          formFields.forEach((field) => {
              field.addEventListener("focus", () => {
                  field.classList.add("focus")
              })

              field.addEventListener("blur", () => {
                  if (!field.value) {
                      field.classList.remove("focus")
                  }
              })

              field.addEventListener("input", () => {
                  clearFieldError(field)
              })
          })

          // Toggle password visibility
          document.getElementById("togglePassword").addEventListener("click", () => {
              const passwordField = document.getElementById("password")
              const toggleBtn = document.getElementById("togglePassword")
              
              if (passwordField.type === "password") {
                  passwordField.type = "text"
                  toggleBtn.innerHTML = `
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                          <line x1="1" y1="1" x2="23" y2="23"></line>
                      </svg>
                  `
              } else {
                  passwordField.type = "password"
                  toggleBtn.innerHTML = `
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                          <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                  `
              }
          })

          // Login form submission
          loginForm.addEventListener("submit", async (e) => {
              e.preventDefault()
              
              const formData = new FormData(loginForm)
              const data = {
                  email: formData.get("email"),
                  contrasena: formData.get("password")
              }

              if (!validateForm(data)) {
                  return
              }

              setLoading(true)

              try {
                  const response = await fetch("login.php", {
                      method: "POST",
                      headers: {
                          "Content-Type": "application/json",
                      },
                      body: JSON.stringify(data),
                  })

                  const result = await response.json()

                  if (result.success) {
                      showModal("successModal")
                      setTimeout(() => {
                          window.location.href = result.data.redirect || "index.php"
                      }, 2000)
                  } else {
                      document.getElementById("errorMessage").textContent = result.message
                      showModal("errorModal")
                  }
              } catch (error) {
                  console.error("Login error:", error)
                  document.getElementById("errorMessage").textContent = "Error de conexión. Intenta de nuevo."
                  showModal("errorModal")
              } finally {
                  setLoading(false)
              }
          })

          // Recovery form submission
          recoveryForm.addEventListener("submit", async (e) => {
              e.preventDefault()
              
              const email = document.getElementById("recoveryEmail").value

              if (!email || !isValidEmail(email)) {
                  alert("Por favor, ingresa un email válido")
                  return
              }

              try {
                  const response = await fetch("backend/php/forgot-password.php", {
                      method: "POST",
                      headers: {
                          "Content-Type": "application/json",
                      },
                      body: JSON.stringify({ email }),
                  })

                  const result = await response.json()

                  closeModal("recoveryModal")
                  showModal("recoverySuccessModal")
              } catch (error) {
                  console.error("Recovery error:", error)
                  alert("Error al enviar el enlace. Intenta de nuevo.")
              }
          })

          // Forgot password link
          document.getElementById("forgotPasswordLink").addEventListener("click", (e) => {
              e.preventDefault()
              showModal("recoveryModal")
          })

          function validateForm(data) {
              let isValid = true

              if (!data.email || !isValidEmail(data.email)) {
                  showFieldError("email", "Ingresa un email válido")
                  isValid = false
              }

              if (!data.contrasena || data.contrasena.length < 6) {
                  showFieldError("password", "La contraseña debe tener al menos 6 caracteres")
                  isValid = false
              }

              return isValid
          }

          function isValidEmail(email) {
              const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
              return emailRegex.test(email)
          }

          function showFieldError(fieldName, message) {
              const field = document.getElementById(fieldName)
              const errorElement = document.getElementById(`${fieldName}-error`)
              
              field.classList.add("error")
              errorElement.textContent = message
              errorElement.classList.add("show")
          }

          function clearFieldError(field) {
              const fieldName = field.id
              const errorElement = document.getElementById(`${fieldName}-error`)
              
              field.classList.remove("error")
              if (errorElement) {
                  errorElement.classList.remove("show")
              }
          }

          function setLoading(loading) {
              const loginBtn = document.getElementById("loginBtn")
              const btnText = loginBtn.querySelector(".btn-text")
              const btnLoader = loginBtn.querySelector(".btn-loader")

              if (loading) {
                  loginBtn.disabled = true
                  loginBtn.classList.add("loading")
                  btnText.textContent = "Iniciando sesión..."
                  btnLoader.style.display = "inline-block"
              } else {
                  loginBtn.disabled = false
                  loginBtn.classList.remove("loading")
                  btnText.textContent = "Continuar"
                  btnLoader.style.display = "none"
              }
          }

          function showModal(modalId) {
              document.getElementById(modalId).style.display = "block"
          }

          function closeModal(modalId) {
              document.getElementById(modalId).style.display = "none"
          }

          // Make closeModal global for onclick handlers
          window.closeModal = closeModal
      })
  </script>
</body>
</html>
