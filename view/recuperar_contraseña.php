<?php
// recuperar_contraseña.php
session_start();

// Si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Aquí normalmente buscarías el usuario en la BD
        // y enviarías un correo con un enlace de recuperación.
        // Por ahora solo simulamos el proceso.
        $_SESSION['mensaje'] = "Se ha enviado un enlace de recuperación a $email";
    } else {
        $_SESSION['mensaje'] = "Por favor ingresa un correo válido.";
    }

    header("Location: recuperar_contraseña.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
        }

        .container {
            width: 400px;
            margin: 80px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }

        h2 {
            text-align: center;
        }

        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #007BFF;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .mensaje {
            margin-top: 15px;
            color: green;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Recuperar contraseña</h2>
        <form method="POST" action="">
            <label for="email">Ingresa tu correo:</label>
            <input type="email" name="email" id="email" required>
            <button type="submit">Enviar enlace</button>
        </form>
        <?php if (isset($_SESSION['mensaje'])): ?>
            <p class="mensaje"><?php echo $_SESSION['mensaje'];
                                unset($_SESSION['mensaje']); ?></p>
        <?php endif; ?>
    </div>
</body>

</html>