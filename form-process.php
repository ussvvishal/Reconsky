<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/vendor/autoload.php';

function get_post_value(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

function respond(string $message, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('Invalid request method.', 405);
}

$firstName = get_post_value('fname');
$lastName = get_post_value('lname');
$email = get_post_value('email');
$phone = get_post_value('phone');
$message = get_post_value('message');

if ($firstName === '' || $lastName === '' || $phone === '' || $message === '') {
    respond('Please complete all required fields.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond('Please enter a valid email address.', 400);
}

$smtpUsername = getenv('SMTP_USERNAME');
$smtpAppPassword = getenv('SMTP_APP_PASSWORD');

if ($smtpUsername === false || $smtpUsername === '' || $smtpAppPassword === false || $smtpAppPassword === '') {
    error_log('Contact form email failed: Gmail SMTP credentials are not configured.');
    respond('Email is not configured yet. Please contact us by phone.', 500);
}

$mailer = new PHPMailer(true);

try {
    $mailer->isSMTP();
    $mailer->Host = 'smtp.gmail.com';
    $mailer->SMTPAuth = true;
    $mailer->Username = $smtpUsername;
    $mailer->Password = $smtpAppPassword;
    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Port = 587;
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom($smtpUsername, 'Website Contact Form');
    $mailer->addAddress('info@reconskyfiberglass.com');
    $mailer->addReplyTo($email, $firstName . ' ' . $lastName);
    $mailer->Subject = 'New contact form submission';
    $mailer->Body = implode("\n", [
        'A new message was submitted through the website contact form.',
        '',
        'Name: ' . $firstName . ' ' . $lastName,
        'Email: ' . $email,
        'Phone: ' . $phone,
        '',
        'Message:',
        $message,
    ]);
    $mailer->send();
} catch (Exception $exception) {
    error_log('Contact form email failed: ' . $mailer->ErrorInfo);
    respond('The message could not be sent. Please check the email configuration and try again.', 500);
}

respond('success');
