<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Используйте POST-запрос.';
    exit;
}

if (!empty($_POST['website'])) {
    http_response_code(400);
    echo 'Заявка не отправлена.';
    exit;
}

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$email   = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$topic   = trim($_POST['topic']   ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '' || !$email) {
    http_response_code(422);
    echo 'Заполните имя, телефон и корректный e-mail.';
    exit;
}

$config = require __DIR__ . '/config.php';

$toEmail  = $config['to_email'];
$smtpUser = $config['smtp_user'];
$smtpPass = $config['smtp_pass'];
$smtpHost = $config['smtp_host'];
$smtpPort = $config['smtp_port'];
$mail->Port = $smtpPort;

$subject = 'Новая заявка с сайта Prime Drive';

$body  = "Новая заявка с сайта Prime Drive\n";
$body .= "----------------------------------------\n\n";
$body .= "Имя:        {$name}\n";
$body .= "Телефон:    {$phone}\n";
$body .= "E-mail:     {$email}\n";
$body .= "Интересует: " . ($topic !== '' ? $topic : '—') . "\n\n";
$body .= "Комментарий:\n" . ($message !== '' ? $message : '—') . "\n";
$body .= "\n----------------------------------------\n";
$body .= 'Дата: ' . date('d.m.Y H:i') . "\n";
$body .= 'IP:   ' . ($_SERVER['REMOTE_ADDR'] ?? 'неизвестен') . "\n";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = $smtpPort;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($smtpUser, 'Prime Drive');
    $mail->addAddress($toEmail, 'Prime Drive');
    $mail->addReplyTo($email, $name);

    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();

    echo 'Спасибо, заявка успешно отправлена! Мы свяжемся с вами в ближайшее время.';
} catch (Exception $e) {
    http_response_code(500);
    echo 'Ошибка при отправке письма. Попробуйте позже или позвоните нам.';
}