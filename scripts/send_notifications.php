<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../config/email.php';

use PHPMailer\PHPMailer\PHPMailer;

// Fetch users who want emails
$stmt = $pdo->prepare("
    SELECT u.id, u.email, u.username 
    FROM users u 
    WHERE u.receive_emails = 1
");
$stmt->execute();
$users = $stmt->fetchAll();

foreach ($users as $user) {
    // Fetch decks needing review
    $stmt = $pdo->prepare("
        SELECT d.title, DATEDIFF(CURDATE(), d.last_studied) AS days_since_studied
        FROM decks d
        WHERE d.user_id = ? 
          AND (d.last_studied < DATE_SUB(CURDATE(), INTERVAL d.reminder_interval DAY)
          AND d.reminder_interval > 0
    ");
    $stmt->execute([$user['id']]);
    $decks = $stmt->fetchAll();

    if (!empty($decks)) {
        // Compose email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->Port = $config['port'];
        $mail->SMTPSecure = $config['encryption'];
        
        $mail->setFrom('noreply@yourdomain.com', 'Flashcards App');
        $mail->addAddress($user['email'], $user['username']);
        $mail->Subject = 'Decks Needing Review';
        
        $body = "<h2>Decks to Review:</h2><ul>";
        foreach ($decks as $deck) {
            $body .= "<li>{$deck['title']} (Last studied {$deck['days_since_studied']} days ago)</li>";
        }
        $body .= "</ul><p><a href='https://yourdomain.com/unsubscribe.php?user={$user['id']}'>Unsubscribe</a></p>";
        
        $mail->isHTML(true);
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
    }
}