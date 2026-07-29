<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/lib/PHPMailer/src/Exception.php';
require __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/src/SMTP.php';

const FORM_URL = 'contacto.html';
const MAX_MESSAGE_LENGTH = 5000;

function redirect_with_status(string $status): void
{
    header('Location: ' . FORM_URL . '?estado=' . rawurlencode($status) . '#formulario-contacto', true, 303);
    exit;
}

function field(string $name, int $maximumLength): string
{
    $value = trim((string) filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW));
    $value = preg_replace('/\R/u', ' ', $value) ?? '';

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maximumLength, 'UTF-8');
    }

    return substr($value, 0, $maximumLength);
}

function html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function vcard_value(string $value): string
{
    return str_replace(
        ["\\", ";", ",", "\r", "\n"],
        ["\\\\", "\\;", "\\,", '', "\\n"],
        $value
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método no permitido.');
}

session_start();

$now = time();
$startedAt = (int) filter_input(INPUT_POST, 'inicio', FILTER_VALIDATE_INT);
$honeypot = trim((string) filter_input(INPUT_POST, 'sitio_web', FILTER_UNSAFE_RAW));

if ($honeypot !== '' || $startedAt < 1 || ($now - $startedAt) < 3 || ($now - $startedAt) > 7200) {
    redirect_with_status('invalido');
}

if (isset($_SESSION['grupoecores_last_contact']) && ($now - (int) $_SESSION['grupoecores_last_contact']) < 20) {
    redirect_with_status('limite');
}

$name = field('nombre', 120);
$company = field('empresa', 160);
$email = trim((string) filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL));
$phone = field('telefono', 60);
$type = field('tipo-consulta', 160);
$message = trim((string) filter_input(INPUT_POST, 'mensaje', FILTER_UNSAFE_RAW));

if (function_exists('mb_substr')) {
    $message = mb_substr($message, 0, MAX_MESSAGE_LENGTH, 'UTF-8');
} else {
    $message = substr($message, 0, MAX_MESSAGE_LENGTH);
}

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_status('invalido');
}

$configPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'grupoecores-mail-config.php';

if (!is_file($configPath) || !is_readable($configPath)) {
    error_log('Grupo ECORES contacto: no se encontró la configuración SMTP privada.');
    redirect_with_status('error');
}

$config = require $configPath;
$requiredConfig = ['host', 'port', 'encryption', 'username', 'password', 'recipient'];

if (!is_array($config)) {
    error_log('Grupo ECORES contacto: configuración SMTP inválida.');
    redirect_with_status('error');
}

foreach ($requiredConfig as $key) {
    if (!isset($config[$key]) || $config[$key] === '') {
        error_log('Grupo ECORES contacto: falta un valor en la configuración SMTP.');
        redirect_with_status('error');
    }
}

$safeCompany = $company !== '' ? $company : 'No informada';
$safePhone = $phone !== '' ? $phone : 'No informado';
$safeType = $type !== '' ? $type : 'Consulta general';
$receivedAt = new DateTimeImmutable('now', new DateTimeZone('America/Santiago'));
$subject = '[Sitio Grupo ECORES] ' . $safeType . ' — ' . $name;

