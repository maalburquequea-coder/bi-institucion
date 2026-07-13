<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

class EmailService
{
    /**
     * Envía un correo electrónico utilizando el servidor SMTP de Gmail.
     */
    public static function enviar(string $destino, string $asunto, string $mensaje): bool
    {
        if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (SMTP_HOST === '' || SMTP_USER === '' || SMTP_PASS === '') {
            error_log('SMTP no configurado: revise SMTP_HOST, SMTP_USER y SMTP_PASS en config/conexion.php');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // Configuración del Servidor
            // Nivel 0: Off, 1: Cliente, 2: Cliente y Servidor (Recomendado para depurar)
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->Timeout    = 10;
            $mail->CharSet    = 'UTF-8';

            // Destinatarios
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($destino);

            // Contenido
            $mail->isHTML(false);
            $mail->Subject = $asunto;
            $mail->Body    = $mensaje;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error de PHPMailer: {$mail->ErrorInfo}");
            return false;
        }
    }
}
