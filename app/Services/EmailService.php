<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    public function sendEmail($to, $subject, $message, $attachments = [])
    {
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'dte.megaload@gmail.com';
            $mail->Password   = 'yfox sqrj wgvd orec';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->CharSet = 'UTF-8';

            // Destinatarios
            $mail->setFrom('dte.megaload@gmail.com', '[MEGALOAD] Facturación Electrónica');
            $mail->addAddress($to);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            // Adjuntar archivos
            foreach ($attachments as $file) {
                $mail->addAttachment($file); // Agregar archivo adjunto
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            return "Error al enviar el correo: {$mail->ErrorInfo}";
        }
    }
}