$htmlBody = '
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Nueva consulta — Grupo ECORES</title>
</head>
<body style="margin:0;background:#f5f0e7;color:#24332b;font-family:Arial,sans-serif;">
  <div style="max-width:680px;margin:0 auto;padding:28px 18px;">
    <div style="background:#123c28;color:#f5f0e7;padding:26px 30px;">
      <div style="font-size:12px;letter-spacing:1.7px;text-transform:uppercase;opacity:.78;">Sitio web Grupo ECORES</div>
      <h1 style="margin:8px 0 0;font-size:25px;line-height:1.25;">Nueva consulta de contacto</h1>
    </div>
    <div style="background:#ffffff;padding:28px 30px;border:1px solid #ded8cc;">
      <table role="presentation" style="width:100%;border-collapse:collapse;font-size:15px;line-height:1.5;">
        <tr><td style="width:150px;padding:8px 0;color:#607066;">Nombre</td><td style="padding:8px 0;font-weight:bold;">' . html($name) . '</td></tr>
        <tr><td style="padding:8px 0;color:#607066;">Empresa</td><td style="padding:8px 0;">' . html($safeCompany) . '</td></tr>
        <tr><td style="padding:8px 0;color:#607066;">Correo</td><td style="padding:8px 0;"><a href="mailto:' . html($email) . '" style="color:#2f6847;">' . html($email) . '</a></td></tr>
        <tr><td style="padding:8px 0;color:#607066;">Teléfono</td><td style="padding:8px 0;">' . html($safePhone) . '</td></tr>
        <tr><td style="padding:8px 0;color:#607066;">Tipo de consulta</td><td style="padding:8px 0;">' . html($safeType) . '</td></tr>
        <tr><td style="padding:8px 0;color:#607066;">Recibida</td><td style="padding:8px 0;">' . html($receivedAt->format('d-m-Y H:i')) . ' (Chile)</td></tr>
      </table>
      <div style="height:1px;background:#e7e1d7;margin:22px 0;"></div>
      <div style="font-size:12px;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;color:#2f6847;">Mensaje</div>
      <div style="margin-top:10px;font-size:15px;line-height:1.7;white-space:normal;">' . nl2br(html($message)) . '</div>
      <div style="margin-top:26px;">
        <a href="mailto:' . html($email) . '" style="display:inline-block;background:#123c28;color:#ffffff;text-decoration:none;padding:12px 18px;font-weight:bold;">Responder a ' . html($name) . '</a>
      </div>
    </div>
    <p style="margin:14px 4px 0;color:#607066;font-size:12px;line-height:1.5;">Se adjunta una ficha .vcf para guardar este contacto directamente en la libreta de direcciones.</p>
  </div>
</body>
</html>';

$plainBody = "NUEVA CONSULTA — SITIO GRUPO ECORES\n\n"
    . "Nombre: {$name}\n"
    . "Empresa: {$safeCompany}\n"
    . "Correo: {$email}\n"
    . "Teléfono: {$safePhone}\n"
    . "Tipo de consulta: {$safeType}\n"
    . "Recibida: " . $receivedAt->format('d-m-Y H:i') . " (Chile)\n\n"
    . "MENSAJE\n{$message}\n";

$vcard = "BEGIN:VCARD\r\n"
    . "VERSION:3.0\r\n"
    . 'FN:' . vcard_value($name) . "\r\n"
    . ($company !== '' ? 'ORG:' . vcard_value($company) . "\r\n" : '')
    . 'EMAIL;TYPE=INTERNET:' . vcard_value($email) . "\r\n"
    . ($phone !== '' ? 'TEL;TYPE=CELL:' . vcard_value($phone) . "\r\n" : '')
    . 'NOTE:' . vcard_value($safeType . ' — contacto desde grupoecores.cl') . "\r\n"
    . "END:VCARD\r\n";

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) $config['host'];
    $mail->Port = (int) $config['port'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $config['username'];
    $mail->Password = (string) $config['password'];
    $mail->SMTPSecure = strtolower((string) $config['encryption']) === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->setFrom((string) $config['username'], 'Sitio web Grupo ECORES');
    $mail->addAddress((string) $config['recipient'], 'Grupo ECORES');
    $mail->addReplyTo($email, $name);
    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $htmlBody;
    $mail->AltBody = $plainBody;
    $mail->addStringAttachment($vcard, 'contacto-grupo-ecores.vcf', 'base64', 'text/vcard; charset=utf-8');
    $mail->send();

    $_SESSION['grupoecores_last_contact'] = $now;
    redirect_with_status('enviado');
} catch (Exception $exception) {
    error_log('Grupo ECORES contacto: fallo el envío SMTP.');
    redirect_with_status('error');
}
