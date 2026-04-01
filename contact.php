<?php
session_start();

// Generate CAPTCHA question and store answer in session
function generateCaptcha() {
    $a = rand(2, 9);
    $b = rand(2, 9);
    $ops = ['+', '-', '×'];
    $op = $ops[array_rand($ops)];
    switch ($op) {
        case '+': $answer = $a + $b; break;
        case '-': $answer = max($a,$b) - min($a,$b); $a = max($a,$b); $b = min($a,$b); break;
        case '×': $answer = $a * $b; break;
    }
    $_SESSION['captcha_answer'] = $answer;
    $_SESSION['captcha_time']   = time();
    return "$a $op $b";
}

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Honeypot: bots fill the hidden field, humans leave it blank
    if (!empty($_POST['website'])) {
        http_response_code(200); // Silently succeed to fool bots
        header('Location: /?sent=1#contact');
        exit;
    }

    // Time check: reject if submitted in under 2 seconds (bot speed)
    $elapsed = time() - (int)($_SESSION['captcha_time'] ?? 0);
    if ($elapsed < 2) {
        $error = 'Form submitted too quickly. Please try again.';
    }

    // CAPTCHA check
    $userAnswer = (int)trim($_POST['captcha_answer'] ?? '');
    $expected   = (int)($_SESSION['captcha_answer'] ?? -999);
    if ($userAnswer !== $expected) {
        $error = 'Incorrect answer to the security question. Please try again.';
    }

    if (empty($error)) {
        $name     = htmlspecialchars(strip_tags(trim($_POST['name']    ?? '')));
        $email    = filter_var(trim($_POST['email']   ?? ''), FILTER_VALIDATE_EMAIL);
        $phone    = htmlspecialchars(strip_tags(trim($_POST['phone']   ?? '')));
        $interest = htmlspecialchars(strip_tags(trim($_POST['interest'] ?? '')));
        $message  = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')));

        if (!$email) {
            $error = 'Please enter a valid email address.';
        } elseif (empty($name) || empty($message)) {
            $error = 'Name and message are required.';
        } else {
            $subject = "Source Vida Enquiry from $name" . ($interest ? " — $interest" : '');
            $body  = "Name:     $name\n";
            $body .= "Email:    $email\n";
            $body .= "Phone:    $phone\n";
            $body .= "Interest: $interest\n\n";
            $body .= "Message:\n$message\n";
            $headers  = "From: noreply@sourcevida.com\r\n";
            $headers .= "Reply-To: $email\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            mail('info@sourcevida.com', $subject, $body, $headers);
            mail('mark@sourcevida.com', $subject, $body, $headers);

            // Clear session captcha to force new one
            unset($_SESSION['captcha_answer'], $_SESSION['captcha_time']);
            header('Location: /?sent=1#contact');
            exit;
        }
    }

    // Regenerate captcha after failed attempt
    $captchaQuestion = generateCaptcha();

} else {
    $captchaQuestion = generateCaptcha();
    $error = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact — Source Vida</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:sans-serif;background:#1a4a2e;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:2rem}
.box{background:rgba(255,255,255,.08);border-radius:8px;padding:2.5rem;max-width:500px;width:100%}
h2{font-family:Georgia,serif;color:#c9a84c;margin-bottom:1.5rem}
.err{background:#c0392b;padding:.75rem 1rem;border-radius:4px;margin-bottom:1rem;font-size:.9rem}
a{color:#c9a84c;font-size:.9rem}
</style>
</head>
<body>
<div class="box">
  <h2>⚠️ Form Error</h2>
  <?php if ($error): ?>
    <div class="err"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <p style="color:rgba(255,255,255,.7);font-size:.9rem;margin-bottom:1.5rem">Please go back and correct the issue.</p>
  <a href="/#contact">← Back to contact form</a>
</div>
</body>
</html>
