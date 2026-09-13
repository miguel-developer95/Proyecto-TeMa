<?php
declare(strict_types=1);

/**
 * Envío de correo de recuperación (restaurado del commit bda5c73 de Miguel,
 * saneado: sin credenciales hardcodeadas, sin echo/die de debug).
 * Usa libs/PHPMailer/* con configuración tomada de .env vía config.php.
 */
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';
require_once __DIR__ . '/../libs/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/** True si hay credenciales SMTP configuradas en el entorno. */
function mailerConfigurado(): bool
{
    return SMTP_USER !== '' && SMTP_PASS !== '';
}

/**
 * Envía el enlace de restablecimiento. Retorna true/false sin imprimir nada.
 * Si no hay SMTP configurado, retorna false para que el llamador aplique fallback local.
 */
function enviarCorreoRecuperacion(string $destinatario, string $nombre, string $link): bool
{
    if (!mailerConfigurado()) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 10;

        $from = SMTP_FROM !== '' ? SMTP_FROM : SMTP_USER;
        $mail->setFrom($from, SMTP_FROM_NAME);
        $mail->addAddress($destinatario, $nombre);

        // Logo embebido (CID): se muestra en cualquier bandeja sin URL pública.
        $logoPath = __DIR__ . '/../public/logo.png';
        $tieneLogo = is_file($logoPath);
        if ($tieneLogo) {
            $mail->addEmbeddedImage($logoPath, 'logo_tentaciones', 'logo.png');
        }

        $mail->isHTML(true);
        $mail->Subject = 'Recupera tu contraseña - ' . SMTP_FROM_NAME;
        $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $linkSeguro = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        $marcaSegura = htmlspecialchars(SMTP_FROM_NAME, ENT_QUOTES, 'UTF-8');
        $cabeceraMarca = $tieneLogo
            ? '<img src="cid:logo_tentaciones" alt="' . $marcaSegura . '" width="110" style="width:110px;height:auto;border-radius:50%;display:block;margin:0 auto;">'
            : '<div style="font-size:44px;line-height:1;">🔐</div>';
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#fce4ec;font-family:'Segoe UI',Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fce4ec;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(230,60,130,0.20);">
<tr><td align="center" style="background:linear-gradient(135deg,#e63c82,#c22b68);padding:32px 24px;">
{$cabeceraMarca}
<h1 style="margin:12px 0 0;color:#ffffff;font-size:22px;">{$marcaSegura}</h1>
<p style="margin:6px 0 0;color:#ffd8e8;font-size:13px;letter-spacing:1px;">RECUPERACIÓN DE CONTRASEÑA</p>
</td></tr>
<tr><td style="padding:32px 28px;color:#2b3a55;">
<p style="margin:0 0 12px;font-size:16px;">Hola, <strong>{$nombreSeguro}</strong> 👋</p>
<p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#555;">Recibimos una solicitud para restablecer tu contraseña en el sistema <strong>{$marcaSegura}</strong>. Haz clic en el botón para crear una nueva:</p>
<p align="center" style="margin:24px 0;">
<a href="{$linkSeguro}" style="display:inline-block;background-color:#e63c82;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:14px 36px;border-radius:10px;">Restablecer contraseña</a>
</p>
<p style="margin:0 0 8px;font-size:13px;color:#888;">⏳ Este enlace expirará en <strong>1 hora</strong>.</p>
<p style="margin:0;font-size:13px;color:#888;">Si el botón no funciona, copia y pega este enlace en tu navegador:<br><a href="{$linkSeguro}" style="color:#e63c82;word-break:break-all;">{$linkSeguro}</a></p>
</td></tr>
<tr><td align="center" style="background-color:#f8f9fa;padding:16px 24px;border-top:1px solid #eee;">
<p style="margin:0;font-size:12px;color:#999;">Si no solicitaste este cambio, ignora este mensaje. Tu contraseña seguirá intacta.</p>
<p style="margin:6px 0 0;font-size:12px;color:#bbb;">{$marcaSegura} · Mensaje automático, no responder</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
        $mail->AltBody = "Hola, {$nombre}. Restablece tu contraseña aquí (válido 1 hora): {$link}";

        return $mail->send();
    } catch (Exception $e) {
        error_log('[mailer_recuperacion] ' . $mail->ErrorInfo);
        return false;
    }
}
