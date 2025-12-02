<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



require 'vendor/autoload.php';

  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();

function sendEmailNotification($toEmail, $subject, $body) {
$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->Host= $_ENV['MAIL_HOST'];
  $mail->SMTPAuth= true;
  $mail->Username= $_ENV['MAIL_USERNAME'];
  $mail->Password= $_ENV['MAIL_PASSWORD'];
  $mail->SMTPSecure= 'tls';
  $mail->Port= 587;

    $mail->setFrom($_ENV['MAIL_FROM'], 'Movie Discussion Board');
    $mail->addAddress($toEmail);

  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body= nl2br($body);

  $mail->send();
    echo " Email sent to $toEmail\n";}
      catch (Exception $e) {
        echo " Email failed: {$mail->ErrorInfo}\n";
    }
}
?>
