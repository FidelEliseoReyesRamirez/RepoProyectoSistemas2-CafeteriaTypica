<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordCustom extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
{
    $url = url(route('password.reset', [
        'token' => $this->token,
        'email' => $notifiable->getEmailForPasswordReset(),
    ], false));

    return (new MailMessage)
    ->subject('🔐 Restablece tu contraseña - Cafetería Typica')
    ->greeting('¡Hola!')
    ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
    ->line(new \Illuminate\Support\HtmlString(
        '<div style="text-align: center; margin: 24px 0;">
            <a href="' . $url . '" 
                style="display: inline-block; padding: 12px 24px; background-color: hsl(30, 35%, 35%); 
                       color: #fff; text-decoration: none; font-weight: bold; border-radius: 8px;">
                Restablecer Contraseña
            </a>
        </div>'
    ))
    ->line('Este enlace expirará en 60 minutos.')
    ->line('Si no realizaste esta solicitud, puedes ignorar este correo.')
    ->line('Si estás teniendo problemas para hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:')
    ->line($url)
    ->salutation('Saludos, Cafetería Typica ♥');
    }
}
