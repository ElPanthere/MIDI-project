<?php
// Contact form handler using PHPMailer over SMTP (Gmail).
// Requirements:
//   1) Run: composer require phpmailer/phpmailer
//   2) PHP extensions: openssl, mbstring
// Configuration: fill the SMTP_PASSWORD below (Gmail App Password recommended).

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: contact.html'); exit; }

function sanitize($s) { return htmlspecialchars(trim($s ?? ''), ENT_QUOTES, 'UTF-8'); }

$name    = sanitize($_POST['name'] ?? '');
$email   = sanitize($_POST['email'] ?? '');
$phone   = sanitize($_POST['phone'] ?? '');
$subject = sanitize($_POST['subject'] ?? 'Demande de contact');
$message = sanitize($_POST['message'] ?? '');
$website = trim($_POST['website'] ?? '');
$consent = isset($_POST['consent']);

// Basic validations
if ($website !== '') { header('Location: contact.html'); exit; } // honeypot
if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$message || !$consent) {
  header('Location: contact.html'); exit;
}

// SMTP configuration (Gmail)
$SMTP_HOST = 'smtp.gmail.com';
$SMTP_PORT = 587; // TLS
$SMTP_ENCRYPTION = PHPMailer::ENCRYPTION_STARTTLS;
$SMTP_USER = 'montagespanthere@gmail.com';
$SMTP_PASSWORD = ''; // <<< REMPLIR ICI (Mot de passe d'application Gmail)
$TO_EMAIL = 'montagespanthere@gmail.com'; // destinataire
$TO_NAME  = 'Panthère Informatique';

require __DIR__ . '/vendor/autoload.php';

try {
  $mail = new PHPMailer(true);
  // Server settings
  $mail->isSMTP();
  $mail->Host       = $SMTP_HOST;
  $mail->Port       = $SMTP_PORT;
  $mail->SMTPSecure = $SMTP_ENCRYPTION;
  $mail->SMTPAuth   = true;
  $mail->Username   = $SMTP_USER;
  $mail->Password   = $SMTP_PASSWORD;

  // Charset & language
  $mail->CharSet = 'UTF-8';

  // Recipients
  $mail->setFrom($SMTP_USER, 'Site Web — Panthère Informatique');
  $mail->addAddress($TO_EMAIL, $TO_NAME);
  $mail->addReplyTo($email, $name);

  // Content
  $mail->isHTML(false);
  $mail->Subject = "Contact site — " . ($subject ?: 'Demande');
  $body  = "Nouvelle demande depuis le site :\n\n";
  $body .= "Nom: $name\nEmail: $email\nTéléphone: $phone\nObjet: $subject\n\nMessage:\n$message\n";
  $mail->Body = $body;

  $mail->send();
  header('Location: thanks.html');
  exit;
} catch (Exception $e) {
  // Optionnel: log erreur => file_put_contents(__DIR__.'/mail-error.log', $mail->ErrorInfo.PHP_EOL, FILE_APPEND);
  header('Location: contact.html');
  exit;
}