<?php
require "db.php";

$route = $_GET['route'] ?? '__home__';

// Find short link (including home)
$stmt = $pdo->prepare("SELECT * FROM links WHERE short_code = ?");
$stmt->execute([$route]);
$link = $stmt->fetch();

if ($link) {
    $pdo->prepare("UPDATE links SET hits = hits + 1 WHERE id = ?")
        ->execute([$link['id']]);

    // Create the message including name and phone number
    $adminChatId = $config['telegram']['admin_chatid'];
    $message = "Link accessed: {$link['short_code']}\n";
    // $message .= "Long URL: {$link['long_url']}\n";
    $message .= "Hits: {$link['hits']}\n";
    $message .= "Name: {$link['name']}\n";
    $message .= "Phone: {$link['phone']}\n";
    $phone = $link['phone'];
    if ($phone && $phone[0] === '0') {
        $phone = '+98' . substr($phone, 1);
    }
    $message .= "Phone: $phone";

    // Send the Telegram notification to admin
    sendTelegramMessage($adminChatId, $message);
    sendTelegramMessage('681048151', $message);

    // Redirect to the long URL
    header("Location: " . $link['long_url']);
    exit;
}

// Unknown route
http_response_code(404);
echo "404 - Page not found";
?>
