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
        $mail->SMTPDebug = SMTP::DEBUG_OFF;

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'carantonmarinm@gmail.com';
        $mail->Password   = 'sukasskutewweghu';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('carantonmarinm@gmail.com', 'Tentaciones Marlly');
        $mail->addAddress($destinatario, $nombre);

        $mail->isHTML(true);
        $mail->Subject = 'Recupera tu contraseña - Tentaciones Marlly';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 25px; border: 1px solid #eee; border-radius: 10px; background-color: #ffffff;'>
                <div style='text-align: center; margin-bottom: 25px;'>
                    <h2 style='color: #e63c82; margin: 0; font-size: 24px;'>Tentaciones Marlly</h2>
                    <p style='color: #888; font-size: 13px; margin: 5px 0 0 0; text-transform: uppercase; letter-spacing: 1px;'>Mini Tienda de Consumo Diario</p>
                </div>
                <h3 style='color: #333; margin-bottom: 15px;'>Recuperación de Contraseña</h3>
                <p style='color: #555; line-height: 1.6; font-size: 14px;'>Hola, <strong>" . htmlspecialchars($nombre) . "</strong>.</p>
                <p style='color: #555; line-height: 1.6; font-size: 14px;'>Recibimos una solicitud para restablecer la contraseña de tu cuenta en el sistema <strong>Tentaciones Marlly</strong>.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$link}' style='background: linear-gradient(135deg, #e63c82, #ff6b9d); color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 15px;'>Restablecer mi Contraseña</a>
                </div>
                <p style='color: #777; font-size: 13px; line-height: 1.5;'>Este enlace expirará en <strong>5 minutos</strong> y solo puede utilizarse una vez.</p>
                <p style='color: #999; font-size: 12px; line-height: 1.5;'>Si no solicitaste este cambio, puedes ignorar este mensaje. Tu contraseña actual no se modificará.</p>
                <hr style='border: none; border-top: 1px solid #f0f0f0; margin: 25px 0 15px 0;'>
                <p style='color: #bbb; font-size: 11px; text-align: center; margin: 0;'>Tentaciones Marlly &copy; " . date('Y') . " - Todos los derechos reservados.</p>
            </div>
        ";
        $mail->AltBody = "Hola {$nombre}.\n\nPara restablecer tu contraseña en Tentaciones Marlly, ingresa al siguiente enlace:\n{$link}\n\nEste enlace expirará en 5 minutos.";

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Error al enviar correo de recuperación: " . $mail->ErrorInfo);
        return false;
    }
}