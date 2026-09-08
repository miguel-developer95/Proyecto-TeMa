<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentaciones Marlly - Iniciar Sesión</title>
    <link rel="stylesheet" href="/Proyecto-TeMa/public/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

    <div class="login-wrapper">
        <div class="login-card">

            <img src="/Proyecto-TeMa/public/logo.png" alt="Logo Tentaciones Marlly" class="shop-logo">

            <p class="tagline">MINI TIENDA DE CONSUMO DIARIO</p>

            <h2 class="welcome">Bienvenido</h2>
            <p class="subtitle">Ingresa para continuar</p>

            <!-- Alertas dinámicas -->
            <?php if (isset($_GET['status']) && $_GET['status'] === 'registered'): ?>
                <p
                    style="color: #2e7d32; background-color: #e8f5e9; padding: 10px; border-radius: 5px; font-size: 14px; text-align: center;">
                    ¡Usuario registrado correctamente! Ya puedes iniciar sesión.
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_credentials'): ?>
                <p
                    style="color: #c62828; background-color: #ffebee; padding: 10px; border-radius: 5px; font-size: 14px; text-align: center;">
                    Usuario o contraseña incorrectos.
                </p>
            <?php endif; ?>

            <!-- Formulario configurado para el controlador MVC -->
            <form action="/Proyecto-TeMa/index.php" method="POST">
                <input type="hidden" name="action" value="login">

                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Usuario o Correo" required>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Contraseña" required>
                    <i class="fa-solid fa-eye toggle-eye" id="toggleEye"></i>
                </div>

                <a href="recuperar_contraseña.php" class="forgot-link">¿Olvidaste tu contraseña?</a>

                <button type="submit">Iniciar Sesión</button>
            </form>

            <!-- Opción 1: Enlace relativo correcto (ambos archivos están en la carpeta view) -->
            <p class="register-link" style="margin-top: 15px; font-size: 14px; color: #888;">
                ¿No tienes cuenta? <a href="register.php"
                    style="color: #e63c82; text-decoration: none; font-weight: 600;">Regístrate</a>
            </p>

        </div>
    </div>

    <script>
        const toggleEye = document.getElementById('toggleEye');
        const password = document.getElementById('password');

        toggleEye.addEventListener('click', () => {
            const isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            toggleEye.classList.toggle('fa-eye');
            toggleEye.classList.toggle('fa-eye-slash');
        });
    </script>

</body>

</html>