<?php
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';
require_once __DIR__ . '/../libs/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoRecuperacion(string $destinatario, string $nombre, string $link): bool
{
    $mail = new PHPMailer(true);

    try {
        // --- DEBUG TEMPORAL: mostrar toda la conversación SMTP ---
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function ($str, $level) {
            echo "<pre>DEBUG SMTP: " . htmlspecialchars($str) . "</pre>";
        };

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'carantonmarinm@gmail.com';
        $mail->Password   = 'sukasskutewweghu'; // <-- verifica esto
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('carantonmarinm@gmail.com', 'Tentaciones Marlly');
        $mail->addAddress($destinatario, $nombre);

        $mail->isHTML(true);
        $mail->Subject = 'Recupera tu contraseña - Tentaciones Marlly';
        $mail->Body = "
            <h2>Recuperación de contraseña</h2>
            <p>Hola, {$nombre}.</p>
            <p>Recibimos una solicitud para restablecer tu contraseña en el sistema <strong>Tentaciones Marlly</strong>.</p>
            <p><a href=\"{$link}\">Restablecer contraseña</a></p>
            <p>Este enlace expirará en 30 minutos.</p>
        ";
        $mail->AltBody = "Visita: {$link}";

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->send();
        echo "<p style='color:green'>CORREO ENVIADO CON ÉXITO</p>";
        return true;

    } catch (Exception $e) {
        // DEBUG TEMPORAL: mostrar el error directamente en pantalla
        echo "<div style='background:#ffebee;color:#c62828;padding:20px;font-family:monospace;'>";
        echo "<strong>ERROR AL ENVIAR CORREO:</strong><br>";
        echo htmlspecialchars($mail->ErrorInfo);
        echo "</div>";
        die(); // detener aquí para poder leer el error sin que redirija
        return false;
    }
}