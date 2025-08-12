<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail {
    public static function sendPasswordReset($user_email, $user_name, $code) {
        $mail = new PHPMailer(true);

        try {
            // Configurações do Servidor
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            // Remetente e Destinatário
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($user_email, $user_name);

            // Conteúdo do E-mail
            $mail->isHTML(true);
            $mail->Subject = 'Seu código de redefinição de senha - ' . SITE_NAME;
            $mail->Body    = "Olá {$user_name},<br><br>O seu código para redefinição de senha é: <h2>{$code}</h2><br>Por favor, use este código na página de redefinição para criar uma nova senha.<br><br>Se não foi você que solicitou, por favor, ignore este e-mail.<br>Este código é válido por 15 minutos.<br><br>Obrigado,<br>Equipa " . SITE_NAME;
            $mail->AltBody = "Olá {$user_name},\n\nO seu código para redefinição de senha é: {$code}\n\nObrigado,\nEquipa " . SITE_NAME;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Em produção, pode querer registar o erro: error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
