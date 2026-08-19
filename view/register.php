<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentaciones Marlly - Registrarse</title>
    <link rel="stylesheet" href="/PHP---Tentaciones-Marlly/public/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">

            <img src="/PHP---Tentaciones-Marlly/public/logo.png" alt="Logo Tentaciones Marlly" class="shop-logo">

            <p class="tagline">MINI TIENDA DE CONSUMO DIARIO</p>

            <h2 class="welcome">Crear Cuenta</h2>
            <p class="subtitle">Regístrate para comenzar</p>

            <!-- Formulario configurado hacia el controlador -->
            <form action="/PHP---Tentaciones-Marlly/index.php" method="POST">
                <input type="hidden" name="action" value="register">

                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Nombre de usuario" required>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Contraseña" required>
                    <i class="fa-solid fa-eye toggle-eye" id="toggleEye"></i>
                </div>

                <button type="submit">Registrarme</button>
            </form>

            <p class="register-link" style="margin-top: 20px; font-size: 14px; color: #888;">
                ¿Ya tienes una cuenta? <a href="../index.php" style="color: #e63c82; text-decoration: none; font-weight: 600;">Iniciar Sesión</a>
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