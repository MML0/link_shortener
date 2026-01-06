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
    // $message .= "Hits: {$link['hits']}\n";
    $hits_plus_one = $link['hits'] + 1;
    $message .= "Hits: $hits_plus_one\n";
    $message .= "Name: {$link['name']}\n";
    $message .= "Phone: {$link['phone']}\n";
    $phone = $link['phone'];
    if ($phone && $phone[0] === '0') {
        $phone = '+98' . substr($phone, 1);
    }
    $message .= "Phone: $phone";

    // Send the Telegram notification to admin if its not home page
    if ($link['short_code'] != '__home__') {
        sendTelegramMessage($adminChatId, $message);
        sendTelegramMessage('681048151', $message);
        // global daily hits
        $pdo->prepare("
            INSERT INTO daily_hits (hit_date, hits)
            VALUES (CURDATE(), 1)
            ON DUPLICATE KEY UPDATE hits = hits + 1
        ")->execute();
    }

    // Redirect to the long URL
    header("Location: " . $link['long_url']);
    exit;
}

// Unknown route
http_response_code(404);
echo "404 - Page not found";
?>
