<?php
/**
 * Source Vida — Contact Form Handler
 * Sends form submissions to info@sourcevida.com
 */

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

// Sanitize inputs
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

$name    = sanitize($_POST['name']    ?? '');
$email   = sanitize($_POST['email']   ?? '');
$phone   = sanitize($_POST['phone']   ?? '');
$message = sanitize($_POST['message'] ?? '');

// Basic validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required.';
}

if (empty($email) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
}

if (!empty($errors)) {
    // Redirect back with error (simple approach)
    header('Location: /?error=' . urlencode(implode(' ', $errors)) . '#contact');
    exit;
}

// Email configuration
$to      = 'info@sourcevida.com';
$subject = 'New Enquiry from Source Vida Website - ' . $name;

$body  = "You have received a new message from the Source Vida website contact form.\n\n";
$body .= "--------------------------------------------\n";
$body .= "Name:    " . $name . "\n";
$body .= "Email:   " . $email . "\n";
$body .= "Phone:   " . (!empty($phone) ? $phone : 'Not provided') . "\n";
$body .= "--------------------------------------------\n\n";
$body .= "Message:\n" . $message . "\n\n";
$body .= "--------------------------------------------\n";
$body .= "Sent from: https://sourcevida.com\n";
$body .= "Time: " . date('Y-m-d H:i:s T') . "\n";

// Headers
$headers  = "From: noreply@sourcevida.com\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email
$sent = mail($to, $subject, $body, $headers);

// Also send copy to mark@ and hello@
if ($sent) {
    mail('mark@sourcevida.com', $subject, $body, $headers);
}

// Redirect with success/failure flag
if ($sent) {
    header('Location: /?sent=1#contact');
} else {
    header('Location: /?error=Email+could+not+be+sent.+Please+try+again.#contact');
}
exit;
