<?php
namespace MyApp\Plugins;

use PHPMailer;

class Mail
{

    public function sendMail($email, $title, $content, $from = 'GameHeTu Network Team', $attachment = '')
    {
        return $this->sendMailBySystem($email, $title, $content, $from, $attachment = '');
    }


    private function sendBySMTP($email, $title, $content, $from = '', $attachment = '')
    {
        global $config;

        $mail = new PHPMailer();
        $mail->isSMTP();                                       // Set mailer to use SMTP
        $mail->Host = $config->env->serviceEmail->smtp;        // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                                // Enable SMTP authentication
        $mail->Username = $config->env->serviceEmail->account;
        $mail->Password = $config->env->serviceEmail->password;
        $mail->SMTPSecure = 'tls';                             // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $config->env->serviceEmail->port;
        $mail->setFrom($config->env->serviceEmail->account, $from);
        if (is_array($email)) {
            foreach ($email as $one) {
                $mail->addAddress($one);
            }
        }
        else {
            $mail->addAddress($email);
        }
        //$mail->addReplyTo('support@gamehetu.com', 'GameHeTu Network Team');
        if ($attachment) {
            $mail->addAttachment($attachment);                 // 附件绝对路径
        }
        $mail->isHTML(true);                                   // Set email format to HTML
        $mail->Subject = $title;
        $mail->Body = $content;
        $mail->AltBody = strip_tags($content);

        if (!$mail->send()) {
            return false;
            dump($mail->ErrorInfo);
            exit;
        }
        return true;
    }


    private function sendMailBySystem($email, $title, $content, $from = '', $attachment = '')
    {
        $mail = <<<EOF
SUBJECT: $title
TO: $email
MIME-VERSION: 1.0
Content-type: text/html

<html>
$content
</html>
EOF;
        $cmd = "/usr/sbin/sendmail -t -F {$from} <<EOF\n$mail\nEOF";
        exec($cmd);
        return true;
    }

}