<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - EducaMente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-hover: #2980b9;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --error-color: #e74c3c;
            --success-color: #27ae60;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            align-items: center;
            justify-content: center;
        }
        
        .container {
            width: 100%;
            max-width: 420px;
            margin: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .register-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            padding: 30px;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo h1 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .logo span {
            color: var(--dark-color);
            font-size: 14px;
        }
        
        h2 {
            color: var(--dark-color);
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group input, .input-group select {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus, .input-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .input-group input:focus + i, .input-group select:focus + i {
            color: var(--primary-color);
        }
        
        .show-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        button:after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(100, 100);
                opacity: 0;
            }
        }
        
        button:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #555;
            font-size: 14px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        /* Toast notification */
        #toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
            z-index: 1000;
            min-width: 280px;
            max-width: 90%;
        }
        
        #toast.show {
            opacity: 1;
            visibility: visible;
            animation: slideInUp 0.4s ease forwards;
        }
        
        #toast.success {
            background-color: var(--success-color);
        }
        
        #toast.error {
            background-color: var(--error-color);
        }
        
        #toast i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        @keyframes slideInUp {
            from { transform: translate(-50%, 20px); opacity: 0; }
            to { transform: translate(-50%, 0); opacity: 1; }
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
        }
        
        /* Modo oscuro si el sistema lo tiene activado */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #1a2a3a, #0f1923);
            }
            
            .register-container {
                background: #1f2937;
            }
            
            .logo h1 {
                color: #60a5fa;
            }
            
            .logo span, h2 {
                color: #e5e7eb;
            }
            
            .input-group input, .input-group select {
                background-color: #374151;
                border-color: #4b5563;
                color: #e5e7eb;
            }
            
            .input-group i, .show-password {
                color: #9ca3af;
            }
            
            .login-link {
                color: #d1d5db;
            }
            
            .login-link a {
                color: #60a5fa;
            }
            
            .login-link a:hover {
                color: #93c5fd;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="logo">
                <h1>EducaMente</h1>
                <span>Tu plataforma educativa</span>
            </div>
            
            <h2>Crear Cuenta</h2>
            
            <!-- Formulario de registro -->
            <form action="realizarregistro.php" method="POST" id="registerForm">
                <div class="input-group">
                    <input type="text" name="nombre" id="nombre" placeholder="Nombre completo" required>
                    <i class="fas fa-user"></i>
                </div>
                
                <div class="input-group">
                    <input type="email" name="correo" id="correo" placeholder="Correo electrónico" required autocomplete="email">
                    <i class="fas fa-envelope"></i>
                </div>
                
                <div class="input-group">
                    <input type="password" name="contrasena" id="contrasena" placeholder="Contraseña" required autocomplete="new-password">
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye show-password" id="togglePassword"></i>
                </div>
                
                <div class="input-group">
                    <select name="tipo" id="tipo" required>
                        <option value="" disabled selected>Selecciona tu rol</option>
                        <option value="alumno">Alumno</option>
                        <option value="maestro">Maestro</option>
                    </select>
                    <i class="fas fa-user-graduate"></i>
                </div>
                
                <button type="submit" id="registerBtn">
                    <i class="fas fa-user-plus"></i> Registrarse
                </button>
            </form>
            
            <div class="login-link">
                <p>¿Ya tienes una cuenta?</p>
                <a href="index.php">Iniciar sesión</a>
            </div>
        </div>
        
        <div class="footer">
            &copy; <?php echo date('Y'); ?> EducaMente - Todos los derechos reservados
        </div>
    </div>

    <!-- Toast para notificaciones -->
    <div id="toast"></div>

    <script>
        // Toggle para mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('contrasena');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Prevenir múltiples envíos del formulario
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('registerBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
        });

        // Función para mostrar notificaciones toast mejorada
        function showToast(message, type = 'error') {
            const toast = document.getElementById("toast");
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            toast.className = 'show ' + type;
            
            // Ocultar la notificación después de 5 segundos
            setTimeout(function() {
                toast.className = toast.className.replace("show", "");
            }, 5000);
        }
        
        // Mostrar la notificación si hay un mensaje en la sesión
        <?php if (isset($_SESSION['error'])): ?>
            showToast("<?php echo htmlspecialchars($_SESSION['error']); ?>", "error");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            showToast("<?php echo htmlspecialchars($_SESSION['success']); ?>", "success");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        // Añadir validación de formulario
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            const nombre = document.getElementById('nombre').value.trim();
            const email = document.getElementById('correo').value.trim();
            const password = document.getElementById('contrasena').value;
            const tipo = document.getElementById('tipo').value;
            
            // Validación del nombre
            if (nombre.length < 3) {
                event.preventDefault();
                showToast("El nombre debe tener al menos 3 caracteres");
                return false;
            }
            
            // Validación del correo electrónico
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                event.preventDefault();
                showToast("Por favor, introduce un correo electrónico válido");
                return false;
            }
            
            // Validación de la contraseña
            if (password.length < 6) {
                event.preventDefault();
                showToast("La contraseña debe tener al menos 6 caracteres");
                return false;
            }
            
            // Validación del tipo de usuario
            if (tipo === "" || tipo === null) {
                event.preventDefault();
                showToast("Por favor, selecciona tu rol");
                return false;
            }
        });
    </script>
</body>
</html>






















<!--
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - EducaMente</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f8ff;
            font-family: Arial, sans-serif;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 320px;
        }

        h2 {
            color: #0073e6;
        }

        input, select {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        button {
            width: calc(100% - 20px);
            padding: 10px;
            background-color: #0073e6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        button:hover {
            background-color: #005bb5;
        }

        .login-link {
            margin-top: 10px;
            color: #333;
        }

        .login-link a {
            color: #0073e6;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Estilos de las notificaciones emergentes (toast) */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #333;
            color: white;
            padding: 15px;
            border-radius: 5px;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            z-index: 1000;
        }

        .toast.show {
            opacity: 1;
        }

        .toast.success {
            background-color: green;
        }

        .toast.error {
            background-color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Regístrate en EducaMente</h2>

        <form action="realizarregistro.php" method="POST">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="email" name="correo" placeholder="Correo" required>
            <input type="password" name="contrasena" placeholder="Contraseña" required>
            <select name="tipo" required>
                <option value="alumno">Alumno</option>
                <option value="maestro">Maestro</option>
            </select>
            <button type="submit">Registrar</button>
        </form>

        <div class="login-link">
            <p>¿Ya tienes cuenta? <a href="index.php">Iniciar sesión</a></p>
        </div>
    </div>

    <!-- Contenedor para la notificación emergente 
    <div id="toast" class="toast"></div>

    <script>
        // Mostrar la notificación emergente si hay un mensaje de error o éxito en la sesión
        <?php if (isset($_SESSION['error'])): ?>
            showToast("<?php echo $_SESSION['error']; ?>", "error");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            showToast("<?php echo $_SESSION['success']; ?>", "success");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        // Función para mostrar el toast
        function showToast(message, type) {
            var toast = document.getElementById("toast");
            toast.textContent = message;
            toast.classList.add("show", type); // Agrega las clases 'show' y 'success/error'
            
            // Ocultar la notificación después de 5 segundos
            setTimeout(function() {
                toast.classList.remove("show", type);
            }, 5000);
        }
    </script>
</body>
</html>

-->