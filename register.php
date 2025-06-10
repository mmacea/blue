<?php
require_once 'backend/config/database.php';

// Si ya está autenticado, redirigir al inicio
$user = validateSession();
if ($user) {
    header('Location: index.php');
    exit;
}

// Procesar registro si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiar cualquier salida anterior
    if (ob_get_length()) ob_clean();
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Si no hay datos JSON, intentar obtener de $_POST
        if (!$input) {
            $input = $_POST;
        }
        
        $required_fields = ['nombre', 'telefono', 'email', 'contrasena', 'confirmar_contrasena'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || empty(trim($input[$field]))) {
                sendJsonResponse(false, "El campo {$field} es requerido", null, 400);
            }
        }
        
        $nombre = trim($input['nombre']);
        $telefono = trim($input['telefono']);
        $email = trim($input['email']);
        $password = $input['contrasena'];
        $confirm_password = $input['confirmar_contrasena'];
        
        // Validaciones
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJsonResponse(false, "Email inválido", null, 400);
        }
        
        if (strlen($password) < 6) {
            sendJsonResponse(false, "La contraseña debe tener al menos 6 caracteres", null, 400);
        }
        
        if ($password !== $confirm_password) {
            sendJsonResponse(false, "Las contraseñas no coinciden", null, 400);
        }
        
        if (!preg_match('/^[0-9]{10,15}$/', $telefono)) {
            sendJsonResponse(false, "Teléfono inválido", null, 400);
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
        
        // Crear usuario
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $token_confirmacion = bin2hex(random_bytes(32));
        
        $query = "INSERT INTO usuarios (nombre, telefono, email, contrasena, tokenConfirmacion) 
                  VALUES (:nombre, :telefono, :email, :contrasena, :token)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contrasena', $hashed_password);
        $stmt->bindParam(':token', $token_confirmacion);
        
        if ($stmt->execute()) {
            $user_id = $db->lastInsertId();
            
            // Crear notificación de bienvenida
            try {
                $query = "INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo) 
                          VALUES (:user_id, :titulo, :mensaje, 'success')";
                
                $stmt = $db->prepare($query);
                $titulo = "¡Bienvenido a Blue Pharmacy!";
                $mensaje = "Tu cuenta ha sido creada exitosamente. ¡Comienza a explorar nuestros productos!";
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':mensaje', $mensaje);
                $stmt->execute();
            } catch (Exception $e) {
                error_log("Notification creation failed: " . $e->getMessage());
            }
            
            // Iniciar sesión automáticamente después del registro
            session_start();
            $session_id = bin2hex(random_bytes(32));
            $expiration = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Crear sesión en la base de datos
            try {
                $query = "INSERT INTO sesiones (idSesion, idUsuario, fechaExpiracion) 
                  VALUES (:session_id, :user_id, :expiration)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':session_id', $session_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':expiration', $expiration);
                $stmt->execute();
            } catch (Exception $e) {
                error_log("Session creation failed: " . $e->getMessage());
            }
            
            // Establecer variables de sesión
            $_SESSION['user_id'] = $user_id;
            $_SESSION['session_id'] = $session_id;
            $_SESSION['user_name'] = $nombre;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'usuario';
            
            sendJsonResponse(true, "Usuario registrado e iniciado sesión exitosamente", [
                'user_id' => $user_id,
                'user' => [
                    'id' => $user_id,
                    'nombre' => $nombre,
                    'email' => $email,
                    'rol' => 'usuario'
                ],
                'redirect' => 'index.php'
            ]);
        } else {
            sendJsonResponse(false, "Error al crear el usuario", null, 500);
        }
        
    } catch (Exception $e) {
        error_log("Register error: " . $e->getMessage());
        sendJsonResponse(false, "Error del servidor: " . $e->getMessage(), null, 500);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regístrate en Blue</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 480px;
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

        .form-row {
            display: flex;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            text-align: left;
            flex: 1;
        }

        .form-group label {
            color: #2d3748;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-container input[type="text"],
        .form-container input[type="email"],
        .form-container input[type="tel"],
        .form-container input[type="password"] {
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

        .form-container input:focus {
            border-color: #667eea;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-container input::placeholder {
            color: #a0aec0;
            font-weight: 400;
        }

        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-container input {
            padding-right: 50px;
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
        }

        .toggle-password:hover {
            color: #667eea;
        }

        .terms-container {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            margin: 8px 0;
            text-align: left;
        }

        .checkbox-container {
            display: flex;
            align-items: flex-start;
            cursor: pointer;
            font-size: 0.9rem;
            color: #4a5568;
            line-height: 1.5;
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
            margin-top: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .checkbox-container input:checked + .checkmark {
            background-color: #667eea;
            border-color: #667eea;
        }

        .checkbox-container input:checked + .checkmark::after {
            content: "✓";
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .form-container button[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.4);
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .TextualButtons {
            margin: 12px 0;
        }

        .TextualButtons .Link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .TextualButtons .Link:hover {
            color: #5a67d8;
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

        .password-strength {
            margin-top: 8px;
            font-size: 0.8rem;
        }

        .strength-bar {
            height: 4px;
            background-color: #e2e8f0;
            border-radius: 2px;
            margin: 4px 0;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak .strength-fill {
            width: 33%;
            background-color: #e53e3e;
        }

        .strength-medium .strength-fill {
            width: 66%;
            background-color: #ed8936;
        }

        .strength-strong .strength-fill {
            width: 100%;
            background-color: #38a169;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
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

            .form-row {
                flex-direction: column;
                gap: 24px;
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

            .form-container input {
                padding: 14px 16px;
                font-size: 0.95rem;
            }

            .form-container button[type="submit"] {
                padding: 14px 20px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logoLoMejor">
            <img src="img/header/loMejorParaYou2.png" alt="Blue - Lo mejor para ti">
        </div>
        
        <h2>Crear cuenta</h2>
        
        <form id="registerForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Tu nombre completo" required>
                    <span class="error-message" id="nombre-error"></span>
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" placeholder="3001234567" required>
                    <span class="error-message" id="telefono-error"></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                <span class="error-message" id="email-error"></span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
                        <button type="button" class="toggle-password" id="togglePassword">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill"></div>
                        </div>
                        <span class="strength-text"></span>
                    </div>
                    <span class="error-message" id="password-error"></span>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite tu contraseña" required>
                        <button type="button" class="toggle-password" id="toggleConfirmPassword">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <span class="error-message" id="confirm_password-error"></span>
                </div>
            </div>

            <div class="terms-container">
                <label class="checkbox-container">
                    <input type="checkbox" id="terms" name="terms" required>
                    <span class="checkmark"></span>
                    Acepto los <a href="#" style="color: #667eea;">términos y condiciones</a> y la <a href="#" style="color: #667eea;">política de privacidad</a>
                </label>
            </div>
            
            <button type="submit" id="registerBtn">
                <span class="btn-text">Crear cuenta</span>
                <div class="btn-loader" id="btnLoader"></div>
            </button>
        </form>
        
        <div class="links-separator"></div>
        
        <div class="TextualButtons">
            <a href="login.php" class="Link">¿Ya tienes una cuenta? Inicia sesión</a>
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
            <h2>¡Cuenta creada!</h2>
            <p>Tu cuenta ha sido creada exitosamente y ya has iniciado sesión. ¡Bienvenido a Blue Pharmacy!</p>
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
            <h2>Error en el registro</h2>
            <p id="errorMessage">Ha ocurrido un error. Por favor, intenta nuevamente.</p>
            <button class="modal-btn" onclick="closeModal('errorModal')">Intentar de nuevo</button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const registerForm = document.getElementById("registerForm")
            const formFields = document.querySelectorAll("input")
            const passwordField = document.getElementById("password")
            const confirmPasswordField = document.getElementById("confirm_password")

            // Setup field validation
            formFields.forEach((field) => {
                field.addEventListener("focus", () => {
                    field.classList.add("focus")
                })

                field.addEventListener("blur", () => {
                    if (!field.value) {
                        field.classList.remove("focus", "success", "error")
                        clearFieldError(field)
                    } else {
                        validateField(field)
                    }
                })

                field.addEventListener("input", () => {
                    validateField(field)
                })
            })

            // Password strength indicator
            passwordField.addEventListener("input", () => {
                updatePasswordStrength(passwordField.value)
                if (confirmPasswordField.value) {
                    validatePasswordMatch()
                }
            })

            confirmPasswordField.addEventListener("input", () => {
                validatePasswordMatch()
            })

            registerForm.addEventListener("submit", async (event) => {
                event.preventDefault()

                // Validate all fields
                let isValid = true
                formFields.forEach((field) => {
                    if (!validateField(field)) {
                        isValid = false
                    }
                })

                if (!validatePasswordMatch()) {
                    isValid = false
                }

                if (!document.getElementById("terms").checked) {
                    alert("Debes aceptar los términos y condiciones")
                    isValid = false
                }

                if (isValid) {
                    setLoading(true)
                    
                    try {
                        const formData = new FormData(registerForm)
                        const registerData = Object.fromEntries(formData.entries())

                        console.log("📤 Registrando usuario:", {
                            nombre: registerData.nombre,
                            email: registerData.email,
                            telefono: registerData.telefono
                        })

                        const response = await fetch("register.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({
                                nombre: registerData.nombre,
                                telefono: registerData.telefono,
                                email: registerData.email,
                                contrasena: registerData.password,
                                confirmar_contrasena: registerData.confirm_password
                            }),
                        })

                        const result = await response.json()

                        if (result.success) {
                            console.log("✅ Registro exitoso:", result.data)
                            showModal("successModal")
                            registerForm.reset()
                            formFields.forEach((field) => {
                                field.classList.remove("success", "error")
                                clearFieldError(field)
                            })
                            updatePasswordStrength("")
                            
                            // Redirigir después de 2 segundos
                            setTimeout(() => {
                                window.location.href = result.data.redirect || 'index.php'
                            }, 2000)
                        } else {
                            throw new Error(result.message || "Error en el registro")
                        }
                    } catch (error) {
                        console.error("❌ Error en registro:", error)
                        document.getElementById("errorMessage").textContent =
                            error.message || "Error de conexión. Por favor, intenta nuevamente."
                        showModal("errorModal")
                    } finally {
                        setLoading(false)
                    }
                }
            })

            function validateField(field) {
                const value = field.value.trim()
                let isValid = true
                let errorMessage = ""

                // Basic required validation
                if (field.hasAttribute("required") && !value) {
                    isValid = false
                    errorMessage = "Este campo es requerido"
                } else {
                    // Specific validations
                    switch (field.type) {
                        case "email":
                            if (value && !isValidEmail(value)) {
                                isValid = false
                                errorMessage = "Email inválido"
                            }
                            break
                        case "tel":
                            if (value && !isValidPhone(value)) {
                                isValid = false
                                errorMessage = "Teléfono inválido (10-15 dígitos)"
                            }
                            break
                        case "password":
                            if (field.id === "password" && value && value.length < 6) {
                                isValid = false
                                errorMessage = "Mínimo 6 caracteres"
                            }
                            break
                        case "text":
                            if (field.id === "nombre" && value && value.length < 2) {
                                isValid = false
                                errorMessage = "Nombre muy corto"
                            }
                            break
                    }
                }

                if (isValid) {
                    field.classList.remove("error")
                    field.classList.add("success")
                    clearFieldError(field)
                } else {
                    field.classList.remove("success")
                    field.classList.add("error")
                    displayFieldError(field, errorMessage)
                }

                return isValid
            }

            function validatePasswordMatch() {
                const password = passwordField.value
                const confirmPassword = confirmPasswordField.value

                if (confirmPassword && password !== confirmPassword) {
                    confirmPasswordField.classList.remove("success")
                    confirmPasswordField.classList.add("error")
                    displayFieldError(confirmPasswordField, "Las contraseñas no coinciden")
                    return false
                } else if (confirmPassword) {
                    confirmPasswordField.classList.remove("error")
                    confirmPasswordField.classList.add("success")
                    clearFieldError(confirmPasswordField)
                    return true
                }
                return true
            }

            function updatePasswordStrength(password) {
                const strengthContainer = document.getElementById("passwordStrength")
                const strengthBar = strengthContainer.querySelector(".strength-bar")
                const strengthText = strengthContainer.querySelector(".strength-text")

                if (!password) {
                    strengthBar.className = "strength-bar"
                    strengthText.textContent = ""
                    return
                }

                let score = 0
                let feedback = []

                // Length check
                if (password.length >= 8) score += 1
                else feedback.push("al menos 8 caracteres")

                // Uppercase check
                if (/[A-Z]/.test(password)) score += 1
                else feedback.push("una mayúscula")

                // Lowercase check
                if (/[a-z]/.test(password)) score += 1
                else feedback.push("una minúscula")

                // Number check
                if (/\d/.test(password)) score += 1
                else feedback.push("un número")

                // Special character check
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score += 1
                else feedback.push("un carácter especial")

                let strength = ""
                let strengthClass = ""

                if (score < 2) {
                    strength = "Débil"
                    strengthClass = "strength-weak"
                } else if (score < 4) {
                    strength = "Media"
                    strengthClass = "strength-medium"
                } else {
                    strength = "Fuerte"
                    strengthClass = "strength-strong"
                }

                strengthBar.className = `strength-bar ${strengthClass}`
                strengthText.textContent = `Fortaleza: ${strength}`

                if (feedback.length > 0 && score < 4) {
                    strengthText.textContent += ` (Falta: ${feedback.slice(0, 2).join(", ")})`
                }
            }

            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
            }

            function isValidPhone(phone) {
                return /^[0-9]{10,15}$/.test(phone)
            }

            function displayFieldError(field, message) {
                const errorId = field.id + "-error"
                const errorElement = document.getElementById(errorId)
                if (errorElement) {
                    errorElement.textContent = message
                    errorElement.classList.add("show")
                }
            }

            function clearFieldError(field) {
                const errorId = field.id + "-error"
                const errorElement = document.getElementById(errorId)
                if (errorElement) {
                    errorElement.textContent = ""
                    errorElement.classList.remove("show")
                }
            }

            function setLoading(loading) {
                const registerBtn = document.getElementById("registerBtn")
                const btnText = registerBtn.querySelector(".btn-text")
                const btnLoader = registerBtn.querySelector(".btn-loader")

                if (loading) {
                    registerBtn.disabled = true
                    registerBtn.classList.add("loading")
                    btnText.textContent = "Creando cuenta..."
                    btnLoader.style.display = "inline-block"
                } else {
                    registerBtn.disabled = false
                    registerBtn.classList.remove("loading")
                    btnText.textContent = "Crear cuenta"
                    btnLoader.style.display = "none"
                }
            }

            window.showModal = (modalId) => {
                const modal = document.getElementById(modalId)
                modal.style.display = "block"
            }

            window.closeModal = (modalId) => {
                const modal = document.getElementById(modalId)
                modal.style.display = "none"
            }

            // Password toggle functionality
            function setupPasswordToggle(toggleId, inputId) {
                const toggleBtn = document.getElementById(toggleId)
                const passwordInput = document.getElementById(inputId)

                if (toggleBtn && passwordInput) {
                    toggleBtn.addEventListener("click", () => {
                        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password"
                        passwordInput.setAttribute("type", type)

                        const icon = toggleBtn.querySelector("svg")
                        if (icon) {
                            if (type === "text") {
                                icon.innerHTML = `
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                `
                            } else {
                                icon.innerHTML = `
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                `
                            }
                        }
                    })
                }
            }

            setupPasswordToggle("togglePassword", "password")
            setupPasswordToggle("toggleConfirmPassword", "confirm_password")
        })
    </script>
</body>
</html>
