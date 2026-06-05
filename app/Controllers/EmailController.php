<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Services\EmailService;

class EmailController extends BaseController
{
    public function enviarCorreo()
    {
        $emailService = new EmailService();

        $to = 'joseluis.r85@gmail.com';
        $subject = 'Prueba de Correo';
        $message = '<h1>Hola!</h1><p>Este es un correo de prueba.</p>';

        if ($emailService->sendEmail($to, $subject, $message)) {
            return 'Correo enviado exitosamente!';
        } else {
            return 'Error al enviar el correo.';
        }
    }
}